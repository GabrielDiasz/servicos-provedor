@props(['ordem'])

<div class="inline-flex items-center justify-end gap-1.5 rounded-2xl border border-[#3a3a40] bg-[#1f1f22]/90 p-1.5 whitespace-nowrap shadow-sm">
    @if ($ordem->canSendWhatsapp())
        <button type="button" title="Enviar serviço para o técnico pelo WhatsApp"
            x-on:click="openWhatsappModal(@js(route('ordens.enviar-whatsapp', $ordem)), @js('OS #' . $ordem->id . ' - ' . $ordem->cliente_nome))"
            aria-label="Enviar serviço para o técnico pelo WhatsApp"
            class="inline-flex h-[2.125rem] w-[2.125rem] items-center justify-center rounded-xl border border-transparent bg-transparent text-[#a1a1aa] transition hover:-translate-y-0.5 hover:bg-emerald-500/10 hover:text-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/25 focus:ring-offset-0">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path
                    d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z" />
            </svg>
        </button>
    @endif

    <a href="{{ route('ordens.show', $ordem) }}"
        aria-label="Visualizar ordem de serviço"
        class="inline-flex h-[2.125rem] w-[2.125rem] items-center justify-center rounded-xl border border-transparent bg-transparent text-[#a1a1aa] transition hover:-translate-y-0.5 hover:bg-[#ff5a00]/10 hover:text-[#ffb07a] focus:outline-none focus:ring-2 focus:ring-[#ff5a00]/25 focus:ring-offset-0">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
                d="M10 3.75c-4.2 0-7.71 2.69-9.02 6.5 1.31 3.81 4.82 6.5 9.02 6.5s7.71-2.69 9.02-6.5c-1.31-3.81-4.82-6.5-9.02-6.5Zm0 10.75a4.25 4.25 0 1 1 0-8.5 4.25 4.25 0 0 1 0 8.5Zm0-2.25a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
        </svg>
    </a>

    <button type="button" title="Excluir ordem de serviço"
        x-on:click="openDeleteModal(@js(route('ordens.destroy', $ordem)), @js('OS #' . $ordem->id . ' - ' . $ordem->cliente_nome))"
        aria-label="Excluir ordem de serviço"
        class="inline-flex h-[2.125rem] w-[2.125rem] items-center justify-center rounded-xl border border-transparent bg-transparent text-[#a1a1aa] transition hover:-translate-y-0.5 hover:bg-red-500/10 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500/25 focus:ring-offset-0">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v5M14 11v5" />
        </svg>
    </button>
</div>
