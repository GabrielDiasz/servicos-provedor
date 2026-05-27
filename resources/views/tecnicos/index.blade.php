<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Técnicos</h2>
            <a href="{{ route('tecnicos.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                + Novo Técnico
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grupo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OS Abertas</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tecnicos as $tecnico)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $tecnico->nome }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $tecnico->telefone }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $tecnico->whatsappGrupo->nome ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($tecnico->ativo)
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Ativo</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $tecnico->ordensServico()->whereIn('status', ['pendente', 'passada', 'retornar', 'sem_contato'])->count() }}
                            </td>
                            <td class="px-4 py-3 flex gap-3 justify-end">
                                <a href="{{ route('tecnicos.edit', $tecnico) }}"
                                   class="text-blue-600 hover:underline text-xs">Editar</a>

                                <form method="POST" action="{{ route('tecnicos.destroy', $tecnico) }}"
                                      onsubmit="return confirm('Deseja realmente excluir este técnico?')">
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
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                Nenhum técnico cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $tecnicos->links() }}</div>
    </div>
</x-app-layout>
