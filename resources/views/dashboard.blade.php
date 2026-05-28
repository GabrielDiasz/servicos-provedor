@php
    $formatarDelta = function (array $metricas): array {
        $positivo = $metricas['delta'] > 0;
        $negativo = $metricas['delta'] < 0;

        return [
            'texto' => ($positivo ? '+' : '') . $metricas['delta'] . ' no período',
            'badge' => $positivo
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                : ($negativo
                    ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300'
                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'),
            'sinal' => $positivo ? 'alta' : ($negativo ? 'baixa' : 'igual'),
        ];
    };

    $deltaConclusao = $formatarDelta($metricasConclusao);
    $deltaCriacao = $formatarDelta($metricasCriacao);
    $statusResumo = $statusAbertosResumo->keyBy('key');

    $tecnicosLabels = $tecnicos->pluck('nome')->values();
    $tecnicosValores = $tecnicos->pluck('servicos_mes')->values();
    $tiposLabels = $tiposServico->pluck('label')->values();
    $tiposValores = $tiposServico->pluck('total')->values();
    $tiposAbertosLabels = $osAbertasPorTipo->pluck('label')->values();
    $tiposAbertosValores = $osAbertasPorTipo->pluck('total')->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0c5f3a] dark:text-emerald-400">
                    Painel operacional
                </p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-slate-100">Dashboard</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Dados desde {{ $inicioMes->format('d/m/Y') }} com comparação do mês anterior.
                </p>
            </div>

            <a href="{{ route('ordens.create') }}"
               class="inline-flex items-center justify-center rounded-xl bg-[#ff7a00] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#e96f00]">
                + Nova OS
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <section class="overflow-hidden rounded-3xl border border-[#d9e8db] bg-gradient-to-r from-[#064b31] via-[#0a5f3f] to-[#ff7a00] p-6 text-white shadow-xl dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800">
            <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-end">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/90">
                        Visão geral do mês
                    </span>
                    <h3 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">
                        Tudo que importa para a operação em uma tela.
                    </h3>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base">
                        Compare o ritmo de atendimento, veja onde estão as maiores filas e identifique rapidamente
                        os pontos de atenção dos técnicos e dos clientes.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-white/70">Serviços concluídos</p>
                        <div class="mt-2 flex items-end justify-between gap-4">
                            <span class="text-3xl font-bold">{{ $servicosConcluidosNoMes }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $deltaConclusao['badge'] }}">
                                {{ $deltaConclusao['texto'] }}
                            </span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-white/70">Novas OS</p>
                        <div class="mt-2 flex items-end justify-between gap-4">
                            <span class="text-3xl font-bold">{{ $osCriadasNoMes }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $deltaCriacao['badge'] }}">
                                {{ $deltaCriacao['texto'] }}
                            </span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-white/70">OS abertas</p>
                        <p class="mt-2 text-3xl font-bold">{{ $osAbertas }}</p>
                        <p class="mt-1 text-xs text-white/75">Pendentes, passadas, retornos, sem contato e sem viabilidade.</p>
                    </div>
                    <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-white/70">Técnicos ativos</p>
                        <p class="mt-2 text-3xl font-bold">{{ $tecnicosAtivos }}</p>
                        <p class="mt-1 text-xs text-white/75">
                            {{ $tecnicosSobrecarga->count() }} com fila acima do ideal.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#d7e6d9] bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Serviços concluídos</p>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $deltaConclusao['badge'] }}">
                        {{ $deltaConclusao['sinal'] === 'alta' ? 'cresceu' : ($deltaConclusao['sinal'] === 'baixa' ? 'caiu' : 'estável') }}
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-[#064b31] dark:text-emerald-400">{{ $servicosConcluidosNoMes }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ $deltaConclusao['texto'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-[#d7e6d9] bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Novas OS</p>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $deltaCriacao['badge'] }}">
                        {{ $deltaCriacao['sinal'] === 'alta' ? 'cresceu' : ($deltaCriacao['sinal'] === 'baixa' ? 'caiu' : 'estável') }}
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-[#ff7a00] dark:text-amber-400">{{ $osCriadasNoMes }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ $deltaCriacao['texto'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-[#d7e6d9] bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">OS abertas</p>
                <p class="mt-3 text-3xl font-bold text-sky-700 dark:text-sky-400">{{ $osAbertas }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach(['pendente', 'retornar', 'sem_viabilidade'] as $status)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ $statusResumo[$status]['label'] ?? $status }}: {{ $statusResumo[$status]['total'] ?? 0 }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-[#d7e6d9] bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Técnicos sobrecarregados</p>
                <p class="mt-3 text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $tecnicosSobrecarga->count() }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Com 5 ou mais OS abertas.
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-[#d7e6d9] bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Desempenho dos técnicos</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Serviços concluídos no mês atual.</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ $tecnicos->count() }} técnicos
                    </span>
                </div>

                <div class="mt-5 h-[320px]">
                    <canvas
                        data-dashboard-chart="bar"
                        data-chart-title="Serviços por técnico"
                        data-labels='@json($tecnicosLabels)'
                        data-values='@json($tecnicosValores)'
                    ></canvas>
                </div>
            </section>

            <section class="rounded-2xl border border-[#d7e6d9] bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Tipos de serviço concluídos</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Distribuição dos atendimentos finalizados no mês.</p>
                    </div>
                    <span class="rounded-full bg-[#fff3e6] px-3 py-1 text-xs font-semibold text-[#b65300] dark:bg-amber-950/40 dark:text-amber-300">
                        Top {{ $tiposServico->count() }}
                    </span>
                </div>

                <div class="mt-5 h-[320px]">
                    <canvas
                        data-dashboard-chart="bar"
                        data-chart-title="Tipos de serviço"
                        data-labels='@json($tiposLabels)'
                        data-values='@json($tiposValores)'
                        data-accent="orange"
                    ></canvas>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-2xl border border-[#d7e6d9] bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">OS abertas por tipo</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Filas que ainda precisam de atenção.</p>
                    </div>
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                        {{ $osAbertasPorTipo->sum('total') }} abertas
                    </span>
                </div>

                <div class="mt-5 grid gap-6 lg:grid-cols-[1fr_0.95fr]">
                    <div class="h-[300px]">
                        <canvas
                            data-dashboard-chart="doughnut"
                            data-chart-title="OS abertas por tipo"
                            data-labels='@json($tiposAbertosLabels)'
                            data-values='@json($tiposAbertosValores)'
                        ></canvas>
                    </div>

                    <div class="space-y-3">
                        @foreach($osAbertasPorTipo as $item)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item['label'] }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $item['total'] }}
                                    </span>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-2 rounded-full bg-sky-600 dark:bg-sky-500"
                                         style="width: {{ ($item['total'] / $maiorQuantidadeTipoAberto) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-[#d7e6d9] bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Alertas operacionais</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Técnicos com fila acima do ideal e clientes com mais OS abertas.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200">Técnicos sobrecarregados</h4>
                        <div class="mt-3 space-y-2">
                            @forelse($tecnicosSobrecarga as $tecnico)
                                <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-900">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ $tecnico->nome }}</span>
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                        {{ $tecnico->os_abertas }} OS abertas
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-amber-900/80 dark:text-amber-200/80">Nenhum técnico acima do limite ideal neste momento.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Clientes com mais OS abertas</h4>
                        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead class="bg-slate-100 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Cliente</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Abertas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                                    @forelse($clientesComMaisAbertas as $cliente)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                            <td class="px-4 py-3 text-slate-900 dark:text-slate-100">
                                                <div class="font-medium">{{ $cliente->cliente_nome }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $cliente->cliente_telefone ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                                    {{ $cliente->total }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                                Nenhuma OS aberta encontrada.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

    </div>
</x-app-layout>
