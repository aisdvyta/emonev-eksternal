@php
    $user = Auth::user();
    $roleName = $user?->role?->nama_role;
    $isVerifikator = $roleName === 'verifikator';
    $satkerName = $submission && $submission->user ? ($submission->user->masterSatker->nama_satker ?? $submission->user->nama_user) : ($user->masterSatker->nama_satker ?? $user->nama_user);
    $satkerKode = $user->kode_satker ?? ($submission->user->masterSatker->kode_satker ?? '');
    $baseRoute = Route::has('evaluasi.penilaian.index') 
        ? route('evaluasi.penilaian.index') 
        : ($isVerifikator ? route('verifikator.penilaian') : route('operator.penilaian'));

    $intScore = 0;
    if ($submission && $submission->interview) {
        $intScore = $submission->interview->interview_score ?? 0;
    }

    $isGugur = ($submission && $submission->status == 8) || strtoupper($predikat) === 'GUGUR';

    $predClass = '';
    if ($isGugur) {
        $predClass = 'text-merah2';
    } elseif ($predikat === 'INFORMATIF') {
        $predClass = 'text-hijauneon';
    } elseif ($predikat === 'MENUJU INFORMATIF') {
        $predClass = 'text-hijaumuda';
    } elseif ($predikat === 'CUKUP INFORMATIF') {
        $predClass = 'text-kuning1';
    } elseif ($predikat === 'KURANG INFORMATIF') {
        $predClass = 'text-oren';
    } else {
        $predClass = 'text-merah2';
    }

    $latestKabagKomentar = $latestKabagKomentar ?? $kabagKomentar ?? null;
    $kabagKomentar = $kabagKomentar ?? $latestKabagKomentar ?? null;
