<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#ff5a00]">Ordem de Serviço</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">OS #{{ $ordem->id }}</h2>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                @if ($ordem->canSendWhatsapp())
                    <form method="POST" action="{{ route('ordens.enviar-whatsapp', $ordem) }}" x-on:submit="$dispatch('busy-start', { label: 'Enviando WhatsApp...' })">
                        @csrf
                        <button type="submit"
                                title="Enviar serviço para o técnico pelo WhatsApp"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-500/70 bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-[#2b2b2b]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/>
                            </svg>
                            <span class="sr-only">Enviar pelo WhatsApp</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('ordens.edit', $ordem) }}" class="app-btn-primary h-9 px-4 py-0">
                    Editar
                </a>
                <a href="{{ route('ordens.index') }}" class="app-btn-secondary h-9 px-4 py-0">
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            <div class="app-surface overflow-hidden">
                <div class="border-b border-slate-200/80 px-4 py-4 dark:border-[#4a4a4a] sm:px-5">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ordem->cliente_nome }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ordem->bairro }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ordem->data_marcacao->format('d/m/Y') }} · {{ $ordem->turno_label }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ordem->status_label }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ordem->prioridade_label }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <span>Atendente: <strong class="text-slate-900 dark:text-slate-100">{{ $ordem->atendente->name ?? '-' }}</strong></span>
                            <span class="hidden sm:inline">•</span>
                            <span>Criada em: <strong class="text-slate-900 dark:text-slate-100">{{ $ordem->created_at->format('d/m/Y H:i') }}</strong></span>
                            <span class="hidden xl:inline">•</span>
                            <span class="hidden xl:inline">Sincronização SGP:
                                <strong class="text-slate-900 dark:text-slate-100">
                                    {{ \Illuminate\Support\Str::headline($ordem->sgp_sync_status ?? '-') }}
                                </strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:p-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.9fr)_minmax(0,1fr)]">
                    <section class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-[#4a4a4a] dark:bg-[#2f2f2f]">
                        <div class="mb-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#ff5a00]">Dados principais</p>
                            <h3 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">Atendimento</h3>
                        </div>

                        <dl class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434] sm:col-span-2 lg:col-span-3">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cliente</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->cliente_nome }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Telefone</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->cliente_telefone }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Bairro</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->bairro }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->tipo_servico_label }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Data</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->data_marcacao->format('d/m/Y') }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Turno</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->turno_label }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Técnico</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->tecnico->nome ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prioridade</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->prioridade_label }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Status</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->status_label }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Atendente</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->atendente->name ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Criada em</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>

                        @if ($ordem->observacao)
                            <div class="mt-3 rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Observação</p>
                                <p class="mt-1 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $ordem->observacao }}</p>
                            </div>
                        @endif
                    </section>

                    <section class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-[#4a4a4a] dark:bg-[#2f2f2f]">
                        <div class="mb-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#ff5a00]">Dados SGP</p>
                            <h3 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">Cadastro</h3>
                        </div>

                        <dl class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434] sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Contrato SGP</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_contrato_id ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">CPF/CNPJ</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_cpf_cnpj ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nascimento</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_data_nascimento?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Plano</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_plano ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Vencimento</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_vencimento ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Ocorrência</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_ocorrencia_numero ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">OS SGP</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_os_numero ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">PPPoE</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_pppoe_login ?? '-' }} / {{ $ordem->sgp_pppoe_senha ?? '-' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-[#4a4a4a] dark:bg-[#2f2f2f]">
                        <div class="mb-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#ff5a00]">Infraestrutura</p>
                            <h3 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">CTO, porta e endereço</h3>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Sincronização SGP</p>
                                <div class="mt-2">
                                    @if ($ordem->sgp_sync_status === 'sincronizado')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Sincronizado</span>
                                    @elseif ($ordem->sgp_sync_status === 'erro')
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">Erro</span>
                                    @elseif ($ordem->sgp_sync_status === 'ignorado')
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">Ignorado</span>
                                    @else
                                        <span class="text-sm text-slate-500 dark:text-slate-400">-</span>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">CTO / Porta</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @if ($ctoInfo['has_cto'])
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            CTO: {{ $ctoInfo['cto'] }}
                                        </span>
                                        @if ($ctoInfo['has_porta'])
                                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300">
                                                Porta: {{ $ctoInfo['porta'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                                                Porta: sem porta
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                            Sem CTO
                                        </span>
                                        @if ($ctoInfo['has_porta'])
                                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300">
                                                Porta: {{ $ctoInfo['porta'] }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200/80 bg-white/70 p-3 dark:border-[#505050] dark:bg-[#343434]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Endereço SGP</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $ordem->sgp_endereco ?? '-' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
