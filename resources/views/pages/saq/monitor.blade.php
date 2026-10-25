@extends('layouts.app')

@section('title', 'Kuesioner (SAQ)')

@section('content')
    <h1 class="mb-1 text-xl font-bold text-biru1">Kuesioner (SAQ)</h1>
    <p class="mb-4 text-sm text-abu">Hanya untuk dilihat sebagai bahan penilaian. Penilaian kuesioner dilakukan oleh evaluator internal.</p>

    <x-period-filter :periods="$periods" :period="$period" :q="$q" :action="route('penilai-eksternal.monitor-kues')" />

    <div class="card overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-biru5/60">
            <thead class="bg-birumuda/60"><tr><th class="th">Satker</th><th class="th">Status</th><th class="th"></th></tr></thead>
            <tbody class="divide-y divide-biru5/40">
                @forelse ($rows as $row)
                    <tr>
                        <td class="td font-medium">{{ $row->result?->satker?->nama_satker ?? '-' }}</td>
                        <td class="td"><span class="badge bg-slate-100 text-slate-700">{{ $row->statusLabel() }}</span></td>
                        <td class="td text-right"><a class="btn-outline" href="{{ route('penilai-eksternal.review-kues', \App\Helpers\SecureId::encrypt($row->id_submission)) }}">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="td text-center text-abu">Tidak ada kuesioner yang ditugaskan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
