<nav x-data="{ open: false, scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 8)" class="sticky top-0 z-40 h-[72px] transition-colors duration-200" :style="scrolled ? 'background-color: color-mix(in srgb, var(--color-primary) 85%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.22); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);' : 'background-color: transparent; border-bottom: 1px solid rgba(255, 255, 255, 0.22);'">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-full">
            <div class="flex items-center gap-6 flex-wrap xl:flex-nowrap">
                <!-- Brand Logo + Screen Reader Name -->
                <a href="/" class="flex items-center shrink-0">
                    <!-- White Logo Image -->
                    <img src="/images/logo-bapelkes-putih.png" alt="Balai Pelatihan Kesehatan DIY" class="h-9 sm:h-11 w-auto">
                    <!-- App Name for Screen Readers -->
                    <span class="sr-only">{{ config('app.name') }}</span>
                </a>

                <!-- Vertical Divider (2xl ke atas) -->
                <div class="hidden 2xl:block w-px h-7 bg-white/25"></div>

                <!-- Navigation Links (xl ke atas) -->
                <div class="hidden gap-1 xl:flex items-center">
                    <x-nav-link :href="route('pelatihan.katalog')" :active="request()->routeIs('pelatihan.katalog', 'pelatihan.detail')" class="text-sm px-3 py-2">
                        Katalog
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-sm px-3 py-2">
                            Dashboard
                        </x-nav-link>

                    @if (auth()->user()->isStaf())
                        <x-nav-link :href="route('panitia.dashboard')" :active="request()->routeIs('panitia.dashboard')" class="text-sm px-3 py-2">
                            Rekap Panitia
                        </x-nav-link>

                        <x-nav-link :href="route('pelatihans.index')" :active="request()->routeIs('pelatihans.index', 'pelatihans.show', 'pelatihans.create', 'pelatihans.edit')" class="text-sm px-3 py-2">
                            Kelola Pelatihan
                        </x-nav-link>

                        <x-nav-link :href="route('panitia.presensi.index')" :active="request()->routeIs('panitia.presensi.*', 'pelatihan.presensi')" class="text-sm px-3 py-2">
                            Presensi
                        </x-nav-link>

                        <!-- Dropdown "Lainnya" untuk menu tambahan -->
                        @php
                            $isOtherMenuActive = request()->routeIs('panitia.statistik.*', 'panitia.asrama.*', 'admin.users.*', 'admin.aktivitas.*');
                        @endphp
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm rounded-full border font-medium transition-colors text-white {{ $isOtherMenuActive ? 'bg-white/20 border-white/35' : 'border-transparent hover:bg-white/10' }}">
                                    Lainnya
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('panitia.statistik.index')" :active="request()->routeIs('panitia.statistik.*')" class="text-[var(--color-text)]">
                                    Statistik
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('panitia.asrama.index')" :active="request()->routeIs('panitia.asrama.*')" class="text-[var(--color-text)]">
                                    Kelola Asrama
                                </x-dropdown-link>

                                @if (auth()->user()->isAdmin())
                                    <x-dropdown-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-[var(--color-text)]">
                                        Kelola Pengguna
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('admin.aktivitas.index')" :active="request()->routeIs('admin.aktivitas.*')" class="text-[var(--color-text)]">
                                        Aktivitas
                                    </x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @else
                        <x-nav-link :href="route('pendaftarans.index')" :active="request()->routeIs('pendaftarans.index', 'pendaftarans.edit', 'pelatihan.daftar')" class="text-sm px-3 py-2">
                            Pendaftaran Saya
                        </x-nav-link>
                    @endif
                    @endauth
                </div>
            </div>

            <!-- Notification Bell and Settings Dropdown -->
            <div class="hidden xl:flex xl:items-center xl:ms-6 gap-4 shrink-0">
                @auth
                    <!-- Notification Bell Dropdown -->
                    <div x-data="{ notifOpen: false }" class="relative">
                        <button 
                            @click="notifOpen = !notifOpen"
                            class="relative p-2 rounded-lg transition-colors text-white opacity-80 hover:opacity-100"
                        >
                            <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            
                            @php
                                $unreadCount = auth()->user()->unreadNotifications->count();
                            @endphp
                            
                            @if ($unreadCount > 0)
                                <span 
                                    class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1 -translate-y-1 rounded-full"
                                    style="background-color: var(--color-danger); min-width: 1.25rem; height: 1.25rem; display: flex; align-items: center; justify-content: center;"
                                >
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Dropdown Menu -->
                        <div 
                            x-show="notifOpen"
                            @click.outside="notifOpen = false"
                            class="absolute right-0 mt-2 w-80 rounded-lg shadow-lg z-50"
                            style="background-color: var(--color-surface); border: 1px solid var(--color-line); display: none;"
                            x-transition
                        >
                            <div class="p-4 border-b" style="border-color: var(--color-line);">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Notifikasi</h3>
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                @php
                                    $recentNotif = auth()->user()->notifications()->latest()->limit(5)->get();
                                @endphp

                                @if ($recentNotif->count() > 0)
                                    @foreach ($recentNotif as $notif)
                                        <a 
                                            href="{{ route('notifikasi.buka', $notif->id) }}"
                                            class="block p-3 hover:bg-[var(--color-bg)] border-b transition-colors text-[var(--color-text)]"
                                            style="border-color: var(--color-line); text-decoration: none;"
                                        >
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold text-sm">
                                                        {{ $notif->data['judul_pelatihan'] ?? 'Notifikasi' }}
                                                    </p>
                                                    <p class="text-xs mt-1 opacity-70">
                                                        {{ Str::limit($notif->data['pesan'], 50) }}
                                                    </p>
                                                    <p class="text-xs mt-1 opacity-50">
                                                        {{ $notif->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                                @if (!$notif->read_at)
                                                    <span 
                                                        class="inline-block w-2 h-2 rounded-full mt-1"
                                                        style="background-color: var(--color-accent);"
                                                    ></span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                @else
                                    <div class="p-4 text-center text-[var(--color-text)]">
                                        <p class="text-sm">Tidak ada notifikasi</p>
                                    </div>
                                @endif
                            </div>

                            <div class="p-3 border-t" style="border-color: var(--color-line);">
                                <a 
                                    href="{{ route('notifikasi.index') }}"
                                    class="block text-center py-2 text-sm font-semibold rounded-lg transition-colors hover:bg-[var(--color-primary-hover)] text-white"
                                    style="background-color: var(--color-primary); text-decoration: none;"
                                >
                                    Lihat Semua
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth

                @guest
                    <div class="space-x-2 text-sm font-medium">
                        <a href="{{ route('login') }}" class="px-3 py-2 rounded-md text-white hover:opacity-100 opacity-80">Masuk</a>
                        <a href="{{ route('register') }}" class="px-4 py-2 rounded-full text-white font-semibold border border-white/35 bg-white/20 hover:bg-white/30 transition-colors inline-block">Daftar</a>
                    </div>
                @else
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm font-medium rounded-full border border-white/35 bg-white/15 hover:bg-white/25 transition-colors text-white">
                            <div class="truncate max-w-[9rem]">{{ Auth::user()->name }}</div>

                            @if (auth()->user()->isPanitia())
                                <span class="ms-2 hidden 2xl:inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: color-mix(in srgb, var(--color-primary) 15%, white); color: var(--color-primary);">Panitia</span>
                            @elseif (auth()->user()->isAdmin())
                                <span class="ms-2 hidden 2xl:inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: color-mix(in srgb, var(--color-primary) 15%, white); color: var(--color-primary);">Admin</span>
                            @else
                                <span class="ms-2 hidden 2xl:inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: color-mix(in srgb, var(--color-success) 15%, white); color: var(--color-success);">Peserta</span>
                            @endif

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-[var(--color-muted)]">
                            @if (auth()->user()->isPanitia())
                                Panitia
                            @elseif (auth()->user()->isAdmin())
                                Admin
                            @else
                                Peserta
                            @endif
                        </div>

                        <x-dropdown-link :href="route('profile.edit')" class="text-[var(--color-text)]">
                            Profil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" class="text-[var(--color-text)]"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @endguest
            </div>

            <!-- Hamburger (bawah xl) -->
            <div class="flex items-center xl:hidden -me-2">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md transition duration-150 ease-in-out text-white" @keydown.escape="open = false">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (mobile + tablet) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden overflow-x-hidden" style="background-color: var(--color-primary-hover);">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('pelatihan.katalog')" :active="request()->routeIs('pelatihan.katalog', 'pelatihan.detail')" class="text-[var(--color-text)]">
                Katalog
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-[var(--color-text)]">
                    Dashboard
                </x-responsive-nav-link>

                @if (auth()->user()->isStaf())
                    <x-responsive-nav-link :href="route('panitia.dashboard')" :active="request()->routeIs('panitia.dashboard')" class="text-[var(--color-text)]">
                        Rekap Panitia
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.asrama.index')" :active="request()->routeIs('panitia.asrama.*')" class="text-[var(--color-text)]">
                        Kelola Asrama
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('pelatihans.index')" :active="request()->routeIs('pelatihans.index', 'pelatihans.show', 'pelatihans.create', 'pelatihans.edit')" class="text-[var(--color-text)]">
                        Kelola Pelatihan
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.presensi.index')" :active="request()->routeIs('panitia.presensi.*', 'pelatihan.presensi')" class="text-[var(--color-text)]">
                        Presensi
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.statistik.index')" :active="request()->routeIs('panitia.statistik.*')" class="text-[var(--color-text)]">
                        Statistik
                    </x-responsive-nav-link>

                    @if (auth()->user()->isAdmin())
                        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-[var(--color-text)]">
                            Kelola Pengguna
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('admin.aktivitas.index')" :active="request()->routeIs('admin.aktivitas.*')" class="text-[var(--color-text)]">
                            Aktivitas
                        </x-responsive-nav-link>
                    @endif
                @else
                    <x-responsive-nav-link :href="route('pendaftarans.index')" :active="request()->routeIs('pendaftarans.index', 'pendaftarans.edit', 'pelatihan.daftar')" class="text-[var(--color-text)]">
                        Pendaftaran Saya
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @guest
        <div class="pt-4 pb-1 space-y-1" style="border-top: 1px solid color-mix(in srgb, white 15%, transparent);">
            <x-responsive-nav-link :href="route('login')" class="text-[var(--color-text)]">Masuk</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')" class="text-[var(--color-text)]">Daftar</x-responsive-nav-link>
        </div>
        @else
        <div class="pt-4 pb-1 space-y-1" style="border-top: 1px solid color-mix(in srgb, white 15%, transparent);">
            <div class="px-4">
                <div class="font-medium text-base truncate text-[var(--color-text)]">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm truncate" style="color: color-mix(in srgb, white 60%, transparent);">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('notifikasi.index')" :active="request()->routeIs('notifikasi.*')" class="text-[var(--color-text)]">
                    Notifikasi
                    @php
                        $unreadCountMobile = auth()->user()->unreadNotifications->count();
                    @endphp
                    @if ($unreadCountMobile > 0)
                        <span 
                            class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white rounded-full ml-2"
                            style="background-color: var(--color-danger); min-width: 1.25rem; height: 1.25rem;"
                        >
                            {{ $unreadCountMobile }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('profile.edit')" class="text-[var(--color-text)]">
                    Profil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" class="text-[var(--color-text)]"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endguest
    </div>
</nav>
