<x-layouts.app title="Visitasi - HaloKIP">
    @php
        $user = Auth::user();
        $roleName = $user?->role?->nama_role;
        $isVerifikator = $roleName === 'verifikator';
        $isOperator = $roleName === 'operator';
        $homeRoute = $isVerifikator ? route('verifikator.index') : route('operator.index');
        $monitoringRoute = Route::has('evaluasi.visitasi.index')
            ? route('evaluasi.visitasi.index')
            : ($isVerifikator ? route('verifikator.monitoring-visitasi') : route('operator.visitasi'));

        $isGugur = $isGugur ?? false;
        $hasScore = $visitasi && $visitasi->visit_score !== null;
        $hasSchedule = $visitasi && ($visitasi->visit_at !== null || $visitasi->visit_mode === 'online_upload');

        $visitEndObj = $period->visit_end ? \Carbon\Carbon::parse($period->visit_end) : null;
        $uploadDeadlineDate = $visitEndObj ? $visitEndObj->copy()->subDays(3)->toDateString() : null;
        $uploadDeadlineFormatted = $visitEndObj ? $visitEndObj->copy()->subDays(3)->translatedFormat('d F Y') : '—';

        $isPastVisitDate = false;
        $pastVisitDateLabel = '';
        if ($visitasi) {
            $mode = $visitasi->visit_mode ?? 'offline';
            if ($mode === 'online_upload') {
                if ($uploadDeadlineDate && now()->toDateString() > $uploadDeadlineDate) {
                    $isPastVisitDate = true;
                    $pastVisitDateLabel = $uploadDeadlineFormatted;
                }
            } else {
                if ($visitasi->visit_at && now()->toDateString() > $visitasi->visit_at) {
                    $isPastVisitDate = true;
                    $pastVisitDateLabel = \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('d F Y');
                }
            }
        }

        $visitEnd = $period->visit_end ? \Carbon\Carbon::parse($period->visit_end)->endOfDay() : null;
        $isReleased = !$visitEnd || now()->greaterThan($visitEnd);
        $evalEnd = $period->eval_end ? \Carbon\Carbon::parse($period->eval_end)->endOfDay() : null;
        $isSaqReleased = !$evalEnd || now()->greaterThan($evalEnd);

        $satkerName = $submission->user->masterSatker->nama_satker ?? ($user->masterSatker->nama_satker ?? $user->nama_user);

        if ($isGugur && !$visitasi) {
            $heroTitle = 'Gugur — Tidak Lolos KKM SAQ';
            $heroDesc =
                'Nilai SAQ unit kerja Anda tidak memenuhi KKM per indikator (>=80), sehingga tidak melanjutkan ke tahap Visitasi & Verifikasi Faktual pada periode ini.';
        } elseif ($hasScore) {
            $heroTitle = 'Visitasi Selesai';
            $heroDesc =
                'Penilaian visitasi dan verifikasi berkas telah selesai dilaksanakan oleh tim evaluator provinsi.';
        } elseif ($visitasi) {
            $mode = $visitasi->visit_mode ?? 'offline';
            if ($mode === 'online_upload') {
                $heroTitle = 'Terjadwal: Upload Bukti Dukung Online';
                $heroDesc =
                    'Batas akhir pengunggahan link bukti dukung adalah H-3 sebelum periode visitasi berakhir (' .
                    $uploadDeadlineFormatted .
                    ').';
            } elseif ($mode === 'online_meeting') {
                $heroTitle = 'Terjadwal: Visitasi Online';
                $heroDesc =
                    'Visitasi online via virtual meeting dijadwalkan pada ' .
                    ($visitasi->visit_at
                        ? \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('l, d F Y')
                        : 'jadwal yang ditentukan evaluator') .
                    '.';
            } else {
                $heroTitle = 'Terjadwal: Kunjungan Lapangan';
                $heroDesc =
                    'Kunjungan lapangan dijadwalkan pada ' .
                    ($visitasi->visit_at
                        ? \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('l, d F Y')
                        : 'jadwal yang ditentukan evaluator') .
                    '.';
            }
        } else {
            $heroTitle = 'Menunggu Jadwal Visitasi';
            $heroDesc =
                'Tim evaluator provinsi sedang menentukan jadwal kunjungan lapangan/visitasi untuk kantor Anda.';
        }

        $linkBuktiRoute = Route::has('evaluasi.visitasi.link-bukti') 
            ? route('evaluasi.visitasi.link-bukti') 
            : route('operator.visitasi.link-bukti');
    @endphp

    <x-ui.page-header :breadcrumb="[
        'Beranda' => $homeRoute,
        'Monitoring Visitasi' => $monitoringRoute,
        'Detail Visitasi' => '#',
    ]">
    </x-ui.page-header>

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
            <img src="{{ asset('images/orangVisitasi.svg') }}" alt="Visitasi Lapangan" class="h-60 w-auto object-contain">
        </div>

        <div class="relative z-10 text-left">
            <h2 class="text-3xl md:text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Penilaian
                Visitasi & Verifikasi Faktual</h2>
            <div class="text-2xl md:text-2xl font-semibold text-white leading-tight mb-4 tracking-tight">
                {{ $satkerName }} - Tahun {{ $period->tahun }}
            </div>
            <p class="text-base leading-relaxed text-white font-normal max-w-xl">
                <span class="text-kuning2 font-bold">{{ $heroTitle }}.</span> {{ $heroDesc }}
            </p>
        </div>
    </section>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold text-left">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold text-left">
            {{ session('error') }}
        </div>
    @endif

    <!-- Layout kiri-kanan: Info Visitasi | Penilaian & Bukti Dukung -->
    <div class="flex flex-col lg:flex-row gap-6 mb-6 items-start">

        <!-- Kolom Kiri: Informasi Visitasi -->
        <div
            class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col lg:w-2/5 shrink-0 overflow-hidden">
            <div class="flex flex-col w-full text-left">
                <!-- Header Card -->
                <div class="bg-biru2 px-6 py-4 flex flex-col text-left">
                    <span class="text-white font-extrabold text-lg">Informasi Visitasi</span>
                    <span class="text-xs text-white/80">Berikut adalah jadwal dan moda pelaksanaan visitasi</span>
                </div>

                <div class="p-6 flex flex-col w-full text-left">
                    <!-- Moda Visitasi -->
                    <div class="pb-3.5 flex items-center gap-3.5">
                        <div class="flex flex-col">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Moda Visitasi</span>
                            <span class="text-sm font-extrabold text-biru1 mt-0.5">
                                @if ($visitasi && $visitasi->visit_mode === 'online_upload')
                                    Upload Bukti Dukung Online
                                @elseif ($visitasi && $visitasi->visit_mode === 'online_meeting')
                                    Visitasi Online (Meeting)
                                @else
                                    Kunjungan Lapangan (Onsite)
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Tanggal Pelaksanaan / Batas Akhir Upload -->
                    <div class="pt-3.5 flex items-center gap-3.5 border-t border-abubiru/40">
                        <div class="flex flex-col">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">
                                {{ $visitasi && $visitasi->visit_mode === 'online_upload' ? 'Batas Akhir Upload' : 'Tanggal Pelaksanaan' }}
                            </span>
                            <span class="text-sm font-bold text-biru1 mt-0.5">
                                @if ($visitasi && $visitasi->visit_mode === 'online_upload')
                                    H-3 Visitasi Berakhir ({{ $uploadDeadlineFormatted }})
                                @else
                                    {{ $visitasi && $visitasi->visit_at ? \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('d F Y') : 'Belum Dijadwalkan' }}
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Lokasi / Satker -->
                    <div class="pt-3.5 flex items-center gap-3.5 border-t border-abubiru/40">
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Lokasi / Satker</span>
                            <span class="text-sm font-bold text-biru1 truncate mt-0.5">{{ $satkerName }}</span>
                        </div>
                    </div>

                    <!-- Petugas Evaluator -->
                    <div class="pt-3.5 flex flex-col gap-2.5 border-t border-abubiru/40">
                        <div class="flex items-center gap-3.5">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Petugas Evaluator</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            @if ($visitasi && $visitasi->evaluators->count() > 0)
                                @foreach ($visitasi->evaluators as $ev)
                                    <div class="flex items-center justify-between text-xs font-bold text-biru1 bg-putihbg px-3.5 py-2 rounded-xl border border-abubiru/40">
                                        <span class="capitalize">{{ ucwords($ev->user->name ?? '-') }}</span>
                                        <span class="text-xs font-bold text-biru2 bg-biru5/50 px-2 py-0.5 rounded-md">{{ $ev->user->role->nama_role ?? 'Evaluator' }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-xs font-medium text-biru4">-</span>
                            @endif
                        </div>
                    </div>

                    @if ($visitasi && ($visitasi->visit_mode ?? 'offline') !== 'offline')
                        <!-- Content per Mode Visitasi -->
                        @if ($visitasi->visit_mode === 'online_upload')
                            {{-- Mode: Upload Bukti Dukung Online --}}
                            <div class="pt-3.5 flex flex-col border-t border-abubiru/40" x-data="{
                                link: '{{ $visitasi->link_buktidukung ?? '' }}',
                                savedLink: '{{ $visitasi->link_buktidukung ?? '' }}',
                                isEditing: {{ $isPastVisitDate || $visitasi->link_buktidukung ? 'false' : 'true' }},
                                isPastVisitDate: {{ $isPastVisitDate ? 'true' : 'false' }},
                                saveStatus: '',
                                async submitForm(e) {
                                    e.preventDefault();
                                    if (this.isPastVisitDate) return;
                                    this.saveStatus = 'saving';
                                    const form = e.target;
                                    const formData = new FormData(form);
                                    try {
                                        const response = await fetch(form.action, {
                                            method: 'POST',
                                            body: formData,
                                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                        });
                                        if (response.ok) {
                                            this.savedLink = this.link;
                                            this.isEditing = false;
                                            this.saveStatus = 'success';
                                            setTimeout(() => { this.saveStatus = ''; }, 3000);
                                        } else {
                                            this.saveStatus = 'error';
                                        }
                                    } catch (err) {
                                        console.error(err);
                                        this.saveStatus = 'error';
                                    }
                                }
                            }">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru4 mb-2">Bukti Dukung Visitasi</span>
                                <p class="text-xs text-biru3 font-medium leading-relaxed mb-2.5">
                                    Masukkan tautan (link) Google Drive atau folder berkas bukti dukung. Pengisian dibuka hingga <strong>H-3 visitasi berakhir ({{ $uploadDeadlineFormatted }})</strong>.
                                </p>
                                @if ($isOperator)
                                    <form @submit="submitForm" method="POST"
                                        action="{{ $linkBuktiRoute }}" class="flex flex-col gap-2">
                                        @csrf
                                        <input type="hidden" name="id_periode" value="{{ $period->id_periode }}" />
                                        <div class="relative flex items-center border rounded-xl p-1 px-3 bg-white"
                                            :class="(isEditing && !isPastVisitDate) ?
                                            'border-slate-300 focus-within:border-biru2' :
                                            'border-abubiru bg-slate-50 cursor-not-allowed'">
                                            <input type="url" name="link_buktidukung" x-model="link"
                                                :readonly="!isEditing || isPastVisitDate" placeholder="https://drive.google.com/..."
                                                class="w-full bg-transparent px-2 py-2 text-xs text-biru1 font-semibold focus:outline-none placeholder:text-slate-400"
                                                :class="(!isEditing || isPastVisitDate) && 'cursor-not-allowed text-biru3'" />
                                        </div>

                                        <div class="flex justify-end gap-2 mt-1">
                                            <template x-if="(!isEditing || isPastVisitDate) && link">
                                                <a :href="link" target="_blank" rel="noopener noreferrer"
                                                    class="px-3 py-1.5 bg-biru2 hover:bg-biru1 text-white rounded-lg text-xs font-bold transition flex items-center gap-1">
                                                    <span>Buka</span>
                                                </a>
                                            </template>
                                            <template x-if="isEditing && !isPastVisitDate && savedLink">
                                                <button type="button" @click="isEditing = false; link = savedLink"
                                                    class="px-3 py-1.5 text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg text-xs font-bold transition cursor-pointer">
                                                    Batal
                                                </button>
                                            </template>
                                            <template x-if="!isEditing && !isPastVisitDate">
                                                <button type="button" @click="isEditing = true"
                                                    class="px-3 py-1.5 text-white bg-kuning1 hover:bg-kuning2 rounded-lg text-xs font-bold transition cursor-pointer">
                                                    Ubah Link
                                                </button>
                                            </template>
                                            <template x-if="isEditing && !isPastVisitDate">
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-white bg-biru1 hover:bg-biru2 rounded-lg text-xs font-bold transition cursor-pointer">
                                                    Simpan Link
                                                </button>
                                            </template>
                                        </div>
                                    </form>
                                @else
                                    <div class="p-3 bg-putihbg rounded-xl border border-abubiru flex items-center justify-between gap-3">
                                        @if ($visitasi->link_buktidukung)
                                            <a href="{{ $visitasi->link_buktidukung }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 text-xs font-semibold text-biru2 hover:underline break-all">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                {{ $visitasi->link_buktidukung }}
                                            </a>
                                            <a href="{{ $visitasi->link_buktidukung }}" target="_blank" rel="noopener noreferrer"
                                                class="px-3 py-1.5 bg-biru2 hover:bg-biru1 text-white rounded-lg text-xs font-bold transition shrink-0 flex items-center gap-1">
                                                <span>Buka</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-amber-600 font-semibold italic">Belum diunggah oleh Operator</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @elseif ($visitasi->visit_mode === 'online_meeting')
                            {{-- Mode: Virtual Meeting --}}
                            <div class="pt-3.5 flex flex-col border-t border-abubiru/40">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru4 mb-2">Tautan Virtual Meeting</span>
                                @if ($visitasi->link_meeting)
                                    <p class="text-xs text-biru3 font-medium leading-relaxed mb-2.5">
                                        Tautan ruang virtual meeting telah ditentukan oleh tim evaluator provinsi:
                                    </p>
                                    <div class="p-3 bg-putihbg rounded-xl border border-abubiru flex items-center justify-between gap-3">
                                        <a href="{{ $visitasi->link_meeting }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-2 text-xs font-semibold text-biru2 hover:underline break-all">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            {{ $visitasi->link_meeting }}
                                        </a>
                                        <a href="{{ $visitasi->link_meeting }}" target="_blank" rel="noopener noreferrer"
                                            class="px-3 py-1.5 bg-biru2 hover:bg-biru1 text-white rounded-lg text-xs font-bold transition shrink-0 flex items-center gap-1">
                                            <span>Masuk</span>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-xs text-amber-600 font-semibold italic">
                                        Tautan room meeting belum diatur oleh evaluator provinsi.
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Notif / Alert di paling bawah -->
            <div class="mx-5 mb-5 flex items-center gap-2 text-xs text-biru4 font-medium border-t border-abubiru/40 pt-3">
                <svg class="w-4 h-4 text-biru4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    @if ($visitasi && $visitasi->visit_mode === 'online_upload')
                        Pastikan tautan bukti dukung diunggah sebelum H-3 visitasi berakhir.
                    @elseif ($visitasi && $visitasi->visit_mode === 'online_meeting')
                        Pastikan Anda bergabung ke room meeting tepat waktu sesuai jadwal.
                    @else
                        Siapkan dokumen berkas fisik dan lokasi kantor untuk menyambut tim evaluator provinsi.
                    @endif
                </span>
            </div>
        </div>

        <!-- Kolom Kanan: Penilaian & Catatan Evaluator -->
        <div
            class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col flex-1 text-left overflow-hidden">
            <!-- Header Card -->
            <div class="bg-biru2 px-6 py-4 flex flex-col text-left">
                <span class="text-white font-extrabold text-lg">Penilaian & Catatan Evaluator</span>
                <span class="text-xs text-white/80">Berikut adalah nilai visitasi dan catatan ulasan dari seluruh petugas evaluator.</span>
            </div>

            @if ($isReleased)
                <div class="p-6 flex flex-col gap-6 flex-1 justify-between">
                    <div>
                        <!-- List Evaluator Inputs (Read-only) -->
                        <div class="flex flex-col gap-4">
                            @if ($visitasi && $visitasi->evaluators->count() > 0)
                                @foreach ($visitasi->evaluators as $index => $ev)
                                    @php
                                        $evNote = $ev->note;
                                        $evIsDraft = str_starts_with($evNote ?? '', '[DRAFT]');
                                        if (str_starts_with($evNote ?? '', '[DRAFT] ')) {
                                            $evNote = substr($evNote, 8);
                                        } elseif ($evNote === '[DRAFT]') {
                                            $evNote = '';
                                        }
                                    @endphp
                                    <div
                                        class="p-5 rounded-2xl border transition-all duration-200 bg-[#F4F7FC]/80 border-slate-200/80">
                                        <!-- Header: Nama Evaluator -->
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                                Penilai {{ $index + 1 }}: <span class="font-extrabold text-slate-500 capitalize">{{ ucwords($ev->user->name ?? '-') }}</span>
                                                @if ($evIsDraft && $ev->score !== null)
                                                    <span class="text-oren2 font-black text-xs ml-1">(Draft)</span>
                                                @endif
                                            </span>
                                            @if ($ev->user && $ev->user->hasRole('penilai_eksternal'))
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-bold uppercase tracking-wider">
                                                    Penilai Eksternal
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-4 items-start">
                                            <!-- Nilai Box -->
                                            <div class="flex flex-col items-center shrink-0">
                                                <div class="w-24 h-14 flex items-center justify-center text-center text-2xl font-extrabold rounded-xl border border-slate-200/80 bg-white text-slate-500">
                                                    {{ $ev->score !== null ? number_format($ev->score, 2) : '-' }}
                                                </div>
                                                <label class="text-xs font-bold uppercase tracking-wider mt-1.5 text-slate-400">Nilai</label>
                                            </div>

                                            <!-- Catatan Box -->
                                            <div class="flex flex-col flex-1 min-w-0 w-full">
                                                <div class="w-full rounded-xl border border-slate-200/80 bg-white text-slate-500 px-4 py-3 text-xs sm:text-sm font-medium leading-relaxed min-h-20 {{ !$evNote ? 'italic text-slate-400' : '' }}">
                                                    {{ $evNote ? $evNote : 'Belum memberikan catatan...' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="rounded-xl border border-dashed border-abubiru p-6 text-center">
                                    <p class="text-sm text-biru3 italic">Belum ada petugas evaluator yang ditugaskan.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Overall Average: tampil jika visit_score sudah difinalisasi -->
                    @if ($hasScore)
                        <hr class="border-abubiru my-1 mt-4">
                        <div
                            class="rounded-xl bg-birumuda border border-biru5 px-6 py-5 flex items-center justify-between shadow-sm mt-4">
                            <div>
                                <div class="font-bold text-biru6 text-sm mb-0.5">Rata-rata Nilai Visitasi Final</div>
                                <p class="text-xs text-biru3">Nilai telah difinalisasi dari seluruh evaluator.</p>
                            </div>
                            <div class="text-3xl font-black text-biru1">
                                {{ number_format($visitasi->visit_score, 2) }}
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
                        <h4 class="text-lg font-bold text-biru1 mb-1">Nilai Visitasi Belum Dirilis</h4>
                        <p class="text-sm text-biru3 font-medium leading-relaxed mb-4">
                            Nilai visitasi dan catatan evaluator sedang diproses. Nilai visitasi akan dirilis secara
                            serentak setelah masa visitasi berakhir pada tanggal
                            <strong>{{ $period->visit_end ? \Carbon\Carbon::parse($period->visit_end)->translatedFormat('d F Y') : '-' }}</strong>.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
