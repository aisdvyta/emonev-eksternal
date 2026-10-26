<x-layouts.app title="Manajemen SK & Sertifikat - HaloKIP">
@php
    $currentRoute = request()->route()?->getName() ?? '';
    if (str_contains($currentRoute, 'super-evaluator')) {
        $routePrefix = 'super-evaluator';
    } elseif (str_contains($currentRoute, 'evaluator')) {
        $routePrefix = 'evaluator';
    } else {
        $routePrefix = 'dokumen';
    }
@endphp
<x-ui.page-header :breadcrumb="[
        'SK & Sertifikat' => route($routePrefix . '.manage-sk'),
    ]">
</x-ui.page-header>

<!-- Hero Banner -->
<section
    class="rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8">
    <div class="absolute right-20 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block"></div>
    <div class="absolute right-60 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block"></div>
    <div class="absolute right-40 bottom-3 hidden lg:block z-10">
        <img src="{{ asset('images/orangSKSertif.svg') }}" alt="SK & Sertifikat" class="h-auto w-75 object-contain">
    </div>
    <div class="relative z-10">
        <h2 class="text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Manajemen File SK & Sertifikat</h2>
        <p class="text-base leading-relaxed text-white font-normal max-w-xl">Upload file SK dan Sertifikat untuk setiap periode. File yang diunggah dapat diunduh oleh semua operator Kabupaten/Kota.</p>
    </div>
</section>

