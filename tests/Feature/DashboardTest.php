<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exibe_metricas_basicas(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Tecnico Dashboard',
            'telefone' => '73999999999',
            'ativo' => true,
        ]);

        OrdemServico::create([
            'cliente_nome' => 'Cliente A',
            'cliente_telefone' => '73911111111',
            'bairro' => 'Centro',
            'tipo_servico' => 'reparo',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'concluida',
            'data_marcacao' => now()->toDateString(),
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
        ]);

        OrdemServico::create([
            'cliente_nome' => 'Cliente B',
            'cliente_telefone' => '73922222222',
            'bairro' => 'Centro',
            'tipo_servico' => 'instalacao',
            'turno' => 'tarde',
            'prioridade' => 'normal',
            'status' => 'pendente',
            'data_marcacao' => now()->toDateString(),
            'tecnico_id' => $tecnico->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Serviços concluídos no mês');
        $response->assertSee('OS abertas');
        $response->assertSee('Clientes com mais OS abertas');
    }
}
