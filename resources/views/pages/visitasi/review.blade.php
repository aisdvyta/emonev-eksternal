@extends('layouts.app')

@section('title', 'Visitasi '.($visitasi->result?->satker?->nama_satker ?? ''))

@section('content')
    @php
        $linkMeet = \App\Helpers\Links::safe($visitasi->link_meet_visitasi);
        $linkBukti = \App\Helpers\Links::safe($visitasi->link_buktidukung);
        $disabled = (bool) $lock;
    @endphp

    <a href="{{ route('penilai-eksternal.monitor-visitasi') }}" class="text-sm text-biru2 underline">&larr; Kembali</a>
    <h1 class="mt-2 text-xl font-bold text-biru1">Visitasi · {{ $visitasi->result?->satker?->nama_satker ?? 'Satker' }}</h1>
    <p class="mb-4 text-sm text-abu"><x-status :row="$mine" :final="$visitasi->is_final" /></p>

    <section class="card mb-4">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs uppercase text-abu">Tanggal kunjungan</dt><dd>{{ $visitasi->visit_at?->translatedFormat('l, d F Y') ?? 'Belum dijadwalkan' }}</dd></div>
            <div><dt class="text-xs uppercase text-abu">Mode</dt><dd>@include('pages.visitasi._mode', ['mode' => $visitasi->visit_mode])</dd></div>
            <div><dt class="text-xs uppercase text-abu">Tautan pertemuan</dt>
                <dd>@if ($linkMeet)<a href="{{ $linkMeet }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-biru2 underline">{{ $linkMeet }}</a>@else - @endif</dd></div>
            <div><dt class="text-xs uppercase text-abu">Tautan bukti dukung</dt>
                <dd>@if ($linkBukti)<a href="{{ $linkBukti }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-biru2 underline">{{ $linkBukti }}</a>@else - @endif</dd></div>
        </dl>
    </section>

    @if ($lock)
        <div class="mb-4 rounded-lg border border-kuning1 bg-kuning2/60 px-4 py-3 text-sm text-amber-900" role="alert">{{ $lock }}</div>
    @endif

    <form method="POST" action="{{ route('penilai-eksternal.review-visitasi.save', \App\Helpers\SecureId::encrypt($visitasi->id_visit)) }}" class="card space-y-4">
        @csrf
        <div>
            <label for="nilai_visitasi" class="label">Nilai visitasi (0-100)</label>
            <input id="nilai_visitasi" name="nilai_visitasi" type="number" min="0" max="100" step="0.01" required
                   value="{{ old('nilai_visitasi', $mine->score) }}" class="input max-w-48" @disabled($disabled)>
        </div>
        <div>
            <label for="catatan_visitasi" class="label">Catatan</label>
            <textarea id="catatan_visitasi" name="catatan_visitasi" rows="6" maxlength="5000" required class="input" @disabled($disabled)>{{ old('catatan_visitasi', $mine->catatan) }}</textarea>
        </div>
        @unless ($disabled)
            <div class="flex flex-wrap gap-3">
                <button type="submit" name="action" value="draft" class="btn-outline">Simpan draft</button>
                <button type="submit" name="action" value="submit" class="btn-primary"
                        data-confirm="Kirim penilaian visitasi ini? Nilai akan diteruskan ke sistem pusat.">Kirim penilaian</button>
            </div>
        @endunless
    </form>
@endsection
