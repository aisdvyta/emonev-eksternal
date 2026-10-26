<x-layouts.app title="Monitoring SAQ - HaloKIP">
    @php
        $roleName = Auth::user()?->role?->nama_role;
        $isVerifikator = $roleName === 'verifikator';
        $exportAction = Route::has('evaluasi.saq.export')
            ? route('evaluasi.saq.export')
            : ($isVerifikator ? route('verifikator.monitoring-saq.export') : route('operator.kues-saq.export'));
        $homeRoute = $isVerifikator ? route('verifikator.index') : route('operator.index');
    @endphp
    <x-ui.page-header title="Monitoring Kuesioner SAQ"
        description="{{ $isVerifikator ? 'Pantau progres pengisian dan histori kuesioner SAQ pada Satker Anda.' : 'Pantau progres pengisian dan histori kuesioner SAQ Anda pada periode berjalan maupun sebelumnya.' }}"
        :breadcrumb="[
            'Beranda' => $homeRoute,
            'Kegiatan Evaluasi' => '#',
            'Monitoring SAQ' => '#',
        ]">
    </x-ui.page-header>

    <div data-monitoring-root x-data="{
        openMobileFilter: false,
        search: '',
        selectedTahun: [],
        selectedStatus: [],
        tahunSearch: '',
        statusSearch: '',
        allTahun: [],
        allStatus: [
            { value: 'belum_dimulai', label: 'Belum Dimulai', checked: true },
            { value: 'draft_pengisian', label: 'Draft Pengisian', checked: true },
            { value: 'menunggu_verif', label: 'Menunggu Verif', checked: true },
            { value: 'dikembalikan', label: 'Dikembalikan', checked: true },
            { value: 'menunggu_eval', label: 'Menunggu Eval', checked: true },
            { value: 'draft_penilaian', label: 'Draft Penilaian', checked: true },
            { value: 'selesai', label: 'Selesai', checked: true },
            { value: 'gugur', label: 'Gugur', checked: true },
        ],
        activeDropdown: null,
        showExportModal: false,
        currentPage: 1,
        perPage: 5,
        totalRows: {{ count($periods) }},
        get startIndex() { return (this.currentPage - 1) * this.perPage; },
        get endIndex() { return this.startIndex + this.perPage; },
        init() {
            this.$nextTick(() => {
                const root = this.$el ? (this.$el.closest('[data-monitoring-root]') || this.$el) : document;
                const rows = Array.from(root.querySelectorAll('tbody tr[data-tahun]'));
                const tahunSet = new Set();
                rows.forEach(row => {
                    if (row.getAttribute('data-tahun')) {
                        tahunSet.add(row.getAttribute('data-tahun'));
                    }
                });
                this.allTahun = Array.from(tahunSet).map(val => ({ value: val, checked: true }));
                this.evaluateRows();
            });
            this.$watch('search', () => { this.currentPage = 1; this.evaluateRows(); });
            this.$watch('selectedTahun', () => { this.currentPage = 1; this.evaluateRows(); });
            this.$watch('selectedStatus', () => { this.currentPage = 1; this.evaluateRows(); });
            this.$watch('currentPage', () => { this.evaluateRows(); });
        },
        toggleDropdown(name) {
            this.activeDropdown = this.activeDropdown === name ? null : name;
            const root = this.$el.closest('[data-monitoring-root]') || this.$el;
            const wrapper = root.querySelector('.overflow-x-auto');
            if (wrapper) {
                wrapper.style.overflow = this.activeDropdown ? 'visible' : '';
            }
        },
        closeDropdowns() {
            this.activeDropdown = null;
            const root = this.$el.closest('[data-monitoring-root]') || this.$el;
            const wrapper = root.querySelector('.overflow-x-auto');
            if (wrapper) wrapper.style.overflow = '';
        },
        toggleAll(name, checked) {
            if (name === 'tahun') {
                this.allTahun.forEach(t => t.checked = checked);
            } else if (name === 'status') {
                this.allStatus.forEach(s => s.checked = checked);
            }
        },
        applyFilter(name) {
            if (name === 'tahun') {
                this.selectedTahun = this.allTahun.filter(t => t.checked).map(t => t.value);
            } else if (name === 'status') {
                this.selectedStatus = this.allStatus.filter(s => s.checked).map(s => s.value);
            }
            this.currentPage = 1;
            this.evaluateRows();
            this.closeDropdowns();
        },
        cancelFilter(name) {
            if (name === 'tahun') {
                this.allTahun.forEach(t => {
                    t.checked = this.selectedTahun.length === 0 || this.selectedTahun.includes(t.value);
                });
            } else if (name === 'status') {
                this.allStatus.forEach(s => {
                    s.checked = this.selectedStatus.length === 0 || this.selectedStatus.includes(s.value);
                });
            }
            this.closeDropdowns();
        },
        resetFilters() {
            this.search = '';
            this.selectedTahun = [];
            this.selectedStatus = [];
            this.allTahun.forEach(t => t.checked = true);
            this.allStatus.forEach(s => s.checked = true);
            this.currentPage = 1;
            this.evaluateRows();
        },
        evaluateRows() {
            const root = this.$el ? (this.$el.closest('[data-monitoring-root]') || this.$el) : document;
            const tbody = root.querySelector('tbody');
            const rows = tbody ? Array.from(tbody.querySelectorAll('tr[data-tahun]')) : [];
            let matchCount = 0;
            rows.forEach(row => {
                const name = (row.getAttribute('data-name') || '').toLowerCase();
                const tahun = row.getAttribute('data-tahun') || '';
                const status = row.getAttribute('data-status') || '';
                
                const q = (this.search || '').toLowerCase().trim();
                const matchesSearch = !q || name.includes(q) || tahun.includes(q);
                const matchesTahun = this.selectedTahun.length === 0 || this.selectedTahun.includes(tahun);
                const matchesStatus = this.selectedStatus.length === 0 || this.selectedStatus.includes(status);
                
                if (matchesSearch && matchesTahun && matchesStatus) {
                    if (matchCount >= this.startIndex && matchCount < this.endIndex) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                    matchCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            this.totalRows = matchCount;

            // Also filter mobile cards
            const cardsContainer = root.querySelector('[data-cards-container]');
            if (cardsContainer) {
                const cards = Array.from(cardsContainer.children).filter(el => el.hasAttribute('data-tahun'));
                let cardMatchCount = 0;
                cards.forEach(card => {
                    const name = (card.getAttribute('data-name') || '').toLowerCase();
                    const tahun = card.getAttribute('data-tahun') || '';
                    const status = card.getAttribute('data-status') || '';
                    
                    const q = (this.search || '').toLowerCase().trim();
                    const matchesSearch = !q || name.includes(q) || tahun.includes(q);
                    const matchesTahun = this.selectedTahun.length === 0 || this.selectedTahun.includes(tahun);
                    const matchesStatus = this.selectedStatus.length === 0 || this.selectedStatus.includes(status);
                    
                    if (matchesSearch && matchesTahun && matchesStatus) {
                        if (cardMatchCount >= this.startIndex && cardMatchCount < this.endIndex) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                        cardMatchCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        }
    }" @click.outside="closeDropdowns()">
        <x-tables.monitoring-table title="Histori Kuesioner SAQ" :headers="['TAHUN', 'NAMA PERIODE', 'JADWAL PENGISIAN', 'STATUS', 'NILAI SAQ', 'AKSI']" hasCards="true">
            <x-slot name="headerCenter">
                <!-- Search + Unduh Excel (mobile: side by side) -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text" x-model="search" placeholder="Cari Periode/Tahun..."
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
                        @click="showExportModal = true" 
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
                            @click="showExportModal = true" 
                            class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-hijau hover:opacity-90 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Unduh Excel</span>
                    </button>
                    <!-- Reset Filter: desktop only -->
                    <button type="button" 
                            @click="resetFilters()" 
                            class="hidden md:inline-flex items-center gap-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </x-slot>

            <x-slot name="mobileFilters">
                <div class="space-y-3">
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
                        <button type="button" @click="resetFilters()"
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
                                    searchLocal: '',
                                    get triggerLabel() {
                                        if (selectedTahun.length === 0) return 'Semua Periode';
                                        if (selectedTahun.length === 1) return 'Tahun ' + selectedTahun[0];
                                        return selectedTahun.length + ' periode dipilih';
                                    },
                                    get filtered() {
                                        const q = this.searchLocal.toLowerCase().trim();
                                        if (!q) return allTahun;
                                        return allTahun.filter(t => String(t.value).toLowerCase().includes(q));
                                    },
                                    toggle(val) {
                                        const idx = selectedTahun.indexOf(val);
                                        if (idx >= 0) selectedTahun.splice(idx, 1);
                                        else selectedTahun.push(val);
                                        currentPage = 1;
                                        evaluateRows();
                                    },
                                    clearAll() {
                                        selectedTahun = [];
                                        this.searchLocal = '';
                                        currentPage = 1;
                                        evaluateRows();
                                    }
                                }">
                                <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                    <span class="font-bold text-xs text-biru1">Filter Periode</span>
                                    <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" x-text="selectedTahun.length > 0 ? selectedTahun.length + ' dipilih' : 'Semua'"></span>
                                </div>
                                <div class="p-2 relative">
                                    <button type="button" @click="isOpen = !isOpen"
                                        class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                        :class="selectedTahun.length > 0 ? 'text-biru1' : 'text-biru3'">
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
                                            x-model="searchLocal"
                                            x-ref="periodeSearch"
                                            x-init="$watch('isOpen', v => v && $nextTick(() => $refs.periodeSearch?.focus()))"
                                            class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                        <div @click="clearAll()"
                                            :class="selectedTahun.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                            class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                            <input type="checkbox" :checked="selectedTahun.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                            <span>Semua Periode</span>
                                        </div>
                                        <div class="max-h-48 overflow-y-auto space-y-0.5">
                                            <template x-for="item in filtered" :key="item.value">
                                                <div @click="toggle(item.value)"
                                                    :class="selectedTahun.includes(item.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                    <input type="checkbox" :checked="selectedTahun.includes(item.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                    <span x-text="'Tahun ' + item.value" class="truncate flex-1"></span>
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
                                    searchLocal: '',
                                    get triggerLabel() {
                                        if (selectedStatus.length === 0) return 'Semua Status';
                                        if (selectedStatus.length === 1) {
                                            const f = allStatus.find(s => s.value === selectedStatus[0]);
                                            return f ? f.label : 'Semua Status';
                                        }
                                        return selectedStatus.length + ' status dipilih';
                                    },
                                    get filtered() {
                                        const q = this.searchLocal.toLowerCase().trim();
                                        if (!q) return allStatus;
                                        return allStatus.filter(s => s.label.toLowerCase().includes(q));
                                    },
                                    toggle(val) {
                                        const idx = selectedStatus.indexOf(val);
                                        if (idx >= 0) selectedStatus.splice(idx, 1);
                                        else selectedStatus.push(val);
                                        currentPage = 1;
                                        evaluateRows();
                                    },
                                    clearAll() {
                                        selectedStatus = [];
                                        this.searchLocal = '';
                                        currentPage = 1;
                                        evaluateRows();
                                    }
                                }">
                                <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                    <span class="font-bold text-xs text-biru1">Filter Status</span>
                                    <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" x-text="selectedStatus.length > 0 ? selectedStatus.length + ' dipilih' : 'Semua'"></span>
                                </div>
                                <div class="p-2 relative">
                                    <button type="button" @click="isOpen = !isOpen"
                                        class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                        :class="selectedStatus.length > 0 ? 'text-biru1' : 'text-biru3'">
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
                                            x-model="searchLocal"
                                            x-ref="statusSearch"
                                            x-init="$watch('isOpen', v => v && $nextTick(() => $refs.statusSearch?.focus()))"
                                            class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                        <div @click="clearAll()"
                                            :class="selectedStatus.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                            class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                            <input type="checkbox" :checked="selectedStatus.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                            <span>Semua Status</span>
                                        </div>
                                        <div class="max-h-48 overflow-y-auto space-y-0.5">
                                            <template x-for="item in filtered" :key="item.value">
                                                <div @click="toggle(item.value)"
                                                    :class="selectedStatus.includes(item.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                    <input type="checkbox" :checked="selectedStatus.includes(item.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                    <span x-text="item.label" class="truncate flex-1"></span>
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
                <div data-cards-container class="space-y-4">
                    @forelse ($periods as $p)
                        @php
                            $sub = $submissions->get($p->id_periode);
                            $status = $sub ? $sub->status : -1;
                            
                            $saqStart = $p->saq_start;
                            $saqEnd = $p->saq_end;
                            $hasSaqSchedule = !empty($saqStart) && !empty($saqEnd);
                            $evalEnd = $p->eval_end ? \Carbon\Carbon::parse($p->eval_end)->endOfDay() : null;
                            $isStageExpired = $evalEnd && now()->greaterThan($evalEnd);
                            $isSaqReleased = !$evalEnd || now()->greaterThan($evalEnd);
                            
                            $today = now()->toDateString();
                            $isActive = false;
                            $isFuture = false;
                            $isPast = false;

                            if ($hasSaqSchedule) {
                                if ($today >= $saqStart && $today <= $saqEnd) {
                                    $isActive = true;
                                } elseif ($today < $saqStart) {
                                    $isFuture = true;
                                } else {
                                    $isPast = true;
                                }
                            } else {
                                if ($p->periode_end && $today > $p->periode_end) {
                                    $isPast = true;
                                } else {
                                    $isFuture = true;
                                }
                            }

                            // Status Mapping: Selama masa evaluasi belum selesai, status tetap "menunggu_eval" untuk Operator
                            $statusVal = 'belum_dimulai';
                            $statusNum = (int) $status;
                            if ($statusNum === 0) {
                                $answeredCount = $sub ? $sub->answers->filter(fn($a) => !is_null($a->answer) && ((int)$a->answer === 0 || (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? '')))))->count() : 0;
                                $statusVal = $answeredCount > 0 ? 'draft_pengisian' : 'belum_dimulai';
                            } elseif ($statusNum === 1) {
                                $statusVal = 'menunggu_verif';
                            } elseif ($statusNum === 2) {
                                $statusVal = 'dikembalikan';
                            } elseif ($statusNum === 8) {
                                $statusVal = 'gugur';
                            } else {
                                if (!$isSaqReleased) {
                                    $statusVal = 'menunggu_eval';
                                } else {
                                    $statusVal = in_array($statusNum, [4, 5, 6, 7]) ? 'selesai' : 'menunggu_eval';
                                }
                            }
                        @endphp
                        <div data-tahun="{{ $p->tahun }}" data-name="{{ $p->nama }}" data-status="{{ $statusVal }}"
                            class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="font-bold text-biru1 text-sm leading-snug">{{ $p->nama }}</div>
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold shrink-0">
                                    {{ $p->tahun }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-2 pt-2 border-t border-abubiru/30 text-xs font-semibold text-biru3">
                                <div class="flex justify-between items-center gap-2">
                                    <span>Jadwal Pengisian:</span>
                                    <span class="text-biru1 font-bold">
                                        @if ($hasSaqSchedule)
                                            {{ \Carbon\Carbon::parse($saqStart)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($saqEnd)->translatedFormat('d M Y') }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span>Status:</span>
                                    <x-ui.status-badge :status="$statusVal" />
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span>Nilai SAQ:</span>
                                    <span class="text-biru1 font-extrabold text-sm">
                                        @if ($sub && $sub->result?->ques_score !== null && $isSaqReleased)
                                            {{ number_format($sub->result->ques_score, 2) }}
                                        @else
                                            <span class="text-biru3/60 italic text-xs font-semibold">Belum Dirilis</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end pt-2.5 border-t border-abubiru/30">
                                @if ($isVerifikator)
                                    @if ($sub)
                                        @if ($status == 1 && !$isStageExpired)
                                            <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                                wire:navigate.hover
                                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                                title="Verifikasi SAQ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                                <span>Verifikasi</span>
                                            </a>
                                        @else
                                            <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                                wire:navigate.hover
                                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                                title="Lihat Jawaban SAQ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Lihat Jawaban</span>
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-xs text-biru3/50 italic">Belum Dimulai</span>
                                    @endif
                                @else
                                    @if ($isActive && !$isStageExpired)
                                        @if (in_array($status, [-1, 0, 1, 2]))
                                            <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                                wire:navigate.hover
                                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                                title="{{ in_array($status, [-1, 0, 2]) ? 'Mulai / Isi Kuesioner' : 'Lihat Jawaban SAQ' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>{{ in_array($status, [-1, 0, 2]) ? 'Mulai / Isi' : 'Lihat Jawaban' }}</span>
                                            </a>
                                        @else
                                            <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                                wire:navigate.hover
                                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                                title="Lihat Hasil Evaluasi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Lihat Hasil</span>
                                            </a>
                                        @endif
                                    @else
                                        @if ($sub)
                                            <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                                wire:navigate.hover
                                                class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                                title="Lihat Jawaban SAQ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Lihat Jawaban</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-biru3/50 italic">Belum Dimulai</span>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                            Tidak ada data kuesioner SAQ.
                        </div>
                    @endforelse
                </div>
            </x-slot>

            <x-slot name="thead">
                <thead class="bg-putihbg text-xs font-bold uppercase tracking-widest text-biru4 border-b border-abubiru">
                    <tr>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">
                            <div class="relative inline-block text-left">
                                <div class="flex items-center gap-1.5 cursor-pointer" @click.stop="toggleDropdown('tahun')">
                                    <span>Tahun</span>
                                    <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.472 6.028A2.5 2.5 0 0017.5 5H6.5a2.5 2.5 0 00-1.972 4.028L9 14.25v5.5a1.25 1.25 0 001.956 1.03l2.5-1.667A1.25 1.25 0 0014 18.083v-3.833l4.472-5.222a2.5 2.5 0 001-2.028z" />
                                    </svg>
                                </div>
                                <div x-show="activeDropdown === 'tahun'" @click.outside="closeDropdowns()"
                                    class="absolute left-0 top-full mt-1.5 z-40 bg-white border border-abubiru rounded-2xl shadow-xl w-48 p-3 text-biru1 font-medium normal-case text-left">
                                    <input type="text" placeholder="Cari..." x-model="tahunSearch"
                                        class="w-full rounded-xl border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 text-biru1">
                                    <label class="flex items-center gap-2 px-1.5 py-1 text-xs font-bold text-biru2 cursor-pointer mb-1.5">
                                        <input type="checkbox"
                                            :checked="allTahun.length > 0 && allTahun.every(t => t.checked)"
                                            @change="toggleAll('tahun', $event.target.checked)"
                                            class="w-3.5 h-3.5 rounded accent-biru2">
                                        (Select All)
                                    </label>
                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                        <template x-for="item in allTahun">
                                            <label x-show="tahunSearch === '' || item.value.toLowerCase().includes(tahunSearch.toLowerCase())"
                                                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-biru5/20 cursor-pointer text-xs font-semibold">
                                                <input type="checkbox" x-model="item.checked"
                                                    class="w-3.5 h-3.5 rounded accent-biru2">
                                                <span x-text="item.value"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-3 pt-2.5 border-t border-abubiru">
                                        <button type="button" @click.stop="cancelFilter('tahun')"
                                            class="px-3 py-1 text-xs font-bold border border-abubiru rounded-xl hover:bg-slate-50 transition text-biru1">Batal</button>
                                        <button type="button" @click.stop="applyFilter('tahun')"
                                            class="px-3 py-1 text-xs font-black bg-hijau text-white rounded-xl hover:opacity-90 transition">OK</button>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Nama Periode</th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Jadwal Pengisian</th>
                        <th class="px-8 py-5 text-center border-r border-abubiru/20">
                            <div class="relative inline-block text-left">
                                <div class="flex items-center justify-center gap-1.5 cursor-pointer" @click.stop="toggleDropdown('status')">
                                    <span>Status</span>
                                    <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.472 6.028A2.5 2.5 0 0017.5 5H6.5a2.5 2.5 0 00-1.972 4.028L9 14.25v5.5a1.25 1.25 0 001.956 1.03l2.5-1.667A1.25 1.25 0 0014 18.083v-3.833l4.472-5.222a2.5 2.5 0 001-2.028z" />
                                    </svg>
                                </div>
                                <div x-show="activeDropdown === 'status'" @click.outside="closeDropdowns()"
                                    class="absolute left-1/2 -translate-x-1/2 top-full mt-1.5 z-40 bg-white border border-abubiru rounded-2xl shadow-xl w-56 p-3 text-biru1 font-medium normal-case text-left">
                                    <input type="text" placeholder="Cari..." x-model="statusSearch"
                                        class="w-full rounded-xl border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 text-biru1">
                                    <label class="flex items-center gap-2 px-1.5 py-1 text-xs font-bold text-biru2 cursor-pointer mb-1.5">
                                        <input type="checkbox"
                                            :checked="allStatus.length > 0 && allStatus.every(s => s.checked)"
                                            @change="toggleAll('status', $event.target.checked)"
                                            class="w-3.5 h-3.5 rounded accent-biru2">
                                        (Select All)
                                    </label>
                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                        <template x-for="item in allStatus">
                                            <label x-show="statusSearch === '' || item.label.toLowerCase().includes(statusSearch.toLowerCase()) || item.value.toLowerCase().includes(statusSearch.toLowerCase())"
                                                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-biru5/20 cursor-pointer text-xs font-semibold">
                                                <input type="checkbox" x-model="item.checked"
                                                    class="w-3.5 h-3.5 rounded accent-biru2">
                                                <span x-text="item.label"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-3 pt-2.5 border-t border-abubiru">
                                        <button type="button" @click.stop="cancelFilter('status')"
                                            class="px-3 py-1 text-xs font-bold border border-abubiru rounded-xl hover:bg-slate-50 transition text-biru1">Batal</button>
                                        <button type="button" @click.stop="applyFilter('status')"
                                            class="px-3 py-1 text-xs font-black bg-hijau text-white rounded-xl hover:opacity-90 transition">OK</button>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Nilai SAQ</th>
                        <th class="px-8 py-5 text-center">Aksi</th>
                    </tr>
                </thead>
            </x-slot>

            @forelse ($periods as $p)
                    @php
                        $sub = $submissions->get($p->id_periode);
                        $status = $sub ? $sub->status : -1;
                        
                        $saqStart = $p->saq_start;
                        $saqEnd = $p->saq_end;
                        $hasSaqSchedule = !empty($saqStart) && !empty($saqEnd);
                        $evalEnd = $p->eval_end ? \Carbon\Carbon::parse($p->eval_end)->endOfDay() : null;
                        $isStageExpired = $evalEnd && now()->greaterThan($evalEnd);
                        $isSaqReleased = !$evalEnd || now()->greaterThan($evalEnd);
                        
                        $today = now()->toDateString();
                        $isActive = false;
                        $isFuture = false;
                        $isPast = false;

                        if ($hasSaqSchedule) {
                            if ($today >= $saqStart && $today <= $saqEnd) {
                                $isActive = true;
                            } elseif ($today < $saqStart) {
                                $isFuture = true;
                            } else {
                                $isPast = true;
                            }
                        } else {
                            if ($p->periode_end && $today > $p->periode_end) {
                                $isPast = true;
                            } else {
                                $isFuture = true;
                            }
                        }

                        // Status Mapping: Selama masa evaluasi belum selesai, status tetap "menunggu_eval" untuk Operator
                        $statusVal = 'belum_dimulai';
                        $statusNum = (int) $status;
                        if ($statusNum === 0) {
                            $answeredCount = $sub ? $sub->answers->filter(fn($a) => !is_null($a->answer) && ((int)$a->answer === 0 || (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? '')))))->count() : 0;
                            $statusVal = $answeredCount > 0 ? 'draft_pengisian' : 'belum_dimulai';
                        } elseif ($statusNum === 1) {
                            $statusVal = 'menunggu_verif';
                        } elseif ($statusNum === 2) {
                            $statusVal = 'dikembalikan';
                        } elseif ($statusNum === 8) {
                            $statusVal = 'gugur';
                        } else {
                            if (!$isSaqReleased) {
                                $statusVal = 'menunggu_eval';
                            } else {
                                $statusVal = in_array($statusNum, [4, 5, 6, 7]) ? 'selesai' : 'menunggu_eval';
                            }
                        }
                        $isPeriodActive = ($p->periode_start <= now() && $p->periode_end >= now());
                        $rowBg = $isPeriodActive ? 'bg-kuning2/25 hover:bg-kuning2/35' : 'hover:bg-abubiru/10';
                    @endphp
                    <tr data-tahun="{{ $p->tahun }}" data-name="{{ $p->nama }}" data-status="{{ $statusVal }}" class="border-t border-abubiru/30 transition {{ $rowBg }}">
                        <td class="px-8 py-5 text-sm font-bold text-biru1 border-r border-abubiru/10">
                            {{ $p->tahun }}
                        </td>
                        <td class="px-8 py-5 text-sm font-semibold text-biru2 border-r border-abubiru/10">
                            {{ $p->nama }}
                        </td>
                        <td class="px-8 py-5 text-sm font-medium text-biru3 border-r border-abubiru/10">
                            @if ($hasSaqSchedule)
                                {{ \Carbon\Carbon::parse($saqStart)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($saqEnd)->translatedFormat('d M Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-8 py-5 text-center border-r border-abubiru/10">
                            <x-ui.status-badge :status="$statusVal" />
                        </td>
                        <td class="px-8 py-5 text-sm font-black text-biru1 border-r border-abubiru/10">
                            @if ($sub && $sub->result?->ques_score !== null && $isSaqReleased)
                                {{ number_format($sub->result->ques_score, 2) }}
                            @else
                                <span class="text-biru3/60 italic text-xs font-semibold">Belum Dirilis</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-center">
                            @if ($isVerifikator)
                                @if ($sub)
                                    @if ($status == 1 && !$isStageExpired)
                                        <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                            wire:navigate.hover
                                            class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                            title="Verifikasi SAQ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </a>
                                    @else
                                        <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($sub->id_submission)]) }}"
                                            wire:navigate.hover
                                            class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                            title="Lihat Jawaban SAQ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @endif
                                @else
                                    <span class="text-xs text-biru3/50 italic">Belum Dimulai</span>
                                @endif
                            @else
                                @if ($isActive && !$isStageExpired)
                                    @if (in_array($status, [-1, 0, 1, 2]))
                                        <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                            wire:navigate.hover
                                            class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                            title="{{ in_array($status, [-1, 0, 2]) ? 'Mulai / Isi Kuesioner' : 'Lihat Jawaban SAQ' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @else
                                        <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                            wire:navigate.hover
                                            class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                            title="Lihat Hasil Evaluasi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @endif
                                @else
                                    @if ($sub)
                                        <a href="{{ route('operator.kues-saq', ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                            wire:navigate.hover
                                            class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                            title="Lihat Jawaban SAQ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-biru3/50 italic">Belum Dimulai</span>
                                    @endif
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr x-show="false">
                        <td colspan="6" class="py-8 px-6 text-center font-medium text-biru3">Tidak ada data periode penilaian tersedia.</td>
                    </tr>
                @endforelse
                <tr x-show="totalRows === 0" x-cloak style="display: none;">
                    <td colspan="6" class="py-8 px-6 text-center font-medium text-biru3">Data tidak ditemukan.</td>
                </tr>

            <x-slot name="pagination">
                <div class="flex items-center justify-between w-full">
                    <div class="text-xs font-semibold text-biru3">
                        Menampilkan <span class="font-bold text-biru1" x-text="totalRows > 0 ? startIndex + 1 : 0"></span> 
                        - <span class="font-bold text-biru1" x-text="Math.min(endIndex, totalRows)"></span> 
                        dari <span class="font-bold text-biru1" x-text="totalRows"></span> data
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1"
                            class="px-3 py-1.5 rounded-lg border border-abubiru bg-white text-biru2 hover:bg-putihbg text-xs font-bold transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Sebelumnya
                        </button>
                        <button type="button" @click="if(currentPage * perPage < totalRows) currentPage++" :disabled="currentPage * perPage >= totalRows"
                            class="px-3 py-1.5 rounded-lg border border-abubiru bg-white text-biru2 hover:bg-putihbg text-xs font-bold transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </x-slot>
        </x-tables.monitoring-table>

        <!-- Modal Export SAQ Excel Operator -->
        <div x-show="showExportModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             style="display: none;">
            <div @click.outside="showExportModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 bg-biru1 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Unduh Rekap SAQ Excel</h3>
                            <p class="text-white/80 text-xs">Pilih tahun periode kuesioner</p>
                        </div>
                    </div>
                    <button type="button" @click="showExportModal = false" class="text-white/70 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form method="GET" action="{{ $exportAction }}" class="p-6 space-y-5">
                    @php
                        $validSaqPeriods = $periods->filter(function($p) use ($submissions) {
                            $sub = $submissions->get($p->id_periode);
                            return $sub && $sub->status >= 1;
                        });
                    @endphp
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-biru1 mb-2">Tahun Periode SAQ</label>
                        @if ($validSaqPeriods->isNotEmpty())
                            <select name="tahun" class="w-full rounded-xl border border-abubiru bg-putihbg px-4 py-2.5 text-sm font-semibold text-biru1 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                                @foreach ($validSaqPeriods as $p)
                                    <option value="{{ $p->tahun }}">
                                        Periode {{ $p->tahun }} ({{ $p->nama }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="tahun" disabled class="w-full rounded-xl border border-abubiru bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed">
                                <option value="" disabled selected>— Tidak ada periode SAQ yang siap diunduh —</option>
                            </select>
                        @endif
                        <p class="text-xs text-biru3 mt-2 leading-relaxed">
                            <span class="font-bold text-biru1">Info:</span> File Excel dapat diunduh setelah kuesioner SAQ untuk tahun periode terkait selesai diisi / disubmit (Status Menunggu Verifikasi atau lebih tinggi).
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-abubiru">
                        <button type="button" @click="showExportModal = false" class="px-4 py-2.5 rounded-xl border border-abubiru text-biru1 font-semibold text-xs bg-white hover:bg-slate-50 transition">
                            Batal
                        </button>
                        <button type="submit" @click="showExportModal = false" @if($validSaqPeriods->isEmpty()) disabled @endif class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-hijau text-white font-bold text-xs hover:opacity-90 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Unduh Excel</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