<div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3 animate-fade-in">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-xl text-sm font-semibold shadow-xs">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex justify-end items-center gap-2 sm:gap-4 mb-6 w-full sm:w-auto">
        {{-- SK Actions --}}
        <div class="flex items-center gap-2 sm:gap-3 flex-nowrap w-full sm:w-auto justify-end">
            @if ($period->skSertifikat?->has_sk)
                {{-- Download SK --}}
                <a href="{{ route('sk.download', ['uuid' => $period->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-5 py-2.5 sm:py-3 bg-blue-50 border border-blue-200 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-bold text-biru2 hover:bg-blue-100 transition shadow-2xs whitespace-nowrap">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Download SK</span>
                </a>

                {{-- Hapus SK --}}
                <form action="{{ route($routePrefix . '.manage-sk.delete-file') }}" method="POST" class="inline shrink-0"
                    onsubmit="return confirm('Hapus file SK untuk periode ini?')">
                    @csrf
                    <input type="hidden" name="id_periode" value="{{ $period->id_periode }}">
                    <input type="hidden" name="type" value="sk">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-5 py-2.5 sm:py-3 bg-rose-50 border border-rose-200 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-bold text-rose-600 hover:bg-rose-100 transition cursor-pointer shadow-2xs whitespace-nowrap">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus SK</span>
                    </button>
                </form>
            @else
                {{-- Upload SK --}}
                <button type="button" onclick="openUploadSkModal()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 sm:py-3 bg-oren2 hover:bg-oren text-white text-xs sm:text-sm font-bold rounded-xl sm:rounded-2xl transition duration-200 shadow-md hover:shadow-lg cursor-pointer whitespace-nowrap">
                    <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                    <span>Upload SK</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Tabel Unduh per Kabkot --}}
    <x-tables.monitoring-table title="Manajemen Berkas per Kabupaten / Kota" :hasCards="true">
        <x-slot name="headerCenter">
            <!-- Mobile: Search + Dropdown side-by-side | Desktop: Search in Center -->
            <div class="flex items-center gap-2 w-full">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-0">
                    <input type="text" id="searchKabkot" onkeyup="filterKabkotTable()" placeholder="Cari Kab/Kota..."
                        class="w-full rounded-xl border border-white/30 bg-white/20 shadow-sm pl-9 pr-4 py-2 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/50 transition">
                    <div class="absolute left-3 top-2.5 text-white/70">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Custom Styled Periode Dropdown (Mobile Only) -->
                <div class="md:hidden shrink-0"
                    x-data="{
                        isOpen: false,
                        search: '',
                        periods: {{ json_encode($periods->map(fn($p) => [
                            'value' => \App\Helpers\SecureId::encrypt($p->id_periode),
                            'label' => $p->nama ?? ('Tahun ' . $p->tahun),
                            'tahun' => $p->tahun,
                            'selected' => $p->id_periode == $period->id_periode
                        ])) }},
                        currentLabel: '{{ $period->nama ?? ('Tahun ' . $period->tahun) }}',
                        get filtered() {
                            const q = this.search.toLowerCase().trim();
                            if (!q) return this.periods;
                            return this.periods.filter(p => p.label.toLowerCase().includes(q) || String(p.tahun).includes(q));
                        },
                        select(val) {
                            window.location.href = '{{ route($routePrefix . '.manage-sk') }}/' + val;
                        }
                    }">
                    <button type="button" @click.stop="isOpen = !isOpen"
                        class="flex items-center gap-1.5 rounded-xl border border-white/30 bg-white/95 hover:bg-white text-biru1 px-3 py-2 text-xs font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-white/40 cursor-pointer transition select-none">
                        <span x-text="currentLabel" class="truncate max-w-27.5"></span>
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
        </x-slot>

        <x-slot name="headerActions">
            <!-- Custom Styled Periode Dropdown (Desktop / Laptop Only - Far Right) -->
            <div class="hidden md:flex items-center justify-end w-full md:w-auto ml-auto"
                x-data="{
                    isOpen: false,
                    search: '',
                    periods: {{ json_encode($periods->map(fn($p) => [
                        'value' => \App\Helpers\SecureId::encrypt($p->id_periode),
                        'label' => $p->nama ?? ('Tahun ' . $p->tahun),
                        'tahun' => $p->tahun,
                        'selected' => $p->id_periode == $period->id_periode
                    ])) }},
                    currentLabel: '{{ $period->nama ?? ('Tahun ' . $period->tahun) }}',
                    get filtered() {
                        const q = this.search.toLowerCase().trim();
                        if (!q) return this.periods;
                        return this.periods.filter(p => p.label.toLowerCase().includes(q) || String(p.tahun).includes(q));
                    },
                    select(val) {
                        window.location.href = '{{ route($routePrefix . '.manage-sk') }}/' + val;
                    }
                }">
                <div class="relative shrink-0">
                    <button type="button" @click.stop="isOpen = !isOpen"
                        class="flex items-center gap-2 rounded-xl border border-white/30 bg-white/95 hover:bg-white text-biru1 px-3.5 py-2 text-sm font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-white/40 cursor-pointer transition select-none">
                        <span x-text="currentLabel" class="truncate max-w-40"></span>
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
                        class="absolute right-0 top-full mt-1.5 bg-white border border-abubiru rounded-2xl shadow-2xl p-2 z-999 w-64 text-left">
                        
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
        </x-slot>

        <x-slot name="cards">
            @forelse ($operators as $index => $op)
                @php
                    $sub = $op->submissions->first();
                    $result = $sub ? $sub->result : null;
                    $specificCert = ($sub && $sub->skSertifikat && $sub->skSertifikat->has_sertifikat) ? $sub->skSertifikat : null;
                    $namaKabkot = $op->masterSatker->nama_satker ?? $op->nama_user;
                @endphp
                <div data-kabkot="{{ strtolower($namaKabkot) }}" class="operator-card bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-3 border-b border-abubiru/30 pb-2.5">
                        <div>
                            <span class="text-xs font-extrabold uppercase text-biru4 tracking-wider block mb-0.5">Kabupaten / Kota</span>
                            <div class="font-bold text-biru1 text-sm leading-snug">{{ $namaKabkot }}</div>
                        </div>
                        <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold shrink-0">
                            {{ $period->tahun }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 text-xs">
                        {{-- SK Status & Action --}}
                        <div class="bg-slate-50 rounded-xl p-2.5 border border-abubiru/40">
                            <span class="text-xs font-extrabold uppercase text-biru4 block mb-1.5">Berkas SK</span>
                            @if ($period->skSertifikat?->has_sk)
                                <a href="{{ route('sk.download', ['uuid' => $period->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-biru2 hover:bg-blue-100 border border-blue-200 text-xs font-bold transition w-full justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat SK
                                </a>
                            @else
                                <span class="text-xs text-slate-400 font-semibold italic block py-1 text-center">Belum ada file</span>
                            @endif
                        </div>

                        {{-- Sertifikat Actions --}}
                        <div class="bg-slate-50 rounded-xl p-2.5 border border-abubiru/40">
                            <span class="text-xs font-extrabold uppercase text-biru4 block mb-1.5">Sertifikat</span>
                            <div class="flex items-center gap-1.5 justify-center">
                                @if ($specificCert)
                                    <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid]) }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition shrink-0"
                                        title="Download Sertifikat Khusus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    <form action="{{ route($routePrefix . '.manage-sk.delete') }}" method="POST" class="inline"
                                        onsubmit="return confirm('Hapus file Sertifikat khusus untuk Satker ini?')">
                                        @csrf
                                        <input type="hidden" name="id_periode" value="{{ $period->id_periode }}">
                                        <input type="hidden" name="id_user" value="{{ $op->id_user }}">
                                        <input type="hidden" name="type" value="sertifikat">
                                        <button type="submit" class="inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition cursor-pointer shrink-0"
                                            title="Hapus Sertifikat">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" onclick="openUploadModal('{{ $op->id_user }}', '{{ addslashes($namaKabkot) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 text-xs font-bold transition w-full justify-center cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                                        Unggah
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                    Tidak ada data Kabupaten/Kota.
                </div>
            @endforelse
        </x-slot>

        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                <tr>
                    <th class="px-6 py-4 w-15 text-left">No</th>
                    <th class="px-6 py-4 text-left">Kabupaten / Kota</th>
                    <th class="px-6 py-4 text-center w-30">SK</th>
                    <th class="px-6 py-4 text-center w-55">Sertifikat</th>
                </tr>
            </thead>
        </x-slot>

        @forelse ($operators as $index => $op)
            @php
                $sub = $op->submissions->first();
                $result = $sub ? $sub->result : null;
                $specificCert = ($sub && $sub->skSertifikat && $sub->skSertifikat->has_sertifikat) ? $sub->skSertifikat : null;
            @endphp
            <tr class="operator-row border-t border-abubiru/30 hover:bg-abubiru/10 transition">
                <td class="px-6 py-4 font-bold text-biru3">{{ $index + 1 }}</td>
                <td class="px-6 py-4 font-bold text-biru1">
                    {{ $op->masterSatker->nama_satker ?? $op->nama_user }}
                </td>

                {{-- Kolom SK: hanya LIHAT --}}
                <td class="px-6 py-4 text-center">
                     @if ($period->skSertifikat?->has_sk)
                         <a href="{{ route('sk.download', ['uuid' => $period->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer"
                             class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-biru2 hover:bg-blue-100 border border-blue-200 transition"
                             title="Lihat SK">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                             </svg>
                         </a>
                     @else
                         <span class="text-xs text-slate-400 font-semibold italic">Belum ada file</span>
                     @endif
                </td>

                {{-- Kolom Sertifikat: LIHAT dan UNGGAH (dan HAPUS jika ada yang spesifik) --}}
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center gap-1.5 justify-center">
                        @if ($specificCert)
                            {{-- 1. DOWNLOAD SERTIFIKAT SPESIFIK (KABKOT) --}}
                            <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid]) }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition"
                                title="Download Sertifikat Khusus (Kabkot)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>

                            {{-- 2. HAPUS SPESIFIK --}}
                            <form action="{{ route($routePrefix . '.manage-sk.delete') }}" method="POST" class="inline"
                                onsubmit="return confirm('Hapus file Sertifikat khusus untuk Satker ini?')">
                                @csrf
                                <input type="hidden" name="id_periode" value="{{ $period->id_periode }}">
                                <input type="hidden" name="id_user" value="{{ $op->id_user }}">
                                <input type="hidden" name="type" value="sertifikat">
                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                    title="Hapus Sertifikat Khusus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        @else
                            {{-- 3. UNGGAH --}}
                            <button type="button" onclick="openUploadModal('{{ $op->id_user }}', '{{ addslashes($op->masterSatker->nama_satker ?? $op->nama_user) }}')"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition cursor-pointer"
                                title="Unggah Sertifikat Spesifik Kabkot">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0L8 8m4-4l4 4"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-sm font-semibold text-biru3 italic">Tidak ada data Kabupaten/Kota.</td>
            </tr>
        @endforelse
    </x-tables.monitoring-table>

