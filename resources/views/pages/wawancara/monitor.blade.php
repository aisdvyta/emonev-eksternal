@extends('layouts.app')

@section('title', 'Wawancara')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-biru1">Penilaian Wawancara</h1>

    <x-period-filter :periods="$periods" :period="$period" :q="$q" :action="route('penilai-eksternal.monitor-wawancara')" />

    <div class="card overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-biru5/60">
            <thead class="bg-birumuda/60">
                <tr><th class="th">Satker</th><th class="th">Tanggal</th><th class="th">Status</th><th class="th"></th></tr>
            </thead>
            <tbody class="divide-y divide-biru5/40">
                @forelse ($rows as $row)
                    @php $i = $row->interview; @endphp
                    <tr>
                        <td class="td font-medium">{{ $i?->result?->satker?->nama_satker ?? '-' }}</td>
                        <td class="td whitespace-nowrap">{{ $i?->interview_at?->translatedFormat('d M Y') ?? 'Belum dijadwalkan' }}</td>
                        <td class="td"><x-status :row="$row" :final="(bool) $i?->is_final" /></td>
                        <td class="td text-right">
                            @if ($i)
                                <a class="btn-outline" href="{{ route('penilai-eksternal.review-wawancara', \App\Helpers\SecureId::encrypt($i->id_interview)) }}">Buka</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td text-center text-abu">Tidak ada wawancara yang ditugaskan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
