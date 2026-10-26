@php
    $userRole = Auth::user()?->role?->nama_role;
    $isSuperEval = $userRole === 'super_evaluator';
    $isPenilaiEksternal = $userRole === 'penilai_eksternal';
    $routePrefix = $isSuperEval ? 'super-evaluator' : ($isPenilaiEksternal ? 'penilai-eksternal' : 'evaluator');
    $monitoringUrl = Route::has('evaluasi.visitasi.monitoring')
        ? route('evaluasi.visitasi.monitoring')
        : route($routePrefix . '.monitor-visitasi');
    $saveReviewUrl = Route::has('evaluasi.visitasi.review.save')
        ? route('evaluasi.visitasi.review.save', \App\Helpers\SecureId::encrypt($visitasi->id_visit))
        : route('evaluator.review-visitasi.save', \App\Helpers\SecureId::encrypt($visitasi->id_visit));
    $scheduleSingleUrl = Route::has('evaluasi.visitasi.schedule')
        ? route('evaluasi.visitasi.schedule', $visitasi->id_visit)
        : ($isSuperEval ? route('super-evaluator.schedule-visitasi', $visitasi->id_visit) : route('evaluator.schedule-visitasi', $visitasi->id_visit));

    $isMe = $visitasi->evaluators->contains(fn($ev) => (string)($ev->id_user ?? '') === (string)Auth::id());
    $visitStart = $visitasi->period?->visit_start ? \Carbon\Carbon::parse($visitasi->period->visit_start)->startOfDay() : null;
    $visitDate = $visitasi->visit_at ? \Carbon\Carbon::parse($visitasi->visit_at)->startOfDay() : null;
    $isBeforeVisitStart = $visitStart ? now()->startOfDay()->lt($visitStart) : false;

    $visitMode = $visitasi->visit_mode ?? 'offline';
    if ($visitMode === 'online_upload') {
        $canScoreNow = !$isBeforeVisitStart;
    } else {
        $canScoreNow = !$isBeforeVisitStart && ($visitDate && now()->startOfDay()->gte($visitDate));
    }
    // Cek apakah visitasi sudah final (semua evaluator sudah submit dan visit_score tersimpan)
    $isFinalized = $visitasi->visit_score !== null;
    // Cek apakah evaluator ini sudah men-submit final (bukan draft)
    $hasSubmitted = $visitasiEvaluator && $visitasiEvaluator->score !== null && !$visitasiEvaluator->is_draft;
    $periodeEnd = $visitasi->period?->visit_end
        ? \Carbon\Carbon::parse($visitasi->period->visit_end)->endOfDay()
        : null;
    $isPastDeadline = $periodeEnd ? now()->greaterThan($periodeEnd) : false;
    $submissionStatus = $visitasi->submission?->status ?? 5;
    $isStagePassed = $submissionStatus >= 7;
    // Evaluator boleh mengisi jika: ditugaskan, tanggal sudah tiba, belum melewati deadline, durasi tahap belum selesai, dan belum final
    $canEdit = $isMe && $canScoreNow && !$isPastDeadline && !$isFinalized && !$isStagePassed;
