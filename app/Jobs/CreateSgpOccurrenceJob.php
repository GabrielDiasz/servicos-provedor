<?php

namespace App\Jobs;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Services\SgpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSgpOccurrenceJob implements ShouldQueue, ShouldBeUnique
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
        public bool $dispatchWhatsapp = true,
        public ?int $tecnicoIdSnapshot = null,
    ) {
    }

    public function backoff(): array
    {
        return [5, 10, 30];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('ordem-flow:'.$this->ordemId))
                ->shared()
                ->releaseAfter(10)
                ->expireAfter(900),
        ];
    }

    public function uniqueId(): string
    {
        return 'sgp-occurrence:'.$this->ordemId;
    }

    public function handle(SgpService $sgp): void
    {
        $ordem = OrdemServico::query()
            ->with(['tecnico.whatsappGrupo', 'atendente'])
            ->findOrFail($this->ordemId);

        if ($this->tecnicoIdSnapshot !== null) {
            $tecnicoSnapshot = Tecnico::query()
                ->with('whatsappGrupo')
                ->find($this->tecnicoIdSnapshot);

            if ($tecnicoSnapshot) {
                $ordem->setRelation('tecnico', $tecnicoSnapshot);
                $ordem->tecnico_id = $tecnicoSnapshot->id;
            }
        }

        Log::info('Iniciando job de criacao de ocorrencia no SGP.', [
            'ordem_id' => $ordem->id,
            'cliente_id' => $ordem->sgp_cliente_id,
            'tecnico_id' => $ordem->tecnico_id,
            'tecnico_id_snapshot' => $this->tecnicoIdSnapshot,
            'status_sgp' => $ordem->sgp_sync_status,
            'payload' => $this->contextoOrdem($ordem),
        ]);

        if ($this->ordemJaSincronizada($ordem)) {
            Log::info('Ordem ja estava sincronizada no SGP; job encerrado sem duplicar envio.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_sgp' => $ordem->sgp_sync_status,
                'ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
                'ocorrencia_sgp_id' => $ordem->sgp_ocorrencia_sgp_id,
                'os_numero' => $ordem->sgp_os_numero,
            ]);

            if ($this->dispatchWhatsapp) {
                $ordem->forceFill([
                    'whatsapp_send_status' => 'queued',
                    'whatsapp_send_error' => null,
                ])->save();

                SendWhatsappMessageJob::dispatch(
                    $ordem->id,
                    $this->usuarioResponsavel,
                    $this->usuarioEmail,
                    $this->tecnicoIdSnapshot
                );
            }

            return;
        }

        $ordem->forceFill([
            'sgp_sync_status' => 'processando',
            'sgp_sync_error' => null,
        ])->save();

        try {
            $this->garantirDadosSgpLocalmente($ordem, $sgp);
            $ordem->refresh();

            $resultado = $sgp->sincronizarOcorrenciaEOrdemServico(
                $ordem,
                $this->usuarioResponsavel,
                $this->usuarioEmail
            );

            if (($resultado['status'] ?? null) !== 'synced') {
                $ordem->forceFill([
                    'sgp_sync_status' => $this->mapearStatusFalha($resultado['status'] ?? null),
                    'sgp_sync_error' => $resultado['message'] ?? 'Falha desconhecida ao sincronizar no SGP.',
                ])->save();

                Log::warning('Job do SGP nao concluiu a sincronizacao.', [
                    'ordem_id' => $ordem->id,
                    'cliente_id' => $ordem->sgp_cliente_id,
                    'status_sgp' => $resultado['status'] ?? null,
                    'erro' => $resultado['message'] ?? null,
                    'payload' => $this->contextoOrdem($ordem),
                ]);

                if (($resultado['status'] ?? null) === 'skipped') {
                    return;
                }

                throw new \RuntimeException($resultado['message'] ?? 'Falha ao sincronizar no SGP.');
            }

            $ordem->forceFill([
                'sgp_ocorrencia_sgp_id' => $resultado['ocorrencia_sgp_id'] ?? $ordem->sgp_ocorrencia_sgp_id,
                'sgp_ocorrencia_numero' => $resultado['ocorrencia_numero'] ?? $ordem->sgp_ocorrencia_numero,
                'sgp_os_numero' => $resultado['os_numero'] ?? $ordem->sgp_os_numero,
                'sgp_sync_status' => 'sincronizado',
                'sgp_sync_error' => null,
            ])->save();

            Log::info('Ocorrencia criada no SGP com sucesso.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_sgp' => $ordem->sgp_sync_status,
                'ocorrencia_sgp_id' => $ordem->sgp_ocorrencia_sgp_id,
                'ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
                'os_numero' => $ordem->sgp_os_numero,
                'payload' => $this->contextoOrdem($ordem),
            ]);

            if ($this->dispatchWhatsapp) {
                $ordem->forceFill([
                    'whatsapp_send_status' => 'queued',
                    'whatsapp_send_error' => null,
                ])->save();

                SendWhatsappMessageJob::dispatch(
                    $ordem->id,
                    $this->usuarioResponsavel,
                    $this->usuarioEmail,
                    $this->tecnicoIdSnapshot
                );
            }
        } catch (Throwable $exception) {
            $ordem->forceFill([
                'sgp_sync_status' => 'erro',
                'sgp_sync_error' => $exception->getMessage(),
            ])->save();

            Log::error('Falha ao criar ocorrencia no SGP.', [
                'ordem_id' => $ordem->id,
                'cliente_id' => $ordem->sgp_cliente_id,
                'status_sgp' => $ordem->sgp_sync_status,
                'erro' => $exception->getMessage(),
                'payload' => $this->contextoOrdem($ordem),
            ]);

            throw $exception;
        }
    }

    private function ordemJaSincronizada(OrdemServico $ordem): bool
    {
        return $ordem->sgp_sync_status === 'sincronizado'
            && filled($ordem->sgp_ocorrencia_numero);
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
            'tecnico_id_snapshot' => $this->tecnicoIdSnapshot,
            'sgp_ocorrencia_sgp_id' => $ordem->sgp_ocorrencia_sgp_id,
            'sgp_cliente_link' => $ordem->sgp_cliente_link,
            'tipo_servico' => $ordem->tipo_servico,
            'status' => $ordem->status,
        ];
    }

    private function mapearStatusFalha(?string $status): string
    {
        return match ($status) {
            'skipped' => 'ignorado',
            'error' => 'erro',
            default => 'erro',
        };
    }
}
