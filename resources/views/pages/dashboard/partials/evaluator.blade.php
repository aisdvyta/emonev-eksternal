{{-- Welcome Banner --}}
<x-ui.welcome-banner :name="Auth::user()->nama_user">
    @if ($role === 'super_evaluator')
        <strong>Panel Kontrol Super Evaluator</strong> - Kelola seluruh periode, kuesioner, SK, panduan,
        pengguna evaluator/operator, serta pantau dan petakan evaluator untuk setiap kabupaten/kota.
    @else
        <strong>Panel Kontrol Evaluator Provinsi</strong> - Pantau dan evaluasi seluruh kabupaten/kota dalam penilaian
        keterbukaan informasi publik Jawa Timur.
    @endif
</x-ui.welcome-banner>

@if (!$period)
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-6 text-amber-700 text-sm font-semibold">
        Tidak ada periode evaluasi aktif saat ini.
    </div>
@else
    @php
        $saqStart = $period->saq_start ? \Carbon\Carbon::parse($period->saq_start) : null;
        $saqEnd = $period->saq_end ? \Carbon\Carbon::parse($period->saq_end) : null;
        $saqDateRange =
            $saqStart && $saqEnd
                ? $saqStart->translatedFormat('d M Y') . ' - ' . $saqEnd->translatedFormat('d M Y')
                : 'Belum diatur';

        $evalStart = $period->eval_start ? \Carbon\Carbon::parse($period->eval_start) : null;
        $evalEnd = $period->eval_end ? \Carbon\Carbon::parse($period->eval_end) : null;
        $evalDateRange =
            $evalStart && $evalEnd
                ? $evalStart->translatedFormat('d M Y') . ' - ' . $evalEnd->translatedFormat('d M Y')
                : 'Belum diatur';

        $visitStart = $period->visit_start ? \Carbon\Carbon::parse($period->visit_start) : null;
        $visitEnd = $period->visit_end ? \Carbon\Carbon::parse($period->visit_end) : null;
        $visitDateRange =
            $visitStart && $visitEnd
                ? $visitStart->translatedFormat('d M Y') . ' - ' . $visitEnd->translatedFormat('d M Y')
                : 'Belum diatur';

        $interviewStart = $period->interview_start ? \Carbon\Carbon::parse($period->interview_start) : null;
        $interviewEnd = $period->interview_end ? \Carbon\Carbon::parse($period->interview_end) : null;
        $interviewDateRange =
            $interviewStart && $interviewEnd
                ? $interviewStart->translatedFormat('d M Y') . ' - ' . $interviewEnd->translatedFormat('d M Y')
                : 'Belum diatur';

        $resultStart = $period->result_start ? \Carbon\Carbon::parse($period->result_start) : null;
        $resultEnd = $period->result_end ? \Carbon\Carbon::parse($period->result_end) : null;
        $resultDateRange =
            $resultStart && $resultEnd
                ? $resultStart->translatedFormat('d M Y') . ' - ' . $resultEnd->translatedFormat('d M Y')
                : 'Belum diatur';

        $dashStageOrder = \App\Models\MasterPembobotan::getActiveStageOrder($period?->id_periode);
        $stepMap = [
            'saq' => [
                [
                    'key' => 'saq_fill',
                    'label' => 'Pengisian Kuesioner',
                    'icon' =>
                        'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
                    'date' => $saqDateRange === 'Belum diatur' ? 'Menunggu Jadwal' : $saqDateRange,
                    'actionUrl' => route('evaluasi.saq.monitoring'),
                    'actionLabel' => 'Monitor SAQ',
                ],
                [
                    'key' => 'saq_eval',
                    'label' => 'Evaluasi Data',
                    'icon' =>
                        'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'date' => $evalDateRange === 'Belum diatur' ? 'Menunggu Jadwal' : $evalDateRange,
                    'actionUrl' => route('evaluasi.saq.monitoring'),
                    'actionLabel' => 'Evaluasi',
                ],
            ],
            'visitasi' => [
                [
                    'key' => 'visit',
                    'label' => 'Visitasi Lapangan',
                    'icon' =>
                        'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                    'date' => $visitDateRange === 'Belum diatur' ? 'Menunggu Jadwal' : $visitDateRange,
                    'actionUrl' => route('evaluasi.visitasi.monitoring'),
                    'actionLabel' => 'Visitasi',
                ],
            ],
            'wawancara' => [
                [
                    'key' => 'interview',
                    'label' => 'Wawancara',
                    'icon' =>
                        'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'date' => $interviewDateRange === 'Belum diatur' ? 'Menunggu Jadwal' : $interviewDateRange,
                    'actionUrl' => route('evaluasi.wawancara.monitoring'),
                    'actionLabel' => 'Wawancara',
                ],
            ],
        ];

        $rawSteps = [];
        foreach ($dashStageOrder as $stgKey) {
            if (isset($stepMap[$stgKey])) {
                foreach ($stepMap[$stgKey] as $stepItem) {
                    $rawSteps[] = $stepItem;
                }
            }
        }
        $rawSteps[] = [
            'key' => 'result',
            'label' => 'Penilaian Akhir',
            'icon' =>
                'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0',
            'date' => $resultDateRange === 'Belum diatur' ? 'Menunggu Jadwal' : $resultDateRange,
            'actionUrl' => route('evaluasi.penilaian.index'),
            'actionLabel' => 'Hasil Akhir',
        ];

        // Temukan index active stage
        $activeStageIndex = null;
        if ($phase === 'Selesai / Terpublikasi') {
            $activeStageIndex = count($rawSteps);
        } else {
            foreach ($rawSteps as $idx => $stg) {
                if (
                    $stg['label'] === $phase ||
                    ($stg['key'] === 'saq_fill' && $phase === 'Pengisian Kuesioner') ||
                    ($stg['key'] === 'saq_eval' &&
                        in_array($phase, ['Evaluasi Kuesioner', 'Evaluasi Data', 'Evaluasi & Penilaian'])) ||
                    ($stg['key'] === 'visit' && $phase === 'Visitasi Lapangan') ||
                    ($stg['key'] === 'interview' &&
                        in_array($phase, ['Wawancara & Presentasi', 'Presentasi & Wawancara', 'Wawancara'])) ||
                    ($stg['key'] === 'result' && in_array($phase, ['Sidang Penilaian Akhir', 'Penilaian Akhir']))
                ) {
                    $activeStageIndex = $idx;
                    break;
                }
            }
        }
        if ($activeStageIndex === null) {
            $activeStageIndex = 0;
        }

        $stepperPercent = $progressPercent ?? 0;

        $steps = [];
        foreach ($rawSteps as $idx => $stg) {
            $isActive = $idx === $activeStageIndex && $phase !== 'Selesai / Terpublikasi';
            $isDone = $idx < $activeStageIndex || $phase === 'Selesai / Terpublikasi';

            $statusText = $isDone
                ? 'Selesai'
                : ($isActive
                    ? ($idx === 0
                        ? 'Sedang Berlangsung'
                        : 'Aktif')
                    : 'Belum Dimulai');
            $colorText = $isDone ? 'done' : ($isActive ? 'active' : 'pending');

            $steps[] = array_merge($stg, [
                'status' => $statusText,
                'color' => $colorText,
                'active' => $isActive,
                'done' => $isDone,
            ]);
        }
    @endphp

    <!-- Main Content Grid (70:30) -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-5 mb-5 items-stretch">
        <!-- Timeline Progress Section (70%) -->
        <section
            class="rounded-2xl bg-white border border-abubiru shadow-sm p-5 sm:p-6 lg:col-span-7 flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center justify-between mb-4 md:mb-5 gap-3">
                    <h2 class="text-lg sm:text-xl font-bold text-biru1">Progress Evaluasi Tahun {{ $period->tahun }}</h2>
                    <div class="flex items-center gap-2">
                        <div class="bg-biru5/60 px-3 py-1 rounded-full flex items-center shadow-xs">
                            <span class="w-2 h-2 rounded-full mr-2 shrink-0 animate-pulse-biru"></span>
                            <span class="text-xs font-bold text-biru1">
                                Fase Saat Ini: {{ $phase }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="relative px-0 sm:px-2">
                    <!-- Stepper Track & Active Progress Line (Desktop only) -->
                    <div class="absolute top-6 left-[10%] right-[10%] h-0.5 bg-abubiru hidden md:block">
                        <div class="h-full bg-biru4 transition-all duration-1000"
                            style="width: {{ $stepperPercent }}%;"></div>
                    </div>

                    <!-- Desktop Grid Stepper (sm: and above) -->
                    <div class="hidden sm:grid sm:grid-cols-3 md:grid-cols-5 gap-3 md:gap-4 relative z-10">
                        @foreach ($steps as $step)
                            <div class="flex flex-col items-center text-center group relative pb-0">
                                <!-- Circle Column -->
                                <div class="relative flex flex-col items-center shrink-0">
                                    <div @class([
                                        'w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center shadow-md group-hover:scale-110 transition-transform relative z-20',
                                        'bg-biru4 text-white shadow-xs' => $step['color'] === 'done',
                                        'bg-hijau text-white' => $step['color'] === 'active',
                                        'bg-white border-2 border-abubiru text-biru4/60 shadow-xs' =>
                                            $step['color'] === 'pending',
                                    ])>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="{{ $step['icon'] }}" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Compact height content wrapper with uniform flex gap -->
                                <div class="flex flex-col items-center w-full mt-2.5 gap-2.5 pl-0">
                                    <div @class([
                                        'font-bold text-xs sm:text-sm leading-snug',
                                        'text-biru1' => $step['color'] !== 'pending' || $step['active'],
                                        'text-biru3/80' => $step['color'] === 'pending' && !$step['active'],
                                    ])>{{ $step['label'] }}</div>
                                    <div>
                                        <span @class([
                                            'text-xs font-semibold px-2.5 py-1.5 rounded-full tracking-tight',
                                            'text-biru3 bg-abubiru/60' => $step['color'] === 'done',
                                            'text-white bg-hijau font-bold px-2.5 shadow-xs' =>
                                                $step['color'] === 'active',
                                            'text-biru4 bg-abubiru' => $step['color'] === 'pending',
                                        ])>
                                            {{ $step['status'] }}
                                        </span>
                                    </div>
                                    <div
                                        class="text-xs font-semibold tracking-tight text-biru2 max-w-36 text-center">
                                        @if ($step['date'] === 'Menunggu Jadwal')
                                            <span class="italic font-medium text-biru3/80">Menunggu Jadwal</span>
                                        @else
                                            {{ $step['date'] }}
                                        @endif
                                    </div>

                                    @if ($step['actionUrl'])
                                        @if (in_array($step['color'], ['pending', 'slate']))
                                            <button type="button" disabled
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg border border-abubiru bg-putihbg text-biru3/60 text-center shadow-xs w-full max-w-31 flex items-center justify-center gap-1 mt-1 cursor-not-allowed opacity-70">
                                                {{ $step['actionLabel'] }} <span class="text-xs">&gt;</span>
                                            </button>
                                        @else
                                            <a href="{{ $step['actionUrl'] }}" wire:navigate.hover
                                                @class([
                                                    'px-2.5 py-1 text-xs font-bold rounded-lg border text-center transition shadow-xs w-full max-w-31 flex items-center justify-center gap-1 mt-1',
                                                    'border-biru4/50 bg-white hover:bg-birumuda/50 text-biru2' =>
                                                        in_array($step['color'], ['done', 'success']),
                                                    'border-hijau/50 bg-hijau/10 hover:bg-hijau/20 text-hijau' =>
                                                        in_array($step['color'], ['active', 'info']),
                                                    'border-merah1/50 bg-merah1/10 hover:bg-merah1/20 text-merah1' =>
                                                        in_array($step['color'], ['danger', 'merah']),
                                                ])>
                                                {{ $step['actionLabel'] }} <span class="text-xs">&gt;</span>
                                            </a>
                                        @endif
                                    @endif

                                    {{-- Khusus step Evaluasi Data: info lolos --}}
                                    @if ($step['label'] === 'Evaluasi Data' && $totalEvaluated > 0)
                                        <div class="-mt-1.5 flex flex-col items-center leading-tight">
                                            <span class="text-xs font-bold text-center text-oren2">
                                                {{ $lolosCount }}/{{ $totalKabkot }} Lolos Visitasi
                                            </span>
                                            @if ($gugurCount > 0)
                                                <span class="text-[11px] font-semibold text-center text-biru3">
                                                    {{ $gugurCount }} Gugur (<80)
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Khusus step Visitasi: info jadwal & penilaian --}}
                                    @if ($step['label'] === 'Visitasi Lapangan' && $lolosCount > 0)
                                        <div class="-mt-1.5 flex flex-col items-center leading-tight">
                                            <span class="text-xs font-bold text-center text-biru2">
                                                {{ $visitasiScheduled }}/{{ $lolosCount }} Dijadwalkan
                                            </span>
                                            <span class="text-[11px] font-semibold text-center text-biru3">
                                                {{ $visitasiEvaluated }}/{{ $lolosCount }} Sudah Dinilai
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Mobile Stacked Stepper (xs only) -->
                    <div class="sm:hidden space-y-4">
                        @foreach ($steps as $step)
                            <div class="flex items-start gap-3.5 p-3 rounded-2xl bg-slate-50/70 border border-abubiru">
                                <div @class([
                                    'w-10 h-10 rounded-full flex items-center justify-center shrink-0 shadow-xs mt-0.5',
                                    'bg-biru4 text-white' => $step['color'] === 'done',
                                    'bg-hijau text-white' => $step['color'] === 'active',
                                    'bg-white border-2 border-abubiru text-biru4/60' =>
                                        $step['color'] === 'pending',
                                ])>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $step['icon'] }}" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <span class="font-bold text-sm text-biru1">{{ $step['label'] }}</span>
                                        <span @class([
                                            'text-xs font-bold px-2 py-0.5 rounded-full shrink-0',
                                            'text-biru3 bg-abubiru/60' => $step['color'] === 'done',
                                            'text-white bg-hijau' => $step['color'] === 'active',
                                            'text-biru3 bg-abubiru' => $step['color'] === 'pending',
                                        ])>
                                            {{ $step['status'] }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-biru3 font-medium mb-2">{{ $step['date'] }}</div>
                                    @if ($step['actionUrl'])
                                        @if (in_array($step['color'], ['pending', 'slate']))
                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-biru3/60 cursor-not-allowed opacity-70">
                                                <span>{{ $step['actionLabel'] }}</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </span>
                                        @else
                                            <a href="{{ $step['actionUrl'] }}" wire:navigate.hover
                                                class="inline-flex items-center gap-1 text-xs font-bold text-biru2 hover:underline">
                                                <span>{{ $step['actionLabel'] }}</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div
                class="mt-4 pt-3 border-t border-abubiru/80 flex flex-wrap items-center justify-between gap-2 text-xs text-biru3 font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="text-biru2 font-bold">Keterangan Fase:</span>
                </div>
                <div class="flex flex-wrap items-center gap-3 sm:gap-6">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-biru4 shrink-0"></span>
                        <span>Periode Berakhir</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-hijau shrink-0"></span>
                        <span>Fase Aktif (Sedang Berlangsung)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-white border border-abubiru shrink-0"></span>
                        <span>Belum Dimulai</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Agenda Card (30%) -->
        <div
            class="rounded-2xl bg-white border border-abubiru shadow-sm p-5 sm:p-6 flex flex-col relative overflow-hidden group lg:col-span-3 justify-between">
            <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-biru4/5 rounded-full blur-2xl group-hover:bg-biru4/10 transition-all">
            </div>
            <div>
                <div class="flex items-center gap-3 text-biru1 font-bold text-base sm:text-lg mb-4">
                    <span>Agenda Mendatang</span>
                </div>
                <div class="space-y-2.5">
                    @forelse ($agenda as $item)
                        <a href="{{ $item['url'] ?? '#' }}"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-putihbg border border-abubiru hover:bg-abubiru/20 hover:border-biru2/30 transition duration-200 cursor-pointer">
                            <div class="text-center shrink-0 w-12">
                                <div class="text-xs font-bold text-biru4 uppercase">{{ $item['day'] }}</div>
                                <div class="text-sm font-black text-biru1">{{ $item['date'] }}</div>
                            </div>
                            <div class="w-0.5 h-7 bg-abubiru shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs sm:text-sm font-bold text-biru1 truncate">{{ $item['event'] }}
                                </div>
                            </div>
                            <div class="w-2 h-2 rounded-full bg-{{ $item['color'] }} shrink-0"></div>
                        </a>
                    @empty
                        <div class="text-center py-8 text-xs sm:text-sm font-bold text-biru3">
                            Tidak ada agenda terdekat.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
