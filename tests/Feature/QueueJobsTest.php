<?php

namespace Tests\Feature;

use App\Jobs\CreateSgpOccurrenceJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\WhatsAppGrupo;
use App\Services\SgpService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class QueueJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sgp_job_atualiza_a_os_e_encadeia_o_whatsapp_apos_sucesso(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Teste',
            'telefone' => '73900000000',
            'ativo' => true,
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'sgp_cliente_link' => 'https://seu-sgp.exemplo/admin/cliente/5151/edit/',
            'bairro' => 'Tapera',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
            'sgp_sync_status' => 'queued',
        ]);

        $sgp = Mockery::mock(SgpService::class);
        $sgp->shouldReceive('consultarClientePorLink')
            ->once()
            ->andReturn([
                'sgp_cliente_id' => 5151,
                'sgp_contrato_id' => 5151,
                'sgp_cpf_cnpj' => '00000000000',
                'sgp_data_nascimento' => null,
                'sgp_plano' => '500M',
                'sgp_vencimento' => '10',
                'sgp_pppoe_login' => 'cliente5151',
                'sgp_pppoe_senha' => 'senha5151',
                'sgp_endereco' => 'Rua Exemplo, 10',
                'sgp_dados' => [],
            ]);
        $sgp->shouldReceive('sincronizarOcorrenciaEOrdemServico')
            ->once()
            ->andReturn([
                'status' => 'synced',
                'ocorrencia_numero' => '260528999999',
                'os_numero' => '14147',
            ]);

        $job = new CreateSgpOccurrenceJob($ordem->id, $user->name, $user->email, true);
        $job->handle($sgp);

        Bus::assertDispatched(SendWhatsappMessageJob::class, function (SendWhatsappMessageJob $queuedJob) use ($ordem) {
            return $queuedJob->ordemId === $ordem->id;
        });

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
            'sgp_ocorrencia_numero' => '260528999999',
            'sgp_os_numero' => '14147',
            'sgp_sync_status' => 'sincronizado',
            'sgp_sync_error' => null,
        ]);
    }

    public function test_send_whatsapp_job_marca_a_os_como_enviada_quando_tudo_da_certo(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $grupo = WhatsAppGrupo::create([
            'nome' => 'Grupo Tecnicos',
            'grupo_id' => '12345-67890@g.us',
            'ativo' => true,
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Teste',
            'telefone' => '73900000000',
            'ativo' => true,
            'whatsapp_grupo_id' => $grupo->id,
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'tipo_servico' => 'instalacao',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA EXEMPLO',
                            'numero' => '10',
                            'bairro' => 'CENTRO',
                        ],
                    ]],
                ]],
            ],
        ]);

        config([
            'services.sgp.url' => 'https://sgp.exemplo',
            'services.sgp.web_username' => 'usuario-teste',
            'services.sgp.web_password' => 'senha-teste',
        ]);

        Http::fake([
            '*send-sgp-address*' => Http::response(['sent' => true], 200),
            '*send-message*' => Http::response(['sent' => true], 200),
        ]);

        $job = new SendWhatsappMessageJob($ordem->id, $user->name, $user->email);
        $job->handle(app(WhatsAppService::class), app(SgpService::class));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/send-sgp-address'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/send-message'));

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'passada',
            'whatsapp_send_status' => 'sent',
        ]);
    }

    public function test_send_whatsapp_job_reenvia_quando_a_ocorrencia_sgp_muda(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $grupo = WhatsAppGrupo::create([
            'nome' => 'Grupo Tecnicos',
            'grupo_id' => '12345-67890@g.us',
            'ativo' => true,
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Teste',
            'telefone' => '73900000000',
            'ativo' => true,
            'whatsapp_grupo_id' => $grupo->id,
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'passada',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
            'sgp_ocorrencia_numero' => '260602114044',
            'whatsapp_sent_at' => now()->subMinutes(30),
            'whatsapp_sent_for_sgp_ocorrencia_numero' => '260602114044',
            'whatsapp_send_status' => 'sent',
        ]);

        $ordem->update([
            'sgp_ocorrencia_numero' => '260602114300',
            'sgp_os_numero' => '14147',
        ]);

        config([
            'services.sgp.url' => 'https://sgp.exemplo',
            'services.sgp.web_username' => 'usuario-teste',
            'services.sgp.web_password' => 'senha-teste',
        ]);

        Http::fake([
            '*send-sgp-address*' => Http::response(['sent' => true], 200),
            '*send-message*' => Http::response(['sent' => true], 200),
        ]);

        $job = new SendWhatsappMessageJob($ordem->id, $user->name, $user->email);
        $job->handle(app(WhatsAppService::class), app(SgpService::class));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/send-sgp-address'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/send-message'));

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'passada',
            'whatsapp_send_status' => 'sent',
            'whatsapp_sent_for_sgp_ocorrencia_numero' => '260602114300',
        ]);
    }
}
