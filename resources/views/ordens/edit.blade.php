<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar OS #{{ $ordem->id }}</h2>
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

            <form method="POST" action="{{ route('ordens.update', $ordem) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <input type="text" name="cliente_nome" value="{{ old('cliente_nome', $ordem->cliente_nome) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone *</label>
                        <input type="text" name="cliente_telefone" value="{{ old('cliente_telefone', $ordem->cliente_telefone) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                        <input type="text" name="bairro" value="{{ old('bairro', $ordem->bairro) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Serviço *</label>
                        <select name="tipo_servico" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                            @foreach(\App\Models\OrdemServico::TIPOS as $key => $label)
                                <option value="{{ $key }}" {{ old('tipo_servico', $ordem->tipo_servico) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Turno *</label>
                        <select name="turno" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                            @foreach(\App\Models\OrdemServico::TURNOS as $key => $label)
                                <option value="{{ $key }}" {{ old('turno', $ordem->turno) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade *</label>
                        <select name="prioridade" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                            @foreach(\App\Models\OrdemServico::PRIORIDADES as $key => $label)
                                <option value="{{ $key }}" {{ old('prioridade', $ordem->prioridade) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                            @foreach(\App\Models\OrdemServico::STATUS as $key => $label)
                                <option value="{{ $key }}" {{ old('status', $ordem->status) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                        <input type="date" name="data_marcacao"
                               value="{{ old('data_marcacao', $ordem->data_marcacao->format('Y-m-d')) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Técnico *</label>
                    <select name="tecnico_id" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                        @foreach($tecnicos as $tecnico)
                            <option value="{{ $tecnico->id }}" {{ old('tecnico_id', $ordem->tecnico_id) == $tecnico->id ? 'selected' : '' }}>
                                {{ $tecnico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                    <textarea name="observacao" rows="3"
                              class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('observacao', $ordem->observacao) }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Atualizar OS
                    </button>
                    <a href="{{ route('ordens.show', $ordem) }}"
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>