</div>

<!-- Modal Dialog Unggah Sertifikat Spesifik -->
<div id="uploadCertModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-abubiru shadow-2xl w-full max-w-md overflow-hidden transition-all duration-300 transform scale-95 opacity-0" id="modalContent">
        {{-- Header Modal --}}
        <div class="bg-biru1 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-kuning1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-base font-bold">Unggah Sertifikat Khusus</h3>
            </div>
            <button type="button" onclick="closeUploadModal()" class="text-white/80 hover:text-white transition duration-200 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route($routePrefix . '.manage-sk.save') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id_periode" value="{{ $period->id_periode }}">
            <input type="hidden" name="id_user" id="modalUserId">

            {{-- Body Modal --}}
            <div class="p-6">
                <p class="text-xs text-biru3 font-semibold mb-4">Satker: <span id="modalSatkerName" class="text-biru1 font-bold"></span></p>
                
                <div class="relative border-2 border-dashed border-slate-200 hover:border-kuning1 rounded-2xl p-5 transition-all duration-300 bg-slate-50/50 hover:bg-amber-50/20 flex flex-col items-center justify-center cursor-pointer group min-h-30">
                    <input type="file" name="link_sertifikat" required accept=".pdf"
                        class="absolute inset-0 opacity-0 cursor-pointer z-10"
                        onchange="updateFileName(this, 'info_modal_sertifikat')">
                    <div class="text-center space-y-2 pointer-events-none">
                        <div class="w-10 h-10 rounded-full bg-kuning1/10 text-kuning1 flex items-center justify-center mx-auto group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-biru1">Pilih File Sertifikat</span>
                            <span class="block text-xs text-slate-400 font-bold uppercase tracking-wider mt-0.5">PDF · Maks. 10MB</span>
                        </div>
                        <div id="info_modal_sertifikat" class="hidden text-xs text-kuning1 font-bold bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1 items-center gap-1.5"></div>
                    </div>
                </div>
            </div>
            
            {{-- Footer Modal --}}
            <div class="flex justify-end gap-2 p-6 border-t border-slate-100 bg-slate-50/50">
                <button type="button" onclick="closeUploadModal()"
                    class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-linear-to-r from-kuning1 to-oren2 hover:from-oren2 hover:to-kuning1 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg cursor-pointer">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Unggah SK -->
