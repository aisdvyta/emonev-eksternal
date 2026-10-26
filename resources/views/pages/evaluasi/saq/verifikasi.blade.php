<x-layouts.app title="Verifikasi SAQ - HaloKIP">
@php
    $isDraftVerif = ($submission->status === 1 && !empty($submission->verified_by));
    $statusLabel = $isDraftVerif ? 'Draft Verifikasi' : ($submission->status === 1 ? 'Menunggu Verifikasi' : $submission->status_label);
    $statusColorClass = $submission->status_color_class;
    $period = $submission->result?->period ?? \App\Models\Period::getActivePeriod();
    $evalEnd = $period?->eval_end ? \Carbon\Carbon::parse($period->eval_end)->endOfDay() : null;
    $isStageExpired = $evalEnd && now()->greaterThan($evalEnd);
    $canVerify = ($submission->status == 1) && !$isStageExpired;

    $notesPerIndicator = [];
    if ($submission->status < 3) {
        foreach ($indicators as $ind) {
            $c = 0;
            foreach ($ind->subIndikators as $sub) {
                foreach ($sub->pertanyaans as $q) {
                    $a = $answers->get($q->id_master_pertanyaan);
                    if ($a && !empty(trim($a->verifikator_note ?? ''))) {
                        $c++;
                    }
                }
            }
            $notesPerIndicator[$ind->id_indicators] = $c;
        }
    }
@endphp

