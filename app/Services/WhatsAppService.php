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
        $observacao = trim((string) $ordem->observacao);
        $servicosCompletos = ['instalacao', 'upgrade', 'mudanca_endereco', 'desconectado'];

        if (in_array($ordem->tipo_servico, $servicosCompletos, true)) {
            return [collect([
                mb_strtoupper($tipo),
                '',
                $ordem->cliente_nome,
                $ordem->sgp_contrato_id,
                '',
                $ordem->sgp_cpf_cnpj ? "Titular: {$ordem->cliente_nome}" : null,
                $ordem->sgp_data_nascimento ? 'Data de nascimento: ' . $ordem->sgp_data_nascimento->format('d/m/Y') : null,
                $ordem->sgp_cpf_cnpj ? "CPF: {$ordem->sgp_cpf_cnpj}" : null,
                "Tel: {$ordem->cliente_telefone}",
                $ordem->sgp_plano ? "Nome do plano {$ordem->sgp_plano}" : null,
                $ordem->sgp_pppoe_login ? "Login PPPoE: {$ordem->sgp_pppoe_login}" : null,
                $ordem->sgp_pppoe_senha ? "Senha PPPoE: {$ordem->sgp_pppoe_senha}" : null,
                $ordem->sgp_vencimento ? "Vencimento: {$ordem->sgp_vencimento}" : null,
                $observacao !== '' ? "Observacao: {$observacao}" : null,
            ])->filter(fn ($linha) => $linha !== null)->implode("\n")];
        }

        return collect([
            trim(mb_strtoupper($tipo) . ($observacao !== '' ? " - {$observacao}" : '')),
            $ordem->cliente_nome,
            $ordem->sgp_pppoe_login ? "Login: {$ordem->sgp_pppoe_login}" : null,
            $ordem->sgp_pppoe_senha ? "Senha: {$ordem->sgp_pppoe_senha}" : null,
            $ordem->cliente_telefone,
        ])->filter()->values()->all();
    }
}
