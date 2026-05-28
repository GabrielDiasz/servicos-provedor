<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Grupos WhatsApp</h2>
            <a href="{{ route('whatsapp-grupos.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                + Novo Grupo
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID do Grupo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Técnicos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($grupos as $grupo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $grupo->nome }}</td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $grupo->grupo_id }}</td>
                            <td class="px-4 py-3">
                                @if($grupo->ativo)
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Ativo</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $grupo->tecnicos()->count() }}</td>
                            <td class="px-4 py-3 flex gap-3 justify-end">
                                <a href="{{ route('whatsapp-grupos.edit', $grupo) }}"
                                   class="text-blue-600 hover:underline text-xs">Editar</a>

                                <form method="POST" action="{{ route('whatsapp-grupos.destroy', $grupo) }}"
                                      onsubmit="return confirm('Deseja realmente excluir este grupo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                Nenhum grupo cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $grupos->links() }}</div>
    </div>
</x-app-layout>
