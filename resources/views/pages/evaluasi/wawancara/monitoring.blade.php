@php
    $userRole = Auth::user()?->role?->nama_role;
    $isPenilaiEksternal = $userRole === 'penilai_eksternal';
    $isSuperEval = $userRole === 'super_evaluator';
    $routePrefix = $isSuperEval ? 'super-evaluator' : ($isPenilaiEksternal ? 'penilai-eksternal' : 'evaluator');
    $homeRoute = route($routePrefix . '.index');
    $scheduleBatchAction = Route::has('evaluasi.wawancara.schedule-batch')
        ? route('evaluasi.wawancara.schedule-batch')
        : route('super-evaluator.schedule-wawancara-batch');
@endphp
<x-layouts.app title="Monitoring Wawancara - HaloKIP">
<script>
    window.evaluatorsMap = {
        @if(isset($evaluators))
            @foreach($evaluators as $ev)
                '{{ $ev->id_user }}': {!! json_encode($ev->name) !!},
            @endforeach
        @endif
        @if(isset($externalEvaluators))
            @foreach($externalEvaluators as $ev)
                '{{ $ev->id_user }}': {!! json_encode($ev->name) !!},
            @endforeach
        @endif
    };
</script>

<div x-data="{
    showJadwalModal: false,
    showDetailModal: false,
    detailEvent: {},
    selectedInterviewId: '',
    selectedSatkerName: '',
    isEditScheduled: false,
    modalTitle: 'Jadwalkan Wawancara',
    interviewAt: '',
    interviewDate: '',
    interviewer1Id: '',
    interviewer2Id: '',
    interviewer3Id: '',
    externalInterviewerId: '',
    externalInterviewerIds: [],
    jenisInterview: 'online',
    interviewLoc: '',
    meetsLink: '',
    platformOnline: '',
    selectedInterviewIds: [],
    interviewsMap: @js(isset($allEligibleSubmissions) ? $allEligibleSubmissions->mapWithKeys(fn($sub) => [$sub->id_result => ($sub->user->masterSatker ? ('(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker) : 'BPS Kab/Kota')])->toArray() : []),
    interviewsList: @js(isset($allEligibleSubmissions) ? $allEligibleSubmissions->map(fn($sub) => ['id' => (string) $sub->id_result, 'name' => ($sub->user->masterSatker ? ('(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker) : 'BPS Kab/Kota'), 'is_scheduled' => ($sub->interview && $sub->interview->interview_at !== null)])->values()->toArray() : []),
    evaluatorsList: @js(isset($evaluators) ? collect($evaluators)->map(fn($ev) => ['id' => (string) $ev->id_user, 'name' => $ev->name])->values()->toArray() : []),
    externalEvaluatorsList: @js(collect($externalEvaluators ?? \App\Models\User::role('penilai_eksternal')->get())->map(fn($ev) => ['id' => (string) $ev->id_user, 'name' => $ev->name])->values()->toArray()),
    searchKabkot: '',
    searchEvaluator: '',
    searchExternalEvaluator: '',
    activeDropdown: null,
    get filteredInterviews() {
        const query = this.searchKabkot.toLowerCase();
        return this.interviewsList.filter(item => {
            const isSelected = this.selectedInterviewIds.map(String).includes(String(item.id));
            const matchesSearch = item.name.toLowerCase().includes(query);
            return !item.is_scheduled && !isSelected && matchesSearch;
        });
    },
    get filteredEvaluators() {
        const query = this.searchEvaluator.toLowerCase();
        return this.evaluatorsList.filter(item => {
            const isSelected = String(this.interviewer1Id) === String(item.id) || 
                               String(this.interviewer2Id) === String(item.id) || 
                               String(this.interviewer3Id) === String(item.id);
            const matchesSearch = item.name.toLowerCase().includes(query);
            return !isSelected && matchesSearch;
        });
    },
    get filteredExternalEvaluators() {
        const query = this.searchExternalEvaluator.toLowerCase();
        return this.externalEvaluatorsList.filter(item => {
            const isSelected = this.externalInterviewerIds.map(String).includes(String(item.id));
            const matchesSearch = item.name.toLowerCase().includes(query);
            return !isSelected && matchesSearch;
        });
    },
    getEvaluatorName(id) {
        return window.evaluatorsMap[id] || '';
    },
    getExternalEvaluatorName(id) {
        const ext = this.externalEvaluatorsList.find(e => String(e.id) === String(id));
        return ext ? ext.name : (window.evaluatorsMap[id] || '');
    },
    addExternalEvaluator(id) {
        if (!id) return;
        if (!this.externalInterviewerIds.map(String).includes(String(id))) {
            this.externalInterviewerIds.push(String(id));
        }
    },
    removeExternalEvaluator(id) {
        this.externalInterviewerIds = this.externalInterviewerIds.filter(x => String(x) !== String(id));
    },
    interviewStartLimit: '{{ $period->interview_start }}',
    interviewEndLimit: '{{ $period->interview_end }}',
    formatDateIndo(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    },
    init() {
        this.$watch('showJadwalModal', value => {
            if (value) {
                this.searchKabkot = '';
                this.searchEvaluator = '';
                this.searchExternalEvaluator = '';
                this.activeDropdown = null;
            }
        });
        this.$watch('interviewDate', value => {
            if (value) {
                if (value < this.interviewStartLimit || value > this.interviewEndLimit) {
                    const startFormatted = this.formatDateIndo(this.interviewStartLimit);
                    const endFormatted = this.formatDateIndo(this.interviewEndLimit);
                    const valueFormatted = this.formatDateIndo(value);
                    alert(`Peringatan: Tanggal wawancara yang dipilih (${valueFormatted}) berada di luar periode tahapan wawancara (${startFormatted} s.d. ${endFormatted}). Tanggal otomatis disesuaikan.`);
                    if (value < this.interviewStartLimit) {
                        this.interviewDate = this.interviewStartLimit;
                    } else if (value > this.interviewEndLimit) {
                        this.interviewDate = this.interviewEndLimit;
                    }
                }
            }
        });
    },
    addEvaluator(id) {
        if (!id) return;
        if (this.interviewer1Id == id || this.interviewer2Id == id || this.interviewer3Id == id) return;
        
        if (!this.interviewer1Id) {
            this.interviewer1Id = id;
        } else if (!this.interviewer2Id) {
            this.interviewer2Id = id;
        } else if (!this.interviewer3Id) {
            this.interviewer3Id = id;
        }
    },
    removeEvaluator(id) {
        if (this.interviewer1Id == id) {
            this.interviewer1Id = '';
        } else if (this.interviewer2Id == id) {
            this.interviewer2Id = '';
        } else if (this.interviewer3Id == id) {
            this.interviewer3Id = '';
        }
    },
    get saveDisabled() {
        return [this.interviewer1Id, this.interviewer2Id, this.interviewer3Id].filter(id => id).length < 3;
    },
    async submitJadwal(e) {
        if (this.selectedInterviewIds.length === 0) {
            return window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: 'Harap pilih minimal 1 Kabupaten/Kota.' }
            }));
        }
        if (!this.interviewer1Id || !this.interviewer2Id || !this.interviewer3Id) {
            return window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: 'Harap pilih tepat 3 orang pewawancara.' }
            }));
        }

        await window.submitFormAsync(e.target, () => {
            this.showJadwalModal = false;
            window.location.reload();
        });
    }
}"
@open-event-detail.window="detailEvent = $event.detail; showDetailModal = true;"
>
    <x-ui.page-header title="Monitoring Jadwal Wawancara"
        description="Pantau progress dan jadwal wawancara untuk Kabupaten/Kota disini." :breadcrumb="[
            'Beranda' => $homeRoute,
            'Monitoring Wawancara' => '#',
        ]">
    </x-ui.page-header>

    <!-- Info Box: Desktop (static) -->
    <div class="hidden sm:flex rounded-xl bg-birumuda border border-biru5 px-6 py-4.5 mb-8 items-center gap-4 shadow-sm">
        <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="text-left">
            <div class="font-bold text-biru6 text-sm mb-0.5">Informasi</div>
            <p class="text-sm text-biru6 font-medium leading-relaxed">
                @if($isPenilaiEksternal)
                    Penilaian dilakukan secara independen oleh Pewawancara yang ditugaskan. Anda hanya dapat memberikan nilai untuk Kab/Kota yang ditugaskan kepada Anda. Skor akhir adalah rata-rata dari semua penilaian.
                @else
                    Penilaian dilakukan secara independen oleh 3 Pewawancara/Evaluator yang ditugaskan. Skor akhir adalah rata-rata dari ketiga penilaian.
                @endif
            </p>
        </div>
    </div>

    <!-- Info Box: Mobile (collapsible) -->
    <div x-data="{ open: false }" class="sm:hidden rounded-xl bg-birumuda border border-biru5 mb-6 shadow-sm overflow-hidden">
        <button type="button" @click="open = !open" class="w-full flex items-center gap-3.5 p-4">
            <div class="w-8 h-8 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1 text-left">
                <div class="font-bold text-biru6 text-xs">Informasi</div>
                <div class="text-xs text-biru6/70 font-medium" x-text="open ? 'Tap untuk tutup' : 'Tap untuk baca'"></div>
            </div>
            <svg class="w-4 h-4 text-biru6 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" class="px-4 pb-4 text-left" style="display: none;">
            <p class="text-xs text-biru6 font-medium leading-relaxed">
                Penilaian dilakukan secara independen oleh Pewawancara/Evaluator yang ditugaskan. Skor akhir adalah rata-rata dari seluruh penilaian.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs Navigation & Action Button Aligned -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-abubiru mb-6 gap-3 sm:gap-4">
        <div class="flex items-center gap-2 order-last sm:order-first">
            <button id="tab-kabkot-btn" onclick="switchTab('kabkot')"
                class="px-5 py-3 text-sm font-bold border-b-2 border-biru1 text-biru1 transition duration-200 cursor-pointer">
                Daftar KabKot
            </button>
            <button id="tab-hari-btn" onclick="switchTab('hari')"
                class="px-5 py-3 text-sm font-bold border-b-2 border-transparent text-biru3 hover:text-biru1 transition duration-200 cursor-pointer">
                Daftar Hari
            </button>
        </div>
        @if($isSuperEval)
        <div class="flex justify-end order-first sm:order-last sm:pb-2.5">
            <button type="button" 
                    @click="selectedInterviewIds = []; selectedInterviewId = ''; isEditScheduled = false; modalTitle = 'Jadwalkan Wawancara'; interviewDate = ''; platformOnline = ''; interviewLoc = ''; meetsLink = ''; interviewer1Id = ''; interviewer2Id = ''; interviewer3Id = ''; externalInterviewerId = ''; externalInterviewerIds = []; showJadwalModal = true" 
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-oren2 hover:bg-oren2/90 text-white px-5 py-2.5 text-sm font-bold shadow-md hover:shadow-lg transition cursor-pointer w-full sm:w-auto">
                <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Jadwalkan Wawancara</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Tab 1: Daftar Kabupaten & Kota -->
    <div id="tab-kabkot-content">
        <livewire:evaluasi.monitor-wawancara-table :periodeId="$period->id_periode" />
    </div>

    <!-- Tab 2: Daftar Hari Wawancara -->
    <div id="tab-hari-content" class="hidden">
        <!-- Period Info Banner -->
        <div
            class="rounded-xl bg-kuning2/20 border border-kuning1 p-4 sm:px-6 sm:py-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 min-w-0">
                <div class="flex items-center gap-2.5 text-oren2 font-bold shrink-0">
                    <svg class="w-5 h-5 text-oren2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-xs sm:text-sm font-extrabold">Tahap Wawancara:</span>
                </div>
                <span class="font-extrabold text-amber-950 text-xs sm:text-sm">
                    {{ \Carbon\Carbon::parse($period->interview_start)->translatedFormat('d F Y') }} — {{ \Carbon\Carbon::parse($period->interview_end)->translatedFormat('d F Y') }}
                    <span class="text-oren2 font-medium">({{ \Carbon\Carbon::parse($period->interview_start)->diffInDays(\Carbon\Carbon::parse($period->interview_end)) + 1 }} hari)</span>
                </span>
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-amber-500/20 shrink-0">
                <span class="text-xs sm:text-sm text-amber-900 font-bold">{{ $interviews->count() }} kabkot terjadwal</span>
                <span class="inline-flex items-center gap-1.5 bg-oren2/20 text-oren2 px-2.5 py-1 rounded-full text-xs sm:text-xs font-bold uppercase border border-amber-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>Aktif
                </span>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden mb-6">
            <div class="px-4 py-4 sm:px-8 sm:py-5 border-b border-abubiru/30 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                <h2 class="text-lg sm:text-xl font-bold text-biru1">Kalender Wawancara</h2>
                <span class="text-xs sm:text-sm text-biru4 font-medium">{{ \Carbon\Carbon::parse($period->interview_start)->diffInDays(\Carbon\Carbon::parse($period->interview_end)) + 1 }} hari dalam tahapan ini</span>
            </div>
            <div class="p-3 sm:p-6">
                <div id="wawancara-calendar"></div>
            </div>
        </div>
    </div>

    @if(!$isPenilaiEksternal)
    <!-- Schedule Modal (Hanya untuk Super Evaluator / Evaluator) -->
    <x-modals.jadwal title="Jadwalkan Wawancara">
        <form method="POST" action="{{ $scheduleBatchAction }}" id="jadwal-form"
              @submit.prevent="submitJadwal">
            @csrf
            
            <div class="flex items-center gap-2 mb-5" x-show="isEditScheduled">
                <span class="text-md font-bold text-biru2">Nama Satker:</span>
                <span class="inline-flex items-center rounded-lg bg-slate-100 border border-slate-200 text-md font-extrabold text-slate-700 px-3 py-1 shadow-xs" x-text="selectedSatkerName"></span>
            </div>
            
            <template x-for="id in selectedInterviewIds" :key="id">
                <input type="hidden" name="interview_ids[]" :value="id">
            </template>
            
            <input type="hidden" name="interview_at" :value="interviewDate">
            <input type="hidden" name="interviewer_1_id" :value="interviewer1Id">
            <input type="hidden" name="interviewer_2_id" :value="interviewer2Id">
            <input type="hidden" name="interviewer_3_id" :value="interviewer3Id">
            <input type="hidden" name="external_interviewer_id" :value="externalInterviewerIds[0] || ''">
            <template x-for="extId in externalInterviewerIds" :key="extId">
                <input type="hidden" name="id_external_interviewer[]" :value="extId">
            </template>

            <div class="space-y-5">
                <div class="space-y-1">
                    <span class="block text-md font-bold text-biru2 border-b border-slate-100">Jadwal Wawancara</span>
                    <div>
                        <label class="block text-md font-medium text-biru3 mb-1.5">Tanggal Wawancara</label>
                        <input type="date" x-model="interviewDate" min="{{ $period->interview_start }}" max="{{ $period->interview_end }}" required class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-md font-medium text-abu shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                    </div>
                </div>

                <div class="space-y-1">
                    <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Tautan Wawancara</span>
                    <div class="space-y-2">
                        <input type="url" name="meets_link" x-model="meetsLink" required placeholder="https://..." class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-abu shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                    </div>
                </div>

                <div x-show="!isEditScheduled" class="space-y-1">
                    <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Pilih Kabupaten / Kota</span>
                    <div class="space-y-2">                   
                        <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5">
                            <template x-if="selectedInterviewIds.length === 0">
                                <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada Kabupaten / Kota terpilih</span>
                            </template>
                            <template x-for="id in selectedInterviewIds" :key="id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                    <span x-text="interviewsMap[id] || 'Kab/Kota'"></span>
                                    <button type="button" @click="selectedInterviewIds = selectedInterviewIds.filter(vId => String(vId) !== String(id))" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        
                        <div class="relative" @click.outside="if (activeDropdown === 'kabkot') activeDropdown = null">
                            <div class="relative">
                                <input type="text"
                                       x-model="searchKabkot"
                                       @focus="activeDropdown = 'kabkot'"
                                       @mousedown="if (activeDropdown === 'kabkot') { activeDropdown = null; $event.preventDefault(); } else { activeDropdown = 'kabkot'; }"
                                       @keydown.escape="if (activeDropdown === 'kabkot') activeDropdown = null"
                                       placeholder="Cari Kabupaten / Kota... (Dapat pilih lebih dari satu)"
                                       class="w-full rounded-xl px-3 py-2.5 bg-white text-sm font-semibold border border-abubiru text-slate-700 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition outline-none">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 cursor-pointer"
                                     @click.stop="activeDropdown = (activeDropdown === 'kabkot' ? null : 'kabkot')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div x-show="activeDropdown === 'kabkot'" class="absolute z-60 w-full mt-1.5 max-h-60 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1" style="display: none;">
                                <template x-for="item in filteredInterviews" :key="item.id">
                                    <button type="button"
                                            @click="if (!selectedInterviewIds.map(String).includes(String(item.id))) { selectedInterviewIds.push(item.id); } searchKabkot = ''; activeDropdown = null;"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-biru1 font-semibold transition">
                                        <span x-text="item.name"></span>
                                    </button>
                                </template>
                                <div x-show="filteredInterviews.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                    Tidak ada Kabupaten / Kota yang cocok
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-1">
                        <span class="block text-md font-bold text-biru2">Pilih Pewawancara</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full transition"
                            :class="{
                                'bg-red-50 text-red-500 border border-red-200': [interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length < 3,
                                'bg-green-50 text-green-600 border border-green-200': [interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length === 3
                            }">
                            <span x-text="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length + '/3 dipilih'"></span>
                        </span>
                    </div>
                    <div class="space-y-2">                        
                        <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border rounded-xl min-h-11.5 transition"
                            :class="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length === 3
                                ? 'border-green-300 bg-green-50/50'
                                : 'border-slate-200'">
                            <template x-if="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length === 0">
                                <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada pewawancara dipilih</span>
                            </template>
                            <template x-for="id in [interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id)" :key="id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                    <span x-text="getEvaluatorName(id)"></span>
                                    <button type="button" @click="removeEvaluator(id)" class="text-slate-400 hover:text-red-500 focus:outline-none cursor-pointer transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        
                        <div class="relative" @click.outside="if (activeDropdown === 'evaluator') activeDropdown = null">
                            <div class="relative">
                                <input type="text"
                                       x-model="searchEvaluator"
                                       @focus="if ([interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length < 3) { activeDropdown = 'evaluator'; }"
                                       @mousedown="if (activeDropdown === 'evaluator') { activeDropdown = null; $event.preventDefault(); } else if ([interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length < 3) { activeDropdown = 'evaluator'; }"
                                       @keydown.escape="if (activeDropdown === 'evaluator') activeDropdown = null"
                                       :placeholder="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length >= 3 ? '✓ 3 pewawancara telah dipilih' : 'Cari nama evaluator... (Dapat pilih lebih dari satu)'"
                                       :disabled="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length >= 3"
                                       class="w-full rounded-xl px-3 py-2.5 bg-white text-sm font-semibold shadow-sm focus:ring-2 transition outline-none"
                                       :class="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length >= 3 
                                           ? 'border border-green-400 text-green-700 focus:ring-green-200 focus:border-green-400 bg-green-50' 
                                           : 'border border-abubiru text-slate-700 focus:ring-biru4/20 focus:border-biru4'">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400"
                                     :class="[interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length >= 3 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                                     @click.stop="if ([interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length < 3) { activeDropdown = (activeDropdown === 'evaluator' ? null : 'evaluator'); }">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div x-show="activeDropdown === 'evaluator' && [interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length < 3"
                                 class="absolute z-60 w-full mt-1.5 max-h-60 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1"
                                 style="display: none;">
                                <template x-for="item in filteredEvaluators" :key="item.id">
                                    <button type="button"
                                            @click="addEvaluator(item.id); searchEvaluator = ''; if ([interviewer1Id, interviewer2Id, interviewer3Id].filter(id => id).length >= 3) { activeDropdown = null; }"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-biru1 font-semibold transition">
                                        <span x-text="item.name"></span>
                                    </button>
                                </template>
                                <div x-show="filteredEvaluators.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                    Tidak ada evaluator yang cocok
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-1">
                        <span class="block text-md font-bold text-biru2">Penilai Eksternal (Opsional)</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                            <span x-text="externalInterviewerIds.length + ' dipilih'"></span>
                        </span>
                    </div>
                    <div class="space-y-2">                        
                        <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5 transition">
                            <template x-if="externalInterviewerIds.length === 0">
                                <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada penilai eksternal dipilih</span>
                            </template>
                            <template x-for="id in externalInterviewerIds" :key="id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-xs font-bold text-purple-800 shadow-2xs">
                                    <span x-text="getExternalEvaluatorName(id)"></span>
                                    <button type="button" @click="removeExternalEvaluator(id)" class="text-purple-400 hover:text-red-500 focus:outline-none cursor-pointer transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        
                        <div class="relative" @click.outside="if (activeDropdown === 'external') activeDropdown = null">
                            <div class="relative">
                                <input type="text"
                                       x-model="searchExternalEvaluator"
                                       @focus="activeDropdown = 'external'"
                                       @mousedown="if (activeDropdown === 'external') { activeDropdown = null; $event.preventDefault(); } else { activeDropdown = 'external'; }"
                                       @keydown.escape="if (activeDropdown === 'external') activeDropdown = null"
                                       placeholder="Cari nama penilai eksternal... (Dapat pilih lebih dari satu)"
                                       class="w-full rounded-xl px-3 py-2.5 bg-white text-sm font-semibold border border-abubiru text-slate-700 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition outline-none">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 cursor-pointer"
                                     @click.stop="activeDropdown = (activeDropdown === 'external' ? null : 'external')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div x-show="activeDropdown === 'external'"
                                 class="absolute z-60 w-full mt-1.5 max-h-60 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1"
                                 style="display: none;">
                                <template x-for="item in filteredExternalEvaluators" :key="item.id">
                                    <button type="button"
                                            @click="addExternalEvaluator(item.id); searchExternalEvaluator = '';"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-purple-50 hover:text-purple-700 font-semibold transition">
                                        <span x-text="item.name"></span>
                                    </button>
                                </template>
                                <div x-show="filteredExternalEvaluators.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                    Tidak ada penilai eksternal yang cocok
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-modals.jadwal>
    @endif

    <x-modals.detail-kegiatan title="Detail Jadwal Wawancara" />
</div>

@push('styles')
    <style>
        .fc-day-muted {
            background-color: #f8fafc !important;
            opacity: 0.6;
        }
        .fc-day-highlighted {
            background-color: #ffffff !important;
            position: relative;
        }
    </style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>
@include('components.charts.monitoring-calendar')
<script>
    function switchTab(tab) {
        const kabkotContent = document.getElementById('tab-kabkot-content');
        const hariContent = document.getElementById('tab-hari-content');
        const kabkotBtn = document.getElementById('tab-kabkot-btn');
        const hariBtn = document.getElementById('tab-hari-btn');

        if (tab === 'kabkot') {
            kabkotContent.classList.remove('hidden');
            hariContent.classList.add('hidden');
            kabkotBtn.classList.add('border-biru1', 'text-biru1');
            kabkotBtn.classList.remove('border-transparent', 'text-biru3');
            hariBtn.classList.remove('border-biru1', 'text-biru1');
            hariBtn.classList.add('border-transparent', 'text-biru3');
        } else {
            kabkotContent.classList.add('hidden');
            hariContent.classList.remove('hidden');
            hariBtn.classList.add('border-biru1', 'text-biru1');
            hariBtn.classList.remove('border-transparent', 'text-biru3');
            kabkotBtn.classList.remove('border-biru1', 'text-biru1');
            kabkotBtn.classList.add('border-transparent', 'text-biru3');

            setTimeout(() => {
                initCalendar();
            }, 50);
        }
    }

    function initCalendar() {
        const calendarEl = document.getElementById('wawancara-calendar');
        if (calendarEl && !calendarEl.dataset.calendarInitialized) {
            calendarEl.dataset.calendarInitialized = 'true';
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                initialDate: '{{ $period->periode_start }}',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    list: 'Daftar'
                },
                events: [
                    @foreach($interviews as $int)
                    {
                        title: '{{ ($int->submission->user->masterSatker ? ('(' . $int->submission->user->masterSatker->kode_satker . ') ' . $int->submission->user->masterSatker->nama_satker) : 'BPS Kab/Kota') }}',
                        start: '{{ \Carbon\Carbon::parse($int->interview_at)->toIso8601String() }}',
                        color: '{{ ($int->interview_score !== null) ? "#1e6091" : "#e67e22" }}',
                        extendedProps: {
                            id: '{{ $int->submission->user->masterSatker->kode_satker ?? "" }}',
                            link: '{{ $int->meets_link }}',
                            status: '{{ ($int->interview_score !== null) ? "selesai" : "belum" }}'
                        }
                    },
                    @endforeach
                ],
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const formatDateStr = (date) => {
                        return date.toLocaleDateString('id-ID', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    };

                    window.dispatchEvent(new CustomEvent('open-event-detail', {
                        detail: {
                            title: info.event.title,
                            waktu: formatDateStr(info.event.start),
                            link: props.link || '',
                            status: props.status || 'belum',
                            gcalUrl: getGoogleCalendarUrl(info.event)
                        }
                    }));
                },
                dayCellDidMount: function(arg) {
                    const date = arg.date;
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    const dateStr = `${y}-${m}-${d}`;

                    const start = '{{ $period->interview_start }}';
                    const end = '{{ $period->interview_end }}';

                    if (dateStr < start || dateStr > end) {
                        arg.el.classList.add('fc-day-muted');
                    } else {
                        arg.el.classList.add('fc-day-highlighted');
                    }
                },
                height: 'auto',
                dayMaxEvents: 3,
            });
            calendar.render();
            window.wawancaraCalendar = calendar;
        }
    }
</script>
@endpush
</x-layouts.app>
