<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Models\WhatsAppGrupo;
use App\Models\User;
use App\Jobs\CreateSgpOccurrenceJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Services\SgpService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class OrdemServicoTelefoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_nao_dispara_sgp_ou_whatsapp_automaticamente(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Teste',
            'telefone' => '73900000000',
            'ativo' => true,
        ]);

        $sgp = Mockery::mock(SgpService::class);
        $sgp->shouldNotReceive('consultarClientePorLink');
        $this->app->instance(SgpService::class, $sgp);

        Bus::fake();

        $this->actingAs($user)
            ->post(route('ordens.store'), [
                'cliente_nome' => 'Cliente Final',
                'cliente_telefone' => '73999999999',
                'sgp_cliente_link' => 'https://seu-sgp.exemplo/admin/cliente/5151/edit/',
                'bairro' => 'Tapera',
                'tipo_servico' => 'reparo',
                'turno' => 'manha',
                'prioridade' => 'normal',
                'data_marcacao' => now()->toDateString(),
                'tecnico_id' => $tecnico->id,
                'observacao' => null,
            ])
            ->assertRedirect(route('ordens.index'));

        Bus::assertNotDispatched(CreateSgpOccurrenceJob::class);
        Bus::assertNotDispatched(SendWhatsappMessageJob::class);

        $this->assertDatabaseHas('ordens_servico', [
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'sgp_cliente_id' => null,
            'sgp_ocorrencia_numero' => null,
            'sgp_os_numero' => null,
            'sgp_sync_status' => null,
            'whatsapp_send_status' => null,
        ]);
    }

    public function test_enviar_whatsapp_com_checkbox_abre_sgp_e_sincroniza_antes_de_enviar(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => null,
            'user_id' => $user->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
        ]);

        Bus::fake();

        $this->actingAs($user)
            ->post(route('ordens.enviar-whatsapp', $ordem), [
                'abrir_ocorrencia_sgp' => '1',
            ])
            ->assertRedirect();

        Bus::assertDispatched(CreateSgpOccurrenceJob::class, function (CreateSgpOccurrenceJob $job) use ($ordem) {
            return $job->ordemId === $ordem->id && $job->dispatchWhatsapp === true;
        });
        Bus::assertNotDispatched(SendWhatsappMessageJob::class);

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'pendente',
            'sgp_ocorrencia_numero' => null,
            'sgp_os_numero' => null,
            'sgp_sync_status' => 'queued',
        ]);
    }

    public function test_enviar_whatsapp_sem_checkbox_envia_apenas_outra_mensagem_sem_sgp(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => null,
            'user_id' => $user->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
        ]);

        Bus::fake();

        $this->actingAs($user)
            ->post(route('ordens.enviar-whatsapp', $ordem), [
                'abrir_ocorrencia_sgp' => '0',
            ])
            ->assertRedirect();

        Bus::assertDispatched(SendWhatsappMessageJob::class, function (SendWhatsappMessageJob $job) use ($ordem) {
            return $job->ordemId === $ordem->id;
        });
        Bus::assertNotDispatched(CreateSgpOccurrenceJob::class);

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'pendente',
            'sgp_ocorrencia_numero' => null,
            'sgp_os_numero' => null,
            'whatsapp_send_status' => 'queued',
        ]);
    }

    public function test_enviar_whatsapp_sem_checkbox_reagenda_envio_mesmo_quando_a_mesma_ocorrencia_ja_foi_enviada(): void
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
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
            'sgp_ocorrencia_numero' => '260602114300',
            'whatsapp_sent_at' => now()->subMinutes(15),
            'whatsapp_sent_for_sgp_ocorrencia_numero' => '260602114300',
            'whatsapp_send_status' => 'sent',
        ]);

        Bus::fake();

        $this->actingAs($user)
            ->post(route('ordens.enviar-whatsapp', $ordem), [
                'abrir_ocorrencia_sgp' => '0',
            ])
            ->assertRedirect();

        Bus::assertDispatched(SendWhatsappMessageJob::class, function (SendWhatsappMessageJob $job) use ($ordem, $tecnico) {
            return $job->ordemId === $ordem->id
                && $job->tecnicoIdSnapshot === $tecnico->id;
        });

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'whatsapp_send_status' => 'queued',
            'whatsapp_sent_at' => null,
            'whatsapp_sent_for_sgp_ocorrencia_numero' => null,
        ]);
    }

    public function test_enviar_whatsapp_envia_a_imagem_do_endereco_antes_das_mensagens_textuais(): void
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
        ]);
        Bus::fake();

        $this->actingAs($user)
            ->post(route('ordens.enviar-whatsapp', $ordem))
            ->assertRedirect();

        Bus::assertDispatched(SendWhatsappMessageJob::class, function (SendWhatsappMessageJob $job) use ($ordem) {
            return $job->ordemId === $ordem->id;
        });

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'pendente',
            'whatsapp_send_status' => 'queued',
        ]);
    }

    public function test_store_exige_observacao_quando_o_tipo_e_upgrade(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Teste',
            'telefone' => '73900000000',
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->post(route('ordens.store'), [
                'cliente_nome' => 'Cliente Upgrade',
                'cliente_telefone' => '73999999999',
                'sgp_cliente_link' => null,
                'bairro' => 'Tapera',
                'tipo_servico' => 'upgrade',
                'turno' => 'manha',
                'prioridade' => 'normal',
                'data_marcacao' => now()->toDateString(),
                'tecnico_id' => $tecnico->id,
                'observacao' => null,
            ])
            ->assertSessionHasErrors([
                'observacao' => 'A observação é obrigatória para o serviço Upgrade.',
            ]);
    }

    public function test_update_nao_consulta_sgp_quando_o_link_nao_mudou_e_a_os_ja_tem_dados_sgp(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $ordem = OrdemServico::create([
            'cliente_nome' => 'Cliente Atual',
            'cliente_telefone' => '73999999999',
            'sgp_cliente_link' => 'https://seu-sgp.exemplo/admin/cliente/5151/edit/',
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
            'bairro' => 'Centro',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'observacao' => null,
            'tecnico_id' => null,
            'user_id' => $user->id,
        ]);

        $sgp = Mockery::mock(SgpService::class);
        $sgp->shouldReceive('consultarClientePorLink')->never();
        $this->app->instance(SgpService::class, $sgp);

        $this->actingAs($user)
            ->put(route('ordens.update', $ordem), [
                'cliente_nome' => 'Cliente Editado',
                'cliente_telefone' => '73999999999',
                'sgp_cliente_link' => 'https://seu-sgp.exemplo/admin/cliente/5151/edit/',
                'bairro' => 'Centro',
                'tipo_servico' => 'reparo',
                'turno' => 'manha',
                'prioridade' => 'normal',
                'status' => 'pendente',
                'data_marcacao' => now()->toDateString(),
                'tecnico_id' => null,
                'observacao' => null,
            ])
            ->assertRedirect(route('ordens.index'));

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'cliente_nome' => 'Cliente Editado',
            'sgp_cliente_id' => 5151,
            'sgp_contrato_id' => 5151,
        ]);
    }

    public function test_index_mostra_cliente_como_link_do_sgp_em_nova_aba(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $ordem = new OrdemServico([
            'cliente_nome' => 'Cliente SGP',
            'cliente_telefone' => '73999999999',
            'sgp_cliente_link' => 'https://seu-sgp.exemplo/admin/cliente/5151/edit/',
            'bairro' => 'Centro',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $ordem->save();

        $this->actingAs($user);

        $ordens = new LengthAwarePaginator(
            collect([$ordem]),
            1,
            20,
            1,
            [
                'path' => route('ordens.index'),
                'pageName' => 'page',
            ]
        );

        $html = view('ordens.index', [
            'ordens' => $ordens,
            'tecnicos' => Collection::make(),
            'tecnicosDisponiveis' => Collection::make(),
            'dataMarcacao' => now()->toDateString(),
            'statusFilterOptions' => OrdemServico::STATUS,
            'tecnicoFilterOptions' => [],
            'tecnicoOptions' => [],
            'tipoOptions' => OrdemServico::TIPOS,
            'prioridadeOptions' => OrdemServico::PRIORIDADES,
            'resumoCards' => [
                ['title' => 'Serviços no dia', 'value' => 1, 'tone' => 'blue'],
                ['title' => 'Serviços passados', 'value' => 0, 'tone' => 'amber'],
                ['title' => 'Serviços concluídos', 'value' => 0, 'tone' => 'emerald'],
            ],
        ])->render();

        $this->assertStringContainsString('href="https://seu-sgp.exemplo/admin/cliente/5151/edit/"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('Cliente SGP', $html);
    }
}
