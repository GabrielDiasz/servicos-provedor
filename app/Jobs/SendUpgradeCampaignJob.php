<?php

namespace App\Jobs;

use App\Models\UpgradeCampaign;
use App\Models\UpgradeContact;
use App\Services\UpgradeMessageService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendUpgradeCampaignJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public int $campaignId,
        public array $contactIds,
        public array $phonePreferences = [],
        public int $index = 0,
        public ?int $usuarioId = null,
        public ?string $usuarioResponsavel = null,
        public ?string $usuarioEmail = null,
    ) {
        $this->contactIds = array_values(array_map('intval', $contactIds));
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('upgrade-campaign:'.$this->campaignId))
                ->shared()
                ->releaseAfter(10)
                ->expireAfter(1800),
            (new WithoutOverlapping('upgrade-global-send'))
                ->shared()
                ->releaseAfter(10)
                ->expireAfter(1800),
        ];
    }

    public function uniqueId(): string
    {
        $contactIds = $this->contactIds;
        sort($contactIds);

        return 'upgrade-campaign:'.$this->campaignId.':'.$this->index.':'.md5(json_encode([
            $contactIds,
            $this->phonePreferences,
        ], JSON_THROW_ON_ERROR));
    }

    public function handle(WhatsAppService $whatsApp, UpgradeMessageService $messageService): void
    {
        $campaign = null;
        $contato = null;

        try {
            $campaign = UpgradeCampaign::query()->findOrFail($this->campaignId);

            if ($campaign->status_envio === 'concluido' || $campaign->status_envio === 'concluido_com_erro') {
                $this->workerConsole("Campanha Upgrade #{$campaign->id} ja foi finalizada. Job encerrado.");

                return;
            }

            $contactId = $this->contactIds[$this->index] ?? null;

            if (! $contactId) {
                $this->finalizarCampanha($campaign);

                return;
            }

            $contato = UpgradeContact::query()
                ->where('upgrade_campaign_id', $campaign->id)
                ->findOrFail($contactId);

            if ($contato->status_envio === 'enviado') {
                $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id} ja estava enviado. Avancando para o proximo.");
                $this->atualizarResumo($campaign);
                $this->agendarProximoPasso();

                return;
            }

            $campaign->forceFill(['status_envio' => 'enviando'])->save();

            $contato->forceFill([
                'status_envio' => 'enviando',
                'erro_envio' => null,
            ])->save();

            $telefones = $contato->telefonesParaEnvio();

            if ($telefones === []) {
                $contato->forceFill([
                    'status_envio' => 'erro',
                    'erro_envio' => 'Nenhum telefone valido foi encontrado na linha importada.',
                ])->save();

                $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id}: sem telefone valido.", [
                    'nome_cliente' => $contato->nome_cliente,
                    'linha_planilha' => $contato->linha_planilha,
                ], true);

                $this->atualizarResumo($campaign, 'Nenhum telefone valido foi encontrado em pelo menos um dos contatos.');
                $this->agendarProximoPasso();

                return;
            }

            $mensagem = $messageService->montarMensagem($contato->nome_cliente);

            $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id}: iniciando envio.", [
                'nome_cliente' => $contato->nome_cliente,
                'telefones' => $telefones,
                'preferencia' => $contato->contato_preferido,
                'passo' => ($this->index + 1).'/'.count($this->contactIds),
            ]);

            $enviado = false;
            $erros = [];
            $telefoneUsado = null;

            foreach ($telefones as $telefone) {
                $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id}: tentando telefone.", [
                    'telefone' => $telefone,
                    'tentativa' => count($erros) + 1,
                ]);

                $enviado = $whatsApp->enviarMensagemParaTelefone($telefone, $mensagem);

                if ($enviado) {
                    $telefoneUsado = $telefone;
                    break;
                }

                $erros[] = "Falha ao enviar para {$telefone}.";
            }

            if (! $enviado) {
                $contato->forceFill([
                    'status_envio' => 'erro',
                    'erro_envio' => implode(' ', $erros) ?: 'O WhatsApp retornou falso ao tentar enviar a mensagem.',
                ])->save();

                $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id}: falha nos telefones do contato.", [
                    'nome_cliente' => $contato->nome_cliente,
                    'telefones' => $telefones,
                ], true);

                $this->atualizarResumo($campaign, 'Falha ao enviar mensagens do Upgrade.');
                $this->agendarProximoPasso();

                return;
            }

            $contato->forceFill([
                'status_envio' => 'enviado',
                'erro_envio' => null,
                'enviado_em' => now(),
            ])->save();

            $this->workerConsole("Upgrade #{$campaign->id} > contato #{$contato->id}: mensagem enviada com sucesso.", [
                'nome_cliente' => $contato->nome_cliente,
                'telefone' => $telefoneUsado,
            ]);

            $this->atualizarResumo($campaign);
        } catch (Throwable $exception) {
            if ($campaign) {
                if ($contato) {
                    $contato->forceFill([
                        'status_envio' => 'erro',
                        'erro_envio' => $exception->getMessage(),
                    ])->save();
                }

                Log::error('Falha inesperada ao enviar campanha de Upgrade via WhatsApp.', [
                    'campaign_id' => $campaign->id,
                    'erro' => $exception->getMessage(),
                ]);

                $this->workerConsole("Upgrade #{$campaign->id}: erro inesperado ao processar a campanha.", [
                    'erro' => $exception->getMessage(),
                ], true);

                $this->atualizarResumo($campaign, $exception->getMessage());
            }
        }

        $this->agendarProximoPasso();
    }

    private function agendarProximoPasso(): void
    {
        if (! isset($this->contactIds[$this->index + 1])) {
            $campaign = UpgradeCampaign::query()->find($this->campaignId);

            if ($campaign) {
                $this->finalizarCampanha($campaign);
            }

            return;
        }

        self::dispatch(
            $this->campaignId,
            $this->contactIds,
            $this->phonePreferences,
            $this->index + 1,
            $this->usuarioId,
            $this->usuarioResponsavel,
            $this->usuarioEmail,
        )->delay(now()->addSeconds((int) config('upgrade.send_delay_seconds', 3)));
    }

    private function finalizarCampanha(UpgradeCampaign $campaign): void
    {
        $this->marcarPendentesComoErro($campaign);

        $enviadosCount = UpgradeContact::query()
            ->where('upgrade_campaign_id', $campaign->id)
            ->where('status_envio', 'enviado')
            ->count();

        $falhasCount = UpgradeContact::query()
            ->where('upgrade_campaign_id', $campaign->id)
            ->where('status_envio', 'erro')
            ->count();

        $campaign->forceFill([
            'enviados' => $enviadosCount,
            'falhas' => $falhasCount,
            'status_envio' => $falhasCount > 0 ? 'concluido_com_erro' : 'concluido',
            'finalizado_em' => now(),
            'enviado_em' => $campaign->enviado_em ?: now(),
        ])->save();

        $this->workerConsole("Upgrade #{$campaign->id}: campanha finalizada.", [
            'enviados' => $campaign->enviados,
            'falhas' => $campaign->falhas,
            'status' => $campaign->status_envio,
        ]);
    }

    private function marcarPendentesComoErro(UpgradeCampaign $campaign): void
    {
        UpgradeContact::query()
            ->where('upgrade_campaign_id', $campaign->id)
            ->whereIn('status_envio', ['na_fila', 'enviando'])
            ->update([
                'status_envio' => 'erro',
                'erro_envio' => 'Envio interrompido antes do processamento completo da campanha.',
            ]);
    }

    private function atualizarResumo(UpgradeCampaign $campaign, ?string $erroUltimo = null): void
    {
        $enviadosCount = UpgradeContact::query()
            ->where('upgrade_campaign_id', $campaign->id)
            ->where('status_envio', 'enviado')
            ->count();

        $falhasCount = UpgradeContact::query()
            ->where('upgrade_campaign_id', $campaign->id)
            ->where('status_envio', 'erro')
            ->count();

        $campaign->forceFill([
            'enviados' => $enviadosCount,
            'falhas' => $falhasCount,
            'erro_ultimo' => $erroUltimo,
            'enviado_em' => $campaign->enviado_em ?: now(),
        ])->save();
    }

    private function workerConsole(string $message, array $context = [], bool $isError = false): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $prefix = $isError ? '[ERRO]' : '[INFO]';
        $line = $prefix.' Upgrade > '.$message;

        if ($context !== []) {
            $line .= ' '.json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        echo $line.PHP_EOL;
        flush();
    }
}
