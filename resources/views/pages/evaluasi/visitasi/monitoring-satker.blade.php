<x-layouts.app title="Monitoring Visitasi - HaloKIP">
    @php
        $roleName = Auth::user()?->role?->nama_role;
        $isVerifikator = $roleName === 'verifikator';
        $homeRoute = $isVerifikator ? route('verifikator.index') : route('operator.index');
        $exportAction = $isVerifikator 
            ? (Route::has('verifikator.monitoring-visitasi.export') ? route('verifikator.monitoring-visitasi.export') : (Route::has('evaluasi.visitasi.export') ? route('evaluasi.visitasi.export') : '#'))
            : (Route::has('operator.visitasi.export') ? route('operator.visitasi.export') : (Route::has('evaluasi.visitasi.export') ? route('evaluasi.visitasi.export') : '#'));
        
        $detailRouteName = Route::has('evaluasi.visitasi.index')
            ? 'evaluasi.visitasi.index'
            : ($isVerifikator ? 'verifikator.monitoring-visitasi' : 'operator.visitasi');
    @endphp

    <x-ui.page-header title="Monitoring Visitasi & Verifikasi Faktual"
        description="Pantau jadwal kunjungan lapangan dan histori nilai visitasi Anda dari tim evaluator provinsi."
        :breadcrumb="[
            'Beranda' => $homeRoute,
            'Monitoring Visitasi' => '#',
        ]">
    </x-ui.page-header>

    <div data-monitoring-root x-data="{
        openMobileFilter: false,
        search: '',
        selectedTahun: [],
        selectedStatus: [],
        selectedMode: [],
        tahunSearch: '',
        statusSearch: '',
        modeSearch: '',
        allTahun: [],
        allStatus: [
            { value: 'belum_dimulai', label: 'Belum Dimulai', checked: true },
            { value: 'proses', label: 'Proses', checked: true },
            { value: 'selesai', label: 'Selesai', checked: true },
            { value: 'gugur', label: 'Gugur', checked: true },
        ],
        allMode: [
            { value: 'offline', label: 'Offline', checked: true },
            { value: 'online_meeting', label: 'Online Meeting', checked: true },
            { value: 'online_upload', label: 'Upload Bukti', checked: true },
            { value: 'none', label: 'Belum Diatur (-)', checked: true },
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
                const root = this.$el.closest('[data-monitoring-root]') || this.$el;
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
            this.$watch('selectedMode', () => { this.currentPage = 1; this.evaluateRows(); });
            this.$watch('currentPage', () => { this.evaluateRows(); });
        },
        toggleDropdown(name) {
            this.activeDropdown = (this.activeDropdown === name) ? null : name;
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
        toggleAll(type, checked) {
            if (type === 'tahun') {
                this.allTahun.forEach(t => t.checked = checked);
            } else if (type === 'status') {
                this.allStatus.forEach(s => s.checked = checked);
            } else if (type === 'mode') {
                this.allMode.forEach(m => m.checked = checked);
            }
        },
        applyFilter(type) {
            if (type === 'tahun') {
                this.selectedTahun = this.allTahun.filter(t => t.checked).map(t => t.value);
            } else if (type === 'status') {
                this.selectedStatus = this.allStatus.filter(s => s.checked).map(s => s.value);
            } else if (type === 'mode') {
                this.selectedMode = this.allMode.filter(m => m.checked).map(m => m.value);
            }
            this.currentPage = 1;
            this.closeDropdowns();
            this.evaluateRows();
        },
        cancelFilter(type) {
            if (type === 'tahun') {
                this.allTahun.forEach(t => t.checked = this.selectedTahun.length === 0 || this.selectedTahun.includes(t.value));
            } else if (type === 'status') {
                this.allStatus.forEach(s => s.checked = this.selectedStatus.length === 0 || this.selectedStatus.includes(s.value));
            } else if (type === 'mode') {
                this.allMode.forEach(m => m.checked = this.selectedMode.length === 0 || this.selectedMode.includes(m.value));
            }
            this.closeDropdowns();
        },
        resetFilters() {
            this.search = '';
            this.selectedTahun = [];
            this.selectedStatus = [];
            this.selectedMode = [];
            this.allTahun.forEach(t => t.checked = true);
            this.allStatus.forEach(s => s.checked = true);
            this.allMode.forEach(m => m.checked = true);
            this.currentPage = 1;
            this.evaluateRows();
        },
        evaluateRows() {
            const root = this.$el.closest('[data-monitoring-root]') || this.$el;
            const rows = Array.from(root.querySelectorAll('tbody tr[data-tahun]'));
            const cards = Array.from(root.querySelectorAll('[data-cards-container] > div[data-tahun]'));
            
            const isMatch = (item) => {
                const tahun = item.getAttribute('data-tahun') || '';
                const name = item.getAttribute('data-name') || '';
                const status = item.getAttribute('data-status') || '';
                const mode = item.getAttribute('data-mode') || '';

                if (this.search) {
                    const q = this.search.toLowerCase().trim();
                    const matchSearch = tahun.toLowerCase().includes(q) || name.toLowerCase().includes(q);
                    if (!matchSearch) return false;
                }

                if (this.selectedTahun.length > 0 && !this.selectedTahun.includes(tahun)) return false;
                if (this.selectedStatus.length > 0 && !this.selectedStatus.includes(status)) return false;
                if (this.selectedMode.length > 0 && !this.selectedMode.includes(mode)) return false;

                return true;
            };

            const matchedRows = rows.filter(isMatch);
            const matchedCards = cards.filter(isMatch);
            this.totalRows = rows.length > 0 ? matchedRows.length : matchedCards.length;

            rows.forEach(r => r.style.display = 'none');
            matchedRows.slice(this.startIndex, this.endIndex).forEach(r => r.style.display = '');

            cards.forEach(c => c.style.display = 'none');
            matchedCards.slice(this.startIndex, this.endIndex).forEach(c => c.style.display = '');
        }
    }" @click.outside="closeDropdowns()">
        <x-tables.monitoring-table title="Daftar Periode Evaluasi Visitasi" :hasCards="true">
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
                        <span>Unduh Rekap</span>
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
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
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
                                        allTahun.forEach(t => {
                                            t.checked = selectedTahun.length === 0 || selectedTahun.includes(t.value);
                                        });
                                        currentPage = 1;
                                        evaluateRows();
                                    },
                                    clearAll() {
                                        selectedTahun = [];
                                        allTahun.forEach(t => t.checked = true);
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

                            {{-- Filter Mode --}}
                            <div class="bg-white border border-abubiru/60 rounded-2xl shadow-xs"
                                x-data="{
                                    isOpen: false,
                                    searchLocal: '',
                                    get triggerLabel() {
                                        if (selectedMode.length === 0) return 'Semua Mode';
                                        if (selectedMode.length === 1) {
                                            const f = allMode.find(m => m.value === selectedMode[0]);
                                            return f ? f.label : 'Semua Mode';
                                        }
                                        return selectedMode.length + ' mode dipilih';
                                    },
                                    get filtered() {
                                        const q = this.searchLocal.toLowerCase().trim();
                                        if (!q) return allMode;
                                        return allMode.filter(m => m.label.toLowerCase().includes(q));
                                    },
                                    toggle(val) {
                                        const idx = selectedMode.indexOf(val);
                                        if (idx >= 0) selectedMode.splice(idx, 1);
                                        else selectedMode.push(val);
                                        allMode.forEach(m => {
                                            m.checked = selectedMode.length === 0 || selectedMode.includes(m.value);
                                        });
                                        currentPage = 1;
                                        evaluateRows();
                                    },
                                    clearAll() {
                                        selectedMode = [];
                                        allMode.forEach(m => m.checked = true);
                                        this.searchLocal = '';
                                        currentPage = 1;
                                        evaluateRows();
                                    }
                                }">
                                <div class="bg-[#f0f5fa] px-3.5 py-2 border-b border-abubiru/40 flex items-center justify-between rounded-t-2xl">
                                    <span class="font-bold text-xs text-biru1">Filter Mode</span>
                                    <span class="bg-biru1 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full" x-text="selectedMode.length > 0 ? selectedMode.length + ' dipilih' : 'Semua'"></span>
                                </div>
                                <div class="p-2 relative">
                                    <button type="button" @click="isOpen = !isOpen"
                                        class="w-full flex items-center justify-between rounded-xl border border-abubiru/60 bg-slate-50/50 px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-biru2/20 transition"
                                        :class="selectedMode.length > 0 ? 'text-biru1' : 'text-biru3'">
                                        <span x-text="triggerLabel" class="truncate text-left"></span>
                                        <svg class="w-3.5 h-3.5 text-biru3 shrink-0 ml-2 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                                        class="absolute left-0 right-0 top-full mt-1 bg-white border border-abubiru rounded-xl shadow-xl p-2 z-100">
                                        <input type="text" placeholder="Cari mode..."
                                            x-model="searchLocal"
                                            class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                        <div @click="clearAll()"
                                            :class="selectedMode.length === 0 ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                            class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none mb-1 border-b border-abubiru/30 pb-2">
                                            <input type="checkbox" :checked="selectedMode.length === 0" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                            <span>Semua Mode</span>
                                        </div>
                                        <div class="max-h-48 overflow-y-auto space-y-0.5">
                                            <template x-for="item in filtered" :key="item.value">
                                                <div @click="toggle(item.value)"
                                                    :class="selectedMode.includes(item.value) ? 'bg-biru5/30 font-bold text-biru1' : 'text-slate-700 font-medium'"
                                                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition cursor-pointer select-none">
                                                    <input type="checkbox" :checked="selectedMode.includes(item.value)" class="w-3.5 h-3.5 rounded accent-biru2 pointer-events-none">
                                                    <span x-text="item.label" class="truncate flex-1"></span>
                                                </div>
                                            </template>
                                            <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Filter Status --}}
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
                                        allStatus.forEach(s => {
                                            s.checked = selectedStatus.length === 0 || selectedStatus.includes(s.value);
                                        });
                                        currentPage = 1;
                                        evaluateRows();
                                    },
                                    clearAll() {
                                        selectedStatus = [];
                                        allStatus.forEach(s => s.checked = true);
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
                            $visitasi = $sub ? $sub->visitasi : null;
                            $visitEnd = $p->visit_end ? \Carbon\Carbon::parse($p->visit_end)->endOfDay() : null;
                            $isVisitReleased = !$visitEnd || now()->greaterThan($visitEnd);
                            
                            $statusLabel = 'belum_dimulai';
                            $statusText = 'Belum Dimulai';
                            $statusBadge = 'bg-abubiru text-biru4';
                            $jadwalText = '—';
                            $nilaiText = '—';
                            $canOpen = false;

                            $modeVal = 'none';
                            if ($visitasi && $visitasi->visit_mode === 'online_upload') {
                                $modeVal = 'online_upload';
                            } elseif ($visitasi && ($visitasi->visit_at || $visitasi->evaluators->isNotEmpty()) && $visitasi->visit_mode === 'online_meeting') {
                                $modeVal = 'online_meeting';
                            } elseif ($visitasi && ($visitasi->visit_at || $visitasi->evaluators->isNotEmpty()) && ($visitasi->visit_mode === 'offline' || !$visitasi->visit_mode)) {
                                $modeVal = 'offline';
                            }

                            if ($sub) {
                                if ($status == 8) {
                                    $statusLabel = 'gugur';
                                    $statusText = 'Gugur';
                                    $statusBadge = 'bg-red-100 text-red-700 border border-red-300';
                                } elseif ($status >= 5) {
                                    $canOpen = true;
                                    if ($modeVal === 'online_upload') {
                                        $jadwalText = '-';
                                    } elseif ($visitasi && $visitasi->visit_at) {
                                        $jadwalText = \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('d M Y');
                                    } else {
                                        $jadwalText = 'Menunggu Jadwal';
                                    }

                                    if ($visitasi && $visitasi->visit_score !== null && $isVisitReleased) {
                                        $statusLabel = 'selesai';
                                        $statusText = 'Selesai';
                                        $statusBadge = 'bg-hijau2/10 text-hijau border border-hijau2/30';
                                        $nilaiText = number_format($visitasi->visit_score, 2);
                                    } else {
                                        $statusLabel = 'proses';
                                        $statusText = 'Proses';
                                        $statusBadge = 'bg-biru5/20 text-biru2 border border-biru5/40';
                                    }
                                }
                            }
                        @endphp
                        <div data-tahun="{{ $p->tahun }}" data-name="{{ $p->nama }}" data-status="{{ $statusLabel }}" data-mode="{{ $modeVal }}"
                            class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="font-bold text-biru1 text-sm leading-snug">{{ $p->nama }}</div>
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold shrink-0">
                                    {{ $p->tahun }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-2 pt-2 border-t border-abubiru/30 text-xs font-semibold text-biru3">
                                <div class="flex justify-between items-center gap-4">
                                    <span>Mode:</span>
                                    <span class="text-biru1 text-right">
                                        @if ($modeVal === 'online_upload')
                                            <span class="inline-flex items-center rounded-md bg-amber-500/10 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                                Upload Bukti
                                            </span>
                                        @elseif ($modeVal === 'online_meeting')
                                            <span class="inline-flex items-center rounded-md bg-sky-500/10 text-sky-700 px-2.5 py-0.5 text-xs font-medium">
                                                Online Meeting
                                            </span>
                                        @elseif ($modeVal === 'offline')
                                            <span class="inline-flex items-center rounded-md bg-emerald-500/10 text-emerald-700 px-2.5 py-0.5 text-xs font-medium">
                                                Offline
                                            </span>
                                        @else
                                            <span class="text-xs text-biru3 font-medium">-</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span>Jadwal Visitasi:</span>
                                    <span class="text-biru1 font-bold">{{ $jadwalText }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span>Status:</span>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide {{ $statusBadge }}">
                                        {{ $statusText }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span>Nilai Visitasi:</span>
                                    <span class="text-biru1 font-extrabold text-sm">{{ $nilaiText }}</span>
                                </div>
                            </div>
                            
                            @if ($canOpen)
                                <div class="flex items-center justify-end pt-2.5 border-t border-abubiru/30">
                                    <a href="{{ route($detailRouteName, ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                        wire:navigate.hover
                                        class="flex items-center gap-1.5 rounded-lg bg-biru2 border border-biru2/30 px-3 py-1.5 text-xs text-white hover:bg-biru2/20 hover:text-biru2 font-bold transition"
                                        title="Detail">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                            Tidak ada data periode visitasi.
                        </div>
                    @endforelse
                    <div x-show="totalRows === 0" x-cloak class="p-6 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs" style="display: none;">
                        Data tidak ditemukan.
                    </div>
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
                        <th class="px-8 py-5 text-center border-r border-abubiru/20">
                            <div class="relative inline-block text-left">
                                <div class="flex items-center justify-center gap-1.5 cursor-pointer" @click.stop="toggleDropdown('mode')">
                                    <span>Mode</span>
                                    <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.472 6.028A2.5 2.5 0 0017.5 5H6.5a2.5 2.5 0 00-1.972 4.028L9 14.25v5.5a1.25 1.25 0 001.956 1.03l2.5-1.667A1.25 1.25 0 0014 18.083v-3.833l4.472-5.222a2.5 2.5 0 001-2.028z" />
                                    </svg>
                                </div>
                                <div x-show="activeDropdown === 'mode'" @click.outside="closeDropdowns()"
                                    class="absolute left-1/2 -translate-x-1/2 top-full mt-1.5 z-40 bg-white border border-abubiru rounded-2xl shadow-xl w-48 p-3 text-biru1 font-medium normal-case text-left">
                                    <input type="text" placeholder="Cari..." x-model="modeSearch"
                                        class="w-full rounded-xl border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2 text-biru1">
                                    <label class="flex items-center gap-2 px-1.5 py-1 text-xs font-bold text-biru2 cursor-pointer mb-1.5">
                                        <input type="checkbox"
                                            :checked="allMode.length > 0 && allMode.every(m => m.checked)"
                                            @change="toggleAll('mode', $event.target.checked)"
                                            class="w-3.5 h-3.5 rounded accent-biru2">
                                        (Select All)
                                    </label>
                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                        <template x-for="item in allMode">
                                            <label x-show="modeSearch === '' || item.label.toLowerCase().includes(modeSearch.toLowerCase()) || item.value.toLowerCase().includes(modeSearch.toLowerCase())"
                                                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-biru5/20 cursor-pointer text-xs font-semibold">
                                                <input type="checkbox" x-model="item.checked"
                                                    class="w-3.5 h-3.5 rounded accent-biru2">
                                                <span x-text="item.label"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-3 pt-2.5 border-t border-abubiru">
                                        <button type="button" @click.stop="cancelFilter('mode')"
                                            class="px-3 py-1 text-xs font-bold border border-abubiru rounded-xl hover:bg-slate-50 transition text-biru1">Batal</button>
                                        <button type="button" @click.stop="applyFilter('mode')"
                                            class="px-3 py-1 text-xs font-black bg-hijau text-white rounded-xl hover:opacity-90 transition">OK</button>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Jadwal Visitasi</th>
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
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Nilai Visitasi</th>
                        <th class="px-8 py-5 text-center">Aksi</th>
                    </tr>
                </thead>
            </x-slot>

            @forelse ($periods as $p)
                @php
                    $sub = $submissions->get($p->id_periode);
                    $status = $sub ? $sub->status : -1;
                    $visitasi = $sub ? $sub->visitasi : null;
                    $visitEnd = $p->visit_end ? \Carbon\Carbon::parse($p->visit_end)->endOfDay() : null;
                    $isVisitReleased = !$visitEnd || now()->greaterThan($visitEnd);
                    
                    $statusLabel = 'belum_dimulai';
                    $jadwalText = '—';
                    $nilaiText = '—';
                    $canOpen = false;

                    $modeVal = 'none';
                    if ($visitasi && $visitasi->visit_mode === 'online_upload') {
                        $modeVal = 'online_upload';
                    } elseif ($visitasi && ($visitasi->visit_at || $visitasi->evaluators->isNotEmpty()) && $visitasi->visit_mode === 'online_meeting') {
                        $modeVal = 'online_meeting';
                    } elseif ($visitasi && ($visitasi->visit_at || $visitasi->evaluators->isNotEmpty()) && ($visitasi->visit_mode === 'offline' || !$visitasi->visit_mode)) {
                        $modeVal = 'offline';
                    }

                    if ($sub) {
                        if ($status == 8) {
                            $statusLabel = 'gugur';
                        } elseif ($status >= 5) {
                            $canOpen = true;
                            if ($modeVal === 'online_upload') {
                                $jadwalText = '-';
                            } elseif ($visitasi && $visitasi->visit_at) {
                                $jadwalText = \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('d M Y');
                            } else {
                                $jadwalText = 'Menunggu Jadwal';
                            }

                            if ($visitasi && $visitasi->visit_score !== null && $isVisitReleased) {
                                $statusLabel = 'selesai';
                                $nilaiText = number_format($visitasi->visit_score, 2);
                            } else {
                                $statusLabel = 'proses';
                            }
                        }
                    }
                    $isActive = ($p->periode_start <= now() && $p->periode_end >= now());
                    $rowBg = $isActive ? 'bg-kuning2/25 hover:bg-kuning2/35' : 'hover:bg-abubiru/10';
                @endphp
                <tr data-tahun="{{ $p->tahun }}" data-name="{{ $p->nama }}" data-status="{{ $statusLabel }}" data-mode="{{ $modeVal }}" class="border-t border-abubiru/30 transition {{ $rowBg }}">
                    <td class="px-8 py-5 text-sm font-bold text-biru1 border-r border-abubiru/10">
                        {{ $p->tahun }}
                    </td>
                    <td class="px-8 py-5 text-sm font-semibold text-biru2 border-r border-abubiru/10">
                        {{ $p->nama }}
                    </td>
                    <td class="px-8 py-5 text-center border-r border-abubiru/10">
                        @if ($modeVal === 'online_upload')
                            <span class="inline-flex items-center rounded-md bg-amber-500/10 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                Upload Bukti
                            </span>
                        @elseif ($modeVal === 'online_meeting')
                            <span class="inline-flex items-center rounded-md bg-sky-500/10 text-sky-700 px-2.5 py-0.5 text-xs font-medium">
                                Online Meeting
                            </span>
                        @elseif ($modeVal === 'offline')
                            <span class="inline-flex items-center rounded-md bg-emerald-500/10 text-emerald-700 px-2.5 py-0.5 text-xs font-medium">
                                Offline
                            </span>
                        @else
                            <span class="text-xs text-biru3 font-medium">-</span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-sm font-medium text-biru3 border-r border-abubiru/10">
                        {{ $jadwalText }}
                    </td>
                    <td class="px-8 py-5 text-center border-r border-abubiru/10">
                        <x-ui.status-badge :status="$statusLabel" />
                    </td>
                    <td class="px-8 py-5 text-sm font-black text-biru1 border-r border-abubiru/10">
                        {{ $nilaiText }}
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if ($canOpen)
                            <a href="{{ route($detailRouteName, ['id_periode' => \App\Helpers\SecureId::encrypt($p->id_periode)]) }}"
                                wire:navigate.hover
                                class="inline-flex rounded-xl bg-biru2 p-2 text-white shadow-sm hover:opacity-90 transition"
                                title="Detail">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        @else
                            <span class="text-sm font-semibold text-biru4">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr x-show="false">
                    <td colspan="7" class="py-8 px-6 text-center font-medium text-biru3">Tidak ada data periode penilaian tersedia.</td>
                </tr>
            @endforelse
            <tr x-show="totalRows === 0" x-cloak style="display: none;">
                <td colspan="7" class="py-8 px-6 text-center font-medium text-biru3">Data tidak ditemukan.</td>
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

        <!-- Modal Export Visitasi Excel -->
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
                            <h3 class="font-bold text-base">Unduh Rekap Visitasi Excel</h3>
                            <p class="text-white/80 text-xs">Pilih tahun periode visitasi</p>
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
                        $validVisitasiPeriods = $periods->filter(function($p) use ($submissions) {
                            $sub = $submissions->get($p->id_periode);
                            return $sub && $sub->status >= 5 && $sub->visitasi;
                        });
                    @endphp
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-biru1 mb-2">Tahun Periode Visitasi</label>
                        @if ($validVisitasiPeriods->isNotEmpty())
                            <select name="tahun" class="w-full rounded-xl border border-abubiru bg-putihbg px-4 py-2.5 text-sm font-semibold text-biru1 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                                @foreach ($validVisitasiPeriods as $p)
                                    <option value="{{ $p->tahun }}">
                                        Periode {{ $p->tahun }} ({{ $p->nama }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="tahun" disabled class="w-full rounded-xl border border-abubiru bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed">
                                <option value="" disabled selected>— Tidak ada periode Visitasi yang siap diunduh —</option>
                            </select>
                        @endif
                        <p class="text-xs text-biru3 mt-2 leading-relaxed">
                            <span class="font-bold text-biru1">Info:</span> File Excel dapat diunduh jika unit kerja Anda telah lolos ke tahap Visitasi & Verifikasi Faktual.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-abubiru">
                        <button type="button" @click="showExportModal = false" class="px-4 py-2.5 rounded-xl border border-abubiru text-biru1 font-semibold text-xs bg-white hover:bg-slate-50 transition">
                            Batal
                        </button>
                        <button type="submit" @click="showExportModal = false" @if($validVisitasiPeriods->isEmpty()) disabled @endif class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-hijau text-white font-bold text-xs hover:opacity-90 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
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
