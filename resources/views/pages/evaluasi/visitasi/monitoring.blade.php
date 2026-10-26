@php
    $userRole = Auth::user()?->role?->nama_role;
    $isSuperEval = $userRole === 'super_evaluator';
    $isPenilaiEksternal = $userRole === 'penilai_eksternal';
    $routePrefix = $isSuperEval ? 'super-evaluator' : ($isPenilaiEksternal ? 'penilai-eksternal' : 'evaluator');
    $homeRoute = Route::has($routePrefix . '.index') ? route($routePrefix . '.index') : url('/');
    $scheduleBatchAction = Route::has('evaluasi.visitasi.schedule-batch')
        ? route('evaluasi.visitasi.schedule-batch')
        : (Route::has('super-evaluator.schedule-visitasi-batch') ? route('super-evaluator.schedule-visitasi-batch') : route('evaluator.schedule-visitasi-batch'));
    $monitoringUrl = Route::has('evaluasi.visitasi.monitoring')
        ? route('evaluasi.visitasi.monitoring')
        : route($routePrefix . '.monitor-visitasi');
@endphp
<x-layouts.app title="Monitoring Visitasi - HaloKIP">
    <div x-data="{
        showJadwalModal: false,
        showDetailModal: false,
        detailEvent: {},
        selectedVisitId: '',
        selectedSatkerName: '',
        isEditScheduled: false,
        modalTitle: 'Jadwalkan Visitasi',
        visitAt: '',
        visitMode: 'offline',
        linkMeeting: '',
        visitorIds: [],
        selectedVisitIds: [],
        evaluatorsMap: @js($evaluators->pluck('name', 'id_user')->toArray()),
        visitationsMap: @js($allVisitations->mapWithKeys(fn($vis) => [$vis->id_visit => ($vis->submission->user->masterSatker ? ('(' . $vis->submission->user->masterSatker->kode_satker . ') ' . $vis->submission->user->masterSatker->nama_satker) : 'BPS Kab/Kota')])->toArray()),
        visitationsList: @js($allVisitations->map(fn($vis) => ['id' => (string) $vis->id_visit, 'name' => ($vis->submission->user->masterSatker ? ('(' . $vis->submission->user->masterSatker->kode_satker . ') ' . $vis->submission->user->masterSatker->nama_satker) : 'BPS Kab/Kota'), 'is_scheduled' => $vis->visit_at !== null])->values()->toArray()),
        evaluatorsList: @js(collect($evaluators)->map(fn($ev) => ['id' => (string) $ev->id_user, 'name' => $ev->name])->values()->toArray()),
        searchKabkot: '',
        searchEvaluator: '',
        get filteredVisitations() {
            const query = this.searchKabkot.toLowerCase();
            return this.visitationsList.filter(item => {
                const isSelected = this.selectedVisitIds.map(String).includes(String(item.id));
                const matchesSearch = item.name.toLowerCase().includes(query);
                return !item.is_scheduled && !isSelected && matchesSearch;
            });
        },
        get filteredEvaluators() {
            const query = this.searchEvaluator.toLowerCase();
            return this.evaluatorsList.filter(item => {
                const isSelected = this.visitorIds.map(String).includes(String(item.id));
                const matchesSearch = item.name.toLowerCase().includes(query);
                return !isSelected && matchesSearch;
            });
        },
        getVisitorName(id) {
            return this.evaluatorsMap[id] || '';
        },
        visitStartLimit: '{{ $period->visit_start }}',
        visitEndLimit: '{{ $period->visit_end }}',
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
                }
            });
            this.$watch('visitAt', value => {
                if (value) {
                    if (value < this.visitStartLimit || value > this.visitEndLimit) {
                        const startFormatted = this.formatDateIndo(this.visitStartLimit);
                        const endFormatted = this.formatDateIndo(this.visitEndLimit);
                        const valueFormatted = this.formatDateIndo(value);
                        alert(`Peringatan: Tanggal visitasi yang dipilih (${valueFormatted}) berada di luar periode tahapan visitasi (${startFormatted} s.d. ${endFormatted}). Tanggal otomatis disesuaikan.`);
                        if (value < this.visitStartLimit) {
                            this.visitAt = this.visitStartLimit;
                        } else if (value > this.visitEndLimit) {
                            this.visitAt = this.visitEndLimit;
                        }
                    }
                }
            });
        },
        addVisitor(id) {
            if (!id) return;
            if (!this.visitorIds.map(String).includes(String(id))) {
                this.visitorIds.push(id);
            }
        },
        removeVisitor(id) {
            this.visitorIds = this.visitorIds.filter(vId => String(vId) !== String(id));
        },
        async submitJadwal(e) {
            if (this.visitorIds.length === 0) {
                return window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Harap pilih minimal 1 orang petugas visitasi.' }
                }));
            }

            if (this.visitMode === 'online_meeting' && !this.linkMeeting.trim()) {
                return window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Harap isi link virtual meeting.' }
                }));
            }

            await window.submitFormAsync(e.target, () => {
                this.showJadwalModal = false;
                window.location.reload();
            });
        }
    }" @open-event-detail.window="detailEvent = $event.detail; showDetailModal = true;">
        <x-ui.page-header title="Monitoring Visitasi dan Verifikasi Faktual"
            description="Pantau progres dan jadwal visitasi dan verifikasi Kabupaten/Kota disini." :breadcrumb="[
                'Evaluasi' => '#',
                'Monitoring Visitasi' => $monitoringUrl,
            ]">
        </x-ui.page-header>

        <!-- Info Box: Desktop (static, selalu terbuka) -->
        <div class="hidden sm:flex rounded-2xl bg-birumuda border border-biru5 px-6 py-4.5 mb-8 items-center gap-4 shadow-sm">
            <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-bold text-biru6 text-sm mb-0.5">Informasi</div>
                <p class="text-sm text-biru6 font-medium leading-relaxed">Penilaian visitasi dilakukan oleh 1 atau lebih Petugas dan diatur tanggalnya dengan menekan tombol <strong>Jadwalkan</strong>. Skor akhir adalah rata-rata dari seluruh penilaian petugas.</p>
            </div>
        </div>

        <!-- Info Box: Mobile (collapsible) -->
        <div x-data="{ open: false }" class="sm:hidden rounded-2xl bg-birumuda border border-biru5 mb-6 shadow-sm overflow-hidden">
            <button type="button" @click="open = !open"
                class="w-full flex items-center gap-3.5 p-4">
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
            <div x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="px-4 pb-4 text-left"
                style="display: none;">
                <p class="text-xs text-biru6 font-medium leading-relaxed">Penilaian visitasi dilakukan oleh 1 atau lebih Petugas dan diatur tanggalnya dengan menekan tombol <strong>Jadwalkan</strong>. Skor akhir adalah rata-rata dari seluruh penilaian petugas.</p>
            </div>
        </div>

        <!-- Alert Success/Error -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
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
            @if(Auth::user()?->role?->nama_role === 'super_evaluator')
            <div class="flex justify-end order-first sm:order-last sm:pb-2.5">
                <button type="button" 
                        @click="selectedVisitIds = []; visitorIds = []; visitAt = ''; visitMode = 'offline'; linkMeeting = ''; isEditScheduled = false; modalTitle = 'Jadwalkan Visitasi'; showJadwalModal = true" 
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-oren2 hover:bg-oren2/90 text-white px-5 py-2.5 text-sm font-bold shadow-md hover:shadow-lg transition cursor-pointer w-full sm:w-auto">
                    <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Jadwalkan Visitasi</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Tab 1: Daftar Kabupaten & Kota -->
        <div id="tab-kabkot-content">
            <livewire:evaluasi.monitor-visitasi-table :periodeId="$period->id_periode" />
        </div>

        <!-- Tab 2: Daftar Hari Visitasi (FullCalendar) -->
        <div id="tab-hari-content" class="hidden">
            <!-- Period Info Banner (Yellow Theme) -->
            <div
                class="rounded-xl bg-kuning2/20 border border-kuning1 p-4 sm:px-6 sm:py-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 min-w-0">
                    <div class="flex items-center gap-2.5 text-oren2 font-bold shrink-0">
                        <svg class="w-5 h-5 text-oren2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-xs sm:text-sm font-extrabold">Tahap Visitasi:</span>
                    </div>
                    <span class="font-extrabold text-amber-950 text-xs sm:text-sm">
                        {{ \Carbon\Carbon::parse($period->visit_start)->translatedFormat('d F Y') }} —
                        {{ \Carbon\Carbon::parse($period->visit_end)->translatedFormat('d F Y') }}
                        <span
                            class="text-oren2 font-medium">({{ \Carbon\Carbon::parse($period->visit_start)->diffInDays(\Carbon\Carbon::parse($period->visit_end)) + 1 }}
                            hari)</span>
                    </span>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-amber-500/20 shrink-0">
                    <span class="text-xs sm:text-sm text-amber-900 font-bold">{{ $visitations->count() }} kabkot terjadwal</span>
                    <span
                        class="inline-flex items-center gap-1.5 bg-oren2/20 text-oren2 px-2.5 py-1 rounded-full text-xs sm:text-xs font-bold uppercase border border-amber-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>Aktif
                    </span>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden mb-6">
                <div class="px-4 py-4 sm:px-8 sm:py-5 border-b border-abubiru/30 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                    <h2 class="text-lg sm:text-xl font-bold text-biru1">Kalender Visitasi</h2>
                    <span
                        class="text-xs sm:text-sm text-biru4 font-medium">{{ \Carbon\Carbon::parse($period->visit_start)->diffInDays(\Carbon\Carbon::parse($period->visit_end)) + 1 }}
                        hari dalam tahapan ini</span>
                </div>
                <div class="p-3 sm:p-6">
                    <div id="visitasi-calendar"></div>
                </div>
            </div>
        </div>

        <!-- Schedule Modal -->
        <x-modals.jadwal title="Jadwalkan Visitasi">
            <form method="POST" action="{{ $scheduleBatchAction }}" id="jadwal-form"
                  @submit.prevent="submitJadwal">
                @csrf

                <!-- Hidden: visit_mode -->
                <input type="hidden" name="visit_mode" :value="visitMode">

                <!-- Hidden: link_meeting (hanya dikirim bila mode meeting) -->
                <input type="hidden" name="link_meeting" :value="linkMeeting">

                <!-- Hidden inputs for selected visit IDs -->
                <template x-for="id in selectedVisitIds" :key="id">
                    <input type="hidden" name="visit_ids[]" :value="id">
                </template>
                
                <!-- Hidden inputs for selected visitor IDs (array submitted to controller) -->
                <template x-for="id in visitorIds" :key="id">
                    <input type="hidden" name="id_visitor[]" :value="id">
                </template>

                <div class="space-y-5">
                    <!-- Satker Badge Info (shown only in Edit mode) -->
                    <div class="flex items-center gap-2 mb-5" x-show="isEditScheduled">
                        <span class="text-md font-bold text-biru2">Nama Satker:</span>
                        <span class="inline-flex items-center rounded-lg bg-slate-100 border border-slate-200 text-md font-extrabold text-slate-700 px-3 py-1 shadow-xs" x-text="selectedSatkerName"></span>
                    </div>

                    <!-- ===== SECTION: Moda Visitasi (Radio Cards) ===== -->
                    <div>
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1 mb-3">Moda Visitasi</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Offline -->
                            <label @click="visitMode = 'offline'"
                                class="relative flex flex-col gap-2 p-3.5 rounded-xl border-2 cursor-pointer transition-all duration-150"
                                :class="visitMode === 'offline'
                                    ? 'border-biru2 bg-birumuda shadow-sm'
                                    : 'border-abubiru bg-white hover:border-biru4/50 hover:bg-slate-50'">
                                <input type="radio" name="_visit_mode_ui" value="offline" class="sr-only" :checked="visitMode === 'offline'">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                        :class="visitMode === 'offline' ? 'bg-biru2 text-white' : 'bg-slate-100 text-biru4'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-extrabold transition"
                                        :class="visitMode === 'offline' ? 'text-biru1' : 'text-biru3'">Offline</span>
                                </div>
                                <p class="text-xs leading-snug transition"
                                    :class="visitMode === 'offline' ? 'text-biru4' : 'text-slate-400'">
                                    Kunjungan langsung ke Kabupaten/Kota oleh tim Provinsi
                                </p>
                                <!-- Check indicator -->
                                <div x-show="visitMode === 'offline'" class="absolute top-2.5 right-2.5 w-4.5 h-4.5 bg-biru2 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </label>

                            <!-- Online Virtual Meeting -->
                            <label @click="visitMode = 'online_meeting'"
                                class="relative flex flex-col gap-2 p-3.5 rounded-xl border-2 cursor-pointer transition-all duration-150"
                                :class="visitMode === 'online_meeting'
                                    ? 'border-biru2 bg-birumuda shadow-sm'
                                    : 'border-abubiru bg-white hover:border-biru4/50 hover:bg-slate-50'">
                                <input type="radio" name="_visit_mode_ui" value="online_meeting" class="sr-only" :checked="visitMode === 'online_meeting'">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                        :class="visitMode === 'online_meeting' ? 'bg-biru2 text-white' : 'bg-slate-100 text-biru4'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.81v6.38a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-extrabold transition"
                                        :class="visitMode === 'online_meeting' ? 'text-biru1' : 'text-biru3'">Virtual Meeting</span>
                                </div>
                                <p class="text-xs leading-snug transition"
                                    :class="visitMode === 'online_meeting' ? 'text-biru4' : 'text-slate-400'">
                                    Meeting online langsung antara Provinsi dan Kab/Kota
                                </p>
                                <div x-show="visitMode === 'online_meeting'" class="absolute top-2.5 right-2.5 w-4.5 h-4.5 bg-biru2 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </label>

                            <!-- Online Upload Bukti -->
                            <label @click="visitMode = 'online_upload'"
                                class="relative flex flex-col gap-2 p-3.5 rounded-xl border-2 cursor-pointer transition-all duration-150"
                                :class="visitMode === 'online_upload'
                                    ? 'border-biru2 bg-birumuda shadow-sm'
                                    : 'border-abubiru bg-white hover:border-biru4/50 hover:bg-slate-50'">
                                <input type="radio" name="_visit_mode_ui" value="online_upload" class="sr-only" :checked="visitMode === 'online_upload'">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                        :class="visitMode === 'online_upload' ? 'bg-biru2 text-white' : 'bg-slate-100 text-biru4'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-extrabold transition"
                                        :class="visitMode === 'online_upload' ? 'text-biru1' : 'text-biru3'">Upload Bukti</span>
                                </div>
                                <p class="text-xs leading-snug transition"
                                    :class="visitMode === 'online_upload' ? 'text-biru4' : 'text-slate-400'">
                                    Kab/Kota upload video bukti dukung via Google Drive
                                </p>
                                <div x-show="visitMode === 'online_upload'" class="absolute top-2.5 right-2.5 w-4.5 h-4.5 bg-biru2 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </label>
                        </div>

                        <!-- Info banner untuk mode Upload Bukti -->
                        <div x-show="visitMode === 'online_upload'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="mt-3 rounded-xl bg-kuning2/20 border border-kuning2/40 px-4 py-3 flex items-start gap-3">
                            <svg class="w-4.5 h-4.5 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-xs text-yellow-800 font-semibold leading-snug">
                                Kab/Kota dapat mengupload bukti dukung mulai dari <strong>awal periode visitasi</strong> hingga
                                <strong>H-3 sebelum periode visitasi berakhir</strong>
                                (s.d. <span x-text="(() => { const d = new Date('{{ $period->visit_end }}'); d.setDate(d.getDate() - 3); return d.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}); })()"></span>).
                                Evaluator memiliki 3 hari terakhir untuk melakukan verifikasi dan penilaian.
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION: Jadwal (tersembunyi untuk mode upload) ===== -->
                    <div x-show="visitMode !== 'online_upload'"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100">
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Jadwal Visitasi</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                            <div>
                                <input type="date" name="visit_at" x-model="visitAt"
                                    min="{{ $period->visit_start }}" max="{{ $period->visit_end }}"
                                    :required="visitMode !== 'online_upload'"
                                    class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-md font-medium text-abu shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION: Link Meeting (hanya untuk mode virtual meeting) ===== -->
                    <div x-show="visitMode === 'online_meeting'"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100">
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Link Virtual Meeting</span>
                        <div class="mt-2">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </div>
                                <input type="url" x-model="linkMeeting"
                                    :required="visitMode === 'online_meeting'"
                                    placeholder="https://meet.google.com/... atau https://zoom.us/..."
                                    class="w-full pl-9 pr-3 py-2.5 border border-abubiru rounded-xl bg-white text-sm font-medium text-slate-700 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                            </div>
                            <p class="mt-1.5 text-xs text-slate-400 font-semibold">Link meeting disiapkan oleh Provinsi (Google Meet, Zoom, Teams, dll.)</p>
                        </div>
                    </div>

                    <!-- ===== SECTION: Pilih Kabupaten / Kota ===== -->
                    <div x-show="!isEditScheduled">
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Pilih Kabupaten / Kota</span>
                        <div class="space-y-2 mt-2">                          
                            <!-- Selected BPS Chips Container -->
                            <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5">
                                <template x-if="selectedVisitIds.length === 0">
                                    <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada Kabupaten / Kota terpilih</span>
                                </template>
                                <template x-for="id in selectedVisitIds" :key="id">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                        <span x-text="visitationsMap[id] || 'Kab/Kota'"></span>
                                        <button type="button" @click="selectedVisitIds = selectedVisitIds.filter(vId => String(vId) !== String(id))" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            
                            <!-- Searchable Dropdown for Kab/Kota -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <div class="relative">
                                    <input type="text"
                                           x-model="searchKabkot"
                                           @focus="open = true"
                                           @mousedown="if (document.activeElement === $el) { open = !open; $event.preventDefault(); }"
                                           @keydown.escape="open = false"
                                           placeholder="Cari Kabupaten / Kota... (Dapat pilih lebih dari satu)"
                                           class="w-full rounded-xl px-3 py-2.5 bg-white text-sm font-semibold border border-abubiru text-slate-700 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition outline-none">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 cursor-pointer" @click.stop="open = !open">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-60 w-full mt-1.5 max-h-60 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1"
                                     style="display: none;">
                                     <template x-for="item in filteredVisitations" :key="item.id">
                                        <button type="button"
                                                @click="if (!selectedVisitIds.map(String).includes(String(item.id))) { selectedVisitIds.push(item.id); } searchKabkot = ''; open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-biru1 font-semibold transition">
                                            <span x-text="item.name"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredVisitations.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                        Tidak ada Kabupaten / Kota yang cocok
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION: Petugas ===== -->
                    <div>
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1">Pilih Petugas</span>
                        <div class="space-y-2 mt-2">                            
                            <!-- Selected Tags Container (Chips) -->
                            <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5">
                                <template x-if="visitorIds.length === 0">
                                    <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada petugas dipilih</span>
                                </template>
                                <template x-for="id in visitorIds" :key="id">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                        <span x-text="getVisitorName(id)"></span>
                                        <button type="button" @click="removeVisitor(id)" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            
                            <!-- Searchable Dropdown for Petugas -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <div class="relative">
                                    <input type="text"
                                           x-model="searchEvaluator"
                                           @focus="open = true"
                                           @mousedown="if (document.activeElement === $el) { open = !open; $event.preventDefault(); }"
                                           @keydown.escape="open = false"
                                           placeholder="Cari nama petugas... (Dapat pilih lebih dari satu)"
                                           class="w-full rounded-xl px-3 py-2.5 bg-white text-sm font-semibold border border-abubiru text-slate-700 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition outline-none">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 cursor-pointer" @click.stop="open = !open">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-60 w-full mt-1.5 max-h-60 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-lg py-1"
                                     style="display: none;">
                                     <template x-for="item in filteredEvaluators" :key="item.id">
                                        <button type="button"
                                                @click="addVisitor(item.id); searchEvaluator = ''; open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-biru1 font-semibold transition">
                                            <span x-text="item.name"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredEvaluators.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                        Tidak ada petugas yang cocok
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </x-modals.jadwal>

        {{-- Modal Detail Jadwal (Google Calendar Integration) --}}
        <div x-show="showDetailModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"

            x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none" x-cloak>
            <!-- Overlay -->
            <div class="absolute inset-0 bg-biru1/40 backdrop-blur-sm" @click="showDetailModal = false"></div>
            <!-- Modal Content -->
            <div x-show="showDetailModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md rounded-2xl bg-white border border-abubiru shadow-2xl overflow-hidden">
                <div class="px-6 py-4.5 border-b border-abubiru/30 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-birumuda flex items-center justify-center text-biru2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <h3 class="text-base font-extrabold text-biru1">Detail Jadwal Kegiatan</h3>
                    </div>
                    <button @click="showDetailModal = false"
                        class="w-8 h-8 rounded-lg hover:bg-abubiru/20 flex items-center justify-center text-biru4 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-6 space-y-4">
                    <div class="space-y-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kabupaten / Kota</div>
                        <div class="text-base font-extrabold text-biru1 animate-pulse" x-show="!detailEvent.title">&nbsp;
                        </div>
                        <div class="text-base font-extrabold text-biru1" x-show="detailEvent.title"
                            x-text="detailEvent.title"></div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Waktu Pelaksanaan</div>
                        <div class="text-sm font-semibold text-biru2" x-text="detailEvent.waktu"></div>
                    </div>

                    <div class="space-y-1" x-show="detailEvent.petugas">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Petugas Verifikator
                        </div>
                        <div class="text-sm font-semibold text-biru2" x-text="detailEvent.petugas"></div>
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</div>
                        <div class="pt-0.5">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide"
                                :class="{
                                    'bg-hijau2/10 text-hijau': detailEvent.status === 'selesai',
                                    'bg-biru4/10 text-biru4': detailEvent.status === 'aktif',
                                    'bg-abubiru text-biru4': detailEvent.status === 'belum'
                                    }"
                                x-text="detailEvent.status">
                            </span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4.5 border-t border-abubiru/30 flex items-center justify-end gap-3 bg-slate-50">
                    <button @click="showDetailModal = false"
                        class="px-4.5 py-2.5 rounded-xl border border-abubiru text-xs font-bold text-biru3 hover:bg-abubiru/20 transition">Tutup</button>
                    <a :href="detailEvent.gcalUrl" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4.5 py-2.5 rounded-xl bg-[#4285F4] text-xs font-bold text-white shadow-md hover:bg-[#357ae8] transition">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                        </svg>
                        <span>+ Google Calendar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

@push('styles')
    <style>
        /* Muted days outside active range */
        .fc-day-muted {
            background-color: #f1f5f9 !important;
            opacity: 0.45;
        }

        .fc-day-muted .fc-daygrid-day-number {
            color: #94a3b8 !important;
            font-weight: normal !important;
        }

        /* Highlighted active days inside range */
        .fc-day-highlighted {
            background-color: #ffffff !important;
            position: relative;
        }

        .fc-day-highlighted:hover {
            background-color: #f8fafc !important;
            cursor: pointer;
        }

        .fc-day-highlighted .fc-daygrid-day-number {
            color: #1e293b !important;
            font-weight: 800 !important;
        }

        .fc-day-highlighted::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 1.5px dashed #4C84B0;
            pointer-events: none;
            border-radius: 4px;
            margin: 2px;
        }

        /* Mobile responsiveness for FullCalendar */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column !important;
                gap: 0.6rem !important;
                align-items: center !important;
                justify-content: center !important;
                margin-bottom: 0.85rem !important;
            }
            .fc .fc-toolbar-chunk {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                width: 100% !important;
                flex-wrap: wrap !important;
                gap: 0.35rem !important;
            }
            .fc-toolbar-title {
                font-size: 1.1rem !important;
                font-weight: 800 !important;
                text-align: center !important;
                color: #1e293b !important;
                margin: 0 !important;
            }
            .fc .fc-button {
                padding: 0.35rem 0.65rem !important;
                font-size: 0.75rem !important;
                border-radius: 0.5rem !important;
                font-weight: 700 !important;
            }
            .fc .fc-button-group {
                gap: 2px !important;
            }
            .fc .fc-daygrid-day-number {
                font-size: 0.75rem !important;
                padding: 2px 4px !important;
            }
            .fc .fc-col-header-cell-cushion {
                font-size: 0.75rem !important;
                padding: 4px 2px !important;
            }
            .fc-event {
                font-size: 0.68rem !important;
                padding: 1px 2px !important;
            }
            .fc .fc-daygrid-more-link {
                font-size: 0.65rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>
    @include('components.charts.monitoring-calendar')
    <script>
        function initCalendar() {
            const calendarEl = document.getElementById('visitasi-calendar');
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
                        @foreach ($visitations as $vis)
                            {
                                title: '{{ ($vis->submission->user->masterSatker ? ('(' . $vis->submission->user->masterSatker->kode_satker . ') ' . $vis->submission->user->masterSatker->nama_satker) : 'BPS Kab/Kota') }}',
                                start: '{{ $vis->visit_at }}',
                                allDay: true,
                                color: '{{ $vis->visit_score !== null ? '#1e6091' : '#e67e22' }}',
                                extendedProps: {
                                    petugas: '{{ $vis->evaluators->pluck('user.name')->implode(', ') }}',
                                    id: '{{ $vis->submission->user->masterSatker->kode_satker }}',
                                    status: '{{ $vis->visit_score !== null ? 'selesai' : 'belum' }}'
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
                                petugas: props.petugas || '',
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

                        const start = '{{ $period->visit_start }}';
                        const end = '{{ $period->visit_end }}';

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
                window.visitasiCalendar = calendar;
            }
        }
    </script>
@endpush

</x-layouts.app>
