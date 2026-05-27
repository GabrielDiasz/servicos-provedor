<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SgpService
{
    public function consultarClientePorLink(?string $link): ?array
    {
        $clienteId = $this->extrairClienteIdDoLink($link);

        if ($clienteId) {
            return $this->consultarClientePorId($clienteId);
        }

        if (preg_match('~^\d+$~', trim((string) $link))) {
            return $this->consultarClientePorId(trim((string) $link))
                ?? $this->consultarClientePorContrato(trim((string) $link));
        }

        return null;
    }

    public function consultarClientePorId(string|int $clienteId): ?array
    {
        if (! config('services.sgp.enabled')) {
            return null;
        }

        try {
            $response = Http::timeout(config('services.sgp.timeout', 15))
                ->withHeaders(['Expect' => ''])
                ->asForm()
                ->post(rtrim(config('services.sgp.url'), '/') . '/api/ura/clientes/', [
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
    }

    public function consultarClientePorContrato(string|int $contrato): ?array
    {
        if (! config('services.sgp.enabled')) {
            return null;
        }

        try {
            $response = Http::timeout(config('services.sgp.timeout', 15))
                ->asForm()
                ->post(rtrim(config('services.sgp.url'), '/') . '/api/ura/consultacliente/', [
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
            isset($endereco['complemento']) && $endereco['complemento'] !== '' ? 'Complemento: ' . $endereco['complemento'] : null,
            isset($endereco['pontoreferencia']) && $endereco['pontoreferencia'] !== '' ? 'Referência: ' . $endereco['pontoreferencia'] : null,
            isset($endereco['ponto_referencia']) && $endereco['ponto_referencia'] !== '' ? 'Referência: ' . $endereco['ponto_referencia'] : null,
        ])));

        return trim(implode(', ', array_filter([$logradouro, $extras]))) ?: null;
    }
}
