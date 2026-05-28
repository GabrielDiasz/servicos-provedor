<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Técnico</h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="app-surface p-6">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tecnicos.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="nome" value="{{ old('nome') }}"
                           class="app-field w-full"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone WhatsApp *</label>
                    <input type="text" name="telefone" value="{{ old('telefone') }}"
                           placeholder="Ex: 5573999990000"
                           class="app-field w-full"
                           required>
                    <p class="text-xs text-gray-400 mt-1">Formato: código do país + DDD + número (sem espaços ou traços)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grupo de envio *</label>
                    <select name="whatsapp_grupo_id"
                            class="app-select w-full"
                            required>
                        <option value="">Selecione um grupo</option>
                        @foreach($whatsappGrupos as $grupo)
                            <option value="{{ $grupo->id }}" {{ old('whatsapp_grupo_id') == $grupo->id ? 'selected' : '' }}>
                                {{ $grupo->nome }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">As ordens deste técnico serão enviadas para esse grupo.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="app-btn-primary">
                        Salvar
                    </button>
                    <a href="{{ route('tecnicos.index') }}"
                       class="app-btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
