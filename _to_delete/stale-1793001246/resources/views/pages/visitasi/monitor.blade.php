@extends('layouts.app')

@section('title', 'Visitasi')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-biru1">Penilaian Visitasi</h1>

    <x-period-filter :periods="$periods" :period="$period" :q="$q" :action="route('penilai-eksternal.monitor-visitasi')" />

    <div class="card overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-biru5/60">
            <thead class="bg-birumuda/60">
                <tr><th class="th">Satker</th><th class="th">Tanggal</th><th class="th">Mode</th><th class="th">Status</th><th class="th"></th></tr>
            </thead>
            <tbody class="divide-y divide-biru5/40">
                @forelse ($rows as $row)
                    @php $v = $row->visitasi; @endphp
                    <tr>
                        <td class="td font-medium">{{ $v?->result?->satker?->nama_satker ?? '-' }}</td>
                        <td class="td whitespace-nowrap">{{ $v?->visit_at?->translatedFormat('d M Y') ?? 'Belum dijadwalkan' }}</td>
                        <td class="td">@include('pages.visitasi._mode', ['mode' => $v?->visit_mode])</td>
                        <td class="td"><x-status :row="$row" :final="(bool) $v?->is_final" /></td>
                        <td class="td text-right">
                            @if ($v)
                                <a class="btn-outline" href="{{ route('penilai-eksternal.review-visitasi', \App\Helpers\SecureId::encrypt($v->id_visit)) }}">Buka</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td text-center text-abu">Tidak ada visitasi yang ditugaskan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
