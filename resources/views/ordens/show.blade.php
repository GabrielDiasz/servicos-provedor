<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">OS #{{ $ordem->id }}</h2>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('ordens.enviar-whatsapp', $ordem) }}">
                    @csrf
                    <button type="submit"
                            title="Enviar serviço para o técnico pelo WhatsApp"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-500 text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/>
                        </svg>
                        <span class="sr-only">Enviar pelo WhatsApp</span>
                    </button>
                </form>
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
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
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

            @if($ordem->sgp_contrato_id || $ordem->sgp_pppoe_login || $ordem->sgp_plano)
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Contrato SGP</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_contrato_id ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">CPF/CNPJ</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_cpf_cnpj ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Nascimento</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_data_nascimento?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Plano</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_plano ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Vencimento</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_vencimento ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">PPPoE</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_pppoe_login ?? '-' }} / {{ $ordem->sgp_pppoe_senha ?? '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 uppercase font-medium">Endereço SGP</p>
                        <p class="text-gray-900 mt-1">{{ $ordem->sgp_endereco ?? '-' }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
