<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $agora = now();
        $inicioMes = $agora->copy()->startOfMonth();
        $inicioMesAnterior = $agora->copy()->subMonthNoOverflow()->startOfMonth();
        $fimMesAnterior = $inicioMes->copy()->subSecond();
        $statusAbertos = ['pendente', 'passada', 'retornar', 'sem_contato', 'sem_viabilidade'];
        $statusResumo = ['pendente', 'passada', 'retornar', 'sem_contato', 'sem_viabilidade'];

        $tecnicosAtivos = Tecnico::where('ativo', true)->count();

        $servicosConcluidosNoMes = OrdemServico::query()
            ->where('status', 'concluida')
            ->whereBetween('updated_at', [$inicioMes, $agora])
            ->count();

        $servicosConcluidosMesAnterior = OrdemServico::query()
            ->where('status', 'concluida')
            ->whereBetween('updated_at', [$inicioMesAnterior, $fimMesAnterior])
            ->count();

        $osCriadasNoMes = OrdemServico::query()
            ->whereBetween('created_at', [$inicioMes, $agora])
            ->count();

        $osCriadasMesAnterior = OrdemServico::query()
            ->whereBetween('created_at', [$inicioMesAnterior, $fimMesAnterior])
            ->count();

        $osAbertas = OrdemServico::query()
            ->whereIn('status', $statusAbertos)
            ->count();

        $servicosPorTecnico = OrdemServico::query()
            ->select('tecnico_id', DB::raw('COUNT(*) as total'))
            ->with('tecnico:id,nome')
            ->where('status', 'concluida')
            ->whereBetween('updated_at', [$inicioMes, $agora])
            ->groupBy('tecnico_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->tecnico_id => (int) $row->total]);

        $osAbertasPorTecnico = OrdemServico::query()
            ->select('tecnico_id', DB::raw('COUNT(*) as total'))
            ->with('tecnico:id,nome')
            ->whereIn('status', $statusAbertos)
            ->groupBy('tecnico_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->tecnico_id => (int) $row->total]);

        $tecnicosBase = Tecnico::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $tecnicos = $tecnicosBase
            ->map(function (Tecnico $tecnico) use ($servicosPorTecnico) {
                $tecnico->servicos_mes = (int) ($servicosPorTecnico[$tecnico->id] ?? 0);

                return $tecnico;
            })
            ->sortByDesc('servicos_mes')
            ->values();

        $tecnicosSobrecarga = $tecnicosBase
            ->map(function (Tecnico $tecnico) use ($servicosPorTecnico, $osAbertasPorTecnico) {
                $tecnico->servicos_mes = (int) ($servicosPorTecnico[$tecnico->id] ?? 0);
                $tecnico->os_abertas = (int) ($osAbertasPorTecnico[$tecnico->id] ?? 0);

                return $tecnico;
            })
            ->filter(fn (Tecnico $tecnico) => $tecnico->os_abertas >= 5)
            ->sortByDesc('os_abertas')
            ->values();

        $servicosConcluidosPorTipo = OrdemServico::query()
            ->select('tipo_servico', DB::raw('COUNT(*) as total'))
            ->where('status', 'concluida')
            ->whereBetween('updated_at', [$inicioMes, $agora])
            ->groupBy('tipo_servico')
            ->pluck('total', 'tipo_servico');

        $tiposServico = collect(OrdemServico::TIPOS)
            ->map(function ($label, $key) use ($servicosConcluidosPorTipo) {
                return [
                    'key' => $key,
                    'label' => $label,
                    'total' => (int) ($servicosConcluidosPorTipo[$key] ?? 0),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $statusAbertosPorStatus = OrdemServico::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', $statusResumo)
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusAbertosResumo = collect($statusResumo)
            ->map(function (string $status) use ($statusAbertosPorStatus) {
                return [
                    'key' => $status,
                    'label' => OrdemServico::STATUS[$status] ?? $status,
                    'total' => (int) ($statusAbertosPorStatus[$status] ?? 0),
                ];
            })
            ->values();

        $osAbertasPorTipoCounts = OrdemServico::query()
            ->select('tipo_servico', DB::raw('COUNT(*) as total'))
            ->whereIn('status', $statusAbertos)
            ->groupBy('tipo_servico')
            ->pluck('total', 'tipo_servico');

        $osAbertasPorTipo = collect(OrdemServico::TIPOS)
            ->map(function ($label, $key) use ($osAbertasPorTipoCounts) {
                return [
                    'key' => $key,
                    'label' => $label,
                    'total' => (int) ($osAbertasPorTipoCounts[$key] ?? 0),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $clientesComMaisAbertas = OrdemServico::query()
            ->selectRaw('COALESCE(sgp_cliente_id, cliente_nome) as cliente_key, MAX(cliente_nome) as cliente_nome, MAX(cliente_telefone) as cliente_telefone, COUNT(*) as total')
            ->whereIn('status', $statusAbertos)
            ->groupByRaw('COALESCE(sgp_cliente_id, cliente_nome)')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $maiorQuantidadeTecnico = max($tecnicos->pluck('servicos_mes')->max() ?? 0, 1);
        $maiorQuantidadeTipoConcluido = max($tiposServico->pluck('total')->max() ?? 0, 1);
        $maiorQuantidadeTipoAberto = max($osAbertasPorTipo->pluck('total')->max() ?? 0, 1);

        $comparativo = function (int $atual, int $anterior): array {
            $delta = $atual - $anterior;
            $percentual = $anterior > 0
                ? round((($atual - $anterior) / $anterior) * 100, 1)
                : ($atual > 0 ? 100.0 : 0.0);

            return [
                'atual' => $atual,
                'anterior' => $anterior,
                'delta' => $delta,
                'percentual' => $percentual,
                'direcao' => $delta > 0 ? 'alta' : ($delta < 0 ? 'baixa' : 'igual'),
            ];
        };

        $metricasConclusao = $comparativo($servicosConcluidosNoMes, $servicosConcluidosMesAnterior);
        $metricasCriacao = $comparativo($osCriadasNoMes, $osCriadasMesAnterior);

        return view('dashboard', compact(
            'inicioMes',
            'inicioMesAnterior',
            'tecnicosAtivos',
            'servicosConcluidosNoMes',
            'servicosConcluidosMesAnterior',
            'osCriadasNoMes',
            'osCriadasMesAnterior',
            'osAbertas',
            'tecnicos',
            'tecnicosSobrecarga',
            'tiposServico',
            'statusAbertosResumo',
            'osAbertasPorTipo',
            'clientesComMaisAbertas',
            'maiorQuantidadeTecnico',
            'maiorQuantidadeTipoConcluido',
            'maiorQuantidadeTipoAberto',
            'metricasConclusao',
            'metricasCriacao'
        ));
    }
}
