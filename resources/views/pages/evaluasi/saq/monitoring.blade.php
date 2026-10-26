@php
    $routePrefix = match(Auth::user()?->role?->nama_role) {
        'super_evaluator' => 'super-evaluator',
        'penilai_eksternal' => 'penilai-eksternal',
        default => 'evaluator',
    };
@endphp
<x-layouts.app title="Monitoring Pengisian Kuesioner SAQ - HaloKIP">
    <div x-data="{
        activeMainTab: new URLSearchParams(window.location.search).get('tab') === 'review' ? 'review' : 'kuesioner',
        showJadwalModal: false,
        selectedSubmissionIds: [],
        evaluatorIds: [],
        evaluatorsMap: @js($evaluators->pluck('name', 'id_user')->toArray()),
        submissionsMap: @js(collect($submissions)->mapWithKeys(fn($sub) => [$sub->id_submission => $sub->user->masterSatker ? ('(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker) : 'BPS Kab/Kota'])->toArray()),
        submissionsList: @js(collect($submissions)->map(function($sub) {
            $hasDraftScore = $sub->answers->whereNotNull('answer_score')->count() > 0;
            $isMenungguEval = ($sub->status === 3 && !$hasDraftScore);
            $isScheduled = $sub->kuesionerEvaluators->isNotEmpty() || !empty($sub->evaluated_by);
            return [
                'id' => (string) $sub->id_submission,
                'name' => $sub->user->masterSatker ? ('(' . $sub->user->masterSatker->kode_satker . ') ' . $sub->user->masterSatker->nama_satker) : 'BPS Kab/Kota',
                'is_scheduled' => $isScheduled,
                'is_menunggu_eval' => $isMenungguEval
            ];
        })->values()->toArray()),
        evaluatorsList: @js(collect($evaluators)->map(fn($ev) => ['id' => (string) $ev->id_user, 'name' => $ev->name])->values()->toArray()),
        searchKabkot: '',
        searchEvaluator: '',
        get filteredSubmissions() {
            const query = this.searchKabkot.toLowerCase();
            return this.submissionsList.filter(item => {
                const isSelected = this.selectedSubmissionIds.map(String).includes(String(item.id));
                const matchesSearch = item.name.toLowerCase().includes(query);
                return item.is_menunggu_eval && !item.is_scheduled && !isSelected && matchesSearch;
            });
        },
        get filteredEvaluators() {
            const query = this.searchEvaluator.toLowerCase();
            return this.evaluatorsList.filter(item => {
                const isSelected = this.evaluatorIds.map(String).includes(String(item.id));
                const matchesSearch = item.name.toLowerCase().includes(query);
                return !isSelected && matchesSearch;
            });
        },
        isEditScheduled: false,
        modalTitle: 'Atur Evaluator Kuesioner',
        selectedSatkerName: '',
        getEvaluatorName(id) {
            return this.evaluatorsMap[String(id)] || this.evaluatorsMap[id] || '';
        },
        init() {
            this.$watch('showJadwalModal', value => {
                if (value) {
                    this.searchKabkot = '';
                    this.searchEvaluator = '';
                }
            });
        },
        addEvaluator(id) {
            if (!id) return;
            if (!this.evaluatorIds.map(String).includes(String(id))) {
                this.evaluatorIds.push(id);
            }
        },
        removeEvaluator(id) {
            this.evaluatorIds = this.evaluatorIds.filter(vId => String(vId) !== String(id));
        },
        async submitAssign(e) {
            if (this.selectedSubmissionIds.length === 0) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Harap pilih minimal 1 Kabupaten/Kota.' }
                }));
                return;
            }
            if (this.evaluatorIds.length === 0) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Harap pilih minimal 1 orang evaluator.' }
                }));
                return;
            }

            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = document.querySelector('[x-show=showJadwalModal] button[type=submit]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showJadwalModal = false;
                    this.selectedSubmissionIds = [];
                    window.location.reload();
                } else {
                    let errorText = data.message || 'Terjadi kesalahan.';
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey].length > 0) {
                            errorText = data.errors[firstKey][0];
                        }
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'error', message: errorText }
                    }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Koneksi gagal atau terjadi kesalahan.' }
                }));
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }
    }">
        <x-ui.page-header title="Monitoring Pengisian Kuesioner SAQ"
            description="Pantau progress dan status evaluasi untuk Kabupaten/Kota disini." :breadcrumb="[
                'Monitoring Kegiatan' => '#',
                'Kuesioner SAQ' => route($routePrefix . '.monitor-kues'),
            ]">
        </x-ui.page-header>

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
        <!-- Info Box: Desktop (static, selalu terbuka) -->
        <div class="hidden sm:flex rounded-2xl bg-birumuda border border-biru5 px-6 py-4.5 mb-8 items-center gap-4 shadow-sm">
            <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-bold text-biru6 text-sm mb-0.5">Informasi</div>
                <p class="text-sm text-biru6 font-medium leading-relaxed">Daftar di bawah menunjukkan BPS Kabupaten/Kota yang telah mengumpulkan kuesioner SAQ. Sebelum memberi penilaian, atur terlebih dahulu evaluator untuk masing-masing Kab/Kota.</p>
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
                <p class="text-xs text-biru6 font-medium leading-relaxed">Daftar di bawah menunjukkan BPS Kabupaten/Kota yang telah mengumpulkan kuesioner SAQ. Sebelum memberi penilaian, atur terlebih dahulu evaluator untuk masing-masing Kab/Kota.</p>
            </div>
        </div>

        <!-- Tabs Navigation & Action Button Aligned (Persis seperti Visitasi) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-abubiru mb-6 gap-3 sm:gap-4">
            <div class="flex items-center gap-2 order-last sm:order-first">
                <button id="tab-kuesioner-btn" type="button" @click="activeMainTab = 'kuesioner'"
                    :class="activeMainTab === 'kuesioner' ? 'border-biru1 text-biru1 font-bold' : 'border-transparent text-biru3 hover:text-biru1 font-bold'"
                    class="px-5 py-3 text-sm border-b-2 transition duration-200 cursor-pointer">
                    Kuesioner SAQ
                </button>
                <button id="tab-review-btn" type="button" @click="activeMainTab = 'review'; $nextTick(() => { window.dispatchEvent(new Event('resize')); if (window.initSaqReviewSection) window.initSaqReviewSection(); else { if (window.mySaqMap) setTimeout(() => window.mySaqMap.invalidateSize(), 100); if (window._saqHistogramInstance) setTimeout(() => window._saqHistogramInstance.resize(), 100); } })"
                    :class="activeMainTab === 'review' ? 'border-biru1 text-biru1 font-bold' : 'border-transparent text-biru3 hover:text-biru1 font-bold'"
                    class="px-5 py-3 text-sm border-b-2 transition duration-200 cursor-pointer">
                    Review SAQ
                </button>
            </div>
            @if(Auth::user()?->role?->nama_role === 'super_evaluator')
            <div x-show="activeMainTab === 'kuesioner'" class="flex justify-end order-first sm:order-last sm:pb-2.5">
                <button type="button" 
                        @click="selectedSubmissionIds = []; evaluatorIds = []; isEditScheduled = false; modalTitle = 'Atur Evaluator Kuesioner'; showJadwalModal = true" 
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-oren2 hover:bg-oren2/90 text-white px-5 py-2.5 text-sm font-bold shadow-md hover:shadow-lg transition cursor-pointer w-full sm:w-auto">
                    <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Atur Evaluator Kuesioner</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Tab 1: Kuesioner SAQ Content -->
        <div x-show="activeMainTab === 'kuesioner'">
            <livewire:evaluasi.monitor-kuesioner-table />
        </div>

        <!-- Tab 2: Review SAQ Content -->
        <div x-show="activeMainTab === 'review'" x-cloak>
            <livewire:evaluasi.saq-review-section :periodeId="$period?->id_periode" />
        </div>

        <!-- Assign Modal -->
        <x-modals.jadwal title="Atur Evaluator Kuesioner">
            <form method="POST" action="{{ Auth::user()?->role?->nama_role === 'super_evaluator' ? route('super-evaluator.schedule-kues-batch') : '#' }}" id="jadwal-form"
                  @submit.prevent="submitAssign">
                @csrf
                
                <!-- Hidden inputs for submission IDs -->
                <template x-for="id in selectedSubmissionIds" :key="id">
                    <input type="hidden" name="submission_ids[]" :value="id">
                </template>
                
                <!-- Hidden inputs for evaluator IDs -->
                <template x-for="id in evaluatorIds" :key="id">
                    <input type="hidden" name="evaluator_ids[]" :value="id">
                </template>

                <div class="space-y-5">
                    <!-- Satker Badge Info (shown only in Edit mode) -->
                    <div class="flex items-center gap-2 mb-5" x-show="isEditScheduled">
                        <span class="text-md font-bold text-biru2">Nama Satker:</span>
                        <span class="inline-flex items-center rounded-lg bg-slate-100 border border-slate-200 text-md font-extrabold text-slate-700 px-3 py-1 shadow-xs" x-text="selectedSatkerName"></span>
                    </div>

                    <!-- Section: Pilih Kabupaten / Kota -->
                    <div x-show="!isEditScheduled">
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1.5">Pilih Kabupaten / Kota</span>
                        <div class="space-y-2 mt-2">
                            <label class="block text-md font-medium text-biru3">Kabupaten / Kota yang Akan Diatur</label>
                            
                            <!-- Selected BPS Chips Container -->
                            <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5">
                                <template x-if="selectedSubmissionIds.length === 0">
                                    <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada Kabupaten / Kota terpilih</span>
                                </template>
                                <template x-for="id in selectedSubmissionIds" :key="id">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                        <span x-text="submissionsMap[id] || 'Kab/Kota'"></span>
                                        <button type="button" @click="selectedSubmissionIds = selectedSubmissionIds.filter(vId => String(vId) !== String(id))" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
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
                                           placeholder="Cari Kabupaten / Kota..."
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
                                    <template x-for="item in filteredSubmissions" :key="item.id">
                                        <button type="button"
                                                @click="if (!selectedSubmissionIds.map(String).includes(String(item.id))) { selectedSubmissionIds.push(item.id); } searchKabkot = ''; open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-biru1 font-semibold transition">
                                            <span x-text="item.name"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredSubmissions.length === 0" class="px-4 py-2.5 text-xs text-slate-400 italic font-semibold">
                                        Tidak ada Kabupaten / Kota yang cocok
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Pilih Evaluator -->
                    <div>
                        <span class="block text-md font-bold text-biru2 border-b border-slate-100 pb-1.5">Pilih Evaluator</span>
                        <div class="space-y-2 mt-2">
                            <label class="block text-md font-medium text-biru3">Evaluator Penilai</label>
                            
                            <!-- Selected Tags Container (Chips) -->
                            <div class="flex flex-wrap gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl min-h-11.5">
                                <template x-if="evaluatorIds.length === 0">
                                    <span class="text-xs text-slate-400 italic font-semibold self-center px-1">Belum ada evaluator dipilih</span>
                                </template>
                                <template x-for="id in evaluatorIds" :key="id">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-biru4/10 border border-biru4/20 text-xs font-bold text-biru1 shadow-2xs">
                                        <span x-text="getEvaluatorName(id)"></span>
                                        <button type="button" @click="removeEvaluator(id)" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            
                            <!-- Searchable Dropdown for Evaluator -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <div class="relative">
                                    <input type="text"
                                           x-model="searchEvaluator"
                                           @focus="open = true"
                                           @mousedown="if (document.activeElement === $el) { open = !open; $event.preventDefault(); }"
                                           @keydown.escape="open = false"
                                           placeholder="Cari nama evaluator..."
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
                                                @click="addEvaluator(item.id); searchEvaluator = ''; open = false"
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
                </div>
            </form>
        </x-modals.jadwal>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    @endpush
</x-layouts.app>
