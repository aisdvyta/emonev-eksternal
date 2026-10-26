<div>
    @php
        $userRole = Auth::user()?->role?->nama_role;
        $isExternal = $userRole === 'penilai_eksternal';
        $routePrefix = match($userRole) {
            'super_evaluator' => 'super-evaluator',
            'penilai_eksternal' => 'penilai-eksternal',
            default => 'evaluator',
        };
    @endphp

    {{-- Modal Filter Export --}}
    @include('livewire.evaluasi.partials.modal-export')

    <!-- Page Header -->
    <x-ui.page-header title="Monitoring Penilaian Akhir"
        description="Kabupaten/Kota se Jawa Timur - Tahun {{ $period->tahun }}" :breadcrumb="[
            'Monitoring Kegiatan' => '#',
            'Penilaian Akhir' => route($routePrefix . '.penilaian'),
        ]">
        <x-slot name="actions">
            <div class="flex items-center justify-between sm:justify-end gap-2 sm:gap-3 w-full sm:w-auto ml-auto"
                x-data="{
                    isOpen: false,
                    search: '',
                    periods: {{ json_encode($periods->map(fn($p) => [
                        'value' => (string) $p->id_periode,
                        'label' => $p->nama ?? ('Tahun ' . $p->tahun),
                        'tahun' => (string) $p->tahun
                    ])) }},
                    currentTahun: '{{ $period->tahun }}',
                    currentId: '{{ $period->id_periode }}',
                    get filtered() {
                        const q = this.search.toLowerCase().trim();
                        if (!q) return this.periods;
                        return this.periods.filter(p => p.label.toLowerCase().includes(q) || p.tahun.includes(q));
                    },
                    select(val, tahun) {
                        this.currentId = String(val);
                        this.currentTahun = String(tahun);
                        $wire.set('periodeId', val);
                        this.isOpen = false;
                    }
                }">
                <span class="text-xs sm:text-sm font-semibold text-biru3 shrink leading-tight text-left">Silahkan pilih periode KIP terlebih dahulu:</span>
                
                <!-- Custom Styled Single-Select Periode Dropdown (Alpine.js) -->
                <div class="relative shrink-0">
                    <button type="button" @click.stop="isOpen = !isOpen"
                        class="flex items-center gap-1.5 sm:gap-2 rounded-xl border border-abubiru bg-white hover:bg-slate-50 text-biru1 px-3 sm:px-3.5 py-1.5 sm:py-2 text-xs sm:text-sm font-extrabold shadow-sm focus:outline-none focus:ring-2 focus:ring-biru2/20 cursor-pointer transition select-none">
                        <span x-text="currentTahun" class="font-extrabold text-biru1"></span>
                        <svg class="w-4 h-4 text-biru2 shrink-0 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Popup Menu -->
                    <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        class="absolute right-0 top-full mt-1.5 bg-white border border-abubiru rounded-2xl shadow-2xl p-2 z-999 w-60 text-left">
                        
                        <div class="px-2 py-1 mb-1.5 border-b border-abubiru/40 flex items-center justify-between">
                            <span class="text-xs font-extrabold text-biru3 uppercase tracking-wider">Pilih Periode</span>
                            <span class="text-xs font-bold text-biru4">1 Periode</span>
                        </div>

                        <!-- Search input if > 3 periods -->
                        <template x-if="periods.length > 3">
                            <div class="px-1 mb-2">
                                <input type="text" placeholder="Cari periode..."
                                    x-model="search"
                                    x-ref="periodSearch"
                                    x-init="$watch('isOpen', v => v && $nextTick(() => $refs.periodSearch?.focus()))"
                                    class="w-full rounded-lg border border-abubiru px-2.5 py-1.5 text-xs text-biru1 placeholder-biru3/60 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                            </div>
                        </template>

                        <!-- Items List (Single Select Only) -->
                        <div class="max-h-52 overflow-y-auto space-y-1">
                            <template x-for="p in filtered" :key="p.value">
                                <div @click.stop="select(p.value, p.tahun)"
                                    :class="String(p.value) === String(currentId) ? 'bg-biru5/40 text-biru1 font-extrabold border border-biru5' : 'text-slate-700 font-medium hover:bg-slate-50 border border-transparent'"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs transition cursor-pointer select-none">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                            :class="String(p.value) === String(currentId) ? 'border-biru2 bg-biru2 text-white' : 'border-slate-300 bg-white'">
                                            <div x-show="String(p.value) === String(currentId)" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <span x-text="p.label" class="truncate"></span>
                                    </div>
                                    <template x-if="String(p.value) === String(currentId)">
                                        <span class="text-xs font-bold text-biru2 bg-white/80 px-2 py-0.5 rounded-full shrink-0">Aktif</span>
                                    </template>
                                </div>
                            </template>
                            <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-ui.page-header>

    <!-- Stat Cards -->
    <section class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 md:gap-6 mb-6">
        <x-ui.card-stat label="TOTAL KABKOT" value="{{ $totalCount }}" color="white" bg="bg-biru4">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="SELESAI SAQ" value="{{ $countSudahSAQ }}" color="biru2" bg="bg-biru5">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="SELESAI VISITASI" value="{{ $countSudahVisit }}" color="biru2" bg="bg-biru5">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="SELESAI WAWANCARA" value="{{ $countSudahInterview }}" color="biru2" bg="bg-biru5">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>
    </section>

    <!-- Row 1: Catatan Super Evaluator -->
    @include('livewire.evaluasi.partials.card-catatan')

    <!-- Visualisasi 50:50 (Histogram Skor SAQ per Indikator & Peta Sebaran) -->
    @include('livewire.evaluasi.partials.charts-section')

    <!-- Tabel Monitoring Penilaian Akhir -->
    <x-tables.monitoring-table title="Tabel Monitoring Penilaian Akhir" hasCards="true">
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
                    <button type="button" wire:click="resetFilters" onclick="resetPenilaianExcelFilters()"
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
                    $saq = $sub->result?->ques_score;
                    $visit = $sub->visitasi?->visit_score;
                    $interview = $sub->interview?->interview_score;
                @endphp
                @if ($satker)
                    <div wire:key="penilaian-card-{{ $sub->id_submission }}" class="bg-white rounded-2xl border border-abubiru p-4 shadow-xs space-y-3 transition">
                        <div class="flex items-start justify-between gap-2 border-b border-abubiru/30 pb-2.5">
                            <div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-biru3 uppercase">Kode: {{ $satker->kode_satker }}</span>
                                    <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.2 text-xs font-bold">
                                        {{ $sub->period->tahun ?? '-' }}
                                    </span>
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
                <div wire:key="penilaian-card-empty" class="p-6 text-center text-sm font-medium text-biru3 bg-white rounded-xl border border-abubiru">
                    Belum ada data penilaian akhir.
                </div>
            @endforelse
        </x-slot>
        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-widest text-biru4 border-b border-abubiru">
                <tr>
                    <th class="px-8 py-5 text-left">NO</th>

                    {{-- PERIODE with Excel Filter --}}
                    <x-tables.excel-filter column="periode" title="PERIODE" :items="$periods" valueField="id_periode" labelField="tahun" align="center" />

                    {{-- KODE SATKER with Excel Filter --}}
                    <x-tables.excel-filter column="kode_satker" title="KODE SATKER" :items="$satkers" valueField="kode_satker" labelField="kode_satker" align="center" />

                    {{-- NAMA KABUPATEN/KOTA with Excel Filter --}}
                    <x-tables.excel-filter column="nama_satker" title="NAMA KABUPATEN/KOTA" :items="$satkers" valueField="nama_satker" labelField="nama_satker" align="left" popupPosition="left-0" popupWidth="w-64" />

                    @foreach($stageOrder as $stageKey)
                        @if($stageKey === 'saq')
                            <th class="px-8 py-5 text-center">NILAI SAQ ({{ (float) ($pembobotan->weight_saq ?? 30) }}%)</th>
                        @elseif($stageKey === 'visitasi')
                            <th class="px-8 py-5 text-center">NILAI VISITASI ({{ (float) ($pembobotan->weight_visitasi ?? 40) }}%)</th>
                        @elseif($stageKey === 'wawancara')
                            <th class="px-8 py-5 text-center">NILAI WAWANCARA ({{ (float) ($pembobotan->weight_wawancara ?? 30) }}%)</th>
                        @endif
                    @endforeach
                    <th class="px-8 py-5 text-center">
                        <button type="button" wire:click="sortBy('calculated_final')"
                            class="inline-flex items-center gap-1.5 cursor-pointer hover:text-biru1 transition group">
                            <span>NILAI MONEV KIP</span>
                            <span class="flex flex-col items-center -space-y-1">
                                <svg class="w-3 h-3 transition {{ $sortField === 'calculated_final' && $sortDirection === 'asc' ? 'text-biru1' : 'text-biru4/40 group-hover:text-biru4' }}"
                                    fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7 14l5-5 5 5z" />
                                </svg>
                                <svg class="w-3 h-3 transition {{ $sortField === 'calculated_final' && $sortDirection === 'desc' ? 'text-biru1' : 'text-biru4/40 group-hover:text-biru4' }}"
                                    fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7 10l5 5 5-5z" />
                                </svg>
                            </span>
                        </button>
                    </th>

                    {{-- SKALA PENILAIAN with Excel Filter --}}
                    <x-tables.excel-filter column="skala_penilaian" title="SKALA PENILAIAN" :items="[
                        'INFORMATIF' => 'Informatif',
                        'MENUJU INFORMATIF' => 'Menuju Informatif',
                        'CUKUP INFORMATIF' => 'Cukup Informatif',
                        'KURANG INFORMATIF' => 'Kurang Informatif',
                        'TIDAK INFORMATIF' => 'Tidak Informatif',
                        'BELUM DINILAI' => 'Belum Dinilai',
                    ]" :searchable="false" align="center" popupPosition="right-0" />
                </tr>
            </thead>
        </x-slot>

        <x-slot name="headerCenter">
            <!-- Live Search + Unduh (mobile: side by side) -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1" wire:key="penilaian-search-wrapper">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        id="penilaian-table-search"
                        wire:key="penilaian-table-search"
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
                <button type="button" wire:click="resetFilters" onclick="resetPenilaianExcelFilters()"
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
            @endphp
            @if ($satker)
                <tr wire:key="penilaian-row-{{ $sub->id_submission }}" class="border-t border-abubiru/30 transition text-sm cursor-default even:bg-putihbg">
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
                        <span class="font-extrabold text-biru2 text-sm">{{ $satker->nama_satker }}</span>
                    </td>
                    @foreach($stageOrder as $stageKey)
                        @if($stageKey === 'saq')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @if ($sub->id_submission !== null)
                                    @if (in_array(Auth::user()?->role?->nama_role, ['evaluator', 'super_evaluator']))
                                        <a href="{{ route('evaluator.review-kues', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                            wire:navigate.hover onclick="event.stopPropagation()"
                                            class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                            {{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}
                                        </a>
                                    @else
                                        <span
                                            class="text-biru1 font-bold">{{ $sub->result?->ques_score !== null ? number_format($sub->result->ques_score, 2) : '-' }}</span>
                                    @endif
                                @else
                                    <span class="text-biru4">-</span>
                                @endif
                            </td>
                        @elseif($stageKey === 'visitasi')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @php
                                    $visitEnd = $sub->result?->period?->visit_end ? \Carbon\Carbon::parse($sub->result->period->visit_end)->endOfDay() : null;
                                    $visitVisible = !$isExternal || !$visitEnd || now()->greaterThan($visitEnd);
                                @endphp
                                @if ($sub->visitasi && $visitVisible)
                                    @if (in_array(Auth::user()?->role?->nama_role, ['evaluator', 'super_evaluator']))
                                        <a href="{{ route('evaluator.review-visitasi', \App\Helpers\SecureId::encrypt($sub->visitasi->id_visit)) }}"
                                            wire:navigate.hover onclick="event.stopPropagation()"
                                            class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                            {{ $sub->visitasi->visit_score !== null ? number_format($sub->visitasi->visit_score, 2) : '-' }}
                                        </a>
                                    @else
                                        <span
                                            class="text-biru1 font-bold">{{ $sub->visitasi->visit_score !== null ? number_format($sub->visitasi->visit_score, 2) : '-' }}</span>
                                    @endif
                                @elseif($isExternal && !$visitVisible)
                                    <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                                @else
                                    <span class="text-biru4">-</span>
                                @endif
                            </td>
                        @elseif($stageKey === 'wawancara')
                            <td class="px-8 py-5 text-center text-base font-bold">
                                @php
                                    $interviewEnd = $sub->result?->period?->interview_end ? \Carbon\Carbon::parse($sub->result->period->interview_end)->endOfDay() : null;
                                    $interviewVisible = !$isExternal || !$interviewEnd || now()->greaterThan($interviewEnd);
                                @endphp
                                @if ($sub->interview && $interviewVisible)
                                    @if (in_array(Auth::user()?->role?->nama_role, ['evaluator', 'super_evaluator']))
                                        <a href="{{ route('evaluator.review-wawancara', \App\Helpers\SecureId::encrypt($sub->interview->id_interview)) }}"
                                            wire:navigate.hover onclick="event.stopPropagation()"
                                            class="text-biru2 hover:text-biru1 font-bold hover:underline transition">
                                            {{ $sub->interview->interview_score !== null ? number_format($sub->interview->interview_score, 2) : '-' }}
                                        </a>
                                    @else
                                        <span class="text-biru1 font-bold">
                                            {{ $sub->interview->interview_score !== null ? number_format($sub->interview->interview_score, 2) : '-' }}
                                        </span>
                                    @endif
                                @elseif($isExternal && !$interviewVisible)
                                    <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                                @else
                                    <span class="text-biru4">-</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                    <td class="px-8 py-5 text-center text-biruinfo font-black text-base">
                        @if($isExternal && !($sub->is_final ?? false))
                            <span class="text-biru3/60 italic text-xs font-semibold">Pending</span>
                        @else
                            {{ number_format($sub->calculated_final, 2) }}
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if($isExternal && !($sub->is_final ?? false))
                            <span class="inline-flex rounded-lg bg-slate-100 text-slate-400 px-3 py-1.5 text-xs font-extrabold uppercase whitespace-nowrap">Pending</span>
                        @else
                            <x-ui.status-badge :status="$sub->predikat" class="uppercase! text-xs! font-extrabold! whitespace-nowrap!" />
                        @endif
                    </td>
                </tr>
            @endif
        @empty
            <tr wire:key="penilaian-row-empty" class="empty-row">
                <td colspan="9" class="px-8 py-15 text-center text-sm font-medium text-biru3">
                    Belum ada data penilaian akhir.
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <x-tables.pagination :paginator="$submissions" livewire="true" />
        </x-slot>
    </x-tables.monitoring-table>

    {{-- Script Helpers and Chart Initializers --}}
    @include('livewire.evaluasi.partials.table-scripts')
</div>
