<x-app-layout>
    <div x-data="{
        deleteModalOpen: false,
        deleteAction: '',
        deleteLabel: '',
        whatsappModalOpen: false,
        whatsappAction: '',
        whatsappLabel: '',
        whatsappAbrirSgp: false,
        whatsappReady: false,
        whatsappCountdown: 5,
        whatsappTimer: null,
        whatsappReadyTimer: null,
        filtersOpen: false,
        openWhatsappModal(action, label) {
            this.whatsappAction = action;
            this.whatsappLabel = label;
            this.whatsappAbrirSgp = false;
            this.whatsappReady = false;
            this.whatsappCountdown = 5;
            this.whatsappModalOpen = true;
            clearInterval(this.whatsappTimer);
            clearTimeout(this.whatsappReadyTimer);
    
            this.whatsappTimer = setInterval(() => {
                if (this.whatsappCountdown > 1) {
                    this.whatsappCountdown -= 1;
                    return;
                }
    
                this.whatsappCountdown = 0;
                clearInterval(this.whatsappTimer);
            }, 1000);
    
            this.whatsappReadyTimer = setTimeout(() => {
                if (!this.whatsappModalOpen) {
                    return;
                }
    
                this.whatsappReady = true;
                this.whatsappCountdown = 0;
                clearInterval(this.whatsappTimer);
            }, 3000);
        },
        closeWhatsappModal() {
            this.whatsappModalOpen = false;
            this.whatsappReady = false;
            this.whatsappCountdown = 5;
            this.whatsappAbrirSgp = false;
            clearInterval(this.whatsappTimer);
            clearTimeout(this.whatsappReadyTimer);
        }
    }" class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-1 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">

        </div>

        <div class="mb-3">
            <div class="app-surface w-full overflow-hidden">
                <button type="button" x-on:click="filtersOpen = !filtersOpen"
                    class="flex w-full items-center justify-between gap-4 px-4 py-4 text-left transition hover:bg-white/5 dark:hover:bg-white/5">
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#ff5a00]">Filtros</span>
                    </div>

                    <a href="{{ route('ordens.create') }}"
                        class="inline-flex h-7 items-center justify-center rounded-md bg-[#ff5a00] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#e45200] focus:outline-none focus:ring-2 focus:ring-[#ff5a00] focus:ring-offset-2 dark:focus:ring-offset-[#2b2b2b]"
                        x-on:click.stop>
                        Cadastrar Serviço
                    </a>
                </button>

                <div x-show="filtersOpen" x-cloak x-transition.opacity x-transition.duration.200ms
                    class="border-t border-slate-200/70 dark:border-slate-700/70">
                    @php
                        $tecnicoFilterOptions = $tecnicos->mapWithKeys(fn ($tecnico) => [
                            $tecnico->id => $tecnico->nome,
                        ])->all();
                    @endphp

                    <form method="GET" action="{{ route('ordens.index') }}"
                        class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
                        <x-sgp-select
                            name="status"
                            :options="\App\Models\OrdemServico::STATUS"
                            :selected="request('status')"
                            placeholder="Todos os status"
                            class="w-full"
                        />

                        <x-sgp-select
                            name="tecnico_id"
                            :options="$tecnicoFilterOptions"
                            :selected="request('tecnico_id')"
                            placeholder="Todos os técnicos"
                            class="w-full"
                        />

                        <x-sgp-select
                            name="tipo_servico"
                            :options="\App\Models\OrdemServico::TIPOS"
                            :selected="request('tipo_servico')"
                            placeholder="Todos os tipos"
                            class="w-full"
                        />

                        <x-sgp-select
                            name="prioridade"
                            :options="\App\Models\OrdemServico::PRIORIDADES"
                            :selected="request('prioridade')"
                            placeholder="Todas prioridades"
                            class="w-full"
                        />

                        <input type="text" name="data_marcacao" value="{{ $dataMarcacao }}" data-datepicker
                            placeholder="Selecionar data" class="app-field">

                        <div class="flex gap-2">
                            <button type="submit" class="app-btn-primary flex-1">Filtrar</button>
                            <a href="{{ route('ordens.index') }}" class="app-btn-secondary flex-1">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mb-1 flex flex-nowrap gap-2 overflow-x-auto pb-1">
            <div
                class="min-w-[240px] shrink-0 flex-1 rounded-xl border border-blue-300/40 bg-gradient-to-r from-blue-50 to-white px-4  shadow-sm dark:border-blue-500/20 dark:from-blue-500/10 dark:to-[#2f2f2f]/90 dark:shadow-none">
                <div class="flex min-h-14 items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-300">
                            Serviços no dia</p>
                    </div>
                    <span
                        class="inline-flex min-w-12 justify-center rounded-full bg-blue-200 px-3 py-1 text-lg font-semibold text-blue-900 dark:bg-blue-400/20 dark:text-blue-200">
                        {{ (int) ($resumoDia->total ?? 0) }}
                    </span>
                </div>
            </div>

            <div
                class="min-w-[240px] shrink-0 flex-1 rounded-xl border border-amber-300/40 bg-gradient-to-r from-amber-50 to-white px-4  shadow-sm dark:border-amber-500/20 dark:from-amber-500/10 dark:to-[#2f2f2f]/90 dark:shadow-none">
                <div class="flex min-h-14 items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">
                            Serviços passados</p>
                    </div>
                    <span
                        class="inline-flex min-w-12 justify-center rounded-full bg-amber-200 px-3 py-1 text-lg font-semibold text-amber-900 dark:bg-amber-400/20 dark:text-amber-200">
                        {{ (int) ($resumoDia->total_passadas ?? 0) }}
                    </span>
                </div>
            </div>

            <div
                class="min-w-[240px] shrink-0 flex-1 rounded-xl border border-emerald-300/40 bg-gradient-to-r from-emerald-50 to-white px-4  shadow-sm dark:border-emerald-500/20 dark:from-emerald-500/10 dark:to-[#2f2f2f]/90 dark:shadow-none">
                <div class="flex min-h-14 items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">
                            Serviços concluídos</p>
                    </div>
                    <span
                        class="inline-flex min-w-12 justify-center rounded-full bg-emerald-200 px-3 py-1 text-lg font-semibold text-emerald-900 dark:bg-emerald-400/20 dark:text-emerald-200">
                        {{ (int) ($resumoDia->total_concluidas ?? 0) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div
            class="overflow-x-auto rounded-lg border border-[#d7e6d9] bg-white shadow-sm dark:border-[#4a4a4a] dark:bg-[#333333] dark:shadow-none">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[#f5fbf4] dark:bg-[#272727]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serviço</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bairro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Técnico</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/90 dark:divide-slate-700/80">
                    @php
                        $rowStyles = [
                            'passada' => [
                                'class' => 'bg-yellow-50/80 dark:bg-yellow-900/10',
                                'accent' => 'box-shadow: inset 8px 0 0 #facc15;',
                                'id' => 'text-yellow-700 dark:text-yellow-300 font-semibold',
                            ],
                            'concluida' => [
                                'class' => 'bg-emerald-50/80 dark:bg-emerald-900/10',
                                'accent' => 'box-shadow: inset 8px 0 0 #34d399;',
                                'id' => 'text-emerald-700 dark:text-emerald-300 font-semibold',
                            ],
                            'pendente' => [
                                'class' => 'bg-white dark:bg-slate-900',
                                'accent' => 'box-shadow: inset 8px 0 0 #cbd5e1;',
                                'id' => 'text-slate-500 dark:text-slate-400',
                            ],
                            'retornar' => [
                                'class' => 'bg-orange-50/80 dark:bg-orange-900/10',
                                'accent' => 'box-shadow: inset 8px 0 0 #fb923c;',
                                'id' => 'text-orange-700 dark:text-orange-300 font-semibold',
                            ],
                            'sem_contato' => [
                                'class' => 'bg-rose-50/80 dark:bg-rose-900/10',
                                'accent' => 'box-shadow: inset 8px 0 0 #fb7185;',
                                'id' => 'text-rose-700 dark:text-rose-300 font-semibold',
                            ],
                            'sem_viabilidade' => [
                                'class' => 'bg-red-50/80 dark:bg-red-900/10',
                                'accent' => 'box-shadow: inset 8px 0 0 #f87171;',
                                'id' => 'text-red-700 dark:text-red-300 font-semibold',
                            ],
                            'cancelada' => [
                                'class' => 'bg-slate-50/80 dark:bg-slate-800/80',
                                'accent' => 'box-shadow: inset 8px 0 0 #94a3b8;',
                                'id' => 'text-slate-500 dark:text-slate-400',
                            ],
                        ];
                    @endphp
                    @forelse($ordens as $ordem)
                        @php
                            $rowStyle = $rowStyles[$ordem->status] ?? [
                                'class' => 'bg-white dark:bg-slate-900',
                                'accent' => 'box-shadow: inset 8px 0 0 transparent;',
                                'id' => 'text-gray-500',
                            ];
                        @endphp
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors {{ $rowStyle['class'] }}"
                            style="{{ $rowStyle['accent'] }}">
                            <td class="px-4 py-4 align-middle {{ $rowStyle['id'] }}">{{ $ordem->id }}</td>
                            <td class="px-4 py-4 align-middle font-medium text-gray-900">
                                <div class="flex flex-col gap-1 leading-tight">
                                    @if (filled($ordem->sgp_cliente_link))
                                        <a href="{{ $ordem->sgp_cliente_link }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-start gap-1 text-gray-900 transition hover:text-[#ff7a00] hover:underline dark:text-slate-100 dark:hover:text-[#ffb366]">
                                            <span>{{ $ordem->cliente_nome }}</span>
                                            <svg class="mt-0.5 h-3.5 w-3.5 opacity-70" viewBox="0 0 20 20"
                                                fill="currentColor" aria-hidden="true">
                                                <path
                                                    d="M11 3a1 1 0 1 0 0 2h2.586l-7.293 7.293a1 1 0 0 0 1.414 1.414L15 6.414V9a1 1 0 1 0 2 0V3h-6z" />
                                                <path
                                                    d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 1 0 0-2H5z" />
                                            </svg>
                                        </a>
                                    @else
                                        <span>{{ $ordem->cliente_nome }}</span>
                                    @endif
                                    <span class="text-xs font-normal text-gray-500 dark:text-slate-400">
                                        {{ $ordem->cliente_telefone }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-middle">
                                {{ \App\Models\OrdemServico::TIPOS[$ordem->tipo_servico] ?? ($ordem->tipo_servico ?? '-') }}
                            </td>
                            <td class="px-4 py-4 align-middle text-gray-600">{{ $ordem->bairro }}</td>
                            <td class="px-4 py-4 align-middle">
                                @php
                                    $tecnicoOptions = $tecnicosDisponiveis
                                        ->mapWithKeys(fn ($tecnico) => [
                                            $tecnico->id => $tecnico->nome . ($tecnico->ativo ? '' : ' (Inativo)'),
                                        ])
                                        ->all();
                                @endphp
                                <form method="POST" action="{{ route('ordens.atualizar-tecnico', $ordem) }}"
                                    class="flex items-center">
                                    @csrf
                                    @method('PATCH')
                                    <x-sgp-select
                                        name="tecnico_id"
                                        :options="$tecnicoOptions"
                                        :selected="$ordem->tecnico_id"
                                        placeholder="Sem técnico"
                                        size="sm"
                                        submit-on-change="true"
                                        busy-label="Atualizando técnico..."
                                        class="w-full min-w-40 max-w-44"
                                    />
                                </form>
                            </td>
                            <td class="px-4 py-4 align-middle text-gray-600">
                                <div class="flex flex-col items-start gap-1 leading-tight">
                                    <span>{{ $ordem->data_marcacao->format('d/m/Y') }}</span>
                                    @php
                                        $turnoCores = [
                                            'manha' =>
                                                'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-400/15 dark:text-sky-200 dark:border-sky-400/20',
                                            'tarde' =>
                                                'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-400/15 dark:text-amber-200 dark:border-amber-400/20',
                                        ];
                                    @endphp
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold tracking-wide {{ $turnoCores[$ordem->turno] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600' }}">
                                        {{ \App\Models\OrdemServico::TURNOS[$ordem->turno] ?? ($ordem->turno ?? '-') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-middle">
                                @php
                                    $cores = [
                                        'normal' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-200',
                                        'alta' =>
                                            'bg-yellow-100 text-yellow-700 dark:bg-amber-400/15 dark:text-amber-200',
                                        'urgente' => 'bg-red-100 text-red-700 dark:bg-red-400/15 dark:text-red-200',
                                    ];
                                @endphp
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium leading-none {{ $cores[$ordem->prioridade] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">
                                    {{ \App\Models\OrdemServico::PRIORIDADES[$ordem->prioridade] ?? ($ordem->prioridade ?? '-') }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-middle">
                                @php
                                    $statusOptions = \App\Models\OrdemServico::STATUS;

                                    if ($ordem->status !== 'passada') {
                                        unset($statusOptions['passada']);
                                    }
                                @endphp
                                <form method="POST" action="{{ route('ordens.atualizar-status', $ordem) }}"
                                    class="flex items-center">
                                    @csrf
                                    @method('PATCH')
                                    <x-sgp-select
                                        name="status"
                                        :options="$statusOptions"
                                        :selected="$ordem->status"
                                        placeholder="Status"
                                        size="sm"
                                        submit-on-change="true"
                                        busy-label="Atualizando status..."
                                        class="w-full min-w-32 max-w-36"
                                    />
                                </form>
                            </td>
                            <td class="px-4 py-4 align-middle">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    @if ($ordem->tecnico_id)
                                        <button type="button" title="Enviar serviço para o técnico pelo WhatsApp"
                                            x-on:click="openWhatsappModal(@js(route('ordens.enviar-whatsapp', $ordem)), @js('OS #' . $ordem->id . ' - ' . $ordem->cliente_nome))"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-emerald-500/10 text-emerald-700 transition hover:border-emerald-500 hover:bg-emerald-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-white">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"
                                                aria-hidden="true">
                                                <path
                                                    d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z" />
                                            </svg>
                                        </button>
                                    @endif

                                    <a href="{{ route('ordens.show', $ordem) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-[#ff7a00] hover:bg-[#fff8f2] hover:text-[#ff7a00] focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[#ff7a00] dark:hover:bg-[#3a3128] dark:hover:text-[#ffb366] dark:focus:ring-offset-[#2b2b2b]">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"
                                            aria-hidden="true">
                                            <path
                                                d="M10 3.75c-4.2 0-7.71 2.69-9.02 6.5 1.31 3.81 4.82 6.5 9.02 6.5s7.71-2.69 9.02-6.5c-1.31-3.81-4.82-6.5-9.02-6.5Zm0 10.75a4.25 4.25 0 1 1 0-8.5 4.25 4.25 0 0 1 0 8.5Zm0-2.25a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                        </svg>
                                    </a>

                                    <button type="button" title="Excluir ordem de serviço"
                                        x-on:click="deleteModalOpen = true; deleteAction = @js(route('ordens.destroy', $ordem)); deleteLabel = @js('OS #' . $ordem->id . ' - ' . $ordem->cliente_nome)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-red-200 bg-white text-red-600 shadow-sm transition hover:border-red-500 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:border-red-900/60 dark:bg-slate-900 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-950/30 dark:hover:text-red-300 dark:focus:ring-offset-[#2b2b2b]">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 6l-1 14H6L5 6" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10 11v5M14 11v5" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                Nenhuma ordem de serviço encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $ordens->links() }}</div>

        <div x-show="whatsappModalOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 px-4">
            <div x-show="whatsappModalOpen" x-transition x-on:click.outside="closeWhatsappModal()"
                class="w-full max-w-md rounded-xl border border-slate-700 bg-[#2f2f2f] p-6 shadow-2xl">
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-white">Confirmar envio</h3>
                        <p class="mt-1 text-sm text-slate-300">
                            Você está prestes a enviar <span class="font-semibold text-white"
                                x-text="whatsappLabel"></span>.
                        </p>
                    </div>
                </div>

                <button type="button" role="checkbox" x-bind:aria-checked="whatsappAbrirSgp.toString()"
                    x-on:click="whatsappAbrirSgp = !whatsappAbrirSgp"
                    class="mt-5 flex w-full items-start gap-3 rounded-lg border border-slate-600 bg-[#353535] px-4 py-3 text-left transition focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 focus:ring-offset-[#2f2f2f]"
                    x-bind:class="whatsappAbrirSgp ? 'border-[#ff7a00]/70 bg-[#3a3128]' : 'hover:border-slate-500'">
                    <span
                        class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-slate-400 bg-[#1f1f1f] shadow-inner transition duration-150 ease-out"
                        x-bind:class="whatsappAbrirSgp
                            ?
                            'border-[#ff7a00] bg-[#ff7a00] shadow-[0_0_0_4px_rgba(255,122,0,0.18)]' :
                            'hover:border-slate-300'">
                        <svg class="h-4 w-4 text-white transition-opacity"
                            x-bind:class="whatsappAbrirSgp ? 'opacity-100' : 'opacity-0'" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.3a1 1 0 0 1-1.42.006L3.29 9.29a1 1 0 1 1 1.42-1.41l3.03 3.05 6.54-6.58a1 1 0 0 1 1.414-.06Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span class="mt-0.5 text-sm text-slate-100">
                        Abrir ocorrência no SGP
                        <span class="mt-1 block text-xs text-slate-400">
                            Se desmarcado, envia apenas a mensagem no grupo do WhatsApp.
                        </span>
                    </span>
                </button>

                <form method="POST" x-bind:action="whatsappAction"
                    class="mt-6 flex w-full items-center justify-between gap-3"
                    x-on:submit="$dispatch('busy-start', { label: 'Enviando WhatsApp...' })">
                    @csrf
                    <input type="hidden" name="abrir_ocorrencia_sgp" :value="whatsappAbrirSgp ? '1' : '0'">
                    <button type="submit" x-bind:disabled="!whatsappReady"
                        x-bind:class="whatsappReady ? 'bg-[#ff7a00] hover:bg-[#e96c00] text-white' :
                            'cursor-not-allowed bg-[#ff7a00]/50 text-white/80'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 focus:ring-offset-[#2f2f2f]">
                        <span x-show="whatsappReady">Sim</span>
                        <span x-show="!whatsappReady" x-cloak x-text="`Aguarde ${whatsappCountdown}s`"></span>
                    </button>
                    <button type="button" x-on:click="closeWhatsappModal()"
                        class="rounded-lg border border-slate-600 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/5">
                        Cancelar
                    </button>
                </form>
            </div>
        </div>

        <div x-show="deleteModalOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div x-show="deleteModalOpen" x-transition x-on:click.outside="deleteModalOpen = false"
                class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:border dark:border-slate-700 dark:bg-slate-900 dark:shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Excluir ordem de serviço?</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">
                    Esta ação vai remover definitivamente <span class="font-semibold" x-text="deleteLabel"></span>.
                </p>

                <form method="POST" x-bind:action="deleteAction" class="mt-6 flex justify-end gap-3"
                    x-on:submit="$dispatch('busy-start', { label: 'Excluindo OS...' })">
                    @csrf
                    @method('DELETE')
                    <button type="button" x-on:click="deleteModalOpen = false"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

