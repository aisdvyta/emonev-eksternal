@props(['periods', 'period', 'q' => '', 'action'])
<form method="GET" action="{{ $action }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label for="id_periode" class="label">Periode</label>
        <select id="id_periode" name="id_periode" class="input min-w-48">
            @foreach ($periods as $p)
                <option value="{{ $p->id_periode }}" @selected($period && (int) $period->id_periode === (int) $p->id_periode)>
                    Periode {{ $p->periode_start?->translatedFormat('Y') ?? $p->id_periode }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="grow">
        <label for="q" class="label">Cari satker</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="input" placeholder="Nama satker">
    </div>
    <button type="submit" class="btn-outline">Terapkan</button>
</form>
