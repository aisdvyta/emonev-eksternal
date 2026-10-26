<x-layouts.app title="Manajemen Master Pertanyaan - HaloKIP"
    sidebar="{{ Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super_evaluator' : 'evaluator' }}">
    @php
        $rolePrefix = Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super-evaluator' : 'evaluator';
    @endphp
    <div x-data="{
        activeTab: {{ $activeTab ?? ($indicators->first()?->id_indicators ?? 1) }},
        indicators: {{ json_encode($indicatorsData) }},
        showFormModal: {{ (!empty($errors) && $errors->any()) ? 'true' : 'false' }},
        formMode: {{ old('_method') === 'PUT' ? "'edit'" : "'add'" }},
        formData: {
            id: {{ old('id') ?? 'null' }},
            indicator: {{ json_encode(old('indicator') ?? ($activeInd?->nama_indikator ?? '')) }},
            sub: {{ json_encode(old('sub_indicator') ?? '') }},
            weight: {{ json_encode(old('weight') ?? 2.5) }},
            question: {{ json_encode(old('question') ?? '') }},
            deskripsi: {{ json_encode(old('deskripsi') ?? '') }},
            link_drive: {{ json_encode(old('link_drive') ?? '') }},
            link_ppid: {{ json_encode(old('link_ppid') ?? '') }},
            dokumen: {{ json_encode(old('dokumen') ?? '') }}
        },
        showDeleteModal: false,
        deleteTarget: null,
        deleteLabel: '',
        showImportModal: false,
        subIndicators: {{ json_encode($subIndicatorsMapping) }},
        questions: {{ json_encode($questionsData) }},
        getActiveIndicatorName() {
            const ind = this.indicators.find(i => i.id === this.activeTab);
            return ind ? ind.name : '';
        },
        openAdd() {
            this.formMode = 'add';
            this.formData = { indicator: this.getActiveIndicatorName(), sub: '', weight: 2.5, question: '', deskripsi: '', link_drive: '', link_ppid: '', dokumen: '' };
            this.showFormModal = true;
        },
        openEdit(id) {
            this.formMode = 'edit';
            const q = this.questions[id];
            this.formData = { ...q, sub: '' };
            this.showFormModal = true;
            this.$nextTick(() => {
                this.formData.sub = q.sub;
            });
        },
        openDelete(id) {
            this.deleteTarget = id;
            this.deleteLabel = this.questions[id].question;
            this.showDeleteModal = true;
        },
        getSubOptions() {
            return this.formData.indicator ? (this.subIndicators[this.formData.indicator] || []) : [];
        },
        get driveError() {
            if (!this.formData.link_drive || !this.formData.link_drive.trim()) return '';
            const val = this.formData.link_drive.trim();
            return /^https?:\/\//i.test(val) ? '' : 'Isikan Link dengan benar';
        },
        get ppidError() {
            if (!this.formData.link_ppid || !this.formData.link_ppid.trim()) return '';
            const val = this.formData.link_ppid.trim();
            return /^https?:\/\//i.test(val) ? '' : 'Isikan Link dengan benar';
        },
        get hasLinkError() {
            return !!(this.driveError || this.ppidError);
        }
    }">
        <x-ui.page-header title="Manajemen Master Pertanyaan"
            description="Kelola daftar pertanyaan kuesioner SAQ per indikator." :breadcrumb="['Master Kuesioner' => '#', 'Pertanyaan' => '#']">
            <x-slot name="actions">
                @if (!$hasActivePeriod)
                    <button @click="showImportModal = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-hijau px-5 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Import Excel
                    </button>
                    <button @click="openAdd()"
                        class="inline-flex items-center gap-2 rounded-lg bg-biru2 px-5 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Pertanyaan
                    </button>
                @else
                    <button disabled title="Master data dikunci karena periode KIP sedang aktif"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-5 py-3 text-sm font-bold text-slate-500 cursor-not-allowed shadow-none border border-slate-300">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Master Dikunci
                    </button>
                @endif
            </x-slot>
        </x-ui.page-header>

        <!-- Active Period Lock Warning Banner -->
        @if ($hasActivePeriod)
            <div class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-4 flex items-center gap-3.5 mb-6 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-sm font-bold text-amber-900">Master Data Dikunci (Read-Only)</span>
                    <p class="text-xs sm:text-sm text-amber-800 font-medium mt-0.5">
                        Terdapat periode KIP yang sedang aktif ({{ $activePeriod?->nama ?? 'Periode Aktif' }}). Pengubahan, penambahan, dan penghapusan master data ditangguhkan selama periode berlangsung.
                    </p>
                </div>
            </div>
        @endif

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="rounded-xl bg-hijau/10 border border-hijau/30 px-5 py-4 flex items-center gap-3 mb-6 shadow-sm">
                <svg class="w-5 h-5 text-hijau shrink-0" fill="none" stroke="currentColor" stroke-width="2.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-sm font-semibold text-hijau">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div
                class="rounded-xl bg-merah2/10 border border-merah2/30 px-5 py-4 flex items-center gap-3 mb-6 shadow-sm">
                <svg class="w-5 h-5 text-merah2 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <p class="text-sm font-semibold text-merah2">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Info Banner -->
        <div class="rounded-xl bg-birumuda border-abubiru px-5 py-4 flex items-center gap-4 mb-6 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-sm font-bold text-biru6">Informasi</span>
                <p class="text-xs sm:text-sm text-biru6 font-medium mt-0.5">Hanya dapat menambahkan pertanyaan dari indikator KIP yang
                    telah terdaftar.</p>
            </div>
        </div>

        <!-- Mobile Custom Dropdown Selection for Indicators (Visible on Mobile HP Only) -->
        <div class="block sm:hidden mb-4 relative" x-data="{ openIndSelect: false }">
            <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1.5">
                Pilih Indikator:
            </label>
            <!-- Trigger Button -->
            <button type="button" @click="openIndSelect = !openIndSelect" @click.away="openIndSelect = false"
                class="w-full bg-biru4 text-white font-bold text-xs sm:text-sm rounded-xl border border-biru4 py-3 pl-3.5 pr-9 shadow-sm focus:outline-none flex items-center justify-between transition cursor-pointer relative">
                <span class="truncate pr-2 font-bold">
                    <template x-for="ind in indicators" :key="ind.id">
                        <span x-show="activeTab === ind.id" x-text="ind.name + ' (' + ind.count + ' pertanyaan)'"></span>
                    </template>
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
                @foreach ($indicators as $ind)
                    @php
                        $encryptedTabId = \App\Helpers\SecureId::encrypt($ind->id_indicators);
                        $count = $ind->subIndikators->sum(fn($s) => $s->pertanyaans->count());
                    @endphp
                    <button type="button"
                        @click="activeTab = {{ $ind->id_indicators }}; openIndSelect = false; window.history.replaceState({}, '', '?tab={{ $encryptedTabId }}')"
                        :class="activeTab === {{ $ind->id_indicators }}
                            ? 'bg-biru5/40 text-biru1 font-extrabold'
                            : 'text-biru2 hover:bg-putihbg hover:text-biru1'"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-semibold transition text-left cursor-pointer">
                        <span class="truncate pr-2">{{ $ind->nama_indikator }}</span>
                        <span :class="activeTab === {{ $ind->id_indicators }} ? 'bg-biru2 text-white' : 'bg-biru5 text-biru2'"
                              class="px-2 py-0.5 rounded-full text-xs font-bold shrink-0">
                            {{ $count }} soal
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Indicator Overview Tabs (Visible on Desktop/Tablet) — Interactive Horizontal Scroll with Arrow Controls -->
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
           class="hidden sm:block relative mb-0 z-10 group">

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
                class="flex items-stretch overflow-x-auto overflow-y-hidden w-full pb-0.5 -mb-px custom-scrollbar scrollbar-thin scrollbar-thumb-biru4/30 scrollbar-track-transparent scroll-smooth">
                @foreach ($indicators as $ind)
                    @php
                        $encryptedTabId = \App\Helpers\SecureId::encrypt($ind->id_indicators);
                        $count = $ind->subIndikators->sum(fn($s) => $s->pertanyaans->count());
                    @endphp
                    <button type="button"
                        @click="activeTab = {{ $ind->id_indicators }}; window.history.replaceState({}, '', '?tab={{ $encryptedTabId }}')"
                        :class="activeTab === {{ $ind->id_indicators }}
                            ? 'bg-biru2 border-biru2 z-20 text-white shadow-sm -mb-px pb-3.5'
                            : 'bg-white border-abubiru hover:bg-putihbg text-biru2'"
                        :style="activeTab === {{ $ind->id_indicators }} ? 'background: radial-gradient(ellipse 140% 140% at 100% 110%, #2563A0 0%, transparent 55%), #184D77;' : ''"
                        class="shrink-0 min-w-35 md:min-w-[calc(100%/6)] relative px-3 py-3 text-center cursor-pointer transition-all select-none border-biru5 border-t border-l border-r overflow-hidden rounded-tl-xl rounded-tr-xl flex flex-col justify-center">
                        <div :class="activeTab === {{ $ind->id_indicators }} ? 'text-white' : 'text-biru2'"
                             class="text-sm md:text-sm font-bold leading-tight line-clamp-2" title="{{ $ind->nama_indikator }}">
                            {{ $ind->nama_indikator }}
                        </div>
                        <div :class="activeTab === {{ $ind->id_indicators }} ? 'text-white/80' : 'text-biru4'"
                             class="text-xs md:text-sm mt-1 font-medium">
                            {{ $count }} pertanyaan
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- Right Arrow Button -->
            <button type="button" @click="scrollRight()" x-show="canScrollRight" x-cloak
                title="Geser ke kanan"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7-7" />
                </svg>
            </button>
        </div>

        <!-- Questions Panels (Per Indicator) -->
        @foreach ($indicators as $ind)
            @php
                $totalQuestionsCount = $ind->subIndikators->sum(fn($s) => $s->pertanyaans->count());
            @endphp
            <div x-show="activeTab === {{ $ind->id_indicators }}" x-cloak
                 class="rounded-2xl sm:rounded-tl-none bg-white border border-abubiru shadow-sm overflow-hidden mb-6 -mt-px">
                <!-- Panel Header - Dark Navy -->
                <div class="px-5 sm:px-8 py-5 sm:py-6 border-b border-abubiru bg-biru2 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base uppercase font-bold text-white">{{ $ind->nama_indikator }}</h2>
                        <p class="text-xs text-white font-medium mt-0.5">{{ $ind->deskripsi_indikator }}</p>
                    </div>
                    <span class="shrink-0 bg-white text-biru2 text-xs font-bold px-4 py-1.5 rounded-full">
                        {{ $totalQuestionsCount }} pertanyaan
                    </span>
                </div>

                <!-- Table — Desktop only -->
                <div class="hidden md:block bg-white overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-biru5 border-b border-abubiru">
                                <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-14">NO</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2 w-[28%]">PERTANYAAN</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2 w-[24%]">DESKRIPSI</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2 w-[24%]">DOKUMEN YANG DIPERLUKAN</th>
                                <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-20">BOBOT</th>
                                <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-28">AKSI</th>
                            </tr>
                        </thead>

                        @forelse ($ind->subIndikators as $sub)
                            @php
                                $subQuestions = $sub->pertanyaans;
                            @endphp
                            <tbody x-data="{ openSub: true }">
                                <tr class="bg-abubiru border-t border-abubiru">
                                    <td colspan="6" class="px-5 py-0">
                                        <button type="button" @click="openSub = !openSub"
                                            class="w-full flex items-center justify-between py-2.5 text-left cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-bold text-biru1">{{ $sub->nama_sub_indikator }}</span>
                                                <span class="text-xs text-biru4 font-medium">Sub Indikator • {{ $subQuestions->count() }} Pertanyaan</span>
                                            </div>
                                            <svg class="w-4 h-4 text-biru3 transition-transform duration-200 mr-1"
                                                :class="openSub ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>

                                @forelse ($subQuestions as $index => $q)
                                    <tr x-show="openSub" x-cloak class="border-t border-abubiru/40 hover:bg-putihbg/70 transition">
                                        <td class="px-5 py-4 text-center">
                                            <span class="inline-flex w-7 h-7 rounded-full bg-biru5/40 text-biru2 font-bold items-center justify-center text-xs">
                                                {{ $index + 1 }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="text-biru1 font-semibold text-sm leading-snug">{{ $q->ques_text }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-xs text-biru3 leading-relaxed">
                                            {{ $q->ques_description }}
                                        </td>
                                        <td class="px-5 py-4 text-xs text-biru3 leading-relaxed">
                                            @if ($q->doc_requirement)
                                                <p class="whitespace-pre-line leading-relaxed mb-2 font-medium text-biru1">{{ $q->doc_requirement }}</p>
                                            @endif
                                            @if ($q->link_drive || $q->link_ppid)
                                                <div class="space-y-1">
                                                    @if ($q->link_drive)
                                                        <div>
                                                            <span class="font-bold text-hijau block">Link Drive:</span>
                                                            <a href="{{ $q->link_drive }}" target="_blank" class="text-biru2 hover:underline break-all">{{ $q->link_drive }}</a>
                                                        </div>
                                                    @endif
                                                    @if ($q->link_ppid)
                                                        <div>
                                                            <span class="font-bold text-hijau block">Link PPID:</span>
                                                            <a href="{{ $q->link_ppid }}" target="_blank" class="text-biru2 hover:underline break-all">{{ $q->link_ppid }}</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif (!$q->doc_requirement)
                                                <span class="text-slate-400 italic">-</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-center font-extrabold text-biru1 text-sm">
                                            {{ $q->bobot_pertanyaan }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            @if (!$hasActivePeriod)
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button" title="Edit" @click="openEdit({{ $q->id_master_pertanyaan }})"
                                                        class="w-9 h-9 rounded-lg bg-oren2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" title="Hapus" @click="openDelete({{ $q->id_master_pertanyaan }})"
                                                        class="w-9 h-9 rounded-lg bg-merah2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-xs font-semibold text-slate-400 italic">Terkunci</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr x-show="openSub" x-cloak>
                                        <td colspan="6" class="px-5 py-4 text-center text-xs text-slate-400 italic">
                                            Belum ada pertanyaan pada sub-indikator ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400 italic">
                                        Belum ada sub-indikator pada indikator ini.
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                <!-- Card List — Mobile only -->
                <div class="block md:hidden">
                    @forelse ($ind->subIndikators as $sub)
                        @php
                            $subQuestions = $sub->pertanyaans;
                        @endphp
                        <div x-data="{ openSub: true }">
                            <button type="button" @click="openSub = !openSub"
                                class="w-full flex items-center justify-between px-4 py-3 bg-abubiru border-t border-abubiru/60 text-left cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-biru1 block">{{ $sub->nama_sub_indikator }}</span>
                                    <span class="text-xs text-biru4 font-medium">Sub Indikator • {{ $subQuestions->count() }} Pertanyaan</span>
                                </div>
                                <svg class="w-4 h-4 text-biru3 transition-transform duration-200 shrink-0 ml-2"
                                    :class="openSub ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            @forelse ($subQuestions as $index => $q)
                                <div x-show="openSub" x-cloak class="mx-3 my-2 rounded-xl border border-abubiru bg-white shadow-sm overflow-hidden">
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-biru5/50 border-b border-abubiru/40">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex w-6 h-6 rounded-full bg-biru2 text-white font-bold items-center justify-center text-xs">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="text-xs font-semibold text-biru3">{{ $sub->nama_sub_indikator }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 bg-biru2/10 text-biru2 text-xs font-bold px-2 py-0.5 rounded-full border border-biru2/20">
                                                Bobot {{ $q->bobot_pertanyaan }}
                                            </span>
                                            @if (!$hasActivePeriod)
                                                <button type="button" title="Edit" @click="openEdit({{ $q->id_master_pertanyaan }})"
                                                    class="w-7 h-7 rounded-lg bg-oren2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button type="button" title="Hapus" @click="openDelete({{ $q->id_master_pertanyaan }})"
                                                    class="w-7 h-7 rounded-lg bg-merah2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="text-xs font-semibold text-slate-400 italic">Terkunci</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="px-3 py-2.5 space-y-2">
                                        <p class="text-biru1 font-semibold text-xs leading-snug">{{ $q->ques_text }}</p>
                                        @if ($q->ques_description)
                                            <div class="flex gap-1.5">
                                                <span class="shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-biru4 inline-block"></span>
                                                <p class="text-xs text-biru3 leading-relaxed">{{ $q->ques_description }}</p>
                                            </div>
                                        @endif
                                        @if ($q->doc_requirement || $q->link_drive || $q->link_ppid)
                                            <div class="space-y-1.5 bg-hijau/5 border border-hijau/20 rounded-lg p-2.5">
                                                <div class="flex items-center gap-1.5 text-xs font-bold text-hijau mb-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <span>Dokumen yang Diperlukan</span>
                                                </div>
                                                @if ($q->doc_requirement)
                                                    <p class="text-xs text-biru1 font-medium whitespace-pre-line leading-relaxed">{{ $q->doc_requirement }}</p>
                                                @endif
                                                @if ($q->link_drive)
                                                    <div class="text-xs mt-1">
                                                        <span class="font-bold text-hijau block">Link Drive:</span>
                                                        <a href="{{ $q->link_drive }}" target="_blank" class="text-biru2 hover:underline break-all">{{ $q->link_drive }}</a>
                                                    </div>
                                                @endif
                                                @if ($q->link_ppid)
                                                    <div class="text-xs mt-1">
                                                        <span class="font-bold text-hijau block">Link PPID:</span>
                                                        <a href="{{ $q->link_ppid }}" target="_blank" class="text-biru2 hover:underline break-all">{{ $q->link_ppid }}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div x-show="openSub" x-cloak class="px-4 py-3 text-xs text-slate-400 italic">
                                    Belum ada pertanyaan pada sub-indikator ini.
                                </div>
                            @endforelse
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-xs text-slate-400 italic">
                            Belum ada sub-indikator pada indikator ini.
                        </div>
                    @endforelse
                </div>

                <!-- Footer Count -->
                <div class="px-4 sm:px-8 py-3.5 sm:py-4 bg-white border-t border-abubiru flex items-center justify-between text-xs text-slate-500 font-medium">
                    <span>Menampilkan total <strong>{{ $totalQuestionsCount }}</strong> pertanyaan pada indikator ini</span>
                </div>
            </div>
        @endforeach

        {{-- Modal Form Tambah/Edit Pertanyaan --}}
        <x-modals.form add-title="Form Tambah Pertanyaan" edit-title="Form Edit Pertanyaan"
            add-action="{{ url($rolePrefix . '/kuesioner') }}" edit-action="{{ url($rolePrefix . '/kuesioner') }}"
            size="lg" @submit="if (hasLinkError) { $event.preventDefault(); return false; }">
            <div>
                <input type="hidden" name="id" x-model="formData.id">
                {{-- Indikator --}}
                <div class="py-4 border-b border-abubiru/60">
                    <div x-data="{
                        isOpen: false,
                        search: '',
                        selectedVal: formData.indicator || '',
                        options: indicators,
                        get selectedName() {
                            const found = this.options.find(o => o.name == this.selectedVal);
                            return found ? found.name : 'Pilih Indikator';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                        }
                    }" x-effect="selectedVal = formData.indicator || ''" class="relative">
                        <label class="block text-sm font-bold text-biru1 mb-2">INDIKATOR</label>
                        <input type="hidden" name="indicator" :value="selectedVal" required>
                        <button type="button" @click="isOpen = !isOpen"
                            :class="selectedVal ? 'text-biru1' : 'text-slate-400'"
                            class="w-full flex items-center justify-between border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm font-semibold shadow-sm focus:ring-1 focus:ring-biru4/30 focus:border-biru4 transition">
                            <span x-text="selectedName"></span>
                            <svg class="w-4 h-4 text-biru3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                            class="absolute left-0 right-0 mt-1 z-50 bg-white border border-abubiru rounded-xl shadow-lg p-3 text-biru1 font-medium text-left">
                            <input type="text" placeholder="Cari..." x-model="search"
                                class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                            <div class="max-h-40 overflow-y-auto space-y-1 custom-scrollbar">
                                <template x-for="opt in filteredOptions" :key="opt.name">
                                    <button type="button"
                                        @click="selectedVal = opt.name; formData.indicator = opt.name; isOpen = false; search = '';"
                                        class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition flex items-center justify-between"
                                        :class="selectedVal == opt.name ? 'bg-biru5/40 font-bold text-biru1' : ''">
                                        <span x-text="opt.name"></span>
                                        <svg x-show="selectedVal == opt.name" class="w-3.5 h-3.5 text-biru2"
                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="filteredOptions.length === 0"
                                    class="text-xs text-center text-biru3 py-2 italic">
                                    Tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sub Indikator --}}
                <div class="py-4 border-b border-abubiru/60">
                    <div x-data="{
                        isOpen: false,
                        search: '',
                        selectedVal: formData.sub || '',
                        get options() {
                            return getSubOptions().map(name => ({ name }));
                        },
                        get selectedName() {
                            const found = this.options.find(o => o.name == this.selectedVal);
                            return found ? found.name : 'Pilih Sub Indikator';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                        }
                    }" x-effect="selectedVal = formData.sub || ''" class="relative">
                        <label class="block text-sm font-bold text-biru1 mb-2">SUB-INDIKATOR</label>
                        <input type="hidden" name="sub_indicator" :value="selectedVal" required>
                        <button type="button" @click="isOpen = !isOpen"
                            :class="selectedVal ? 'text-biru1' : 'text-slate-400'"
                            class="w-full flex items-center justify-between border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm font-semibold shadow-sm focus:ring-1 focus:ring-biru4/30 focus:border-biru4 transition text-left">
                            <span x-text="selectedName" class="truncate"></span>
                            <svg class="w-4 h-4 text-biru3 shrink-0 ml-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="isOpen" @click.away="isOpen = false" x-cloak
                            class="absolute left-0 right-0 mt-1 z-50 bg-white border border-abubiru rounded-xl shadow-lg p-3 text-biru1 font-medium text-left">
                            <input type="text" placeholder="Cari..." x-model="search"
                                class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                            <div class="max-h-40 overflow-y-auto space-y-1 custom-scrollbar">
                                <template x-for="opt in filteredOptions" :key="opt.name">
                                    <button type="button"
                                        @click="selectedVal = opt.name; formData.sub = opt.name; isOpen = false; search = '';"
                                        class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition flex items-center justify-between"
                                        :class="selectedVal == opt.name ? 'bg-biru5/40 font-bold text-biru1' : ''">
                                        <span x-text="opt.name"></span>
                                        <svg x-show="selectedVal == opt.name" class="w-3.5 h-3.5 text-biru2"
                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="filteredOptions.length === 0"
                                    class="text-xs text-center text-biru3 py-2 italic">
                                    Tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bobot --}}
                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">BOBOT NILAI PERTANYAAN</label>
                    <input type="number" name="weight" x-model="formData.weight" min="0" max="100"
                        step="any" required
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition" />
                </div>

                {{-- Deskripsi Pertanyaan --}}
                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">DESKRIPSI <span
                            class="text-oren2">PERTANYAAN</span></label>
                    <textarea name="deskripsi" x-model="formData.deskripsi" rows="3"
                        placeholder="Ketikkan teks deskripsi pertanyaan secara lengkap disini..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition resize-none placeholder:text-abumuda"></textarea>
                </div>

                {{-- Deskripsi Dokumen Persyaratan --}}
                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">DESKRIPSI DOKUMEN <span class="text-hijau">PERSYARATAN</span></label>
                    <textarea name="dokumen" x-model="formData.dokumen" rows="2"
                        placeholder="Ketikkan deskripsi dokumen persyaratan yang diperlukan (opsional)..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-hijau/30 focus:border-hijau outline-none transition resize-none placeholder:text-abumuda"></textarea>
                    @error('dokumen')
                        <p class="text-xs font-semibold text-merah1 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contoh Link Drive --}}
                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">CONTOH LINK <span class="text-hijau">DRIVE</span></label>
                    <textarea name="link_drive" x-model="formData.link_drive" rows="2"
                        placeholder="Ketikkan contoh / petunjuk link drive disini..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-hijau/30 focus:border-hijau outline-none transition resize-none placeholder:text-abumuda"></textarea>
                    @error('link_drive')
                        <p class="text-xs font-semibold text-merah1 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contoh Link PPID --}}
                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">CONTOH LINK <span class="text-hijau">PPID</span></label>
                    <textarea name="link_ppid" x-model="formData.link_ppid" rows="2"
                        placeholder="Ketikkan contoh link PPID/lainnya disini..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-hijau/30 focus:border-hijau outline-none transition resize-none placeholder:text-abumuda"></textarea>
                    @error('link_ppid')
                        <p class="text-xs font-semibold text-merah1 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Isi Pertanyaan --}}
                <div class="py-4">
                    <label class="block text-sm font-bold text-biru1 mb-2">ISI <span
                            class="text-biru4">PERTANYAAN</span></label>
                    <textarea name="question" x-model="formData.question" rows="3" required
                        placeholder="Ketikkan teks pertanyaan secara lengkap disini..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition resize-none placeholder:text-abumuda"></textarea>
                </div>
            </div>
        </x-modals.form>

        {{-- Modal Konfirmasi Hapus Pertanyaan --}}
        <x-modals.confirm-delete title="Hapus Pertanyaan"
            message="Apakah Anda yakin ingin menghapus pertanyaan ini? Pertanyaan yang sudah dihapus tidak dapat dikembalikan."
            action-url="{{ url($rolePrefix . '/kuesioner') }}" />

        {{-- Modal Import Pertanyaan --}}
        <x-modals.import-pertanyaan :action="route($rolePrefix . '.manage-kuesioner.import')" :indicators="$indicators" />
    </div>

</x-layouts.app>
