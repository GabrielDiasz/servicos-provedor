<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">OS #{{ $ordem->id }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('ordens.edit', $ordem) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                    Editar
                </a>
                <a href="{{ route('ordens.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow divide-y divide-gray-100">

            <div class="p-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Cliente</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $ordem->cliente_nome }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Telefone</p>
                    <p class="text-gray-900 mt-1">{{ $ordem->cliente_telefone }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Bairro</p>
                    <p class="text-gray-900 mt-1">{{ $ordem->bairro }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Tipo de Serviço</p>
                    <p class="text-gray-900 mt-1">{{ \App\Models\OrdemServico::TIPOS[$ordem->tipo_servico] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Data / Turno</p>
                    <p class="text-gray-900 mt-1">
                        {{ $ordem->data_marcacao->format('d/m/Y') }} —
                        {{ \App\Models\OrdemServico::TURNOS[$ordem->turno] }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Técnico</p>
                    <p class="text-gray-900 mt-1">{{ $ordem->tecnico->nome ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Prioridade</p>
                    <p class="text-gray-900 mt-1">{{ \App\Models\OrdemServico::PRIORIDADES[$ordem->prioridade] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Status</p>
                    <p class="text-gray-900 mt-1">{{ \App\Models\OrdemServico::STATUS[$ordem->status] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Atendente</p>
                    <p class="text-gray-900 mt-1">{{ $ordem->atendente->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Criada em</p>
                    <p class="text-gray-900 mt-1">{{ $ordem->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            @if($ordem->observacao)
                <div class="p-6">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">Observação</p>
                    <p class="text-gray-700 text-sm">{{ $ordem->observacao }}</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>