<div id="uploadSkModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-abubiru shadow-2xl w-full max-w-md overflow-hidden transition-all duration-300 transform scale-95 opacity-0" id="modalSkContent">
        {{-- Header Modal --}}
        <div class="bg-biru1 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-biru2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                </svg>
                <h3 class="text-base font-bold">Upload SK</h3>
            </div>
            <button type="button" onclick="closeUploadSkModal()" class="text-white/80 hover:text-white transition duration-200 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route($routePrefix . '.manage-sk.upload-file') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id_periode" value="{{ $period->id_periode }}">
            <input type="hidden" name="type" value="sk">

            {{-- Body Modal --}}
            <div class="p-6">
                <p class="text-xs text-biru3 font-semibold mb-4">Periode: <span class="text-biru1 font-bold">{{ $period->nama }}</span></p>
                
                <div class="relative border-2 border-dashed border-slate-200 hover:border-biru2 rounded-2xl p-5 transition-all duration-300 bg-slate-50/50 hover:bg-blue-50/20 flex flex-col items-center justify-center cursor-pointer group min-h-30">
                    <input type="file" name="file_upload" required accept=".pdf"
                        class="absolute inset-0 opacity-0 cursor-pointer z-10"
                        onchange="updateFileName(this, 'info_modal_sk')">
                    <div class="text-center space-y-2 pointer-events-none">
                        <div class="w-10 h-10 rounded-full bg-biru2/10 text-biru2 flex items-center justify-center mx-auto group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-biru1">Pilih File SK</span>
                            <span class="block text-xs text-slate-400 font-bold uppercase tracking-wider mt-0.5">PDF · Maks. 20MB</span>
                        </div>
                        <div id="info_modal_sk" class="hidden text-xs text-biru2 font-bold bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-1 items-center gap-1.5"></div>
                    </div>
                </div>
            </div>
            
            {{-- Footer Modal --}}
            <div class="flex justify-end gap-2 p-6 border-t border-slate-100 bg-slate-50/50">
                <button type="button" onclick="closeUploadSkModal()"
                    class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-oren2 hover:bg-oren rounded-xl transition duration-200 shadow-md hover:shadow-lg cursor-pointer">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateFileName(input, infoId) {
    const infoDiv = document.getElementById(infoId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        infoDiv.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4"/></svg><span class="truncate max-w-37.5">${file.name}</span><span class="font-semibold text-xs ml-1">(${sizeMB} MB)</span>`;
        infoDiv.classList.remove('hidden');
        infoDiv.classList.add('inline-flex');
    } else {
        infoDiv.classList.add('hidden');
        infoDiv.classList.remove('inline-flex');
    }
}

