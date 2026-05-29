<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $routeName = request()->route()?->getName();
            $routeTitles = [
                'login' => 'Entrar',
                'password.request' => 'Recuperar Senha',
                'password.reset' => 'Nova Senha',
                'password.confirm' => 'Confirmar Senha',
                'verification.notice' => 'Verificar E-mail',
                'verification.verify' => 'Verificar E-mail',
            ];

            $pageTitle = $routeTitles[$routeName] ?? config('app.name', 'Laravel');
        @endphp

        <title>{{ $pageTitle }} | GPR Fibra</title>
        <link rel="icon" type="image/png" href="{{ asset('images/gpr-fibra-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/gpr-fibra-logo.png') }}">
        <meta name="theme-color" content="#064b31">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900 dark:text-slate-100">
        <div class="min-h-screen bg-[linear-gradient(135deg,#f7faf7_0%,#eef4ef_48%,#e6efe8_100%)] text-slate-900 dark:bg-[linear-gradient(135deg,#050a12_0%,#0b1220_55%,#101826_100%)] dark:text-slate-100">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(6,75,49,0.05)_1px,transparent_1px),linear-gradient(rgba(6,75,49,0.05)_1px,transparent_1px)] bg-[size:28px_28px] opacity-40 dark:bg-[linear-gradient(90deg,rgba(255,122,0,0.06)_1px,transparent_1px),linear-gradient(rgba(255,122,0,0.06)_1px,transparent_1px)]"></div>

        <div class="relative min-h-screen lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <aside class="hidden lg:flex lg:flex-col lg:justify-between bg-[#064b31] px-12 py-10 text-white">
                <div class="max-w-xl">
                    <a href="/" class="inline-flex items-center gap-3">
                        <img src="{{ asset('images/gpr-fibra-logo.png') }}" alt="GPR Fibra" class="h-14 w-auto">
                    </a>

                    <div class="mt-12">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/70">Acesso interno</p>
                        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">
                            GPR Fibra
                        </h1>
                    </div>
                </div>

                <div class="max-w-lg text-sm leading-6 text-white/65">
                    Ambiente protegido para acesso da equipe.
                </div>
            </aside>

            <main class="relative flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-10">
                <div class="absolute inset-x-0 top-0 h-1 bg-[#ff7a00]"></div>
                <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(255,255,255,0.30)_0%,rgba(255,255,255,0)_28%)] dark:bg-none"></div>

                <div class="w-full max-w-md">
                    <div class="mb-6 text-center lg:hidden">
                        <div class="mx-auto inline-flex items-center justify-center rounded-3xl bg-white px-5 py-4 shadow-[0_18px_50px_rgba(6,75,49,0.12)] ring-1 ring-slate-200/70">
                            <img src="{{ asset('images/gpr-fibra-logo.png') }}" alt="GPR Fibra" class="h-24 w-auto">
                        </div>
                        <p class="mt-3 text-sm font-semibold uppercase tracking-[0.25em] text-[#ff7a00]">Acesso interno</p>
                    </div>

                    <div class="rounded-3xl border border-white/70 bg-white/95 p-6 shadow-[0_24px_80px_rgba(6,75,49,0.12)] backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 dark:shadow-[0_24px_80px_rgba(0,0,0,0.28)] sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