@endphp
<x-layouts.app title="Penilaian Akhir - HaloKIP">
    <!-- Header: Title & Single Row for Satker + Period Dropdown -->
    <div class="mb-6 lg:mb-8 relative z-30">
        <div class="mb-3 text-sm text-biru3 flex items-center flex-wrap gap-1">
            <span class="text-biru3 cursor-default">Evaluasi</span>
            <span class="mx-1">\</span>
            <span class="font-bold text-biru1">Penilaian Akhir</span>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-biru1 mb-2 tracking-tight">Hasil Evaluasi Indeks Keterbukaan Informasi Publik</h1>

        <!-- Single Row for Satker Name & Periode Selector (Mobile & Desktop) -->
        <div class="flex items-center justify-between gap-3 mt-1">
            <p class="text-sm sm:text-base lg:text-lg text-biru3 font-semibold truncate min-w-0">
                {{ $satkerName }}
            </p>

            <div class="flex items-center justify-end shrink-0 ml-auto"
                x-data="{
                    isOpen: false,
                    search: '',
                    periods: {{ json_encode($periods->map(fn($p) => [
                        'value' => \App\Helpers\SecureId::encrypt($p->id_periode),
                        'label' => $p->nama ?? ('Tahun ' . $p->tahun),
                        'tahun' => (string) $p->tahun,
                        'selected' => $p->id_periode == $period->id_periode
                    ])) }},
                    currentTahun: '{{ $period->tahun }}',
                    get filtered() {
                        const q = this.search.toLowerCase().trim();
                        if (!q) return this.periods;
                        return this.periods.filter(p => p.label.toLowerCase().includes(q) || String(p.tahun).includes(q));
                    },
                    select(val) {
                        window.location.href = '{{ $baseRoute }}/' + val;
                    }
                }">
                
                <!-- Custom Styled Single-Select Periode Dropdown (Alpine.js) -->
                <div class="relative">
                    <button type="button" @click.stop="isOpen = !isOpen"
                        class="flex items-center gap-1.5 sm:gap-2 rounded-xl border border-abubiru bg-white hover:bg-slate-50 text-biru1 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-extrabold shadow-sm focus:outline-none focus:ring-2 focus:ring-biru2/20 cursor-pointer transition select-none">
                        <span class="text-biru3 font-medium hidden sm:inline">Pilih Periode:</span>
                        <span class="hidden sm:inline">Tahun </span>
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
                                <div @click.stop="select(p.value)"
                                    :class="p.selected ? 'bg-biru5/40 text-biru1 font-extrabold border border-biru5' : 'text-slate-700 font-medium hover:bg-slate-50 border border-transparent'"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs transition cursor-pointer select-none">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                            :class="p.selected ? 'border-biru2 bg-biru2 text-white' : 'border-slate-300 bg-white'">
                                            <div x-show="p.selected" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <span x-text="p.label" class="truncate"></span>
                                    </div>
                                    <template x-if="p.selected">
                                        <span class="text-xs font-bold text-biru2 bg-white/80 px-2 py-0.5 rounded-full shrink-0">Aktif</span>
                                    </template>
                                </div>
                            </template>
                            <div x-show="filtered.length === 0" class="text-xs text-center text-biru3 py-3 italic">Tidak ditemukan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6 items-stretch">
        <!-- Hero Banner (50%) -->
        <div
            class="rounded-2xl border border-abubiru shadow-xs overflow-hidden flex flex-col sm:flex-row min-h-47 text-left bg-linear-to-r from-white via-kuning2/20 to-biru5">
            <div class="flex-1 p-6 flex flex-col justify-between">
                <div>
                    <div
                        class="inline-flex rounded-full bg-biru5 px-4 py-1 text-xs font-extrabold text-biru2 mb-4 uppercase tracking-wider">
                        NILAI AKHIR
                    </div>
                    <div class="text-5xl leading-none font-extrabold {{ $predClass }} mb-4">
                        @if ($isPublished || $isGugur)
                            {{ number_format($finalScore, 2) }}
                        @else
                            -
                        @endif
                    </div>
                    <div class="text-base font-extrabold text-biru2 mt-1 leading-snug">
                        @if ($isPublished || $isGugur)
                            @if (in_array($predikat, ['INFORMATIF', 'MENUJU INFORMATIF', 'CUKUP INFORMATIF']))
                                Selamat! Anda memperoleh predikat
                            @else
                                Maaf, Anda memperoleh predikat
                            @endif
                            <span class="{{ $predClass }} uppercase font-extrabold">{{ $isGugur ? 'GUGUR' : $predikat }}</span>
                        @else
                            Hasil Penilaian Akhir Belum Diterbitkan
                        @endif
                    </div>
                </div>
                <p class="text-sm text-biru2 leading-relaxed font-medium">
                    @if ($isPublished || $isGugur)
                        Terima kasih atas kerja keras Anda dalam mewujudkan keterbukaan informasi publik yang
                        berkualitas.
                    @else
                        Hasil penilaian akhir Anda untuk periode ini sedang dalam proses evaluasi oleh tim verifikator
                        provinsi.
                    @endif
                </p>
            </div>
            <div class="hidden sm:flex sm:w-72 md:w-96 items-center justify-center shrink-0 pr-2 sm:pr-4 py-1">
                @if ($isGugur)
                    <img src="{{ asset('images/icon-gugur.svg') }}" alt="Gugur"
                        class="h-52 sm:h-60 md:h-64 max-h-60 w-auto object-contain transform scale-105">
                @else
                    <img src="{{ asset('images/penilaianAkhir.svg') }}" alt="Penilaian Akhir"
                        class="h-52 sm:h-60 md:h-64 max-h-60 w-auto object-contain transform scale-110">
                @endif
            </div>
        </div>

        <!-- Catatan Penilaian Akhir -->
        <div id="card-catatan-kabag"
            class="rounded-2xl bg-kuning2/30 border border-kuning2 shadow-xs relative overflow-hidden flex flex-col sm:flex-row transition-all duration-200">
            <!-- Illustration Side -->
            <div class="hidden lg:flex lg:w-44 xl:w-48 items-center justify-center shrink-0 pl-5 py-4 relative overflow-hidden border-r border-kuning2/40">
                <img src="{{ asset('images/catatanKabag.svg') }}" alt="Catatan Penilaian Akhir"
                    class="h-32 sm:h-36 max-h-37.5 w-auto object-contain transform -rotate-6 filter drop-shadow-sm hover:scale-105 transition-transform duration-300">
            </div>
            <!-- Catatan Content Side -->
            <div class="flex-1 p-4 sm:p-6 flex flex-col justify-between text-left">
                <div>
                    <!-- Header Badge -->
                    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-kuning2/70 border border-kuning1/50 text-biru1 text-xs font-extrabold shadow-2xs tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-kuning1 animate-pulse"></span>
                            <span>Catatan Penilaian Akhir Periode {{ $period->tahun }}</span>
                        </div>
                    </div>
                    <div class="relative my-2.5 py-0.5">
                        <p class="text-xs sm:text-sm text-biru1 italic leading-relaxed font-semibold whitespace-pre-line">
                            "{{ $isPublished && $latestKabagKomentar ? $latestKabagKomentar->komentar : 'Belum ada catatan atau rekomendasi dari Super Evaluator BPS Provinsi untuk periode ini.' }}"
                        </p>
                    </div>
                </div>
                <div class="mt-3.5 pt-3 border-t border-kuning2/60 flex items-center gap-2.5">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full overflow-hidden shrink-0">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($latestKabagKomentar?->user?->nama_user ?? 'Super Evaluator') }}&background=f08c1a&color=fff"
                            alt="Super Evaluator avatar" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <div class="font-extrabold text-biru1 text-xs sm:text-sm truncate">
                            {{ $latestKabagKomentar?->user?->nama_user ?? 'Super Evaluator' }}
                        </div>
                        <div class="text-xs text-biru3 font-bold uppercase tracking-wider truncate">
                            {{ $latestKabagKomentar?->user?->role?->nama_role === 'super_evaluator' ? 'Super Evaluator BPS Provinsi' : 'Evaluator BPS Provinsi' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Histogram per Indikator & Peta Sebaran (Tabbed on Mobile, 50:50 on Desktop) -->
    <section x-data="{
        activeTab: 'histogram',
        switchTab(tab) {
            this.activeTab = tab;
            this.$nextTick(() => {
                window.dispatchEvent(new Event('resize'));
                if (tab === 'peta' && window.myMap) {
                    setTimeout(() => { window.myMap.invalidateSize(); }, 150);
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
            
            <!-- Card Histogram per Indikator -->
            <div id="card-perbandingan"
                x-show="activeTab === 'histogram' || window.innerWidth >= 1280"
                class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 overflow-hidden text-left flex flex-col justify-between relative">
                <div id="chart-data-bridge" data-indicators="{{ json_encode($indicators) }}"
                    data-indicator-scores-per-kabkot="{{ json_encode($indicatorScoresPerKabkot) }}"
                    data-chart-kabkot-kode="{{ $chartKabkotKode }}" style="display: none;"></div>
                
                <!-- Row 1: Title & Copy Button -->
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-xs sm:text-base font-extrabold text-biru2 truncate">
                            Skor SAQ per Indikator (<span id="line-chart-active-satker" class="text-biru2 font-black"></span>)
                        </h3>
                    </div>

                    <!-- Tombol Salin Gambar -->
                    <button id="btn-copy-perbandingan" onclick="copyPerbandinganCard(this)" title="Salin sebagai gambar"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 select-none cursor-pointer">
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
                    @php
                        $initialSatker = collect($satkers)->firstWhere('kode_satker', $chartKabkotKode) ?? collect($satkers)->first();
                        $initialSatkerName = $initialSatker ? $initialSatker->nama_satker : 'Pilih Kab/Kota';
                    @endphp
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
                                    if (sel.options[i].value === kode) {
                                        sel.selectedIndex = i;
                                        break;
                                    }
                                }
                                sel.dispatchEvent(new Event('change'));
                            }
                            if (typeof changeLineChartKabkot === 'function') {
                                changeLineChartKabkot(kode);
                            }
                            if (typeof updateChartTitle === 'function') {
                                updateChartTitle();
                            }
                        }
                    }">
                        <!-- Hidden select for JS compatibility -->
                        <select id="line-chart-select-kabkot" onchange="changeLineChartKabkot(this.value)" class="hidden">
                            @foreach ($satkers as $satker)
                                <option value="{{ $satker->kode_satker }}"
                                    {{ $chartKabkotKode == $satker->kode_satker ? 'selected' : '' }}>
                                    {{ $satker->nama_satker }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Dropdown Trigger Button -->
                        <div class="relative">
                            <button type="button" @click.stop="open = !open"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl border border-abubiru bg-white hover:bg-slate-50 text-biru1 text-xs font-bold transition shadow-2xs cursor-pointer select-none">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-biru2 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-text="selectedName" class="truncate"></span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-biru2 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Popup -->
                            <div x-show="open" @click.away="open = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white border border-abubiru rounded-2xl shadow-2xl p-2 z-50 text-left">
                                
                                <div class="px-2 py-1 mb-1.5 border-b border-abubiru/40">
                                    <span class="text-xs font-extrabold text-biru3 uppercase tracking-wider">Pilih Satuan Kerja</span>
                                </div>

                                <div class="px-1 mb-2">
                                    <input type="text" placeholder="Cari Satker..."
                                        x-model="searchSatker"
                                        class="w-full rounded-lg border border-abubiru px-2.5 py-1.5 text-xs text-biru1 placeholder-biru3/60 focus:outline-none focus:border-biru2 focus:ring-1 focus:ring-biru2/20">
                                </div>

                                <div class="max-h-52 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $satker)
                                        <div x-show="!searchSatker || '{{ strtolower(addslashes($satker->nama_satker)) }}'.includes(searchSatker.toLowerCase())"
                                            @click.stop="selectSatker('{{ $satker->kode_satker }}', '{{ addslashes($satker->nama_satker) }}')"
                                            :class="selectedKode === '{{ $satker->kode_satker }}' ? 'bg-biru5/40 text-biru1 font-extrabold border border-biru5' : 'text-slate-700 font-medium hover:bg-slate-50 border border-transparent'"
                                            class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs transition cursor-pointer select-none">
                                            <span class="truncate">{{ $satker->nama_satker }}</span>
                                            <span x-show="selectedKode === '{{ $satker->kode_satker }}'" class="text-xs font-bold text-biru2 bg-white/80 px-2 py-0.5 rounded-full shrink-0">Aktif</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Container -->
                <div class="relative w-full h-72.5 sm:h-85">
                    <div id="indikator-histogram" style="width: 100%; height: 100%;" class="absolute inset-0"></div>
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

            <!-- Peta Sebaran Indeks KIP Jatim (OP-9) -->
            <div id="card-peta"
                x-show="activeTab === 'peta' || window.innerWidth >= 1280"
                class="rounded-2xl bg-white border border-abubiru shadow-xs p-4 sm:p-6 flex flex-col justify-between text-left">
                
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
                        class="inline-flex items-center gap-1.5 rounded-xl border border-abubiru bg-white shadow-2xs px-2.5 sm:px-3 py-1.5 text-xs font-bold text-biru3 hover:bg-slate-50 hover:text-biru1 transition shrink-0 cursor-pointer">
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
                    class="relative bg-putihbg border border-abubiru rounded-xl p-2 sm:p-4 overflow-hidden h-72.5 sm:h-85 flex items-center justify-center map-container">
                    <div id="map-sebaran" class="w-full h-full rounded-lg" style="min-height: 270px; z-index: 1;"></div>
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
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-3 h-3 flex items-center justify-center"><svg width="10" height="14"
                                    viewBox="0 0 12 16" fill="none">
                                    <path
                                        d="M6 0C3.79 0 2 1.79 2 4c0 3 4 8 4 8s4-5 4-8c0-2.21-1.79-4-4-4zm0 5.5A1.5 1.5 0 1 1 6 2.5a1.5 1.5 0 0 1 0 3z"
                                        fill="#E82D1F" />
                                </svg></span>
                            Wilayah Anda
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabel Hasil Penilaian Akhir -->
    <div class="mb-6">
        <livewire:evaluasi.penilaian-detail-table :periodeId="$period->id_periode" :userSatkerKode="$satkerKode" />
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            .leaflet-container {
                background: #f8fafc;
            }

            .custom-tooltip {
                background: white !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                padding: 12px 16px !important;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
                color: #0f172a !important;
                opacity: 1 !important;
            }

            .custom-tooltip::before {
                border-top-color: white !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        @include('components.charts.penilaian-histogram')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        @include('components.charts.penilaian-map')
    @endpush

</x-layouts.app>