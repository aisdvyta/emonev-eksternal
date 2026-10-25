@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-biru1">Beranda Penilai Eksternal</h1>
        @if ($period)
            <p class="text-sm text-abu">
                Periode {{ $period->periode_start?->translatedFormat('Y') ?? $period->id_periode }}
                · Tahapan saat ini: <span class="font-semibold text-biru2">{{ $period->phaseLabel() }}</span>
            </p>
        @else
            <p class="text-sm text-abu">Belum ada data periode. Data akan muncul setelah disinkronkan dari sistem pusat.</p>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="card"><p class="text-xs uppercase text-abu">Ditugaskan</p><p class="text-3xl font-bold text-biru1">{{ $assigned }}</p><p class="text-xs text-abu">visitasi + wawancara</p></div>
        <div class="card"><p class="text-xs uppercase text-abu">Sudah dikirim</p><p class="text-3xl font-bold text-hijau">{{ $done }}</p></div>
        <div class="card"><p class="text-xs uppercase text-abu">Belum selesai</p><p class="text-3xl font-bold text-merah1">{{ $pending }}</p></div>
        <div class="card"><p class="text-xs uppercase text-abu">Kuesioner (lihat saja)</p><p class="text-3xl font-bold text-biru2">{{ $saqCount }}</p></div>
    </div>

    @if ($waitingSync > 0)
        <div class="mt-4 rounded-lg border border-orange-300 bg-orange-50 px-4 py-3 text-sm text-orange-900">
            {{ $waitingSync }} penilaian Anda sudah tersimpan dan sedang menunggu diteruskan ke sistem pusat. Tidak perlu mengirim ulang.
        </div>
    @endif

    <section class="card mt-6">
        <h2 class="mb-3 font-semibold text-biru1">Agenda terdekat</h2>
        @forelse ($upcoming as $item)
            <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 border-b border-biru5/60 py-3 last:border-0 hover:bg-birumuda/50">
                <div>
                    <p class="font-medium">{{ $item['type'] }} · {{ $item['satker'] }}</p>
                    <p class="text-xs text-abu">{{ $item['date']->translatedFormat('l, d F Y') }}</p>
                </div>
                <span class="text-sm text-biru2">Buka &rarr;</span>
            </a>
        @empty
            <p class="text-sm text-abu">Tidak ada agenda yang akan datang.</p>
        @endforelse
    </section>
@endsection
