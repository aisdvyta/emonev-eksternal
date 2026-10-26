@props([
    'column',
    'title',
    'items' => [],
    'valueField' => null,
    'labelField' => null,
    'searchable' => true,
    'align' => 'center',
    'popupWidth' => 'w-56',
    'popupPosition' => 'left-1/2 -translate-x-1/2',
])

@php
    $thAlignClass = $align === 'left' ? 'text-left' : ($align === 'right' ? 'text-right' : 'text-center');
    $flexAlignClass = $align === 'left' ? 'justify-start' : ($align === 'right' ? 'justify-end' : 'justify-center');
@endphp

<th class="px-6 py-5 {{ $thAlignClass }}">
    <div class="relative inline-block text-left">
        <div class="flex items-center {{ $flexAlignClass }} gap-1.5 cursor-pointer select-none group"
            onclick="toggleExcelFilter('{{ $column }}')">
            <span class="group-hover:text-biru1 transition">{{ $title }}</span>
            <svg class="w-3 h-3 text-biru3 group-hover:text-biru1 transition" fill="currentColor"
                viewBox="0 0 24 24">
                <path
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
        </div>
        <div id="excel-filter-{{ $column }}"
            class="hidden absolute {{ $popupPosition }} top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-xl {{ $popupWidth }} p-3 text-biru1 font-medium normal-case text-left">
            @if($searchable)
                <input type="text" placeholder="Cari..."
                    oninput="filterExcelList('{{ $column }}', this.value)"
                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
            @endif
            <label
                class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1 select-none">
                <input type="checkbox" id="excel-all-{{ $column }}"
                    onchange="toggleExcelAll('{{ $column }}', this)" checked class="accent-biru2 w-3.5 h-3.5">
                (Select All)
            </label>
            <div id="excel-list-{{ $column }}" class="max-h-40 overflow-y-auto space-y-1 pr-1">
                @foreach ($items as $key => $item)
                    @php
                        if (is_object($item)) {
                            $val = $valueField ? $item->{$valueField} : ($item->id ?? $key);
                            $lbl = $labelField ? $item->{$labelField} : ($item->name ?? $val);
                        } elseif (is_array($item)) {
                            $val = $valueField ? $item[$valueField] : ($item['id'] ?? $key);
                            $lbl = $labelField ? $item[$labelField] : ($item['name'] ?? $val);
                        } else {
                            $val = $key;
                            $lbl = $item;
                        }
                    @endphp
                    <label
                        class="excel-item-{{ $column }} flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs transition select-none"
                        data-value="{{ strtolower((string)$lbl) }}">
                        <input type="checkbox" class="excel-cb-{{ $column }} accent-biru2 w-3.5 h-3.5"
                            value="{{ $val }}" checked>
                        <span class="truncate">{{ $lbl }}</span>
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                <button type="button" onclick="closeExcelFilter('{{ $column }}')"
                    class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition cursor-pointer">Batal</button>
                <button type="button" onclick="applyExcelFilter('{{ $column }}')"
                    class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition cursor-pointer">OK</button>
            </div>
        </div>
    </div>
</th>
