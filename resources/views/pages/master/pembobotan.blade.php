<x-layouts.app title="Master Pembobotan & KKM - HaloKIP" sidebar="super_evaluator">
    @php
        $rolePrefix = 'super-evaluator';
    @endphp

    <div class="space-y-6" x-data="pembobotanForm()">
        <x-ui.page-header
            title="Master Pembobotan & KKM"
            description="Kelola pembobotan tahapan evaluasi akhir, KKM SAQ, dan bobot per indikator SAQ."
            :breadcrumb="[
                'Master Data' => '#',
                'Master Pembobotan' => '#',
            ]">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
                    {{-- Pilih Periode --}}
                    <form action="{{ route($rolePrefix . '.manage-pembobotan') }}" method="GET" id="periodForm"
                          class="flex items-center gap-2 w-full sm:w-auto">
                        <label for="id_periode" class="text-sm font-semibold text-biru3 shrink-0">Periode:</label>
                        <select name="id_periode" id="id_periode" onchange="document.getElementById('periodForm').submit()"
                                class="bg-white border border-abubiru text-biru1 text-sm font-semibold rounded-xl focus:ring-2 focus:ring-biru1 focus:border-biru1 block p-2.5 shadow-sm w-full sm:w-auto min-w-0">
                            @foreach($periods as $p)
                                <option value="{{ $p->id_periode }}" {{ $period && $period->id_periode == $p->id_periode ? 'selected' : '' }}>
                                    {{ $p->nama }} ({{ $p->tahun }})
                                </option>
                            @endforeach
                        </select>
                    </form>

                    {{-- Tombol kunci: berubah tergantung apakah ada periode aktif --}}
                    @if($hasActivePeriod)
                        <button type="button" disabled
                                title="Pembobotan dikunci karena periode KIP sedang aktif"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-400 cursor-not-allowed shadow-none w-full sm:w-auto shrink-0">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Master Terkunci</span>
                        </button>
                    @else
                        {{-- Tidak ada periode aktif -> tombol Edit bisa diklik (hanya saat belum mode edit) --}}
                        <template x-if="!isEditing">
                            <button type="button" @click="toggleEdit()"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-oren2 hover:bg-oren2/90 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:shadow-lg transition duration-200 w-full sm:w-auto shrink-0 cursor-pointer">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                <span>Edit Pembobotan</span>
                            </button>
                        </template>
                    @endif
                </div>
            </x-slot>
        </x-ui.page-header>

        <!-- Notifications -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Locked State Banner (tampil saat tidak editing dan periode terkunci) -->
        @if($hasActivePeriod)
            <div x-show="!isEditing"
                 class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-sm font-bold text-amber-900">Master Data Dikunci (Read-Only)</span>
                    <p class="text-xs sm:text-sm text-amber-800 font-medium mt-0.5">
                        @if($isPastPeriod)
                            Periode KIP ini telah berakhir ({{ $period->nama }}). Pengubahan master data ditangguhkan untuk menjaga integritas data riwayat evaluasi.
                        @else
                            Terdapat periode KIP yang sedang aktif ({{ $period->nama }}). Pengubahan, penambahan, dan penghapusan master data ditangguhkan selama periode berlangsung.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        <!-- Mode Edit Aktif Banner -->
        <div x-show="isEditing" x-cloak
             class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-4 flex items-center gap-3.5 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <span class="block text-sm font-bold text-amber-900">Mode Edit Aktif</span>
                <p class="text-xs sm:text-sm text-amber-800 font-medium mt-0.5" x-show="canReorderStages">
                    Anda dapat mengubah angka persentase bobot dan menggeser urutan tahapan / indikator. Klik <strong>Simpan</strong> untuk menyimpan perubahan.
                </p>
                <p class="text-xs sm:text-sm text-amber-800 font-medium mt-0.5" x-show="!canReorderStages">
                    Anda dapat mengubah nilai persentase bobot tahapan dan indikator. <strong>Urutan tahapan kegiatan tidak dapat dipindahkan</strong> karena periode evaluasi sedang aktif. Klik <strong>Simpan</strong> untuk menyimpan perubahan.
                </p>
            </div>
        </div>

        <form action="{{ route($rolePrefix . '.manage-pembobotan.save') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="id_periode" value="{{ $period->id_periode ?? '' }}">

            <!-- Card 1: Bobot & Urutan Tahapan Evaluasi Akhir -->
            <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-abubiru bg-biru2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base uppercase font-bold text-white">Bobot & Urutan Tahapan Evaluasi Akhir</h2>
                        <p class="text-xs text-white/80 font-medium mt-0.5">Atur persentase bobot dari 3 tahapan evaluasi (total harus 100%) dan geser posisi tiap tahapan menggunakan tombol panah.</p>
                    </div>
                    <!-- Real-time Stage Weight Validation Badge -->
                    <div class="shrink-0">
                        <span x-show="isStageValid" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-hijau text-white shadow-xs">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Total: <span x-text="stageTotal.toFixed(2)"></span>% &check;
                        </span>
                        <span x-show="!isStageValid" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-merah2 text-white shadow-xs">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Total: <span x-text="stageTotal.toFixed(2)"></span>% (Harus 100%)
                        </span>
                    </div>
                </div>

                {{-- Edit-mode hint --}}
                <div x-show="isEditing" x-cloak class="px-5 pt-4 pb-0">
                    <template x-if="canReorderStages">
                        <p class="text-xs text-biru3 font-medium">
                            Gunakan tombol panah di sudut bawah tiap kartu untuk menggeser urutan tahapan.
                        </p>
                    </template>
                    <template x-if="!canReorderStages">
                        <div class="flex items-center gap-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold">
                            <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Urutan tahapan kegiatan tidak dapat dipindahkan karena periode sedang aktif. Anda tetap dapat mengubah nilai persentase pembobotan tiap tahapan di bawah ini.</span>
                        </div>
                    </template>
                </div>

                {{-- 3 Stage Cards -- rendered in dynamic order via Alpine --}}
                <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <template x-for="(stageKey, idx) in stageOrder" :key="stageKey">
                        <div class="p-5 rounded-2xl border transition-all duration-200 relative flex flex-col justify-between"
                             :class="isEditing ? 'bg-white border-biru4/40 shadow-sm' : 'bg-linear-to-b from-biru5/20 to-white border-abubiru shadow-xs'">
                            <div>
                                {{-- Header row: label + Tahap N badge --}}
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2">
                                        <label class="block text-xs font-black uppercase tracking-wider text-biru1"
                                               :for="'weight_' + stageKey"
                                               x-text="'Bobot ' + stageLabelsShort[stageKey]"></label>
                                    </div>
                                    <span class="text-xs font-extrabold uppercase px-2 py-0.5 rounded-md bg-biru4/10 text-biru4"
                                          x-text="'Tahap ' + (idx + 1)"></span>
                                </div>

                                {{-- Value display (read-only) --}}
                                <template x-if="!isEditing">
                                    <div class="my-2 flex items-baseline gap-1">
                                        <span class="text-3xl sm:text-4xl font-black text-biru1 tracking-tight"
                                              x-text="parseFloat(stageWeightValues[stageKey]).toFixed(0)"></span>
                                        <span class="text-lg font-extrabold text-biru3">%</span>
                                    </div>
                                </template>

                                {{-- Weight input (edit mode) --}}
                                <template x-if="isEditing">
                                    <div class="my-2 relative">
                                        <input type="number" step="0.01" min="0" max="100"
                                               :name="'weight_' + stageKey"
                                               :id="'weight_' + stageKey"
                                               x-model.number="stageWeightValues[stageKey]"
                                               class="w-full pl-3.5 pr-9 py-2.5 text-lg font-black text-biru1 bg-white border-2 border-biru4/30 rounded-xl focus:ring-2 focus:ring-biru4/30 focus:border-biru4 transition shadow-inner">
                                        <span class="absolute right-3 top-3 text-sm font-bold text-biru3">%</span>
                                    </div>
                                </template>

                                <p class="text-xs text-biru3/90 font-medium" x-text="stageDescs[stageKey]"></p>
                            </div>

                            {{-- Shift buttons -- only in edit mode --}}
                            <template x-if="isEditing">
                                <div class="flex items-center justify-between mt-4 pt-3 border-t border-abubiru/40">
                                    <span class="text-xs font-bold text-biru3 uppercase tracking-wide">Geser Urutan</span>
                                    <template x-if="canReorderStages">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                @click="moveStage(idx, 'up')"
                                                :disabled="idx === 0"
                                                :class="idx === 0 ? 'opacity-25 cursor-not-allowed bg-slate-100' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white'"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-biru4/30 text-biru2 font-bold transition shadow-xs"
                                                title="Geser ke kiri">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                @click="moveStage(idx, 'down')"
                                                :disabled="idx === stageOrder.length - 1"
                                                :class="idx === stageOrder.length - 1 ? 'opacity-25 cursor-not-allowed bg-slate-100' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white'"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-biru4/30 text-biru2 font-bold transition shadow-xs"
                                                title="Geser ke kanan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!canReorderStages">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-biru3/80 bg-abubiru/50 px-2.5 py-1 rounded-md">
                                            <svg class="w-3 h-3 text-biru3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <span>Terkunci</span>
                                        </span>
                                    </template>
                                </div>
                            </template>
                            <input type="hidden" name="stage_order[]" :value="stageKey">
                        </div>
                    </template>
                </div>
            </div>

            <!-- Card 2: Pembobotan & Urutan Indikator SAQ -->
            <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-abubiru bg-biru2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base uppercase font-bold text-white">Pembobotan & Urutan Indikator SAQ</h2>
                        <p class="text-xs text-white/80 font-medium mt-0.5">Atur kontribusi persen bobot dan urutan dari masing-masing indikator pada periode ini. Total harus tepat 100%.</p>
                    </div>
                    <!-- Real-time Indicator Weight Validation Badge -->
                    <div class="shrink-0">
                        <span x-show="isIndicatorValid" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-hijau text-white shadow-xs">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Total: <span x-text="indicatorTotal.toFixed(2)"></span>% &check;
                        </span>
                        <span x-show="!isIndicatorValid" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-merah2 text-white shadow-xs">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Total: <span x-text="indicatorTotal.toFixed(2)"></span>% (Harus 100%)
                        </span>
                    </div>
                </div>

                <!-- KKM SAQ Setting Banner inside Card 2 -->
                <div class="px-5 sm:px-6 py-4 bg-biruinfo/10 border-b border-abubiru/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-biru1 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
                            KKM
                        </div>
                        <div>
                            <label for="threshold_saq" class="block text-xs font-black uppercase tracking-wider text-biru1">Nilai Ambang Batas (KKM SAQ Per Indikator)</label>
                            <p class="text-xs text-biru3 font-medium">Batas minimal kelulusan per indikator agar peserta berlanjut ke tahap berikutnya.</p>
                        </div>
                    </div>
                    <div class="relative w-full sm:w-36 shrink-0">
                        <input type="number" step="0.01" min="0" max="100" name="threshold_saq" id="threshold_saq"
                               value="{{ old('threshold_saq', $pembobotan->threshold_saq ?? 80) }}" :disabled="!isEditing"
                               :class="!isEditing ? 'bg-white text-biru1 font-extrabold cursor-not-allowed border-abubiru' : 'bg-white font-extrabold text-biru1 focus:ring-2 focus:ring-biru1 focus:border-biru1'"
                               class="w-full px-4 py-2.5 text-center text-base font-black rounded-xl border transition shadow-inner">
                    </div>
                </div>

                {{-- Edit-mode hint for indicator ordering --}}
                <div x-show="isEditing" x-cloak class="px-5 pt-4 pb-0">
                    <p class="text-xs text-biru3 font-medium">
                        Gunakan tombol panah atas/bawah di kolom <strong>Ubah</strong> untuk mengatur urutan indikator pada periode ini.
                    </p>
                </div>

                <!-- Desktop Table of Indicators -->
                <div class="hidden md:block overflow-x-auto p-5">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-biru5/70 border-b border-abubiru">
                                <th class="px-4 py-3.5 text-center text-xs font-extrabold uppercase tracking-wider text-biru1 w-16">NO</th>
                                <th x-show="isEditing" class="px-3 py-3.5 text-center text-xs font-extrabold uppercase tracking-wider text-biru1 w-24">UBAH</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-biru1">NAMA INDIKATOR</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-biru1">DESKRIPSI</th>
                                <th class="px-5 py-3.5 text-center text-xs font-extrabold uppercase tracking-wider text-biru1 w-52">BOBOT INDIKATOR (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-abubiru/60">
                            <template x-for="(indId, idx) in indicatorOrder" :key="indId">
                                <tr class="hover:bg-birumuda/30 transition-colors duration-150">
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex w-7 h-7 rounded-xl bg-biru5/60 text-biru1 font-black items-center justify-center text-xs shadow-2xs"
                                              x-text="idx + 1">
                                        </span>
                                    </td>
                                    <td x-show="isEditing" class="px-3 py-2.5 text-center">
                                        <div class="inline-flex flex-col items-center justify-center gap-1">
                                            <button type="button"
                                                @click="moveIndicator(idx, 'up')"
                                                :disabled="idx === 0"
                                                :class="idx === 0 ? 'opacity-25 cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white text-biru2'"
                                                class="inline-flex items-center justify-center w-7 h-6 rounded-md border border-biru4/30 transition shadow-2xs"
                                                title="Pindah ke atas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                @click="moveIndicator(idx, 'down')"
                                                :disabled="idx === indicatorOrder.length - 1"
                                                :class="idx === indicatorOrder.length - 1 ? 'opacity-25 cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white text-biru2'"
                                                class="inline-flex items-center justify-center w-7 h-6 rounded-md border border-biru4/30 transition shadow-2xs"
                                                title="Pindah ke bawah">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-black text-biru1 text-sm">
                                        <span x-text="indicatorsData[indId]?.nama_indikator"></span>
                                        <input type="hidden" name="indicator_order[]" :value="indId">
                                    </td>
                                    <td class="px-5 py-4 text-xs text-biru3 font-medium leading-relaxed" x-text="indicatorsData[indId]?.deskripsi_indikator || '-'"></td>
                                    <td class="px-5 py-4 text-center">
                                        <div x-show="isEditing" class="relative max-w-40 mx-auto">
                                            <input type="number" step="0.01" min="0" max="100"
                                                   :name="'indicator_weights[' + indId + ']'"
                                                   x-model.number="indWeights[indId]"
                                                   class="w-full pl-3 pr-7 py-2 text-center text-sm font-bold rounded-xl border border-abubiru bg-white text-biru1 focus:ring-2 focus:ring-biru1 focus:border-biru1 transition shadow-inner">
                                            <span class="absolute right-2.5 top-2.5 text-xs font-bold text-biru3">%</span>
                                        </div>
                                        <div x-show="!isEditing">
                                            <input type="hidden"
                                                   :name="'indicator_weights[' + indId + ']'"
                                                   x-model.number="indWeights[indId]">
                                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-biru5/50 border border-biru4/30 shadow-2xs">
                                                <span class="text-xs font-black text-biru1" x-text="(indWeights[indId] || 0).toFixed(2) + '%'"></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-linear-to-r from-biru5/30 via-birumuda/40 to-biru5/30 border-t-2 border-abubiru">
                            <tr>
                                <td :colspan="isEditing ? 4 : 3" class="px-5 py-4 text-right text-xs font-black text-biru1 uppercase tracking-wider">TOTAL BOBOT INDIKATOR SAQ:</td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="isIndicatorValid ? 'text-hijau bg-hijau/10 border-hijau/30' : 'text-merah2 bg-merah2/10 border-merah2/30'"
                                          class="inline-block px-4 py-1.5 rounded-full border text-sm font-black shadow-2xs">
                                        <span x-text="indicatorTotal.toFixed(2)"></span>%
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Mobile Indicator Cards --}}
                <div class="block md:hidden p-4 space-y-3 bg-putihbg/30">
                    <template x-for="(indId, idx) in indicatorOrder" :key="indId">
                        <div class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex w-7 h-7 rounded-xl bg-biru5/70 text-biru1 font-black items-center justify-center text-xs shrink-0"
                                          x-text="idx + 1">
                                    </span>
                                    <div class="font-black text-biru1 text-sm leading-snug" x-text="indicatorsData[indId]?.nama_indikator"></div>
                                </div>
                                <div x-show="isEditing" class="flex flex-col items-center gap-1">
                                    <button type="button"
                                        @click="moveIndicator(idx, 'up')"
                                        :disabled="idx === 0"
                                        :class="idx === 0 ? 'opacity-25 cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white text-biru2'"
                                        class="w-7 h-6 rounded-md border border-biru4/30 flex items-center justify-center transition shadow-2xs"
                                        title="Pindah ke atas">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                        @click="moveIndicator(idx, 'down')"
                                        :disabled="idx === indicatorOrder.length - 1"
                                        :class="idx === indicatorOrder.length - 1 ? 'opacity-25 cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:bg-biru1 hover:text-white hover:border-biru1 cursor-pointer bg-white text-biru2'"
                                        class="w-7 h-6 rounded-md border border-biru4/30 flex items-center justify-center transition shadow-2xs"
                                        title="Pindah ke bawah">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <template x-if="indicatorsData[indId]?.deskripsi_indikator">
                                <p class="text-xs text-biru3 font-medium leading-relaxed border-t border-abubiru/30 pt-2"
                                   x-text="indicatorsData[indId].deskripsi_indikator">
                                </p>
                            </template>

                            <div class="flex items-center justify-between border-t border-abubiru/30 pt-2.5">
                                <span class="text-xs font-bold text-biru4 uppercase tracking-wider">Bobot Indikator:</span>
                                <div x-show="isEditing">
                                    <div class="relative w-32">
                                        <input type="number" step="0.01" min="0" max="100"
                                               :name="'indicator_weights[' + indId + ']'"
                                               x-model.number="indWeights[indId]"
                                               class="w-full pl-3 pr-7 py-1.5 text-center text-xs font-bold rounded-lg border border-abubiru bg-white text-biru1 focus:ring-2 focus:ring-biru1 focus:border-biru1 transition">
                                        <span class="absolute right-2.5 top-1.5 text-xs font-bold text-biru3">%</span>
                                    </div>
                                </div>
                                <div x-show="!isEditing">
                                    <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-black bg-biru5/50 text-biru1 border border-biru4/30">
                                        <span x-text="(indWeights[indId] || 0).toFixed(2)"></span>%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Total Mobile Footer Card -->
                    <div class="bg-white rounded-xl border border-abubiru p-4 flex items-center justify-between text-xs font-bold shadow-xs">
                        <span class="text-biru1 font-black uppercase tracking-wide">Total Bobot SAQ:</span>
                        <span :class="isIndicatorValid ? 'text-hijau bg-hijau/10 border-hijau/30' : 'text-merah2 bg-merah2/10 border-merah2/30'"
                              class="inline-block px-4 py-1.5 rounded-full border text-xs font-black shadow-2xs">
                            <span x-text="indicatorTotal.toFixed(2)"></span>%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Submit Button Area (Hanya tampil saat mode edit aktif) -->
            <div x-show="isEditing" x-cloak class="flex flex-col sm:flex-row sm:items-center justify-between bg-white p-5 rounded-2xl border border-abubiru shadow-md gap-4">
                <div class="text-xs">
                    <div>
                        <span x-show="isAllValid" class="text-hijau font-bold flex items-center gap-1.5 text-sm">
                            <svg class="w-5 h-5 shrink-0 text-hijau" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Semua total bobot valid (100%). Siap disimpan.
                        </span>
                        <span x-show="!isAllValid" class="text-merah2 font-bold flex items-center gap-1.5 text-sm">
                            <svg class="w-5 h-5 shrink-0 text-merah2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Pastikan Total Tahapan = 100% dan Total Indikator = 100% sebelum menyimpan.
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <button type="button" @click="toggleEdit()"
                            class="flex-1 sm:flex-initial justify-center px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 transition duration-200 text-center cursor-pointer">
                        Batal
                    </button>

                    <button type="submit" :disabled="!isAllValid"
                            :class="isAllValid ? 'bg-biru1 hover:bg-biru2 text-white shadow-md hover:shadow-lg' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            class="flex-1 sm:flex-initial justify-center px-6 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition duration-200 flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan Master Pembobotan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
    function pembobotanForm() {
        return {
            isEditing: {{ $errors->any() ? 'true' : 'false' }},
            canReorderStages: {{ $canReorderStages ? 'true' : 'false' }},
            indWeights: {
                @foreach($indicators as $ind)
                    {{ $ind->id_indicators }}: {{ old('indicator_weights.' . $ind->id_indicators, $savedIndWeights[$ind->id_indicators] ?? ($pembobotan->bobot_indikator[$ind->id_indicators] ?? 0)) }},
                @endforeach
            },

            // Stage ordering & weights (unified)
            stageOrder: {!! json_encode(old('stage_order') ?? $stageOrder) !!},
            stageWeightValues: {
                saq:       {{ old('weight_saq',       $pembobotan->weight_saq       ?? 30) }},
                visitasi:  {{ old('weight_visitasi',  $pembobotan->weight_visitasi  ?? 40) }},
                wawancara: {{ old('weight_wawancara', $pembobotan->weight_wawancara ?? 30) }},
            },
            stageLabels: {
                saq:       'Kuesioner SAQ',
                visitasi:  'Visitasi dan Verifikasi',
                wawancara: 'Presentasi dan Wawancara',
            },
            stageLabelsShort: {
                saq:       'SAQ',
                visitasi:  'Visitasi dan Verifikasi',
                wawancara: 'Presentasi dan Wawancara',
            },
            stageDescs: {
                saq:       'Penilaian kuesioner mandiri badan publik.',
                visitasi:  'Verifikasi faktual & kunjungan lapangan.',
                wawancara: 'Presentasi badan publik & wawancara pimpinan.',
            },

            // Indicator ordering & metadata
            indicatorOrder: {!! json_encode(old('indicator_order') ?? $indicatorOrder) !!},
            indicatorsData: {!! json_encode($indicators->keyBy('id_indicators')) !!},

            moveStage(idx, direction) {
                if (!this.canReorderStages) return;
                const arr = [...this.stageOrder];
                if (direction === 'up' && idx > 0) {
                    [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
                } else if (direction === 'down' && idx < arr.length - 1) {
                    [arr[idx], arr[idx + 1]] = [arr[idx + 1], arr[idx]];
                }
                this.stageOrder = arr;
            },

            moveIndicator(idx, direction) {
                const arr = [...this.indicatorOrder];
                if (direction === 'up' && idx > 0) {
                    [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
                } else if (direction === 'down' && idx < arr.length - 1) {
                    [arr[idx], arr[idx + 1]] = [arr[idx + 1], arr[idx]];
                }
                this.indicatorOrder = arr;
            },

            toggleEdit() {
                this.isEditing = !this.isEditing;
            },

            get stageTotal() {
                let total = 0;
                for (let k in this.stageWeightValues) {
                    total += parseFloat(this.stageWeightValues[k]) || 0;
                }
                return total;
            },

            get isStageValid() {
                return Math.abs(this.stageTotal - 100.0) < 0.01;
            },

            get indicatorTotal() {
                let total = 0;
                for (let id in this.indWeights) {
                    total += parseFloat(this.indWeights[id]) || 0;
                }
                return total;
            },

            get isIndicatorValid() {
                return Math.abs(this.indicatorTotal - 100.0) < 0.01;
            },

            get isAllValid() {
                return this.isStageValid && this.isIndicatorValid;
            }
        };
    }
    </script>
</x-layouts.app>
