<div>

    {{-- Modal Filter Export --}}
    <div id="modal-export" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-7 py-5 bg-biru2 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-bold text-base">Unduh Laporan Excel</h3>
                    <p class="text-white/70 text-xs mt-0.5">Pilih filter sebelum mengunduh</p>
                </div>
                <button onclick="document.getElementById('modal-export').classList.add('hidden')"
                    class="text-white/70 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-export" method="GET" action="{{ route('operator.penilaian.export') }}"
                onsubmit="handleExportSubmit(event, this)" class="flex flex-col flex-1 min-h-0">
                <div class="p-7 space-y-6 overflow-y-auto flex-1">

                    {{-- Filter Tahun/Periode --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-biru4 mb-2">Tahun /
                            Periode</label>
                        <select name="id_periode"
                            class="w-full rounded-xl border border-abubiru bg-putihbg px-4 py-2.5 text-sm text-biru1 font-medium focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                            @foreach ($periods as $p)
                                <option value="{{ $p->id_periode }}"
                                    {{ $period->id_periode == $p->id_periode ? 'selected' : '' }}>
                                    {{ $p->nama ?? $p->tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Kabupaten/Kota --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-biru4">Kabupaten /
                                Kota</label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                                <input type="checkbox" id="check-all-kota" onchange="toggleAllKota(this)"
                                    class="w-3.5 h-3.5 rounded accent-biru2" checked>
                                Pilih Semua
                            </label>
                        </div>
                        <!-- Search kota -->
                        <div class="relative mb-2">
                            <input type="text" id="search-kota" placeholder="Cari kab/kota..."
                                oninput="filterKotaList(this.value)"
                                class="w-full rounded-lg border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-sm text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                            <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <!-- List kota -->
                        <div id="list-kota"
                            class="border border-abubiru rounded-xl bg-putihbg max-h-44 overflow-y-auto px-3 py-2 space-y-1">
                            @foreach ($allSubmissions as $sub)
                                @if ($sub->user && $sub->user->masterSatker)
                                    <label
                                        class="kota-item flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition"
                                        data-nama="{{ strtolower($sub->user->masterSatker->nama_satker) }}">
                                        <input type="checkbox" name="kota[]"
                                            value="{{ $sub->user->masterSatker->nama_satker }}"
                                            class="kota-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                        <span
                                            class="text-sm text-biru1 font-medium">{{ $sub->user->masterSatker->nama_satker }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Filter Skala Penilaian --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-biru4">Skala
                                Penilaian</label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                                <input type="checkbox" id="check-all-predikat" onchange="toggleAllPredikat(this)"
                                    class="w-3.5 h-3.5 rounded accent-biru2" checked>
                                Pilih Semua
                            </label>
                        </div>
                        <div class="border border-abubiru rounded-xl bg-putihbg px-3 py-2 space-y-1">
                            @foreach ([
        'INFORMATIF' => 'Informatif',
        'MENUJU INFORMATIF' => 'Menuju Informatif',
        'CUKUP INFORMATIF' => 'Cukup Informatif',
        'KURANG INFORMATIF' => 'Kurang Informatif',
        'TIDAK INFORMATIF' => 'Tidak Informatif',
        'BELUM DINILAI' => 'Belum Dinilai',
    ] as $val => $lbl)
                                <label
                                    class="flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition">
                                    <input type="checkbox" name="predikat[]" value="{{ $val }}"
                                        class="predikat-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                    <span class="text-sm text-biru1 font-medium">{{ $lbl }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-7 py-5 border-t border-abubiru shrink-0">
                    <button type="button" onclick="document.getElementById('modal-export').classList.add('hidden')"
                        class="px-5 py-2.5 rounded-xl border border-abubiru text-biru1 font-semibold text-sm bg-white hover:bg-putihbg transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-hijau text-white font-bold text-sm hover:opacity-90 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Unduh Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleAllKota(el) {
            document.querySelectorAll('.kota-checkbox').forEach(cb => {
                if (cb.closest('.kota-item').style.display !== 'none') cb.checked = el.checked;
            });
        }

        function filterKotaList(val) {
            const query = val.toLowerCase();
            document.querySelectorAll('.kota-item').forEach(el => {
                const nama = el.getAttribute('data-nama');
                if (nama.includes(query)) el.style.display = 'flex';
                else el.style.display = 'none';
            });
        }

        function toggleAllPredikat(el) {
            document.querySelectorAll('.predikat-checkbox').forEach(cb => cb.checked = el.checked);
        }
    </script>


    <x-tables.monitoring-table title="Tabel Hasil Penilaian Akhir" hasCards="true">
        <x-slot name="mobileFilters">
            <div x-data="{ openMobileFilter: false }" class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" @click="openMobileFilter = !openMobileFilter"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-biru4/10 border border-abubiru text-biru1 text-xs font-bold hover:bg-biru4/20 transition cursor-pointer">
                        <svg class="w-4 h-4 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Filter Kolom</span>
                        <svg class="w-3.5 h-3.5 text-biru3 transition-transform" :class="openMobileFilter ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <button type="button" wire:click="resetFilters" onclick="resetOperatorExcelFilters()"
                        class="inline-flex items-center gap-1 text-xs font-bold text-red-500 hover:text-red-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Reset Filter
                    </button>
                </div>

                <div x-show="openMobileFilter" x-collapse x-cloak class="pt-3 border-t border-abubiru/40 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                        {{-- Filter Periode (multi-select + searchable + checkbox) --}}
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="{
                                isOpen: false,
                                search: '',
                                periods: {{ json_encode($periods->map(fn($p) => ['value' => (string)$p->id_periode, 'label' => 'Tahun ' . $p->tahun])) }},
                                get sel() {
                                    const v = $wire.filterPeriodes;
                                    return Array.isArray(v) ? v.map(String) : (v ? [String(v)] : []);
                                },
                                get triggerLabel() {
                                    if (this.sel.length === 0) return 'Semua Periode';
                                    if (this.sel.length === 1) {
                                        const f = this.periods.find(p => p.value === this.sel[0]);
                                        return f ? f.label : 'Semua Periode';
                                    }
                                    return this.sel.length + ' periode dipilih';
                                },
                                get filtered() {
                                    const q = this.search.toLowerCase().trim();
                                    if (!q) return this.periods;
                                    return this.periods.filter(p => p.label.toLowerCase().includes(q));
                                },
                                toggle(val) {
                                    let cur = [...this.sel];
                                    const i = cur.indexOf(val);
                                    if (i >= 0) cur.splice(i, 1); else cur.push(val);
                                    $wire.set('filterPeriodes', cur);
                                },
                                clearAll() {
                                    $wire.set('filterPeriodes', []);
                                    this.search = '';
                                }
                            }">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Periode</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterPeriodes">
                                    {{ !empty($filterPeriodes) ? count((array)$filterPeriodes) . ' dipilih' : 'Semua' }}
                                </span>
                            </div>
                            <div class="p-2 relative">
                                <button type="button" @click="isOpen = !isOpen"
                                    class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                    :class="sel.length > 0 ? 'text-biru1' : 'text-biru3'">
                                    <span x-text="triggerLabel" class="truncate text-left"></span>
                                    <svg class="w-3.5 h-3.5 text-biru3 shrink-0 ml-2 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                    class="absolute left-0 right-0 top-full mt-1 bg-white border border-abubiru rounded-xl shadow-xl p-2 z-100">
                                    <input type="text" placeholder="Cari periode..."
                                        x-model="search"
                                        x-ref="periodeSearch"
                                        x-init="$watch('isOpen', v => v && $nextTick(() => $refs.periodeSearch?.focus()))"
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20 text-biru1">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Periode</span>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-0.5">
                                        <template x-for="p in filtered" :key="p.value">
                                            <div @click="toggle(p.value)"
                                                :class="sel.includes(p.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                <input type="checkbox" :checked="sel.includes(p.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                <span x-text="p.label" class="truncate flex-1"></span>
                                            </div>
                                        </template>
                                        <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filter Satker / Kab-Kota (multi-select + searchable + checkbox) --}}
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="{
                                isOpen: false,
                                search: '',
                                satkers: {{ json_encode($satkers->map(fn($s) => ['kode' => $s->kode_satker, 'label' => $s->nama_satker, 'value' => strtolower($s->nama_satker)])) }},
                                get sel() {
                                    const v = $wire.filterKabkots;
                                    return Array.isArray(v) ? v.map(s => String(s).toLowerCase()) : (v ? [String(v).toLowerCase()] : []);
                                },
                                get triggerLabel() {
                                    if (this.sel.length === 0) return 'Semua Satker';
                                    if (this.sel.length === 1) {
                                        const f = this.satkers.find(s => s.value === this.sel[0]);
                                        return f ? f.label : 'Semua Satker';
                                    }
                                    return this.sel.length + ' satker dipilih';
                                },
                                get filtered() {
                                    const q = this.search.toLowerCase().trim();
                                    if (!q) return this.satkers;
                                    return this.satkers.filter(s => s.label.toLowerCase().includes(q) || s.kode.includes(q));
                                },
                                toggle(val) {
                                    let cur = [...this.sel];
                                    const i = cur.indexOf(val);
                                    if (i >= 0) cur.splice(i, 1); else cur.push(val);
                                    $wire.set('filterKabkots', cur);
                                },
                                clearAll() {
                                    $wire.set('filterKabkots', []);
                                    this.search = '';
                                }
                            }">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Kab / Kota</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterKabkots">
                                    {{ !empty($filterKabkots) ? count((array)$filterKabkots) . ' dipilih' : 'Semua' }}
                                </span>
                            </div>
                            <div class="p-2 relative">
                                <button type="button" @click="isOpen = !isOpen"
                                    class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                    :class="sel.length > 0 ? 'text-biru1' : 'text-biru3'">
                                    <span x-text="triggerLabel" class="truncate text-left"></span>
                                    <svg class="w-3.5 h-3.5 text-biru3 shrink-0 ml-2 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                    class="absolute left-0 right-0 top-full mt-1 bg-white border border-abubiru rounded-xl shadow-xl p-2 z-100">
                                    <input type="text" placeholder="Cari kode / nama satker..."
                                        x-model="search"
                                        x-ref="satkerSearch"
                                        x-init="$watch('isOpen', v => v && $nextTick(() => $refs.satkerSearch?.focus()))"
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20 text-biru1">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Satker</span>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-0.5">
                                        <template x-for="s in filtered" :key="s.value">
                                            <div @click="toggle(s.value)"
                                                :class="sel.includes(s.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                <input type="checkbox" :checked="sel.includes(s.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                <span x-text="s.label" class="truncate flex-1"></span>
                                            </div>
                                        </template>
                                        <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filter Kualifikasi / Skala Penilaian (multi-select + searchable + checkbox) --}}
                        <div class="sm:col-span-2 bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="{
                                isOpen: false,
                                search: '',
                                opts: [
                                    { value: 'INFORMATIF', label: 'Informatif' },
                                    { value: 'MENUJU INFORMATIF', label: 'Menuju Informatif' },
                                    { value: 'CUKUP INFORMATIF', label: 'Cukup Informatif' },
                                    { value: 'KURANG INFORMATIF', label: 'Kurang Informatif' },
                                    { value: 'TIDAK INFORMATIF', label: 'Tidak Informatif' },
                                    { value: 'BELUM DINILAI', label: 'Belum Dinilai' },
                                ],
                                get sel() {
                                    const v = $wire.filterStatuses;
                                    return Array.isArray(v) ? v : (v ? [String(v)] : []);
                                },
                                get triggerLabel() {
                                    if (this.sel.length === 0) return 'Semua Kualifikasi';
                                    if (this.sel.length === 1) {
                                        const f = this.opts.find(o => o.value === this.sel[0]);
                                        return f ? f.label : 'Semua Kualifikasi';
                                    }
                                    return this.sel.length + ' kualifikasi dipilih';
                                },
                                get filtered() {
                                    const q = this.search.toLowerCase().trim();
                                    if (!q) return this.opts;
                                    return this.opts.filter(o => o.label.toLowerCase().includes(q));
                                },
                                toggle(val) {
                                    let cur = [...this.sel];
                                    const i = cur.indexOf(val);
                                    if (i >= 0) cur.splice(i, 1); else cur.push(val);
                                    $wire.set('filterStatuses', cur);
                                },
                                clearAll() {
                                    $wire.set('filterStatuses', []);
                                    this.search = '';
                                }
                            }">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Kualifikasi MONEV KIP</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterStatuses">
                                    {{ !empty($filterStatuses) ? count((array)$filterStatuses) . ' dipilih' : 'Semua' }}
                                </span>
                            </div>
                            <div class="p-2 relative">
                                <button type="button" @click="isOpen = !isOpen"
                                    class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                    :class="sel.length > 0 ? 'text-biru1' : 'text-biru3'">
                                    <span x-text="triggerLabel" class="truncate text-left"></span>
                                    <svg class="w-3.5 h-3.5 text-biru3 shrink-0 ml-2 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                    class="absolute left-0 right-0 top-full mt-1 bg-white border border-abubiru rounded-xl shadow-xl p-2 z-100">
                                    <input type="text" placeholder="Cari kualifikasi..."
                                        x-model="search"
                                        x-ref="statusSearch"
                                        x-init="$watch('isOpen', v => v && $nextTick(() => $refs.statusSearch?.focus()))"
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20 text-biru1">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Kualifikasi</span>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-0.5">
                                        <template x-for="opt in filtered" :key="opt.value">
                                            <div @click="toggle(opt.value)"
                                                :class="sel.includes(opt.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                <input type="checkbox" :checked="sel.includes(opt.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                <span x-text="opt.label" class="truncate flex-1"></span>
                                            </div>
                                        </template>
                                        <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="cards">
            @forelse($submissions as $sub)
                @php
                    $satker = $sub->user->masterSatker;
                    $v = $sub->visitasi;
                    $i = $sub->interview;
                    $saq = $sub->result?->ques_score;
                    $visit = $v?->visit_score;
                    $interview = $i?->interview_score;
                    $isMySatker = $satker && $satker->kode_satker === $userSatkerKode;
                @endphp
                @if ($satker)
                    <div wire:key="penilaian-detail-card-{{ $sub->id_submission }}" @class([
                        'rounded-2xl border p-4 shadow-xs space-y-3 transition',
                        'bg-yellow-50/40 border-yellow-400 ring-2 ring-yellow-400/50 shadow-sm' => $isMySatker,
                        'bg-white border-abubiru' => !$isMySatker,
                    ])>
                        <div class="flex items-start justify-between gap-2 border-b border-abubiru/30 pb-2.5">
                            <div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-biru3 uppercase">Kode: {{ $satker->kode_satker }}</span>
                                    <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.2 text-xs font-bold">
                                        {{ $sub->period->tahun ?? '-' }}
                                    </span>
                                    @if($isMySatker)
                                        <span class="inline-flex rounded-md bg-yellow-400 text-biru1 px-1.5 py-0.2 text-xs font-black uppercase tracking-wider">
                                            Satker Anda
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm font-bold text-biru1 mt-0.5">{{ $satker->nama_satker }}</div>
                            </div>
                            <x-ui.status-badge :status="$sub->predikat" class="uppercase! text-xs! font-extrabold! whitespace-nowrap! shrink-0" />
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-putihbg p-2.5 rounded-xl border border-abubiru/40">
                                <div class="flex items-center justify-between text-biru3 text-xs font-medium mb-0.5">
                                    <span>Nilai SAQ</span>
                                    <span class="text-xs font-bold bg-abubiru px-1 rounded">{{ (float) ($pembobotan->weight_saq ?? 30) }}%</span>
                                </div>
                                <span class="font-bold text-biru1 text-sm">{{ $saq !== null ? number_format($saq, 2) : '-' }}</span>
                            </div>
                            <div class="bg-putihbg p-2.5 rounded-xl border border-abubiru/40">
                                <div class="flex items-center justify-between text-biru3 text-xs font-medium mb-0.5">
                                    <span>Nilai Visitasi</span>
                                    <span class="text-xs font-bold bg-abubiru px-1 rounded">{{ (float) ($pembobotan->weight_visitasi ?? 40) }}%</span>
                                </div>
                                <span class="font-bold text-biru1 text-sm">{{ $visit !== null ? number_format($visit, 2) : '-' }}</span>
                            </div>
                            <div class="bg-putihbg p-2.5 rounded-xl border border-abubiru/40">
                                <div class="flex items-center justify-between text-biru3 text-xs font-medium mb-0.5">
                                    <span>Nilai Wawancara</span>
                                    <span class="text-xs font-bold bg-abubiru px-1 rounded">{{ (float) ($pembobotan->weight_wawancara ?? 30) }}%</span>
                                </div>
                                <span class="font-bold text-biru1 text-sm">{{ $interview !== null ? number_format($interview, 2) : '-' }}</span>
                            </div>
                            <div class="bg-birumuda p-2.5 rounded-xl border border-biru5">
                                <div class="flex items-center justify-between text-biru2 text-xs font-extrabold mb-0.5">
                                    <span>Nilai MONEV KIP</span>
                                </div>
                                <span class="font-black text-biru2 text-base">{{ number_format($sub->calculated_final, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div wire:key="penilaian-detail-card-empty" class="p-6 text-center text-sm font-medium text-biru3 bg-white rounded-xl border border-abubiru">
                    Belum ada data penilaian akhir.
                </div>
            @endforelse
        </x-slot>
        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-widest text-biru4 border-b border-abubiru">
                <tr>
                    <th class="px-8 py-5 text-left">NO</th>

                    {{-- PERIODE with Excel Filter --}}
                    <th class="px-6 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer"
                                onclick="toggleExcelFilter('periode')">
                                <span>PERIODE</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-periode"
                                class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('periode', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-periode"
                                        onchange="toggleExcelAll('periode', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-periode" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($periods as $p)
                                        <label
                                            class="excel-item-periode flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($p->tahun) }}">
                                            <input type="checkbox" class="excel-cb-periode accent-biru2"
                                                value="{{ $p->id_periode }}" checked>
                                            {{ $p->tahun }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('periode')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('periode')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- KODE SATKER with Excel Filter --}}
                    <th class="px-8 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer"
                                onclick="toggleExcelFilter('kode_satker')">
                                <span>KODE SATKER</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-kode_satker"
                                class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('kode_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-kode_satker"
                                        onchange="toggleExcelAll('kode_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-kode_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label
                                            class="excel-item-kode_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($sat->kode_satker) }}">
                                            <input type="checkbox" class="excel-cb-kode_satker accent-biru2"
                                                value="{{ $sat->kode_satker }}" checked>
                                            {{ $sat->kode_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- NAMA KABUPATEN/KOTA with Excel Filter --}}
                    <th class="px-8 py-5 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer"
                                onclick="toggleExcelFilter('nama_satker')">
                                <span>NAMA KABUPATEN/KOTA</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-nama_satker"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('nama_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-nama_satker"
                                        onchange="toggleExcelAll('nama_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-nama_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label
                                            class="excel-item-nama_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($sat->nama_satker) }}">
                                            <input type="checkbox" class="excel-cb-nama_satker accent-biru2"
                                                value="{{ strtolower($sat->nama_satker) }}" checked>
                                            {{ $sat->nama_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    @foreach($stageOrder as $stageKey)
                        @if($stageKey === 'saq')
                            <th class="px-8 py-5 text-center">NILAI SAQ ({{ (float) ($pembobotan->weight_saq ?? 30) }}%)</th>
                        @elseif($stageKey === 'visitasi')
                            <th class="px-8 py-5 text-center">NILAI VISITASI ({{ (float) ($pembobotan->weight_visitasi ?? 40) }}%)</th>
                        @elseif($stageKey === 'wawancara')
                            <th class="px-8 py-5 text-center">NILAI WAWANCARA ({{ (float) ($pembobotan->weight_wawancara ?? 30) }}%)</th>
                        @endif
                    @endforeach
                    <th class="px-8 py-5 text-center">NILAI MONEV KIP</th>

                    {{-- SKALA PENILAIAN with Excel Filter --}}
                    <th class="px-8 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer"
                                onclick="toggleExcelFilter('skala_penilaian')">
                                <span>SKALA PENILAIAN</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-skala_penilaian"
                                class="hidden absolute right-16 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-56 p-3 text-biru1 font-medium normal-case text-left">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-skala_penilaian"
                                        onchange="toggleExcelAll('skala_penilaian', this)" checked
                                        class="accent-biru2">
                                    (Select All)
                                </label>
                                <div class="space-y-1">
                                    @foreach ([
        'INFORMATIF' => 'Informatif',
        'MENUJU INFORMATIF' => 'Menuju Informatif',
        'CUKUP INFORMATIF' => 'Cukup Informatif',
        'KURANG INFORMATIF' => 'Kurang Informatif',
        'TIDAK INFORMATIF' => 'Tidak Informatif',
        'BELUM DINILAI' => 'Belum Dinilai',
    ] as $val => $lbl)
                                        <label
                                            class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs">
                                            <input type="checkbox" class="excel-cb-skala_penilaian accent-biru2"
                                                value="{{ $val }}" checked>
                                            {{ $lbl }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('skala_penilaian')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('skala_penilaian')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>
        </x-slot>
        <x-slot name="headerCenter">
            <!-- Live Search + Unduh (mobile: side by side) -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1" wire:key="penilaian-detail-search-wrapper">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        id="penilaian-detail-table-search"
                        wire:key="penilaian-detail-table-search"
                        placeholder="Cari Kab/Kota..."
                        class="w-full rounded-lg border border-abubiru bg-white/20 shadow-sm pl-9 pr-4 py-2 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/50 transition">
                    <div class="absolute left-3 top-2.5 text-white/70">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <!-- Unduh button on Mobile (right of search bar) -->
                <button type="button" onclick="document.getElementById('modal-export').classList.remove('hidden')"
                    class="md:hidden inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-3 py-2 text-xs font-bold shadow-md transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh</span>
                </button>
            </div>
        </x-slot>

        <x-slot name="headerActions">
            <div class="hidden md:flex items-center gap-3">
                <button type="button" onclick="document.getElementById('modal-export').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Excel</span>
                </button>
                <button type="button" wire:click="resetFilters" onclick="resetOperatorExcelFilters()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            </div>
        </x-slot>


        @forelse($submissions as $sub)
            @php
                $satker = $sub->user->masterSatker;
                $v = $sub->visitasi;
                $i = $sub->interview;

                $saq = $sub->result?->ques_score ?? 0;
                $visit = $v->visit_score ?? 0;
                $interview = 0;
                if ($i && $i->interview_score !== null) {
                    $interview = $i->interview_score;
                }
            @endphp
            @if ($satker)
                <tr wire:key="penilaian-detail-row-{{ $sub->id_submission }}"
                    class="border-t border-abubiru/30 transition text-sm cursor-default {{ $satker->kode_satker === $userSatkerKode ? 'bg-yellow-100 ring-2 ring-yellow-400 font-extrabold shadow-sm z-10 relative' : 'even:bg-putihbg hover:bg-slate-50' }}">
                    <td class="px-8 py-5 text-left font-bold text-biru3">
                        {{ sprintf('%02d', ($submissions->currentPage() - 1) * $submissions->perPage() + $loop->iteration) }}
                    </td>
                    <td class="px-6 py-5 text-center font-medium text-biru2">{{ $sub->period->tahun ?? '-' }}</td>
                    <td class="px-8 py-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="font-extrabold text-biru2 text-sm">{{ $satker->kode_satker }}</span>
                        </div>
                    </td>
                    <td class="px-8 py-5 text-left">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="font-extrabold text-biru2 text-sm">{{ $satker->nama_satker }}</span>
                        </div>
                    </td>
                    @foreach($stageOrder as $stageKey)
                        @if($stageKey === 'saq')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @php
                                    $evalEnd = $sub->period->eval_end
                                        ? \Carbon\Carbon::parse($sub->period->eval_end)->endOfDay()
                                        : null;
                                    $saqVisible = !$evalEnd || now()->greaterThan($evalEnd);
                                    $isOwnSatker = $userSatkerKode && $satker->kode_satker === $userSatkerKode;
                                @endphp
                                @if ($sub->id_submission !== null && $saqVisible)
                                    @if ($isOwnSatker)
                                        @if (Auth::user()?->role?->nama_role === 'verifikator')
                                            <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                                class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                                {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                                            </a>
                                        @else
                                            <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($sub->id_periode)]) }}"
                                                class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                                {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-biru4">
                                            {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                                @endif
                            </td>
                        @elseif($stageKey === 'visitasi')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @php
                                    $visitEnd = $sub->period->visit_end
                                        ? \Carbon\Carbon::parse($sub->period->visit_end)->endOfDay()
                                        : null;
                                    $visitVisible = !$visitEnd || now()->greaterThan($visitEnd);
                                    $isOwnSatker = $userSatkerKode && $satker->kode_satker === $userSatkerKode;
                                @endphp
                                @if ($sub->visitasi && $visitVisible)
                                    @if ($isOwnSatker && Auth::user()?->role?->nama_role === 'operator')
                                        <a href="{{ route('operator.visitasi', ['id_periode' => \App\Helpers\SecureId::encrypt($sub->id_periode)]) }}"
                                            class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                            {{ $sub->visitasi->visit_score !== null ? number_format($sub->visitasi->visit_score, 2) : '-' }}
                                        </a>
                                    @else
                                        <span class="text-biru4">
                                            {{ $sub->visitasi->visit_score !== null ? number_format($sub->visitasi->visit_score, 2) : '-' }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                                @endif
                            </td>
                        @elseif($stageKey === 'wawancara')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @php
                                    $interviewEnd = $sub->period->interview_end
                                        ? \Carbon\Carbon::parse($sub->period->interview_end)->endOfDay()
                                        : null;
                                    $interviewVisible = !$interviewEnd || now()->greaterThan($interviewEnd);
                                    $isOwnSatker = $userSatkerKode && $satker->kode_satker === $userSatkerKode;
                                @endphp
                                @if ($sub->interview && $interviewVisible)
                                    @if ($isOwnSatker && Auth::user()?->role?->nama_role === 'operator')
                                        <a href="{{ route('operator.wawancara', ['id_periode' => \App\Helpers\SecureId::encrypt($sub->id_periode)]) }}"
                                            class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                            {{ $sub->interview->interview_score !== null ? number_format($sub->interview->interview_score, 2) : '-' }}
                                        </a>
                                    @else
                                        <span class="text-biru4">
                                            {{ $sub->interview->interview_score !== null ? number_format($sub->interview->interview_score, 2) : '-' }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                    <td class="px-8 py-5 text-center text-biruinfo font-black text-base">
                        @if ($interviewVisible)
                            {{ number_format($sub->calculated_final, 2) }}
                        @else
                            <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @php
                            if (!$interviewVisible) {
                                $badgeColor = 'bg-slate-100 text-slate-400';
                                $labelText = 'Pending';
                            } elseif ($sub->predikat === 'BELUM DINILAI') {
                                $badgeColor = 'bg-slate-100 text-slate-500';
                                $labelText = 'Belum Dinilai';
                            } elseif ($sub->predikat === 'INFORMATIF') {
                                $badgeColor = 'bg-hijau text-white';
                                $labelText = 'Informatif';
                            } elseif ($sub->predikat === 'MENUJU INFORMATIF') {
                                $badgeColor = 'bg-hijaumuda/90 text-white';
                                $labelText = 'Menuju Informatif';
                            } elseif ($sub->predikat === 'CUKUP INFORMATIF') {
                                $badgeColor = 'bg-kuning2/40 text-oren2';
                                $labelText = 'Cukup Informatif';
                            } elseif ($sub->predikat === 'KURANG INFORMATIF') {
                                $badgeColor = 'bg-oren2/10 text-oren2';
                                $labelText = 'Kurang Informatif';
                            } else {
                                $badgeColor = 'bg-merah2 text-white';
                                $labelText = 'Tidak Informatif';
                            }
                        @endphp
                        <span
                            class="inline-flex rounded-lg {{ $badgeColor }} px-3 py-1.5 text-xs font-extrabold uppercase whitespace-nowrap">
                            {{ $labelText }}
                        </span>
                    </td>
                </tr>
            @endif
        @empty
            <tr wire:key="penilaian-detail-row-empty" class="empty-row">
                <td colspan="9" class="px-8 py-15 text-center text-sm font-medium text-biru3">
                    Belum ada data penilaian akhir.
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <x-tables.pagination :paginator="$submissions" livewire="true" />
        </x-slot>
    </x-tables.monitoring-table>

    <script>
        /* ============================================================
         * EXCEL-STYLE TABLE FILTERS & COMPONENT DROPDOWNS
         * ============================================================ */

        function toggleExcelFilter(column) {
            const el = document.getElementById('excel-filter-' + column);
            if (!el) return;
            const isHidden = el.classList.contains('hidden');

            // Close all filters first
            closeAllExcelFilters();

            const wrapper = el.closest('.overflow-x-auto');
            if (isHidden) {
                el.classList.remove('hidden');
                if (wrapper && window.innerWidth >= 768) {
                    wrapper.style.overflow = 'visible';
                }
            } else {
                el.classList.add('hidden');
                if (wrapper) wrapper.style.overflow = '';
            }
        }

        function closeExcelFilter(column) {
            const el = document.getElementById('excel-filter-' + column);
            if (el) el.classList.add('hidden');

            // Restore overflow if all are closed
            const activeFilters = document.querySelectorAll('[id^="excel-filter-"]:not(.hidden)');
            if (activeFilters.length === 0) {
                const wrapper = document.querySelector('.overflow-x-auto');
                if (wrapper) wrapper.style.overflow = '';
            }
        }

        function closeAllExcelFilters() {
            document.querySelectorAll('[id^="excel-filter-"]').forEach(el => el.classList.add('hidden'));
            const wrapper = document.querySelector('.overflow-x-auto');
            if (wrapper) wrapper.style.overflow = '';
        }

        function filterExcelList(column, q) {
            const query = q.toLowerCase();
            document.querySelectorAll('.excel-item-' + column).forEach(item => {
                const val = item.dataset.value || '';
                item.style.display = val.includes(query) ? '' : 'none';
            });
        }

        function toggleExcelAll(column, master) {
            document.querySelectorAll('.excel-cb-' + column).forEach(cb => {
                if (cb.parentElement.style.display !== 'none') {
                    cb.checked = master.checked;
                }
            });
        }

        // Apply filters & call Livewire component
        function applyExcelFilter(column) {
            if (column === 'periode') {
                const checked = [...document.querySelectorAll('.excel-cb-periode:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-periode');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterPeriodes', filterVal);
            } else if (column === 'kode_satker') {
                const checked = [...document.querySelectorAll('.excel-cb-kode_satker:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-kode_satker');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterKodes', filterVal);
            } else if (column === 'nama_satker') {
                const checked = [...document.querySelectorAll('.excel-cb-nama_satker:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-nama_satker');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterKabkots', filterVal);
            } else if (column === 'skala_penilaian') {
                const checked = [...document.querySelectorAll('.excel-cb-skala_penilaian:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-skala_penilaian');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterStatuses', filterVal);
            }
            closeExcelFilter(column);
        }



        /* --- Line Chart Local controls --- */
        function changeLineChartKabkot(kode) {
            updateLineChartForKabkot();
        }

        /* Indeterminate state sync on checking individual Excel filter boxes */
        document.addEventListener('change', function(e) {
            const columns = ['periode', 'kode_satker', 'nama_satker', 'skala_penilaian'];
            columns.forEach(col => {
                if (e.target.classList.contains('excel-cb-' + col) || e.target.classList.contains(
                        'excel-cb-skala_penilaian')) {
                    const selector = col === 'skala_penilaian' ? '.excel-cb-skala_penilaian' :
                        '.excel-cb-' + col;
                    const all = document.querySelectorAll(selector);
                    const checked = document.querySelectorAll(selector + ':checked');
                    const master = document.getElementById('excel-all-' + col);
                    if (master) {
                        master.checked = all.length === checked.length;
                        master.indeterminate = checked.length > 0 && checked.length < all.length;
                    }
                }
            });
        });

        /* Close popovers when clicking outside */
        document.addEventListener('click', function(e) {
            // Excel headers
            const relativeHeaders = document.querySelectorAll('th.relative');
            relativeHeaders.forEach(th => {
                if (!th.contains(e.target)) {
                    const panel = th.querySelector('[id^="excel-filter-"]');
                    if (panel) panel.classList.add('hidden');
                }
            });
            // Reset table overflow if all excel filters closed
            const activeFilters = document.querySelectorAll('[id^="excel-filter-"]:not(.hidden)');
            if (activeFilters.length === 0) {
                const wrapper = document.querySelector('.overflow-x-auto');
                if (wrapper) wrapper.style.overflow = '';
            }
        });

        async function handleExportSubmit(event, form) {
            event.preventDefault();
            let alertEl = form.querySelector('.export-error-alert');
            if (alertEl) {
                alertEl.classList.add('hidden');
                alertEl.innerText = '';
            }
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            params.append('validate', '1');
            try {
                const response = await fetch(form.action + '?' + params.toString());
                const data = await response.json();
                if (!data.success) {
                    if (!alertEl) {
                        alertEl = document.createElement('div');
                        alertEl.className =
                            'export-error-alert mt-4 mx-7 p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl';
                        const footer = form.querySelector('.flex.justify-end');
                        footer.parentElement.insertBefore(alertEl, footer);
                    }
                    alertEl.innerText = data.message;
                    alertEl.classList.remove('hidden');
                } else {
                    params.delete('validate');
                    window.location.href = form.action + '?' + params.toString();
                    setTimeout(() => {
                        document.getElementById('modal-export').classList.add('hidden');
                        if (alertEl) alertEl.classList.add('hidden');
                    }, 1000);
                }
            } catch (e) {
                console.error(e);
                form.submit();
            }
        }

        function resetOperatorExcelFilters() {
            document.querySelectorAll('[class^="excel-cb-"]').forEach(cb => cb.checked = true);
            document.querySelectorAll('[id^="excel-all-"]').forEach(cb => {
                cb.checked = true;
                cb.indeterminate = false;
            });
        }
    </script>
</div>
