@php
    $currentUserRole = auth()->user()?->role?->nama_role;
    $canEditDrive = in_array($currentUserRole, ['super_evaluator', 'evaluator', 'admin']);
    $initialSatker = collect($satkers)->firstWhere('kode_satker', $chartKabkotKode) ?? collect($satkers)->first();
    $initialSatkerName = $initialSatker
        ? $initialSatker->nama_satker . ' (' . $initialSatker->kode_satker . ')'
        : 'Pilih Kab/Kota';
@endphp

<div x-data="{
    activeTab: 'histogram',
    switchTab(tab) {
        if (this.activeTab === tab) return;
        this.activeTab = tab;
        this.$nextTick(() => {
            window.dispatchEvent(new Event('resize'));
            if (tab === 'peta') {
                if (window.mySaqMap) {
                    setTimeout(() => { window.mySaqMap.invalidateSize(); }, 80);
                } else if (typeof window.initSaqMap === 'function') {
                    setTimeout(() => { window.initSaqMap(); }, 80);
                }
            }
            if (tab === 'histogram') {
                if (window._saqHistogramInstance) {
                    setTimeout(() => { window._saqHistogramInstance.resize(); }, 80);
                } else if (typeof window.initSaqHistogram === 'function') {
                    setTimeout(() => { window.initSaqHistogram(); }, 80);
                }
            }
        });
    },
    refreshCharts() {
        this.$nextTick(() => {
            setTimeout(() => {
                if (window._saqSelectedKabkotName) {
                    const activeLabel = document.getElementById('saq-line-chart-active-satker');
                    if (activeLabel) activeLabel.textContent = window._saqSelectedKabkotName;
                }
                if (typeof window.refreshSaqCharts === 'function') {
                    window.refreshSaqCharts();
                } else {
                    if (typeof window.initSaqHistogram === 'function') window.initSaqHistogram();
                    if (typeof window.initSaqMap === 'function') window.initSaqMap();
                }
            }, 60);
        });
    }
}"
@saq-data-updated.window="refreshCharts()"
class="mb-6 space-y-6">

    <!-- Top Filter Bar: Filter Tahun & Status Summary -->
    <div class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Filter Tahun -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-biru1/10 text-biru1 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-biru1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <label for="filter-tahun-saq" class="block text-xs font-bold text-biru3 uppercase tracking-wider">
                    Periode / Tahun Evaluasi
                </label>
                <div class="flex items-center gap-2.5 mt-1">
                    <div class="relative inline-flex items-center">
                        <select id="filter-tahun-saq" wire:model.live="selectedTahun"
                            class="appearance-none rounded-xl border border-abubiru bg-putihbg pl-3.5 pr-7.5 py-1.5 text-xs sm:text-sm font-extrabold text-biru1 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20 transition cursor-pointer w-32">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}">Tahun {{ $year }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-biru1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Loading Indicator Persis di Sebelah Kanan Dropdown -->
                    <span wire:loading wire:target="selectedTahun" class="inline-flex items-center gap-1.5 text-xs font-bold text-biru2 animate-pulse whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 animate-spin text-biru2 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memuat data...</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Summary Status Chips -->
        <div wire:loading.class="opacity-60" wire:target="selectedTahun" class="flex items-center gap-2 flex-wrap sm:justify-end transition-opacity duration-200">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-hijau/10 text-hijau border border-hijau/20 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-hijau"></span>
                Lolos: <strong class="font-black">{{ $countLolos }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-merah2/10 text-merah2 border border-merah2/20 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-merah2"></span>
                Gugur: <strong class="font-black">{{ $countGugur }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-abumuda/40 text-slate-600 border border-abumuda shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-abumuda"></span>
                Belum Dinilai: <strong class="font-black">{{ $countBelum }}</strong>
            </span>
        </div>
    </div>

    <!-- Banner Google Drive Review SAQ (Di atas chart, di bawah filter) -->
    @include('livewire.evaluasi.partials.banner-drive-review')

    <!-- Mobile Segmented Tab Switcher (< xl screens) -->
    <div class="xl:hidden bg-slate-100/90 p-1.5 rounded-2xl border border-abubiru/70 flex items-center gap-1.5 shadow-2xs">
        <button type="button" @click="switchTab('histogram')"
            :class="activeTab === 'histogram' ? 'bg-white text-biru1 shadow-xs font-black' : 'text-biru3 hover:text-biru1 font-bold'"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs transition-all duration-200 cursor-pointer select-none">
            <svg class="w-4 h-4 text-biru2 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="truncate">Skor per Indikator</span>
        </button>

        <button type="button" @click="switchTab('peta')"
            :class="activeTab === 'peta' ? 'bg-white text-biru1 shadow-xs font-black' : 'text-biru3 hover:text-biru1 font-bold'"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs transition-all duration-200 cursor-pointer select-none">
            <svg class="w-4 h-4 text-biru2 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <span class="truncate">Peta Lolos/Gugur SAQ</span>
        </button>
    </div>

    <!-- Cards Grid (50:50 on Desktop xl, Tabbed on Mobile) -->
    <div wire:loading.class="opacity-60 pointer-events-none" wire:target="selectedTahun" class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-stretch transition-opacity duration-200">

        <!-- Card 1: Histogram per Indikator -->
        <div id="card-perbandingan"
            x-show="activeTab === 'histogram'"
            x-cloak
            class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 overflow-hidden flex flex-col justify-between relative text-left xl:flex!">

            <!-- Data Bridge -->
            <div id="saq-chart-data-bridge"
                data-indicators="{{ json_encode($indicators) }}"
                data-indicator-scores-per-kabkot="{{ json_encode($indicatorScoresPerKabkot) }}"
                data-chart-kabkot-kode="{{ $chartKabkotKode }}" style="display: none;"></div>

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-base font-extrabold text-biru2 truncate">
                        Skor SAQ per Indikator (<span id="saq-line-chart-active-satker" class="text-biru2 font-black">{{ $initialSatkerName }}</span>)
                    </h3>
                </div>

                <!-- Tombol Salin Gambar -->
                <button id="btn-copy-perbandingan" onclick="copySaqPerbandinganCard(this)" title="Salin sebagai gambar"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 select-none cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="copy-btn-label hidden sm:inline">Salin Gambar</span>
                    <span class="copy-btn-label sm:hidden">Salin</span>
                </button>
            </div>

            <!-- Filter Kab/Kota Searchable Dropdown -->
            <div class="flex flex-row items-center justify-between gap-3 mb-4 pt-3 border-t border-slate-100/80">
                <div class="w-full" x-data="{
                    open: false,
                    searchSatker: '',
                    selectedKode: '{{ $chartKabkotKode }}',
                    selectedName: '{{ addslashes($initialSatkerName) }}',
                    init() {
                        if (window._saqSelectedKabkotKode && window._saqSelectedKabkotName) {
                            this.selectedKode = window._saqSelectedKabkotKode;
                            this.selectedName = window._saqSelectedKabkotName;
                        } else {
                            window._saqSelectedKabkotKode = this.selectedKode;
                            window._saqSelectedKabkotName = this.selectedName;
                        }
                    },
                    selectSatker(kode, name) {
                        this.selectedKode = kode;
                        this.selectedName = name;
                        this.open = false;
                        window._saqSelectedKabkotKode = kode;
                        window._saqSelectedKabkotName = name;
                        const activeLabel = document.getElementById('saq-line-chart-active-satker');
                        if (activeLabel) activeLabel.textContent = name;
                        if (typeof window.updateSaqHistogramForKabkot === 'function') {
                            window.updateSaqHistogramForKabkot(kode);
                        }
                    }
                }">
                    <div class="relative w-full">
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 rounded-xl border border-abubiru bg-white shadow-2xs px-3.5 py-2 text-xs text-biru1 font-bold hover:bg-slate-50 transition cursor-pointer select-none">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-biru3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="truncate" x-text="selectedName"></span>
                            </div>
                            <svg class="w-4 h-4 text-biru3 shrink-0 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white border border-abubiru rounded-2xl shadow-xl p-3 text-biru1 font-medium text-left">
                            <div class="relative mb-2">
                                <input type="text" placeholder="Cari Kab/Kota..." x-model="searchSatker" @click.stop
                                    class="w-full rounded-xl border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-xs text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20 transition">
                                <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="max-h-52 overflow-y-auto space-y-1 pr-1">
                                @foreach ($satkers as $satker)
                                    <div x-show="searchSatker === '' || '{{ strtolower(addslashes($satker->nama_satker)) }}'.includes(searchSatker.toLowerCase()) || '{{ $satker->kode_satker }}'.includes(searchSatker)"
                                        @click="selectSatker('{{ $satker->kode_satker }}', '{{ addslashes($satker->nama_satker) }} ({{ $satker->kode_satker }})')"
                                        class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl cursor-pointer text-xs font-semibold transition select-none"
                                        :class="selectedKode === '{{ $satker->kode_satker }}' ? 'bg-biru5/30 text-biru2 font-black' : 'hover:bg-biru5/20 text-biru1'">
                                        <span>{{ $satker->nama_satker }} ({{ $satker->kode_satker }})</span>
                                        <template x-if="selectedKode === '{{ $satker->kode_satker }}'">
                                            <svg class="w-4 h-4 text-biru2 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-72.5 sm:h-90">
                <div wire:ignore id="perbandingan-list-chart-saq" data-echarts="true" style="width: 100%; height: 100%; min-height: 270px;" class="absolute inset-0"></div>
            </div>

            <!-- Legend Predikat Indikator -->
            <div class="flex flex-col items-center gap-y-2 text-xs font-bold text-biru4 mt-4 border-t border-slate-100 pt-4 pb-1">
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-hijau shrink-0"></span> Informatif (90 - 100)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-hijauneon shrink-0"></span> Menuju Informatif (80 - 89,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-biru4 shrink-0"></span> Cukup Informatif (60 - 79,9)</span>
                </div>
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-kuning1 shrink-0"></span> Kurang Informatif (40 - 59,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-merah2 shrink-0"></span> Tidak Informatif (&lt; 39,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-abumuda shrink-0"></span> Belum Selesai Dinilai (0)</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Peta Sebaran Lolos/Gugur SAQ Jawa Timur -->
        <div id="card-peta"
            x-show="activeTab === 'peta'"
            x-cloak
            class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 flex flex-col justify-between text-left xl:flex!">

            <!-- Data Bridge -->
            <div id="saq-map-data-bridge"
                data-region-scores="{{ json_encode($regionScores) }}"
                data-satker-scores="{{ json_encode($satkerScores) }}" style="display: none;"></div>

            <!-- Header -->
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-base font-extrabold text-biru2 truncate">Peta Sebaran Lolos/Gugur SAQ Jawa Timur</h3>
                    <p class="text-xs text-biru3 mt-0.5">
                        <span class="hidden sm:inline">Arahkan kursor ke wilayah untuk melihat skor SAQ dan status kelulusan</span>
                        <span class="sm:hidden">Ketuk wilayah pada peta untuk melihat detail skor & status</span>
                    </p>
                </div>
                <button id="btn-copy-peta" onclick="copySaqPetaCard(this)" title="Salin sebagai gambar"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0 text-biru2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="copy-btn-label hidden sm:inline">Salin Gambar</span>
                    <span class="copy-btn-label sm:hidden">Salin</span>
                </button>
            </div>

            <!-- Map Container -->
            <div class="relative bg-putihbg border border-abubiru rounded-xl p-2 sm:p-4 overflow-hidden h-72.5 sm:h-90 flex items-center justify-center map-container">
                <div wire:ignore id="map-sebaran-saq" class="w-full h-full rounded-lg" style="min-height: 270px; z-index: 1;"></div>
            </div>

            <!-- Legend Status Lolos / Gugur SAQ (Sesuai app.css) -->
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs font-bold mt-4 border-t border-slate-100 pt-4 pb-1">
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-hijau shrink-0"></span>
                    <span class="text-slate-700">Lolos (Nilai SAQ &ge; 80)</span>
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-merah2 shrink-0"></span>
                    <span class="text-slate-700">Gugur (Nilai SAQ &lt; 80)</span>
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-abumuda shrink-0"></span>
                    <span class="text-slate-500">Belum Dinilai</span>
                </span>
            </div>
        </div>
    </div>

</div>

{{-- Scripts for Histogram, Mapchart, and Card Exporter --}}
@push('scripts')
<script>
(function() {
    // -------------------------------------------------------------
    // Helper: Map Predikat Color for Indicators
    // -------------------------------------------------------------
    function getIndikatorColor(score) {
        var s = parseFloat(score || 0);
        if (s <= 0) return '#D1D5D8'; // abumuda jika 0 / belum selesai dinilai
        if (s >= 90) return '#388E3C'; // hijau
        if (s >= 80) return '#68B92E'; // hijauneon
        if (s >= 60) return '#4C84B0'; // biru4
        if (s >= 40) return '#F7B750'; // kuning1
        return '#E82D1F';              // merah2
    }

    // -------------------------------------------------------------
    // 1. Histogram SAQ per Indikator
    // -------------------------------------------------------------
    window._saqHistogramInstance = null;
    window._saqHistoObserver = null;

    function initSaqHistogram() {
        var dom = document.getElementById('perbandingan-list-chart-saq');
        if (!dom) return;

        // Auto-observe size changes (e.g. when tab switches or screen rotates)
        if (!window._saqHistoObserver && window.ResizeObserver) {
            window._saqHistoObserver = new ResizeObserver(function(entries) {
                for (var entry of entries) {
                    if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
                        if (!window._saqHistogramInstance) {
                            initSaqHistogram();
                        } else {
                            window._saqHistogramInstance.resize();
                        }
                    }
                }
            });
            window._saqHistoObserver.observe(dom);
        }

        // If container width is 0 (hidden tab), wait until visible
        if (dom.offsetWidth === 0 && dom.offsetHeight === 0) {
            return;
        }

        var echartsObj = window.echarts || (typeof echarts !== 'undefined' ? echarts : null);
        if (echartsObj && !echartsObj.init && echartsObj.default) echartsObj = echartsObj.default;
        if (!echartsObj || !echartsObj.init) {
            if (window.loadECharts) {
                window.loadECharts().then(initSaqHistogram);
            }
            return;
        }

        var bridge = document.getElementById('saq-chart-data-bridge');
        if (!bridge) return;

        var indicators = [];
        var scoresMap = {};
        var activeKode = window._saqSelectedKabkotKode || bridge.getAttribute('data-chart-kabkot-kode') || '3501';

        try {
            indicators = JSON.parse(bridge.getAttribute('data-indicators') || '[]');
            var allScores = JSON.parse(bridge.getAttribute('data-indicator-scores-per-kabkot') || '{}');
            scoresMap = allScores[activeKode] || allScores[String(activeKode)] || allScores[Number(activeKode)] || {};
        } catch(e) {
            console.error('Error parsing SAQ histogram data:', e);
        }

        var chart = window._saqHistogramInstance;
        if (!chart) {
            chart = echartsObj.init(dom);
            window._saqHistogramInstance = chart;
        }

        var categories = [];
        var seriesData = [];

        indicators.forEach(function(ind) {
            var label = ind.nama_indikator || ('Indikator ' + ind.id_indicators);
            if (label.length > 25) {
                label = label.substring(0, 22) + '...';
            }
            categories.push(label);
            var score = parseFloat(scoresMap[ind.id_indicators] || 0);
            seriesData.push({
                value: score,
                fullName: ind.nama_indikator || label,
                itemStyle: {
                    color: getIndikatorColor(score),
                    borderRadius: [6, 6, 0, 0]
                }
            });
        });

        var option = {
            grid: {
                top: 25,
                left: '3%',
                right: '4%',
                bottom: '12%',
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function(params) {
                    var p = params[0];
                    var scoreText = p.value > 0 ? (p.value.toFixed(2) + '%') : '0.00% (Belum Selesai Dinilai)';
                    return '<div style="font-weight: bold; margin-bottom: 4px;">' + (p.data.fullName || p.name) + '</div>' +
                           'Skor SAQ: <span style="font-weight: 800; color:' + p.color + ';">' + scoreText + '</span>';
                }
            },
            xAxis: {
                type: 'category',
                data: categories,
                axisLabel: {
                    interval: 0,
                    rotate: 20,
                    fontSize: 10,
                    color: '#4987A0',
                    fontWeight: 600
                },
                axisTick: { show: false },
                axisLine: { lineStyle: { color: '#E3E9ED' } }
            },
            yAxis: {
                type: 'value',
                max: 100,
                axisLabel: {
                    formatter: '{value}%',
                    fontSize: 10,
                    color: '#737780'
                },
                splitLine: { lineStyle: { color: '#F3F8FB', type: 'dashed' } }
            },
            series: [{
                name: 'Skor SAQ',
                type: 'bar',
                barWidth: '40%',
                data: seriesData,
                animationDuration: 400,
                animationEasing: 'cubicOut'
            }]
        };

        chart.setOption(option, true);
    }
    window.initSaqHistogram = initSaqHistogram;

    window.updateSaqHistogramForKabkot = function(kode) {
        window._saqSelectedKabkotKode = kode;
        var bridge = document.getElementById('saq-chart-data-bridge');
        if (bridge) {
            bridge.setAttribute('data-chart-kabkot-kode', kode);
        }
        initSaqHistogram();
    };

    // -------------------------------------------------------------
    // 2. Peta Sebaran Lolos / Gugur SAQ (Leaflet)
    // -------------------------------------------------------------
    window.mySaqMap = null;
    window._saqGeojsonLayer = null;
    window._saqMapObserver = null;

    function renderSaqGeojson(map, data) {
        if (!map || !data) return;

        if (window._saqGeojsonLayer) {
            try { map.removeLayer(window._saqGeojsonLayer); } catch(e) {}
            window._saqGeojsonLayer = null;
        }

        var bridge = document.getElementById('saq-map-data-bridge');
        var satkerScores = {};
        if (bridge) {
            try {
                satkerScores = JSON.parse(bridge.getAttribute('data-satker-scores') || '{}');
            } catch(e) {}
        }

        var geojsonLayer = L.geoJSON(data, {
            style: function(feature) {
                var rawName = feature.properties.NAME_2 || '';
                var typeName = feature.properties.TYPE_2 || '';
                var normName = rawName.trim();
                if (typeName === 'Kota') {
                    normName = normName.startsWith('Kota') ? normName : ('Kota ' + normName);
                } else if (typeName === 'Kabupaten') {
                    normName = normName.startsWith('Kabupaten') ? normName : ('Kabupaten ' + normName);
                }
                var cleanName = rawName.replace(/Kabupaten|Kota/g, '').trim();

                var satkerData = satkerScores[normName] || satkerScores[rawName] || satkerScores[cleanName] ||
                                 satkerScores['BPS ' + normName] || satkerScores['BPS ' + rawName] || satkerScores['BPS ' + cleanName];

                if (!satkerData) {
                    var lowerNorm = normName.toLowerCase();
                    var lowerClean = cleanName.toLowerCase();
                    for (var sKey in satkerScores) {
                        var kLow = sKey.toLowerCase();
                        if (kLow === lowerNorm || kLow === lowerClean || kLow === ('bps ' + lowerNorm) || kLow === ('bps ' + lowerClean)) {
                            satkerData = satkerScores[sKey];
                            break;
                        }
                    }
                }

                // Default warna: abumuda (#D1D5D8)
                var fillColor = satkerData ? satkerData.color : '#D1D5D8';

                return {
                    fillColor: fillColor,
                    weight: 1.5,
                    opacity: 1,
                    color: 'white',
                    fillOpacity: 0.8
                };
            },
            onEachFeature: function(feature, layer) {
                var rawName = feature.properties.NAME_2 || '';
                var typeName = feature.properties.TYPE_2 || '';
                var normName = rawName.trim();
                if (typeName === 'Kota') {
                    normName = normName.startsWith('Kota') ? normName : ('Kota ' + normName);
                } else if (typeName === 'Kabupaten') {
                    normName = normName.startsWith('Kabupaten') ? normName : ('Kabupaten ' + normName);
                }
                var cleanName = rawName.replace(/Kabupaten|Kota/g, '').trim();

                var satkerData = satkerScores[normName] || satkerScores[rawName] || satkerScores[cleanName] ||
                                 satkerScores['BPS ' + normName] || satkerScores['BPS ' + rawName] || satkerScores['BPS ' + cleanName];

                if (!satkerData) {
                    var lowerNorm = normName.toLowerCase();
                    var lowerClean = cleanName.toLowerCase();
                    for (var sKey in satkerScores) {
                        var kLow = sKey.toLowerCase();
                        if (kLow === lowerNorm || kLow === lowerClean || kLow === ('bps ' + lowerNorm) || kLow === ('bps ' + lowerClean)) {
                            satkerData = satkerScores[sKey];
                            break;
                        }
                    }
                }

                var scoreVal = satkerData ? parseFloat(satkerData.score || 0).toFixed(2) : '0.00';
                var statusVal = satkerData ? (satkerData.status || 'Belum Dinilai') : 'Belum Dinilai';
                var colorVal = satkerData ? (satkerData.color || '#D1D5D8') : '#D1D5D8';

                var tooltipContent = `
                    <div class="font-sans text-xs">
                        <div class="font-extrabold text-sm mb-1 text-slate-800">${normName}</div>
                        <div class="flex items-center justify-between mb-1.5 gap-4">
                            <span class="text-slate-500 font-medium">Skor SAQ:</span>
                            <span class="font-black text-sm" style="color: ${colorVal};">${scoreVal}</span>
                        </div>
                        <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold text-white uppercase tracking-wider" style="background-color: ${colorVal};">
                            ${statusVal}
                        </div>
                    </div>
                `;

                layer.bindTooltip(tooltipContent, {
                    className: 'custom-tooltip',
                    direction: 'auto',
                    sticky: true,
                    opacity: 1
                });

                layer.on({
                    mouseover: function(e) {
                        e.target.setStyle({ weight: 2.5, color: '#063051', fillOpacity: 0.95 });
                    },
                    mouseout: function(e) {
                        geojsonLayer.resetStyle(e.target);
                    }
                });
            }
        }).addTo(map);

        window._saqGeojsonLayer = geojsonLayer;
    }
    window.renderSaqGeojson = renderSaqGeojson;

    window.refreshSaqCharts = function() {
        if (typeof initSaqHistogram === 'function') {
            initSaqHistogram();
        }
        if (window.mySaqMap && window._eastJavaGeojson) {
            renderSaqGeojson(window.mySaqMap, window._eastJavaGeojson);
        } else if (typeof initSaqMap === 'function') {
            initSaqMap();
        }
    };

    function initSaqMap() {
        var container = document.getElementById('map-sebaran-saq');
        if (!container) return;

        // Auto-observe size changes (e.g. when tab switches or screen rotates)
        if (!window._saqMapObserver && window.ResizeObserver) {
            window._saqMapObserver = new ResizeObserver(function(entries) {
                for (var entry of entries) {
                    if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
                        if (!window.mySaqMap) {
                            initSaqMap();
                        } else {
                            window.mySaqMap.invalidateSize();
                        }
                    }
                }
            });
            window._saqMapObserver.observe(container);
        }

        // Wait if Leaflet not ready yet
        if (typeof L === 'undefined') {
            setTimeout(initSaqMap, 200);
            return;
        }

        // If container width is 0 (hidden tab), wait until visible
        if (container.offsetWidth === 0 && container.offsetHeight === 0) {
            return;
        }

        if (window.mySaqMap) {
            if (window.mySaqMap.getContainer() === container) {
                try {
                    window.mySaqMap.invalidateSize();
                    if (window._eastJavaGeojson) {
                        renderSaqGeojson(window.mySaqMap, window._eastJavaGeojson);
                    }
                    return;
                } catch(e) {}
            }
            try { window.mySaqMap.remove(); } catch(e) {}
            window.mySaqMap = null;
        } else if (container._leaflet_id) {
            container._leaflet_id = null;
        }

        var map = L.map('map-sebaran-saq', {
            preferCanvas: true,
            zoomControl: true,
            scrollWheelZoom: false,
            attributionControl: false
        }).setView([-7.5360, 112.2384], 8);

        window.mySaqMap = map;

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 16,
            crossOrigin: true
        }).addTo(map);

        if (window._eastJavaGeojson) {
            renderSaqGeojson(map, window._eastJavaGeojson);
        } else {
            fetch('/data/east-java-districts.geojson')
                .then(res => res.json())
                .then(data => {
                    window._eastJavaGeojson = data;
                    renderSaqGeojson(map, data);
                })
                .catch(err => console.error('Error fetching geojson:', err));
        }
    }
    window.initSaqMap = initSaqMap;

    // -------------------------------------------------------------
    // 3. Card Exporter (Salin Gambar)
    // -------------------------------------------------------------
    function _drawRRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    window.copySaqPerbandinganCard = function(btn) {
        var label = btn ? btn.querySelector('.copy-btn-label') : null;
        if (label) label.textContent = 'Menyalin...';
        if (btn) btn.disabled = true;

        var chartInst = window._saqHistogramInstance;
        if (!chartInst) {
            if (label) label.textContent = 'Gagal!';
            if (btn) btn.disabled = false;
            setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
            return;
        }

        var activeSatkerEl = document.getElementById('saq-line-chart-active-satker');
        var satkerName = activeSatkerEl ? activeSatkerEl.textContent.trim() : '';
        var fullTitle = satkerName ? 'Skor SAQ per Indikator (' + satkerName + ')' : 'Skor SAQ per Indikator';

        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
            try {
                var clipboardPromise = new Promise(function(resolve, reject) {
                    chartInst.resize({ width: 772, height: 260, silent: true });
                    var chartDataUrl = chartInst.getDataURL({ type: 'png', pixelRatio: 2, backgroundColor: '#ffffff' });
                    chartInst.resize({ width: 'auto', height: 'auto', silent: true });

                    var img = new Image();
                    img.onload = function() {
                        var SC = 2;
                        var W = 820;
                        var PAD = 24;
                        var HEADER_H = 48;
                        var CHART_H = 260;
                        var LEGEND_H = 64;
                        var H = PAD + HEADER_H + CHART_H + LEGEND_H + PAD;

                        var canvas = document.createElement('canvas');
                        canvas.width = W * SC;
                        canvas.height = H * SC;
                        var ctx = canvas.getContext('2d');
                        ctx.scale(SC, SC);

                        // Card bg
                        ctx.fillStyle = '#ffffff';
                        _drawRRect(ctx, 0, 0, W, H, 14);
                        ctx.fill();
                        ctx.strokeStyle = '#e2e8f0';
                        ctx.lineWidth = 1;
                        _drawRRect(ctx, 0.5, 0.5, W - 1, H - 1, 14);
                        ctx.stroke();

                        // Header Title
                        var hMid = PAD + HEADER_H / 2;
                        ctx.fillStyle = '#184D77';
                        ctx.font = '800 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.textBaseline = 'middle';
                        ctx.textAlign = 'left';
                        ctx.fillText(fullTitle, PAD, hMid);

                        // Chart Image
                        var chartY = PAD + HEADER_H;
                        ctx.drawImage(img, PAD, chartY, W - PAD * 2, CHART_H);

                        // Legend Rows
                        var legends = [
                            { text: 'Informatif (90 - 100)', color: '#388E3C' },
                            { text: 'Menuju Informatif (80 - 89,9)', color: '#68B92E' },
                            { text: 'Cukup Informatif (60 - 79,9)', color: '#4C84B0' }
                        ];
                        var legends2 = [
                            { text: 'Kurang Informatif (40 - 59,9)', color: '#F7B750' },
                            { text: 'Tidak Informatif (< 39,9)', color: '#E82D1F' }
                        ];

                        var legY1 = chartY + CHART_H + 20;
                        var legY2 = legY1 + 24;
                        ctx.font = '700 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.textBaseline = 'middle';
                        ctx.textAlign = 'left';

                        var gapX = 24;
                        function drawLegendRow(rowLegends, yPos) {
                            var totalWidth = 0;
                            var itemWidths = rowLegends.map(function(l) {
                                var w = 12 + 6 + ctx.measureText(l.text).width;
                                totalWidth += w;
                                return w;
                            });
                            totalWidth += gapX * (rowLegends.length - 1);
                            var currentX = (W - totalWidth) / 2;

                            rowLegends.forEach(function(l, i) {
                                ctx.fillStyle = l.color;
                                ctx.beginPath();
                                ctx.arc(currentX + 6, yPos, 6, 0, Math.PI * 2);
                                ctx.fill();

                                ctx.fillStyle = '#475569';
                                ctx.fillText(l.text, currentX + 18, yPos);
                                currentX += itemWidths[i] + gapX;
                            });
                        }

                        drawLegendRow(legends, legY1);
                        drawLegendRow(legends2, legY2);

                        canvas.toBlob(function(blob) {
                            if (blob) resolve(blob);
                            else reject(new Error('Canvas toBlob failed'));
                        }, 'image/png');
                    };
                    img.onerror = function() { reject(new Error('Chart image load failed')); };
                    img.src = chartDataUrl;
                });

                var item = new ClipboardItem({ 'image/png': clipboardPromise });
                navigator.clipboard.write([item]).then(function() {
                    if (label) label.textContent = '✓ Tersalin!';
                    if (btn) btn.style.color = '#388E3C';
                    setTimeout(function() {
                        if (label) label.textContent = 'Salin Gambar';
                        if (btn) { btn.style.color = ''; btn.disabled = false; }
                    }, 2500);
                }).catch(function(err) {
                    console.error('Clipboard write error:', err);
                    if (label) label.textContent = 'Gagal!';
                    if (btn) btn.disabled = false;
                    setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
                });
            } catch(e) {
                console.error(e);
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
            }
        }
    };

    window.copySaqPetaCard = function(btn) {
        var label = btn ? btn.querySelector('.copy-btn-label') : null;
        if (label) label.textContent = 'Menyalin...';
        if (btn) btn.disabled = true;

        var mapContainer = document.getElementById('map-sebaran-saq');
        if (!mapContainer || typeof html2canvas === 'undefined') {
            if (label) label.textContent = 'Gagal!';
            if (btn) btn.disabled = false;
            setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
            return;
        }

        var zoomControls = mapContainer.querySelectorAll('.leaflet-control-container');
        zoomControls.forEach(function(el) { el.style.display = 'none'; });

        function tryDownload(blob) {
            try {
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'peta-sebaran-saq-jatim.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                if (label) label.textContent = '✓ Terunduh!';
                if (btn) btn.style.color = '#388E3C';
                setTimeout(function() {
                    if (label) label.textContent = 'Salin Gambar';
                    if (btn) { btn.style.color = ''; btn.disabled = false; }
                }, 2500);
            } catch(e) {
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
            }
        }

        html2canvas(mapContainer, {
            backgroundColor: '#f8fafc',
            scale: 2,
            useCORS: true,
            allowTaint: false,
            logging: false,
            imageTimeout: 5000,
            onclone: function(clonedDoc) {
                // Resolve computed styles for CSS vars without oklch
                if (typeof window._resolveCSSVars === 'function') {
                    window._resolveCSSVars(clonedDoc);
                } else {
                    try {
                        var computed = window.getComputedStyle(document.documentElement);
                        var sheet = clonedDoc.createElement('style');
                        var vars = [];
                        for (var i = 0; i < computed.length; i++) {
                            var prop = computed[i];
                            if (prop.startsWith('--')) {
                                var val = computed.getPropertyValue(prop);
                                if (val && val.indexOf('oklch') === -1) {
                                    vars.push(prop + ':' + val);
                                }
                            }
                        }
                        sheet.textContent = ':root{' + vars.join(';') + '}';
                        clonedDoc.head.appendChild(sheet);
                    } catch(e) {}
                }

                // Correct Leaflet tile transform offsets (matrix, matrix3d, translate3d)
                clonedDoc.querySelectorAll('#map-sebaran-saq *').forEach(function(el) {
                    var tag = el.tagName.toLowerCase();
                    if (tag === 'path' || tag === 'g') return;
                    var computedStyle = window.getComputedStyle(el);
                    var transform = el.style.transform || computedStyle.transform;
                    if (transform && transform !== 'none') {
                        var x = 0, y = 0, isMatch = false;
                        var matrix3d = transform.match(/^matrix3d\((.+)\)$/);
                        if (matrix3d) {
                            var values3d = matrix3d[1].split(', ');
                            x = parseFloat(values3d[12]) || 0;
                            y = parseFloat(values3d[13]) || 0;
                            isMatch = true;
                        } else {
                            var matrix = transform.match(/^matrix\((.+)\)$/);
                            if (matrix) {
                                var values = matrix[1].split(', ');
                                x = parseFloat(values[4]) || 0;
                                y = parseFloat(values[5]) || 0;
                                isMatch = true;
                            }
                        }
                        if (isMatch) {
                            var currentLeft = parseFloat(el.style.left) || 0;
                            var currentTop = parseFloat(el.style.top) || 0;
                            el.style.left = (currentLeft + x) + 'px';
                            el.style.top = (currentTop + y) + 'px';
                            el.style.transform = 'none';
                        } else {
                            var translateRegex = /translate(?:3d)?\(([^,]+)px,\s*([^,)]+)px(?:,\s*[^,)]+)?\)/;
                            var match = transform.match(translateRegex);
                            if (match) {
                                x = parseFloat(match[1]) || 0;
                                y = parseFloat(match[2]) || 0;
                                var currentLeftFallback = parseFloat(el.style.left) || 0;
                                var currentTopFallback = parseFloat(el.style.top) || 0;
                                el.style.left = (currentLeftFallback + x) + 'px';
                                el.style.top = (currentTopFallback + y) + 'px';
                                el.style.transform = 'none';
                            }
                        }
                    }
                });
            }
        }).then(function(mapCanvas) {
            zoomControls.forEach(function(el) { el.style.display = ''; });

            var SC = 2;
            var W = 820;
            var PAD = 24;
            var HEADER_H = 64;
            var MAP_H = 340;
            var LEGEND_H = 54;
            var H = PAD + HEADER_H + MAP_H + LEGEND_H + PAD;

            var finalCanvas = document.createElement('canvas');
            finalCanvas.width = W * SC;
            finalCanvas.height = H * SC;
            var ctx = finalCanvas.getContext('2d');
            ctx.scale(SC, SC);

            ctx.fillStyle = '#ffffff';
            _drawRRect(ctx, 0, 0, W, H, 14);
            ctx.fill();
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            _drawRRect(ctx, 0.5, 0.5, W - 1, H - 1, 14);
            ctx.stroke();

            // Header
            var hMid = PAD + HEADER_H / 2;
            ctx.fillStyle = '#184D77';
            ctx.font = '800 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'left';
            ctx.fillText('Peta Sebaran Lolos/Gugur SAQ Jawa Timur', PAD, hMid - 8);

            ctx.fillStyle = '#64748b';
            ctx.font = '500 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillText('Arahkan kursor ke wilayah untuk melihat skor SAQ dan status kelulusan', PAD, hMid + 12);

            // Map Image
            var mapY = PAD + HEADER_H;
            ctx.save();
            _drawRRect(ctx, PAD, mapY, W - PAD * 2, MAP_H, 8);
            ctx.clip();
            ctx.drawImage(mapCanvas, PAD, mapY, W - PAD * 2, MAP_H);
            ctx.restore();

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            _drawRRect(ctx, PAD, mapY, W - PAD * 2, MAP_H, 8);
            ctx.stroke();

            // Legends: Lolos, Gugur, Belum Dinilai (sesuai app.css)
            var legends = [
                { text: 'Lolos (Nilai SAQ \u2265 80)', color: '#388E3C' },
                { text: 'Gugur (Nilai SAQ < 80)', color: '#E82D1F' },
                { text: 'Belum Dinilai', color: '#D1D5D8' }
            ];

            var legY = mapY + MAP_H + 28;
            ctx.font = '700 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'left';

            var gapX = 32;
            var totalWidth = 0;
            var itemWidths = legends.map(function(l) {
                var w = 12 + 8 + ctx.measureText(l.text).width;
                totalWidth += w;
                return w;
            });
            totalWidth += gapX * (legends.length - 1);
            var currentX = (W - totalWidth) / 2;

            legends.forEach(function(l, i) {
                ctx.fillStyle = l.color;
                ctx.beginPath();
                ctx.arc(currentX + 6, legY, 6, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = '#334155';
                ctx.fillText(l.text, currentX + 20, legY);
                currentX += itemWidths[i] + gapX;
            });

            finalCanvas.toBlob(function(blob) {
                if (!blob) {
                    if (label) label.textContent = 'Gagal!';
                    if (btn) btn.disabled = false;
                    setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
                    return;
                }
                if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
                    navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })])
                        .then(function() {
                            if (label) label.textContent = '✓ Tersalin!';
                            if (btn) btn.style.color = '#388E3C';
                            setTimeout(function() {
                                if (label) label.textContent = 'Salin Gambar';
                                if (btn) { btn.style.color = ''; btn.disabled = false; }
                            }, 2500);
                        })
                        .catch(function(err) {
                            console.warn('Clipboard write rejected, downloading file as fallback:', err);
                            tryDownload(blob);
                        });
                } else {
                    tryDownload(blob);
                }
            }, 'image/png');
        }).catch(function(err) {
            console.error('html2canvas error:', err);
            zoomControls.forEach(function(el) { el.style.display = ''; });
            if (label) label.textContent = 'Gagal!';
            if (btn) btn.disabled = false;
            setTimeout(function() { if (label) label.textContent = 'Salin Gambar'; }, 2000);
        });
    };

    // -------------------------------------------------------------
    // Lifecycle Inits
    // -------------------------------------------------------------
    function runInitializers() {
        if (window.loadECharts && (!window.echarts || !window.echarts.init)) {
            window.loadECharts();
        }
        setTimeout(function() {
            initSaqHistogram();
            initSaqMap();
        }, 120);
    }

    window.initSaqReviewSection = function() {
        if (window.loadECharts && (!window.echarts || !window.echarts.init)) {
            window.loadECharts();
        }
        setTimeout(function() {
            initSaqHistogram();
            initSaqMap();
        }, 80);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runInitializers);
    } else {
        runInitializers();
    }

    window.addEventListener('echarts:ready', function() {
        initSaqHistogram();
    });

    window.addEventListener('resize', function() {
        if (window._saqHistogramInstance) window._saqHistogramInstance.resize();
        if (window.mySaqMap) window.mySaqMap.invalidateSize();
    });

    function setupEventListeners() {
        if (typeof Livewire !== 'undefined') {
            Livewire.on('saqDataUpdated', function() {
                setTimeout(function() {
                    if (window.refreshSaqCharts) window.refreshSaqCharts();
                }, 50);
            });
        }
    }

    if (typeof Livewire !== 'undefined') {
        setupEventListeners();
    } else {
        document.addEventListener('livewire:init', setupEventListeners);
        document.addEventListener('livewire:initialized', setupEventListeners);
    }

    // Direct window listener for Livewire 3 CustomEvent dispatched to window
    window.addEventListener('saqDataUpdated', function() {
        setTimeout(function() {
            if (window.refreshSaqCharts) window.refreshSaqCharts();
        }, 50);
    });

    document.addEventListener('livewire:navigated', function() {
        runInitializers();
    });
})();
</script>
@endpush