@endphp
<x-layouts.app title="Review Visitasi & Verifikasi Faktual - HaloKIP">
    <x-ui.page-header :breadcrumb="[
        'Evaluasi' => '#',
        'Monitoring Visitasi' => $monitoringUrl,
        'Review' => '#',
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
            <h3 class="text-2xl md:text-2xl font-semibold text-white leading-tight mb-4 tracking-tight">
                {{ $visitasi->submission->user->masterSatker->nama_satker }} - Tahun {{ $visitasi->period->tahun }}</h3>
            <p class="text-base leading-relaxed text-white font-normal max-w-xl">Isi nilai dan catatan hasil
                kunjungan lapangan untuk setiap aspek yang telah diverifikasi. Pastikan penilaian didasarkan bukti
                fisik yang ditemukan saat kunjungan.</p>
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
            <span>Batas waktu pengisian/pengeditan nilai visitasi periode ini telah berakhir{{ $periodeEnd ? ' pada tanggal ' . $periodeEnd->translatedFormat('d F Y') : '' }}. Nilai sudah tidak dapat diubah lagi.</span>
        </div>
    @elseif (!$canScoreNow)
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm font-semibold shadow-sm text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                @if ($visitMode === 'online_upload')
                    Penilaian visitasi online baru dapat dilakukan pada atau setelah awal tahapan visitasi <strong>{{ $visitStart ? $visitStart->translatedFormat('d F Y') : '-' }}</strong>.
                @elseif ($visitDate)
                    Penilaian visitasi hanya dapat dilakukan pada atau setelah tanggal kunjungan lapangan <strong>{{ $visitDate->translatedFormat('d F Y') }}</strong>.
                @else
                    Penilaian visitasi baru dapat dilakukan pada atau setelah awal tahapan visitasi <strong>{{ $visitStart ? $visitStart->translatedFormat('d F Y') : '-' }}</strong>.
                @endif
                Saat ini form penilaian dikunci dan belum dapat diedit/disimpan karena belum waktunya.
            </span>
        </div>
    @elseif (!$isMe && Auth::user()?->role?->nama_role !== 'super_evaluator')
        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-semibold shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Mode Baca (Read-Only): Anda dapat melihat informasi visitasi ini. Namun, Anda tidak dapat mengisi/mengubah nilai karena Anda belum ditugaskan sebagai Petugas Evaluator untuk kunjungan ini.</span>
        </div>
    @elseif ($isFinalized)
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold shadow-sm text-center">
            ✓ Penilaian visitasi telah difinalisasi dengan nilai rata-rata <strong>{{ number_format($visitasi->visit_score, 2) }}</strong>. Tahapan penilaian telah selesai dan nilai tidak dapat diubah lagi.
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
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-oren2 font-extrabold text-base">Panduan Penilaian</span>
                    <span x-show="!open"
                        class="text-xs text-oren2/80 mt-0.5 font-medium transition-opacity duration-200">Klik untuk
                        melihat detail panduan</span>
                </div>
            </div>
            <div
                class="text-oren2 w-7 h-7 rounded-lg hover:bg-oren2/10 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 transform transition-transform duration-300" :class="open ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>
        <div x-show="open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="p-5 pl-15 flex flex-col gap-2.5 bg-white text-left"
            style="display: none;">
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Berikan nilai dalam skala
                    0-100</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Catatan evaluator wajib diisi untuk
                    memberikan penjelasan/feedback hasil visitasi dan verifikasi faktual</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-1.5 h-1.5 rounded-full bg-kuning1 shrink-0 mt-2"></span>
                <span class="text-sm text-slate-700 font-semibold leading-relaxed">Penilaian harus didasarkan pada bukti
                    fisik yang ditemukan saat kunjungan lapangan</span>
            </div>
        </div>
    </div>

    <form id="review-visitasi-form" method="POST"
        action="{{ $saveReviewUrl }}"
        enctype="multipart/form-data" x-data="{
            isDirty: false,
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



        <!-- Layout kiri-kanan: Info Visitasi | Penilaian & Catatan -->
        <div class="flex flex-col lg:flex-row gap-6 mb-6 items-start">

            <!-- Kolom Kiri: Informasi Visitasi -->
            <div
                class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col lg:w-2/5 shrink-0 overflow-hidden">
                <div class="flex flex-col w-full">
                    <!-- Header Card (bg-biru2) -->
                    <div class="bg-biru2 px-6 py-4 flex flex-row gap-4 items-center">
                        <div class="flex flex-col text-left">
                            <span class="text-white font-extrabold text-lg">Informasi Visitasi</span>
                            <span class="text-xs text-white/80">Berikut adalah jadwal kunjungan lapangan dan informasi terkait</span>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col w-full text-left divide-y divide-abubiru/40">
                        <!-- Moda Visitasi -->
                        <div class="pb-3.5 flex items-center gap-3.5">
                            <div class="flex flex-col">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Moda Visitasi</span>
                                <span class="text-sm font-extrabold text-biru1 mt-0.5">
                                    @if ($visitasi->visit_mode === 'online_upload')
                                        Upload Bukti Dukung Online
                                    @elseif ($visitasi->visit_mode === 'online_meeting')
                                        Visitasi Online (Meeting)
                                    @else
                                        Kunjungan Lapangan (Onsite)
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Tanggal Pelaksanaan -->
                        <div class="py-3.5 flex items-center gap-3.5">
                            <div class="flex flex-col">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">
                                    {{ $visitasi->visit_mode === 'online_upload' ? 'Batas Akhir Upload' : 'Tanggal Pelaksanaan' }}
                                </span>
                                <span class="text-sm font-bold text-biru1 mt-0.5">
                                    @if ($visitasi->visit_mode === 'online_upload')
                                        H-3 Visitasi Berakhir ({{ $visitasi->submission->period->visit_end ? \Carbon\Carbon::parse($visitasi->submission->period->visit_end)->subDays(3)->translatedFormat('d F Y') : '—' }})
                                    @else
                                        {{ $visitasi->visit_at ? \Carbon\Carbon::parse($visitasi->visit_at)->translatedFormat('d F Y') : 'Belum Dijadwalkan' }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Lokasi Kunjungan -->
                        <div class="py-3.5 flex items-center gap-3.5">
                            <div class="flex flex-col min-w-0 flex-1">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Lokasi Kunjungan</span>
                                <span class="text-sm font-bold text-biru1 truncate mt-0.5">{{ $visitasi->submission->user->masterSatker->nama_satker }}</span>
                            </div>
                        </div>

                        <!-- Petugas Evaluator -->
                        <div class="pt-3.5 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru3">Petugas Evaluator</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                @if ($visitasi->evaluators->count() > 0)
                                    @foreach ($visitasi->evaluators as $ev)
                                        <div class="flex items-center justify-between text-xs font-bold text-biru1 bg-putihbg px-3.5 py-2 rounded-xl border border-abubiru/40">
                                            <div class="flex items-center gap-2">
                                                <span class="capitalize">{{ ucwords($ev->user->name ?? '-') }}</span>
                                                @if(Auth::id() == $ev->id_user)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-hijau/10 text-hijau text-xs font-bold">Anda</span>
                                                @endif
                                            </div>
                                            @if ($ev->user && $ev->user->hasRole('penilai_eksternal'))
                                                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md">Penilai Eksternal</span>
                                            @else
                                                <span class="text-xs font-bold text-biru2 bg-biru5/50 px-2 py-0.5 rounded-md">{{ $ev->user->role->nama_role ?? 'Evaluator' }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-xs font-medium text-biru4">-</span>
                                @endif
                            </div>
                        </div>

                        <!-- Bukti Dukung jika online mode -->
                        @if ($visitasi->visit_mode === 'online_upload')
                            <div class="pt-3.5 flex flex-col">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-biru4 mb-2">Bukti Dukung Visitasi Online</span>
                                @if ($visitasi->link_buktidukung)
                                    <p class="text-xs text-biru3 font-medium leading-relaxed mb-2.5">
                                        Tautan bukti dukung yang telah diunggah oleh Operator Kabupaten/Kota:
                                    </p>
                                    <div class="p-3 bg-putihbg rounded-xl border border-abubiru flex items-center justify-between gap-3">
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
                                    </div>
                                @else
                                    <p class="text-xs text-biru4 font-medium italic">Operator belum mengunggah bukti dukung visitasi.</p>
                                @endif
                            </div>
                        @elseif ($visitasi->visit_mode === 'online_meeting')
                            <div class="pt-3.5 flex flex-col" x-data="{
                                isEditingLink: {{ $visitasi->link_meeting ? 'false' : 'true' }},
                                linkVal: '{{ $visitasi->link_meeting ?? '' }}',
                                savedVal: '{{ $visitasi->link_meeting ?? '' }}',
                                saveStatus: '',
                                async updateLink() {
                                    this.saveStatus = 'saving';
                                    const formData = new FormData();
                                    formData.append('_token', '{{ csrf_token() }}');
                                    formData.append('visit_id', '{{ $visitasi->id_visit }}');
                                    formData.append('visit_mode', 'online_meeting');
                                    formData.append('visit_at', '{{ $visitasi->visit_at ? \Carbon\Carbon::parse($visitasi->visit_at)->format('Y-m-d') : now()->format('Y-m-d') }}');
                                    @foreach ($visitasi->evaluators as $ev)
                                        formData.append('id_visitor[]', '{{ $ev->id_user }}');
                                    @endforeach
                                    formData.append('link_meeting', this.linkVal);

                                    try {
                                        const response = await fetch('{{ $scheduleSingleUrl }}', {
                                            method: 'POST',
                                            body: formData,
                                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                        });
                                        if (response.ok) {
                                            this.savedVal = this.linkVal;
                                            this.isEditingLink = false;
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
                                <label class="block text-xs font-bold uppercase tracking-wider text-biru2 mb-1">Tautan Virtual Meeting</label>
                                <div class="flex flex-col gap-2">
                                    <div class="relative flex items-center border rounded-xl p-1 px-3 bg-white"
                                        :class="isEditingLink ? 'border-slate-300 focus-within:border-biru2' : 'border-abubiru bg-slate-50 cursor-not-allowed'">
                                        <input type="url" name="link_meeting" x-model="linkVal" :readonly="!isEditingLink"
                                            placeholder="https://meet.google.com/..."
                                            class="w-full bg-transparent px-2 py-2 text-xs text-biru1 font-semibold focus:outline-none placeholder:text-slate-400">
                                    </div>
                                    <div class="flex justify-end gap-2 mt-1">
                                        <template x-if="!isEditingLink">
                                            <button type="button" @click="isEditingLink = true" class="px-3 py-1.5 text-white bg-kuning1 hover:brightness-95 rounded-lg text-xs font-bold transition cursor-pointer">Edit Link</button>
                                        </template>
                                        <template x-if="isEditingLink">
                                            <button type="button" @click="updateLink" class="px-3 py-1.5 text-white bg-biru1 hover:bg-biru2 rounded-lg text-xs font-bold transition cursor-pointer">Simpan Link</button>
                                        </template>
                                    </div>
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
                    <span>Pastikan penilaian didasarkan pada bukti fisik saat kunjungan lapangan.</span>
                </div>
            </div>

            <!-- Kolom Kanan: Penilaian Evaluator -->
            <div class="rounded-xl bg-white border border-abubiru shadow-sm flex flex-col flex-1 overflow-hidden">
                <!-- Header Card (bg-biru2) -->
                <div class="bg-biru2 px-6 py-4 flex flex-row gap-4 items-center">
                    <div class="flex flex-col text-left">
                        <span class="text-white font-extrabold text-lg">Penilaian & Catatan Evaluator</span>
                        <span class="text-xs text-white/80">Berikut adalah nilai visitasi dan catatan ulasan dari seluruh petugas evaluator.</span>
                    </div>
                </div>

                <div class="p-6 flex flex-col gap-6 flex-1 justify-between text-left">
                    <div>
                        <!-- List Evaluator Inputs -->
                        <div class="flex flex-col gap-4">
                            @foreach ($visitasi->evaluators as $index => $ev)
                                @php
                                    $isCurrent = (string)Auth::id() === (string)($ev->id_user ?? '');
                                @endphp
                                <div
                                    class="p-5 rounded-2xl border transition-all duration-200 {{ ($isCurrent && $canEdit) ? 'bg-white border-biru4 shadow-sm' : 'bg-[#F4F7FC]/80 border-slate-200/80' }}">
                                    <!-- Header: Nama Evaluator -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold uppercase tracking-wider {{ ($isCurrent && $canEdit) ? 'text-biru2' : 'text-slate-400' }}">
                                            Penilai {{ $index + 1 }}: <span class="font-extrabold {{ ($isCurrent && $canEdit) ? 'text-biru1' : 'text-slate-500' }} capitalize">{{ ucwords($ev->user->name ?? '-') }}</span>
                                            @if ($ev->is_draft && $ev->score !== null)
                                                <span class="text-oren2 font-black text-xs ml-1">(Draft)</span>
                                            @endif
                                        </span>
                                        <div class="flex items-center gap-2">
                                            @if ($ev->user && $ev->user->hasRole('penilai_eksternal'))
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-bold uppercase tracking-wider">
                                                    Penilai Eksternal
                                                </span>
                                            @endif
                                            @if ($isCurrent)
                                                <span
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-hijau2/10 text-hijau text-xs font-bold uppercase tracking-wider">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-hijau animate-pulse"></span>
                                                    Anda
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                                        <!-- NILAI -->
                                        <div class="flex flex-col items-center shrink-0 relative" x-data="{ errorState: false }">
                                            <input type="number"
                                                @if ($isCurrent && $canEdit) name="nilai_visitasi"
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
                                                    disabled @endif
                                                value="{{ $ev->score }}" min="0" max="100"
                                                step="0.01" placeholder="-"
                                                class="w-24 h-14 text-center text-2xl font-extrabold rounded-xl border {{ ($isCurrent && $canEdit) ? 'border-biru4 bg-white text-biru1 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500' }} transition" />
                                            <label
                                                class="text-xs font-bold uppercase tracking-wider mt-1.5 {{ ($isCurrent && $canEdit) ? 'text-biru4' : 'text-slate-400' }}">Nilai</label>
                                            <div x-show="errorState" x-cloak class="absolute mt-16 text-center w-40 text-xs font-bold text-red-500 bg-red-50 border border-red-200 rounded-lg px-2 py-1 shadow-sm z-50">
                                                Nilai max 100!
                                            </div>
                                        </div>

                                        <!-- CATATAN -->
                                        <div class="flex flex-col flex-1 min-w-0 w-full">
                                            <textarea
                                                @if ($isCurrent && $canEdit) name="catatan_visitasi"
                                                    required
                                                    placeholder="Tuliskan catatan dan feedback visitasi di sini..."
                                                @else
                                                    disabled
                                                    placeholder="Belum memberikan catatan..." @endif
                                                rows="3"
                                                class="w-full rounded-xl border {{ ($isCurrent && $canEdit) ? 'border-abubiru bg-white text-biru1 placeholder-biru4/60 focus:border-biru2 focus:ring-2 focus:ring-biru2/20 focus:outline-none' : 'border-slate-200/80 bg-white text-slate-500 placeholder-slate-300' }} px-4 py-3 text-xs sm:text-sm font-medium resize-none transition leading-relaxed min-h-20">{{ $ev->note }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Overall Average: hanya tampil jika semua evaluator sudah menilai (finalized) -->
                    @php
                        $scores = $visitasi->evaluators->pluck('score')->filter(fn($s) => $s !== null);
                        $avg = $scores->count() > 0 ? $scores->avg() : null;
                    @endphp
                    @if ($isFinalized)
                        <!-- Divider -->
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
                    @elseif ($avg !== null)
                        <!-- Divider -->
                        <hr class="border-abubiru my-1 mt-4">

                        <div
                            class="rounded-xl bg-birumuda border border-biru5 px-6 py-5 flex items-center justify-between shadow-sm mt-4">
                            <div>
                                <div class="font-bold text-biru6 text-sm mb-0.5">Rata-rata Nilai Visitasi Sementara
                                </div>
                                <p class="text-xs text-biru3">Dihitung dari petugas yang sudah memberikan nilai.</p>
                            </div>
                            <div class="text-3xl font-black text-biru1">
                                {{ number_format($avg, 2) }}
                            </div>
                        </div>
                    @endif
                </div>

                @if (!$isMe && Auth::user()?->role?->nama_role !== 'super_evaluator')
                    <div
                        class="rounded-xl bg-orange-50 border border-orange-200 px-6 py-4 mx-6 mb-6 text-sm text-orange-700 font-semibold text-center">
                        Anda berada dalam Mode Lihat-Saja (Read-only).
                    </div>
                @elseif ($isPastDeadline || $isStagePassed)
                    <div
                        class="rounded-xl bg-rose-50 border border-rose-200 px-6 py-4 mx-6 mb-6 text-sm text-rose-700 font-semibold text-center">
                        Tahapan visitasi telah selesai{{ $periodeEnd ? ' pada tanggal ' . $periodeEnd->translatedFormat('d M Y') : '' }}. Nilai sudah tidak dapat diubah lagi.
                    </div>
                @elseif ($isFinalized)
                    <div
                        class="rounded-xl bg-emerald-50 border border-emerald-200 px-6 py-4 mx-6 mb-6 text-sm text-emerald-700 font-semibold text-center">
                        ✓ Penilaian visitasi telah difinalisasi. Nilai rata-rata:
                        <strong>{{ number_format($visitasi->visit_score, 2) }}</strong>. Tahapan penilaian selesai, nilai tidak dapat diubah lagi.
                    </div>
                @elseif ($hasSubmitted)
                    <div
                        class="rounded-xl bg-emerald-50 border border-emerald-200 px-6 py-4 mx-6 mb-6 text-sm text-emerald-700 font-semibold text-center">
                        ✓ Anda telah men-submit penilaian visitasi. Menunggu evaluator lainnya menyelesaikan penilaian.
                        Nilai masih dapat Anda ubah sebelum batas waktu periode berakhir pada tanggal
                        <strong>{{ $periodeEnd->translatedFormat('d M Y') }}</strong>.
                    </div>
                @elseif (!$canScoreNow)
                    <div
                        class="rounded-xl bg-blue-50 border border-blue-200 px-6 py-4 mx-6 mb-6 text-sm text-blue-700 font-semibold text-center">
                        @if ($isBeforeVisitStart)
                            🕐 Tahapan visitasi belum dibuka. Penilaian baru dapat dilakukan mulai tanggal
                            <strong>{{ $visitStart ? $visitStart->translatedFormat('d F Y') : '-' }}</strong>.
                        @elseif ($visitDate)
                            🕐 Penilaian hanya dapat dilakukan pada atau setelah tanggal kunjungan
                            <strong>{{ $visitDate->translatedFormat('d F Y') }}</strong>.
                        @else
                            🕐 Penilaian baru dapat dilakukan pada atau setelah awal tahapan visitasi
                            <strong>{{ $visitStart ? $visitStart->translatedFormat('d F Y') : '-' }}</strong>.
                        @endif
                        Saat ini belum saatnya mengisi penilaian.
                    </div>
                @endif
            </div>

        </div>

        <!-- Action Buttons -->
        @if ($canEdit)
            <div class="flex justify-end gap-3 mb-6">
                <button type="submit" form="review-visitasi-form" name="action" value="draft"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg border border-biru5 text-biru1 font-semibold bg-biru5/20 hover:bg-biru5/40 hover:shadow-sm active:scale-[0.98] transition shadow-sm text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    Simpan Draft
                </button>
                <button type="submit" form="review-visitasi-form" name="action" value="submit"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg bg-hijau hover:bg-emerald-600 hover:shadow-md hover:brightness-105 active:scale-[0.98] text-white font-semibold transition shadow-sm text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim Hasil Visitasi
                </button>
            </div>
        @endif
    </form>

</x-layouts.app>
