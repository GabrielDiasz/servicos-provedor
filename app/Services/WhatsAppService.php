<?php

namespace App\Services;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function enviarMensagemParaTelefone(string $telefone, string $mensagem): bool
    {
        if (! config('services.whatsapp.enabled')) {
            return false;
        }

        $telefone = preg_replace('/\D+/', '', $telefone);

        if ($telefone === '') {
            Log::warning('Nao foi possivel enviar a mensagem direta porque o telefone e invalido.');

            return false;
        }

        try {
            $response = Http::timeout(config('services.whatsapp.timeout', 10))
                ->connectTimeout(config('services.whatsapp.connect_timeout', 3))
                ->when(config('services.whatsapp.token'), fn ($http, $token) => $http->withToken($token))
                ->post(rtrim(config('services.whatsapp.url'), '/').'/send-message', [
                    'phone' => $telefone,
                    'message' => $mensagem,
                ]);

            if ($response->failed()) {
                Log::warning('Falha ao enviar mensagem direta pelo WhatsApp.', [
                    'telefone' => $telefone,
                    'status' => $response->status(),
                    'resposta' => $response->json() ?: $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar mensagem direta pelo WhatsApp.', [
                'telefone' => $telefone,
                'erro' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarOrdemServico(OrdemServico $ordem): bool
    {
        if (! config('services.whatsapp.enabled')) {
            return false;
        }

        $ordem->loadMissing(['tecnico.whatsappGrupo', 'atendente']);

        if (! $ordem->tecnico?->whatsappGrupo?->grupo_id) {
            Log::warning('Ordem de servico sem grupo do tecnico para envio via WhatsApp.', [
                'ordem_id' => $ordem->id,
                'tecnico_id' => $ordem->tecnico_id,
            ]);

            return false;
        }

        try {
            $this->workerConsole("OS #{$ordem->id}: tentando enviar a imagem do endereço.");

            if (! $this->enviarImagemEndereco($ordem)) {
                return false;
            }

            $mensagens = $this->mensagensOrdemServico($ordem);
            $totalMensagens = count($mensagens);
            $this->workerConsole("OS #{$ordem->id}: enviando mensagens de texto no WhatsApp.", [
                'total' => $totalMensagens,
            ]);

            foreach ($mensagens as $index => $mensagem) {
                $passo = $index + 1;
                $this->workerConsole("OS #{$ordem->id}: enviando mensagem {$passo}/{$totalMensagens}.");

                $response = Http::timeout(config('services.whatsapp.timeout', 10))
                    ->connectTimeout(config('services.whatsapp.connect_timeout', 3))
                    ->when(config('services.whatsapp.token'), fn ($http, $token) => $http->withToken($token))
                    ->post(rtrim(config('services.whatsapp.url'), '/').'/send-message', [
                        'group_id' => $ordem->tecnico->whatsappGrupo->grupo_id,
                        'message' => $mensagem,
                    ]);

                if ($response->failed()) {
                    Log::warning('Falha ao enviar ordem de servico pelo WhatsApp.', [
                        'ordem_id' => $ordem->id,
                        'tecnico_id' => $ordem->tecnico_id,
                        'status' => $response->status(),
                        'resposta' => $response->json() ?: $response->body(),
                    ]);

                    $this->workerConsole("OS #{$ordem->id}: falha ao enviar a mensagem {$passo}/{$totalMensagens}.", [
                        'status' => $response->status(),
                    ], true);

                    return false;
                }
            }

            $this->workerConsole("OS #{$ordem->id}: todas as mensagens do WhatsApp foram enviadas.");

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar ordem de servico pelo WhatsApp.', [
                'ordem_id' => $ordem->id,
                'tecnico_id' => $ordem->tecnico_id,
                'erro' => $exception->getMessage(),
            ]);

            $this->workerConsole("OS #{$ordem->id}: falha ao enviar ordem de servico pelo WhatsApp.", [
                'erro' => $exception->getMessage(),
            ], true);

            return false;
        }
    }

    private function mensagensOrdemServico(OrdemServico $ordem): array
    {
        $tipo = OrdemServico::TIPOS[$ordem->tipo_servico] ?? $ordem->tipo_servico;
        $observacao = $this->observacaoMensagem($ordem, $tipo);
        $servicosCompletos = ['instalacao', 'reativacao', 'mudanca_endereco', 'upgrade'];
        $servicosComCtoPorta = ['mudanca_endereco'];
        $login = $this->loginPppoe($ordem);
        $mensagemCtoPorta = $this->mensagemCtoPorta($ordem);

        if (in_array($ordem->tipo_servico, $servicosCompletos, true)) {
            return collect([
                $this->mensagemDadosCliente($ordem),
                $login,
                $ordem->sgp_pppoe_senha,
                in_array($ordem->tipo_servico, $servicosComCtoPorta, true) ? $mensagemCtoPorta : null,
                $this->telefonePrincipal($ordem),
            ])->filter()->values()->all();
        }

        return collect([
            $login,
            $ordem->sgp_pppoe_senha,
            $mensagemCtoPorta,
            $this->telefonePrincipal($ordem),
        ])->filter()->values()->all();
    }

    private function mensagemDadosCliente(OrdemServico $ordem): string
    {
        $telefones = $this->telefonesFormatados($ordem);
        $plano = $ordem->sgp_plano;
        $valorPlano = $this->valorPlano($plano);

        return collect([
            "Titular: {$ordem->cliente_nome}",
            $ordem->sgp_data_nascimento ? 'Data de nascimento: '.$ordem->sgp_data_nascimento->format('d/m/Y') : null,
            $ordem->sgp_cpf_cnpj ? "CPF: {$ordem->sgp_cpf_cnpj}" : null,
            $telefones ? 'Tel :        '.implode('        ', $telefones) : null,
            $plano ? 'Nome do plano: '.$this->planoMensagem($plano) : null,
            $plano ? 'velocidade kbps: '.$this->velocidadeKbps($plano) : null,
            '',
            $valorPlano ? 'Valor do plano: '.$valorPlano : null,
            $ordem->sgp_vencimento ? "Vencimento: {$ordem->sgp_vencimento}" : null,
        ])->filter(fn ($linha) => $linha !== null)->implode("\n");
    }

    private function mensagemEndereco(OrdemServico $ordem): ?string
    {
        $endereco = $this->enderecoSgp($ordem);

        if (! $endereco) {
            return $ordem->sgp_endereco;
        }

        return collect([
            ! empty($endereco['logradouro']) ? "Endereço: {$endereco['logradouro']}" : null,
            isset($endereco['numero']) && $endereco['numero'] !== '' ? "Número: {$endereco['numero']}" : null,
            ! empty($endereco['bairro']) ? "Bairro: {$endereco['bairro']}" : null,
            ! empty($endereco['complemento']) ? "Complemento: {$endereco['complemento']}" : null,
            ! empty($endereco['pontoreferencia']) ? "Referência: {$endereco['pontoreferencia']}" : null,
            ! empty($endereco['ponto_referencia']) ? "Referência: {$endereco['ponto_referencia']}" : null,
        ])->filter()->implode("\n");
    }

    private function mensagemServicoEndereco(OrdemServico $ordem, string $tipo, string $observacao = ''): ?string
    {
        $linhaInicial = $this->linhaInicialServico($ordem, $tipo, $observacao);

        return collect([
            $linhaInicial,
            '',
            $this->mensagemEndereco($ordem),
        ])->filter(fn ($linha) => $linha !== null)->implode("\n");
    }

    private function observacaoMensagem(OrdemServico $ordem, string $tipo): string
    {
        $observacao = trim((string) $ordem->observacao);

        if ($observacao === '') {
            return '';
        }

        return $this->normalizarTextoComparacao($observacao) === $this->normalizarTextoComparacao($tipo)
            ? ''
            : $observacao;
    }

    private function linhaInicialServico(OrdemServico $ordem, string $tipo, string $observacao = ''): string
    {
        $observacaoNormalizada = trim($observacao);

        if ($ordem->tipo_servico === 'upgrade') {
            return mb_strtoupper($tipo).' - '.$observacaoNormalizada;
        }

        if ($observacaoNormalizada !== '') {
            return mb_strtoupper($tipo).' - '.$observacaoNormalizada;
        }

        return match ($ordem->tipo_servico) {
            'reparo' => 'REPARO - OSCILAÇÃO',
            'troca_senha' => mb_strtoupper($tipo),
            default => mb_strtoupper($tipo),
        };
    }

    private function normalizarTextoComparacao(string $texto): string
    {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $normalizado = mb_strtoupper(trim($normalizado ?: $texto));

        return preg_replace('/[^A-Z0-9]+/', '', $normalizado) ?: '';
    }

    private function mensagemCtoPorta(OrdemServico $ordem): ?string
    {
        $onu = $this->onuSgp($ordem);
        $cto = data_get($onu, 'splitter.nome');
        $porta = data_get($onu, 'splitter.porta');

        if (! $cto) {
            return null;
        }

        return 'CTO: '.$cto.' Porta: '.($porta ?: 'sem porta');
    }

    private function loginPppoe(OrdemServico $ordem): ?string
    {
        return $ordem->sgp_pppoe_login ? mb_strtoupper($ordem->sgp_pppoe_login) : null;
    }

    private function planoMensagem(?string $plano): ?string
    {
        $planoNormalizado = $this->normalizarPlanoParaExibicao($plano);

        return $planoNormalizado ? preg_replace('/\s+/', '', $planoNormalizado) : null;
    }

    private function velocidadeKbps(?string $plano): ?string
    {
        $plano = mb_strtoupper((string) $plano);

        return match (true) {
            str_contains($plano, '50') => '52200',
            str_contains($plano, '300') => '310200',
            str_contains($plano, '500') => '500200',
            str_contains($plano, '600') => '500200',
            str_contains($plano, '700') => '700200',
            default => '',
        };
    }

    private function telefonePrincipal(OrdemServico $ordem): ?string
    {
        $telefone = $ordem->cliente_telefone ?: ($this->telefones($ordem)[0] ?? null);

        return $telefone ? preg_replace('/\D+/', '', $telefone) : null;
    }

    private function telefonesFormatados(OrdemServico $ordem): array
    {
        return collect($this->telefones($ordem))
            ->map(fn ($telefone) => $this->formatarTelefone($telefone))
            ->filter()
            ->values()
            ->all();
    }

    private function telefones(OrdemServico $ordem): array
    {
        $dados = $ordem->sgp_dados ?? [];
        $telefones = [
            $ordem->cliente_telefone,
            ...data_get($dados, 'contatos.celulares', []),
            ...data_get($dados, 'contatos.telefones', []),
            ...data_get($dados, 'telefones', []),
        ];

        return collect($telefones)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function formatarTelefone(string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', $telefone);

        if (strlen($digits) === 11) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        }

        if (strlen($digits) === 10) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 4).'-'.substr($digits, 6);
        }

        return $telefone ?: null;
    }

    private function valorPlano(?string $plano): ?string
    {
        $planoNormalizado = $this->normalizarPlanoParaValor($plano);

        if ($planoNormalizado === null) {
            return null;
        }

        return match ($planoNormalizado) {
            50 => '59,90',
            300 => '84,90',
            500, 600 => '99,90',
            700 => '119,90',
            default => null,
        };
    }

    private function normalizarPlanoParaValor(?string $plano): ?int
    {
        if (! is_string($plano) || trim($plano) === '') {
            return null;
        }

        if (! preg_match('/(\d{2,3})/', $plano, $matches)) {
            return null;
        }

        $velocidade = (int) $matches[1];

        return match (true) {
            $velocidade === 50 => 50,
            $velocidade === 600 => 500,
            in_array($velocidade, [300, 500, 700], true) => $velocidade,
            default => null,
        };
    }

    private function normalizarPlanoParaExibicao(?string $plano): ?string
    {
        $planoNormalizado = $this->normalizarPlanoParaValor($plano);

        if ($planoNormalizado === null) {
            return is_string($plano) ? trim($plano) : null;
        }

        return match ($planoNormalizado) {
            50 => '50M',
            300 => '300M',
            500 => '500M',
            700 => '700M',
            default => null,
        };
    }

    private function enderecoSgp(OrdemServico $ordem): ?array
    {
        $dados = $ordem->sgp_dados ?? [];

        return data_get($dados, 'contratos.0.servicos.0.endereco')
            ?? data_get($dados, 'contratos.0.endereco')
            ?? data_get($dados, 'endereco');
    }

    private function enviarImagemEndereco(OrdemServico $ordem): bool
    {
        if (! config('services.sgp.enabled')) {
            return false;
        }

        if (! config('services.sgp.web_username') || ! config('services.sgp.web_password')) {
            Log::warning('Nao foi possivel capturar a imagem do endereco porque as credenciais web do SGP nao estao configuradas.', [
                'ordem_id' => $ordem->id,
            ]);

            return false;
        }

        $clienteUrl = $this->urlClienteSgpParaCaptura($ordem);

        if (! $clienteUrl) {
            Log::warning('Nao foi possivel capturar a imagem do endereco porque a OS nao possui link nem ID de cliente do SGP.', [
                'ordem_id' => $ordem->id,
            ]);

            return false;
        }

        try {
            $caption = $this->linhaInicialServico(
                $ordem,
                OrdemServico::TIPOS[$ordem->tipo_servico] ?? $ordem->tipo_servico,
                $this->observacaoMensagem($ordem, OrdemServico::TIPOS[$ordem->tipo_servico] ?? $ordem->tipo_servico)
            );

            $response = Http::timeout(config('services.whatsapp.image_timeout', 120))
                ->connectTimeout(config('services.whatsapp.connect_timeout', 3))
                ->when(config('services.whatsapp.token'), fn ($http, $token) => $http->withToken($token))
                ->post(rtrim(config('services.whatsapp.url'), '/').'/send-sgp-address', [
                    'group_id' => $ordem->tecnico->whatsappGrupo->grupo_id,
                    'base_url' => config('services.sgp.url'),
                    'cliente_url' => $clienteUrl,
                    'username' => config('services.sgp.web_username'),
                    'password' => config('services.sgp.web_password'),
                    'caption' => $caption,
                ]);

            if ($response->failed()) {
                Log::warning('Falha ao gerar ou enviar a imagem do endereco pelo WhatsApp.', [
                    'ordem_id' => $ordem->id,
                    'tecnico_id' => $ordem->tecnico_id,
                    'status' => $response->status(),
                    'resposta' => $response->json() ?: $response->body(),
                ]);

                $this->workerConsole("OS #{$ordem->id}: não foi possível enviar a imagem do endereço.", [
                    'status' => $response->status(),
                    'resposta' => $response->json() ?: $response->body(),
                ], true);

                return false;
            }

            $this->workerConsole("OS #{$ordem->id}: imagem do endereço enviada com sucesso.");

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Falha ao gerar ou enviar a imagem do endereco pelo WhatsApp.', [
                'ordem_id' => $ordem->id,
                'tecnico_id' => $ordem->tecnico_id,
                'erro' => $exception->getMessage(),
            ]);

            $this->workerConsole("OS #{$ordem->id}: falha ao gerar ou enviar a imagem do endereço.", [
                'erro' => $exception->getMessage(),
            ], true);

            return false;
        }
    }

    private function urlClienteSgpParaCaptura(OrdemServico $ordem): ?string
    {
        if (filled($ordem->sgp_cliente_link)) {
            return $ordem->sgp_cliente_link;
        }

        if (filled($ordem->sgp_cliente_id)) {
            return rtrim((string) config('services.sgp.url'), '/').'/admin/cliente/'.trim((string) $ordem->sgp_cliente_id).'/edit/';
        }

        return null;
    }

    private function onuSgp(OrdemServico $ordem): ?array
    {
        return data_get($ordem->sgp_dados ?? [], 'contratos.0.servicos.0.onu');
    }

    private function workerConsole(string $message, array $context = [], bool $isError = false): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $prefix = $isError ? '[ERRO]' : '[INFO]';
        $line = $prefix.' WhatsApp > '.$message;

        if ($context !== []) {
            $line .= ' '.json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        echo $line.PHP_EOL;
        flush();
    }
}
