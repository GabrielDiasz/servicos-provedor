<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Grupo WhatsApp</h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
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

            <form method="POST" action="{{ route('whatsapp-grupos.update', $whatsappGrupo) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="nome" value="{{ old('nome', $whatsappGrupo->nome) }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID do Grupo *</label>
                    <input type="text" name="grupo_id" value="{{ old('grupo_id', $whatsappGrupo->grupo_id) }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-blue-500 focus:border-blue-500"
                           required>
                    <p class="text-xs text-gray-400 mt-1">Use o ID retornado em http://localhost:3000/groups, terminando em @g.us.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" id="ativo" value="1"
                           {{ old('ativo', $whatsappGrupo->ativo) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    <label for="ativo" class="text-sm font-medium text-gray-700">Grupo ativo</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Atualizar
                    </button>
                    <a href="{{ route('whatsapp-grupos.index') }}"
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
