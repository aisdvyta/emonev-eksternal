@php
    $userRole = Auth::user()?->role?->nama_role;
    $isSuperEval = $userRole === 'super_evaluator';
    $isExternal = $userRole === 'penilai_eksternal';
    $routePrefix = $isSuperEval ? 'super-evaluator' : ($isExternal ? 'penilai-eksternal' : 'evaluator');
@endphp
<div data-table-prefix="monvis">
    @if (!$isExternal)
    {{-- Modal Filter Export Visitasi --}}
    <div id="modal-export-visitasi" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-7 py-5 bg-biru2 text-white flex items-center justify-between shrink-0 text-left">
                <div>
                    <h3 class="font-bold text-base">Unduh Rekap Visitasi Excel</h3>
                    <p class="text-white/70 text-xs mt-0.5">Pilih filter sebelum mengunduh</p>
                </div>
                <button onclick="document.getElementById('modal-export-visitasi').classList.add('hidden')"
                    class="text-white/70 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-export-visitasi" method="GET" action="{{ route($routePrefix . '.monitor-visitasi.export') }}" onsubmit="handleExportVisitasiSubmit(event, this)"
                class="flex flex-col flex-1 min-h-0 text-left">
                <div class="p-7 space-y-6 overflow-y-auto flex-1">
                    <input type="hidden" name="id_periode" value="{{ $periodeId }}">

                    {{-- Filter Kabupaten/Kota --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-biru4">Kabupaten / Kota</label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                                <input type="checkbox" id="check-all-visit-kota" onchange="toggleAllVisitKota(this)"
                                    class="w-3.5 h-3.5 rounded accent-biru2" checked>
                                Pilih Semua
                            </label>
                        </div>
                        <!-- Search kota -->
                        <div class="relative mb-2">
                            <input type="text" id="search-visit-kota" placeholder="Cari kab/kota..."
                                oninput="filterVisitKotaList(this.value)"
                                class="w-full rounded-lg border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-sm text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                            <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <!-- List kota -->
                        <div id="list-visit-kota"
                            class="border border-abubiru rounded-xl bg-putihbg max-h-60 overflow-y-auto px-3 py-2 space-y-1">
                            @foreach ($satkers as $satker)
                                <label
                                    class="visit-kota-item flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition"
                                    data-nama="{{ strtolower($satker->nama_satker) }}">
                                    <input type="checkbox" name="kota[]"
                                        value="{{ $satker->nama_satker }}"
                                        class="visit-kota-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                    <span
                                        class="text-sm text-biru1 font-medium">{{ $satker->nama_satker }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-7 py-5 border-t border-abubiru shrink-0">
                    <button type="button" onclick="document.getElementById('modal-export-visitasi').classList.add('hidden')"
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
    @endif

    <!-- Stat Cards -->
    <section class="grid grid-cols-3 gap-2.5 sm:gap-4 md:gap-6 mb-6">
        <x-ui.card-stat label="TOTAL KABKOT LOLOS" value="{{ $totalLolos }}" color="primary">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="SUDAH DIKUNJUNGI" value="{{ $visitedCount }}" color="success">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>

        <x-ui.card-stat label="BELUM DIKUNJUNGI" value="{{ $notVisitedCount }}" color="info">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </x-slot>
        </x-ui.card-stat>
    </section>

    <x-tables.monitoring-table title="Daftar Kabupaten & Kota" hasCards="true">
        <x-slot name="headerCenter">
            <!-- Search + Unduh Excel (mobile: side by side) -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1" wire:key="monvis-search-wrapper">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        id="monvis-search-input"
                        wire:key="monvis-search-input"
                        placeholder="Cari Kab/Kota..."
                        class="w-full rounded-lg border border-white/30 bg-white/20 shadow-sm pl-9 pr-4 py-2 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/50 transition">
                    <div class="absolute left-3 top-2.5 text-white/70">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <!-- Unduh Excel: mobile only -->
                @if (!$isExternal)
                    <button type="button"
                        onclick="document.getElementById('modal-export-visitasi').classList.remove('hidden')"
                        class="md:hidden inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-3 py-2 text-xs font-bold shadow-md transition cursor-pointer shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Excel</span>
                    </button>
                @endif
            </div>
        </x-slot>

        <x-slot name="headerActions">
            <div class="flex items-center gap-3">
                <!-- Unduh Excel: desktop only -->
                @if (!$isExternal)
                    <button type="button"
                       onclick="document.getElementById('modal-export-visitasi').classList.remove('hidden')"
                       class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Unduh Excel</span>
                    </button>
                @endif
                <!-- Reset Filter: desktop only -->
                <button type="button"
                        wire:click="resetFilters"
                        onclick="monVisResetAllFilters()"
                        class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
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
                        <svg class="w-4 h-4 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Filter Kolom</span>
                        <svg class="w-3.5 h-3.5 text-biru3 transition-transform" :class="openMobileFilter ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <button type="button" wire:click="resetFilters" onclick="monVisResetAllFilters()"
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
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterTahun">
                                    {{ !empty($filterTahun) ? count((array)$filterTahun) . ' dipilih' : 'Semua' }}
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

                        {{-- Filter Mode (multi-select + searchable + checkbox) --}}
                        <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                            x-data="{
                                isOpen: false,
                                search: '',
                                opts: [
                                    { value: 'offline',        label: 'Offline' },
                                    { value: 'online_meeting', label: 'Online Meeting' },
                                    { value: 'online_upload',  label: 'Upload Bukti' },
                                    { value: 'none',           label: 'Belum Diatur (-)' },
                                ],
                                get sel() {
                                    const v = $wire.filterMode;
                                    return Array.isArray(v) ? v : (v ? [String(v)] : []);
                                },
                                get triggerLabel() {
                                    if (this.sel.length === 0) return 'Semua Mode';
                                    if (this.sel.length === 1) {
                                        const f = this.opts.find(o => o.value === this.sel[0]);
                                        return f ? f.label : 'Semua Mode';
                                    }
                                    return this.sel.length + ' mode dipilih';
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
                                    $wire.set('filterMode', cur);
                                },
                                clearAll() {
                                    $wire.set('filterMode', []);
                                    this.search = '';
                                }
                            }">
                            <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                <span class="font-bold text-xs text-biru1">Filter Mode</span>
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterMode">
                                    {{ !empty($filterMode) ? count((array)$filterMode) . ' dipilih' : 'Semua' }}
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
                                    <input type="text" placeholder="Cari mode..."
                                        x-model="search"
                                        x-ref="modeSearch"
                                        x-init="$watch('isOpen', v => v && $nextTick(() => $refs.modeSearch?.focus()))"
                                        class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                    <div @click="clearAll()"
                                        :class="sel.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                        class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                        <input type="checkbox" :checked="sel.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                        <span>Semua Mode</span>
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
                                    { value: 'not_started', label: 'Belum Dinilai' },
                                    { value: 'draft',       label: 'Draft Penilaian' },
                                    { value: 'submitted',   label: 'Sudah Dinilai' },
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
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterStatus">
                                    {{ !empty($filterStatus) ? count((array)$filterStatus) . ' dipilih' : 'Semua' }}
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
                                <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" wire:loading.remove wire:target="filterKodeSatker">
                                    {{ !empty($filterKodeSatker) ? count((array)$filterKodeSatker) . ' dipilih' : 'Semua' }}
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

        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                <tr>
                    {{-- TAHUN / PERIODE with Excel Filter --}}
                    <th class="px-8 py-5 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1 cursor-pointer" onclick="monVisToggleFilter('tahun')">
                                <span>PERIODE</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-tahun" class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-48 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..." oninput="monVisFilterList('tahun', this.value)" class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-tahun" onchange="monVisToggleAll('tahun', this)" class="accent-biru2">
                                    Semua Periode
                                </label>
                                <div id="monvis-list-tahun" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach($periods as $p)
                                        <label class="monvis-item-tahun flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower($p->tahun) }}">
                                            <input type="checkbox" class="monvis-cb-tahun accent-biru2" value="{{ $p->tahun }}"
                                                {{ in_array((string)$p->tahun, (array)$filterTahun) ? 'checked' : '' }}>
                                            {{ $p->nama ?? $p->tahun }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('tahun')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('tahun')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- KODE SATKER with Excel Filter --}}
                    <th class="px-8 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer" onclick="monVisToggleFilter('kode_satker')">
                                <span>KODE SATKER</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-kode_satker" class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..." oninput="monVisFilterList('kode_satker', this.value)" class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-kode_satker" onchange="monVisToggleAll('kode_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monvis-list-kode_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach($submissions as $sub)
                                        @if($sub->user && $sub->user->masterSatker)
                                        <label class="monvis-item-kode_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower($sub->user->masterSatker->kode_satker) }}">
                                            <input type="checkbox" class="monvis-cb-kode_satker accent-biru2" value="{{ $sub->user->masterSatker->kode_satker }}" checked>
                                            {{ $sub->user->masterSatker->kode_satker }}
                                        </label>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('kode_satker')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('kode_satker')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- NAMA SATKER with Excel Filter --}}
                    <th class="px-8 py-5 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer" onclick="monVisToggleFilter('nama_satker')">
                                <span>NAMA SATKER</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-nama_satker" class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..." oninput="monVisFilterList('nama_satker', this.value)" class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-nama_satker" onchange="monVisToggleAll('nama_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monvis-list-nama_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach($submissions as $sub)
                                        @if($sub->user && $sub->user->masterSatker)
                                        <label class="monvis-item-nama_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower($sub->user->masterSatker->nama_satker) }}">
                                            <input type="checkbox" class="monvis-cb-nama_satker accent-biru2" value="{{ strtolower($sub->user->masterSatker->nama_satker) }}" checked>
                                            {{ $sub->user->masterSatker->nama_satker }}
                                        </label>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('nama_satker')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('nama_satker')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- PETUGAS with Excel Filter --}}
                    <th class="px-8 py-5 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer" onclick="monVisToggleFilter('evaluator')">
                                <span>PETUGAS</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-evaluator" class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari nama petugas..." oninput="monVisFilterList('evaluator', this.value)" class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-evaluator" onchange="monVisToggleAll('evaluator', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monvis-list-evaluator" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach($evaluators as $ev)
                                        <label class="monvis-item-evaluator flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower($ev->name) }}">
                                            <input type="checkbox" class="monvis-cb-evaluator accent-biru2" value="{{ strtolower($ev->name) }}" checked>
                                            {{ $ev->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('evaluator')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('evaluator')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- MODE with Excel Filter --}}
                    <th class="px-8 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer" onclick="monVisToggleFilter('mode')">
                                <span>MODE</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-mode" class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-48 p-3 text-biru1 font-medium normal-case text-left">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-mode" onchange="monVisToggleAll('mode', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monvis-list-mode" class="space-y-1">
                                    @foreach(['offline' => 'Offline', 'online_meeting' => 'Online Meeting', 'online_upload' => 'Upload Bukti', 'none' => 'Belum Diatur (-)'] as $mKey => $mLabel)
                                        <label class="monvis-item-mode flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ $mKey }}">
                                            <input type="checkbox" class="monvis-cb-mode accent-biru2" value="{{ $mKey }}" checked>
                                            {{ $mLabel }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('mode')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('mode')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>
                    <th class="px-8 py-5 text-center">JADWAL VISITASI</th>

                    {{-- STATUS with Excel Filter --}}
                    <th class="px-8 py-5 text-center">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center justify-center gap-1 cursor-pointer" onclick="monVisToggleFilter('status')">
                                <span>STATUS</span>
                                <svg class="w-3 h-3 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="monvis-filter-status" class="hidden absolute right-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-44 p-3 text-biru1 font-medium normal-case text-left">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="monvis-all-status" onchange="monVisToggleAll('status', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="monvis-list-status" class="space-y-1">
                                    @foreach(['not_started' => 'Belum Dinilai', 'draft' => 'Draft Penilaian', 'submitted' => 'Sudah Dinilai'] as $sKey => $sLabel)
                                        <label class="monvis-item-status flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ $sKey }}">
                                            <input type="checkbox" class="monvis-cb-status accent-biru2" value="{{ $sKey }}" checked>
                                            {{ $sLabel }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="monVisCloseFilter('status')" class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="monVisApplyFilter('status')" class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    <th class="px-8 py-5 text-center">NILAI</th>
                    <th class="px-8 py-5 text-center">AKSI</th>
                </tr>
            </thead>
        </x-slot>

        <x-slot name="cards">
            @forelse($submissions as $sub)
                @php
                    $v = $sub->visitasi;
                    $satker = $sub->user->masterSatker;
                @endphp
                @if ($satker)
                    <div wire:key="monvis-card-{{ $sub->id_submission }}" class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-bold text-biru1 text-sm leading-snug">{{ $satker->nama_satker }}</div>
                            <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold shrink-0">
                                {{ $sub->period->tahun }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-2 pt-2 border-t border-abubiru/30 text-xs font-semibold text-biru3">
                            <div class="flex justify-between items-center gap-2">
                                <span>Kode Satker:</span>
                                <span class="text-biru1 font-mono">{{ $satker->kode_satker }}</span>
                            </div>
                            <div class="flex justify-between items-start gap-4">
                                <span class="shrink-0">Petugas Visitasi:</span>
                                <span class="text-biru2 text-right leading-snug">
                                    @if ($v && $v->evaluators->count() > 0)
                                        {{ $v->evaluators->pluck('user.name')->implode(', ') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <span class="shrink-0">Mode:</span>
                                <span class="text-biru1 text-right">
                                    @if ($v && $v->visit_mode === 'online_upload')
                                        <span class="inline-flex items-center rounded-md bg-amber-500/10 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                            Upload Bukti
                                        </span>
                                    @elseif ($v && ($v->visit_at || $v->evaluators->isNotEmpty()) && $v->visit_mode === 'online_meeting')
                                        <span class="inline-flex items-center rounded-md bg-sky-500/10 text-sky-700 px-2.5 py-0.5 text-xs font-medium">
                                            Online Meeting
                                        </span>
                                    @elseif ($v && ($v->visit_at || $v->evaluators->isNotEmpty()) && $v->visit_mode === 'offline')
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 text-emerald-700 px-2.5 py-0.5 text-xs font-medium">
                                            Offline
                                        </span>
                                    @else
                                        <span class="text-xs text-biru3 font-medium">-</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <span class="shrink-0">Jadwal Visitasi:</span>
                                <span class="text-biru1 text-right">
                                    @if ($v && $v->visit_mode === 'online_upload')
                                        <span class="text-xs text-biru3 font-medium">-</span>
                                    @elseif ($v?->visit_at)
                                        {{ \Carbon\Carbon::parse($v->visit_at)->translatedFormat('d M Y') }}
                                    @else
                                        <span class="text-xs text-biru4 italic">Belum Dijadwalkan</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span>Status:</span>
                                @if ($v?->visit_score !== null)
                                    <span class="inline-flex rounded-full bg-hijau2/10 text-hijau px-2.5 py-0.5 text-xs font-bold">
                                        Sudah Dinilai
                                    </span>
                                @elseif ($v && $v->evaluators->whereNotNull('score')->count() > 0)
                                    <span class="inline-flex rounded-full bg-oren2/10 text-oren2 px-2.5 py-0.5 text-xs font-bold">
                                        Draft Penilaian
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-abubiru/50 text-biru4 px-2.5 py-0.5 text-xs font-bold">
                                        Belum Dinilai
                                    </span>
                                @endif
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span>Nilai Visitasi:</span>
                                <span class="font-bold text-biru1 text-sm">
                                    {{ $v?->visit_score !== null ? number_format($v->visit_score, 2) : '-' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-abubiru/30">
                            @php
                                $isSuperEval = Auth::user()?->role?->nama_role === 'super_evaluator';
                                $isUserVisitor = $v && $v->evaluators->contains(fn($ev) => (string)($ev->id_user ?? '') === (string)Auth::id());
                                $isScheduled = $v && ($v->visit_at || $v->visit_mode === 'online_upload' || $v->evaluators->isNotEmpty());
                            @endphp
                            @if ($isScheduled)
                                @if ($isSuperEval && $v && $v->visit_score === null)
                                    <button
                                        @click="selectedVisitIds = ['{{ $v->id_visit }}']; selectedVisitId = '{{ $v->id_visit }}'; isEditScheduled = true; modalTitle = 'Edit Jadwal Visitasi'; selectedSatkerName = '{{ $satker ? ('(' . $satker->kode_satker . ') ' . $satker->nama_satker) : 'BPS Kab/Kota' }}'; visitAt = '{{ $v->visit_at ? \Carbon\Carbon::parse($v->visit_at)->format('Y-m-d') : '' }}'; visitMode = '{{ $v->visit_mode ?? 'offline' }}'; linkMeeting = '{{ $v->link_meeting ?? '' }}'; visitorIds = {{ json_encode($v->evaluators->pluck('id_user')->toArray()) }}; showJadwalModal = true"
                                        class="flex items-center gap-1.5 rounded-lg bg-oren2 border border-oren2/30 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition cursor-pointer"
                                        title="Edit Jadwal">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit Jadwal</span>
                                    </button>
                                @endif
                                @if ($isSuperEval || $isUserVisitor)
                                    <a href="{{ route('evaluasi.visitasi.review', \App\Helpers\SecureId::encrypt($v->id_visit)) }}"
                                        wire:navigate.hover
                                        class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                        title="Nilai">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Nilai</span>
                                    </a>
                                @endif
                            @else
                                <span class="text-xs text-biru3 font-medium">-</span>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div wire:key="monvis-card-empty" class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                    Tidak ada data Kabupaten/Kota yang sesuai filter atau tahap visitasi.
                </div>
            @endforelse
        </x-slot>

        @forelse($submissions as $sub)
            @php
                $v = $sub->visitasi;
                $satker = $sub->user->masterSatker;
            @endphp
            @if ($satker)
                <tr wire:key="monvis-row-{{ $sub->id_submission }}" class="border-t border-abubiru/30 hover:bg-abubiru/10 transition">
                    <td class="px-8 py-5 text-sm font-bold text-biru1">
                        {{ $sub->period->tahun }}
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-biru1 text-sm text-center">{{ $satker->kode_satker }}</div>
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-biru1 text-sm">{{ $satker->nama_satker }}</div>
                    </td>
                    <td class="px-8 py-5 text-sm font-medium text-biru2">
                        @if ($v && $v->evaluators->count() > 0)
                            {{ $v->evaluators->pluck('user.name')->implode(', ') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if ($v && $v->visit_mode === 'online_upload')
                            <span class="inline-flex items-center rounded-md bg-amber-500/10 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                Upload Bukti
                            </span>
                        @elseif ($v && ($v->visit_at || $v->evaluators->isNotEmpty()) && $v->visit_mode === 'online_meeting')
                            <span class="inline-flex items-center rounded-md bg-sky-500/10 text-sky-700 px-2.5 py-0.5 text-xs font-medium">
                                Online Meeting
                            </span>
                        @elseif ($v && ($v->visit_at || $v->evaluators->isNotEmpty()) && $v->visit_mode === 'offline')
                            <span class="inline-flex items-center rounded-md bg-emerald-500/10 text-emerald-700 px-2.5 py-0.5 text-xs font-medium">
                                Offline
                            </span>
                        @else
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if ($v && $v->visit_mode === 'online_upload')
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @elseif ($v?->visit_at)
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-4.5 h-4.5 text-biru4 mt-0.5 shrink-0" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="text-sm">
                                    <div class="font-bold text-biru1">
                                        {{ \Carbon\Carbon::parse($v->visit_at)->translatedFormat('d M Y') }}</div>
                                </div>
                            </div>
                        @else
                            <div
                                class="inline-flex items-center gap-1.5 rounded-lg bg-abubiru/40 text-biru4 border border-abubiru/60 px-3 py-1.5 text-xs font-bold w-fit">
                                <svg class="w-4.5 h-4.5 text-biru4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Belum Dijadwalkan</span>
                            </div>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if ($v?->visit_score !== null)
                            <span
                                class="inline-flex rounded-full bg-hijau2/10 text-hijau px-3 py-1.5 text-xs font-bold">
                                Sudah Dinilai
                            </span>
                        @elseif ($v && $v->evaluators->whereNotNull('score')->count() > 0)
                            <span
                                class="inline-flex rounded-full bg-oren2/10 text-oren2 px-3 py-1.5 text-xs font-bold">
                                Draft Penilaian
                            </span>
                        @else
                            <span
                                class="inline-flex rounded-full bg-abubiru/50 text-biru4 px-3 py-1.5 text-xs font-bold">
                                Belum Dinilai
                            </span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-biru1">
                        {{ $v?->visit_score !== null ? number_format($v->visit_score, 2) : '-' }}
                    </td>
                    <td class="px-8 py-5 text-center">
                        @php
                            $isSuperEval = Auth::user()?->role?->nama_role === 'super_evaluator';
                            $isUserVisitor = $v && $v->evaluators->contains(fn($ev) => (string)($ev->id_user ?? '') === (string)Auth::id());
                            $isScheduled = $v && ($v->visit_at || $v->visit_mode === 'online_upload' || $v->evaluators->isNotEmpty());
                        @endphp
                        @if ($isScheduled && ($isSuperEval || $isUserVisitor))
                            <div class="flex items-center justify-center gap-2">
                                @if ($isSuperEval && $v && $v->visit_score === null)
                                    <button
                                        @click="selectedVisitIds = ['{{ $v->id_visit }}']; selectedVisitId = '{{ $v->id_visit }}'; isEditScheduled = true; modalTitle = 'Edit Jadwal Visitasi'; selectedSatkerName = '{{ $satker ? ('(' . $satker->kode_satker . ') ' . $satker->nama_satker) : 'BPS Kab/Kota' }}'; visitAt = '{{ $v->visit_at ? \Carbon\Carbon::parse($v->visit_at)->format('Y-m-d') : '' }}'; visitMode = '{{ $v->visit_mode ?? 'offline' }}'; linkMeeting = '{{ $v->link_meeting ?? '' }}'; visitorIds = {{ json_encode($v->evaluators->pluck('id_user')->toArray()) }}; showJadwalModal = true"
                                        class="inline-flex items-center justify-center rounded-lg bg-oren2 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition shadow-xs cursor-pointer"
                                        title="Edit Jadwal">
                                        Edit
                                    </button>
                                @endif
                                @if ($isSuperEval || $isUserVisitor)
                                    <a href="{{ route('evaluasi.visitasi.review', \App\Helpers\SecureId::encrypt($v->id_visit)) }}"
                                        wire:navigate.hover
                                        class="inline-flex items-center justify-center rounded-lg bg-biru2 px-3 py-1.5 text-xs text-white hover:opacity-90 font-bold transition shadow-xs"
                                        title="Nilai">
                                        Nilai
                                    </a>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @endif
                    </td>
                </tr>
            @endif
        @empty
            <tr wire:key="monvis-row-empty">
                <td colspan="9" class="px-8 py-5 text-center text-sm font-medium text-biru3">
                    Tidak ada data Kabupaten/Kota yang sesuai filter atau tahap visitasi.
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4 w-full text-sm text-biru3 font-semibold bg-white">
                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 text-center sm:text-left w-full lg:w-auto">
                    <div class="whitespace-nowrap">Menampilkan {{ $submissions->firstItem() ?? 0 }} - {{ $submissions->lastItem() ?? 0 }} dari {{ $submissions->total() }} data</div>
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
                        <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @endif

                    @php
                        $startPage = max(1, $submissions->currentPage() - 2);
                        $endPage = min($submissions->lastPage(), $submissions->currentPage() + 2);
                    @endphp
                    @for ($p = $startPage; $p <= $endPage; $p++)
                        @if ($p == $submissions->currentPage())
                            <span class="w-9 h-9 rounded-lg bg-biru1 text-white font-bold flex items-center justify-center shadow-md">{{ $p }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $p }})" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru3 font-bold hover:bg-putihbg flex items-center justify-center transition cursor-pointer">{{ $p }}</button>
                        @endif
                    @endfor

                    @if ($submissions->hasMorePages())
                        <button type="button" wire:click="nextPage" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-tables.monitoring-table>
</div>

<script>
    const _monVisWireMap = {
        'tahun':       'setFilterTahun',
        'kode_satker': 'setFilterKodeSatker',
        'nama_satker': 'setFilterNamaSatker',
        'evaluator':   'setFilterEvaluator',
        'status':      'setFilterStatus',
        'mode':        'setFilterMode',
    };
    function monVisToggleFilter(col) { toggleGenericTableFilter('monvis', col); }
    function monVisCloseFilter(col) { closeGenericTableFilter('monvis', col); }
    function monVisToggleAll(col, cb) { toggleGenericTableAll('monvis', col, cb); }
    function monVisFilterList(col, q) { filterGenericTableList('monvis', col, q); }
    function monVisApplyFilter(col, btnEl) { applyGenericTableFilter('monvis', col, _monVisWireMap, @this); }
    function monVisResetAllFilters() {
        document.querySelectorAll('.monvis-cb-tahun, .monvis-cb-kode_satker, .monvis-cb-nama_satker, .monvis-cb-evaluator, .monvis-cb-status, .monvis-cb-mode').forEach(cb => cb.checked = true);
        document.querySelectorAll('#monvis-all-tahun, #monvis-all-kode_satker, #monvis-all-nama_satker, #monvis-all-evaluator, #monvis-all-status, #monvis-all-mode').forEach(cb => cb.checked = true);
    }
    function toggleAllVisitKota(el) { toggleAllModalItems(el, 'visit-kota-checkbox'); }
    function filterVisitKotaList(val) { filterModalItems(val, 'visit-kota-item'); }
    function handleExportVisitasiSubmit(event, form) { handleExportSubmit(event, form, 'modal-export-visitasi'); }
</script>
