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
    $yearSelectOptions = collect($yearOptions)->mapWithKeys(fn ($year) => [$year => $year])->all();
@endphp

<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('dashboard') }}" class="app-surface p-4 dark:bg-[#333333]">
            <div class="grid gap-4 lg:grid-cols-[1.3fr_auto] lg:items-center">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#ff7a00]">Período</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Filtre por mês e ano para revisar a operação.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white/75 px-3 py-3 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-white/5">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(10rem,11rem)_minmax(7rem,8rem)_auto] sm:items-end">
                        <div>
                            <label for="month" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                Mês
                            </label>
                            <x-sgp-select
                                id="month"
                                name="month"
                                :options="$monthOptions"
                                :selected="$selectedMonth"
                                placeholder="Selecione o mês"
                                class="w-full"
                            />
                        </div>

                        <div>
                            <label for="year" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                Ano
                            </label>
                            <x-sgp-select
                                id="year"
                                name="year"
                                :options="$yearSelectOptions"
                                :selected="$selectedYear"
                                placeholder="Selecione o ano"
                                class="w-full"
                            />
                        </div>

                        <button type="submit" class="app-btn-primary h-11 px-6 sm:ml-1">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <section class="overflow-hidden rounded-[28px] border border-slate-700/70 bg-[linear-gradient(135deg,#0b1220_0%,#101929_48%,#16243a_100%)] text-white shadow-[0_24px_70px_rgba(0,0,0,0.28)]">
            <div class="h-1 bg-gradient-to-r from-[#ff7a00] via-[#ff9b45] to-transparent"></div>
            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.15fr_0.85fr] lg:gap-8 lg:px-8 lg:py-8">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/90">
                        Visão geral do mês de {{ $periodLabel }}
                    </span>
                    <h3 class="max-w-2xl text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                        Tudo que importa para a operação em uma tela.
                    </h3>
                    <p class="max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Compare o ritmo de atendimento, veja onde estão as maiores filas e identifique rapidamente
                        os pontos de atenção dos técnicos e dos clientes.
                    </p>
                </div>

                <div class="grid gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-sm backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-white/60">Serviços concluídos</p>
                                <p class="mt-2 text-4xl font-semibold tracking-tight text-white">{{ $servicosConcluidosNoMes }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $deltaConclusao['badge'] }}">
                                {{ $deltaConclusao['texto'] }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-white/70">
                            Finalizados dentro do período selecionado.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-white/60">OS passadas</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-white">{{ $osPassadasNoMes }}</p>
                            <p class="mt-2 text-xs leading-5 text-white/70">Somente OS com status passada.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-white/60">Técnicos sobrecarregados</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-white">{{ $tecnicosSobrecarga->count() }}</p>
                            <p class="mt-2 text-xs leading-5 text-white/70">Mais de 4 OS passadas hoje.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-[#d7e6d9] bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Desempenho dos técnicos</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Serviços concluídos no período selecionado.</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ $tecnicosDesempenho->count() }} técnicos
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
                        <p class="text-sm text-gray-500 dark:text-slate-400">Distribuição dos atendimentos finalizados no período.</p>
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
    </div>
</x-app-layout>
