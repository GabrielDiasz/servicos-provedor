<nav
    x-data="{
        open: false,
        darkMode: document.documentElement.classList.contains('dark'),
        init() {
            this.darkMode = document.documentElement.classList.contains('dark');
        },
        toggleDarkMode() {
            this.darkMode = ! this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    x-init="init()"
    class="sticky top-0 z-40 border-b-2 border-[#ff5a00] bg-[#202020] shadow-none transition-colors duration-300"
>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="h-11 w-auto" />
                    </a>
                </div>

                
                <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    <x-nav-link :href="route('ordens.index')" :active="request()->routeIs('ordens.*')">
                        Ordens de Serviço
                    </x-nav-link>
                    <x-nav-link :href="route('upgrade.index')" :active="request()->routeIs('upgrade.*')">
                        Upgrade
                    </x-nav-link>
                    <x-nav-link :href="route('tecnicos.index')" :active="request()->routeIs('tecnicos.*')">
                        Técnicos
                    </x-nav-link>
                    <x-nav-link :href="route('whatsapp-grupos.index')" :active="request()->routeIs('whatsapp-grupos.*')">
                        Grupos WhatsApp
                    </x-nav-link>
                    @can('gerenciar-usuarios')
                        <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                            Usuários
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                <button
                    type="button"
                    @click="toggleDarkMode()"
                    class="inline-flex items-center justify-center rounded-md border border-[#444] bg-[#303030] p-2 text-slate-200 transition hover:border-[#ff5a00] hover:bg-[#383838] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#ff5a00] focus:ring-offset-2 focus:ring-offset-[#202020]"
                    :title="darkMode ? 'Ativar tema claro' : 'Ativar tema escuro'"
                    aria-label="Alternar tema"
                >
                    <svg x-show="!darkMode" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m8-8h2M2 12h2m14.828 6.828 1.414 1.414M5.758 5.758 4.344 4.344m14.142 0-1.414 1.414M5.758 18.242l-1.414 1.414"/>
                        <circle cx="12" cy="12" r="4"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M21.64 13.02A9 9 0 1 1 10.98 2.36a.75.75 0 0 1 .83 1.14 7.5 7.5 0 0 0 8.69 9.15.75.75 0 0 1 1.14.37Z"/>
                    </svg>
                </button>

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center gap-2 rounded-md border border-[#444] bg-[#303030] px-2 py-1  leading-4 text-slate-200 shadow-sm transition duration-150 ease-in-out hover:border-[#ff5a00] hover:bg-[#383838] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#ff5a00] focus:ring-offset-2 focus:ring-offset-[#202020]">
                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#3a3a3a] text-white/90">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 15 0"/>
                                </svg>
                            </span>

                            <span class="max-w-[9rem] truncate text-slate-100 text-sm">{{ Auth::user()->name }}</span>

                            <div class="ms-1 text-slate-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 pt-3 pb-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                                Conta
                            </p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ Auth::user()->email }}
                            </p>
                        </div>

                        <div class="my-1 h-px bg-slate-100 dark:bg-slate-700"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-start text-sm font-medium text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 focus:bg-rose-50 focus:outline-none dark:text-rose-400 dark:hover:bg-rose-500/10 dark:focus:bg-rose-500/10"
                            >
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-8.25A2.25 2.25 0 0 0 3 5.25v13.5A2.25 2.25 0 0 0 5.25 21h8.25A2.25 2.25 0 0 0 15.75 18.75V15"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12h8.25m0 0-3-3m3 3-3 3"/>
                                </svg>
                                <span>Sair</span>
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    type="button"
                    @click="toggleDarkMode()"
                    class="mr-2 inline-flex items-center justify-center rounded-md p-2 text-slate-200 hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out"
                    :title="darkMode ? 'Ativar tema claro' : 'Ativar tema escuro'"
                    aria-label="Alternar tema"
                >
                    <svg x-show="!darkMode" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m8-8h2M2 12h2m14.828 6.828 1.414 1.414M5.758 5.758 4.344 4.344m14.142 0-1.414 1.414M5.758 18.242l-1.414 1.414"/>
                        <circle cx="12" cy="12" r="4"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M21.64 13.02A9 9 0 1 1 10.98 2.36a.75.75 0 0 1 .83 1.14 7.5 7.5 0 0 0 8.69 9.15.75.75 0 0 1 1.14.37Z"/>
                    </svg>
                </button>

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-200 hover:bg-white/10 hover:text-white focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 bg-[#202020] px-2 pt-2 pb-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ordens.index')" :active="request()->routeIs('ordens.*')">
                Ordens de Serviço
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('upgrade.index')" :active="request()->routeIs('upgrade.*')">
                Upgrade
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tecnicos.index')" :active="request()->routeIs('tecnicos.*')">
                Técnicos
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('whatsapp-grupos.index')" :active="request()->routeIs('whatsapp-grupos.*')">
                Grupos WhatsApp
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-[#3a3a3a] bg-[#202020] pt-4 pb-1">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                {{-- <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link> --}}

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Sair
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
