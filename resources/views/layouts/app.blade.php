<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $routeName = request()->route()?->getName();
            $routeTitles = [
                'dashboard' => 'Dashboard',
                'ordens.index' => 'Ordens de Serviço',
                'ordens.create' => 'Nova OS',
                'ordens.edit' => 'Editar OS',
                'ordens.show' => 'OS #' . optional(request()->route('ordem'))->id . ' - Ordem de Serviço',
                'tecnicos.index' => 'Técnicos',
                'tecnicos.create' => 'Novo Técnico',
                'tecnicos.edit' => 'Editar Técnico',
                'whatsapp-grupos.index' => 'Grupos WhatsApp',
                'whatsapp-grupos.create' => 'Novo Grupo WhatsApp',
                'whatsapp-grupos.edit' => 'Editar Grupo WhatsApp',
                'usuarios.index' => 'Usuários',
                'usuarios.create' => 'Novo Usuário',
                'usuarios.edit' => 'Editar Usuário',
                'profile.edit' => 'Meu Perfil',
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

        <script>
            (() => {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const shouldUseDark = storedTheme ? storedTheme === 'dark' : prefersDark;

                document.documentElement.classList.toggle('dark', shouldUseDark);
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <div
            x-data="{ busy: false, busyLabel: 'Processando...' }"
            @busy-start.window="busy = true; busyLabel = $event.detail?.label || 'Processando...'"
            @busy-stop.window="busy = false"
            class="min-h-screen bg-gradient-to-b from-[#f3f6f2] via-[#f7faf7] to-[#edf3ef] dark:from-[#0b1220] dark:via-[#0b1220] dark:to-[#0b1220]"
        >
            @include('layouts.navigation')

            <div
                x-show="busy"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/35 px-4 backdrop-blur-[2px]"
            >
                <div class="flex items-center gap-3 rounded-2xl border border-white/20 bg-white px-5 py-4 text-sm font-medium text-slate-700 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    <svg class="h-5 w-5 animate-spin text-[#ff7a00]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="busyLabel"></span>
                </div>
            </div>

            @if(session('success') || session('error'))
                <div
                    x-data="{ visible: true }"
                    x-init="setTimeout(() => visible = false, 3000)"
                    x-show="visible"
                    x-cloak
                    x-transition.opacity
                    class="fixed right-4 top-20 z-50 flex w-[min(100vw-2rem,24rem)] justify-end pointer-events-none"
                >
                    @if(session('success'))
                        <div class="pointer-events-auto rounded-2xl border border-green-200 bg-white px-4 py-3 text-sm text-green-900 shadow-2xl ring-1 ring-black/5">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="pointer-events-auto rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm text-red-900 shadow-2xl ring-1 ring-black/5">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white/90 border-b border-[#d7e6d9] shadow-sm backdrop-blur dark:bg-slate-900/90 dark:border-slate-800">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
