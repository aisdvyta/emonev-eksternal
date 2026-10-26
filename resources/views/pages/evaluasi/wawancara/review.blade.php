@php
    $currentUser = Auth::user();
    $userRole = $currentUser?->role?->nama_role;
    $isSuperEval = $userRole === 'super_evaluator';
    $isPenilaiEksternal = $userRole === 'penilai_eksternal';

    $monitoringRoute = Route::has('evaluasi.wawancara.monitoring')
        ? route('evaluasi.wawancara.monitoring')
        : (Route::has('evaluator.monitor-wawancara') ? route('evaluator.monitor-wawancara') : '#');

    $saveRoute = Route::has('evaluasi.wawancara.review.save')
        ? route('evaluasi.wawancara.review.save', \App\Helpers\SecureId::encrypt($interview->id_interview))
        : ($isPenilaiEksternal
            ? route('penilai-eksternal.review-wawancara.save', \App\Helpers\SecureId::encrypt($interview->id_interview))
            : route('evaluator.review-wawancara.save', \App\Helpers\SecureId::encrypt($interview->id_interview)));

    // Cek apakah tahapan wawancara sudah dimulai
    $interviewStart = $interview->period?->interview_start ? \Carbon\Carbon::parse($interview->period->interview_start)->startOfDay() : null;
    $isBeforeInterviewStart = $interviewStart ? now()->startOfDay()->lt($interviewStart) : false;

    // Cek apakah tanggal dan waktu wawancara sudah tiba
    $interviewDateTime = $interview->interview_at ? \Carbon\Carbon::parse($interview->interview_at)->startOfDay() : null;
    $canScoreNow = !$isBeforeInterviewStart && ($interviewDateTime && now()->startOfDay()->gte($interviewDateTime));

    // Cek deadline tahapan
    $periodeEnd = $interview->period?->interview_end ? \Carbon\Carbon::parse($interview->period->interview_end)->endOfDay() : null;
    $isPastDeadline = $periodeEnd ? now()->greaterThan($periodeEnd) : false;

    // Status submission & finalisasi
    $submissionStatus = $interview->submission?->status ?? 5;
    $isStagePassed = $submissionStatus >= 7;
    $isFinalized = $interview->interview_score !== null;

    // Internal interviewer checks
    $isInterviewer1 = $currentUser->id_user == $interview->interviewer_1_id;
    $isInterviewer2 = $currentUser->id_user == $interview->interviewer_2_id;
    $isInterviewer3 = $currentUser->id_user == $interview->interviewer_3_id;

    // External interviewer check
    $interviewData = $interview->interview_data ?? [];
    $isMyExternal = collect($interviewData)->contains(function ($item) use ($currentUser) {
        return !empty($item['is_external']) && isset($item['id_user']) && (string)$item['id_user'] === (string)$currentUser->id_user;
    });

    $isAssigned = $isInterviewer1 || $isInterviewer2 || $isInterviewer3 || $isMyExternal;

    // Can current user edit their own internal score?
    $canEditInternal = $isAssigned && !$isPenilaiEksternal && $canScoreNow && !$isPastDeadline && !$isFinalized && !$isStagePassed;

    // Can current user edit external fields? (Super evaluator or the assigned external evaluator)
    $canEditExternalAsSuper = $isSuperEval && !$isBeforeInterviewStart && !$isPastDeadline && !$isFinalized && !$isStagePassed;
    $canEditExternalAsSelf = $isPenilaiEksternal && $isMyExternal && $canScoreNow && !$isPastDeadline && !$isFinalized && !$isStagePassed;
    $canEditAny = $canEditInternal || $canEditExternalAsSuper || $canEditExternalAsSelf;

    // Cek status penilaian evaluator ini sendiri
    $myScore = null;
    $myIsDraft = false;
    if ($isInterviewer1) {
        $myScore = $interview->score_interviewer_1;
        $myIsDraft = $interview->is_draft_1;
    } elseif ($isInterviewer2) {
        $myScore = $interview->score_interviewer_2;
        $myIsDraft = $interview->is_draft_2;
    } elseif ($isInterviewer3) {
        $myScore = $interview->score_interviewer_3;
        $myIsDraft = $interview->is_draft_3;
    } elseif ($isMyExternal) {
        $myExtEntry = collect($interviewData)->first(fn($item) => (string)($item['id_user'] ?? '') === (string)$currentUser->id_user);
        $myScore = $myExtEntry['score'] ?? null;
        $myIsDraft = $myExtEntry['is_draft'] ?? false;
    }
    $iAlreadyScored = $myScore !== null && !$myIsDraft;

    $satkerName = $interview->submission?->user?->masterSatker?->nama_satker ?? 'BPS Kab/Kota';
    $tahunPeriode = $interview->period?->tahun ?? date('Y');
