<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\SgpService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrdemServicoTelefoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_preserva_o_telefone_editado_manualmente_em_vez_do_telefone_do_sgp(): void
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
        $sgp->shouldReceive('consultarClientePorLink')
            ->once()
            ->andReturn([
                'cliente_nome' => 'Cliente SGP',
                'cliente_telefone' => '73911111111',
                'bairro' => 'Centro',
                'sgp_cliente_id' => 5151,
                'sgp_contrato_id' => 5151,
            ]);

        $this->app->instance(SgpService::class, $sgp);

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

        $this->assertDatabaseHas('ordens_servico', [
            'cliente_nome' => 'Cliente Final',
            'cliente_telefone' => '73999999999',
            'bairro' => 'Tapera',
            'sgp_cliente_id' => 5151,
            'sgp_ocorrencia_numero' => null,
            'sgp_os_numero' => null,
            'sgp_sync_status' => null,
        ]);
    }

    public function test_enviar_whatsapp_sincroniza_sgp_antes_de_enviar(): void
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

        $sgp = Mockery::mock(SgpService::class);
        $sgp->shouldReceive('sincronizarOcorrenciaEOrdemServico')
            ->once()
            ->andReturn([
                'status' => 'synced',
                'ocorrencia_numero' => '260528999999',
                'os_numero' => '14147',
            ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('enviarOrdemServico')
            ->once()
            ->andReturn(true);

        $this->app->instance(SgpService::class, $sgp);
        $this->app->instance(WhatsAppService::class, $whatsApp);

        $this->actingAs($user)
            ->post(route('ordens.enviar-whatsapp', $ordem))
            ->assertRedirect();

        $this->assertDatabaseHas('ordens_servico', [
            'id' => $ordem->id,
            'status' => 'passada',
            'sgp_ocorrencia_numero' => '260528999999',
            'sgp_os_numero' => '14147',
            'sgp_sync_status' => 'sincronizado',
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
}
