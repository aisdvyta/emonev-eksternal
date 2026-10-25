@props(['row', 'final' => false])
@php
    if ($final) {
        [$text, $class] = ['Final di pusat', 'bg-hijaumuda text-hijau'];
    } elseif ($row->score !== null && ! $row->is_draft) {
        [$text, $class] = ['Terkirim', 'bg-birumuda text-biru2'];
    } elseif ($row->score !== null || filled($row->catatan)) {
        [$text, $class] = ['Draft', 'bg-kuning2 text-amber-800'];
    } else {
        [$text, $class] = ['Belum dinilai', 'bg-slate-100 text-slate-600'];
    }
@endphp
<span class="badge {{ $class }}">{{ $text }}</span>
@if ($row->pending_sync)
    <span class="badge bg-orange-100 text-orange-800" title="Nilai sudah tersimpan di portal dan menunggu diteruskan ke sistem pusat.">menunggu sinkron</span>
@endif
