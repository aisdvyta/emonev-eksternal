<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'Masuk') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-putihbg px-4 font-sans text-slate-800">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-biru1">HaloKIP</h1>
            <p class="text-sm text-abu">Portal Penilai Eksternal</p>
        </div>
        <div class="card">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-hijau/40 bg-hijaumuda px-3 py-2 text-sm text-hijau" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-merah1/40 bg-red-50 px-3 py-2 text-sm text-merah1" role="alert">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </div>
        <p class="mt-4 text-center text-xs text-abu">Akun dibuat oleh admin HaloKIP. Tidak ada pendaftaran mandiri.</p>
    </div>
</body>
</html>
