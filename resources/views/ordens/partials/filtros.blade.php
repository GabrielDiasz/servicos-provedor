<div class="mb-3">
    <div class="app-surface w-full overflow-hidden">
        <button type="button" x-on:click="filtersOpen = !filtersOpen"
            x-bind:aria-expanded="filtersOpen.toString()"
            aria-controls="ordens-filters-panel"
            class="group flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-white/[0.03] focus:outline-none">
            <div class="flex min-w-0 items-center gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-[#ff5a00]/20 bg-[#ff5a00]/10 text-[#ffb07a]">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M3 5h14M6 10h8M8 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#ff5a00]">
                        <span class="mr-1 inline-block transition-transform duration-150" x-bind:class="filtersOpen ? 'rotate-90' : ''">▸</span>
                        Filtros
                    </p>
                    <p class="mt-1 text-sm text-[#a1a1aa]">
                        Refinar por status, técnico, tipo, prioridade e data.
                    </p>
                </div>
            </div>

            <a href="{{ route('ordens.create') }}"
                class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#ff5a00] px-4 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#e45200] focus:outline-none focus:ring-2 focus:ring-[#ff5a00]/30 focus:ring-offset-0"
                x-on:click.stop>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Cadastrar Serviço
            </a>
        </button>

        <div x-show="filtersOpen" x-cloak x-transition.opacity x-transition.duration.200ms
            id="ordens-filters-panel"
            class="border-t border-[#3a3a40]">
            <form method="GET" action="{{ route('ordens.index') }}"
                class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2 xl:grid-cols-6">
                <x-sgp-select
                    name="status"
                    :options="$statusFilterOptions"
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
                    :options="$tipoOptions"
                    :selected="request('tipo_servico')"
                    placeholder="Todos os tipos"
                    class="w-full"
                />

                <x-sgp-select
                    name="prioridade"
                    :options="$prioridadeOptions"
                    :selected="request('prioridade')"
                    placeholder="Todas prioridades"
                    class="w-full"
                />

                <input type="text" name="data_marcacao" value="{{ $dataMarcacao }}" data-datepicker
                    placeholder="Selecionar data" class="app-field">

                <div class="flex gap-2 sm:col-span-2 xl:col-span-2">
                    <button type="submit" class="app-btn-primary flex-1">Filtrar</button>
                    <a href="{{ route('ordens.index') }}" class="app-btn-secondary flex-1">Limpar</a>
                </div>
            </form>
        </div>
    </div>
</div>