<div x-data="{ 
    activeTab: {{ $activeTab ?? ($indicators->first()->id_indicators ?? 1) }},
    showConfirmModal: false,
    showDraftModal: false,
    decision: 'approve'
}">
    <x-ui.page-header 
        :title="$submission->status === 1 ? 'Verifikasi Kuesioner SAQ' : ($submission->status === 0 ? 'Pratinjau Kuesioner SAQ' : 'Detail Kuesioner SAQ')" 
        description="{{ $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota' }} - Tahun {{ $period->tahun ?? date('Y') }}" 
        :breadcrumb="['Beranda' => route('verifikator.index'), 'Monitoring SAQ' => route('verifikator.verifikasi-saq'), ($submission->status === 1 ? 'Verifikasi' : 'Lihat Kuesioner') => '#']">
    </x-ui.page-header>

    @if(session('success'))
        <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-semibold shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($submission->status === 0)
        <div class="mb-5 p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Kuesioner SAQ ini masih berstatus <strong>Draft</strong> (sedang diisi oleh Operator). Anda dapat memantau progres jawaban sementara. Verifikasi dapat diproses setelah operator mengirimkan kuesioner.</span>
        </div>
    @endif

    @if($isStageExpired)
        <div class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Tahap evaluasi data kuesioner SAQ telah berakhir pada tanggal <strong>{{ $period?->eval_end ? \Carbon\Carbon::parse($period->eval_end)->translatedFormat('d F Y') : '-' }}</strong>. Halaman ini kini bersifat hanya lihat (read-only).</span>
        </div>
    @endif

    <!-- Status & Score Cards (3 Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mb-6 items-stretch">
        <!-- Left: Status SAQ -->
        <div class="rounded-xl {{ $statusColorClass }} p-6 shadow-sm relative overflow-hidden flex flex-col justify-center min-h-48">
            <!-- Decorative Blobs -->
            <div class="absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-putihbg/50 opacity-100 blur-3xl"></div>
            <div class="absolute -right-10 -top-20 h-50 w-50 rounded-full bg-kuning2/90 opacity-100 blur-3xl"></div>

            <div class="absolute right-4 top-4 z-10 bg-biru1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white shadow-sm">
                STATUS</div>
            <div class="relative text-3xl sm:text-4xl font-bold mb-2 tracking-tight text-white">{{ $statusLabel }}</div>
            <div class="relative">
                @if($submission->submitted_at)
                    <p class="text-sm sm:text-base text-white/90 mb-0.5">Dikirim pada:</p>
                    <p class="text-sm sm:text-base font-bold text-white">{{ \Carbon\Carbon::parse($submission->submitted_at)->translatedFormat('d M Y, H:i') }}</p>
                @else
                    <p class="text-sm sm:text-base text-white/90 mb-0.5">Belum dikirim oleh operator.</p>
                @endif
            </div>
        </div>

        <!-- Center: Informasi Pengirim -->
        <div class="rounded-xl bg-white border border-abubiru p-6 shadow-sm min-h-48 flex flex-col justify-center relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>
            <div class="text-xs font-bold text-biru4 uppercase tracking-widest mb-1">Informasi Pengirim</div>
            <h3 class="text-xl font-black text-biru1 mb-4">{{ $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota' }}</h3>
            
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-biru5/20 text-biru2 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-biru1">{{ $submission->user->nama_user }}</div>
                        <div class="text-xs font-medium text-biru3">Operator SAQ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Status Verifikasi (Redesigned) -->
        <div class="rounded-xl bg-white border border-abubiru p-6 shadow-sm min-h-48 flex flex-col justify-between relative overflow-hidden">
            <div class="text-biru2 font-bold text-lg mb-2 tracking-wide text-left">Status Verifikasi</div>
            
            <div class="flex-1 flex flex-col justify-center">
                @if($submission->status == 1)
                    @if($isDraftVerif)
                        {{-- Draft Verifikasi --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-biru5/40 text-biru2 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-biru2 font-bold">Draft Verifikasi</span>
                                </div>
                                @if($submission->verified_at)
                                    <span class="text-xs text-biru3 font-medium shrink-0">
                                        {{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d M, H:i') }}
                                    </span>
                                @endif
                            </div>
                            @if($submission->verification_note)
                                <div class="bg-putihbg border border-abubiru/40 rounded-lg p-2.5 text-xs text-biru2 italic line-clamp-2">
                                    "{{ $submission->verification_note }}"
                                </div>
                            @else
                                <p class="text-xs text-biru3 font-medium">Draft catatan verifikasi tersimpan.</p>
                            @endif
                        </div>
                    @else
                        {{-- Menunggu Verifikasi --}}
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-oren2/10 text-oren2 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-oren2">Menunggu Verifikasi</div>
                                <div class="text-xs font-medium text-biru3">Belum ditinjau oleh Verifikator</div>
                            </div>
                        </div>
                    @endif
                @elseif($submission->status == 2)
                    {{-- Dikembalikan --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-merah1 font-bold text-sm">
                                <div class="w-7 h-7 rounded-full bg-merah1/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-merah1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <span>Dikembalikan ke Operator</span>
                            </div>
                            @if($submission->verified_at)
                                <span class="text-xs text-merah1/70 font-medium shrink-0">
                                    {{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d M, H:i') }}
                                </span>
                            @endif
                        </div>
                        @if($submission->verification_note)
                            <div class="bg-rose-50 border border-rose-100 rounded-lg p-2.5 text-xs text-rose-950 font-medium italic line-clamp-2">
                                "{{ $submission->verification_note }}"
                            </div>
                        @endif
                    </div>
                @elseif(in_array($submission->status, [3, 4, 5, 6, 7]))
                    {{-- Disetujui --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-hijau font-bold text-sm">
                                <div class="w-7 h-7 rounded-full bg-hijau/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-hijau" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span>Disetujui oleh Verifikator</span>
                            </div>
                            @if($submission->verified_at)
                                <span class="text-xs text-hijau/70 font-medium shrink-0">
                                    {{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d M, H:i') }}
                                </span>
                            @endif
                        </div>
                        @if($submission->verification_note)
                            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-2.5 text-xs text-emerald-950 font-medium italic line-clamp-2">
                                "{{ $submission->verification_note }}"
                            </div>
                        @endif
                    </div>
                @elseif($submission->status === 0)
                    {{-- Draft Operator --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-biru5/40 text-biru2 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-biru1">Kuesioner Masih Draft</div>
                            <div class="text-xs font-medium text-biru3">Menunggu operator mengirimkan pengajuan</div>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-biru3 italic">Tidak ada tindakan verifikasi yang diperlukan.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-6 flex gap-3.5 shadow-xs items-start">
        <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex shrink-0 items-center justify-center mt-0.5 shadow-2xs">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-amber-900 text-sm mb-0.5">Panduan Verifikasi</h3>
            <p class="text-sm text-amber-800 font-medium leading-relaxed">
                Periksa kelengkapan jawaban dan kesesuaian link bukti dukung. Anda dapat memberikan catatan verifikasi per nomor soal, lalu klik <strong>Simpan Draft</strong> untuk menyimpan sementara, atau <strong>Kirim</strong> untuk memfinalisasi keputusan (Setujui / Kembalikan).
            </p>
        </div>
    </div>

    <!-- Mobile Custom Dropdown Selection for Indicators (Visible on Mobile HP Only) -->
    <div class="block md:hidden mb-4 relative z-30" x-data="{ openIndSelect: false }">
        <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1.5">
            Pilih Indikator:
        </label>
        <!-- Trigger Button -->
        <button type="button" @click="openIndSelect = !openIndSelect" @click.away="openIndSelect = false"
            class="w-full bg-biru4 text-white font-bold text-xs sm:text-sm rounded-xl border border-biru4 py-3 pl-3.5 pr-9 shadow-sm focus:outline-none flex items-center justify-between transition cursor-pointer relative">
            <span class="truncate pr-2 font-bold">
                @foreach ($indicators as $ind)
                    <template x-if="activeTab === {{ $ind->id_indicators }}">
                        <span class="inline-flex items-center gap-2">
                            <span>{{ $ind->nama_indikator }}</span>
                            @if (($notesPerIndicator[$ind->id_indicators] ?? 0) > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    {{ $notesPerIndicator[$ind->id_indicators] }} Catatan
                                </span>
                            @endif
                        </span>
                    </template>
                @endforeach
            </span>
            <svg class="w-4 h-4 shrink-0 text-white transition-transform duration-200" :class="openIndSelect ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Custom Dropdown Menu Panel -->
        <div x-show="openIndSelect" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
             class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-abubiru rounded-xl shadow-xl py-1.5 z-50 divide-y divide-abubiru/40 overflow-hidden max-w-full">
            @foreach ($indicators as $indicator)
                @php $mCount = $notesPerIndicator[$indicator->id_indicators] ?? 0; @endphp
                <button type="button" @click="activeTab = {{ $indicator->id_indicators }}; openIndSelect = false"
                   :class="activeTab === {{ $indicator->id_indicators }} ? 'bg-biru5/40 text-biru1 font-extrabold' : 'text-biru2 hover:bg-putihbg hover:text-biru1'"
                   class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-semibold transition text-left cursor-pointer">
                    <span class="truncate pr-2">{{ $indicator->nama_indikator }}</span>
                    @if ($mCount > 0)
                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ $mCount }} Catatan
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <!-- Tabs Navigation — Interactive Horizontal Scroll (Desktop) -->
    <div x-data="{
        canScrollLeft: false,
        canScrollRight: false,
        updateScrollState() {
            const el = this.$refs.tabContainer;
            if (!el) return;
            this.canScrollLeft = el.scrollLeft > 2;
            this.canScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 2;
        },
        scrollLeft() {
            this.$refs.tabContainer.scrollBy({ left: -220, behavior: 'smooth' });
        },
        scrollRight() {
            this.$refs.tabContainer.scrollBy({ left: 220, behavior: 'smooth' });
        }
    }" x-init="updateScrollState(); $nextTick(() => updateScrollState())"
       class="hidden md:block relative mb-0 z-10 group">

        <!-- Left Arrow Button -->
        <button type="button" @click="scrollLeft()" x-show="canScrollLeft" x-cloak
            title="Geser ke kiri"
            class="absolute -left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Scrollable Tab Container -->
        <div x-ref="tabContainer"
            @scroll="updateScrollState()"
            @wheel.prevent="$refs.tabContainer.scrollLeft += $event.deltaY"
            class="flex items-stretch overflow-x-auto overflow-y-hidden w-full pb-0.5 -mb-px custom-scrollbar scrollbar-thin scrollbar-thumb-biru4/30 scrollbar-track-transparent scroll-smooth pt-2">
            @foreach ($indicators as $indicator)
                @php $catatanCount = $notesPerIndicator[$indicator->id_indicators] ?? 0; @endphp
                <button type="button" @click="activeTab = {{ $indicator->id_indicators }}"
                    :class="activeTab === {{ $indicator->id_indicators }} ?
                        'bg-biru2 border-biru2 z-20 text-white shadow-sm -mb-px pb-3.5' :
                        'bg-white border-abubiru hover:bg-putihbg text-biru2'"
                    :style="activeTab === {{ $indicator->id_indicators }} ?
                        'background: radial-gradient(ellipse 140% 140% at 100% 110%, #2563A0 0%, transparent 55%), #184D77;' :
                        ''"
                    class="shrink-0 min-w-35 md:min-w-[calc(100%/6)] relative px-3 py-3 text-center cursor-pointer transition-all select-none border-biru5 border-t border-l border-r rounded-tl-xl rounded-tr-xl flex flex-col justify-center">
                    
                    {{-- Floating Badge Catatan Verifikator --}}
                    @if ($catatanCount > 0)
                        <span class="absolute -top-2 right-2 z-30 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-md border-2 border-white ring-1 ring-amber-300"
                            title="{{ $catatanCount }} pertanyaan memiliki catatan verifikator">
                            <svg class="w-2.5 h-2.5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>{{ $catatanCount }}</span>
                        </span>
                    @endif

                    <div
                        :class="activeTab === {{ $indicator->id_indicators }} ?
                            'text-white text-sm md:text-sm font-bold leading-tight line-clamp-2' :
                            'text-biru2 text-sm md:text-sm font-bold leading-tight line-clamp-2'">
                        {{ $indicator->nama_indikator }}
                    </div>
                    <div class="flex items-center justify-center gap-1.5 mt-1">
                        <span :class="activeTab === {{ $indicator->id_indicators }} ? 'text-white/80' : 'text-biru4'"
                            class="text-xs md:text-sm font-medium">
                            {{ $indicator->subIndikators->sum(fn($s) => $s->pertanyaans->count()) }} pertanyaan
                        </span>
                        @if ($catatanCount > 0)
                            <span class="hidden lg:inline-flex items-center gap-1 px-1.5 py-0.2 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-700"
                                :class="activeTab === {{ $indicator->id_indicators }} ? 'bg-amber-400 text-amber-950 font-black' : ''">
                                {{ $catatanCount }} Catatan
                            </span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        <!-- Right Arrow Button -->
        <button type="button" @click="scrollRight()" x-show="canScrollRight" x-cloak
            title="Geser ke kanan"
            class="absolute -right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <!-- Questions Container -->
    <div id="verif-questions-container" class="bg-white border border-abubiru border-t-0 shadow-sm mb-6 rounded-b-xl rounded-tr-xl overflow-hidden relative">
        @foreach($indicators as $indicator)
            <div x-show="activeTab === {{ $indicator->id_indicators }}" x-cloak>
                @php
                    $qCounter = 1;
                    $totalSoalInd = $indicator->subIndikators->sum(fn($s) => $s->pertanyaans->count());
                @endphp

                <div class="bg-biru2 text-white px-6 py-5">
                    <div>
                        <h2 class="text-base font-bold tracking-wide mb-1 uppercase">
                            {{ $indicator->nama_indikator }}</h2>
                        <p class="text-sm text-white/90 font-light mb-3">
                            {{ $indicator->deskripsi_indikator }}
                        </p>
                        <span class="inline-block bg-white rounded-full px-4 py-1.5 text-xs font-bold text-oren2 shadow-sm">
                            {{ $totalSoalInd }} pertanyaan dalam indikator ini
                        </span>
                    </div>
                </div>

                @foreach ($indicator->subIndikators as $sub)
                    <div x-data="{ openSub: true }" @expand-sub-indicators.window="openSub = true"
                        class="border-b border-abubiru/40">
                        <button type="button" @click="openSub = !openSub"
                            class="w-full flex items-center justify-between bg-abubiru px-6 py-2.5 text-left border-y border-abubiru select-none">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-extrabold text-biru1 tracking-wider">{{ strtoupper($sub->nama_sub_indikator) }}</span>
                                <span
                                    class="text-xs text-biru4 font-bold bg-biru5/50 px-2.5 py-0.5 rounded-full">Sub
                                    Indikator • {{ $sub->pertanyaans->count() }} Pertanyaan</span>
                            </div>
                            <svg class="w-4 h-4 text-biru3 transition-transform duration-200 mr-1"
                                :class="openSub ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openSub" x-cloak class="divide-y divide-abubiru/30">
                            @foreach ($sub->pertanyaans as $q)
                                @php
                                    $ans = $answers->get($q->id_master_pertanyaan);
                                    $val = $ans ? ($ans->answer ? 'Ya' : 'Tidak') : '';
                                    $ppidVal = $ans ? $ans->link_ppid : '';
                                    $driveVal = $ans ? $ans->link_drive : '';
                                    $score = $ans ? $ans->answer_score : 0.0;
                                    $note = $ans ? $ans->evaluator_note : '';
                                    $isWrong = ($ans && $ans->answer == false) || ($ans && $ans->answer_score == 0 && $q->bobot_pertanyaan > 0);
                                    $statusText = $isWrong ? 'danger' : 'success';
                                    $currentQNo = $qCounter++;
                                @endphp
                                <x-evaluasi.question-block 
                                    :mode="$canVerify ? 'verifikator' : 'view'" 
                                    :number="$currentQNo"
                                    :question="$q->ques_text" 
                                    :name="'q' . $q->id_master_pertanyaan"
                                    :value="$val"
                                    :linkPpid="$ppidVal" 
                                    :linkDrive="$driveVal"
                                    :docDrive="$q->link_drive"
                                    :docPpid="$q->link_ppid"
                                    :deskripsi="$q->ques_description"
                                    :dokumen="$q->doc_requirement" 
                                    score="{{ number_format($score, 2) }}"
                                    note="{{ $note }}"
                                    status="{{ $statusText }}"
                                    maxScore="{{ $q->bobot_pertanyaan }}"
                                    questionId="{{ $q->id_master_pertanyaan }}"
                                    verifikatorNote="{{ $ans?->verifikator_note ?? '' }}"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <!-- Action Buttons (Bottom Bar) -->
    @if ($canVerify)
        <div class="flex flex-wrap justify-end gap-3 mb-6">
            <button type="button" @click="showDraftModal = true"
                class="flex items-center gap-2 px-6 py-2.5 rounded-lg border border-biru2/30 text-biru2 font-bold bg-biru5/20 hover:bg-biru5/40 transition shadow-sm text-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
                Simpan Draft
            </button>
            <button type="button" @click="showConfirmModal = true"
                class="flex items-center gap-2 px-6 py-2.5 rounded-lg bg-hijau text-white font-bold hover:bg-hijau/90 transition shadow-sm text-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Kirim
            </button>
        </div>
    @endif

    <!-- Modal Simpan Draft -->
    <div x-show="showDraftModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-draft-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-biru1/70 backdrop-blur-xs" aria-hidden="true" @click="showDraftModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg w-full border border-abubiru relative z-10" @click.stop>
                <div class="px-6 py-5 border-b border-abubiru bg-putihbg flex justify-between items-center">
                    <h3 class="text-lg font-black text-biru1" id="modal-draft-title">
                        Simpan Draft Verifikasi
                    </h3>
                    <button type="button" @click="showDraftModal = false" class="text-biru4 hover:text-merah1 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="verif-draft-form" action="{{ route('verifikator.verifikasi-saq.draft', ['id' => $submission->id_submission]) }}" method="POST"
                    @submit.prevent="submitDraftForm($el)">
                    @csrf
                    <div class="px-6 py-6">
                        <p class="text-sm text-biru2 mb-4 font-medium">
                            Catatan verifikasi umum dan catatan per nomor soal akan disimpan sebagai <strong>Draft Verifikasi</strong>. Status kuesioner belum difinalisasi ke Operator/Evaluator.
                        </p>
                        
                        <div class="mb-2">
                            <label for="draft_verification_note" class="block text-sm font-bold text-biru1 mb-2">
                                Catatan Verifikasi Umum <span class="text-xs text-biru3 font-normal">(opsional)</span>
                            </label>
                            <textarea id="draft_verification_note" name="verification_note" rows="4"
                                class="w-full px-4 py-3 rounded-xl border border-abubiru bg-putihbg text-sm focus:ring-2 focus:ring-biru2 focus:border-biru2 outline-none transition"
                                placeholder="Masukkan catatan draft bila ada...">{{ old('verification_note', $submission->verification_note) }}</textarea>
                        </div>
                    </div>
                    
                    <div class="px-6 py-5 bg-putihbg border-t border-abubiru flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showDraftModal = false"
                            class="px-5 py-2.5 rounded-lg border border-abubiru text-biru3 font-bold hover:bg-abubiru/50 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                            class="px-5 py-2.5 rounded-lg bg-biru2 text-white font-bold hover:bg-biru2/90 transition text-sm shadow-sm cursor-pointer">
                            Simpan Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Kirim (Setujui / Kembalikan) -->
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-biru1/70 backdrop-blur-xs" aria-hidden="true" @click="showConfirmModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg w-full border border-abubiru relative z-10" @click.stop>
                <div class="px-6 py-5 border-b border-abubiru bg-putihbg flex justify-between items-center">
                    <h3 class="text-lg font-black text-biru1" id="modal-title">
                        Konfirmasi Keputusan Verifikasi
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-biru4 hover:text-merah1 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="verif-decision-form" action="{{ route('verifikator.verifikasi-saq.decision', ['id' => $submission->id_submission]) }}" method="POST"
                    @submit.prevent="submitVerifForm($el, decision)">
                    @csrf
                    <input type="hidden" name="decision" :value="decision">
                    <div class="px-6 py-6">
                        <!-- Pilihan Keputusan (Radio Toggle) -->
                        <div class="mb-5">
                            <label class="block text-sm font-bold text-biru1 mb-2.5">
                                Pilih Tindakan <span class="text-merah1">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label @click="decision = 'approve'"
                                    :class="decision === 'approve' ? 'border-hijau bg-hijau/10 text-hijau shadow-xs' : 'border-abubiru/60 bg-white text-biru3 hover:bg-putihbg'"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition select-none font-bold text-sm">
                                    <input type="radio" name="decision_toggle" value="approve" :checked="decision === 'approve'" class="w-4 h-4 accent-hijau">
                                    <span>Setujui</span>
                                </label>
                                <label @click="decision = 'return'"
                                    :class="decision === 'return' ? 'border-merah1 bg-merah1/10 text-merah1 shadow-xs' : 'border-abubiru/60 bg-white text-biru3 hover:bg-putihbg'"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition select-none font-bold text-sm">
                                    <input type="radio" name="decision_toggle" value="return" :checked="decision === 'return'" class="w-4 h-4 accent-merah1">
                                    <span>Kembalikan</span>
                                </label>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm text-biru2 mb-4 font-medium leading-relaxed">
                            <template x-if="decision === 'approve'">
                                <span>Pengajuan akan <strong>disetujui</strong> dan diteruskan ke tahap Evaluasi oleh Tim Evaluator Provinsi.</span>
                            </template>
                            <template x-if="decision === 'return'">
                                <span>Pengajuan akan <strong>dikembalikan</strong> kepada Operator BPS Kab/Kota untuk dilakukan perbaikan.</span>
                            </template>
                        </p>
                        
                        <div class="mb-2">
                            <label for="verification_note" class="block text-sm font-bold text-biru1 mb-2">
                                Catatan / Alasan <span class="text-merah1">*</span>
                            </label>
                            <textarea id="verification_note" name="verification_note" rows="4" required
                                class="w-full px-4 py-3 rounded-xl border border-abubiru bg-putihbg text-sm focus:ring-2 focus:ring-biru2 focus:border-biru2 outline-none transition"
                                placeholder="Masukkan catatan atau alasan keputusan verifikasi...">{{ old('verification_note', $submission->verification_note) }}</textarea>
                            <p class="mt-1.5 text-xs text-biru4 font-medium">Catatan ini wajib diisi dan akan dikirimkan kepada Operator.</p>
                        </div>
                    </div>
                    
                    <div class="px-6 py-5 bg-putihbg border-t border-abubiru flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showConfirmModal = false"
                            class="px-5 py-2.5 rounded-lg border border-abubiru text-biru3 font-bold hover:bg-abubiru/50 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                            :class="decision === 'approve' ? 'px-5 py-2.5 rounded-lg bg-hijau text-white font-bold hover:bg-hijau/90 transition text-sm shadow-sm cursor-pointer' : 'px-5 py-2.5 rounded-lg bg-merah1 text-white font-bold hover:bg-merah1/90 transition text-sm shadow-sm cursor-pointer'"
                            x-text="decision === 'approve' ? 'Konfirmasi Setujui' : 'Konfirmasi Kembalikan'">
                            Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <div x-data="{ show: false }"
         x-init="$nextTick(() => {
             const container = $el.closest('.overflow-y-auto') || window;
             container.addEventListener('scroll', () => {
                 show = (container === window ? window.scrollY : container.scrollTop) > 150;
             });
         })"
         class="fixed bottom-6 right-6 z-9999">
        <button x-show="show"
                @click="($el.closest('.overflow-y-auto') || window).scrollTo({ top: 0, behavior: 'smooth' })"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                class="w-12 h-12 rounded-full bg-biru6 text-white flex items-center justify-center shadow-lg hover:opacity-90 focus:outline-none transition-all duration-300 transform hover:scale-110 active:scale-95"
                title="Scroll ke atas">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
            </svg>
        </button>
    </div>

</x-layouts.app>

<script>
function collectPerQuestionNotes(form) {
    form.querySelectorAll('input[name^="verifikator_note["]').forEach(function(el) {
        el.remove();
    });
    document.querySelectorAll('input[name]').forEach(function(el) {
        if (el.name.startsWith('verifikator_note[') && !form.contains(el)) {
            var clone = document.createElement('input');
            clone.type = 'hidden';
            clone.name = el.name;
            clone.value = el.value;
            form.appendChild(clone);
        }
    });
}

function submitDraftForm(form) {
    collectPerQuestionNotes(form);
    form.submit();
}

function submitVerifForm(form, decision) {
    if (decision === 'return') {
        collectPerQuestionNotes(form);
    }
    form.submit();
}
</script>
