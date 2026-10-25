@extends('layouts.app')

@section('title', 'Kuesioner '.($sub->result?->satker?->nama_satker ?? ''))

@section('content')
    <a href="{{ route('penilai-eksternal.monitor-kues') }}" class="text-sm text-biru2 underline">&larr; Kembali</a>
    <h1 class="mt-2 text-xl font-bold text-biru1">{{ $sub->result?->satker?->nama_satker ?? 'Satker' }}</h1>
    <p class="mb-4 text-sm text-abu">Status: {{ $sub->statusLabel() }} · Tampilan hanya-lihat</p>

    <div class="space-y-4">
        @foreach ($indicators as $indicator)
            <details class="card" @if ($loop->first) open @endif>
                <summary class="cursor-pointer font-semibold text-biru1">{{ $indicator->nama_indikator }}</summary>
                @if ($indicator->deskripsi_indikator)
                    <p class="mt-1 text-sm text-abu">{{ $indicator->deskripsi_indikator }}</p>
                @endif

                @foreach ($indicator->subIndikators as $sub_indikator)
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-biru2">{{ $sub_indikator->nama_sub_indikator }}</h3>
                    <ol class="mt-2 space-y-3">
                        @foreach ($sub_indikator->pertanyaans as $q)
                            @php
                                $a = $answers->get($q->id_master_pertanyaan);
                                $linkPpid = \App\Helpers\Links::safe($a?->link_ppid);
                                $linkDrive = \App\Helpers\Links::safe($a?->link_drive);
                            @endphp
                            <li class="rounded-lg border border-biru5/70 p-3">
                                <p class="text-sm font-medium">{{ $q->ques_text }}</p>
                                @if ($q->ques_description)
                                    <p class="mt-1 text-xs text-abu">{{ $q->ques_description }}</p>
                                @endif
                                <p class="mt-2 text-sm">
                                    Jawaban satker:
                                    @if ($a === null || $a->answer === null)
                                        <span class="badge bg-slate-100 text-slate-600">Belum diisi</span>
                                    @elseif ($a->answer)
                                        <span class="badge bg-hijaumuda text-hijau">Ya</span>
                                    @else
                                        <span class="badge bg-red-50 text-merah1">Tidak</span>
                                    @endif
                                </p>
                                @if ($linkPpid || $linkDrive || ($a && ($a->link_ppid || $a->link_drive)))
                                    <ul class="mt-2 space-y-1 text-sm">
                                        @if ($a?->link_ppid)
                                            <li>Bukti PPID:
                                                @if ($linkPpid)
                                                    <a href="{{ $linkPpid }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-biru2 underline">{{ $linkPpid }}</a>
                                                @else
                                                    <span class="break-all">{{ $a->link_ppid }}</span>
                                                @endif
                                            </li>
                                        @endif
                                        @if ($a?->link_drive)
                                            <li>Bukti Drive:
                                                @if ($linkDrive)
                                                    <a href="{{ $linkDrive }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-biru2 underline">{{ $linkDrive }}</a>
                                                @else
                                                    <span class="break-all">{{ $a->link_drive }}</span>
                                                @endif
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endforeach
            </details>
        @endforeach
    </div>
@endsection
