@props(['ordem', 'tecnicoOptions'])

<tr class="group/row os-row hover:bg-white/[0.04] {{ $ordem->row_classes }}">
    <td class="px-4 py-3.5 align-middle text-sm text-center {{ $ordem->row_id_classes }}">
        {{ $ordem->id }}
    </td>

    {{-- Cliente --}}
    <td class="px-4 py-3.5 align-middle">
        <div class="min-w-0 space-y-1 leading-tight">
            @if (filled($ordem->sgp_cliente_link))
                <a href="{{ $ordem->sgp_cliente_link }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex max-w-full items-start gap-1.5 text-[15px] font-semibold tracking-tight text-[#fafafa] transition hover:text-[#ffb07a] hover:underline">
                    <span>{{ $ordem->cliente_nome }}</span>

                    <svg class="mt-0.5 h-3.5 w-3.5 opacity-70"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true">
                        <path d="M11 3a1 1 0 1 0 0 2h2.586l-7.293 7.293a1 1 0 0 0 1.414 1.414L15 6.414V9a1 1 0 1 0 2 0V3h-6z" />
                        <path d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 1 0 0-2H5z" />
                    </svg>
                </a>
            @else
                <span class="block text-[15px] font-semibold tracking-tight text-[#fafafa]">
                    {{ $ordem->cliente_nome }}
                </span>
            @endif

            <span class="block text-[11px] text-[#85858f]">
                {{ $ordem->cliente_telefone }}
            </span>
        </div>
    </td>

    {{-- Serviço --}}
    <td class="px-4 py-3.5 align-middle text-center text-sm font-medium text-[#d7d7dc]">
        {{ $ordem->tipo_servico_label }}
    </td>

    {{-- Bairro --}}
    <td class="px-4 py-3.5 align-middle">
        <div class="flex items-center justify-center gap-2 text-[13px] text-[#c7c7d1]">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 shrink-0 text-[#ff5a00]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>

            <span class="max-w-[10rem] truncate">
                {{ $ordem->bairro }}
            </span>
        </div>
    </td>

    {{-- Técnico --}}
    <td class="px-4 py-3.5 align-middle">
        <form method="POST"
            action="{{ route('ordens.atualizar-tecnico', $ordem) }}"
            class="flex justify-center">
            @csrf
            @method('PATCH')

            <div class="w-full max-w-44 mx-auto">
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

    {{-- Data --}}
    <td class="px-4 py-3.5 align-middle">
        <div class="flex flex-col items-center gap-1 leading-tight text-center">
            <span class="text-sm font-medium text-[#d7d7dc]">
                {{ $ordem->data_marcacao->format('d/m/Y') }}
            </span>

            <x-ordens.turno-badge :ordem="$ordem" />
        </div>
    </td>

    {{-- Prioridade --}}
    <td class="px-4 py-3.5 align-middle">
        <div class="flex justify-center">
            <x-ordens.prioridade-badge :ordem="$ordem" />
        </div>
    </td>

    {{-- Status --}}
    <td class="px-4 py-3.5 align-middle">
        <form method="POST"
            action="{{ route('ordens.atualizar-status', $ordem) }}"
            class="flex justify-center">
            @csrf
            @method('PATCH')

            <div class="w-full max-w-36 mx-auto">
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

    {{-- Ações --}}
    <td class="px-4 py-3.5 align-middle">
        <div class="flex justify-center">
            <x-ordens.ordem-actions :ordem="$ordem" />
        </div>
    </td>
</tr>