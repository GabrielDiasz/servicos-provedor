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
            class="min-h-screen bg-gradient-to-b from-[#f3f6f2] via-[#f7faf7] to-[#edf3ef] dark:from-[#2b2b2b] dark:via-[#2f2f2f] dark:to-[#262626]"
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
                @php
                    $toastType = session('success') ? 'success' : 'error';
                    $toastMessage = session('success') ?: session('error');
                    $toastTitle = $toastType === 'success' ? 'Tudo certo' : 'Atenção';
                    $toastClasses = $toastType === 'success'
                        ? 'border-emerald-200/80 bg-[#ffffff] text-emerald-950 shadow-emerald-950/10 dark:border-emerald-400/20 dark:bg-[#17251f] dark:text-emerald-50'
                        : 'border-rose-200/80 bg-[#ffffff] text-rose-950 shadow-rose-950/10 dark:border-rose-400/20 dark:bg-[#291b22] dark:text-rose-50';
                    $toastIconClasses = $toastType === 'success'
                        ? 'bg-emerald-500 text-white'
                        : 'bg-rose-500 text-white';
                    $toastBarClasses = $toastType === 'success'
                        ? 'bg-emerald-500'
                        : 'bg-rose-500';
                @endphp

                <div
                    x-data="{ visible: true }"
                    x-init="setTimeout(() => visible = false, 4200)"
                    x-show="visible"
                    x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
                    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100 sm:translate-x-0"
                    x-transition:leave-end="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
                    class="pointer-events-none fixed right-4 top-20 z-50 flex w-[min(100vw-2rem,26rem)] justify-end"
                >
                    <div class="pointer-events-auto relative overflow-hidden rounded-2xl border px-4 py-3.5 shadow-2xl ring-1 ring-black/5 backdrop-blur {{ $toastClasses }}">
                        <div class="flex gap-3">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-sm {{ $toastIconClasses }}">
                                @if($toastType === 'success')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 4h.01"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.4 2.8 17.5A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.5L13.7 4.4a2 2 0 0 0-3.4 0Z"/>
                                    </svg>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 pr-6">
                                <p class="text-sm font-semibold leading-5">{{ $toastTitle }}</p>
                                <p class="mt-0.5 text-sm leading-5 opacity-80">{{ $toastMessage }}</p>
                            </div>

                            <button
                                type="button"
                                @click="visible = false"
                                class="absolute right-3 top-3 rounded-full p-1 text-current opacity-55 transition hover:bg-black/5 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-current/30 dark:hover:bg-white/10"
                                aria-label="Fechar notificação"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/>
                                </svg>
                            </button>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 h-1 bg-black/5 dark:bg-white/10">
                            <div class="h-full origin-left animate-toast-progress {{ $toastBarClasses }}"></div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-transparent bg-transparent">
                    <div class="mx-auto max-w-7xl px-4 pt-5 pb-2 sm:px-6 lg:px-8">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            {{ $header }}
                        </div>
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
