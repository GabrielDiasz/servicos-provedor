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
    class="sticky top-0 z-40 border-b border-white/10 bg-[#064b31]/95 backdrop-blur-md shadow-lg transition-colors duration-300 dark:bg-[#031b13]/95"
>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
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
                    class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/10 p-2 text-green-50 transition hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 focus:ring-offset-[#064b31]"
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

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-sm font-medium leading-4 text-green-50 transition ease-in-out duration-150 hover:bg-white/20 hover:text-white focus:outline-none">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link> --}}

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    type="button"
                    @click="toggleDarkMode()"
                    class="mr-2 inline-flex items-center justify-center rounded-md p-2 text-green-50 hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out"
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

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-green-50 hover:bg-white/10 hover:text-white focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
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
        <div class="space-y-1 bg-[#064b31] px-2 pt-2 pb-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ordens.index')" :active="request()->routeIs('ordens.*')">
                Ordens de Serviço
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tecnicos.index')" :active="request()->routeIs('tecnicos.*')">
                Técnicos
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('whatsapp-grupos.index')" :active="request()->routeIs('whatsapp-grupos.*')">
                Grupos WhatsApp
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-white/10 bg-[#064b31] pt-4 pb-1">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-green-50/70">{{ Auth::user()->email }}</div>
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
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