@endphp

<x-layouts.app title="Review Wawancara - HaloKIP">
    <x-ui.page-header :breadcrumb="[
        'Monitoring Kegiatan' => $monitoringRoute,
        'Wawancara' => $monitoringRoute,
        'Review' => '#',
    ]">
    </x-ui.page-header>

    <!-- Hero Banner -->
    <section
        class="rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8">
        <div
            class="absolute right-10 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block">
        </div>
        <div class="absolute right-50 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block">
        </div>
        <div class="absolute right-30 bottom-2 hidden lg:block z-10">
            <img src="{{ asset('images/orangVisitasi.svg') }}" alt="Tahap Wawancara" class="h-60 w-auto object-contain">
        </div>
        <div class="relative z-10 text-left">
            <h2 class="text-3xl md:text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Evaluasi Wawancara</h2>
            <h3 class="text-2xl md:text-2xl font-semibold text-white leading-tight mb-4 tracking-tight">
                {{ $satkerName }} - Tahun {{ $tahunPeriode }}
            </h3>
            <p class="text-base leading-relaxed text-white font-normal max-w-xl">
                Nilai wawancara diberikan oleh tim pewawancara. Nilai akhir wawancara diperoleh dari rata-rata seluruh nilai.
            </p>
        </div>
    </section>

    <!-- Alert Messages -->
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

    <!-- Status / Periode Notice Banner -->
    @if ($isPastDeadline || $isStagePassed)
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold shadow-sm text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Batas waktu pengisian/pengeditan nilai wawancara periode ini telah berakhir{{ $periodeEnd ? ' pada tanggal ' . $periodeEnd->translatedFormat('d F Y') : '' }}. Nilai sudah tidak dapat diubah lagi.</span>
        </div>
    @elseif (!$canScoreNow)
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm font-semibold shadow-sm text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                Penilaian wawancara hanya dapat dilakukan pada atau setelah jadwal wawancara
                <strong>{{ $interviewDateTime ? $interviewDateTime->translatedFormat('d F Y') : '-' }}</strong>.
                Saat ini form penilaian dikunci dan belum dapat diedit/disimpan karena belum waktunya.
            </span>
        </div>
    @elseif (!$isAssigned && !$isSuperEval)
        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-semibold shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Mode Baca (Read-Only): Anda dapat melihat informasi wawancara ini. Namun, Anda tidak dapat mengisi/mengubah nilai karena Anda belum ditugaskan sebagai Pewawancara untuk sesi ini.</span>
        </div>
    @elseif ($isFinalized)
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold shadow-sm text-center">
            ✓ Penilaian wawancara telah difinalisasi dengan nilai rata-rata <strong>{{ number_format($interview->interview_score, 2) }}</strong>. Tahapan penilaian selesai, nilai tidak dapat diubah lagi.
        </div>
    @endif

    <!-- Panduan Penilaian -->
    <div x-data="{ open: false }"
        class="rounded-xl bg-white border border-kuning1/30 overflow-hidden mb-6 shadow-sm transition-all duration-300">
        <button @click="open = !open"
            class="flex items-center justify-between w-full text-left focus:outline-none select-none bg-kuning2/15 p-4 transition-all duration-200"
            :class="open ? 'border-b border-kuning1/20' : ''">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-oren2 flex items-center justify-center text-white shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-oren2 font-extrabold text-base">Panduan Penilaian</span>
                    <span x-show="!open"
                        class="text-xs text-oren2/80 mt-0.5 font-medium transition-opacity duration-200">Klik untuk melihat detail panduan</span>
                </div>
            </div>
            <div class="text-oren2 w-7 h-7 rounded-lg hover:bg-oren2/10 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 transform transition-transform duration-300" :class="open ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>
        <div x-show="open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="p-5 pl-15 flex flex-col gap-2.5 bg-white text-left" style="display: none;">
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Berikan nilai dalam skala 0-100</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Catatan wajib diisi untuk memberikan feedback atas jalannya wawancara</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Setiap pewawancara hanya dapat merubah nilai miliknya sendiri</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Penilaian dapat dilakukan pada atau setelah jadwal wawancara berlangsung</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold text-left">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold text-left">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form Wawancara -->
    <form id="review-wawancara-form" method="POST" action="{{ $saveRoute }}"
        x-data="{
            isDirty: false,
            externalEvaluators: @js(collect($interview->interview_data)->filter(fn($item) => isset($item['is_external']) && $item['is_external'])->values()->map(function($item) {
                $userId = $item['id_user'] ?? null;
                $userName = $item['name'] ?? '';
                if ($userId && empty($userName)) {
                    $u = \App\Models\User::find($userId);
                    $userName = $u?->name ?? 'Penilai Eksternal';
                }
                return [
                    'id_user' => $userId,
                    'name' => $userName,
                    'instansi' => $item['instansi'] ?? '',
                    'score' => $item['score'] ?? '',
                    'note' => $item['note'] ?? ''
                ];
            })->toArray()),
            init() {
                const form = this.$el;
                const triggerChange = () => {
                    this.isDirty = true;
                };
                form.addEventListener('input', triggerChange);
                form.addEventListener('change', triggerChange);

                const beforeUnloadListener = (event) => {
                    if (this.isDirty) {
                        event.preventDefault();
                        event.returnValue = '';
                        return '';
                    }
                };
                window.addEventListener('beforeunload', beforeUnloadListener);

                const livewireNavigateListener = (event) => {
                    if (this.isDirty) {
                        const confirmLeave = confirm('Jawaban Anda belum disimpan sebagai draft. Apakah Anda yakin ingin meninggalkan halaman ini?');
                        if (!confirmLeave) {
                            event.preventDefault();
                        } else {
                            this.isDirty = false;
                        }
                    }
                };
                document.addEventListener('livewire:navigate', livewireNavigateListener);

                form.addEventListener('submit', () => {
                    this.isDirty = false;
                });

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
            }
        }">
        @csrf

        <!-- Info Jadwal + Penilaian Wawancara -->
        <div class="flex flex-col lg:flex-row gap-6 mb-6 items-start">

            <!-- Kolom Kiri: Informasi Jadwal -->
            <div
                class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col lg:w-2/5 shrink-0 overflow-hidden">
                <div class="flex flex-col w-full text-left">
                    <!-- Header Card -->
                    <div class="bg-biru2 px-6 py-4 flex flex-col text-left">
                        <span class="text-white font-extrabold text-lg">Informasi Jadwal</span>
                        <span class="text-xs text-white/80">Berikut adalah jadwal wawancara dan informasi terkait</span>
                    </div>

                    <div class="p-6 flex flex-col w-full text-left">
                        <!-- Tanggal Wawancara -->
                        <div class="pb-3.5 flex items-center gap-3.5">
                            <div class="flex flex-col">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Tanggal Wawancara</span>
                                <span class="text-sm font-extrabold text-biru1 mt-0.5">
                                    {{ $interview->interview_at ? \Carbon\Carbon::parse($interview->interview_at)->translatedFormat('d F Y') : 'Belum Dijadwalkan' }}
                                </span>
                            </div>
                        </div>

                        <!-- Tim Pewawancara -->
                        <div class="pt-3.5 flex flex-col gap-2.5 border-t border-abubiru/40">
                            <div class="flex items-center gap-3.5">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Pewawancara</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                @php
                                    $allInterviewers = $interview ? $interview->interviewers : collect();
                                @endphp
                                @if($allInterviewers && count($allInterviewers) > 0)
                                    @foreach($allInterviewers as $ev)
                                        @php
                                            $evUser = $ev->user ?? null;
                                            $evId = $ev->id_user ?? null;
                                            $isMe = ($currentUser->id_user && $currentUser->id_user == $evId) || ($isMyExternal && isset($ev->is_external) && $ev->is_external);
                                            $isExt = isset($ev->is_external) && $ev->is_external;
                                        @endphp
                                        <div class="flex items-center justify-between text-xs font-bold text-biru1 bg-putihbg px-3.5 py-2 rounded-xl border border-abubiru/40">
                                            <div class="flex items-center gap-2">
                                                <span class="capitalize">{{ ucwords($evUser->name ?? ($ev->name ?? 'Pewawancara')) }}</span>
                                                @if($isMe)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-hijau/10 text-hijau text-xs font-bold">Anda</span>
                                                @endif
                                            </div>
                                            @if($isExt)
                                                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md">Penilai Eksternal</span>
                                            @else
                                                <span class="text-xs font-bold text-biru2 bg-biru5/50 px-2 py-0.5 rounded-md">{{ $evUser->role->nama_role ?? 'Evaluator' }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-xs font-medium text-biru4">-</span>
                                @endif
                            </div>
                        </div>

                        <!-- Link Meeting Wawancara -->
                        @if ($interview->meets_link)
                            <div class="pt-3.5 flex flex-col ">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru4 mb-2 border-t border-abubiru/40 pt-3">Tautan Virtual Meeting</span>
                                <p class="text-xs text-biru3 font-medium leading-relaxed mb-2.5">
                                    Tautan ruang virtual untuk pelaksanaan wawancara:
                                </p>
                                <div class="p-3 bg-putihbg rounded-xl border border-abubiru flex items-center justify-between gap-3">
                                    <a href="{{ $interview->meets_link }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 text-xs font-semibold text-biru2 hover:underline break-all">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        {{ $interview->meets_link }}
                                    </a>
                                    <a href="{{ $interview->meets_link }}" target="_blank" rel="noopener noreferrer"
                                        class="px-3 py-1.5 bg-biru2 hover:bg-biru1 text-white rounded-lg text-xs font-bold transition shrink-0 flex items-center gap-1">
                                        <span>Masuk</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Notif -->
                <div class="mx-5 mb-5 flex items-center gap-2 text-xs text-biru4 font-medium border-t border-abubiru/40 pt-3 text-left">
                    <svg class="w-4 h-4 text-biru4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pastikan kehadiran seluruh pewawancara pada waktu yang telah disepakati.</span>
                </div>
            </div>

            <!-- Kolom Kanan: Penilaian + Catatan Wawancara -->
            <div class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col flex-1 overflow-hidden">
                <!-- Header Card -->
                <div class="bg-biru2 px-6 py-4 flex flex-col text-left">
                    <span class="text-white font-extrabold text-lg">Penilaian & Catatan Evaluator</span>
                    <span class="text-xs text-white/80">Berikut adalah nilai wawancara dan catatan ulasan dari seluruh petugas pewawancara.</span>
                </div>

                @php
                    $interviewerData = [
                        [
                            'id' => $interview->interviewer_1_id,
                            'name' => $interview->interviewer1->name ?? 'Pewawancara 1',
                            'score' => $interview->score_interviewer_1,
                            'note' => $interview->note_interviewer_1,
                            'label' => 'Penilai 1',
                            'is_draft' => $interview->is_draft_1,
                        ],
                        [
                            'id' => $interview->interviewer_2_id,
                            'name' => $interview->interviewer2->name ?? 'Pewawancara 2',
                            'score' => $interview->score_interviewer_2,
                            'note' => $interview->note_interviewer_2,
                            'label' => 'Penilai 2',
                            'is_draft' => $interview->is_draft_2,
                        ],
                        [
                            'id' => $interview->interviewer_3_id,
                            'name' => $interview->interviewer3->name ?? 'Pewawancara 3',
                            'score' => $interview->score_interviewer_3,
                            'note' => $interview->note_interviewer_3,
                            'label' => 'Penilai 3',
                            'is_draft' => $interview->is_draft_3,
                        ],
                    ];
                @endphp

                <div class="p-6 flex flex-col gap-6 flex-1 justify-between text-left">
                    <div>
                        <!-- List 3 Evaluator Internal -->
                        <div class="flex flex-col gap-4">
                            @foreach ($interviewerData as $index => $data)
                                @php
                                    $isCurrent = $currentUser->id_user == $data['id'];
                                    $canEditThis = $isCurrent && $canEditInternal;
                                @endphp
                                <div
                                    class="p-5 rounded-2xl border transition-all duration-200 {{ $canEditThis ? 'bg-white border-biru4 shadow-sm' : 'bg-[#F4F7FC]/80 border-slate-200/80' }}">
                                    <!-- Header: Nama Pewawancara -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold uppercase tracking-wider {{ $canEditThis ? 'text-biru2' : 'text-slate-400' }}">
                                            {{ $data['label'] }}: <span class="font-extrabold {{ $canEditThis ? 'text-biru1' : 'text-slate-500' }} capitalize">{{ ucwords($data['name']) }}</span>
                                            @if ($data['is_draft'] && $data['score'] !== null)
                                                <span class="text-oren2 font-black text-xs ml-1">(Draft)</span>
                                            @endif
                                        </span>
                                        @if ($isCurrent)
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-hijau2/10 text-hijau text-xs font-bold uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-hijau animate-pulse"></span>
                                                Anda
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                                        <!-- NILAI -->
                                        <div class="flex flex-col items-center shrink-0 relative" x-data="{ errorState: false }">
                                            <input type="number"
                                                @if($canEditThis)
                                                    name="score"
                                                    required
                                                    @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()"
                                                    @input="
                                                        if (parseFloat($el.value) > 100) {
                                                            $el.value = $el.value.slice(0, -1);
                                                            errorState = true;
                                                        } else {
                                                            errorState = false;
                                                        }
                                                    "
                                                @else
                                                    disabled
                                                @endif
                                                value="{{ $data['score'] }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="-"
                                                class="w-24 h-14 text-center text-2xl font-extrabold rounded-xl border {{ $canEditThis ? 'border-biru4 bg-white text-biru1 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500 cursor-not-allowed select-none' }} transition" />
                                            <label class="text-xs font-bold uppercase tracking-wider mt-1.5 {{ $canEditThis ? 'text-biru4' : 'text-slate-400' }}">Nilai</label>
                                            <div x-show="errorState" x-cloak class="absolute mt-16 text-center w-40 text-xs font-bold text-red-500 bg-red-50 border border-red-200 rounded-lg px-2 py-1 shadow-sm z-50">
                                                Nilai max 100!
                                            </div>
                                        </div>

                                        <!-- CATATAN -->
                                        <div class="flex flex-col flex-1 min-w-0 w-full">
                                            <textarea
                                                @if($canEditThis)
                                                    name="note"
                                                    required
                                                    placeholder="Tuliskan catatan dan feedback wawancara di sini..."
                                                @else
                                                    disabled
                                                    placeholder="Belum memberikan catatan..."
                                                @endif
                                                rows="3"
                                                class="w-full rounded-xl border {{ $canEditThis ? 'border-abubiru bg-white text-biru1 placeholder-biru4/60 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500 placeholder-slate-300 cursor-not-allowed select-none' }} px-4 py-3 text-xs sm:text-sm font-medium resize-none transition leading-relaxed min-h-20">{{ $data['note'] }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @php
                            $extEvaluator = $interview->external_interviewer;
                            $hasExternal = !empty($extEvaluator) || collect($interview->interview_data ?? [])->contains('is_external', true);
                        @endphp

                        @if ($hasExternal)
                            <!-- Divider -->
                            <hr class="border-abubiru my-6">

                            <!-- Penilaian Eksternal Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-biru1 text-base">Penilaian Penilai Eksternal</h4>
                                    <p class="text-xs text-biru3 font-medium mt-0.5">Penilai eksternal yang ditugaskan pada saat penjadwalan wawancara</p>
                                </div>
                            </div>

                                <!-- External Evaluators List -->
                                <div class="flex flex-col gap-4 mb-4">
                                    <template x-for="(eval, index) in externalEvaluators" :key="index">
                                        <div class="p-5 rounded-2xl border transition-all duration-200 relative"
                                             :class="((eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }}) || {{ $canEditExternalAsSuper ? 'true' : 'false' }}) ? 'bg-white border-biru4 shadow-sm' : 'bg-[#F4F7FC]/80 border-slate-200/80'">
                                            <input type="hidden" :name="'external_evaluators[' + index + '][id_user]'" :value="eval.id_user">
                                            <input type="hidden" :name="'external_evaluators[' + index + '][name]'" :value="eval.name">
                                            <input type="hidden" :name="'external_evaluators[' + index + '][instansi]'" :value="eval.instansi">

                                            <!-- Header: Nama Penilai Eksternal -->
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold uppercase tracking-wider"
                                                      :class="((eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }}) || {{ $canEditExternalAsSuper ? 'true' : 'false' }}) ? 'text-biru2' : 'text-slate-400'">
                                                    Penilai Eksternal <span x-show="externalEvaluators.length > 1" x-text="index + 1"></span>: 
                                                    <span class="font-extrabold capitalize"
                                                          :class="((eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }}) || {{ $canEditExternalAsSuper ? 'true' : 'false' }}) ? 'text-biru1' : 'text-slate-500'"
                                                          x-text="eval.name || 'Penilai Eksternal'"></span>
                                                    <template x-if="eval.is_draft && eval.score !== null">
                                                        <span class="text-oren2 font-black text-xs ml-1">(Draft)</span>
                                                    </template>
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-bold uppercase tracking-wider"
                                                          x-text="eval.instansi || 'Penilai Eksternal'">
                                                    </span>
                                                    <template x-if="eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }}">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-hijau2/10 text-hijau text-xs font-bold uppercase tracking-wider">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-hijau animate-pulse"></span>
                                                            Anda
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row gap-4 items-start">
                                                <!-- NILAI -->
                                                <div class="flex flex-col items-center shrink-0 relative" x-data="{ errorState: false }">
                                                    <input type="number" :name="'external_evaluators[' + index + '][score]'" x-model="eval.score" min="0" max="100" step="0.01"
                                                           :disabled="!({{ $canEditExternalAsSuper ? 'true' : 'false' }} || (eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }}))"
                                                           placeholder="-"
                                                           @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()"
                                                           @input="
                                                               if (parseFloat($el.value) > 100) {
                                                                   $el.value = $el.value.slice(0, -1);
                                                                   eval.score = $el.value;
                                                                   errorState = true;
                                                               } else {
                                                                   errorState = false;
                                                               }
                                                           "
                                                           class="w-24 h-14 text-center text-2xl font-extrabold rounded-xl border transition"
                                                           :class="({{ $canEditExternalAsSuper ? 'true' : 'false' }} || (eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }})) ? 'border-biru4 bg-white text-biru1 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500 cursor-not-allowed select-none'" />
                                                    <label class="text-xs font-bold uppercase tracking-wider mt-1.5"
                                                           :class="({{ $canEditExternalAsSuper ? 'true' : 'false' }} || (eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }})) ? 'text-biru4' : 'text-slate-400'">Nilai</label>
                                                    <div x-show="errorState" x-cloak class="absolute mt-16 text-center w-40 text-xs font-bold text-red-500 bg-red-50 border border-red-200 rounded-lg px-2 py-1 shadow-sm z-50">
                                                        Nilai max 100!
                                                    </div>
                                                </div>

                                                <!-- CATATAN -->
                                                <div class="flex flex-col flex-1 min-w-0 w-full">
                                                    <textarea :name="'external_evaluators[' + index + '][note]'" x-model="eval.note"
                                                              :disabled="!({{ $canEditExternalAsSuper ? 'true' : 'false' }} || (eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }}))"
                                                              placeholder="Tuliskan catatan dan feedback wawancara di sini..."
                                                              rows="3"
                                                              class="w-full rounded-xl border px-4 py-3 text-xs sm:text-sm font-medium resize-none transition leading-relaxed min-h-20"
                                                              :class="({{ $canEditExternalAsSuper ? 'true' : 'false' }} || (eval.id_user && eval.id_user == {{ (int) $currentUser->id_user }} && {{ $canEditExternalAsSelf ? 'true' : 'false' }})) ? 'border-abubiru bg-white text-biru1 placeholder-biru4/60 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500 placeholder-slate-300 cursor-not-allowed select-none'"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                        @endif

                    </div>

                    <!-- Overall Average: tampil jika finalized atau nilai wawancara sudah tersimpan -->
                    @if ($interview->interview_score !== null)
                        <hr class="border-abubiru my-1 mt-4">

                        <div
                            class="rounded-xl bg-birumuda border border-biru5 px-6 py-5 flex items-center justify-between shadow-sm mt-4">
                            <div>
                                <div class="font-bold text-biru6 text-sm mb-0.5">Rata-rata Nilai Wawancara Final</div>
                                <p class="text-xs text-biru3">Semua penilai (internal & eksternal) telah memberikan nilai.</p>
                            </div>
                            <div class="text-3xl font-black text-biru1">
                                {{ number_format($interview->interview_score, 2) }}
                            </div>
                        </div>
                    @endif
                </div>

                @if ($isSuperEval && !$isAssigned)
                    <div
                        class="rounded-xl bg-blue-50 border border-blue-200 px-6 py-4 mx-6 mb-6 text-sm text-blue-700 font-semibold text-center">
                        Sebagai <strong>Super Evaluator</strong>, Anda dapat mengelola <strong>Penilaian Eksternal (Instansi Lain)</strong>. Nilai pewawancara internal hanya dapat diisi oleh masing-masing evaluator yang ditugaskan.
                    </div>
                @elseif (!$isAssigned && !$isSuperEval)
                    <div
                        class="rounded-xl bg-orange-50 border border-orange-200 px-6 py-4 mx-6 mb-6 text-sm text-orange-700 font-semibold text-center">
                        Anda berada dalam Mode Lihat-Saja (Read-only).
                    </div>
                @elseif ($isPastDeadline || $isStagePassed)
                    <div class="rounded-xl bg-rose-50 border border-rose-200 px-6 py-4 mx-6 mb-6 text-sm text-rose-700 font-semibold text-center">
                        Tahapan wawancara telah selesai{{ $periodeEnd ? ' pada tanggal ' . $periodeEnd->translatedFormat('d M Y') : '' }}. Nilai sudah tidak dapat diubah lagi.
                    </div>
                @elseif ($isFinalized)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-6 py-4 mx-6 mb-6 text-sm text-emerald-700 font-semibold text-center">
                        ✓ Penilaian wawancara telah difinalisasi. Nilai rata-rata:
                        <strong>{{ number_format($interview->interview_score, 2) }}</strong>. Tahapan penilaian selesai, nilai tidak dapat diubah lagi.
                    </div>
                @elseif ($iAlreadyScored)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-6 py-4 mx-6 mb-6 text-sm text-emerald-700 font-semibold text-center">
                        ✓ Anda telah men-submit penilaian wawancara. Menunggu pewawancara lainnya menyelesaikan penilaian.
                    </div>
                @elseif (!$canScoreNow)
                    <div class="rounded-xl bg-blue-50 border border-blue-200 px-6 py-4 mx-6 mb-6 text-sm text-blue-700 font-semibold text-center">
                        @if ($isBeforeInterviewStart)
                            🕐 Tahapan wawancara belum dibuka. Penilaian baru dapat dilakukan mulai tanggal
                            <strong>{{ $interviewStart ? $interviewStart->translatedFormat('d F Y') : '-' }}</strong>.
                        @elseif ($interviewDateTime)
                            🕐 Penilaian hanya dapat dilakukan pada atau setelah jadwal wawancara
                            <strong>{{ $interviewDateTime->translatedFormat('d F Y') }}</strong>.
                        @else
                            🕐 Penilaian baru dapat dilakukan pada atau setelah awal tahapan wawancara
                            <strong>{{ $interviewStart ? $interviewStart->translatedFormat('d F Y') : '-' }}</strong>.
                        @endif
                        Saat ini belum saatnya mengisi penilaian.
                    </div>
                @endif
            </div>

        </div>

        <!-- Action Buttons -->
        @if ($canEditAny)
            <div class="flex justify-end gap-3 mb-6">
                <button type="submit" form="review-wawancara-form" name="action" value="draft"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg border border-biru5 text-biru1 font-semibold bg-biru5/20 hover:bg-biru5/40 hover:shadow-sm active:scale-[0.98] transition shadow-sm text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    Simpan Draft
                </button>
                <button type="submit" form="review-wawancara-form" name="action" value="submit"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg bg-hijau hover:bg-emerald-600 hover:shadow-md hover:brightness-105 active:scale-[0.98] text-white font-semibold transition shadow-sm text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim Penilaian
                </button>
            </div>
        @endif
    </form>
</x-layouts.app>
