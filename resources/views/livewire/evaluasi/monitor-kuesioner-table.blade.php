@php
    $routePrefix = Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super-evaluator' : 'evaluator';
@endphp
<div data-table-prefix="monkues">
    {{-- Modal Filter Export Kues --}}
    <div id="modal-export-kues" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-7 py-5 bg-biru2 text-white flex items-center justify-between shrink-0 text-left">
                <div>
                    <h3 class="font-bold text-base">Unduh Rekap SAQ Excel</h3>
                    <p class="text-white/70 text-xs mt-0.5">Pilih filter sebelum mengunduh</p>
                </div>
                <button onclick="document.getElementById('modal-export-kues').classList.add('hidden')"
                    class="text-white/70 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-export-kues" method="GET" action="{{ route($routePrefix . '.monitor-kues.export') }}"
                onsubmit="handleExportKuesSubmit(event, this)" class="flex flex-col flex-1 min-h-0 text-left">
                <div class="p-7 space-y-6 overflow-y-auto flex-1">
                    <input type="hidden" name="id_periode" value="{{ $periodeId }}">

                    {{-- Filter Kabupaten/Kota --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-biru4">Kabupaten /
                                Kota</label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                                <input type="checkbox" id="check-all-kues-kota" onchange="toggleAllKuesKota(this)"
                                    class="w-3.5 h-3.5 rounded accent-biru2" checked>
                                Pilih Semua
                            </label>
                        </div>
                        <!-- Search kota -->
                        <div class="relative mb-2">
                            <input type="text" id="search-kues-kota" placeholder="Cari kab/kota..."
                                oninput="filterKuesKotaList(this.value)"
                                class="w-full rounded-lg border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-sm text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                            <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <!-- List kota -->
                        <div id="list-kues-kota"
                            class="border border-abubiru rounded-xl bg-putihbg max-h-60 overflow-y-auto px-3 py-2 space-y-1">
                            @foreach ($satkers as $sat)
                                <label
                                    class="kues-kota-item flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition"
                                    data-nama="{{ strtolower($sat->nama_satker) }}">
                                    <input type="checkbox" name="kota[]" value="{{ $sat->nama_satker }}"
                                        class="kues-kota-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                    <span class="text-sm text-biru1 font-medium">{{ $sat->nama_satker }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-7 py-5 border-t border-abubiru shrink-0">
                    <button type="button"
                        onclick="document.getElementById('modal-export-kues').classList.add('hidden')"
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

    <!-- Stat Cards -->
    <section class="grid grid-cols-3 gap-2.5 sm:gap-4 md:gap-6 mb-6">
        <x-ui.card-stat label="TOTAL KABKOT" value="{{ $totalKabkot }}" color="primary">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="SUBMITTED" value="{{ $submittedCount }}" color="success">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="RATA-RATA NILAI SAQ" value="{{ number_format($avgScore, 2) }}" color="info">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>
    </section>

    <!-- Table -->
    <x-tables.monitoring-table title="Daftar Kabupaten & Kota" hasCards="true">
        <x-slot name="headerCenter">
            <!-- Search + Unduh Excel (mobile: side by side) -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1" wire:key="monkues-search-wrapper">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        id="monkues-search-input"
                        wire:key="monkues-search-input"
                        placeholder="Cari Kab/Kota..."
                        class="w-full rounded-lg border border-white/30 bg-white/20 shadow-sm pl-9 pr-4 py-2 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/50 transition">
                    <div class="absolute left-3 top-2.5 text-white/70">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <!-- Unduh Excel: mobile only -->
                <button type="button"
                    onclick="document.getElementById('modal-export-kues').classList.remove('hidden')"
                    class="md:hidden inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-3 py-2 text-xs font-bold shadow-md transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Excel</span>
                </button>
            </div>
        </x-slot>

        <x-slot name="headerActions">
            <div class="flex items-center gap-3">
                <!-- Unduh Excel: desktop only -->
                <button type="button"
                    onclick="document.getElementById('modal-export-kues').classList.remove('hidden')"
                    class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Excel</span>
                </button>
                <!-- Reset Filter: desktop only -->
                <button type="button" wire:click="resetFilters" onclick="monKuesResetAllFilters()"
                    class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            </div>
        </x-slot>
        <x-slot name="mobileFilters">
            <div x-data="{ openMobileFilter: false }" class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" @click="openMobileFilter = !openMobileFilter"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-biru4/10 border border-abubiru text-biru1 text-xs font-bold hover:bg-biru4/20 transition cursor-pointer">
                        <svg class="w-4 h-4 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Filter Kolom</span>
                        <svg class="w-3.5 h-3.5 text-biru3 transition-transform"
                            :class="openMobileFilter ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <button type="button" wire:click="resetFilters" onclick="monKuesResetAllFilters()"
                        class="inline-flex items-center gap-1 text-xs font-bold text-red-500 hover:text-red-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Reset Filter
                    </button>
                </div>

                <div x-show="openMobileFilter" x-collapse x-cloak class="pt-3 border-t border-abubiru/40 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                        {{-- Filter Periode (multi-select + searchable + checkbox) --}}
                        @php
                            $periodeOptions = $periods->map(fn($p) => [
                                'value' => (string) $p->tahun,
                                'label' => 'Tahun ' . $p->tahun . ($p->nama ? ' (' . $p->nama . ')' : ''),
                            ])->values();
                        @endphp
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="(function() {
                                const opts = @js($periodeOptions);
                                return {
                                    isOpen: false,
                                    search: '',
                                    opts,
                                    get sel() {
                                        const v = $wire.filterTahun;
                                        return Array.isArray(v) ? v : (v ? [String(v)] : []);
                                    },
                                    get triggerLabel() {
                                        if (this.sel.length === 0) return 'Semua Periode';
                                        if (this.sel.length === 1) {
                                            const f = opts.find(o => o.value === this.sel[0]);
                                            return f ? f.label : 'Semua Periode';
                                        }
                                        return this.sel.length + ' periode dipilih';
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
                                        $wire.set('filterTahun', cur);
                                    },
                                    clearAll() {
                                        $wire.set('filterTahun', []);
                                        this.search = '';
                                    }
                                };
                            })()">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Periode</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full"
                                    wire:loading.remove wire:target="filterTahun">
                                    {{ !empty($filterTahun) ? count((array) $filterTahun) . ' dipilih' : 'Semua' }}
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
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Periode</span>
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

                        {{-- Filter Status (multi-select + searchable + checkbox) --}}
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="{
                                isOpen: false,
                                search: '',
                                opts: [
                                    { value: 'belum_dimulai',   label: 'Belum Dimulai' },
                                    { value: 'draft_pengisian', label: 'Draft Pengisian' },
                                    { value: 'menunggu_verif',  label: 'Menunggu Verif' },
                                    { value: 'draft_verif',     label: 'Draft Verif' },
                                    { value: 'dikembalikan',    label: 'Dikembalikan' },
                                    { value: 'menunggu_eval',   label: 'Menunggu Eval' },
                                    { value: 'draft_penilaian', label: 'Draft Penilaian' },
                                    { value: 'lolos',           label: 'Lolos' },
                                    { value: 'gugur',           label: 'Gugur' },
                                ],
                                get sel() {
                                    const v = $wire.filterStatus;
                                    return Array.isArray(v) ? v : (v ? [String(v)] : []);
                                },
                                get triggerLabel() {
                                    if (this.sel.length === 0) return 'Semua Status';
                                    if (this.sel.length === 1) {
                                        const f = this.opts.find(o => o.value === this.sel[0]);
                                        return f ? f.label : 'Semua Status';
                                    }
                                    return this.sel.length + ' status dipilih';
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
                                    $wire.set('filterStatus', cur);
                                },
                                clearAll() {
                                    $wire.set('filterStatus', []);
                                    this.search = '';
                                }
                            }">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Status</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full"
                                    wire:loading.remove wire:target="filterStatus">
                                    {{ !empty($filterStatus) ? count((array) $filterStatus) . ' dipilih' : 'Semua' }}
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
                                    <input type="text" placeholder="Cari status..."
                                        x-model="search"
                                        x-ref="statusSearch"
                                        x-init="$watch('isOpen', v => v && $nextTick(() => $refs.statusSearch?.focus()))"
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Status</span>
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

                        {{-- Filter Satker (multi-select + searchable + checkbox) --}}
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs sm:col-span-2"
                            x-data="(function() {
                                const satkers = @js($satkers->sortBy('kode_satker')->map(fn($s) => ['value' => $s->kode_satker, 'label' => '(' . $s->kode_satker . ') ' . $s->nama_satker])->values());
                                return {
                                    isOpen: false,
                                    search: '',
                                    satkers,
                                    get sel() {
                                        const v = $wire.filterKodeSatker;
                                        return Array.isArray(v) ? v : (v ? [String(v)] : []);
                                    },
                                    get triggerLabel() {
                                        if (this.sel.length === 0) return 'Semua Satker';
                                        if (this.sel.length === 1) {
                                            const f = satkers.find(s => s.value === this.sel[0]);
                                            return f ? f.label : 'Semua Satker';
                                        }
                                        return this.sel.length + ' satker dipilih';
                                    },
                                    get filtered() {
                                        const q = this.search.toLowerCase().trim();
                                        if (!q) return this.satkers;
                                        return this.satkers.filter(s => s.label.toLowerCase().includes(q));
                                    },
                                    toggle(val) {
                                        let cur = [...this.sel];
                                        const i = cur.indexOf(val);
                                        if (i >= 0) cur.splice(i, 1); else cur.push(val);
                                        $wire.set('filterKodeSatker', cur);
                                    },
                                    clearAll() {
                                        $wire.set('filterKodeSatker', []);
                                        this.search = '';
                                    }
                                };
                            })()">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Satker</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full"
                                    wire:loading.remove wire:target="filterKodeSatker">
                                    {{ !empty($filterKodeSatker) ? count((array) $filterKodeSatker) . ' dipilih' : 'Semua' }}
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
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Satker</span>
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
            @forelse ($submissions as $sub)
                @php
                    $statusVal = 'belum_dimulai';
                    if ($sub->status === 0) {
                        $answeredCount = $sub->answers
                            ->filter(
                                fn($a) => !is_null($a->answer) &&
                                    ((int) $a->answer === 0 ||
                                        (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? '')))),
                            )
                            ->count();
                        $statusVal = $answeredCount > 0 ? 'draft_pengisian' : 'belum_dimulai';
                    } elseif ($sub->status === 1) {
                        $statusVal = !empty($sub->verified_by) ? 'draft_verif' : 'menunggu_verif';
                    } elseif ($sub->status === 2) {
                        $statusVal = 'dikembalikan';
                    } elseif ($sub->status === 3) {
                        // Menunggu evaluasi: cek apakah evaluator sudah mulai menilai sebagian
                        $hasDraftScore = $sub->answers->whereNotNull('answer_score')->count() > 0;
                        $statusVal = $hasDraftScore ? 'draft_penilaian' : 'menunggu_eval';
                    } elseif ($sub->status === 4) {
                        // Sudah pernah disimpan draft (status 4 = evaluasi parsial yang tersimpan)
                        $statusVal = 'draft_penilaian';
                    } elseif (in_array($sub->status, [5, 6, 7])) {
                        $statusVal = 'lolos';
                    } elseif ($sub->status === 8) {
                        $statusVal = 'gugur';
                    }

                    $isDraftPenilaian = $statusVal === 'draft_penilaian';
                    $canReview = in_array($sub->status, [3, 4, 5, 6, 7, 8]);
                @endphp
                <div wire:key="monkues-card-{{ $sub->id_submission }}" class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="font-bold text-biru1 text-sm leading-snug">
                            {{ $sub->user->masterSatker->nama_satker ?? 'BPS Kab/Kota' }}</div>
                        <span
                            class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold shrink-0">
                            {{ date('Y', strtotime($sub->submitted_at ?? ($period->periode_start ?? now()))) }}
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-2 pt-2 border-t border-abubiru/30 text-xs font-semibold text-biru3">
                        <div class="flex justify-between items-center gap-2">
                            <span>Kode Satker:</span>
                            <span class="text-biru1 font-mono">{{ $sub->user->kode_satker }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <span>Pengisi:</span>
                            <span class="text-biru1 text-right">{{ $sub->user->nama_user }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="shrink-0">Petugas Evaluator:</span>
                            <span class="text-biru1 text-right leading-snug">
                                @if ($sub->kuesionerEvaluators->isNotEmpty())
                                    {{ $sub->kuesionerEvaluators->pluck('nama_user')->implode(', ') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span>Progress SAQ:</span>
                            <div class="w-28 shrink-0">
                                <x-ui.progress-bar :value="$sub->progress_percent" color="success" />
                            </div>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <span>Status:</span>
                            <x-ui.status-badge :status="$statusVal" />
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <span>Nilai SAQ:</span>
                            <span class="font-bold text-biru1 text-sm">
                                @if ($isDraftPenilaian)
                                    <span class="text-biru4 font-medium text-xs italic">— draft —</span>
                                @else
                                    {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-abubiru/30">
                        @php
                            $isSuperEval = Auth::user()?->role?->nama_role === 'super_evaluator';
                            $isUserAssigned =
                                $sub->kuesionerEvaluators->contains('id_user', Auth::id()) ||
                                $sub->evaluated_by == Auth::id() ||
                                $sub->evaluators->contains('id_user', Auth::id());

                            $existingEvaluatorIds = [];
                            $evalUsers = $sub->kuesionerEvaluators;
                            if ($evalUsers->isNotEmpty()) {
                                $existingEvaluatorIds = $evalUsers
                                    ->pluck('id_user')
                                    ->map(fn($id) => (int) $id)
                                    ->toArray();
                            } else {
                                $rawEval = $sub->evaluated_by;
                                if (is_array($rawEval)) {
                                    foreach ($rawEval as $eItem) {
                                        if (is_array($eItem) && isset($eItem['id'])) {
                                            $existingEvaluatorIds[] = (int) $eItem['id'];
                                        } elseif (is_numeric($eItem)) {
                                            $existingEvaluatorIds[] = (int) $eItem;
                                        }
                                    }
                                } elseif (is_numeric($rawEval)) {
                                    $existingEvaluatorIds[] = (int) $rawEval;
                                }
                            }
                            $existingEvaluatorIds = array_values(array_unique(array_filter($existingEvaluatorIds)));
                            $isAlreadyAssigned = !empty($existingEvaluatorIds);
                            $canEdit = $isSuperEval && $isAlreadyAssigned && in_array($sub->status, [3, 4]);
                        @endphp

                        @if ($canEdit)
                            <button type="button"
                                @click="selectedSubmissionIds = ['{{ $sub->id_submission }}']; evaluatorIds = {{ json_encode($existingEvaluatorIds) }}; isEditScheduled = true; modalTitle = 'Edit Evaluator Kuesioner'; selectedSatkerName = '{{ $sub->user->masterSatker ? '(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker : 'BPS Kab/Kota' }}'; showJadwalModal = true"
                                class="flex items-center gap-1.5 rounded-lg bg-oren2 border border-oren2/30 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition cursor-pointer"
                                title="Edit Evaluator">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit Evaluator</span>
                            </button>
                        @endif

                        @if ($canReview && ($isSuperEval || $isUserAssigned))
                            <a href="{{ route('evaluator.review-kues', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                wire:navigate.hover
                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                title="Nilai Kuesioner">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Nilai</span>
                            </a>
                        @elseif(!$isSuperEval && !$isUserAssigned)
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @endif
                    </div>
                </div>
            @empty
                <div wire:key="monkues-card-empty"
                    class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                    Belum ada pengisian kuesioner masuk untuk periode ini.
                </div>
            @endforelse
        </x-slot>

        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                <tr>
                    {{-- TAHUN / PERIODE with Excel Filter --}}
                    <th rowspan="2" class="px-6 py-4 text-left border-r border-abubiru/20">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1 cursor-pointer"
                                onclick="monKuesToggleFilter('tahun')">
                                <span>PERIODE</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monkues-filter-tahun"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-48 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="monKuesFilterList('tahun', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monkues-all-tahun"
                                        onchange="monKuesToggleAll('tahun', this)" class="accent-biru2">
                                    Semua Periode
                                </label>
                                <div id="monkues-list-tahun" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($periods as $p)
                                        <label
                                            class="monkues-item-tahun flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($p->tahun) }}">
                                            <input type="checkbox" class="monkues-cb-tahun accent-biru2"
                                                value="{{ $p->tahun }}"
                                                {{ in_array((string) $p->tahun, (array) $filterTahun) ? 'checked' : '' }}>
                                            {{ $p->nama ?? $p->tahun }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monKuesCloseFilter('tahun')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monKuesApplyFilter('tahun')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- KODE SATKER with Excel Filter --}}
                    <th rowspan="2" class="px-6 py-4 text-center border-r border-abubiru/20">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer"
                                onclick="monKuesToggleFilter('kode_satker')">
                                <span>KODE SATKER</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monkues-filter-kode_satker"
                                class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="monKuesFilterList('kode_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monkues-all-kode_satker"
                                        onchange="monKuesToggleAll('kode_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monkues-list-kode_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label
                                            class="monkues-item-kode_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($sat->kode_satker) }}">
                                            <input type="checkbox" class="monkues-cb-kode_satker accent-biru2"
                                                value="{{ $sat->kode_satker }}" checked>
                                            {{ $sat->kode_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monKuesCloseFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monKuesApplyFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- NAMA SATKER with Excel Filter --}}
                    <th rowspan="2" class="px-6 py-4 text-left border-r border-abubiru/20">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer"
                                onclick="monKuesToggleFilter('nama_satker')">
                                <span>NAMA SATKER</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monkues-filter-nama_satker"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="monKuesFilterList('nama_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monkues-all-nama_satker"
                                        onchange="monKuesToggleAll('nama_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monkues-list-nama_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label
                                            class="monkues-item-nama_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($sat->nama_satker) }}">
                                            <input type="checkbox" class="monkues-cb-nama_satker accent-biru2"
                                                value="{{ strtolower($sat->nama_satker) }}" checked>
                                            {{ $sat->nama_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monKuesCloseFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monKuesApplyFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    <th rowspan="2" class="px-6 py-4 text-left border-r border-abubiru/20">PENGISI</th>

                    {{-- PETUGAS with Excel Filter --}}
                    <th rowspan="2" class="px-6 py-4 text-left border-r border-abubiru/20">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer"
                                onclick="monKuesToggleFilter('evaluator')">
                                <span>PETUGAS</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monkues-filter-evaluator"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari nama evaluator..."
                                    oninput="monKuesFilterList('evaluator', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monkues-all-evaluator"
                                        onchange="monKuesToggleAll('evaluator', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monkues-list-evaluator" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($allEvaluators as $ev)
                                        <label
                                            class="monkues-item-evaluator flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ strtolower($ev->nama_user) }}">
                                            <input type="checkbox" class="monkues-cb-evaluator accent-biru2"
                                                value="{{ strtolower($ev->nama_user) }}" checked>
                                            {{ $ev->nama_user }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monKuesCloseFilter('evaluator')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monKuesApplyFilter('evaluator')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    <th colspan="6"
                        class="px-4 py-3 text-center border-b border-r border-abubiru/20">Nilai Per
                        Indikator</th>
                    <th rowspan="2" class="px-6 py-4 text-center border-r border-abubiru/20">Progress</th>

                    {{-- STATUS with Excel Filter --}}
                    <th rowspan="2" class="px-6 py-4 text-center border-r border-abubiru/20">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer"
                                onclick="monKuesToggleFilter('status')">
                                <span>STATUS</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monkues-filter-status"
                                class="hidden absolute right-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-48 p-3 text-biru1 font-medium normal-case text-left">
                                <label
                                    class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monkues-all-status"
                                        onchange="monKuesToggleAll('status', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monkues-list-status" class="max-h-52 overflow-y-auto space-y-1">
                                    @foreach ([
                                        'belum_dimulai'   => 'Belum Dimulai',
                                        'draft_pengisian' => 'Draft Pengisian',
                                        'menunggu_verif'  => 'Menunggu Verif',
                                        'draft_verif'     => 'Draft Verif',
                                        'dikembalikan'    => 'Dikembalikan',
                                        'menunggu_eval'   => 'Menunggu Eval',
                                        'draft_penilaian' => 'Draft Penilaian',
                                        'lolos'           => 'Lolos',
                                        'gugur'           => 'Gugur',
                                    ] as $sKey => $sLabel)
                                        <label
                                            class="monkues-item-status flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs"
                                            data-value="{{ $sKey }}">
                                            <input type="checkbox" class="monkues-cb-status accent-biru2"
                                                value="{{ $sKey }}" checked>
                                            {{ $sLabel }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monKuesCloseFilter('status')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monKuesApplyFilter('status')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    <th rowspan="2" class="px-6 py-4 text-center border-r border-abubiru/20">Nilai</th>
                    <th rowspan="2" class="px-6 py-4 text-center">Aksi</th>
                </tr>
                <tr>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Kualitas</th>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Jenis</th>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Pelayanan</th>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Sarpras</th>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Digitalisasi</th>
                    <th class="px-3 py-2 text-center text-xs border-r border-abubiru/20 font-semibold normal-case">
                        Komitmen</th>
                </tr>
            </thead>
        </x-slot>

        @forelse ($submissions as $sub)
            @php
                $statusVal = 'belum_dimulai';
                if ($sub->status === 0) {
                    $answeredCount = $sub->answers
                        ->filter(
                            fn($a) => !is_null($a->answer) &&
                                ((int) $a->answer === 0 ||
                                    (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? '')))),
                        )
                        ->count();
                    $statusVal = $answeredCount > 0 ? 'draft_pengisian' : 'belum_dimulai';
                } elseif ($sub->status === 1) {
                    $statusVal = !empty($sub->verified_by) ? 'draft_verif' : 'menunggu_verif';
                } elseif ($sub->status === 2) {
                    $statusVal = 'dikembalikan';
                } elseif ($sub->status === 3) {
                    // Menunggu evaluasi: cek apakah evaluator sudah mulai menilai sebagian
                    $hasDraftScore = $sub->answers->whereNotNull('answer_score')->count() > 0;
                    $statusVal = $hasDraftScore ? 'draft_penilaian' : 'menunggu_eval';
                } elseif ($sub->status === 4) {
                    // Sudah pernah disimpan draft (status 4 = evaluasi parsial yang tersimpan)
                    $statusVal = 'draft_penilaian';
                } elseif (in_array($sub->status, [5, 6, 7])) {
                    $statusVal = 'lolos';
                } elseif ($sub->status === 8) {
                    $statusVal = 'gugur';
                }

                $isDraftPenilaian = $statusVal === 'draft_penilaian';
                $canReview = in_array($sub->status, [3, 4, 5, 6, 7, 8]);
            @endphp
            <tr wire:key="monkues-row-{{ $sub->id_submission }}" class="border-t border-abubiru/30 hover:bg-abubiru/10 transition">
                <td class="px-6 py-4 border-r border-abubiru/20 text-biru1 font-medium text-sm">
                    {{ date('Y', strtotime($sub->submitted_at ?? ($period->periode_start ?? now()))) }}</td>
                <td class="px-6 py-4 border-r border-abubiru/20 text-center">
                    <span class="font-extrabold text-biru2 text-sm">{{ $sub->user->kode_satker }}</span>
                </td>
                <td class="px-6 py-4 border-r border-abubiru/20">
                    <div class="font-bold text-biru1 text-sm">
                        {{ $sub->user->masterSatker->nama_satker ?? 'BPS Kab/Kota' }}</div>
                </td>
                <td class="px-6 py-4 border-r border-abubiru/20 text-biru2 font-medium text-sm">
                    {{ $sub->user->nama_user }}</td>
                <td class="px-6 py-4 border-r border-abubiru/20 text-biru2 font-medium text-sm">
                    @if ($sub->kuesionerEvaluators->isNotEmpty())
                        {{ $sub->kuesionerEvaluators->pluck('nama_user')->implode(', ') }}
                    @elseif ($sub->evaluators->isNotEmpty())
                        {{ $sub->evaluators->pluck('nama_user')->implode(', ') }}
                    @else
                        {{ $sub->evaluator->nama_user ?? '-' }}
                    @endif
                </td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[1] ?? 0, 2) }}</td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[2] ?? 0, 2) }}</td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[3] ?? 0, 2) }}</td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[4] ?? 0, 2) }}</td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[5] ?? 0, 2) }}</td>
                <td class="px-3 py-4 text-center border-r border-abubiru/20 font-bold text-biru1 text-sm">
                    {{ number_format($sub->indicator_scores[6] ?? 0, 2) }}</td>
                <td class="px-6 py-4 border-r border-abubiru/20">
                    <x-ui.progress-bar :value="$sub->progress_percent" color="success" />
                </td>
                <td class="px-6 py-4 border-r border-abubiru/20 text-center">
                    <x-ui.status-badge :status="$statusVal" />
                </td>
                <td class="px-6 py-4 border-r border-abubiru/20 text-center font-bold text-biru1 text-sm">
                    @if ($isDraftPenilaian)
                        <span class="text-biru4 font-medium text-xs italic">— draft —</span>
                    @else
                        {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        @php
                            $isSuperEval = Auth::user()?->role?->nama_role === 'super_evaluator';
                            $isUserAssigned =
                                $sub->kuesionerEvaluators->contains('id_user', Auth::id()) ||
                                $sub->evaluated_by == Auth::id() ||
                                $sub->evaluators->contains('id_user', Auth::id());

                            $existingEvaluatorIds = [];
                            $evalUsers = $sub->kuesionerEvaluators;
                            if ($evalUsers->isNotEmpty()) {
                                $existingEvaluatorIds = $evalUsers
                                    ->pluck('id_user')
                                    ->map(fn($id) => (int) $id)
                                    ->toArray();
                            } else {
                                $rawEval = $sub->evaluated_by;
                                if (is_array($rawEval)) {
                                    foreach ($rawEval as $eItem) {
                                        if (is_array($eItem) && isset($eItem['id'])) {
                                            $existingEvaluatorIds[] = (int) $eItem['id'];
                                        } elseif (is_numeric($eItem)) {
                                            $existingEvaluatorIds[] = (int) $eItem;
                                        }
                                    }
                                } elseif (is_numeric($rawEval)) {
                                    $existingEvaluatorIds[] = (int) $rawEval;
                                }
                            }
                            $existingEvaluatorIds = array_values(array_unique(array_filter($existingEvaluatorIds)));
                            $isAlreadyAssigned = !empty($existingEvaluatorIds);
                            $canEdit = $isSuperEval && $isAlreadyAssigned && in_array($sub->status, [3, 4]);
                        @endphp

                        @if ($canEdit)
                            <button type="button"
                                @click="selectedSubmissionIds = ['{{ $sub->id_submission }}']; evaluatorIds = {{ json_encode($existingEvaluatorIds) }}; isEditScheduled = true; modalTitle = 'Edit Evaluator Kuesioner'; selectedSatkerName = '{{ $sub->user->masterSatker ? '(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker : 'BPS Kab/Kota' }}'; showJadwalModal = true"
                                class="inline-flex items-center justify-center rounded-lg bg-oren2 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition shadow-xs cursor-pointer"
                                title="Edit Evaluator">
                                Edit
                            </button>
                        @endif

                        @if ($canReview && ($isSuperEval || $isUserAssigned))
                            @php
                                $reviewRoute = match(Auth::user()?->role?->nama_role) {
                                    'super_evaluator' => 'super-evaluator.review-kues',
                                    'penilai_eksternal' => 'penilai-eksternal.review-kues',
                                    default => 'evaluator.review-kues',
                                };
                            @endphp
                            <a href="{{ route($reviewRoute, ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                wire:navigate.hover
                                class="inline-flex items-center justify-center rounded-lg bg-biru2 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition shadow-xs"
                                title="Nilai Kuesioner">
                                Nilai
                            </a>
                        @elseif(!$isSuperEval && !$isUserAssigned)
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr wire:key="monkues-row-empty">
                <td colspan="15" class="px-6 py-8 text-center text-biru3 font-medium bg-white">
                    Belum ada pengisian kuesioner masuk untuk periode ini atau data tidak ditemukan.
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <div
                class="flex flex-col lg:flex-row items-center justify-between gap-4 w-full text-sm text-biru3 font-semibold bg-white">
                <div
                    class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 text-center sm:text-left w-full lg:w-auto">
                    <div class="whitespace-nowrap">Menampilkan {{ $submissions->firstItem() ?? 0 }} -
                        {{ $submissions->lastItem() ?? 0 }} dari {{ $submissions->total() }} data</div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-bold text-biru3 uppercase whitespace-nowrap">Tampilkan:</span>
                        <select wire:model.live="perPage"
                            class="rounded-lg border border-abubiru bg-white px-2.5 py-1 text-xs text-biru1 font-bold focus:outline-none cursor-pointer">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-1.5 w-full lg:w-auto flex-wrap">
                    @if ($submissions->onFirstPage())
                        <span
                            class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage"
                            class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @endif

                    @php
                        $startPage = max(1, $submissions->currentPage() - 2);
                        $endPage = min($submissions->lastPage(), $submissions->currentPage() + 2);
                    @endphp
                    @for ($p = $startPage; $p <= $endPage; $p++)
                        @if ($p == $submissions->currentPage())
                            <span
                                class="w-9 h-9 rounded-lg bg-biru1 text-white font-bold flex items-center justify-center shadow-md">{{ $p }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $p }})"
                                class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru3 font-bold hover:bg-putihbg flex items-center justify-center transition cursor-pointer">{{ $p }}</button>
                        @endif
                    @endfor

                    @if ($submissions->hasMorePages())
                        <button type="button" wire:click="nextPage"
                            class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <span
                            class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-tables.monitoring-table>
