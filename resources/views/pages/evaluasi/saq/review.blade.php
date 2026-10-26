@php
    $role = Auth::user()?->role?->nama_role;
    $routePrefix = match($role) {
        'super_evaluator' => 'super-evaluator',
        'penilai_eksternal' => 'penilai-eksternal',
        default => 'evaluator',
    };
@endphp
<x-layouts.app title="Review Kuesioner SAQ - HaloKIP">
    <div x-data="{
        activeTab: {{ $activeTab ?? ($indicators->first()->id_indicators ?? 1) }},
        showSaveModal: false,
        showSubmitModal: false,
        showUnscoredModal: false,
        showInvalidModal: false,
        showEmptyNotesModal: false,
        unscoredList: [],
        invalidList: [],
        emptyNoteList: [],
        isDirty: false,
        autoSaveTimeout: null,
        saveStatus: '',
        init() {
            this.checkUnscored();
            this.checkInvalidScores();
            this.checkEmptyNotes();
            this.setupAutoSave();
        },
        setupAutoSave() {
            const form = document.getElementById('review-form');
            if (!form) return;
    
            const triggerSave = () => {
                this.isDirty = true;
                this.checkUnscored();
                this.checkInvalidScores();
                this.checkEmptyNotes();
    
                clearTimeout(this.autoSaveTimeout);
                this.autoSaveTimeout = setTimeout(() => {
                    this.saveDraft();
                }, 2500);
            };
    
            form.addEventListener('input', triggerSave);
            form.addEventListener('change', triggerSave);
    
            // Intercept standard browser reload/tab close
            const beforeUnloadListener = (event) => {
                if (this.isDirty) {
                    const formData = new FormData(form);
                    formData.set('action', 'draft');
                    formData.append('ajax', '1');
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        keepalive: true
                    });
    
                    event.preventDefault();
                    event.returnValue = '';
                    return '';
                }
            };
            window.addEventListener('beforeunload', beforeUnloadListener);
    
            // Intercept Livewire SPA navigation (including back/forward buttons and links)
            const livewireNavigateListener = (event) => {
                if (this.isDirty) {
                    const confirmLeave = confirm('Jawaban Anda belum disimpan sebagai draft. Apakah Anda yakin ingin meninggalkan halaman ini?');
                    if (!confirmLeave) {
                        event.preventDefault();
                    } else {
                        this.isDirty = false; // Bypass beforeunload
                    }
                }
            };
            document.addEventListener('livewire:navigate', livewireNavigateListener);
    
            // Clean up event listeners when the element is removed
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(() => {
                    window.removeEventListener('beforeunload', beforeUnloadListener);
                    document.removeEventListener('livewire:navigate', livewireNavigateListener);
                });
            }
        },
        destroy() {
            window.removeEventListener('beforeunload', this.beforeUnloadListener);
            document.removeEventListener('livewire:navigate', this.livewireNavigateListener);
        },
        async saveDraft() {
            if (!this.isDirty) return;
            const form = document.getElementById('review-form');
            if (!form) return;
    
            this.saveStatus = 'saving';
    
            const formData = new FormData(form);
            formData.set('action', 'draft');
            formData.append('ajax', '1');
    
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
    
                if (response.ok) {
                    this.isDirty = false;
                    this.saveStatus = 'success';
                    setTimeout(() => {
                        if (this.saveStatus === 'success') this.saveStatus = '';
                    }, 3000);
                } else {
                    this.saveStatus = 'error';
                }
            } catch (e) {
                console.error('Autosave failed:', e);
                this.saveStatus = 'error';
            }
        },
        checkInvalidScores() {
            this.invalidList = [];
            const inputs = document.querySelectorAll('.score-input');
            inputs.forEach(input => {
                const maxVal = parseFloat(input.getAttribute('data-max') || 10);
                const val = parseFloat(input.value.replace(',', '.'));
                if (!isNaN(val) && val > maxVal) {
                    const questionBlock = input.closest('[data-q-no]');
                    if (questionBlock) {
                        this.invalidList.push({
                            no: parseInt(questionBlock.getAttribute('data-q-no')),
                            tab: parseInt(questionBlock.getAttribute('data-q-tab'))
                        });
                    }
                }
            });
        },
        checkUnscored() {
            this.unscoredList = [];
            const inputs = document.querySelectorAll('.score-input');
            inputs.forEach(input => {
                const val = input.value.trim();
                if (val === '') {
                    const questionBlock = input.closest('[data-q-no]');
                    if (questionBlock) {
                        const label = questionBlock.querySelector('label');
                        const rawText = label ? label.textContent.trim().replace(/^\d+\.\s*/, '') : '';
                        const truncatedText = rawText.length > 50 ? rawText.substring(0, 50) + '...' : rawText;
    
                        this.unscoredList.push({
                            no: parseInt(questionBlock.getAttribute('data-q-no')),
                            tab: parseInt(questionBlock.getAttribute('data-q-tab')),
                            text: truncatedText
                        });
                    }
                }
            });
        },
        checkEmptyNotes() {
            this.emptyNoteList = [];
            const noteInputs = document.querySelectorAll('.note-input');
            noteInputs.forEach(input => {
                const val = input.value.trim();
                if (val === '') {
                    const questionBlock = input.closest('[data-q-no]');
                    if (questionBlock) {
                        const label = questionBlock.querySelector('label');
                        const rawText = label ? label.textContent.trim().replace(/^\d+\.\s*/, '') : '';
                        const truncatedText = rawText.length > 50 ? rawText.substring(0, 50) + '...' : rawText;
    
                        this.emptyNoteList.push({
                            no: parseInt(questionBlock.getAttribute('data-q-no')),
                            tab: parseInt(questionBlock.getAttribute('data-q-tab')),
                            text: truncatedText
                        });
                    }
                }
            });
        },
        handleSubmitClick() {
            this.checkInvalidScores();
            if (this.invalidList.length > 0) {
                this.showInvalidModal = true;
                return;
            }
            this.checkUnscored();
            if (this.unscoredList.length > 0) {
                this.showUnscoredModal = true;
                return;
            }
            this.checkEmptyNotes();
            if (this.emptyNoteList.length > 0) {
                this.showEmptyNotesModal = true;
            } else {
                this.showSubmitModal = true;
            }
        },
        navigateToEmptyNoteItem(item) {
            this.activeTab = item.tab;
            this.showEmptyNotesModal = false;
            this.showUnscoredModal = false;
            this.showInvalidModal = false;
            window.dispatchEvent(new Event('expand-sub-indicators'));
            this.$nextTick(() => {
                const els = document.querySelectorAll('[data-q-no]');
                for (const el of els) {
                    if (parseInt(el.getAttribute('data-q-no')) === item.no && parseInt(el.getAttribute('data-q-tab')) === item.tab) {
                        setTimeout(() => {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('ring-2', 'ring-oren2', 'ring-offset-2');
                            setTimeout(() => el.classList.remove('ring-2', 'ring-oren2', 'ring-offset-2'), 2000);
                        }, 100);
                        break;
                    }
                }
            });
        },
        navigateToFirstUnscored() {
            if (this.unscoredList.length === 0) return;
            this.navigateToItem(this.unscoredList[0]);
        },
        navigateToFirstInvalid() {
            if (this.invalidList.length === 0) return;
            const item = this.invalidList[0];
            this.activeTab = item.tab;
            this.showInvalidModal = false;
            window.dispatchEvent(new Event('expand-sub-indicators'));
            this.$nextTick(() => {
                const els = document.querySelectorAll('[data-q-no]');
                for (const el of els) {
                    if (parseInt(el.getAttribute('data-q-no')) === item.no && parseInt(el.getAttribute('data-q-tab')) === item.tab) {
                        setTimeout(() => {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('ring-2', 'ring-rose-500', 'ring-offset-2');
                            setTimeout(() => el.classList.remove('ring-2', 'ring-rose-500', 'ring-offset-2'), 2000);
                        }, 100);
                        break;
                    }
                }
            });
        },
        navigateToItem(item) {
            this.activeTab = item.tab;
            this.showUnscoredModal = false;
            this.showInvalidModal = false;
            window.dispatchEvent(new Event('expand-sub-indicators'));
            this.$nextTick(() => {
                const els = document.querySelectorAll('[data-q-no]');
                for (const el of els) {
                    if (parseInt(el.getAttribute('data-q-no')) === item.no && parseInt(el.getAttribute('data-q-tab')) === item.tab) {
                        setTimeout(() => {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('ring-2', 'ring-oren2', 'ring-offset-2');
                            setTimeout(() => el.classList.remove('ring-2', 'ring-oren2', 'ring-offset-2'), 2000);
                        }, 100);
                        break;
                    }
                }
            });
        }
    }">
        <!-- Header Area -->
        <x-ui.page-header title="Review Kuesioner SAQ Keterbukaan Informasi Publik"
            description="{{ $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota' }} - Tahun 2026"
            :breadcrumb="[
                'Monitoring Kegiatan' => route($routePrefix . '.monitor-kues'),
                'Kuesioner SAQ' => route($routePrefix . '.monitor-kues'),
                'Review' => '#',
            ]">
            <x-slot name="actions">
                <div class="hidden sm:flex items-center gap-3">
                    <a href="{{ route($routePrefix . '.monitor-kues') }}" wire:navigate.hover
                        class="inline-flex items-center gap-2 rounded-lg border border-abubiru bg-white px-5 py-3 text-sm font-semibold text-biru1 shadow-sm hover:bg-putihbg focus:outline-none transition">
                        <svg class="w-4 h-4 text-biru2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <div
                class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-semibold shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @php
            $periodeEnd = $submission->period?->eval_end
                ? \Carbon\Carbon::parse($submission->period->eval_end)->endOfDay()
                : null;
            $isPastDeadline = $periodeEnd ? now()->greaterThan($periodeEnd) : false;

            $evalStart = $submission->period?->eval_start
                ? \Carbon\Carbon::parse($submission->period->eval_start)->startOfDay()
                : null;
            $isBeforeEvalStart = $evalStart ? now()->startOfDay()->lt($evalStart) : false;

            $assignedIds = collect($submission->kuesionerEvaluators)->pluck('id_user')->toArray();
            if (empty($assignedIds) && $submission->evaluated_by) {
                $rawEval = $submission->evaluated_by;
                if (is_array($rawEval)) {
                    foreach ($rawEval as $eItem) {
                        if (is_array($eItem) && isset($eItem['id'])) {
                            $assignedIds[] = (int) $eItem['id'];
                        } elseif (is_numeric($eItem)) {
                            $assignedIds[] = (int) $eItem;
                        }
                    }
                } elseif (is_numeric($rawEval)) {
                    $assignedIds[] = (int) $rawEval;
                }
            }
            $isAssigned = in_array(Auth::id(), $assignedIds);
        @endphp

        @if (!$isAssigned)
            <div
                class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Mode Baca (Read-Only): Anda dapat melihat hasil kuesioner dan evaluasi ini. Namun, Anda tidak
                    dapat mengisi/mengubah nilai karena Anda belum ditugaskan sebagai Petugas Evaluator untuk
                    Kabupaten/Kota ini.</span>
            </div>
        @endif

        @if ($isPastDeadline)
            <div
                class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-semibold shadow-sm text-center">
                Batas waktu pengisian/pengeditan nilai evaluasi kuesioner periode ini telah berakhir pada tanggal
                <strong>{{ $periodeEnd->translatedFormat('d M Y') }}</strong>. Nilai tidak dapat diubah lagi.
            </div>
        @elseif ($isBeforeEvalStart)
            <div
                class="mb-5 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-sm font-semibold shadow-sm text-center flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Tahapan evaluasi kuesioner SAQ baru dapat dilakukan mulai tanggal <strong>{{ $evalStart->translatedFormat('d F Y') }}</strong>. Saat ini form penilaian dikunci dan belum dapat diedit/disimpan karena belum waktunya.</span>
            </div>
        @endif

        <!-- Status & Score Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mb-6 items-stretch">
            @php
                $isDraftVerif = $submission->status === 1 && !empty($submission->verified_by);
                $statusLabels = [
                    0 => 'Draft Pengisian',
                    1 => $isDraftVerif ? 'Draft Verifikasi' : 'Menunggu Verifikasi',
                    2 => 'Dikembalikan',
                    3 => 'Menunggu Evaluasi',
                    4 => 'Draft Evaluasi',
                    5 => 'Lolos',
                    6 => 'Wawancara Selesai',
                    7 => 'Hasil Diterbitkan',
                    8 => 'Gugur',
                ];
                $statusText = $statusLabels[$submission->status] ?? 'Unknown';
                $statusColorClass = $submission->status_color_class;
            @endphp
            <!-- Status Card -->
            <div
                class="rounded-xl {{ $statusColorClass }} p-5 md:p-6 shadow-sm relative overflow-hidden flex flex-col justify-center min-h-40 md:min-h-50">
                <div class="absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-putihbg/50 opacity-100 blur-3xl">
                </div>
                <div class="absolute -right-10 -top-20 h-50 w-50 rounded-full bg-kuning2/90 opacity-100 blur-3xl"></div>

                <div
                    class="absolute right-4 top-4 z-40 bg-biru1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white shadow-sm">
                    STATUS</div>
                <div class="relative text-3xl font-bold mb-2 tracking-tight text-left leading-none text-white">
                    {{ $statusText }}</div>
                <div class="relative">
                    @if ($submission->submitted_at)
                        <p class="text-sm text-white/90 leading-snug text-left mb-0.5">Dikirim oleh operator:</p>
                        <p class="text-sm font-bold text-white text-left">
                            {{ \Carbon\Carbon::parse($submission->submitted_at)->translatedFormat('d M Y, H:i') }}</p>
                    @else
                        <p class="text-sm text-white/90 leading-snug text-left">Belum diajukan</p>
                    @endif
                </div>
            </div>

            <!-- Nilai SAQ Card -->
            <div
                class="rounded-xl border border-abubiru bg-white shadow-sm flex flex-col justify-between min-h-40 md:min-h-50 p-5">
                <div class="text-biru2 font-bold text-xl mb-2 tracking-wide text-left">Nilai SAQ</div>
                <div class="flex-1 flex flex-col justify-center items-center text-center">
                    @php
                        $hasEvaluatedAnswers = $answers->whereNotNull('answer_score')->count() > 0;
                        $isFinalScore =
                            ($submission->result && $submission->result->ques_score > 0) || $submission->status >= 4;

                        if ($isFinalScore) {
                            $saqScore = $submission->result?->ques_score ?? 0.0;
                            $isGugur = $submission->status == 8 || $saqScore < 80;
                            $saqScoreColor = $isGugur ? 'text-merah2' : 'text-hijau';
                        } elseif ($hasEvaluatedAnswers) {
                            $totalEarned = $answers->sum('answer_score');
                            $totalMax = array_sum($dynamicMaxScores);
                            $saqScore = $totalMax > 0 ? round(($totalEarned / $totalMax) * 100, 2) : 0.0;
                            $saqScoreColor = 'text-biru4';
                        } else {
                            $saqScore = 0.0;
                        }
                    @endphp
                    @if ($isFinalScore)
                        <div id="nilai-akhir-text"
                            class="text-6xl leading-none font-bold {{ $saqScoreColor }} tracking-tighter">
                            {{ number_format($saqScore, 2, '.', '') }}
                        </div>
                    @elseif ($hasEvaluatedAnswers)
                        <div id="nilai-akhir-text"
                            class="text-6xl leading-none font-bold {{ $saqScoreColor }} tracking-tighter">
                            {{ number_format($saqScore, 2, '.', '') }}
                        </div>
                        <span
                            class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 shadow-xs">
                            Nilai Sementara (Draft)
                        </span>
                    @else
                        <div id="nilai-akhir-text"
                            class="text-5xl leading-none font-bold text-biru3/60 tracking-tighter">
                            -
                        </div>
                        <p class="text-xs text-biru3 mt-2 font-medium">Belum dilakukan penilaian</p>
                    @endif
                </div>
            </div>

            <!-- Nilai Per Indikator Card -->
            <div
                class="rounded-xl border border-abubiru bg-white shadow-sm flex flex-col justify-between min-h-40 md:min-h-50 p-5">
                <div class="text-biru2 font-bold text-lg mb-2 tracking-wide text-left">Nilai Per Indikator</div>
                <div class="flex-1 flex flex-col justify-center">
                    @if ($isFinalScore || $hasEvaluatedAnswers)
                        <table class="w-full text-xs table-fixed border-collapse">
                            <tbody>
                                @foreach ($indicators as $ind)
                                    @php
                                        $ansForInd = $answers->where('id_indicators', $ind->id_indicators);
                                        $earned = $ansForInd->sum('answer_score');
                                        $max = $dynamicMaxScores[$ind->id_indicators] ?? 1.0;
                                        $percent = $max > 0 ? round(($earned / $max) * 100, 2) : 0;

                                        $colorClass =
                                            $percent >= 80 ? 'bg-hijau/10 text-hijau' : 'bg-merah2/10 text-merah2';
                                        $kkmStatus = $percent >= 80 ? 'Lolos' : 'Gugur';
                                    @endphp
                                    <tr class="h-6 border-none">
                                        <td class="text-biru2 font-semibold truncate pr-2 text-left align-middle"
                                            style="max-width: 0;">
                                            {{ $ind->nama_indikator }}
                                        </td>
                                        <td class="text-center align-middle w-17.5">
                                            <span
                                                class="inline-flex rounded {{ $colorClass }} px-1.5 py-0.5 font-bold text-xs uppercase w-15 justify-center">
                                                {{ $kkmStatus }}
                                            </span>
                                        </td>
                                        <td class="text-right align-middle w-15 font-bold text-biru1 text-xs">
                                            {{ number_format($percent, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="flex flex-col items-center justify-center text-center py-2 px-3">
                            <svg class="w-8 h-8 text-abubiru mb-1.5" fill="none" stroke="currentColor"
                                stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75" />
                            </svg>
                            <p class="text-xs text-biru3 font-medium leading-snug">Rincian nilai per indikator akan
                                muncul setelah dilakukan penilaian.</p>
                        </div>
                    @endif
                </div>
            </div>


        </div>

        <!-- Info Box -->
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-6 flex gap-3.5 shadow-xs items-start">
            <div
                class="w-8 h-8 rounded-full bg-amber-500 text-white flex shrink-0 items-center justify-center mt-0.5 shadow-2xs">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.75"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-amber-900 text-sm mb-0.5">Petunjuk Penilaian</h3>
                <p class="text-sm text-amber-800 font-medium leading-relaxed">Masukkan skor numerik untuk setiap jawaban
                    yang diajukan oleh operator. Nilai
                    maksimal ditentukan per pertanyaan. Kosongkan atau beri nilai 0 jika bukti dukung tidak valid.</p>
            </div>
        </div>

        <!-- Mobile Custom Dropdown Selection for Indicators (Visible on Mobile HP Only) -->
        <div class="block md:hidden mb-4 relative z-30" x-data="{ openIndSelect: false }">
            <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1.5">
                Pilih Indikator:
            </label>
            @php
                $activeIndObj = $indicators->firstWhere('id_indicators', $activeTab) ?? $indicators->first();
            @endphp
            <!-- Trigger Button -->
            <button type="button" @click="openIndSelect = !openIndSelect" @click.away="openIndSelect = false"
                class="w-full bg-biru4 text-white font-bold text-xs sm:text-sm rounded-xl border border-biru4 py-3 pl-3.5 pr-9 shadow-sm focus:outline-none flex items-center justify-between transition cursor-pointer relative">
                <span class="truncate pr-2 font-bold">
                    @foreach ($indicators as $ind)
                        <template x-if="activeTab === {{ $ind->id_indicators }}">
                            <span>{{ $ind->nama_indikator }}
                                ({{ $ind->subIndikators->sum(fn($s) => $s->pertanyaans->count()) }} soal)</span>
                        </template>
                    @endforeach
                </span>
                <svg class="w-4 h-4 shrink-0 text-white transition-transform duration-200"
                    :class="openIndSelect ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Custom Dropdown Menu Panel -->
            <div x-show="openIndSelect" x-cloak x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-abubiru rounded-xl shadow-xl py-1.5 z-50 divide-y divide-abubiru/40 overflow-hidden max-w-full">
                @foreach ($indicators as $indicator)
                    @php
                        $count = $indicator->subIndikators->sum(fn($s) => $s->pertanyaans->count());
                    @endphp
                    <button type="button"
                        @click="activeTab = {{ $indicator->id_indicators }}; openIndSelect = false"
                        :class="activeTab === {{ $indicator->id_indicators }} ? 'bg-biru5/40 text-biru1 font-extrabold' :
                            'text-biru2 hover:bg-putihbg hover:text-biru1'"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-semibold transition text-left cursor-pointer">
                        <span class="truncate pr-2">{{ $indicator->nama_indikator }}</span>
                        <span
                            :class="activeTab === {{ $indicator->id_indicators }} ? 'bg-biru2 text-white' :
                                'bg-biru5 text-biru2'"
                            class="px-2 py-0.5 rounded-full text-xs font-bold shrink-0">
                            {{ $count }} soal
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Tabs Navigation — Interactive Horizontal Scroll (6 Tabs Visible per Screen) -->
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
        }" x-init="updateScrollState();
        $nextTick(() => updateScrollState())" class="hidden md:block relative mb-0 z-10 group">

            <!-- Left Arrow Button -->
            <button type="button" @click="scrollLeft()" x-show="canScrollLeft" x-cloak title="Geser ke kiri"
                class="absolute -left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Scrollable Tab Container -->
            <div x-ref="tabContainer" @scroll="updateScrollState()"
                @wheel.prevent="$refs.tabContainer.scrollLeft += $event.deltaY"
                class="flex items-stretch overflow-x-auto overflow-y-hidden w-full pb-0.5 -mb-px custom-scrollbar scrollbar-thin scrollbar-thumb-biru4/30 scrollbar-track-transparent scroll-smooth">
                @foreach ($indicators as $indicator)
                    <button type="button" @click="activeTab = {{ $indicator->id_indicators }}"
                        :class="activeTab === {{ $indicator->id_indicators }} ?
                            'bg-biru2 border-biru2 z-20 text-white shadow-sm -mb-px pb-3.5' :
                            'bg-white border-abubiru hover:bg-putihbg text-biru2'"
                        :style="activeTab === {{ $indicator->id_indicators }} ?
                            'background: radial-gradient(ellipse 140% 140% at 100% 110%, #2563A0 0%, transparent 55%), #184D77;' :
                            ''"
                        class="shrink-0 min-w-35 md:min-w-[calc(100%/6)] relative px-3 py-3 text-center cursor-pointer transition-all select-none border-biru5 border-t border-l border-r overflow-hidden rounded-tl-xl rounded-tr-xl flex flex-col justify-center">
                        <div
                            :class="activeTab === {{ $indicator->id_indicators }} ?
                                'text-white text-sm md:text-sm font-bold leading-tight line-clamp-2' :
                                'text-biru2 text-sm md:text-sm font-bold leading-tight line-clamp-2'">
                            {{ $indicator->nama_indikator }}
                        </div>
                        <div
                            :class="activeTab === {{ $indicator->id_indicators }} ?
                                'text-white/80 text-xs md:text-sm mt-1 font-medium' :
                                'text-biru4 text-xs md:text-sm mt-1 font-medium'">
                            {{ $indicator->subIndikators->sum(fn($s) => $s->pertanyaans->count()) }} pertanyaan
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- Right Arrow Button -->
            <button type="button" @click="scrollRight()" x-show="canScrollRight" x-cloak title="Geser ke kanan"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Form Content -->
        <form id="review-form" action="{{ route($routePrefix . '.review-kues.save') }}" method="POST">
            @csrf
            <input type="hidden" name="id_submission" value="{{ $submission->id_submission }}">
            <input type="hidden" name="action" id="form-action" value="draft">
            <input type="hidden" name="tab" :value="activeTab">

            <div
                class="bg-white border border-abubiru border-t-0 shadow-sm mb-6 rounded-b-xl rounded-tr-xl overflow-hidden relative">
                @foreach ($indicators as $indicator)
                    <div x-show="activeTab === {{ $indicator->id_indicators }}">
                        @php
                            $qCounter = 1;
                            $totalSoalInd = $indicator->subIndikators->sum(fn($s) => $s->pertanyaans->count());
                            $terjawabInd = $answers
                                ->filter(function ($a) use ($indicator) {
                                    if ($a->id_indicators != $indicator->id_indicators || is_null($a->answer)) {
                                        return false;
                                    }
                                    if ((int) $a->answer === 1 || $a->answer === true) {
                                        return !empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? ''));
                                    }
                                    return true;
                                })
                                ->count();
                        @endphp
                        <div class="bg-biru2 text-white px-6 py-5">
                            <div class="flex items-start gap-4">
                                <div>
                                    <h2 class="text-base font-bold tracking-wide mb-1 uppercase">
                                        {{ $indicator->nama_indikator }}</h2>
                                    <p class="text-sm text-white/90 font-light mb-3">
                                        {{ $indicator->deskripsi_indikator }}
                                    </p>
                                    <span
                                        class="inline-block bg-white rounded-full px-4 py-1.5 text-sm font-bold text-oren2">
                                        {{ $terjawabInd }} dari {{ $totalSoalInd }} pertanyaan terjawab
                                    </span>
                                </div>
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
                                            $earnedScore = $ans
                                                ? ($ans->answer_score !== null
                                                    ? $ans->answer_score
                                                    : '')
                                                : '';
                                            $evaluatorNote = $ans ? $ans->evaluator_note : '';

                                            // Evaluators can grade if submission status is >= 1, not gugur, not past deadline, not before start, and is explicitly assigned
                                            $isEditable =
                                                $submission->status >= 1 &&
                                                $submission->status != 8 &&
                                                !$isPastDeadline &&
                                                !$isBeforeEvalStart &&
                                                $isAssigned;
                                            $currentQNo = $qCounter++;
                                        @endphp
                                        <x-evaluasi.question-block :mode="$isEditable ? 'evaluate' : 'view'" :number="$currentQNo" :question="$q->ques_text"
                                            :name="'q' . $q->id_master_pertanyaan" :value="$val" :linkPpid="$ppidVal" :linkDrive="$driveVal"
                                            :docDrive="$q->link_drive" :docPpid="$q->link_ppid"
                                            :score="$earnedScore" :note="$evaluatorNote" :maxScore="$q->bobot_pertanyaan" :deskripsi="$q->ques_description"
                                            :dokumen="$q->doc_requirement" data-q-no="{{ $currentQNo }}"
                                            data-q-tab="{{ $indicator->id_indicators }}" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <!-- Action Buttons -->
            @if ($submission->status >= 1 && $submission->status != 8 && !$isPastDeadline && !$isBeforeEvalStart && $isAssigned)
                <div class="flex justify-end gap-3 mb-6">
                    <button type="button"
                        @click="checkInvalidScores(); if (invalidList.length > 0) { showInvalidModal = true; } else { showSaveModal = true; }"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-lg border border-biru5 text-biru1 font-semibold bg-biru5/20 hover:bg-biru5/30 transition shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2">
                            </path>
                        </svg>
                        Simpan Draft
                    </button>
                    <button type="button" @click="handleSubmitClick()"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-hijau text-white font-semibold hover:opacity-90 transition shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                            </path>
                        </svg>
                        Kirim Evaluasi (Finalisasi)
                    </button>
                </div>
            @endif
        </form>

        <!-- Modal Konfirmasi Simpan Draft -->
        <div x-show="showSaveModal" x-cloak @keydown.escape.window="showSaveModal = false"
            class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showSaveModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-0 mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header Biru -->
                <div class="bg-biru2 px-6 py-5 flex items-center gap-3 text-white">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold tracking-tight">Simpan sebagai Draft</h3>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 text-center">
                    <div
                        class="w-16 h-16 rounded-full bg-biru5/20 text-biru2 flex items-center justify-center mx-auto mb-4 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                            </path>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-biru1 mb-2">Simpan perubahan evaluasi?</h4>
                    <p class="text-sm text-biru3 font-medium leading-relaxed">
                        Nilai dan catatan evaluasi yang Anda masukkan akan disimpan sebagai draft sementara. Anda dapat
                        melanjutkan penilaian kapan saja.
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-6 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showSaveModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm">
                        Batal
                    </button>
                    <button type="button"
                        @click="showSaveModal = false; isDirty = false; document.getElementById('form-action').value = 'draft'; document.getElementById('review-form').submit();"
                        class="px-5 py-2.5 bg-biru2 text-white rounded-xl text-sm font-bold hover:opacity-90 transition shadow-sm flex items-center gap-1.5">
                        Ya, Simpan Draft
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Kirim Evaluasi -->
        <div x-show="showSubmitModal" x-cloak @keydown.escape.window="showSubmitModal = false"
            class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showSubmitModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-0 mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header Hijau -->
                <div class="bg-hijau px-6 py-5 flex items-center gap-3 text-white">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold tracking-tight">Kirim Hasil Evaluasi</h3>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 text-center">
                    <div
                        class="w-16 h-16 rounded-full bg-hijau2/20 text-hijau flex items-center justify-center mx-auto mb-4 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-biru1 mb-2">Finalisasi dan kirim evaluasi?</h4>
                    <p class="text-sm text-biru3 font-medium leading-relaxed">
                        Apakah Anda yakin ingin menyelesaikan penilaian ini? Setelah dikirim, nilai akhir akan dikunci
                        dan
                        dikirimkan sebagai laporan resmi. Tindakan ini tidak dapat diubah kembali.
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-6 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showSubmitModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm">
                        Batal
                    </button>
                    <button type="button"
                        @click="showSubmitModal = false; isDirty = false; document.getElementById('form-action').value = 'submit'; document.getElementById('review-form').submit();"
                        class="px-5 py-2.5 bg-hijau text-white rounded-xl text-sm font-bold hover:opacity-90 transition shadow-sm flex items-center gap-1.5">
                        Ya, Kirim Hasil
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Peringatan Sebelum Kirim (Unscored) -->
        <div x-show="showUnscoredModal" x-cloak @keydown.escape.window="showUnscoredModal = false"
            class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showUnscoredModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-0 mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header Kuning -->
                <div class="bg-kuning1 px-6 py-4 flex items-center gap-3 text-white">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">Peringatan Sebelum Mengirim</h3>
                        <p class="text-white/80 text-xs font-medium mt-0.5">Semua pertanyaan wajib diberikan nilai</p>
                    </div>
                </div>

                <!-- Body: Clean list of unscored questions -->
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-biru2 font-medium leading-relaxed text-left">
                        Terdapat beberapa pertanyaan yang belum diberikan nilai evaluasi:
                    </p>

                    <!-- Unscored list -->
                    <div x-show="unscoredList.length > 0" class="space-y-2">
                        <div
                            class="text-xs font-extrabold text-biru1 uppercase tracking-wider text-left flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-oren2"></span>
                            Belum Dinilai (<span x-text="unscoredList.length"></span> Pertanyaan):
                        </div>
                        <div class="space-y-1.5 text-left pl-3.5">
                            <template x-for="(item, index) in unscoredList.slice(0, 10)" :key="'unscored-' + item.no">
                                <button type="button" @click="navigateToItem(item)"
                                    class="w-full text-left px-3 py-2 text-xs font-semibold rounded-lg border border-oren2/30 bg-oren2/5 hover:bg-oren2 hover:text-white text-oren2 transition shadow-sm block truncate"
                                    :title="'Soal No. ' + item.no + ': ' + item.text">
                                    Soal No. <span x-text="item.no"></span>: <span x-text="item.text"></span>
                                </button>
                            </template>
                            <div x-show="unscoredList.length > 10" class="text-xs font-bold text-biru3 pl-1">
                                dan <span x-text="unscoredList.length - 10"></span> soal lainnya...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-5 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showUnscoredModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm w-full">
                        Kembali Ke Penilaian
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Peringatan Sebelum Kirim (Empty Notes) -->
        <div x-show="showEmptyNotesModal" x-cloak @keydown.escape.window="showEmptyNotesModal = false"
            class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showEmptyNotesModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-0 mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header Orange/Red -->
                <div class="bg-oren2 px-6 py-4 flex items-center gap-3 text-white">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">Catatan Evaluator Belum Lengkap</h3>
                        <p class="text-white/80 text-xs font-medium mt-0.5">Semua catatan evaluator wajib diisi</p>
                    </div>
                </div>

                <!-- Body: Clean list of questions with empty notes -->
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-biru2 font-medium leading-relaxed text-left">
                        Terdapat beberapa catatan evaluator yang masih kosong:
                    </p>

                    <!-- Empty notes list -->
                    <div x-show="emptyNoteList.length > 0" class="space-y-2">
                        <div
                            class="text-xs font-extrabold text-biru1 uppercase tracking-wider text-left flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-oren2"></span>
                            Belum Ada Catatan (<span x-text="emptyNoteList.length"></span> Pertanyaan):
                        </div>
                        <div class="space-y-1.5 text-left pl-3.5">
                            <template x-for="(item, index) in emptyNoteList.slice(0, 10)"
                                :key="'empty-note-' + item.no">
                                <button type="button" @click="navigateToEmptyNoteItem(item)"
                                    class="w-full text-left px-3 py-2 text-xs font-semibold rounded-lg border border-oren2/30 bg-oren2/5 hover:bg-oren2 hover:text-white text-oren2 transition shadow-sm block truncate"
                                    :title="'Soal No. ' + item.no + ': ' + item.text">
                                    Soal No. <span x-text="item.no"></span>: <span x-text="item.text"></span>
                                </button>
                            </template>
                            <div x-show="emptyNoteList.length > 10" class="text-xs font-bold text-biru3 pl-1">
                                dan <span x-text="emptyNoteList.length - 10"></span> soal lainnya...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-5 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showEmptyNotesModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm w-full">
                        Kembali Ke Penilaian
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Peringatan Nilai Melebihi Batas (Invalid Scores) -->
        <div x-show="showInvalidModal" x-cloak @keydown.escape.window="showInvalidModal = false"
            class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showInvalidModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-0 mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header Merah -->
                <div class="bg-merah1 px-6 py-4 flex items-center gap-3 text-white">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">Peringatan Sebelum Mengirim</h3>
                        <p class="text-white/80 text-xs font-medium mt-0.5">Nilai tidak boleh melebihi bobot pertanyaan
                        </p>
                    </div>
                </div>

                <!-- Body: invalid count card -->
                <div class="px-6 py-6 text-center">
                    <button type="button" @click="navigateToFirstInvalid()"
                        class="group rounded-xl border-2 border-merah1/40 bg-merah1/5 p-5 text-center hover:bg-merah1/15 hover:border-merah1 transition flex flex-col items-center justify-center min-h-40 w-full">
                        <div class="text-5xl font-black text-merah1 leading-none mb-2" x-text="invalidList.length">
                        </div>
                        <div class="text-sm font-bold text-merah1 mb-1">Nilai Melebihi Bobot</div>
                        <p class="text-xs text-biru3 font-medium leading-relaxed max-w-xs mx-auto">
                            Terdapat pertanyaan yang dinilai lebih besar dari bobot maksimalnya. Harap sesuaikan nilai
                            tersebut.
                        </p>
                        <div class="mt-4 text-xs font-bold text-merah1 group-hover:underline">
                            → Klik untuk ke soal pertama yang melebihi bobot
                        </div>
                    </button>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-5 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showInvalidModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm w-full">
                        Kembali Ke Penilaian
                    </button>
                </div>
            </div>
        </div>


        <!-- Scroll to Top Button -->
        <div x-data="{ show: false }" x-init="$nextTick(() => {
            const container = $el.closest('.overflow-y-auto') || window;
            container.addEventListener('scroll', () => {
                show = (container === window ? window.scrollY : container.scrollTop) > 150;
            });
        })" class="fixed bottom-6 right-6 z-9999">
            <button x-show="show"
                @click="($el.closest('.overflow-y-auto') || window).scrollTo({ top: 0, behavior: 'smooth' })"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="w-12 h-12 rounded-full bg-biru6 text-white flex items-center justify-center shadow-lg hover:opacity-90 focus:outline-none transition-all duration-300 transform hover:scale-110 active:scale-95"
                title="Scroll ke atas">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="3"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
                </svg>
            </button>
        </div>


        @push('scripts')
            <script>
                document.addEventListener('livewire:navigated', () => {
                    const scoreInputs = document.querySelectorAll('.score-input');
                    const nilaiAkhirText = document.getElementById('nilai-akhir-text');
                    const kategoriBadge = document.getElementById('kategori-badge');

                    function calculateTotalScore() {
                        if (scoreInputs.length === 0) return;

                        let totalEarned = 0;
                        let totalMax = 0;

                        scoreInputs.forEach(input => {
                            let maxVal = parseFloat(input.getAttribute('data-max') || 10);
                            let rawVal = input.value.replace(',', '.');
                            let val = parseFloat(rawVal);

                            if (isNaN(val)) val = 0;
                            if (val > maxVal) val = maxVal;
                            if (val < 0) val = 0;

                            totalEarned += val;
                            totalMax += maxVal;
                        });

                        let finalScore = totalMax > 0 ? (totalEarned / totalMax) * 100 : 0;

                        if (nilaiAkhirText) {
                            nilaiAkhirText.textContent = finalScore.toFixed(2);
                        }

                        if (kategoriBadge) {
                            let badgeText = 'TIDAK INFORMATIF';
                            let badgeClass =
                                'inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700 uppercase tracking-widest shadow-xs';

                            if (finalScore >= 90) {
                                badgeText = 'INFORMATIF';
                                badgeClass =
                                    'inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-bold text-green-700 uppercase tracking-widest shadow-xs';
                            } else if (finalScore >= 80) {
                                badgeText = 'MENUJU INFORMATIF';
                                badgeClass =
                                    'inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 uppercase tracking-widest shadow-xs';
                            } else if (finalScore >= 60) {
                                badgeText = 'CUKUP INFORMATIF';
                                badgeClass =
                                    'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 uppercase tracking-widest shadow-xs';
                            } else if (finalScore >= 40) {
                                badgeText = 'KURANG INFORMATIF';
                                badgeClass =
                                    'inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700 uppercase tracking-widest shadow-xs';
                            }

                            kategoriBadge.textContent = badgeText;
                            kategoriBadge.className = badgeClass;
                        }
                    }

                    scoreInputs.forEach(input => {
                        input.addEventListener('change', () => {
                            calculateTotalScore();
                        });
                    });

                    // Run initial calculation
                    calculateTotalScore();
                });
            </script>
        @endpush

</x-layouts.app>
