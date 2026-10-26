@props([
    'title' => 'HaloKIP',
    'sidebar' => null,
    'userName' => null,
    'userRole' => null,
])
<!doctype html>
<html lang="id" class="h-full overflow-hidden">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Non-blocking font load: browser download di background, tidak blokir render --}}
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
    </noscript>
    {{-- Leaflet CSS: hanya di-push oleh halaman yang membutuhkan peta --}}
    {{-- FOUC Guard: sembunyikan halaman sampai Vite selesai inject CSS --}}
    <style>
        html {
            visibility: hidden;
            opacity: 0
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
    @vite('resources/js/app.js')
    @livewireStyles
    @stack('styles')
</head>

<body class="antialiased bg-putihbg text-biru1 font-sans h-full overflow-hidden">
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden" data-navigate-once>
        @csrf
    </form>
    {{-- Global SPA Progress Bar Loader --}}
    <div id="global-loading-bar"
        class="fixed top-0 left-0 h-1 bg-linear-to-r from-blue-500 via-indigo-500 to-emerald-500 transition-all duration-300 ease-out z-9999"
        style="width: 0%; opacity: 0; pointer-events: none;"></div>

    <div class="h-full flex flex-col overflow-hidden" x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        }
    }" x-init="
    $watch('sidebarCollapsed', () => {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 310);
    });
    ">

        {{-- Top Navbar / Header across full width --}}
        <header class="h-16 bg-white border-b border-abubiru flex items-center justify-between px-0 gap-0 shrink-0 relative z-50 select-none">
            {{-- MS Word Underwater Theme Ribbon Watermark --}}
            <div class="absolute right-0 top-0 h-full w-175 sm:w-237.5 pointer-events-none select-none opacity-25 overflow-hidden flex items-center justify-end z-0">
                <img src="{{ asset('images/underwater-doodle.svg') }}" alt="Underwater Ribbon Theme" class="h-16 sm:h-15.5 w-auto object-contain object-right">
            </div>

            {{-- Left: Logo and Hamburger Toggle Button (Aligned with Sidebar on Desktop) --}}
            <div class="h-full flex items-center justify-between px-4 sm:px-4 lg:border-r lg:border-abubiru shrink-0 relative z-10 transition-all duration-300 ease-in-out gap-3"
                :class="{
                    'lg:w-16 lg:justify-center lg:px-0': sidebarCollapsed,
                    'lg:w-60': !sidebarCollapsed
                }">
                <a href="/" class="flex items-center gap-3 shrink-0 hover:opacity-90 transition-opacity"
                    :class="{ 'lg:hidden': sidebarCollapsed }">
                    <img src="{{ asset('images/halokip-logopanjang.svg') }}" alt="HaloKIP" class="h-9 sm:h-10 w-auto">
                </a>

                <button type="button"
                    @click="toggleSidebar()"
                    class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-biru4 hover:text-biru1 hover:bg-putihbg transition shrink-0 cursor-pointer"
                    aria-label="Toggle Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            {{-- Right: User Profile & Dropdown --}}
            <div class="flex-1 flex items-center justify-end px-4 lg:px-6 relative z-50">
                <div class="relative flex items-center" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open"
                        class="flex items-center gap-2 sm:gap-3 pl-3 sm:pl-5 ml-1 focus:outline-none cursor-pointer">
                        <div class="text-right leading-tight hidden sm:block">
                            <div class="text-sm font-semibold text-biru1">
                                {{ $userName ?? (Auth::user()?->nama_user ?? (Auth::user()?->name ?? 'User')) }}</div>
                            <div class="text-xs text-biru3">
                                @php
                                    $user = Auth::user();
                                    $roleName =
                                        $user && $user->role
                                            ? ucwords(str_replace('_', ' ', $user->role->nama_role))
                                            : 'Role';

                                    // Operator gets satker code appended
                                    if (
                                        $user &&
                                        $user->role &&
                                        $user->role->nama_role === 'operator' &&
                                        $user->kode_satker
                                    ) {
                                        $roleName .= ' ' . $user->kode_satker;
                                    }

                                    // Determine Satker name
                                    $satkerName = '';
                                    if ($user) {
                                        if ($user->masterSatker) {
                                            $satkerName = $user->masterSatker->nama_satker;
                                        } elseif (
                                            in_array($user->role?->nama_role, ['admin', 'evaluator', 'kabag', 'super_evaluator'])
                                        ) {
                                            $satkerName = 'BPS Provinsi Jawa Timur';
                                        }
                                    }
                                @endphp
                                {{ $roleName }}@if ($satkerName)
                                    · {{ $satkerName }}
                                @endif
                            </div>
                        </div>
                        <div
                            class="w-9 h-9 rounded-full overflow-hidden bg-abubiru flex items-center justify-center shrink-0">
                            @if (Auth::user()?->foto_url || Auth::user()?->profile_pic)
                                <img src="{{ Auth::user()->foto_url ?? Auth::user()->profile_pic }}" alt="User Avatar"
                                    class="w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($userName ?? (Auth::user()?->nama_user ?? (Auth::user()?->name ?? 'User'))) }}&background=f5c6a5&color=7b3f1d';">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($userName ?? (Auth::user()?->nama_user ?? (Auth::user()?->name ?? 'User'))) }}&background=f5c6a5&color=7b3f1d"
                                    alt="User Avatar" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-biru4 transition-transform duration-200 hidden sm:block"
                            :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 top-full mt-2 w-52 bg-white border border-abubiru rounded-xl shadow-xl py-1.5 z-100"
                        style="display: none;">
                        <a href="{{ route('profile.show') }}" wire:navigate.hover
                            class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-biru1 hover:bg-putihbg hover:text-biru2 transition font-medium cursor-pointer">
                            <svg class="w-4 h-4 text-biru3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profil Saya
                        </a>
                        <div class="my-1 border-t border-abubiru"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-merah1 hover:bg-merah1/10 transition font-medium cursor-pointer">
                                <svg class="w-4 h-4 text-merah1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                                Logout / Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Body Layout (Sidebar + Main Content) --}}
        <div class="flex-1 flex min-h-0 overflow-hidden relative">

            <!-- Sidebar: Hanya tampil di desktop (lg ke atas), tidak pernah ada di tampilan hp/mobile -->
            <aside
                :class="{
                    'lg:w-16': sidebarCollapsed,
                    'lg:w-60': !sidebarCollapsed
                }"
                class="hidden lg:flex bg-white border-r border-abubiru flex-col shrink-0 transition-all duration-300 ease-in-out h-full select-none relative inset-auto z-auto">
                
                <nav class="flex-1 pt-3 pb-3 scrollbar-transparent transition-all duration-300"
                    :class="{
                        'overflow-y-auto px-3': !sidebarCollapsed,
                        'overflow-visible px-2': sidebarCollapsed
                    }">
                    @include('layouts.sidebar', ['sidebar' => $sidebar])
                </nav>

                <div class="mt-auto border-t border-abubiru transition-all duration-300 shrink-0"
                    :class="sidebarCollapsed ? 'p-2' : 'p-3'">
                    <form id="logout-form-sidebar" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="flex items-center gap-3 rounded-lg py-2 text-biru3 hover:bg-merah1/10 hover:text-merah1 transition"
                        :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'">
                        <div class="w-5 h-5 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-biru4 group-hover:text-merah1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-biru3 whitespace-nowrap leading-none flex items-center"
                            x-show="!sidebarCollapsed">Keluar</span>
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col min-w-0 bg-putihbg overflow-y-auto custom-scrollbar px-5 pt-8 pb-24 gap-3 sm:px-7 sm:py-6 lg:px-9 lg:py-8">
                <div class="flex-1">
                    {{ $slot }}
                </div>

                <footer class="mt-auto pt-4 pb-2 text-xs text-biru3 font-medium flex items-center justify-between">
                    <span>© {{ date('Y') }} HaloKIP — BPS Prov Jawa Timur. Hak Cipta Dilindungi.</span>
                </footer>
            </main>
        </div>
    </div>

    {{-- Bottom Navbar: hanya mobile, dinamis per role --}}
    @include('layouts.bottom-nav', ['sidebar' => $sidebar])

    {{-- Toast Notification Global --}}
    <x-ui.toast-notification />

    @livewireScripts
    {{-- Leaflet JS: hanya di-push oleh halaman yang membutuhkan peta --}}
    <script data-navigate-once>

        let loadingInterval;
        document.addEventListener('livewire:navigating', () => {
            const bar = document.getElementById('global-loading-bar');
            if (bar) {
                bar.style.opacity = '1';
                bar.style.width = '0%';

                let progress = 0;
                clearInterval(loadingInterval);
                loadingInterval = setInterval(() => {
                    progress += (95 - progress) * 0.1;
                    bar.style.width = `${progress}%`;
                }, 100);
            }
        });

        document.addEventListener('livewire:navigated', function() {
            const bar = document.getElementById('global-loading-bar');
            if (bar) {
                clearInterval(loadingInterval);
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => {
                        bar.style.width = '0%';
                    }, 300);
                }, 150);
            }

            if (typeof window.initEvaluatorMap === 'function') {
                window.initEvaluatorMap();
            }
            if (typeof window.initOperatorMap === 'function') {
                window.initOperatorMap();
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
