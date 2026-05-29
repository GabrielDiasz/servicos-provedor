<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#ff7a00] dark:text-[#ffb366]">
            Recuperar acesso
        </p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            Recuperar senha
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
            Informe seu e-mail para receber o link de redefinição.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button class="w-full sm:w-auto">
                Enviar link de redefinição
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
