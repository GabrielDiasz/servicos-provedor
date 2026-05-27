<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-[#064b31]">Ordens de Serviço</h2>
            <a href="{{ route('ordens.create') }}"
               class="px-4 py-2 bg-[#ff7a00] text-white rounded-lg hover:bg-[#e96f00] text-sm font-medium shadow-sm">
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
              class="bg-white rounded-lg shadow-sm border border-[#d7e6d9] p-4 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3">

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

            <input type="date" name="data_marcacao" value="{{ $dataMarcacao }}"
                   class="border rounded-lg px-3 py-2 text-sm">

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 bg-[#064b31] text-white rounded-lg px-3 py-2 text-sm hover:bg-[#0c5f3a]">
                    Filtrar
                </button>
                <a href="{{ route('ordens.index') }}"
                   class="flex-1 text-center bg-[#fff3e6] text-[#b65300] rounded-lg px-3 py-2 text-sm hover:bg-[#ffe3c2]">
                    Limpar
                </a>
            </div>
        </form>

        {{-- Tabela --}}
        <div class="bg-white rounded-lg shadow-sm border border-[#d7e6d9] overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[#f5fbf4]">
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
                                <form method="POST" action="{{ route('ordens.atualizar-status', $ordem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status"
                                            onchange="this.form.submit()"
                                            class="min-w-32 rounded-full border-[#b9d9c2] bg-[#f5fbf4] px-3 py-1 text-xs font-medium text-[#064b31] focus:border-[#ff7a00] focus:ring-[#ff7a00]">
                                        @foreach(\App\Models\OrdemServico::STATUS as $key => $label)
                                            @continue($key === 'passada' && $ordem->status !== 'passada')
                                            <option value="{{ $key }}" {{ $ordem->status === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('ordens.enviar-whatsapp', $ordem) }}">
                                        @csrf
                                        <button type="submit"
                                                title="Enviar serviço para o técnico pelo WhatsApp"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500 text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/>
                                            </svg>
                                            <span class="sr-only">Enviar pelo WhatsApp</span>
                                        </button>
                                    </form>

                                <a href="{{ route('ordens.show', $ordem) }}"
                                   class="text-[#0c5f3a] hover:text-[#ff7a00] hover:underline text-xs">Ver</a>
                                </div>
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
