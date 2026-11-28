<nav x-data="{ open: false }" style="background-color: var(--color-primary); border-bottom: 4px solid var(--color-accent);">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between" style="height: 72px;">
            <div class="flex items-center gap-6 flex-wrap md:flex-nowrap">
                <!-- Brand Circle + App Name -->
                <a href="/" class="flex items-center gap-3 shrink-0">
                    <!-- Circle Brand -->
                    <div class="flex items-center justify-center w-[42px] h-[42px] rounded-full border-2" style="border-color: var(--color-gold-soft);">
                        <span class="font-serif font-bold text-xl" style="color: var(--color-gold-soft);">B</span>
                    </div>
                    <!-- App Name Lines -->
                    <div class="flex flex-col max-w-[11rem] sm:max-w-[18rem]">
                        <span class="font-serif font-bold text-[15px] sm:text-[19px] leading-tight line-clamp-2" style="color: white;">{{ config('app.name', 'Portal Pelatihan Terpadu') }}</span>
                        <span class="hidden sm:block text-xs uppercase tracking-[0.12em] leading-tight mt-0.5" style="color: var(--color-gold-soft); font-size: 11.5px;">BAPELKES DINKES DIY</span>
                    </div>
                </a>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:space-x-4 md:space-x-2 lg:space-x-4 sm:-my-px sm:flex">
                    <x-nav-link :href="route('pelatihan.katalog')" :active="request()->routeIs('pelatihan.katalog', 'pelatihan.detail')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                        Katalog
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Dashboard
                        </x-nav-link>

                    @if (auth()->user()->isStaf())
                        <x-nav-link :href="route('panitia.dashboard')" :active="request()->routeIs('panitia.dashboard')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Rekap Panitia
                        </x-nav-link>

                        <x-nav-link :href="route('panitia.asrama.index')" :active="request()->routeIs('panitia.asrama.*')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Kelola Asrama
                        </x-nav-link>

                        <x-nav-link :href="route('pelatihans.index')" :active="request()->routeIs('pelatihans.index', 'pelatihans.show', 'pelatihans.create', 'pelatihans.edit')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Kelola Pelatihan
                        </x-nav-link>

                        <x-nav-link :href="route('panitia.presensi.index')" :active="request()->routeIs('panitia.presensi.*', 'pelatihan.presensi')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Presensi
                        </x-nav-link>

                        <x-nav-link :href="route('panitia.statistik.index')" :active="request()->routeIs('panitia.statistik.*')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Statistik
                        </x-nav-link>

                        @if (auth()->user()->isAdmin())
                            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                                Kelola Pengguna
                            </x-nav-link>

                            <x-nav-link :href="route('admin.aktivitas.index')" :active="request()->routeIs('admin.aktivitas.*')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                                Aktivitas
                            </x-nav-link>
                        @endif
                    @else
                        <x-nav-link :href="route('pendaftarans.index')" :active="request()->routeIs('pendaftarans.index', 'pendaftarans.edit', 'pelatihan.daftar')" class="text-[13px] md:text-[13px] lg:text-[14px]">
                            Pendaftaran Saya
                        </x-nav-link>
                    @endif
                    @endauth
                </div>
            </div>

            <!-- Notification Bell and Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                @auth
                    <!-- Notification Bell Dropdown -->
                    <div x-data="{ notifOpen: false }" class="relative">
                        <button 
                            @click="notifOpen = !notifOpen"
                            class="relative p-2 rounded-lg transition-colors text-white opacity-80 hover:opacity-100"
                        >
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <h3 class="text-lg font-semibold" style="color: var(--color-text);">Notifikasi</h3>
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                @php
                                    $recentNotif = auth()->user()->notifications()->latest()->limit(5)->get();
                                @endphp

                                @if ($recentNotif->count() > 0)
                                    @foreach ($recentNotif as $notif)
                                        <a 
                                            href="{{ route('notifikasi.buka', $notif->id) }}"
                                            class="block p-3 hover:bg-[var(--color-bg)] border-b transition-colors"
                                            style="border-color: var(--color-line); text-decoration: none;"
                                        >
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold text-sm" style="color: var(--color-text);">
                                                        {{ $notif->data['judul_pelatihan'] ?? 'Notifikasi' }}
                                                    </p>
                                                    <p class="text-xs mt-1" style="color: var(--color-text); opacity: 0.7;">
                                                        {{ Str::limit($notif->data['pesan'], 50) }}
                                                    </p>
                                                    <p class="text-xs mt-1" style="color: var(--color-text); opacity: 0.5;">
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
                                    <div class="p-4 text-center" style="color: var(--color-text);">
                                        <p class="text-sm">Tidak ada notifikasi</p>
                                    </div>
                                @endif
                            </div>

                            <div class="p-3 border-t" style="border-color: var(--color-line);">
                                <a 
                                    href="{{ route('notifikasi.index') }}"
                                    class="block text-center py-2 text-sm font-semibold rounded-lg transition-colors hover:bg-[var(--color-primary-hover)]"
                                    style="color: white; background-color: var(--color-primary); text-decoration: none;"
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
                        <a href="{{ route('register') }}" class="px-4 py-2 rounded-[var(--radius-btn)] text-white font-semibold hover:bg-[var(--color-primary-hover)]" style="background-color: var(--color-primary-hover);">Daftar</a>
                    </div>
                @else
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-[var(--radius-btn)] text-white hover:bg-[var(--color-primary-hover)]" style="background-color: var(--color-primary-hover);">
                            <div>{{ Auth::user()->name }}</div>

                            @if (auth()->user()->isPanitia())
                                <span class="ms-2 inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: var(--color-primary-soft); color: var(--color-primary);">Panitia</span>
                            @elseif (auth()->user()->isAdmin())
                                <span class="ms-2 inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: var(--color-primary-soft); color: var(--color-primary);">Admin</span>
                            @else
                                <span class="ms-2 inline-block px-2 py-1 text-xs font-semibold rounded-full" style="background-color: color-mix(in srgb, var(--color-success) 15%, white); color: var(--color-success);">Peserta</span>
                            @endif

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @endguest
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md transition duration-150 ease-in-out text-white" @keydown.escape="open = false">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden overflow-x-hidden" style="background-color: var(--color-primary-hover);">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('pelatihan.katalog')" :active="request()->routeIs('pelatihan.katalog', 'pelatihan.detail')">
                Katalog
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-responsive-nav-link>

                @if (auth()->user()->isStaf())
                    <x-responsive-nav-link :href="route('panitia.dashboard')" :active="request()->routeIs('panitia.dashboard')">
                        Rekap Panitia
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.asrama.index')" :active="request()->routeIs('panitia.asrama.*')">
                        Kelola Asrama
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('pelatihans.index')" :active="request()->routeIs('pelatihans.index', 'pelatihans.show', 'pelatihans.create', 'pelatihans.edit')">
                        Kelola Pelatihan
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.presensi.index')" :active="request()->routeIs('panitia.presensi.*', 'pelatihan.presensi')">
                        Presensi
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('panitia.statistik.index')" :active="request()->routeIs('panitia.statistik.*')">
                        Statistik
                    </x-responsive-nav-link>

                    @if (auth()->user()->isAdmin())
                        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            Kelola Pengguna
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('admin.aktivitas.index')" :active="request()->routeIs('admin.aktivitas.*')">
                            Aktivitas
                        </x-responsive-nav-link>
                    @endif
                @else
                    <x-responsive-nav-link :href="route('pendaftarans.index')" :active="request()->routeIs('pendaftarans.index', 'pendaftarans.edit', 'pelatihan.daftar')">
                        Pendaftaran Saya
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @guest
        <div class="pt-4 pb-1 space-y-1" style="border-top: 1px solid color-mix(in srgb, white 15%, transparent);">
            <x-responsive-nav-link :href="route('login')">Masuk</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')">Daftar</x-responsive-nav-link>
        </div>
        @else
        <div class="pt-4 pb-1 space-y-1" style="border-top: 1px solid color-mix(in srgb, white 15%, transparent);">
            <div class="px-4">
                <div class="font-medium text-base truncate text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm truncate" style="color: color-mix(in srgb, white 60%, transparent);">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('notifikasi.index')" :active="request()->routeIs('notifikasi.*')">
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

                <x-responsive-nav-link :href="route('profile.edit')">
                    Profil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
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
