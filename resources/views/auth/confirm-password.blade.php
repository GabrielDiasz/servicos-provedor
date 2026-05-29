<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#ff7a00] dark:text-[#ffb366]">
            Confirmação
        </p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            Confirmar senha
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
            Confirme sua senha para continuar nesta área protegida.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="mt-1 block w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end">
            <x-primary-button class="w-full sm:w-auto">
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
