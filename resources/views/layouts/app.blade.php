<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'Beranda') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-putihbg font-sans text-slate-800">
    <header class="bg-biru1 text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">HaloKIP <span class="font-normal opacity-80">· Penilai Eksternal</span></a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('profile.show') }}" class="opacity-90 hover:underline">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="underline">Keluar</button>
                    </form>
                </div>
            @endauth
        </div>
        @auth
            @php
                $navItems = [
                    ['Beranda', 'penilai-eksternal.index', 'penilai-eksternal.index'],
                    ['Kuesioner (SAQ)', 'penilai-eksternal.monitor-kues', 'penilai-eksternal.*-kues'],
                    ['Visitasi', 'penilai-eksternal.monitor-visitasi', 'penilai-eksternal.*-visitasi*'],
                    ['Wawancara', 'penilai-eksternal.monitor-wawancara', 'penilai-eksternal.*-wawancara*'],
                    ['Profil', 'profile.show', 'profile.*'],
                ];
            @endphp
            <nav class="bg-biru2" aria-label="Menu utama">
                <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 text-sm">
                    @foreach ($navItems as [$label, $route, $pattern])
                        <a href="{{ route($route) }}"
                           class="whitespace-nowrap px-3 py-2.5 {{ request()->routeIs($pattern) ? 'bg-putihbg font-semibold text-biru1' : 'text-white/90 hover:bg-biru3/40' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </nav>
        @endauth
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-hijau/40 bg-hijaumuda px-4 py-3 text-sm text-hijau" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-merah1/40 bg-red-50 px-4 py-3 text-sm text-merah1" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-merah1/40 bg-red-50 px-4 py-3 text-sm text-merah1" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-8 text-xs text-abu">
        Data pada portal ini adalah salinan terbatas dari sistem HaloKIP BPS Provinsi Jawa Timur dan hanya untuk keperluan penilaian.
    </footer>
</body>
</html>