</div>

<script>
    const _monKuesWireMap = {
        'tahun': 'setFilterTahun',
        'kode_satker': 'setFilterKodeSatker',
        'nama_satker': 'setFilterNamaSatker',
        'evaluator': 'setFilterEvaluator',
        'status': 'setFilterStatus',
    };

    function monKuesToggleFilter(col) {
        toggleGenericTableFilter('monkues', col);
    }

    function monKuesCloseFilter(col) {
        closeGenericTableFilter('monkues', col);
    }

    function monKuesToggleAll(col, cb) {
        toggleGenericTableAll('monkues', col, cb);
    }

    function monKuesFilterList(col, q) {
        filterGenericTableList('monkues', col, q);
    }

    function monKuesApplyFilter(col, btnEl) {
        applyGenericTableFilter('monkues', col, _monKuesWireMap, @this);
    }

    function monKuesResetAllFilters() {
        document.querySelectorAll(
            '.monkues-cb-tahun, .monkues-cb-kode_satker, .monkues-cb-nama_satker, .monkues-cb-evaluator, .monkues-cb-status'
        ).forEach(cb => cb.checked = true);
        document.querySelectorAll(
            '#monkues-all-tahun, #monkues-all-kode_satker, #monkues-all-nama_satker, #monkues-all-evaluator, #monkues-all-status'
        ).forEach(cb => cb.checked = true);
    }

    function toggleAllKuesKota(el) {
        toggleAllModalItems(el, 'kues-kota-checkbox');
    }

    function filterKuesKotaList(val) {
        filterModalItems(val, 'kues-kota-item');
    }

    function toggleAllKuesStatus(el) {
        toggleAllModalItems(el, 'kues-status-checkbox');
    }

    function handleExportKuesSubmit(event, form) {
        handleExportSubmit(event, form, 'modal-export-kues');
    }
</script>
