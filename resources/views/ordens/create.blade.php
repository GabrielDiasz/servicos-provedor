<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-slate-100">Nova Ordem de Serviço</h2>
    </x-slot>

    <div class="py-4 max-w-4xl mx-auto px-4 sm:px-6 lg:px-10">
        <div class="app-surface p-6">

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-100 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('ordens.store') }}" class="space-y-2" x-on:submit="$dispatch('busy-start', { label: 'Salvando OS...' })">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Link do cliente no SGP</label>
                    <div class="flex gap-2">
                        <input type="url" name="sgp_cliente_link" id="sgp_cliente_link" value="{{ old('sgp_cliente_link') }}"
                               placeholder="Cole o link da página de cadastro do cliente no SGP"
                               class="app-field flex-1">
                        <button type="button" id="buscar-sgp"
                                class="app-btn-secondary px-4 py-2.5 disabled:cursor-not-allowed disabled:opacity-60">
                            Buscar
                        </button>
                    </div>
                    <p id="sgp-feedback" class="mt-1 text-xs text-gray-400 dark:text-slate-400">Cole o link do cliente no SGP para preencher os dados principais.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Cliente *</label>
                        <input type="text" name="cliente_nome" id="cliente_nome" value="{{ old('cliente_nome') }}"
                               class="app-field w-full"
                               required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Telefone *</label>
                        <input type="text" name="cliente_telefone" id="cliente_telefone" value="{{ old('cliente_telefone') }}"
                               class="app-field w-full"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}"
                               class="app-field w-full"
                               required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Tipo de Serviço *</label>
                        <x-sgp-select
                                name="tipo_servico"
                                :options="$tipoOptions"
                                :selected="old('tipo_servico')"
                                placeholder="Selecione..."
                                class="w-full"
                            />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Turno *</label>
                        <x-sgp-select
                                name="turno"
                                :options="$turnoOptions"
                                :selected="old('turno')"
                                placeholder="Selecione..."
                                class="w-full"
                            />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Prioridade *</label>
                        <x-sgp-select
                                name="prioridade"
                                :options="$prioridadeOptions"
                                :selected="old('prioridade', 'normal')"
                                placeholder="Selecione..."
                                class="w-full"
                            />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Data de Marcação *</label>
                        <input type="date" name="data_marcacao" value="{{ old('data_marcacao') }}"
                               class="app-field w-full"
                               required>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Técnico</label>
                        <x-sgp-select
                                name="tecnico_id"
                                :options="$tecnicoOptions"
                                :selected="old('tecnico_id')"
                                placeholder="Sem técnico"
                                class="w-full"
                            />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Observação</label>
                    <textarea name="observacao" rows="3"
                              class="app-field w-full">{{ old('observacao') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-400">Obrigatório para Upgrade.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="app-btn-primary">
                        Salvar OS
                    </button>
                    <a href="{{ route('ordens.index') }}"
                       class="app-btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('buscar-sgp')?.addEventListener('click', async () => {
            const feedback = document.getElementById('sgp-feedback');
            const link = document.getElementById('sgp_cliente_link').value;
            const button = document.getElementById('buscar-sgp');

            if (!link.trim()) {
                feedback.textContent = 'Informe o link ou ID do cliente no SGP antes de buscar.';
                feedback.className = 'text-xs text-red-600 mt-1';
                return;
            }

            feedback.textContent = 'Buscando cliente no SGP...';
            feedback.className = 'text-xs text-gray-500 mt-1';
            button.disabled = true;
            button.textContent = 'Buscando...';

            try {
                const response = await fetch('{{ route('ordens.buscar-sgp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ sgp_cliente_link: link }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Cliente não encontrado no SGP.');
                }

                document.getElementById('cliente_nome').value = data.cliente_nome || '';
                document.getElementById('cliente_telefone').value = data.cliente_telefone || '';
                document.getElementById('bairro').value = data.bairro || '';

                feedback.textContent = `Cliente encontrado: ID ${data.sgp_cliente_id || '-'} | Contrato ${data.sgp_contrato_id || '-'}`;
                feedback.className = 'text-xs text-green-600 mt-1';
            } catch (error) {
                feedback.textContent = error.message;
                feedback.className = 'text-xs text-red-600 mt-1';
            } finally {
                button.disabled = false;
                button.textContent = 'Buscar';
            }
        });
    </script>
</x-app-layout>
