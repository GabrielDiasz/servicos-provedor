<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Nova Ordem de Serviço</h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('ordens.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link do cliente no SGP</label>
                    <div class="flex gap-2">
                        <input type="url" name="sgp_cliente_link" id="sgp_cliente_link" value="{{ old('sgp_cliente_link') }}"
                               placeholder="https://gpr.sgp.net.br/admin/cliente/3176/edit/"
                               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" id="buscar-sgp"
                                class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm font-medium">
                            Buscar
                        </button>
                    </div>
                    <p id="sgp-feedback" class="text-xs text-gray-400 mt-1">Cole o link do cliente no SGP para preencher os dados principais.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <input type="text" name="cliente_nome" id="cliente_nome" value="{{ old('cliente_nome') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone *</label>
                        <input type="text" name="cliente_telefone" id="cliente_telefone" value="{{ old('cliente_telefone') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Serviço *</label>
                        <select name="tipo_servico"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\OrdemServico::TIPOS as $key => $label)
                                <option value="{{ $key }}" {{ old('tipo_servico') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Turno *</label>
                        <select name="turno"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\OrdemServico::TURNOS as $key => $label)
                                <option value="{{ $key }}" {{ old('turno') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade *</label>
                        <select name="prioridade"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @foreach(\App\Models\OrdemServico::PRIORIDADES as $key => $label)
                                <option value="{{ $key }}" {{ old('prioridade', 'normal') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Marcação *</label>
                        <input type="date" name="data_marcacao" value="{{ old('data_marcacao') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Técnico *</label>
                    <select name="tecnico_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Selecione um técnico...</option>
                        @foreach($tecnicos as $tecnico)
                            <option value="{{ $tecnico->id }}" {{ old('tecnico_id') == $tecnico->id ? 'selected' : '' }}>
                                {{ $tecnico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                    <textarea name="observacao" rows="3"
                              class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('observacao') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Salvar OS
                    </button>
                    <a href="{{ route('ordens.index') }}"
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
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

            feedback.textContent = 'Buscando cliente no SGP...';
            feedback.className = 'text-xs text-gray-500 mt-1';

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
            }
        });
    </script>
</x-app-layout>
