<?php

namespace App\Services;

use App\Models\OrdemServico;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SgpService
{
    public function sincronizarOcorrenciaEOrdemServico(OrdemServico $ordem, ?string $usuarioResponsavel = null, ?string $usuarioEmail = null): array
    {
        if (! config('services.sgp.enabled')) {
            return [
                'status' => 'skipped',
                'message' => 'Integração com o SGP desativada.',
            ];
        }

        if (! config('services.sgp.web_username') || ! config('services.sgp.web_password')) {
            return [
                'status' => 'skipped',
                'message' => 'Credenciais web do SGP não configuradas.',
            ];
        }

        $ordem->loadMissing('tecnico');

        if (! $ordem->sgp_cliente_id || ! $ordem->sgp_contrato_id) {
            return [
                'status' => 'skipped',
                'message' => 'Dados do SGP insuficientes para criar ocorrência e OS.',
            ];
        }

        $tecnicoResponsavelLabel = $this->resolverTecnicoResponsavelLabel($ordem);

        if (! $tecnicoResponsavelLabel) {
            return [
                'status' => 'skipped',
                'message' => 'Selecione um técnico com correspondência no SGP antes de enviar.',
            ];
        }

        $cookieJar = new CookieJar;

        try {
            $ocorrenciaPath = "/admin/atendimento/cliente/{$ordem->sgp_cliente_id}/ocorrencia/add/";
            $this->autenticarPortalWeb($cookieJar, $ocorrenciaPath);

            $ocorrenciaHtml = $this->carregarPaginaWeb($cookieJar, $ocorrenciaPath);
            $numeroOcorrencia = $this->extrairCampoHtml($ocorrenciaHtml, 'numero');

            if (! $numeroOcorrencia) {
                throw new \RuntimeException('Não foi possível obter o número da ocorrência no SGP.');
            }

            $usuarioSgp = $this->resolverUsuarioResponsavelSgp($ocorrenciaHtml, $usuarioResponsavel, $usuarioEmail);
            $dataAgendamento = now()->format('d/m/Y H:i:s');
            $conteudoOcorrencia = $this->conteudoOcorrenciaSgp($ordem);

            $ocorrenciaPayload = array_filter([
                'csrfmiddlewaretoken' => $this->extrairCampoHtml($ocorrenciaHtml, 'csrfmiddlewaretoken'),
                'dpb_token' => $this->extrairCampoHtml($ocorrenciaHtml, 'dpb_token'),
                'numero' => $numeroOcorrencia,
                'clientecontrato' => (string) $ordem->sgp_contrato_id,
                'servico' => $this->resolverOpcaoPorTexto($ocorrenciaHtml, 'servico', 'Internet', null),
                'tipo' => $this->resolverOpcaoPorTexto($ocorrenciaHtml, 'tipo', $this->mapaTipoOcorrencia($ordem), '18'),
                'metodo' => $this->resolverOpcaoPorTexto($ocorrenciaHtml, 'metodo', 'WhatsApp', '5'),
                'origem' => $this->resolverOpcaoPorTexto($ocorrenciaHtml, 'origem', 'WhatsApp', '5'),
                'status' => $this->resolverOpcaoPorTexto($ocorrenciaHtml, 'status', 'Aberta', '0'),
                'usuario_responsavel' => $usuarioSgp,
                'usuariorresponsavel' => $usuarioSgp,
                'responsavel' => $usuarioSgp,
                'conteudo' => $conteudoOcorrencia,
                'observacoes' => '',
                'data_agendamento' => $dataAgendamento,
                'os' => 'on',
                'protocolo_sms_2' => '',
                'encerra_os' => 'on',
                'gateway_sms' => '',
                'gateway_email' => '',
            ], static fn ($value) => $value !== null);

            $ocorrenciaResponse = $this->enviarFormularioWeb($cookieJar, $ocorrenciaPath, $ocorrenciaPayload);

            if ($ocorrenciaResponse->status() !== 302) {
                throw new \RuntimeException('O SGP não redirecionou após criar a ocorrência.');
            }

            $osPath = $this->normalizarCaminhoSgp($ocorrenciaResponse->header('Location') ?: '');
            $ocorrenciaId = $this->extrairIdDoCaminhoSgp($osPath);

            if (! $ocorrenciaId) {
                throw new \RuntimeException('Não foi possível identificar o ID da ocorrência criada no SGP.');
            }

            $osHtml = $this->carregarPaginaWeb($cookieJar, $osPath);
            $tecnicoResponsavel = $this->resolverTecnicoResponsavelSgp($ordem, $osHtml);

            if (! $tecnicoResponsavel) {
                throw new \RuntimeException('Não foi possível mapear o técnico responsável no SGP.');
            }
            $osPayload = array_filter([
                'csrfmiddlewaretoken' => $this->extrairCampoHtml($osHtml, 'csrfmiddlewaretoken'),
                'dpb_token' => $this->extrairCampoHtml($osHtml, 'dpb_token'),
                'tipoos' => $this->resolverOpcaoPorTexto($osHtml, 'tipoos', 'Externa', '1'),
                'motivoos' => $this->resolverMotivoOs($ordem, $osHtml),
                'prioridade' => $this->resolverPrioridadeOs($ordem, $osHtml),
                'data_agendamento' => $dataAgendamento,
                'data_previsao_finalizacao' => '',
                'setor' => '',
                'responsavel' => $tecnicoResponsavel,
                'tecnico_responsavel' => $tecnicoResponsavel,
                'conteudo' => $conteudoOcorrencia,
                'servicoprestado' => '',
                'anotacao' => '',
                'anotacao_publica' => '',
                'veiculo' => '',
                'veiculo_km' => '',
                'sistema_sync' => '',
                'gateway_sms' => '',
                'encerra_ocorrencia' => 'on',
            ], static fn ($value) => $value !== null);

            $osResponse = $this->enviarFormularioWeb($cookieJar, $osPath, $osPayload);

            if ($osResponse->status() !== 302) {
                throw new \RuntimeException('O SGP não redirecionou após criar a ordem de serviço.');
            }

            $finalPath = $this->normalizarCaminhoSgp($osResponse->header('Location') ?: "/admin/atendimento/ocorrencia/{$ocorrenciaId}/edit/#os");
            $finalHtml = $this->carregarPaginaWeb($cookieJar, $finalPath);
            $osNumero = $this->extrairNumeroOs($finalHtml);

            return [
                'status' => 'synced',
                'message' => 'Ocorrência e OS criadas no SGP com sucesso.',
                'ocorrencia_numero' => $numeroOcorrencia,
                'os_numero' => $osNumero,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Falha ao sincronizar ocorrência/OS no SGP.', [
                'ordem_id' => $ordem->id,
                'erro' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function consultarClientePorLink(?string $link): ?array
    {
        $link = trim((string) $link);

        if ($link === '') {
            return null;
        }

        $clienteId = $this->extrairClienteIdDoLink($link);

        if ($clienteId) {
            return $this->consultarClientePorId($clienteId);
        }

        if (preg_match('~^\d+$~', $link)) {
            return $this->consultarClientePorId($link)
                ?? $this->consultarClientePorContrato($link);
        }

        return null;
    }

    public function consultarClientePorId(string|int $clienteId): ?array
    {
        if (! config('services.sgp.enabled')) {
            return null;
        }

        $cacheKey = 'sgp:cliente:id:'.trim((string) $clienteId);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($clienteId) {
            try {
                $response = Http::timeout(config('services.sgp.timeout', 15))
                    ->connectTimeout(config('services.sgp.connect_timeout', 5))
                    ->withHeaders(['Expect' => ''])
                    ->asForm()
                    ->post(rtrim(config('services.sgp.url'), '/').'/api/ura/clientes/', [
                        'app' => config('services.sgp.app'),
                        'token' => config('services.sgp.token'),
                        'id' => $clienteId,
                        'cliente_id' => $clienteId,
                    ]);

                if ($response->failed()) {
                    Log::warning('Falha ao consultar cliente por ID no SGP.', [
                        'cliente_id' => $clienteId,
                        'status' => $response->status(),
                        'resposta' => $response->json() ?: $response->body(),
                    ]);

                    return null;
                }

                $cliente = Arr::first($response->json('clientes', []));

                return $cliente ? $this->normalizarCliente($cliente) : null;
            } catch (\Throwable $exception) {
                Log::warning('Erro ao consultar cliente por ID no SGP.', [
                    'cliente_id' => $clienteId,
                    'erro' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    public function consultarClientePorContrato(string|int $contrato): ?array
    {
        if (! config('services.sgp.enabled')) {
            return null;
        }

        $cacheKey = 'sgp:cliente:contrato:'.trim((string) $contrato);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($contrato) {
            try {
                $response = Http::timeout(config('services.sgp.timeout', 15))
                    ->connectTimeout(config('services.sgp.connect_timeout', 5))
                    ->asForm()
                    ->post(rtrim(config('services.sgp.url'), '/').'/api/ura/consultacliente/', [
                        'app' => config('services.sgp.app'),
                        'token' => config('services.sgp.token'),
                        'contrato' => $contrato,
                    ]);

                if ($response->failed()) {
                    Log::warning('Falha ao consultar cliente no SGP.', [
                        'contrato' => $contrato,
                        'status' => $response->status(),
                        'resposta' => $response->json() ?: $response->body(),
                    ]);

                    return null;
                }

                $contratoSgp = Arr::first($response->json('contratos', []));

                return $contratoSgp ? $this->normalizarContrato($contratoSgp) : null;
            } catch (\Throwable $exception) {
                Log::warning('Erro ao consultar cliente no SGP.', [
                    'contrato' => $contrato,
                    'erro' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    public function extrairClienteIdDoLink(?string $link): ?string
    {
        if (! $link) {
            return null;
        }

        if (preg_match('~/cliente/(\d+)/edit/?~', $link, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizarCliente(array $cliente): array
    {
        $contrato = Arr::first($cliente['contratos'] ?? []);
        $servico = Arr::first($contrato['servicos'] ?? []);
        $endereco = $servico['endereco'] ?? $contrato['endereco'] ?? $cliente['endereco'] ?? [];
        $telefones = array_values(array_filter([
            ...($cliente['contatos']['celulares'] ?? []),
            ...($cliente['contatos']['telefones'] ?? []),
        ]));

        $plano = $servico['plano']['descricao'] ?? $servico['plano'] ?? null;
        if (is_array($plano)) {
            $plano = $plano['descricao'] ?? null;
        }

        return [
            'cliente_nome' => $cliente['nome'] ?? null,
            'cliente_telefone' => $telefones[0] ?? null,
            'bairro' => $endereco['bairro'] ?? null,
            'sgp_cliente_id' => $cliente['id'] ?? null,
            'sgp_contrato_id' => $contrato['id'] ?? null,
            'sgp_cpf_cnpj' => $cliente['cpfcnpj'] ?? null,
            'sgp_data_nascimento' => $cliente['dataNascimento'] ?? null,
            'sgp_plano' => $plano,
            'sgp_vencimento' => $contrato['vencimento'] ?? null,
            'sgp_pppoe_login' => $servico['login'] ?? $contrato['contratoCentralLogin'] ?? null,
            'sgp_pppoe_senha' => $servico['senha'] ?? $contrato['contratoCentralSenha'] ?? null,
            'sgp_endereco' => $this->formatarEndereco($endereco),
            'sgp_dados' => $cliente,
        ];
    }

    private function normalizarContrato(array $contrato): array
    {
        $telefones = array_values(array_filter($contrato['telefones'] ?? []));
        $logradouro = $this->formatarEndereco([
            'logradouro' => $contrato['endereco_logradouro'] ?? null,
            'numero' => $contrato['endereco_numero'] ?? null,
            'bairro' => $contrato['endereco_bairro'] ?? null,
            'cidade' => $contrato['endereco_cidade'] ?? null,
            'uf' => $contrato['endereco_uf'] ?? null,
        ]);

        return [
            'cliente_nome' => $contrato['razaoSocial'] ?? null,
            'cliente_telefone' => $telefones[0] ?? null,
            'bairro' => $contrato['endereco_bairro'] ?? null,
            'sgp_cliente_id' => $contrato['clienteId'] ?? null,
            'sgp_contrato_id' => $contrato['contratoId'] ?? null,
            'sgp_cpf_cnpj' => $contrato['cpfCnpj'] ?? null,
            'sgp_data_nascimento' => $contrato['dataNascimento'] ?? null,
            'sgp_plano' => $contrato['servico_plano'] ?? $contrato['planointernet'] ?? null,
            'sgp_vencimento' => $contrato['cobVencimento'] ?? null,
            'sgp_pppoe_login' => $contrato['servico_login'] ?? $contrato['contratoCentralLogin'] ?? null,
            'sgp_pppoe_senha' => $contrato['servico_senha'] ?? $contrato['contratoCentralSenha'] ?? null,
            'sgp_endereco' => $logradouro ?: null,
            'sgp_dados' => $contrato,
        ];
    }

    private function formatarEndereco(array $endereco): ?string
    {
        $logradouro = trim(implode(', ', array_filter([
            $endereco['logradouro'] ?? null,
            $endereco['numero'] ?? null,
            $endereco['bairro'] ?? null,
            $endereco['cidade'] ?? null,
            $endereco['uf'] ?? null,
        ])));

        $extras = trim(implode(', ', array_filter([
            isset($endereco['complemento']) && $endereco['complemento'] !== '' ? 'Complemento: '.$endereco['complemento'] : null,
            isset($endereco['pontoreferencia']) && $endereco['pontoreferencia'] !== '' ? 'Referência: '.$endereco['pontoreferencia'] : null,
            isset($endereco['ponto_referencia']) && $endereco['ponto_referencia'] !== '' ? 'Referência: '.$endereco['ponto_referencia'] : null,
        ])));

        return trim(implode(', ', array_filter([$logradouro, $extras]))) ?: null;
    }

    private function autenticarPortalWeb(CookieJar $cookies, string $nextPath): void
    {
        $baseUrl = rtrim(config('services.sgp.url'), '/');
        $loginUrl = $baseUrl.'/accounts/login/?next='.$nextPath;

        $loginPage = Http::timeout(config('services.sgp.timeout', 15))
            ->connectTimeout(config('services.sgp.connect_timeout', 5))
            ->withOptions(['cookies' => $cookies])
            ->get($loginUrl);

        if ($loginPage->failed()) {
            throw new \RuntimeException('Não foi possível abrir a tela de login do SGP.');
        }

        $csrf = $this->extrairCampoHtml($loginPage->body(), 'csrfmiddlewaretoken');

        if (! $csrf) {
            throw new \RuntimeException('CSRF do login do SGP não encontrado.');
        }

        $response = Http::timeout(config('services.sgp.timeout', 15))
            ->connectTimeout(config('services.sgp.connect_timeout', 5))
            ->withOptions([
                'cookies' => $cookies,
                'allow_redirects' => false,
            ])
            ->withHeaders([
                'Origin' => $baseUrl,
                'Referer' => $loginUrl,
            ])
            ->asForm()
            ->post($loginUrl, [
                'csrfmiddlewaretoken' => $csrf,
                'username' => config('services.sgp.web_username'),
                'password' => config('services.sgp.web_password'),
                'next' => $nextPath,
            ]);

        if (! in_array($response->status(), [200, 302], true)) {
            throw new \RuntimeException('Falha ao autenticar no SGP.');
        }
    }

    private function carregarPaginaWeb(CookieJar $cookies, string $path): string
    {
        $response = Http::timeout(config('services.sgp.timeout', 15))
            ->connectTimeout(config('services.sgp.connect_timeout', 5))
            ->withOptions(['cookies' => $cookies])
            ->get($this->montarUrlSgp($path));

        if ($response->failed()) {
            throw new \RuntimeException('Falha ao carregar página do SGP: '.$path);
        }

        return $response->body();
    }

    private function enviarFormularioWeb(CookieJar $cookies, string $path, array $payload)
    {
        $url = $this->montarUrlSgp($path);

        return Http::timeout(config('services.sgp.timeout', 15))
            ->connectTimeout(config('services.sgp.connect_timeout', 5))
            ->withOptions([
                'cookies' => $cookies,
                'allow_redirects' => false,
            ])
            ->withHeaders([
                'Origin' => rtrim(config('services.sgp.url'), '/'),
                'Referer' => $url,
            ])
            ->asForm()
            ->post($url, $payload);
    }

    private function montarUrlSgp(string $path): string
    {
        return rtrim(config('services.sgp.url'), '/').'/'.ltrim($path, '/');
    }

    private function normalizarCaminhoSgp(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return $path;
        }

        return parse_url($path, PHP_URL_PATH) ?: $path;
    }

    private function extrairIdDoCaminhoSgp(string $path): ?string
    {
        if (preg_match('~^/admin/atendimento/ocorrencia/(\d+)/os/add/?$~', $path, $matches)) {
            return $matches[1];
        }

        if (preg_match('~^/admin/atendimento/ocorrencia/(\d+)/edit/~', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extrairCampoHtml(string $html, string $name): ?string
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        foreach ([
            "//input[@name='{$name}']",
            "//textarea[@name='{$name}']",
            "//select[@name='{$name}']",
        ] as $query) {
            $nodes = $xpath->query($query);

            if ($nodes && $nodes->length > 0) {
                $node = $nodes->item(0);

                if ($node->nodeName === 'textarea') {
                    return trim($node->textContent) ?: null;
                }

                if ($node->nodeName === 'select') {
                    foreach ($node->childNodes as $option) {
                        if ($option->nodeName === 'option' && $option->hasAttribute('selected')) {
                            $value = trim($option->getAttribute('value'));

                            return $value !== '' ? $value : null;
                        }
                    }

                    return null;
                }

                $value = trim($node->getAttribute('value'));

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function resolverOpcaoPorTexto(string $html, string $name, string $texto, ?string $fallback = null): ?string
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//select[@name='{$name}']/option");

        if (! $nodes) {
            return $fallback;
        }

        $procurado = $this->normalizarBusca($texto);

        foreach ($nodes as $option) {
            $label = $this->normalizarBusca(trim($option->textContent));

            if ($label !== '' && (str_contains($label, $procurado) || $label === $procurado)) {
                $value = trim($option->getAttribute('value'));

                return $value !== '' ? $value : $fallback;
            }
        }

        return $fallback;
    }

    private function normalizarBusca(string $texto): string
    {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $normalizado = mb_strtoupper(trim($normalizado ?: $texto));

        return preg_replace('/[^A-Z0-9]+/', '', $normalizado) ?: '';
    }

    private function mapaTipoOcorrencia(OrdemServico $ordem): string
    {
        return match ($ordem->tipo_servico) {
            'instalacao' => 'INSTALACAO',
            'reativacao' => 'REATIVACAO',
            'upgrade' => 'UPGRADE',
            'mudanca_endereco' => 'MUDANCA DE ENDERECO',
            'cancelamento' => 'CANCELAMENTO',
            'troca_senha' => 'REPARO - TROCA DE SENHA',
            'desconectado' => 'REPARO - DESCONECTADO',
            default => 'REPARO - OSCILACAO',
        };
    }

    private function resolverMotivoOs(OrdemServico $ordem, string $html): string
    {
        $texto = match ($ordem->tipo_servico) {
            'instalacao', 'reativacao' => 'instalacao de kit',
            'mudanca_endereco' => 'mudanca endereco',
            'upgrade' => 'upgrade',
            default => 'corretiva',
        };

        return $this->resolverOpcaoPorTexto($html, 'motivoos', $texto, '9') ?? '9';
    }

    private function resolverPrioridadeOs(OrdemServico $ordem, string $html): string
    {
        $texto = match ($ordem->prioridade) {
            'urgente' => 'Alta',
            'alta' => 'Alta',
            default => 'Normal',
        };

        return $this->resolverOpcaoPorTexto($html, 'prioridade', $texto, '2') ?? '2';
    }

    private function resolverUsuarioResponsavelSgp(string $html, ?string $usuarioResponsavel = null, ?string $usuarioEmail = null): ?string
    {
        foreach (config('services.sgp.responsavel_usuario_map', []) as $entry) {
            $matchers = array_filter(array_map(static fn ($valor) => trim((string) $valor), $entry['matchers'] ?? []));
            $responsaveis = array_values(array_filter(array_map(
                static fn ($valor) => trim((string) $valor),
                $entry['responsaveis'] ?? []
            )));

            if ($responsaveis === [] || $matchers === []) {
                continue;
            }

            foreach (array_filter([$usuarioResponsavel, $usuarioEmail]) as $candidato) {
                $candidatoNormalizado = $this->normalizarBusca((string) $candidato);

                foreach ($matchers as $matcher) {
                    if ($candidatoNormalizado !== '' && $candidatoNormalizado === $this->normalizarBusca($matcher)) {
                        foreach ($responsaveis as $responsavel) {
                            $resolvido = $this->resolverOpcaoPorTexto($html, 'usuario_responsavel', $responsavel)
                                ?? $this->resolverOpcaoPorTexto($html, 'responsavel', $responsavel)
                                ?? $this->resolverOpcaoPorTexto($html, 'usuariorresponsavel', $responsavel);

                            if ($resolvido) {
                                return $resolvido;
                            }
                        }

                        return $this->resolverOpcaoPorTexto($html, 'usuario_responsavel', $matcher)
                            ?? $this->resolverOpcaoPorTexto($html, 'responsavel', $matcher)
                            ?? $this->resolverOpcaoPorTexto($html, 'usuariorresponsavel', $matcher);
                    }
                }
            }
        }

        $candidatos = array_values(array_filter(array_unique([
            $usuarioResponsavel,
            $usuarioEmail,
            config('services.sgp.web_username'),
            config('services.sgp.default_responsavel'),
        ])));

        foreach ($candidatos as $texto) {
            $resolvido = $this->resolverOpcaoPorTexto($html, 'usuario_responsavel', $texto)
                ?? $this->resolverOpcaoPorTexto($html, 'responsavel', $texto)
                ?? $this->resolverOpcaoPorTexto($html, 'usuariorresponsavel', $texto);

            if ($resolvido) {
                return $resolvido;
            }
        }

        return null;
    }

    private function resolverTecnicoResponsavelLabel(OrdemServico $ordem): ?string
    {
        $nomeTecnico = mb_strtolower(trim((string) data_get($ordem, 'tecnico.nome')));

        if ($nomeTecnico === '') {
            return $this->responsavelPadraoSgp();
        }

        $mapa = array_filter(config('services.sgp.tecnico_responsavel_map', []));

        foreach ($mapa as $matcher => $responsavel) {
            $matcher = mb_strtolower(trim((string) $matcher));

            if ($matcher !== '' && str_contains($nomeTecnico, $matcher)) {
                return trim((string) $responsavel) ?: null;
            }
        }

        return $this->responsavelPadraoSgp();
    }

    private function resolverTecnicoResponsavelSgp(OrdemServico $ordem, string $html): ?string
    {
        $label = $this->resolverTecnicoResponsavelLabel($ordem);

        if ($label) {
            return $this->resolverOpcaoPorTexto($html, 'responsavel', $label)
                ?? $this->resolverOpcaoPorTexto($html, 'tecnicos', $label)
                ?? $this->primeiraOpcaoNaoVazia($html, 'responsavel')
                ?? $this->primeiraOpcaoNaoVazia($html, 'tecnicos');
        }

        return $this->primeiraOpcaoNaoVazia($html, 'responsavel')
            ?? $this->primeiraOpcaoNaoVazia($html, 'tecnicos');
    }

    private function responsavelPadraoSgp(): ?string
    {
        return trim((string) (config('services.sgp.default_responsavel') ?: config('services.sgp.web_username') ?: '')) ?: null;
    }

    private function primeiraOpcaoNaoVazia(string $html, string $name): ?string
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//select[@name='{$name}']/option");

        if (! $nodes) {
            return null;
        }

        foreach ($nodes as $option) {
            $value = trim($option->getAttribute('value'));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function conteudoOcorrenciaSgp(OrdemServico $ordem): string
    {
        $observacao = trim((string) $ordem->observacao);

        if ($observacao !== '') {
            return $observacao;
        }

        return match ($ordem->tipo_servico) {
            'instalacao' => 'INSTALAÇÃO',
            'reativacao' => 'REATIVAÇÃO',
            'mudanca_endereco' => 'MUDANÇA DE ENDEREÇO',
            'troca_senha' => 'TROCA DE SENHA',
            'desconectado' => 'DESCONECTADO',
            'reparo' => 'OSCILAÇÃO',
            default => 'SEM OBSERVAÇÃO',
        };
    }

    private function extrairNumeroOs(string $html): ?string
    {
        $texto = preg_replace('/\s+/u', ' ', strip_tags($html));

        if (preg_match('/\bOS:\s*(\d+)\s*-/u', $texto, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\bN\. OS:\s*(\d+)/u', $texto, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
