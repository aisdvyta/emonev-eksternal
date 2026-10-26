<x-layouts.app title="Wawancara - HaloKIP">
    @php
        $roleName = Auth::user()?->role?->nama_role;
        $isVerifikator = $roleName === 'verifikator';
        $homeRoute = $isVerifikator ? route('verifikator.index') : route('operator.index');
        $monitoringRoute = Route::has('evaluasi.wawancara.index') 
            ? route('evaluasi.wawancara.index') 
            : ($isVerifikator ? route('verifikator.monitoring-wawancara') : route('operator.wawancara'));
    @endphp
    <x-ui.page-header :breadcrumb="[
        'Beranda' => $homeRoute,
        'Monitoring Wawancara' => $monitoringRoute,
        'Detail Wawancara' => '#',
    ]">
    </x-ui.page-header>

    @php
        $isGugur = $isGugur ?? false;
        $hasScore = $interview && $submission->status >= 6;
        $hasSchedule = $interview && $interview->interview_at !== null;
        $interviewEnd = $period->interview_end ? \Carbon\Carbon::parse($period->interview_end)->endOfDay() : null;
        $isReleased = !$interviewEnd || now()->greaterThan($interviewEnd);
        $evalEnd = $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->endOfDay() : null;
        $isSaqReleased = !$evalEnd || now()->greaterThan($evalEnd);

        $satkerName = $submission->user->masterSatker->nama_satker ?? (Auth::user()->masterSatker->nama_satker ?? Auth::user()->nama_user);

        if ($isGugur && !$interview) {
            $heroTitle = 'Gugur KKM SAQ';
            $heroDesc =
                'Nilai SAQ unit kerja Anda tidak memenuhi KKM per indikator (>=80), sehingga tidak melanjutkan ke tahap Wawancara pada periode ini.';
        } elseif ($hasScore) {
            $heroTitle = 'Wawancara Selesai';
            $heroDesc =
                'Sesi wawancara dengan tim interviewer provinsi telah selesai dilaksanakan. Nilai sedang direkapitulasi untuk penilaian akhir.';
        } elseif ($hasSchedule) {
            $heroTitle = 'Terjadwal: Sesi Wawancara';
            $heroDesc =
                'Sesi wawancara dijadwalkan pada ' .
                \Carbon\Carbon::parse($interview->interview_at)->translatedFormat('l, d F Y') .
                '.';
        } else {
            $heroTitle = 'Menunggu Jadwal Wawancara';
            $heroDesc =
                'Sesi wawancara sedang dijadwalkan oleh tim evaluator BPS Provinsi Jawa Timur.';
        }
    @endphp

    <!-- Hero Banner -->
    <section
        class="rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8">
        <div
            class="absolute right-10 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block">
        </div>
        <div
            class="absolute right-50 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block">
        </div>
        <div class="absolute right-30 bottom-2 hidden lg:block z-10">
            <img src="{{ asset('images/orangVisitasi.svg') }}" alt="Tahap Wawancara" class="h-60 w-auto object-contain">
        </div>

        <div class="relative z-10 text-left">
            <h2 class="text-3xl md:text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Evaluasi
                Wawancara</h2>
            <div class="text-2xl md:text-2xl font-semibold text-white leading-tight mb-4 tracking-tight">
                {{ $satkerName }} - Tahun {{ $period->tahun }}
            </div>
            <p class="text-base leading-relaxed text-white font-normal max-w-xl">
                <span class="text-kuning2 font-bold">{{ $heroTitle }}.</span> {{ $heroDesc }}
            </p>
        </div>
    </section>

    @if ($isGugur)
        <!-- Tidak Lolos Threshold SAQ -->
        <div class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col justify-between overflow-hidden">
            <div class="bg-red-600 px-6 py-4 flex flex-row gap-4 items-center">
                <svg class="w-8 h-8 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex flex-col text-left">
                    <span class="text-white font-extrabold text-lg">Tidak Lolos KKM SAQ</span>
                    <span class="text-xs text-white/80">Tidak melanjutkan ke tahap Wawancara</span>
                </div>
            </div>
            <div class="p-8 flex flex-col items-center text-center gap-4">
                <div class="w-20 h-20 rounded-full bg-red-50 border-2 border-red-200 flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-black text-red-700 mb-2">Gugur pada Tahap SAQ</h3>
                    <p class="text-sm text-biru3 max-w-md leading-relaxed">
                        Nilai SAQ unit kerja Anda pada periode <strong>{{ $period->nama }}</strong> tidak memenuhi batas minimum
                        KKM per indikator (>=80). Oleh karena itu, proses Wawancara
                        tidak dilaksanakan untuk periode ini.
                    </p>
                </div>
                <div
                    class="mt-2 p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700 font-semibold max-w-md">
                    Nilai SAQ Unit Kerja: <span class="text-2xl font-black">
                        @if ($isSaqReleased && $submission->result?->ques_score !== null)
                            {{ number_format($submission->result->ques_score, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @elseif (!$hasSchedule)
        <!-- Menunggu Jadwal Wawancara -->
        <div class="rounded-xl bg-white border border-abubiru p-8 text-center shadow-sm">
            <div
                class="w-16 h-16 rounded-full bg-biru5/40 text-biru1 flex items-center justify-center mx-auto mb-4 border border-biru5/60">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-biru1 mb-1">Jadwal Sesi Wawancara Belum Ditentukan</h3>
            <p class="text-sm text-biru3 max-w-md mx-auto leading-relaxed">
                Tim Evaluator BPS Provinsi Jawa Timur sedang mengatur tanggal dan waktu pelaksanaan wawancara untuk unit kerja Anda.
                Halaman ini akan diperbarui otomatis begitu jadwal ditetapkan.
            </p>
        </div>
    @else
        <!-- Layout 2 Kolom: Info Jadwal (Kiri) | Penilaian & Catatan Evaluator (Kanan) -->
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
                                    {{ $interview && $interview->interview_at ? \Carbon\Carbon::parse($interview->interview_at)->translatedFormat('d F Y') : 'Belum Dijadwalkan' }}
                                </span>
                            </div>
                        </div>

                        <!-- Pewawancara -->
                        <div class="pt-3.5 flex flex-col gap-2.5 border-t border-abubiru/40">
                            <div class="flex items-center gap-3.5">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Pewawancara</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                @php
                                    $allInterviewers = $interview ? $interview->interviewers : collect();
                                @endphp
                                @if ($allInterviewers && count($allInterviewers) > 0)
                                    @foreach ($allInterviewers as $ev)
                                        <div class="flex items-center justify-between text-xs font-bold text-biru1 bg-putihbg px-3.5 py-2 rounded-xl border border-abubiru/40">
                                            <span class="capitalize">{{ ucwords($ev->user->name ?? ($ev->name ?? 'Evaluator')) }}</span>
                                            <span class="text-xs font-bold text-biru2 bg-biru5/50 px-2 py-0.5 rounded-md">
                                                {{ isset($ev->is_external) && $ev->is_external ? (!empty($ev->instansi) ? $ev->instansi : 'Eksternal') : ($ev->user->role->nama_role ?? 'Evaluator') }}
                                            </span>
                                        </div>
                                    @endforeach
                                @elseif ($interview && ($interview->interviewer1 || $interview->interviewer2 || $interview->interviewer3))
                                    @foreach ([$interview->interviewer1 ?? null, $interview->interviewer2 ?? null, $interview->interviewer3 ?? null] as $ev)
                                        @if ($ev)
                                            <div class="flex items-center justify-between text-xs font-bold text-biru1 bg-putihbg px-3.5 py-2 rounded-xl border border-abubiru/40">
                                                <span class="capitalize">{{ ucwords($ev->name) }}</span>
                                                <span class="text-xs font-bold text-biru2 bg-biru5/50 px-2 py-0.5 rounded-md">{{ $ev->role->nama_role ?? 'Evaluator' }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="text-xs font-medium text-biru4">-</span>
                                @endif
                            </div>
                        </div>

                        <!-- Link Meeting Wawancara -->
                        @if ($interview && $interview->meets_link)
                            <div class="pt-3.5 flex flex-col">
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
                <div class="mx-5 mb-5 flex items-center gap-2 text-xs text-biru4 font-medium border-t border-abubiru/40 pt-3">
                    <svg class="w-4 h-4 text-biru4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pastikan kehadiran seluruh perwakilan satker pada waktu yang telah disepakati.</span>
                </div>
            </div>

            <!-- Kolom Kanan: Penilaian & Catatan Evaluator -->
            <div
                class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col flex-1 text-left overflow-hidden">
                <!-- Header Card -->
                <div class="bg-biru2 px-6 py-4 flex flex-col text-left">
                    <span class="text-white font-extrabold text-lg">Penilaian & Catatan Evaluator</span>
                    <span class="text-xs text-white/80">Berikut adalah rekapitulasi nilai dan catatan ulasan dari tim pewawancara.</span>
                </div>

                @if ($isReleased)
                    <div class="p-6 flex flex-col gap-6 flex-1 justify-between">
                        @php
                            if ($interview) {
                                $interviewerData = [];
                                $idx = 1;
                                $allInterviewers = $interview->interviewers ?? collect();
                                if ($allInterviewers && count($allInterviewers) > 0) {
                                    foreach ($allInterviewers as $ev) {
                                        $interviewerData[] = [
                                            'name' => $ev->user->name ?? ($ev->name ?? 'Pewawancara'),
                                            'score' => $ev->score,
                                            'note' => $ev->note,
                                            'label' =>
                                                isset($ev->is_external) && $ev->is_external
                                                    ? 'Penilai Eksternal'
                                                    : 'Penilai ' . $idx++,
                                            'role' =>
                                                isset($ev->is_external) && $ev->is_external
                                                    ? (!empty($ev->instansi) ? $ev->instansi : 'Penilai Eksternal')
                                                    : $ev->user->role->nama_role ?? 'Evaluator',
                                        ];
                                    }
                                } else {
                                    foreach ([1, 2, 3] as $i) {
                                        $evModel = $interview->{'interviewer' . $i} ?? null;
                                        $interviewerData[] = [
                                            'name' => $evModel->name ?? ('Pewawancara ' . $i),
                                            'score' => $interview->{'score_interviewer_' . $i} ?? null,
                                            'note' => $interview->{'note_interviewer_' . $i} ?? null,
                                            'label' => 'Penilai ' . $i,
                                            'role' => $evModel->role->nama_role ?? 'Evaluator',
                                        ];
                                    }
                                }

                                foreach ($interviewerData as &$data) {
                                    $noteCol = $data['note'];
                                    $data['is_draft'] = str_starts_with($noteCol ?? '', '[DRAFT]');
                                    if (str_starts_with($noteCol ?? '', '[DRAFT] ')) {
                                        $data['note'] = substr($noteCol, 8);
                                    } elseif ($noteCol === '[DRAFT]') {
                                        $data['note'] = '';
                                    }
                                }
                                unset($data);

                                $nilaiSudahLengkap = $interview->interview_score !== null;
                            } else {
                                $interviewerData = [];
                                $nilaiSudahLengkap = false;
                            }
                        @endphp

                        <div>
                            <!-- List Evaluator Cards (Read-only) -->
                            <div class="flex flex-col gap-4">
                                @foreach ($interviewerData as $data)
                                    <div
                                        class="p-5 rounded-2xl border transition-all duration-200 bg-[#F4F7FC]/80 border-slate-200/80">
                                        <!-- Header: Nama Pewawancara -->
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                                {{ $data['label'] }}: <span class="font-extrabold text-slate-500 capitalize">{{ ucwords($data['name']) }}</span>
                                                @if (!empty($data['is_draft']) && $data['score'] !== null)
                                                    <span class="text-oren2 font-black text-xs ml-1">(Draft)</span>
                                                @endif
                                            </span>
                                            @if (!empty($data['role']) && (strtolower($data['role']) === 'penilai eksternal' || str_contains(strtolower($data['label']), 'eksternal')))
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-bold uppercase tracking-wider">
                                                    {{ $data['role'] ?: 'Penilai Eksternal' }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-4 items-start">
                                            <!-- Nilai Box -->
                                            <div class="flex flex-col items-center shrink-0">
                                                <div class="w-24 h-14 flex items-center justify-center text-center text-2xl font-extrabold rounded-xl border border-slate-200/80 bg-white text-slate-500">
                                                    {{ $data['score'] !== null ? number_format($data['score'], 2) : '-' }}
                                                </div>
                                                <label class="text-xs font-bold uppercase tracking-wider mt-1.5 text-slate-400">Nilai</label>
                                            </div>

                                            <!-- Catatan Box -->
                                            <div class="flex flex-col flex-1 min-w-0 w-full">
                                                <div class="w-full rounded-xl border border-slate-200/80 bg-white text-slate-500 px-4 py-3 text-xs sm:text-sm font-medium leading-relaxed min-h-20 {{ empty($data['note']) ? 'italic text-slate-400' : '' }}">
                                                    {{ !empty($data['note']) ? $data['note'] : 'Belum memberikan catatan...' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Overall Average: tampil jika nilai sudah lengkap -->
                        @if ($nilaiSudahLengkap)
                            <hr class="border-abubiru my-1 mt-4">
                            <div
                                class="rounded-xl bg-birumuda border border-biru5 px-6 py-5 flex items-center justify-between shadow-sm mt-4">
                                <div>
                                    <div class="font-bold text-biru6 text-sm mb-0.5">Rata-rata Nilai Wawancara Final
                                    </div>
                                    <p class="text-xs text-biru3">Semua penilai telah memberikan nilai.</p>
                                </div>
                                <div class="text-3xl font-black text-biru1">
                                    {{ number_format($interview->interview_score, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-6 flex flex-col items-center justify-center text-center gap-4 flex-1">
                        <div
                            class="w-14 h-14 rounded-full bg-blue-50 text-biru2 flex items-center justify-center shadow-inner mt-4">
                            <svg class="w-7 h-7 text-biru2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="max-w-md">
                            <h4 class="text-lg font-bold text-biru1 mb-1">Nilai Wawancara Belum Dirilis</h4>
                            <p class="text-sm text-biru3 font-medium leading-relaxed mb-4">
                                Nilai wawancara dan catatan evaluator sedang diproses. Nilai wawancara akan dirilis
                                secara serentak setelah masa wawancara berakhir pada tanggal
                                <strong>{{ $period->interview_end ? \Carbon\Carbon::parse($period->interview_end)->translatedFormat('d F Y') : '-' }}</strong>.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-layouts.app>
