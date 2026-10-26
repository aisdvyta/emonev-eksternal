@if (session('success'))
    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold shadow-sm">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-semibold shadow-sm">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
    <x-ui.welcome-banner :name="Auth::user()->nama_user" class="xl:col-span-2 mb-0 md:mb-0 h-full">
        <strong>Panel Kontrol Verifikator</strong> &mdash; Verifikasi kelengkapan dan keabsahan kuesioner yang telah diisi oleh operator sebelum diteruskan ke evaluator provinsi.
    </x-ui.welcome-banner>

    <!-- Kasubag Profile Card -->
    <div class="rounded-2xl bg-white border border-abubiru shadow-sm p-5 flex flex-col items-center justify-center text-center h-full">
        <div class="relative mb-4">
            @if (Auth::user()->foto_url || Auth::user()->profile_pic)
                <img src="{{ Auth::user()->foto_url ?? Auth::user()->profile_pic }}" alt="{{ Auth::user()->nama_user }}"
                    class="w-24 h-24 rounded-full object-cover shadow-md border-4 border-birumuda"
                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->nama_user) }}&background=004b87&color=ffffff';">
            @else
                <div class="w-24 h-24 rounded-full bg-biru2 text-white flex items-center justify-center font-bold text-3xl shadow-md border-4 border-birumuda">
                    {{ strtoupper(substr(Auth::user()->nama_user, 0, 1)) }}
                </div>
            @endif
            <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full bg-hijau border-2 border-white"></span>
        </div>
        <h3 class="font-bold text-biru1 text-lg mb-1">{{ Auth::user()->nama_user }}</h3>
        <p class="text-xs font-bold uppercase tracking-widest text-biru3 mb-3">{{ strtoupper(str_replace('_', ' ', Auth::user()->role->nama_role)) }}</p>
        <div class="w-full border-t border-abubiru/30 pt-4 flex flex-col gap-2 text-left">
            <div class="flex items-center justify-between text-sm">
                <span class="text-biru3">Nama User:</span>
                <span class="font-semibold text-biru1 font-mono">{{ Auth::user()->nama_user }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-biru3">Satker:</span>
                <span class="font-semibold text-biru1 text-right">{{ Auth::user()->masterSatker->nama_satker ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Section Header --}}
@if ($activePeriod)
    @php
        $tahun = \Carbon\Carbon::parse($activePeriod->periode_start)->year;
        $startFmt = \Carbon\Carbon::parse($activePeriod->periode_start)->translatedFormat('d M Y');
        $endFmt = \Carbon\Carbon::parse($activePeriod->periode_end)->translatedFormat('d M Y');

        if ($activePeriod->is_active) {
            $statusBadge = 'bg-hijau/10 text-hijau border-hijau/30';
            $statusLabel = 'Aktif';
        } elseif (now() < $activePeriod->periode_start) {
            $statusBadge = 'bg-biru5/20 text-biru2 border-biru5/40';
            $statusLabel = 'Akan Datang';
        } else {
            $statusBadge = 'bg-abubiru text-abu border-abumuda';
            $statusLabel = 'Selesai';
        }

        $activeSub = null;
        foreach ($operators as $op) {
            if ($op->submission) {
                $activeSub = $op->submission;
                break;
            }
        }

        $satkerProgress = 0;
        if ($operators->first() && isset($operators->first()->progress_percent)) {
            $satkerProgress = $operators->first()->progress_percent;
        }

        $statusText = 'Belum Mulai';
        $statusColorClass = 'bg-abubiru text-abu border-abumuda';

        if ($activeSub) {
            $isDraftVerif = ($activeSub->status === 1 && !empty($activeSub->verified_by));
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
            $statusText = $statusLabels[$activeSub->status] ?? 'Unknown';

            $statusColors = [
                0 => 'bg-kuning1/10 text-kuning1 border-kuning1/30',
                1 => $isDraftVerif ? 'bg-birumuda text-biru6 border-biru5/40' : 'bg-oren2/10 text-oren2 border-oren2/30',
                2 => 'bg-merah1/10 text-merah1 border-merah1/30',
                3 => 'bg-biru5/20 text-biru2 border-biru5/40',
                4 => 'bg-biru5/20 text-biru2 border-biru5/40',
                5 => 'bg-hijau/10 text-hijau border-hijau/30',
                6 => 'bg-hijau/10 text-hijau border-hijau/30',
                7 => 'bg-hijau/10 text-hijau border-hijau/30',
                8 => 'bg-merah1/10 text-merah1 border-merah1/30',
            ];
            $statusColorClass = $statusColors[$activeSub->status] ?? 'bg-abubiru text-abu border-abumuda';

            if ($activeSub->status === 0 && $satkerProgress === 0) {
                $statusText = 'Belum Mulai';
                $statusColorClass = 'bg-abubiru text-abu border-abumuda';
            }
        }

        $totalOps = $operators->count();
        $visibleOps = $operators->take(3);
        $remainingOpsCount = max(0, $totalOps - 3);

        if ($totalOps === 1) {
            $operatorNamesText = $operators->first()->nama_user;
            $operatorFormattedSubtext = '(' . $operators->first()->nama_user . ')';
        } elseif ($totalOps === 2) {
            $operatorNamesText = $operators->pluck('nama_user')->join(' & ');
            $operatorFormattedSubtext = '(' . $operators->pluck('nama_user')->join(', ') . ')';
        } elseif ($totalOps > 2) {
            $firstTwo = $operators->take(2)->pluck('nama_user')->join(', ');
            $operatorNamesText = $firstTwo . ' + ' . ($totalOps - 2) . ' lainnya';
            $operatorFormattedSubtext = '(' . $firstTwo . ', ...)';
        } else {
            $operatorNamesText = 'Belum ada operator';
            $operatorFormattedSubtext = '';
        }

        $allOperatorsTooltip = $operators->map(fn($u) => $u->nama_user . ($u->nip_panjang ? ' (NIP: ' . $u->nip_panjang . ')' : ''))->join("\n");
    @endphp

    <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 mb-4">
            {{-- Info Periode & Unified SAQ Progress --}}
            <div class="p-6 sm:p-7">
                {{-- Header Periode & Tombol Aksi (Sejajar) --}}
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 min-h-[42px]">
                            <span class="font-bold text-biru1 text-base sm:text-lg leading-tight">
                                {{ Str::startsWith(strtolower($activePeriod->nama), 'periode') ? $activePeriod->nama : 'Periode ' . $activePeriod->nama }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="text-xs text-biru4 font-medium mt-1">
                            {{ $startFmt }} &mdash; {{ $endFmt }}
                        </div>
                    </div>
                    
                    {{-- Tombol Lihat Pengisian / Verifikasi --}}
                    <div class="shrink-0 flex flex-col sm:items-end">
                        <div class="flex items-center min-h-[42px]">
                            @if ($activeSub)
                                @if ($activeSub->status === 1)
                                    <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($activeSub->id_submission)]) }}"
                                        wire:navigate.hover
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-biru2 text-white font-bold text-sm px-5 py-2.5 hover:bg-biru1 transition shadow-md hover:shadow-lg">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Verifikasi Pengajuan
                                    </a>
                                @else
                                    <a href="{{ route('verifikator.verifikasi-saq', ['submission' => \App\Helpers\SecureId::encrypt($activeSub->id_submission)]) }}"
                                        wire:navigate.hover
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-biru4 text-white font-bold text-sm px-5 py-2.5 hover:bg-biru1 transition shadow-xs hover:shadow-sm">
                                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Pengisian
                                    </a>
                                @endif
                            @else
                                <button disabled
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-abubiru border border-abubiru/60 text-biru4 font-bold text-sm px-5 py-2.5 cursor-not-allowed">
                                    Belum Ada Data
                                </button>
                            @endif
                        </div>
                        @if ($activeSub && $activeSub->status === 1 && $activeSub->submitted_at)
                            <span class="text-xs font-semibold text-biru4 text-center sm:text-right mt-1">
                                Dikirim: {{ \Carbon\Carbon::parse($activeSub->submitted_at)->translatedFormat('d M Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Unified Satker Progress Card --}}
                <div class="bg-putihbg rounded-2xl p-4 sm:p-5 border border-abubiru/50 shadow-xs">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-60" title="{{ $allOperatorsTooltip }}">
                            <div class="flex -space-x-2.5 overflow-hidden shrink-0">
                                @forelse ($visibleOps as $op)
                                    <div class="inline-flex h-9 w-9 rounded-full ring-2 ring-white bg-linear-to-br from-biru2 to-biru1 text-white font-black text-xs items-center justify-center shadow-xs cursor-pointer"
                                        title="{{ $op->nama_user }} (NIP: {{ $op->nip_panjang ?? '-' }})">
                                        {{ strtoupper(substr($op->nama_user, 0, 1)) }}
                                    </div>
                                @empty
                                    <div class="w-9 h-9 rounded-full bg-abubiru text-biru3 font-bold text-xs flex items-center justify-center">
                                        -
                                    </div>
                                @endforelse

                                @if ($remainingOpsCount > 0)
                                    <div class="inline-flex h-9 w-9 rounded-full ring-2 ring-white bg-abubiru text-biru1 font-extrabold text-xs items-center justify-center shadow-xs cursor-pointer"
                                        title="Dan {{ $remainingOpsCount }} operator lainnya: {{ $operators->slice(3)->pluck('nama_user')->join(', ') }}">
                                        +{{ $remainingOpsCount }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold uppercase tracking-wider text-biru3">Operator Satker</div>
                                <div class="font-extrabold text-biru1 text-xs sm:text-sm truncate max-w-45" title="{{ $allOperatorsTooltip }}">
                                    {{ $operatorNamesText }}
                                </div>
                            </div>
                        </div>

                        {{-- Unified Progress Bar --}}
                        <div class="flex-1 w-full px-0 md:px-4">
                            <div class="flex items-center justify-between text-xs font-bold text-biru1 mb-1.5">
                                <span class="text-biru3 font-semibold">Progress SAQ Satker</span>
                                <span class="text-biru2 font-black text-sm">{{ $satkerProgress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-abubiru/40 overflow-hidden p-0.5 shadow-inner">
                                <div class="h-full rounded-full transition-all duration-700 {{ $satkerProgress == 100 ? 'bg-hijau' : ($satkerProgress > 0 ? 'bg-linear-to-r from-oren2 to-amber-500' : 'bg-abubiru/40') }}"
                                    style="width: {{ $satkerProgress }}%">
                                </div>
                            </div>
                            @if ($operatorFormattedSubtext)
                                <div class="mt-1.5 text-xs text-biru4 font-medium truncate" title="{{ $allOperatorsTooltip }}">
                                    {{ $operatorFormattedSubtext }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>            
    </div>
@else
    <div class="p-5 text-center text-biru3 border-2 border-dashed border-abubiru rounded-2xl bg-white font-medium mb-4">
        Belum ada periode berjalan saat ini.
    </div>
@endif

<!-- Progress Evaluasi & Agenda Mendatang Grid (70:30) -->
@if ($activePeriod)
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-5 mb-5 items-stretch">
        <!-- Timeline Progress Section (70%) -->
        <section
            class="rounded-2xl bg-white border border-abubiru shadow-xs p-5 sm:p-6 lg:col-span-7 flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center justify-between mb-4 md:mb-5 gap-3">
                    <h2 class="text-lg sm:text-xl font-bold text-biru1">Progress Evaluasi Tahun
                        {{ \Carbon\Carbon::parse($activePeriod->periode_start)->year }}</h2>
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
                            <div class="flex flex-col items-center text-center group min-w-0">
                                <!-- Circle Node -->
                                <div class="relative flex items-center justify-center">
                                    <div @class([
                                        'w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center shadow-md group-hover:scale-110 transition-transform relative z-20',
                                        'bg-biru4 text-white shadow-xs' => in_array($step['color'], ['done', 'success']),
                                        'bg-hijau text-white' => in_array($step['color'], ['active', 'info']),
                                        'bg-merah1 text-white shadow-xs' => in_array($step['color'], ['danger', 'merah']),
                                        'bg-white border-2 border-abubiru text-biru4/60 shadow-xs' =>
                                            in_array($step['color'], ['pending', 'slate']),
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
                                        'text-biru1' => !in_array($step['color'], ['pending', 'slate']) || $step['active'],
                                        'text-biru3/80' => in_array($step['color'], ['pending', 'slate']) && !$step['active'],
                                    ])>{{ $step['label'] }}</div>
                                    <div>
                                        <span @class([
                                            'text-xs font-semibold px-2.5 py-1.5 rounded-full tracking-tight',
                                            'text-biru3 bg-abubiru/60' => in_array($step['color'], ['done', 'success']),
                                            'text-white bg-hijau font-bold px-2.5 shadow-xs' =>
                                                in_array($step['color'], ['active', 'info']),
                                            'text-white bg-merah1' => in_array($step['color'], ['danger', 'merah']),
                                            'text-biru4 bg-abubiru' => in_array($step['color'], ['pending', 'slate']),
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
                                    'bg-biru4 text-white' => in_array($step['color'], ['done', 'success']),
                                    'bg-hijau text-white' => in_array($step['color'], ['active', 'info']),
                                    'bg-merah1 text-white' => in_array($step['color'], ['danger', 'merah']),
                                    'bg-white border-2 border-abubiru text-biru4/60' =>
                                        in_array($step['color'], ['pending', 'slate']),
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
                                            'text-biru3 bg-abubiru/60' => in_array($step['color'], ['done', 'success']),
                                            'text-white bg-hijau' => in_array($step['color'], ['active', 'info']),
                                            'text-white bg-merah1' => in_array($step['color'], ['danger', 'merah']),
                                            'text-biru3 bg-abubiru' => in_array($step['color'], ['pending', 'slate']),
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
        <div class="rounded-2xl bg-white border border-abubiru shadow-sm p-5 sm:p-6 flex flex-col relative overflow-hidden group lg:col-span-3 justify-between">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-biru4/5 rounded-full blur-2xl group-hover:bg-biru4/10 transition-all"></div>
            <div>
                <div class="flex items-center gap-3 text-biru1 font-bold text-base sm:text-lg mb-4">
                    <span>Agenda Mendatang</span>
                </div>
                <div class="space-y-2.5">
                    @forelse ($agenda as $item)
                        <a href="{{ $item['url'] ?? '#' }}" wire:navigate.hover class="flex items-center gap-3 p-2.5 rounded-xl bg-putihbg border border-abubiru hover:bg-abubiru/20 hover:border-biru2/30 transition duration-200 cursor-pointer">
                            <div class="text-center shrink-0 w-12">
                                <div class="text-xs font-bold text-biru4 uppercase">{{ $item['day'] }}</div>
                                <div class="text-sm font-black text-biru1">{{ $item['date'] }}</div>
                            </div>
                            <div class="w-0.5 h-7 bg-abubiru shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs sm:text-sm font-bold text-biru1 truncate">{{ $item['event'] }}</div>
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