function toggleEditForm(id, btnTriggerId) {
    const el = document.getElementById(id);
    const btn = document.getElementById(btnTriggerId);
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        el.classList.add('animate-fade-in');
        if (btn) btn.classList.add('hidden');
    } else {
        el.classList.add('hidden');
        if (btn) btn.classList.remove('hidden');
    }
}

function openUploadModal(userId, satkerName) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalSatkerName').innerText = satkerName;
    document.getElementById('info_modal_sertifikat').classList.add('hidden');
    document.getElementById('info_modal_sertifikat').classList.remove('inline-flex');
    
    const modal = document.getElementById('uploadCertModal');
    const content = document.getElementById('modalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeUploadModal() {
    const modal = document.getElementById('uploadCertModal');
    const content = document.getElementById('modalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function openUploadSkModal() {
    document.getElementById('info_modal_sk').classList.add('hidden');
    document.getElementById('info_modal_sk').classList.remove('inline-flex');
    
    const modal = document.getElementById('uploadSkModal');
    const content = document.getElementById('modalSkContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeUploadSkModal() {
    const modal = document.getElementById('uploadSkModal');
    const content = document.getElementById('modalSkContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function filterKabkotTable() {
    const input = document.getElementById("searchKabkot");
    const filter = input.value.toLowerCase();
    
    const rows = document.querySelectorAll(".operator-row");
    rows.forEach(row => {
        const textCell = row.cells[1];
        if (textCell) {
            const textValue = textCell.textContent || textCell.innerText;
            row.style.display = textValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    });

    const cards = document.querySelectorAll(".operator-card");
    cards.forEach(card => {
        const kabkot = card.getAttribute("data-kabkot") || "";
        card.style.display = kabkot.indexOf(filter) > -1 ? "" : "none";
    });
}
</script>
@endpush
</x-layouts.app>
