<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 5, 30, 14, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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
        $response->assertSee('Visão geral do mês de Maio de 2026');
        $response->assertSee('Serviços concluídos');
        $response->assertSee('OS passadas');
        $response->assertSee('Técnicos sobrecarregados');
        $response->assertDontSee('Novas OS');
        $response->assertDontSee('OS passadas por tipo');
        $response->assertDontSee('Alertas operacionais');
    }

    public function test_tecnicos_sobrecarga_considera_os_passadas(): void
    {
        $user = User::factory()->create([
            'perfil' => 'admin',
        ]);

        $tecnicoHoje = Tecnico::create([
            'nome' => 'Tecnico Hoje',
            'telefone' => '73988888888',
            'ativo' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->criarOrdemServicoComAtualizacao([
                'cliente_nome' => 'Cliente Passada '.$i,
                'cliente_telefone' => '7391000000'.$i,
                'bairro' => 'Centro',
                'tipo_servico' => 'reparo',
                'turno' => 'manha',
                'prioridade' => 'normal',
                'status' => 'passada',
                'data_marcacao' => now()->toDateString(),
                'tecnico_id' => $tecnicoHoje->id,
                'user_id' => $user->id,
            ], now()->startOfDay()->addHours(10)->addMinutes($i));
        }

        $tecnicoOntem = Tecnico::create([
            'nome' => 'Tecnico Ontem',
            'telefone' => '73977777777',
            'ativo' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->criarOrdemServicoComAtualizacao([
                'cliente_nome' => 'Cliente Ontem '.$i,
                'cliente_telefone' => '7392000000'.$i,
                'bairro' => 'Centro',
                'tipo_servico' => 'instalacao',
                'turno' => 'tarde',
                'prioridade' => 'normal',
                'status' => 'passada',
                'data_marcacao' => now()->subDay()->toDateString(),
                'tecnico_id' => $tecnicoOntem->id,
                'user_id' => $user->id,
            ], now()->subDay()->startOfDay()->addHours(10)->addMinutes($i));
        }

        $tecnicoTeste = Tecnico::create([
            'nome' => 'Tecnico de Teste',
            'telefone' => '73966666666',
            'ativo' => true,
        ]);

        $this->criarOrdemServicoComAtualizacao([
            'cliente_nome' => 'Cliente Teste',
            'cliente_telefone' => '73930000000',
            'bairro' => 'Centro',
            'tipo_servico' => 'upgrade',
            'turno' => 'manha',
            'prioridade' => 'normal',
            'status' => 'concluida',
            'data_marcacao' => now()->toDateString(),
            'tecnico_id' => $tecnicoTeste->id,
            'user_id' => $user->id,
        ], now()->startOfDay()->addHours(12));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('tecnicosSobrecarga', function ($value) use ($tecnicoHoje) {
            $tecnicos = collect($value);
            $registro = $tecnicos->firstWhere('id', $tecnicoHoje->id);

            return $tecnicos->count() === 1
                && (int) ($registro['os_passadas_dia'] ?? 0) === 5;
        });
        $response->assertViewHas('tecnicosLabels', function ($value) use ($tecnicoHoje, $tecnicoOntem, $tecnicoTeste) {
            $labels = collect($value);

            return $labels->contains($tecnicoHoje->nome)
                && $labels->contains($tecnicoOntem->nome)
                && ! $labels->contains($tecnicoTeste->nome);
        });
    }

    private function criarOrdemServicoComAtualizacao(array $attributes, Carbon $updatedAt): OrdemServico
    {
        $ordem = OrdemServico::create($attributes);
        $ordem->timestamps = false;
        $ordem->updated_at = $updatedAt;
        $ordem->saveQuietly();

        return $ordem;
    }

    public function test_status_abertas_considera_apenas_passada(): void
    {
        $this->assertSame(['passada'], OrdemServico::STATUS_ABERTOS);
    }
}
