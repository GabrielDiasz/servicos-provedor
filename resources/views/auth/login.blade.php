<x-guest-layout>
    <div class="mb-6">
        <x-auth-session-status class="mb-5" :status="session('status')" />

        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#ff7a00] dark:text-[#ffb366]">
            Acesso restrito
        </p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            Entrar
        </h2>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="mt-1 block w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#ff7a00] shadow-sm focus:ring-[#ff7a00] dark:border-slate-700 dark:bg-slate-900" name="remember">
                <span>Lembrar neste dispositivo</span>
            </label>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm">
                @if (Route::has('password.request'))
                    <a class="font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-[#064b31] dark:text-slate-400 dark:decoration-slate-600 dark:hover:text-[#ffb366]" href="{{ route('password.request') }}">
                        Esqueci minha senha
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full sm:w-auto">
                Entrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
