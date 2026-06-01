<div x-show="whatsappModalOpen" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 px-4">
    <div x-show="whatsappModalOpen" x-transition x-on:click.outside="closeWhatsappModal()"
        class="w-full max-w-md rounded-xl border border-slate-700 bg-[#2f2f2f] p-6 shadow-2xl">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path
                        d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L.06 24l6.28-1.65a11.88 11.88 0 0 0 5.73 1.46h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.42ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.73.98 1-3.64-.24-.37a9.88 9.88 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 0 1 7 2.9 9.83 9.83 0 0 1 2.9 7c0 5.46-4.44 9.89-9.91 9.89Zm5.43-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-white">Confirmar envio</h3>
                <p class="mt-1 text-sm text-slate-300">
                    Você está prestes a enviar <span class="font-semibold text-white" x-text="whatsappLabel"></span>.
                </p>
            </div>
        </div>

        <button type="button" role="checkbox" x-bind:aria-checked="whatsappAbrirSgp.toString()"
            x-on:click="whatsappAbrirSgp = !whatsappAbrirSgp"
            class="mt-5 flex w-full items-start gap-3 rounded-lg border border-slate-600 bg-[#353535] px-4 py-3 text-left transition focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 focus:ring-offset-[#2f2f2f]"
            x-bind:class="whatsappAbrirSgp ? 'border-[#ff7a00]/70 bg-[#3a3128]' : 'hover:border-slate-500'">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-slate-400 bg-[#1f1f1f] shadow-inner transition duration-150 ease-out"
                x-bind:class="whatsappAbrirSgp
                    ? 'border-[#ff7a00] bg-[#ff7a00] shadow-[0_0_0_4px_rgba(255,122,0,0.18)]'
                    : 'hover:border-slate-300'">
                <svg class="h-4 w-4 text-white transition-opacity" x-bind:class="whatsappAbrirSgp ? 'opacity-100' : 'opacity-0'" viewBox="0 0 20 20"
                    fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.3a1 1 0 0 1-1.42.006L3.29 9.29a1 1 0 1 1 1.42-1.41l3.03 3.05 6.54-6.58a1 1 0 0 1 1.414-.06Z"
                        clip-rule="evenodd" />
                </svg>
            </span>
            <span class="mt-0.5 text-sm text-slate-100">
                Abrir ocorrência no SGP
                <span class="mt-1 block text-xs text-slate-400">
                    Se desmarcado, envia apenas a mensagem no grupo do WhatsApp.
                </span>
            </span>
        </button>

        <form method="POST" x-bind:action="whatsappAction" class="mt-6 flex w-full items-center justify-between gap-3"
            x-on:submit="$dispatch('busy-start', { label: 'Enviando WhatsApp...' })">
            @csrf
            <input type="hidden" name="abrir_ocorrencia_sgp" :value="whatsappAbrirSgp ? '1' : '0'">
            <button type="submit" x-bind:disabled="!whatsappReady"
                x-bind:class="whatsappReady ? 'bg-[#ff7a00] text-white hover:bg-[#e96c00]' : 'cursor-not-allowed bg-[#ff7a00]/50 text-white/80'"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 focus:ring-offset-[#2f2f2f]">
                <span x-show="whatsappReady">Sim</span>
                <span x-show="!whatsappReady" x-cloak x-text="`Aguarde ${whatsappCountdown}s`"></span>
            </button>
            <button type="button" x-on:click="closeWhatsappModal()"
                class="rounded-lg border border-slate-600 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/5">
                Cancelar
            </button>
        </form>
    </div>
</div>
