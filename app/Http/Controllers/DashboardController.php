<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $agora = now();
        $selectedMonth = max(1, min(12, (int) $request->integer('month', $agora->month)));
        $selectedYear = max(2020, min(2100, (int) $request->integer('year', $agora->year)));
        $cacheKey = sprintf('dashboard:summary:v2:%04d-%02d', $selectedYear, $selectedMonth);

        $data = Cache::remember($cacheKey, 60, function () use ($selectedMonth, $selectedYear, $agora) {
            $inicioMes = $agora->copy()->setYear($selectedYear)->setMonth($selectedMonth)->startOfMonth();
            $fimMes = $inicioMes->copy()->endOfMonth();
            $inicioMesAnterior = $inicioMes->copy()->subMonthNoOverflow()->startOfMonth();
            $fimMesAnterior = $inicioMes->copy()->subSecond();

            $servicosConcluidosNoMes = OrdemServico::query()
                ->where('status', 'concluida')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->count();

            $servicosConcluidosMesAnterior = OrdemServico::query()
                ->where('status', 'concluida')
                ->whereBetween('updated_at', [$inicioMesAnterior, $fimMesAnterior])
                ->count();

            $osPassadasNoMes = OrdemServico::query()
                ->where('status', 'passada')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->count();

            $servicosPorTecnico = OrdemServico::query()
                ->select('tecnico_id', DB::raw('COUNT(*) as total'))
                ->where('status', 'concluida')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->groupBy('tecnico_id')
                ->pluck('total', 'tecnico_id')
                ->map(fn ($total) => (int) $total);

            $tecnicosBase = Tecnico::query()
                ->where('ativo', true)
                ->select(['id', 'nome', 'ativo'])
                ->orderBy('nome')
                ->get();

            $tecnicos = $tecnicosBase
                ->map(function (Tecnico $tecnico) use ($servicosPorTecnico) {
                    $tecnico->servicos_mes = (int) ($servicosPorTecnico[$tecnico->id] ?? 0);

                    return [
                        'id' => $tecnico->id,
                        'nome' => $tecnico->nome,
                        'ativo' => $tecnico->ativo,
                        'servicos_mes' => $tecnico->servicos_mes,
                    ];
                })
                ->sortByDesc('servicos_mes')
                ->values();

            $tecnicosDesempenho = $tecnicos
                ->reject(function (array $tecnico) {
                    return Str::contains(Str::lower($tecnico['nome']), 'teste');
                })
                ->values()
                ->all();

            $servicosConcluidosPorTipo = OrdemServico::query()
                ->select('tipo_servico', DB::raw('COUNT(*) as total'))
                ->where('status', 'concluida')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
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
                ->values()
                ->all();

            $maiorQuantidadeTecnico = max(collect($tecnicosDesempenho)->pluck('servicos_mes')->max() ?? 0, 1);
            $maiorQuantidadeTipoConcluido = max(collect($tiposServico)->pluck('total')->max() ?? 0, 1);
            $tecnicosLabels = collect($tecnicosDesempenho)->map(fn (array $tecnico) => $tecnico['nome'])->values()->all();
            $tecnicosValores = collect($tecnicosDesempenho)->map(fn (array $tecnico) => $tecnico['servicos_mes'])->values()->all();
            $tiposLabels = collect($tiposServico)->map(fn (array $tipo) => $tipo['label'])->values()->all();
            $tiposValores = collect($tiposServico)->map(fn (array $tipo) => $tipo['total'])->values()->all();

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

            return compact(
                'inicioMes',
                'inicioMesAnterior',
                'servicosConcluidosNoMes',
                'osPassadasNoMes',
                'tecnicos',
                'tecnicosDesempenho',
                'tiposServico',
                'tecnicosLabels',
                'tecnicosValores',
                'tiposLabels',
                'tiposValores',
                'maiorQuantidadeTecnico',
                'maiorQuantidadeTipoConcluido',
                'metricasConclusao'
            );
        });

        $data['tecnicos'] = collect($data['tecnicos']);
        $data['tecnicosDesempenho'] = collect($data['tecnicosDesempenho']);
        $data['tiposServico'] = collect($data['tiposServico']);

        $inicioHoje = $agora->copy()->startOfDay();
        $fimHoje = $agora->copy()->endOfDay();
        $osPassadasHojePorTecnico = OrdemServico::query()
            ->select('tecnico_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'passada')
            ->whereBetween('updated_at', [$inicioHoje, $fimHoje])
            ->groupBy('tecnico_id')
            ->pluck('total', 'tecnico_id')
            ->map(fn ($total) => (int) $total);

        $tecnicosBaseHoje = Tecnico::query()
            ->where('ativo', true)
            ->select(['id', 'nome', 'ativo'])
            ->orderBy('nome')
            ->get();

        $data['tecnicosSobrecarga'] = $tecnicosBaseHoje
            ->map(function (Tecnico $tecnico) use ($osPassadasHojePorTecnico) {
                return [
                    'id' => $tecnico->id,
                    'nome' => $tecnico->nome,
                    'ativo' => $tecnico->ativo,
                    'os_passadas_dia' => (int) ($osPassadasHojePorTecnico[$tecnico->id] ?? 0),
                ];
            })
            ->filter(fn (array $tecnico) => $tecnico['os_passadas_dia'] > 4)
            ->sortByDesc('os_passadas_dia')
            ->values();

        $data['periodLabel'] = $this->formatMonthYearLabel($selectedMonth, $selectedYear);
        $data['monthOptions'] = $this->monthOptions();
        $data['yearOptions'] = $this->yearOptions($selectedYear);
        $data['selectedMonth'] = $selectedMonth;
        $data['selectedYear'] = $selectedYear;
        $data['currentMonthLabel'] = $this->formatMonthYearLabel($agora->month, $agora->year);

        return view('dashboard', $data);
    }

    private function monthOptions(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }

    private function yearOptions(int $selectedYear): array
    {
        $currentYear = now()->year;
        $startYear = min($currentYear - 5, $selectedYear);
        $endYear = max($currentYear + 1, $selectedYear);

        return range($startYear, $endYear);
    }

    private function formatMonthYearLabel(int $month, int $year): string
    {
        $months = $this->monthOptions();

        return ($months[$month] ?? 'Mês').' de '.$year;
    }
}
