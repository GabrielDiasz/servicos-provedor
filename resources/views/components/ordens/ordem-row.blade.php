@props(['ordem', 'tecnicoOptions'])

<tr class="group/row os-row hover:bg-white/[0.04] {{ $ordem->row_classes }}">
    <td class="px-4 py-3.5 align-middle text-sm {{ $ordem->row_id_classes }}">{{ $ordem->id }}</td>
    <td class="px-4 py-3.5 align-middle">
        <div class="min-w-0 space-y-1 leading-tight">
            @if (filled($ordem->sgp_cliente_link))
                <a href="{{ $ordem->sgp_cliente_link }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex max-w-full items-start gap-1.5 text-[15px] font-semibold tracking-tight text-[#fafafa] transition hover:text-[#ffb07a] hover:underline">
                    <span>{{ $ordem->cliente_nome }}</span>
                    <svg class="mt-0.5 h-3.5 w-3.5 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path
                            d="M11 3a1 1 0 1 0 0 2h2.586l-7.293 7.293a1 1 0 0 0 1.414 1.414L15 6.414V9a1 1 0 1 0 2 0V3h-6z" />
                        <path
                            d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 1 0 0-2H5z" />
                    </svg>
                </a>
            @else
                <span class="block text-[15px] font-semibold tracking-tight text-[#fafafa]">{{ $ordem->cliente_nome }}</span>
            @endif
            <span class="block text-[11px] text-[#85858f]">
                {{ $ordem->cliente_telefone }}
            </span>
        </div>
    </td>
    <td class="px-4 py-3.5 align-middle text-sm font-medium text-[#d7d7dc]">
        {{ $ordem->tipo_servico_label }}
    </td>
    <td class="px-4 py-3.5 align-middle text-[12px] text-[#8b8b95]">
        <span class="block max-w-[11rem] truncate">{{ $ordem->bairro }}</span>
    </td>
    <td class="px-4 py-3.5 align-middle">
        <form method="POST" action="{{ route('ordens.atualizar-tecnico', $ordem) }}" class="flex items-center">
            @csrf
            @method('PATCH')
            <div class="w-full max-w-44">
                <x-sgp-select
                    name="tecnico_id"
                    :options="$tecnicoOptions"
                    :selected="$ordem->tecnico_id"
                    placeholder="Sem técnico"
                    size="sm"
                    submit-on-change="true"
                    busy-label="Atualizando técnico..."
                    class="w-full"
                />
            </div>
        </form>
    </td>
    <td class="px-4 py-3.5 align-middle">
        <div class="flex flex-col items-start gap-1 leading-tight">
            <span class="text-sm font-medium text-[#d7d7dc]">{{ $ordem->data_marcacao->format('d/m/Y') }}</span>
            <x-ordens.turno-badge :ordem="$ordem" />
        </div>
    </td>
    <td class="px-4 py-3.5 align-middle">
        <x-ordens.prioridade-badge :ordem="$ordem" />
    </td>
    <td class="px-4 py-3.5 align-middle">
        <form method="POST" action="{{ route('ordens.atualizar-status', $ordem) }}" class="flex items-center">
            @csrf
            @method('PATCH')
            <div class="w-full max-w-36">
                <x-sgp-select
                    name="status"
                    :options="$ordem->editable_status_options"
                    :selected="$ordem->status"
                    placeholder="Status"
                    size="sm"
                    submit-on-change="true"
                    busy-label="Atualizando status..."
                    class="w-full"
                />
            </div>
        </form>
    </td>
    <td class="px-4 py-3.5 align-middle">
        <x-ordens.ordem-actions :ordem="$ordem" />
    </td>
</tr>
