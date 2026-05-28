<?php

namespace App\Services;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
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
            foreach ($this->mensagensOrdemServico($ordem) as $mensagem) {
                $response = Http::timeout(config('services.whatsapp.timeout', 10))
                    ->withToken(config('services.whatsapp.token'))
                    ->post(rtrim(config('services.whatsapp.url'), '/') . '/send-message', [
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

                    return false;
                }
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar ordem de servico pelo WhatsApp.', [
                'ordem_id' => $ordem->id,
                'tecnico_id' => $ordem->tecnico_id,
                'erro' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function mensagensOrdemServico(OrdemServico $ordem): array
    {
        $tipo = OrdemServico::TIPOS[$ordem->tipo_servico] ?? $ordem->tipo_servico;
        $observacao = $this->observacaoMensagem($ordem, $tipo);
        $servicosCompletos = ['instalacao', 'reativacao', 'mudanca_endereco', 'upgrade'];
        $login = $this->loginPppoe($ordem);

        if (in_array($ordem->tipo_servico, $servicosCompletos, true)) {
            return collect([
                $this->mensagemServicoEndereco($ordem, $tipo, $observacao),
                $login,
                $ordem->sgp_pppoe_senha,
                $this->mensagemDadosCliente($ordem),
                $this->telefonePrincipal($ordem),
            ])->filter()->values()->all();
        }

        return collect([
            $this->mensagemServicoEndereco($ordem, $tipo, $observacao),
            $login,
            $ordem->sgp_pppoe_senha,
            $this->mensagemCtoPorta($ordem),
            $this->telefonePrincipal($ordem),
        ])->filter()->values()->all();
    }

    private function mensagemDadosCliente(OrdemServico $ordem): string
    {
        $telefones = $this->telefonesFormatados($ordem);

        return collect([
            "Titular: {$ordem->cliente_nome}",
            $ordem->sgp_data_nascimento ? 'Data de nascimento: ' . $ordem->sgp_data_nascimento->format('d/m/Y') : null,
            $ordem->sgp_cpf_cnpj ? "CPF: {$ordem->sgp_cpf_cnpj}" : null,
            $telefones ? 'Tel :        ' . implode('        ', $telefones) : null,
            $ordem->sgp_plano ? 'Nome do plano ' . $this->planoMensagem($ordem->sgp_plano) : null,
            $ordem->sgp_plano ? 'velocidade kbps: ' . $this->velocidadeKbps($ordem->sgp_plano) : null,
            '',
            $this->valorPlano($ordem) ? 'Valor do plano: ' . $this->valorPlano($ordem) : null,
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
            return mb_strtoupper($tipo) . ' - ' . $observacaoNormalizada;
        }

        if ($observacaoNormalizada !== '') {
            return mb_strtoupper($tipo) . ' - ' . $observacaoNormalizada;
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

        return 'CTO: ' . $cto . ' Porta: ' . ($porta ?: 'sem porta');
    }

    private function loginPppoe(OrdemServico $ordem): ?string
    {
        return $ordem->sgp_pppoe_login ? mb_strtoupper($ordem->sgp_pppoe_login) : null;
    }

    private function planoMensagem(?string $plano): ?string
    {
        return $plano ? preg_replace('/\s+/', '', $plano) : null;
    }

    private function velocidadeKbps(?string $plano): ?string
    {
        $plano = mb_strtoupper((string) $plano);

        return match (true) {
            str_contains($plano, '50') => '52200',
            str_contains($plano, '300') => '310200',
            str_contains($plano, '500') => '500200',
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
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
        }

        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
        }

        return $telefone ?: null;
    }

    private function valorPlano(OrdemServico $ordem): ?string
    {
        $dados = $ordem->sgp_dados ?? [];
        $valor = data_get($dados, 'contratos.0.valor')
            ?? data_get($dados, 'contratos.0.valorPlano')
            ?? data_get($dados, 'contratos.0.plano.valor')
            ?? data_get($dados, 'contratoValor')
            ?? data_get($dados, 'servico_valor');

        if ($valor === null || $valor === '') {
            return null;
        }

        return is_numeric($valor) ? number_format((float) $valor, 2, ',', '') : (string) $valor;
    }

    private function enderecoSgp(OrdemServico $ordem): ?array
    {
        $dados = $ordem->sgp_dados ?? [];

        return data_get($dados, 'contratos.0.servicos.0.endereco')
            ?? data_get($dados, 'contratos.0.endereco')
            ?? data_get($dados, 'endereco');
    }

    private function onuSgp(OrdemServico $ordem): ?array
    {
        return data_get($ordem->sgp_dados ?? [], 'contratos.0.servicos.0.onu');
    }
}

