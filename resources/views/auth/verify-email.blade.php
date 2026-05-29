<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#ff7a00] dark:text-[#ffb366]">
            Verificação
        </p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            Confirmar e-mail
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
            Verifique seu endereço de e-mail para continuar.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
            Um novo link de verificação foi enviado para o seu e-mail.
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="w-full sm:w-auto">
                    Reenviar e-mail de verificação
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-[#ff7a00] hover:text-[#ff7a00] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-[#ffb366] sm:w-auto">
                Sair
            </button>
        </form>
    </div>
</x-guest-layout>
