<x-layouts.app title="Pengisian Kuesioner SAQ - HaloKIP">
    @php
        $isDraftVerif = (int) $submission->status === 1 && !empty($submission->verified_by);
        if ((int) $submission->status === 0 && $answeredCount === 0) {
            $statusLabel = 'Belum Dimulai';
        } elseif ($isDraftVerif) {
            $statusLabel = 'Draft Verifikasi';
        } else {
            $statusLabel = $submission->status_label;
        }

        $statusColorClass = $submission->status_color_class;

        // Build list of unanswered questions: [{no, tab, id, text}]
        $unansweredQuestions = [];
        foreach ($indicators as $indicator) {
            $qCounter = 1;
            foreach ($indicator->subIndikators as $sub) {
                foreach ($sub->pertanyaans as $q) {
                    $ans = $answers->get($q->id_master_pertanyaan);
                    if (!$ans || $ans->answer === null) {
                        $unansweredQuestions[] = [
                            'no' => $qCounter,
                            'tab' => $indicator->id_indicators,
                            'id' => $q->id_master_pertanyaan,
                            'text' => \Illuminate\Support\Str::limit($q->ques_text, 50),
                        ];
                    }
                    $qCounter++;
                }
            }
        }

        // Build list of "galat" questions: answered Ya but missing link_ppid or link_drive
        $galatQuestions = [];
        foreach ($indicators as $indicator) {
            $qCounter = 1;
            foreach ($indicator->subIndikators as $sub) {
                foreach ($sub->pertanyaans as $q) {
                    $ans = $answers->get($q->id_master_pertanyaan);
                    if ($ans && (bool) $ans->answer) {
                        $missingPpid = empty($ans->link_ppid);
                        $missingDrive = empty($ans->link_drive);
                        if ($missingPpid || $missingDrive) {
                            $galatQuestions[] = [
                                'no' => $qCounter,
                                'tab' => $indicator->id_indicators,
                                'id' => $q->id_master_pertanyaan,
                                'text' => \Illuminate\Support\Str::limit($q->ques_text, 50),
                            ];
                        }
                    }
                    $qCounter++;
                }
            }
        }

        $evalEnd = $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->endOfDay() : null;
        $isStageExpired = $evalEnd && now()->greaterThan($evalEnd);
        $isEditable = in_array($submission->status, [0, 2]) && !$isStageExpired;
        $isSaqReleased = !$evalEnd || now()->greaterThan($evalEnd);
        $saqScore = $submission->result?->ques_score ?? 0;
        $saqScoreText = $saqScore >= 80 ? 'text-hijau' : ($saqScore > 0 ? 'text-merah2' : 'text-abu');

        $notesPerIndicator = [];
        if ((int) $submission->status === 2) {
            foreach ($indicators as $ind) {
                $c = 0;
                foreach ($ind->subIndikators as $sub) {
                    foreach ($sub->pertanyaans as $q) {
                        $ans = $answers->get($q->id_master_pertanyaan);
                        if ($ans && !empty(trim($ans->verifikator_note ?? ''))) {
                            $c++;
                        }
                    }
                }
                $notesPerIndicator[$ind->id_indicators] = $c;
            }
        }
    @endphp

    <script>
        window._unansweredQuestions = @json($unansweredQuestions);
        window._galatQuestions = @json($galatQuestions);
    </script>

    <div x-data="{
        activeTab: {{ $activeTab ?? ($indicators->first()->id_indicators ?? 1) }},
        isEditable: {{ $isEditable ? 'true' : 'false' }},
        showSaveModal: false,
        showSubmitModal: false,
        showImportModal: false,
        showUnansweredModal: false,
        unansweredList: [],
        galatList: [],
        isDirty: false,
        autoSaveTimeout: null,
        saveStatus: '',
        lastSavedTime: '{{ $submission->draft_saved_at ? (\Carbon\Carbon::parse($submission->draft_saved_at)->isToday() ? 'Hari ini, ' . \Carbon\Carbon::parse($submission->draft_saved_at)->format('H:i') : \Carbon\Carbon::parse($submission->draft_saved_at)->translatedFormat('d M Y, H:i')) : '' }}',
        init() {
            this.unansweredList = window._unansweredQuestions || [];
            this.galatList = window._galatQuestions || [];
            this.setupAutoSave();
        },
        setupAutoSave() {
            if (!this.isEditable) return;
            const form = document.getElementById('saq-form');
            if (!form) return;
    
            const triggerSave = () => {
                this.isDirty = true;
                this.recalculateLists();
    
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
            const form = document.getElementById('saq-form');
            if (!form) return;
    
            // Check for format errors before auto-saving
            let hasFormatError = false;
            const qBlocks = document.querySelectorAll('[data-q-no]');
            qBlocks.forEach(block => {
                const radioYa = block.querySelector('input[type=radio][value=Ya]');
                if (radioYa?.checked) {
                    const ppidInput = block.querySelector('input[name*=\'_ppid\']');
                    const driveInput = block.querySelector('input[name*=\'_drive\']');
                    const ppidVal = ppidInput ? ppidInput.value.trim() : '';
                    const driveVal = driveInput ? driveInput.value.trim() : '';
                    if ((ppidVal !== '' && !/^https?:\/\//i.test(ppidVal)) || (driveVal !== '' && !/^https?:\/\//i.test(driveVal))) {
                        hasFormatError = true;
                    }
                }
            });
    
            if (hasFormatError) {
                this.saveStatus = 'error';
                return;
            }
    
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
                    const resData = await response.json();
                    if (resData.draft_saved_at) {
                        this.lastSavedTime = resData.draft_saved_at;
                    }
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
        recalculateLists() {
            const unanswered = [];
            const galat = [];
    
            const qBlocks = document.querySelectorAll('[data-q-no]');
            let answeredCount = 0;
            const totalQuestions = qBlocks.length;
    
            qBlocks.forEach(block => {
                const qNo = parseInt(block.getAttribute('data-q-no'));
                const tabId = parseInt(block.getAttribute('data-q-tab') || 1);
    
                const label = block.querySelector('label');
                const rawText = label ? label.textContent.trim().replace(/^\d+\.\s*/, '') : '';
                const truncatedText = rawText.length > 50 ? rawText.substring(0, 50) + '...' : rawText;
    
                const radioYa = block.querySelector('input[type=radio][value=Ya]');
                const radioTidak = block.querySelector('input[type=radio][value=Tidak]');
    
                let isYa = radioYa?.checked;
                let isTidak = radioTidak?.checked;
    
                if (!isYa && !isTidak) {
                    unanswered.push({ no: qNo, tab: tabId, id: qNo, text: truncatedText });
                } else if (isTidak) {
                    answeredCount++;
                } else if (isYa) {
                    const ppidInput = block.querySelector('input[name*=\'_ppid\']');
                    const driveInput = block.querySelector('input[name*=\'_drive\']');
    
                    const ppidVal = ppidInput ? ppidInput.value.trim() : '';
                    const driveVal = driveInput ? driveInput.value.trim() : '';
    
                    if (ppidVal === '' || driveVal === '') {
                        galat.push({ no: qNo, tab: tabId, id: qNo, text: truncatedText + ' (Bukti dukung belum diisi lengkap)' });
                    } else if (!/^https?:\/\//i.test(ppidVal) || !/^https?:\/\//i.test(driveVal)) {
                        galat.push({ no: qNo, tab: tabId, id: qNo, text: truncatedText + ' (Format link tidak valid)' });
                    } else {
                        answeredCount++;
                    }
                }
            });
    
            this.unansweredList = unanswered;
            this.galatList = galat;
    
            const pct = totalQuestions > 0 ? Math.round((answeredCount / totalQuestions) * 100) : 0;
            const pctEl = document.querySelector('.col-span-2 span.text-hijau');
            if (pctEl) pctEl.textContent = pct + '%';
            const barEl = document.querySelector('.col-span-2 div.bg-hijau');
            if (barEl) barEl.style.width = pct + '%';
            const txtEl = document.querySelector('.col-span-2 p.italic');
            if (txtEl) txtEl.textContent = answeredCount + ' dari ' + totalQuestions + ' pertanyaan terjawab';
        },
        navigateToFirst(list) {
            if (!list || list.length === 0) return;
            const item = list[0];
            this.navigateToItem(item);
        },
        navigateToItem(item) {
            this.activeTab = item.tab;
            this.showUnansweredModal = false;
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
        handleSubmitClick() {
            this.recalculateLists();
            if (this.unansweredList.length > 0 || this.galatList.length > 0) {
                this.showUnansweredModal = true;
            } else {
                this.showSubmitModal = true;
            }
        }
    }">
        <x-ui.page-header title="Pengisian Kuesioner SAQ Keterbukaan Informasi Publik"
            description="{{ Auth::user()->masterSatker->nama_satker ?? 'BPS Kabupaten/Kota' }} - Tahun {{ $period->tahun }}"
            :breadcrumb="[
                'Beranda' => route('operator.index'),
                'Monitoring SAQ' => route('operator.kues-saq'),
                'Kuesioner SAQ' => '#',
            ]">
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

        @if ($isStageExpired)
            <div
                class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Tahap pengisian dan evaluasi data kuesioner SAQ telah berakhir pada tanggal
                    <strong>{{ $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->translatedFormat('d F Y') : '-' }}</strong>.
                    Kuesioner SAQ kini bersifat hanya lihat (read-only).</span>
            </div>
        @endif

        <!-- Status Card and Progress -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mb-6">
            <!-- Status Card (1/3) -->
            <div
                class="md:col-span-1 rounded-xl {{ $statusColorClass }} p-5 md:p-6 shadow-sm relative overflow-hidden flex flex-col justify-center min-h-40 md:min-h-50">
                <!-- Decorative Blobs -->
                <div class="absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-putihbg/50 opacity-100 blur-3xl">
                </div>
                <div class="absolute -right-10 -top-20 h-50 w-50 rounded-full bg-kuning2/90 opacity-100 blur-3xl"></div>

                <div
                    class="absolute right-4 top-4 z-40 bg-biru1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white shadow-sm">
                    STATUS</div>
                <div class="relative text-3xl font-bold mb-2 tracking-tight text-left leading-none text-white">
                    {{ $statusLabel }}</div>

                <div class="relative">
                    @if ($submission->submitted_at)
                        <p class="text-sm text-white/90 leading-snug text-left mb-0.5">Kues SAQ dikirim tanggal:</p>
                        <p class="text-sm font-bold text-white text-left">
                            {{ \Carbon\Carbon::parse($submission->submitted_at)->translatedFormat('d M Y, H:i') }}</p>
                    @else
                        <div x-show="lastSavedTime">
                            <p class="text-sm text-white/90 leading-snug text-left mb-0.5">Terakhir disimpan:</p>
                            <p class="text-sm font-bold text-white text-left" x-text="lastSavedTime"></p>
                        </div>
                        <div x-show="!lastSavedTime">
                            <p class="text-sm text-white/90 leading-snug text-left mb-0.5">Sedang dalam pengisian
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Nilai / Progress SAQ Card (Card 2) -->
            <div
                class="rounded-xl border border-abubiru bg-white shadow-sm flex flex-col justify-between min-h-40 md:min-h-50 p-5">
                <div class="text-biru2 font-bold text-lg mb-2 tracking-wide text-left">
                    @if ($submission->result && $submission->result->ques_score > 0 && $isSaqReleased)
                        Nilai SAQ
                    @else
                        Progress Pengisian SAQ
                    @endif
                </div>
                <div class="flex-1 flex flex-col justify-center items-center text-center">
                    @if ($submission->result && $submission->result->ques_score > 0 && $isSaqReleased)
                        <div class="text-6xl leading-none font-bold {{ $saqScoreText }} tracking-tighter">
                            {{ number_format($saqScore, 2) }}
                        </div>
                    @else
                        <div class="w-full px-2">
                            <div class="flex items-center gap-3 mb-1.5">
                                <div class="flex-1 h-3 bg-abubiru/60 rounded-full overflow-hidden">
                                    <div class="h-3 rounded-full bg-oren2 transition-all duration-500"
                                        style="width: {{ $progressPercent }}%"></div>
                                </div>
                                <span class="text-sm font-bold text-oren2 shrink-0">{{ $progressPercent }}%</span>
                            </div>
                            <p class="text-xs text-biru3 font-medium text-left mb-3">{{ $answeredCount }} dari
                                {{ $totalQuestions }} pertanyaan terjawab</p>

                            @if ($submission->status >= 4 || $submission->status == 8)
                                <div
                                    class="mt-3.5 inline-flex items-center gap-1.5 text-xs font-semibold text-biru2 bg-white px-3.5 py-1.5 rounded-full border border-abubiru shadow-xs">
                                    <svg class="w-3.5 h-3.5 text-biru4 shrink-0" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Nilai akan dirilis setelah masa evaluasi berakhir
                                        (<strong>{{ $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->translatedFormat('d M Y') : '-' }}</strong>)</span>
                                </div>
                            @else
                                <div
                                    class="mt-3.5 inline-flex items-center gap-1.5 text-xs font-semibold text-biru2 bg-white px-3.5 py-1.5 rounded-full border border-abubiru shadow-xs">
                                    <svg class="w-3.5 h-3.5 text-biru4 shrink-0" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Nilai akan muncul setelah dievaluasi</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Status Verifikasi Card (Card 3) -->
            <div
                class="rounded-xl border border-abubiru bg-white shadow-sm flex flex-col justify-between min-h-40 md:min-h-50 p-5">
                <div class="text-biru2 font-bold text-lg mb-2 tracking-wide text-left">
                    @if ($submission->result && $submission->result->ques_score > 0 && $isSaqReleased)
                        Nilai Per Indikator
                    @else
                        Status Verifikasi
                    @endif
                </div>
                <div class="flex-1 flex flex-col justify-center">
                    @if ($submission->result && $submission->result->ques_score > 0 && $isSaqReleased)
                        <table class="w-full text-xs table-fixed border-collapse">
                            <tbody>
                                @foreach ($indicators as $ind)
                                    @php
                                        $score = $indicatorScores[$ind->id_indicators] ?? 0.0;
                                        $colorClass =
                                            $score >= 80 ? 'bg-hijau/10 text-hijau' : 'bg-merah2/10 text-merah2';
                                        $kkmStatus = $score >= 80 ? 'Lolos' : 'Gugur';
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
                                            {{ number_format($score, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="w-full">
                            @if ((int) $submission->status === 1)
                                {{-- Menunggu Verifikasi --}}
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-7 h-7 rounded-full bg-oren2/10 text-oren2 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-oren2" fill="none" stroke="currentColor"
                                            stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm text-oren2 font-bold leading-tight mb-0.5">Menunggu Verifikasi</div>
                                        <div class="text-xs text-biru2 font-medium">Belum ditinjau oleh Verifikator</div>
                                    </div>
                                </div>
                            @elseif ((int) $submission->status === 2)
                                {{-- Dikembalikan --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-full bg-merah1/10 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-merah1" fill="none" stroke="currentColor"
                                                stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm text-merah1 font-bold">Dikembalikan oleh
                                            Verifikator</span>
                                    </div>
                                    @if ($submission->verified_at)
                                        <span
                                            class="text-xs text-biru3 font-medium shrink-0">{{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d M Y, H:i') }}</span>
                                    @endif
                                </div>

                                @if ($submission->verification_note)
                                    <div class="border-t border-abubiru/50 my-2.5"></div>
                                    <div class="text-xs text-biru2 font-medium italic wrap-break-word text-left pl-9">
                                        "{{ trim($submission->verification_note) }}"
                                    </div>
                                @endif
                            @elseif ((int) $submission->status === 3)
                                {{-- Disetujui, menunggu evaluasi --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-full bg-hijau/10 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-hijau" fill="none" stroke="currentColor"
                                                stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm text-hijau font-bold">Disetujui oleh Verifikator</span>
                                    </div>
                                    @if ($submission->verified_at)
                                        <span
                                            class="text-xs text-biru3 font-medium shrink-0">{{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d M Y, H:i') }}</span>
                                    @endif
                                </div>

                                @if ($submission->verification_note)
                                    <div class="border-t border-abubiru/50 my-2.5"></div>
                                    <div class="text-xs text-biru2 font-medium italic wrap-break-word text-left pl-9">
                                        "{{ trim($submission->verification_note) }}"
                                    </div>
                                @endif
                            @elseif ($submission->status >= 4 || $submission->status == 8)
                                <div class="flex flex-col items-center justify-center text-center py-2">
                                    <svg class="w-8 h-8 text-abubiru mb-1" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75" />
                                    </svg>
                                    <p class="text-xs text-biru3 font-medium leading-snug">Nilai akan dirilis
                                        setelah masa evaluasi berakhir pada tanggal
                                        <strong>{{ $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->translatedFormat('d F Y') : '-' }}</strong>.
                                    </p>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center text-center py-2">
                                    <svg class="w-8 h-8 text-abubiru mb-1" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75" />
                                    </svg>
                                    <p class="text-xs text-biru3 font-medium leading-snug">Belum ada
                                        nilai.<br>Menunggu evaluasi dari Evaluator Provinsi.</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Box -->
        @if ($isEvaluated)
            <div class="rounded-xl bg-hijau/10 border border-hijau/30 p-4 mb-6 flex gap-3.5 shadow-sm items-start">
                <div
                    class="w-6 h-6 rounded-full bg-hijau text-white flex shrink-0 items-center justify-center mt-0.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-hijau text-sm mb-0.5">Hasil Evaluasi SAQ</h3>
                    <p class="text-sm text-hijau font-medium">Halaman ini menampilkan nilai evaluasi SAQ per
                        indikator beserta catatan koreksi dari Evaluator Provinsi Jawa Timur.
                    </p>
                </div>
            </div>
        @else
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-4 flex gap-3.5 shadow-xs items-start">
                <div
                    class="w-8 h-8 rounded-full bg-amber-500 text-white flex shrink-0 items-center justify-center mt-0.5 shadow-2xs">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.75"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-amber-900 text-sm mb-0.5">Petunjuk Pengisian</h3>
                    <p class="text-sm text-amber-800 font-medium leading-relaxed">
                        Silakan isi semua pertanyaan dengan lengkap dan benar. Setiap jawaban <strong>Ya</strong> wajib
                        melampirkan link bukti dukung PPID dan link Google Drive.
                    </p>
                </div>
            </div>
        @endif



        {{-- Tombol Import Excel di atas Tab Indikator --}}
        @if ($isEditable)
            <div class="flex flex-col items-end mb-3 gap-2">
                <p class="text-sm text-biru1 font-bold leading-relaxed">Gunakan template Excel untuk import jawaban:</p>
                <div class="w-fit">
                    <button type="button" @click="showImportModal = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-hijau px-4 py-2 sm:px-4.5 sm:py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:opacity-90 transition cursor-pointer">
                        <svg class="w-5 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Import Excel
                    </button>
                </div>
            </div>
        @endif

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
                <svg class="w-4 h-4 shrink-0 text-white transition-transform duration-200"
                    :class="openIndSelect ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
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
                    @php $mCount = $notesPerIndicator[$indicator->id_indicators] ?? 0; @endphp
                    <button type="button"
                        @click="activeTab = {{ $indicator->id_indicators }}; openIndSelect = false"
                        :class="activeTab === {{ $indicator->id_indicators }} ? 'bg-biru5/40 text-biru1 font-extrabold' :
                            'text-biru2 hover:bg-putihbg hover:text-biru1'"
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
            <button type="button" @click="scrollRight()" x-show="canScrollRight" x-cloak title="Geser ke kanan"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Form Content -->
        <form id="saq-form" action="{{ route('operator.kues-saq.save') }}" method="POST">
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
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-full bg-white text-biru2 flex shrink-0 items-center justify-center font-bold shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold tracking-wide mb-1 uppercase">
                                        {{ $indicator->nama_indikator }}</h2>
                                    <p class="text-sm text-white/90 font-medium mb-2.5">Evaluasi Keterbukaan
                                        Informasi Publik pada aspek {{ $indicator->nama_indikator }}.</p>
                                    <span
                                        class="inline-block bg-white rounded-full px-4 py-1.5 text-xs font-bold text-oren2 shadow-sm">
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
                                            if ($isEvaluated) {
                                                $score =
                                                    $ans && !is_null($ans->answer_score) ? $ans->answer_score : null;
                                                $evalNote = $ans ? $ans->evaluator_note : '';
                                                $isWrong =
                                                    ($ans && $ans->answer == false) ||
                                                    ($ans && $ans->answer_score == 0 && $q->bobot_pertanyaan > 0);
                                                $qStatus = $isWrong ? 'danger' : 'success';
                                            }
                                        @endphp
                                        @if ($isEvaluated)
                                            <x-evaluasi.question-block mode="view" :number="$qCounter"
                                                :question="$q->ques_text" :name="'q' . $q->id_master_pertanyaan" :value="$val"
                                                :linkPpid="$ppidVal" :linkDrive="$driveVal"
                                                :docDrive="$q->link_drive" :docPpid="$q->link_ppid"
                                                score="{{ is_null($score) ? '-' : number_format($score, 2) }}"
                                                note="{{ $evalNote }}" status="{{ $qStatus }}"
                                                :deskripsi="$q->ques_description ?? 'Deskripsi untuk pertanyaan ini belum ditentukan.'"
                                                :dokumen="$q->doc_requirement ?? ''"
                                                maxScore="{{ $q->bobot_pertanyaan }}" data-q-no="{{ $qCounter }}"
                                                data-q-tab="{{ $indicator->id_indicators }}" />
                                        @else
                                            @php
                                                $verifNote = $ans?->verifikator_note ?? '';
                                            @endphp
                                            <x-evaluasi.question-block :number="$qCounter" :question="$q->ques_text" :name="'q' . $q->id_master_pertanyaan"
                                                required :value="$val" :linkPpid="$ppidVal" :linkDrive="$driveVal"
                                                :docDrive="$q->link_drive" :docPpid="$q->link_ppid"
                                                :mode="$isEditable ? 'fill' : 'view'" :deskripsi="$q->ques_description" :dokumen="$q->doc_requirement"
                                                sub-sub-indikator="{{ $sub->nama_sub_indikator }}"
                                                data-q-no="{{ $qCounter }}"
                                                data-q-tab="{{ $indicator->id_indicators }}"
                                                verifikatorNote="{{ (int) $submission->status === 2 ? $verifNote : '' }}" />
                                        @endif
                                        @php $qCounter++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <!-- Action Buttons -->
            @if ($isEditable)
                <div class="flex flex-wrap justify-end gap-3 mb-6">
                    <button type="button" @click="showSaveModal = true"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-lg border border-biru2/30 text-biru2 font-bold bg-biru5/20 hover:bg-biru5/40 transition shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                            </path>
                        </svg>
                        Simpan Draft
                    </button>
                    <button type="button" @click="handleSubmitClick()"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-lg bg-hijau text-white font-bold hover:opacity-90 transition shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                            </path>
                        </svg>
                        Kirim Kuesioner
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
                    <h3 class="text-lg font-bold tracking-tight">Simpan Draft Kuesioner</h3>
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
                    <h4 class="text-base font-bold text-biru1 mb-2">Simpan jawaban kuesioner?</h4>
                    <p class="text-sm text-biru3 font-medium leading-relaxed">
                        Jawaban Anda akan disimpan sebagai draft sementara. Anda dapat melanjutkan pengisian kuesioner
                        kapan saja sebelum batas waktu berakhir.
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-6 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showSaveModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm">
                        Batal
                    </button>
                    <button type="button"
                        @click="showSaveModal = false; isDirty = false; document.getElementById('form-action').value = 'draft'; document.getElementById('saq-form').submit();"
                        class="px-5 py-2.5 bg-biru2 text-white rounded-xl text-sm font-bold hover:opacity-90 transition shadow-sm flex items-center gap-1.5">
                        Ya, Simpan Draft
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Kirim Kuesioner -->
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
                    <h3 class="text-lg font-bold tracking-tight">Kirim Kuesioner SAQ</h3>
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
                    <h4 class="text-base font-bold text-biru1 mb-2">Kirim kuesioner sekarang?</h4>
                    <p class="text-sm text-biru3 font-medium leading-relaxed">
                        Apakah Anda yakin ingin mengirim kuesioner SAQ ini? Setelah dikirim, jawaban Anda akan dikirim
                        ke Kasubag Kabupaten/Kota untuk diverifikasi terlebih dahulu sebelum diteruskan ke Evaluator
                        Provinsi.
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-6 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showSubmitModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm">
                        Batal
                    </button>
                    <button type="button"
                        @click="showSubmitModal = false; isDirty = false; document.getElementById('form-action').value = 'submit'; document.getElementById('saq-form').submit();"
                        class="px-5 py-2.5 bg-hijau text-white rounded-xl text-sm font-bold hover:opacity-90 transition shadow-sm flex items-center gap-1.5">
                        Ya, Kirim Kuesioner
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Peringatan Sebelum Kirim -->
        <div x-show="showUnansweredModal" x-cloak @keydown.escape.window="showUnansweredModal = false"
            class="fixed inset-0 flex items-center justify-center z-50"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-biru1/60 backdrop-blur-xs" @click="showUnansweredModal = false"></div>

            <!-- Panel -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 relative z-10 border border-abubiru/40 overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.stop>
                <!-- Header -->
                <div class="bg-kuning1 px-6 py-4 flex items-center gap-3 text-white">
                    <div class="w-10 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">Peringatan Sebelum Mengirim</h3>
                        <p class="text-white/80 text-xs font-medium mt-0.5">Klik kotak untuk langsung ke soal pertama
                            yang bermasalah</p>
                    </div>
                </div>

                <!-- Body: Clean list of unanswered and invalid questions -->
                <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto pr-5 custom-scrollbar">
                    <p class="text-sm text-biru2 font-medium leading-relaxed text-left">
                        Terdapat beberapa pertanyaan yang belum diisi dengan lengkap sebelum Anda mengirimkan kuesioner
                        SAQ ini:
                    </p>

                    <!-- List of Unanswered & Galat questions -->
                    <div
                        :class="(unansweredList.length > 0 && galatList.length > 0) ? 'grid grid-cols-1 md:grid-cols-2 gap-6' :
                        'space-y-4'">
                        <div x-show="unansweredList.length > 0" class="space-y-2">
                            <div
                                class="text-xs font-extrabold text-biru1 uppercase tracking-wider text-left flex items-start gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-oren2 shrink-0 mt-0.5"></span>
                                <span>Belum Dijawab (<span x-text="unansweredList.length"></span> Pertanyaan):</span>
                            </div>
                            <div class="space-y-1.5 text-left pl-3.5">
                                <template x-for="(item, index) in unansweredList.slice(0, 10)"
                                    :key="'unans-' + item.id">
                                    <button type="button" @click="navigateToItem(item)"
                                        class="w-full text-left px-3 py-2 text-xs font-semibold rounded-lg border border-oren2/30 bg-oren2/5 hover:bg-oren2 hover:text-white text-oren2 transition shadow-sm block truncate"
                                        :title="'Soal No. ' + item.no + ': ' + item.text">
                                        Soal No. <span x-text="item.no"></span>: <span x-text="item.text"></span>
                                    </button>
                                </template>
                                <div x-show="unansweredList.length > 10" class="text-xs font-bold text-biru3 pl-1">
                                    dan <span x-text="unansweredList.length - 10"></span> soal lainnya...
                                </div>
                            </div>
                        </div>

                        <div x-show="galatList.length > 0" class="space-y-2">
                            <div
                                class="text-xs font-extrabold text-biru1 uppercase tracking-wider text-left flex items-start gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-merah1 shrink-0 mt-0.5"></span>
                                <span>Galat Bukti Dukung Kosong atau Tidak Valid (<span
                                        x-text="galatList.length"></span>
                                    Pertanyaan):</span>
                            </div>
                            <div class="space-y-1.5 text-left pl-3.5">
                                <template x-for="(item, index) in galatList.slice(0, 10)" :key="'galat-' + item.id">
                                    <button type="button" @click="navigateToItem(item)"
                                        class="w-full text-left px-3 py-2 text-xs font-semibold rounded-lg border border-merah1/30 bg-merah1/5 hover:bg-merah1 hover:text-white text-merah1 transition shadow-sm block truncate"
                                        :title="'Soal No. ' + item.no + ': ' + item.text">
                                        Soal No. <span x-text="item.no"></span>: <span x-text="item.text"></span>
                                    </button>
                                </template>
                                <div x-show="galatList.length > 10" class="text-xs font-bold text-biru3 pl-1">
                                    dan <span x-text="galatList.length - 10"></span> soal lainnya...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 pb-5 pt-4 border-t border-abubiru/30 bg-white">
                    <button type="button" @click="showUnansweredModal = false"
                        class="px-5 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru2 rounded-xl text-sm font-bold transition shadow-sm w-full">
                        Kembali ke Pengisian
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

        {{-- Modal Import Jawaban SAQ --}}
        <x-modals.import-jawaban-saq
            :action="route('operator.kues-saq.import')"
            :template-url="route('operator.kues-saq.template', ['id_periode' => $period->id_periode])"
            :submission="$submission"
            :indicators="$indicators"
        />

</x-layouts.app>
