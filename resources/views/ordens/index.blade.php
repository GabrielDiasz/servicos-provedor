<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Ordens de Serviço</h2>
            <a href="{{ route('ordens.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                + Nova OS
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Alertas --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Filtros --}}
        <form method="GET" action="{{ route('ordens.index') }}"
              class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3">

            <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos os status</option>
                @foreach(\App\Models\OrdemServico::STATUS as $key => $label)
                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select name="tecnico_id" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos os técnicos</option>
                @foreach($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id }}" {{ request('tecnico_id') == $tecnico->id ? 'selected' : '' }}>
                        {{ $tecnico->nome }}
                    </option>
                @endforeach
            </select>

            <select name="tipo_servico" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos os tipos</option>
                @foreach(\App\Models\OrdemServico::TIPOS as $key => $label)
                    <option value="{{ $key }}" {{ request('tipo_servico') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="data_marcacao" value="{{ request('data_marcacao') }}"
                   class="border rounded-lg px-3 py-2 text-sm">

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 text-white rounded-lg px-3 py-2 text-sm hover:bg-blue-700">
                    Filtrar
                </button>
                <a href="{{ route('ordens.index') }}"
                   class="flex-1 text-center bg-gray-200 text-gray-700 rounded-lg px-3 py-2 text-sm hover:bg-gray-300">
                    Limpar
                </a>
            </div>
        </form>

        {{-- Tabela --}}
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serviço</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bairro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Técnico</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($ordens as $ordem)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $ordem->id }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $ordem->cliente_nome }}<br>
                                <span class="text-xs text-gray-500">{{ $ordem->cliente_telefone }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ \App\Models\OrdemServico::TIPOS[$ordem->tipo_servico] ?? $ordem->tipo_servico }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $ordem->bairro }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $ordem->tecnico->nome ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $ordem->data_marcacao->format('d/m/Y') }}<br>
                                <span class="text-xs text-gray-500">
                                    {{ \App\Models\OrdemServico::TURNOS[$ordem->turno] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $cores = ['normal' => 'bg-gray-100 text-gray-700', 'alta' => 'bg-yellow-100 text-yellow-700', 'urgente' => 'bg-red-100 text-red-700'];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $cores[$ordem->prioridade] }}">
                                    {{ \App\Models\OrdemServico::PRIORIDADES[$ordem->prioridade] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusCores = [
                                        'passada'     => 'bg-blue-100 text-blue-700',
                                        'concluida'   => 'bg-green-100 text-green-700',
                                        'cancelada'   => 'bg-red-100 text-red-700',
                                        'retornar'    => 'bg-yellow-100 text-yellow-700',
                                        'sem_contato' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusCores[$ordem->status] }}">
                                    {{ \App\Models\OrdemServico::STATUS[$ordem->status] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('ordens.show', $ordem) }}"
                                   class="text-blue-600 hover:underline text-xs">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                Nenhuma ordem de serviço encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $ordens->links() }}</div>
    </div>
</x-app-layout>