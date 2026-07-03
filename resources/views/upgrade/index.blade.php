<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-slate-100">Upgrade</h2>
    </x-slot>

    @php
        $phoneOptions = [
            'auto' => 'Automático',
            'primeiro' => 'Primeiro contato',
            'segundo' => 'Segundo contato',
        ];
    @endphp

    <div class="py-4 max-w-[92rem] mx-auto px-4 sm:px-6 lg:px-8">
        <div
            data-upgrade-page
            @if($campaign)
                data-status-url="{{ route('upgrade.status', $campaign) }}"
            @endif
            class="space-y-6"
        >
            @if(session('success'))
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="app-surface p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#ff7a00]">Upgrade</p>
                        <h3 class="mt-1 text-2xl font-semibold text-white">Importar planilha</h3>
                        <p class="mt-1 text-sm text-slate-400">
                            Envie um arquivo Excel com as colunas Nome do cliente, Primeiro contato e Segundo contato.
                        </p>
                    </div>

                    @if($campaign)
                        <div class="flex flex-wrap gap-2">
                            <button type="button" data-upgrade-refresh class="app-btn-secondary">
                                Atualizar tabela
                            </button>

                            <form method="POST" action="{{ route('upgrade.destroy', $campaign) }}" onsubmit="return confirm('Deseja remover esta planilha carregada?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="app-btn-secondary border-rose-500/30 text-rose-300 hover:bg-rose-500/10">
                                    Remover planilha carregada
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('upgrade.importar') }}" enctype="multipart/form-data" class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center">
                    @csrf
                    <input
                        type="file"
                        name="arquivo"
                        accept=".xlsx,.xls"
                        class="app-field flex-1"
                        required
                    >
                    <button type="submit" class="app-btn-primary px-5 py-3">
                        Importar Planilha
                    </button>
                </form>

                @error('arquivo')
                    <p class="mt-3 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            @if($campaign)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="app-surface p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Clientes carregados</p>
                        <p class="mt-2 text-3xl font-semibold text-white" data-upgrade-total-count>{{ $campaign->total_clientes }}</p>
                    </div>
                    <div class="app-surface p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Selecionados</p>
                        <p class="mt-2 text-3xl font-semibold text-white" data-upgrade-selected-count>{{ $campaign->selecionados }}</p>
                    </div>
                    <div class="app-surface p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Enviados</p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-300" data-upgrade-sent-count>{{ $campaign->enviados }}</p>
                    </div>
                    <div class="app-surface p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Falhas</p>
                        <p class="mt-2 text-3xl font-semibold text-rose-300" data-upgrade-failed-count>{{ $campaign->falhas }}</p>
                    </div>
                </div>

                <div class="app-surface p-6" x-data>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-[#ff7a00]">Campanha atual</p>
                            <h3 class="mt-1 text-2xl font-semibold text-white">{{ $campaign->nome_arquivo }}</h3>
                            <p class="mt-1 text-sm text-slate-400">
                                Status da campanha:
                                <span class="ml-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $campaign->status_badge_class }}" data-upgrade-status-badge>
                                    {{ $campaign->status_label }}
                                </span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Erro mais recente:
                                <span class="text-slate-300" data-upgrade-error-last>{{ $campaign->erro_ultimo ?: 'Nenhum erro registrado.' }}</span>
                            </p>
                        </div>

                        <div class="min-w-[18rem]">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span>Progresso do envio</span>
                                <span data-upgrade-progress-label>{{ $campaign->progresso_percentual }}%</span>
                            </div>
                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-800">
                                <div
                                    data-upgrade-progress-bar
                                    class="h-full rounded-full bg-gradient-to-r from-[#ff7a00] to-[#ffb07a] transition-all duration-300"
                                    style="width: {{ $campaign->progresso_percentual }}%;"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('upgrade.enviar', $campaign) }}" id="upgrade-send-form" data-upgrade-send-form class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="scope" value="selected">

                        <div
                            data-upgrade-live-message
                            class="hidden rounded-xl border border-[#3a3a40] bg-[#2a2a2e] px-4 py-3 text-sm text-slate-200"
                        ></div>

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="flex items-center gap-2 rounded-xl border border-[#3a3a40] bg-[#2a2a2e] px-4 py-3 text-sm text-slate-200">
                                    <input type="checkbox" data-upgrade-select-all class="rounded border-[#4a4a50] bg-[#1f1f22] text-[#ff7a00] focus:ring-[#ff7a00]">
                                    Selecionar todos os visiveis
                                </label>

                                <input
                                    type="search"
                                    data-upgrade-search
                                    placeholder="Pesquisar cliente pelo nome"
                                    class="app-field w-full lg:w-[24rem]"
                                >
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit" data-upgrade-scope="selected" class="app-btn-primary">
                                    Enviar para selecionados
                                </button>
                                <button type="submit" name="scope" value="all" data-upgrade-scope="all" class="app-btn-secondary">
                                    Enviar para todos
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-slate-400">
                            <span>Total importado: <strong class="text-white" data-upgrade-total-count>{{ $campaign->total_clientes }}</strong></span>
                            <span>Selecionados: <strong class="text-white" data-upgrade-selected-count>{{ $campaign->selecionados }}</strong></span>
                            <span>Enviados: <strong class="text-emerald-300" data-upgrade-sent-count>{{ $campaign->enviados }}</strong></span>
                            <span>Falhas: <strong class="text-rose-300" data-upgrade-failed-count>{{ $campaign->falhas }}</strong></span>
                            <span>Status: <strong class="text-white" data-upgrade-status-label>{{ $campaign->status_label }}</strong></span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-[#3a3a40]">
                            <table class="min-w-full divide-y divide-[#3a3a40] text-sm">
                                <thead class="bg-[#2a2a2e] text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">
                                            <input type="checkbox" data-upgrade-select-all class="rounded border-[#4a4a50] bg-[#1f1f22] text-[#ff7a00] focus:ring-[#ff7a00]">
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold">Nome do cliente</th>
                                        <th class="px-4 py-3 text-left font-semibold">Primeiro contato</th>
                                        <th class="px-4 py-3 text-left font-semibold">Segundo contato</th>
                                        <th class="px-4 py-3 text-left font-semibold">Telefone a usar</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold">Ações</th>
                                        <th class="px-4 py-3 text-left font-semibold">Enviado em</th>
                                        <th class="px-4 py-3 text-left font-semibold">Erro</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#333338] bg-[#232326]">
                                    @forelse($contatos as $contato)
                                        <tr
                                            data-upgrade-row
                                            data-upgrade-row-id="{{ $contato->id }}"
                                            data-upgrade-name="{{ \Illuminate\Support\Str::ascii(mb_strtolower($contato->nome_cliente)) }}"
                                            class="align-top transition hover:bg-white/3"
                                        >
                                            <td class="px-4 py-4">
                                                <input
                                                    type="checkbox"
                                                    name="selected_ids[]"
                                                    value="{{ $contato->id }}"
                                                    form="upgrade-send-form"
                                                    data-upgrade-checkbox
                                                    class="rounded border-[#4a4a50] bg-[#1f1f22] text-[#ff7a00] focus:ring-[#ff7a00]"
                                                    @disabled(in_array($contato->status_envio, ['enviado', 'na_fila', 'enviando', 'ignorado'], true))
                                                >
                                            </td>
                                            <td class="px-4 py-4 font-medium text-white">{{ $contato->nome_cliente }}</td>
                                            <td class="px-4 py-4 text-slate-300">{{ $contato->primeiro_contato_formatado ?: '—' }}</td>
                                            <td class="px-4 py-4 text-slate-300">{{ $contato->segundo_contato_formatado ?: '—' }}</td>
                                            <td class="px-4 py-4">
                                                <x-sgp-select
                                                    name="phone_preferences[{{ $contato->id }}]"
                                                    :options="$phoneOptions"
                                                    :selected="$contato->contato_preferido"
                                                    placeholder="Automático"
                                                    size="sm"
                                                    class="w-full min-w-[11rem]"
                                                    @disabled(in_array($contato->status_envio, ['enviado', 'na_fila', 'enviando', 'ignorado'], true))
                                                />
                                            </td>
                                            <td class="px-4 py-4">
                                                <span
                                                    data-upgrade-status-badge
                                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $contato->status_badge_class }}"
                                                >
                                                    {{ $contato->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <button
                                                    type="button"
                                                    data-upgrade-send-single="{{ $contato->id }}"
                                                    class="rounded-lg border border-[#3a3a40] bg-[#2a2a2e] px-3 py-2 text-xs font-semibold text-white transition hover:border-[#ff7a00]/60 hover:bg-[#2f2f33]"
                                                    @disabled(in_array($contato->status_envio, ['enviado', 'na_fila', 'enviando', 'ignorado'], true))
                                                >
                                                    Enviar agora
                                                </button>
                                            </td>
                                            <td class="px-4 py-4 text-slate-300" data-upgrade-sent-at>
                                                {{ $contato->enviado_em?->format('d/m/Y H:i') ?: '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-slate-400" data-upgrade-error>
                                                {{ $contato->erro_envio ?: '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                                Nenhum cliente encontrado nesta planilha.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            @else
                <div class="app-surface p-8 text-center">
                    <p class="text-lg font-semibold text-white">Nenhuma planilha carregada ainda</p>
                    <p class="mt-2 text-sm text-slate-400">
                        Importe uma planilha para visualizar os clientes e iniciar o envio individual pelo WhatsApp.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
