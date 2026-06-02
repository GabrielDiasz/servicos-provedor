<?php

namespace App\Jobs;

use App\Models\OrdemServico;
use App\Services\SgpService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsappMessageJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $ordemId,
        public ?string $usuarioResponsavel = null,
        public ?string $usuarioEmail = null,
    ) {
    }

    public function backoff(): array
    {
        return [5, 10, 30];
    }

    public function uniqueId(): string
    {
        return 'whatsapp-message:'.$this->ordemId;
    }

    public function handle(WhatsAppService $whatsApp, SgpService $sgp): void
    {
        $ordem = OrdemServico::query()
            ->with(['tecnico.whatsappGrupo', 'atendente'])
            ->findOrFail($this->ordemId);

        Log::info('Iniciando job de envio de WhatsApp.', [
            'ordem_id' => $ordem->id,
            'cliente_id' => $ordem->sgp_cliente_id,
            'tecnico_id' => $ordem->tecnico_id,
            'status_whatsapp' => $ordem->whatsapp_send_status,
            'sgp_ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
            'whatsapp_sent_for_sgp_ocorrencia_numero' => $ordem->whatsapp_sent_for_sgp_ocorrencia_numero,
            'payload' => $this->contextoOrdem($ordem),
        ]);

        if ($this->whatsappJaEnviado($ordem)) {
            Log::info('WhatsApp ja havia sido enviado para esta ordem; job encerrado sem duplicar envio.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_whatsapp' => $ordem->whatsapp_send_status,
                'whatsapp_sent_at' => $ordem->whatsapp_sent_at?->toDateTimeString(),
                'sgp_ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
                'whatsapp_sent_for_sgp_ocorrencia_numero' => $ordem->whatsapp_sent_for_sgp_ocorrencia_numero,
            ]);

            return;
        }

        if (! config('services.whatsapp.enabled')) {
            $ordem->forceFill([
                'whatsapp_send_status' => 'ignorado',
                'whatsapp_send_error' => 'Integração com o WhatsApp desativada.',
            ])->save();

            Log::info('WhatsApp desativado; job encerrado sem envio.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'payload' => $this->contextoOrdem($ordem),
            ]);

            return;
        }

        if (! $ordem->tecnico?->whatsappGrupo?->grupo_id) {
            $ordem->forceFill([
                'whatsapp_send_status' => 'ignorado',
                'whatsapp_send_error' => 'Ordem sem grupo de WhatsApp do técnico.',
            ])->save();

            Log::warning('Ordem sem grupo do tecnico para envio via WhatsApp.', [
                'ordem_id' => $ordem->id,
                'tecnico_id' => $ordem->tecnico_id,
                'payload' => $this->contextoOrdem($ordem),
            ]);

            return;
        }

        $ordem->forceFill([
            'whatsapp_send_status' => 'processando',
            'whatsapp_send_error' => null,
        ])->save();

        try {
            $this->garantirDadosSgpLocalmente($ordem, $sgp);
            $ordem->refresh();

            $enviado = $whatsApp->enviarOrdemServico($ordem);

            if (! $enviado) {
                $ordem->forceFill([
                    'whatsapp_send_status' => 'erro',
                    'whatsapp_send_error' => 'O envio pelo WhatsApp retornou falso sem excecao.',
                ])->save();

                Log::warning('Job do WhatsApp nao conseguiu concluir o envio.', [
                    'ordem_id' => $ordem->id,
                    'cliente_id' => $ordem->sgp_cliente_id,
                    'status_whatsapp' => $ordem->whatsapp_send_status,
                    'payload' => $this->contextoOrdem($ordem),
                ]);

                return;
            }

            $ordem->forceFill([
                'status' => 'passada',
                'whatsapp_send_status' => 'sent',
                'whatsapp_send_error' => null,
                'whatsapp_sent_at' => now(),
                'whatsapp_sent_for_sgp_ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
            ])->save();

            Log::info('WhatsApp enviado com sucesso.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_whatsapp' => $ordem->whatsapp_send_status,
                'whatsapp_sent_at' => $ordem->whatsapp_sent_at?->toDateTimeString(),
                'sgp_ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
                'whatsapp_sent_for_sgp_ocorrencia_numero' => $ordem->whatsapp_sent_for_sgp_ocorrencia_numero,
                'payload' => $this->contextoOrdem($ordem),
            ]);
        } catch (Throwable $exception) {
            $ordem->forceFill([
                'whatsapp_send_status' => 'erro',
                'whatsapp_send_error' => $exception->getMessage(),
            ])->save();

            Log::error('Falha ao enviar WhatsApp.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_whatsapp' => $ordem->whatsapp_send_status,
                'erro' => $exception->getMessage(),
                'payload' => $this->contextoOrdem($ordem),
            ]);

            throw $exception;
        }
    }

    private function whatsappJaEnviado(OrdemServico $ordem): bool
    {
        if (filled($ordem->sgp_ocorrencia_numero)) {
            return $ordem->whatsapp_sent_for_sgp_ocorrencia_numero === $ordem->sgp_ocorrencia_numero;
        }

        return filled($ordem->whatsapp_sent_at)
            || $ordem->whatsapp_send_status === 'sent';
    }

    private function garantirDadosSgpLocalmente(OrdemServico $ordem, SgpService $sgp): void
    {
        if (filled($ordem->sgp_cliente_id) && filled($ordem->sgp_contrato_id)) {
            return;
        }

        if (blank($ordem->sgp_cliente_link)) {
            return;
        }

        $dadosSgp = $sgp->consultarClientePorLink($ordem->sgp_cliente_link);

        if (! $dadosSgp) {
            return;
        }

        $ordem->forceFill([
            'sgp_cliente_id' => $dadosSgp['sgp_cliente_id'] ?? $ordem->sgp_cliente_id,
            'sgp_contrato_id' => $dadosSgp['sgp_contrato_id'] ?? $ordem->sgp_contrato_id,
            'sgp_cpf_cnpj' => $dadosSgp['sgp_cpf_cnpj'] ?? $ordem->sgp_cpf_cnpj,
            'sgp_data_nascimento' => $dadosSgp['sgp_data_nascimento'] ?? $ordem->sgp_data_nascimento,
            'sgp_plano' => $dadosSgp['sgp_plano'] ?? $ordem->sgp_plano,
            'sgp_vencimento' => $dadosSgp['sgp_vencimento'] ?? $ordem->sgp_vencimento,
            'sgp_pppoe_login' => $dadosSgp['sgp_pppoe_login'] ?? $ordem->sgp_pppoe_login,
            'sgp_pppoe_senha' => $dadosSgp['sgp_pppoe_senha'] ?? $ordem->sgp_pppoe_senha,
            'sgp_endereco' => $dadosSgp['sgp_endereco'] ?? $ordem->sgp_endereco,
            'sgp_dados' => $dadosSgp['sgp_dados'] ?? $ordem->sgp_dados,
        ])->save();
    }

    private function contextoOrdem(OrdemServico $ordem): array
    {
        return [
            'ordem_id' => $ordem->id,
            'cliente_id' => $ordem->sgp_cliente_id,
            'cliente_nome' => $ordem->cliente_nome,
            'tecnico_id' => $ordem->tecnico_id,
            'whatsapp_send_status' => $ordem->whatsapp_send_status,
            'sgp_ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
            'status' => $ordem->status,
            'tipo_servico' => $ordem->tipo_servico,
        ];
    }
}
