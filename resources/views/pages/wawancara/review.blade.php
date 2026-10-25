@extends('layouts.app')

@section('title', 'Wawancara '.($interview->result?->satker?->nama_satker ?? ''))

@section('content')
    @php
        $linkMeet = \App\Helpers\Links::safe($interview->link_meet_interview);
        $disabled = (bool) $lock;
    @endphp

    <a href="{{ route('penilai-eksternal.monitor-wawancara') }}" class="text-sm text-biru2 underline">&larr; Kembali</a>
    <h1 class="mt-2 text-xl font-bold text-biru1">Wawancara · {{ $interview->result?->satker?->nama_satker ?? 'Satker' }}</h1>
    <p class="mb-4 text-sm text-abu"><x-status :row="$mine" :final="$interview->is_final" /></p>

    <section class="card mb-4">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs uppercase text-abu">Jadwal wawancara</dt><dd>{{ $interview->interview_at?->translatedFormat('l, d F Y') ?? 'Belum dijadwalkan' }}</dd></div>
            <div><dt class="text-xs uppercase text-abu">Tautan pertemuan</dt>
                <dd>@if ($linkMeet)<a href="{{ $linkMeet }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-biru2 underline">{{ $linkMeet }}</a>@else - @endif</dd></div>
        </dl>
    </section>

    @if ($lock)
        <div class="mb-4 rounded-lg border border-kuning1 bg-kuning2/60 px-4 py-3 text-sm text-amber-900" role="alert">{{ $lock }}</div>
    @endif

    <form method="POST" action="{{ route('penilai-eksternal.review-wawancara.save', \App\Helpers\SecureId::encrypt($interview->id_interview)) }}" class="card space-y-4">
        @csrf
        <div>
            <label for="score" class="label">Nilai wawancara (0-100)</label>
            <input id="score" name="score" type="number" min="0" max="100" step="0.01"
                   value="{{ old('score', $mine->score) }}" class="input max-w-48" @disabled($disabled)>
        </div>
        <div>
            <label for="note" class="label">Catatan</label>
            <textarea id="note" name="note" rows="6" maxlength="5000" class="input" @disabled($disabled)>{{ old('note', $mine->catatan) }}</textarea>
            <p class="mt-1 text-xs text-abu">Nilai dan catatan wajib diisi saat mengirim penilaian. Draft boleh dikosongkan sebagian.</p>
        </div>
        @unless ($disabled)
            <div class="flex flex-wrap gap-3">
                <button type="submit" name="action" value="draft" class="btn-outline">Simpan draft</button>
                <button type="submit" name="action" value="submit" class="btn-primary"
                        data-confirm="Kirim penilaian wawancara ini? Nilai akan diteruskan ke sistem pusat.">Kirim penilaian</button>
            </div>
        @endunless
    </form>
@endsection
