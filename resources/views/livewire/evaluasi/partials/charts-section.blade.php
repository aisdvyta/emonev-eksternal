<div x-data="{
    activeTab: 'histogram',
    switchTab(tab) {
        if (this.activeTab === tab) return;
        this.activeTab = tab;
        this.$nextTick(() => {
            window.dispatchEvent(new Event('resize'));
            if (tab === 'peta' && window.myMap) {
                setTimeout(() => { window.myMap.invalidateSize(); }, 80);
            }
            if (tab === 'histogram' && window._histogramChartInstance) {
                setTimeout(() => { window._histogramChartInstance.resize(); }, 80);
            }
        });
    }
}" class="mb-6">

    <!-- Mobile Segmented Tab Switcher (< xl screens) -->
    <div class="xl:hidden mb-4 bg-slate-100/90 p-1.5 rounded-2xl border border-abubiru/70 flex items-center gap-1.5 shadow-2xs">
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
            <span class="truncate">Peta Sebaran Jatim</span>
        </button>
    </div>

    <!-- Cards Container (50:50 on Desktop xl, Tabbed on Mobile) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-stretch">
        
        <!-- Card 1: Histogram per Indikator -->
        @php
            $initialSatker =
                collect($satkers)->firstWhere('kode_satker', $chartKabkotKode) ?? collect($satkers)->first();
            $initialSatkerName = $initialSatker
                ? $initialSatker->nama_satker . ' (' . $initialSatker->kode_satker . ')'
                : 'Pilih Kab/Kota';
        @endphp
        <div id="card-perbandingan"
            x-show="activeTab === 'histogram'"
            x-cloak
            class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 overflow-hidden flex flex-col justify-between relative text-left xl:!flex">
            <div id="chart-data-bridge" data-indicators="{{ json_encode($indicators) }}"
                data-indicator-scores-per-kabkot="{{ json_encode($indicatorScoresPerKabkot) }}"
                data-chart-kabkot-kode="{{ $chartKabkotKode }}" style="display: none;"></div>
            
            <!-- Header: Title & Copy Button -->
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-base font-extrabold text-biru2 truncate">
                        Skor SAQ per Indikator (<span id="line-chart-active-satker" class="text-biru2 font-black">{{ $initialSatkerName }}</span>)
                    </h3>
                </div>

                <!-- Tombol Salin Gambar -->
                <button id="btn-copy-perbandingan" onclick="copyPerbandinganCard(this)" title="Salin sebagai gambar"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs sm:text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 select-none cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="copy-btn-label hidden sm:inline">Salin Gambar</span>
                    <span class="copy-btn-label sm:hidden">Salin</span>
                </button>
            </div>

            <!-- Filters Row: Pilih Kab/Kota -->
            <div class="flex flex-row items-center justify-between gap-3 mb-4 pt-3 border-t border-slate-100/80">
                <div class="w-full" x-data="{
                    open: false,
                    searchSatker: '',
                    selectedKode: '{{ $chartKabkotKode }}',
                    selectedName: '{{ addslashes($initialSatkerName) }}',
                    selectSatker(kode, name) {
                        this.selectedKode = kode;
                        this.selectedName = name;
                        this.open = false;
                        const sel = document.getElementById('line-chart-select-kabkot');
                        if (sel) {
                            sel.value = kode;
                            for (let i = 0; i < sel.options.length; i++) {
                                if (sel.options[i].value === String(kode)) {
                                    sel.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                        if (typeof window.changeLineChartKabkot === 'function') {
                            window.changeLineChartKabkot(kode);
                        } else if (typeof window.updateLineChartForKabkot === 'function') {
                            window.updateLineChartForKabkot(kode);
                        }
                        if (typeof window.updateChartTitle === 'function') {
                            window.updateChartTitle();
                        }
                    }
                }">
                    <!-- Hidden select for JS compatibility -->
                    <select id="line-chart-select-kabkot" onchange="if(window.changeLineChartKabkot) window.changeLineChartKabkot(this.value)" class="hidden">
                        @foreach ($satkers as $satker)
                            <option value="{{ $satker->kode_satker }}"
                                {{ $chartKabkotKode == $satker->kode_satker ? 'selected' : '' }}>
                                {{ $satker->nama_satker }} ({{ $satker->kode_satker }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Dropdown Trigger Button -->
                    <div class="relative w-full">
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 rounded-xl border border-abubiru bg-white shadow-2xs px-3.5 py-2 text-xs text-biru1 font-bold hover:bg-slate-50 transition cursor-pointer select-none">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-biru3 shrink-0" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="truncate" x-text="selectedName"></span>
                            </div>
                            <svg class="w-4 h-4 text-biru3 shrink-0 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Popup Panel -->
                        <div x-show="open" @click.outside="open = false" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white border border-abubiru rounded-2xl shadow-xl p-3 text-biru1 font-medium text-left">

                            <!-- Search Field -->
                            <div class="relative mb-2">
                                <input type="text" placeholder="Cari Kab/Kota..." x-model="searchSatker"
                                    @click.stop
                                    class="w-full rounded-xl border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-xs text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20 transition">
                                <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <!-- List Options -->
                            <div class="max-h-52 overflow-y-auto space-y-1 pr-1">
                                @foreach ($satkers as $satker)
                                    <div x-show="searchSatker === '' || '{{ strtolower(addslashes($satker->nama_satker)) }}'.includes(searchSatker.toLowerCase()) || '{{ $satker->kode_satker }}'.includes(searchSatker)"
                                        @click="selectSatker('{{ $satker->kode_satker }}', '{{ addslashes($satker->nama_satker) }} ({{ $satker->kode_satker }})')"
                                        class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl cursor-pointer text-xs font-semibold transition select-none"
                                        :class="selectedKode === '{{ $satker->kode_satker }}' ?
                                            'bg-biru5/30 text-biru2 font-black' : 'hover:bg-biru5/20 text-biru1'">
                                        <span>{{ $satker->nama_satker }} ({{ $satker->kode_satker }})</span>
                                        <template x-if="selectedKode === '{{ $satker->kode_satker }}'">
                                            <svg class="w-4 h-4 text-biru2 shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chart Canvas Container -->
            <div class="relative w-full h-72.5 sm:h-90">
                <div wire:ignore id="perbandingan-list-chart" style="width: 100%; height: 100%;"
                    class="absolute inset-0"></div>
            </div>

            <!-- Legend Predikat -->
            <div class="flex flex-col items-center gap-y-2 text-xs sm:text-xs font-bold text-biru4 mt-4 border-t border-slate-100 pt-4 pb-1">
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-hijau shrink-0"></span>
                        Informatif (90 - 100)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-hijauneon shrink-0"></span>
                        Menuju Informatif (80 - 89,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-biru4 shrink-0"></span>
                        Cukup Informatif (60 - 79,9)</span>
                </div>
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-kuning1 shrink-0"></span>
                        Kurang Informatif (40 - 59,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-merah2 shrink-0"></span>
                        Tidak Informatif (&lt; 39,9)</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Peta Sebaran Indeks KIP Jawa Timur -->
        <div id="card-peta"
            x-show="activeTab === 'peta'"
            x-cloak
            class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 flex flex-col justify-between text-left xl:!flex">
            
            <!-- Row 1: Title & Copy Button -->
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-base font-extrabold text-biru2 truncate">Peta Sebaran Indeks KIP Jawa Timur</h3>
                    <p class="text-xs sm:text-xs text-biru3 mt-0.5">
                        <span class="hidden sm:inline">Arahkan kursor ke wilayah untuk melihat skor Indeks KIP daerah lain</span>
                        <span class="sm:hidden">Ketuk wilayah pada peta untuk melihat detail skor</span>
                    </p>
                </div>
                <button id="btn-copy-peta" onclick="copyPetaCard(this)" title="Salin sebagai gambar"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs sm:text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0 text-biru2" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="copy-btn-label hidden sm:inline">Salin Gambar</span>
                    <span class="copy-btn-label sm:hidden">Salin</span>
                </button>
            </div>

            <!-- Row 2: Map Container -->
            <div
                class="relative bg-putihbg border border-abubiru rounded-xl p-2 sm:p-4 overflow-hidden h-72.5 sm:h-90 flex items-center justify-center map-container">
                <div id="map-data-bridge" data-region-scores="{{ json_encode($regionScores) }}"
                    data-satker-scores="{{ json_encode($satkerScores) }}" style="display: none;"></div>
                <div wire:ignore id="map-sebaran" class="w-full h-full rounded-lg"
                    style="min-height: 270px; z-index: 1;"></div>
            </div>

            <!-- Legend Predikat -->
            <div class="flex flex-col items-center gap-y-2 text-xs sm:text-xs font-bold text-biru4 mt-4 border-t border-slate-100 pt-4 pb-1">
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-hijau shrink-0"></span>
                        Informatif (90 - 100)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-hijauneon shrink-0"></span>
                        Menuju Informatif (80 - 89,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-biru4 shrink-0"></span>
                        Cukup Informatif (60 - 79,9)</span>
                </div>
                <div class="flex flex-wrap justify-center gap-x-3.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-kuning1 shrink-0"></span>
                        Kurang Informatif (40 - 59,9)</span>
                    <span class="inline-flex items-center gap-1.5"><span
                            class="w-2.5 h-2.5 rounded-full bg-merah2 shrink-0"></span>
                        Tidak Informatif (&lt; 39,9)</span>
                </div>
            </div>
        </div>
    </div>
</div>
