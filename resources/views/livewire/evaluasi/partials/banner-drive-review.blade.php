@php
    $currentUserRole = auth()->user()?->role?->nama_role;
    $canEditDrive = in_array($currentUserRole, ['super_evaluator', 'evaluator', 'admin']);
    $yearLabel = $period->tahun ?? ($selectedTahun ?? date('Y'));
@endphp

@if (!in_array($currentUserRole, ['operator', 'verifikator']))
    <div id="banner-drive-review-saq"
        x-data="{
            isEditing: false,
            initialLink: @js($driveReviewLink ?? ''),
            linkValue: @js($driveReviewLink ?? ''),
            get linkError() {
                if (!this.linkValue || !this.linkValue.trim()) return '';
                return /^https?:\/\//i.test(this.linkValue.trim()) ? '' : 'Isikan Link dengan benar';
            }
        }"
        class="rounded-2xl bg-white border border-abubiru shadow-xs p-5 sm:p-6 transition-all duration-200 text-left">

        {{-- Flash Notification --}}
        @if (session()->has('drive_link_status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                class="mb-4 p-3.5 rounded-xl bg-hijaumuda2   border border-hijaumuda2/20 text-hijau/50 text-xs sm:text-sm font-semibold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-hijau shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('drive_link_status') }}</span>
                </div>
                <button type="button" @click="show = false" class="text-hijau/50 hover:text-hijau cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Header: Icon & Information (Tanpa Tombol Buka/Tutup) --}}
        <div class="flex items-start sm:items-center gap-4 min-w-0">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-biru4 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-6 h-6 sm:w-7 sm:h-7" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3zM8 13h2.55v3h2.9v-3H16l-4-4z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-biru4/10 text-biru4 border border-biru4/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-biru4"></span>
                        GOOGLE DRIVE REVIEW SAQ
                    </span>
                    <template x-if="linkValue && !linkError">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-hijau border border-hijaumuda">
                            <svg class="w-2.5 h-2.5 text-hijau" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                            Link Tersedia
                        </span>
                    </template>
                    <template x-if="!linkValue || linkError">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-600 border border-amber-200">
                            Belum Diatur
                        </span>
                    </template>
                </div>
                <h4 class="text-sm sm:text-base font-extrabold text-biru1 tracking-tight">
                    Dokumen Hasil Review & Kesimpulan SAQ Seluruh Kab/Kota
                </h4>
                <p class="text-xs text-biru3 mt-0.5 leading-relaxed max-w-2xl">
                    Akses kumpulan file review, kesimpulan, dan berita acara penilaian SAQ dalam format .pdf untuk periode <strong>{{ $yearLabel }}</strong>.
                </p>
            </div>
        </div>

        {{-- Form Mode (Selalu Terbuka / Tanpa Buka-Tutup Accordion) --}}
        <div class="mt-4 pt-4 border-t border-slate-100">
            <form wire:submit.prevent="saveDriveReviewLink" @submit="if (linkError) { $event.preventDefault(); return false; } isEditing = false; initialLink = linkValue" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-biru2 mb-1.5">
                        Tautan Google Drive Hasil Review SAQ <span class="text-amber-600">(Periode {{ $yearLabel }})</span>
                    </label>
                    <div class="relative rounded-xl shadow-2xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>

                        <input type="url" wire:model.defer="driveReviewLink" x-model="linkValue" :readonly="!isEditing"
                            placeholder="https://drive.google.com/drive/folders/..."
                            pattern="https?://.+"
                            title="Isikan Link dengan benar"
                            class="w-full pl-10 pr-28 py-2.5 rounded-xl border text-xs sm:text-sm font-semibold transition outline-none"
                            :class="linkError ? 'border-merah1 focus:ring-merah1/20 focus:border-merah1 text-biru1' : (isEditing ? 'bg-white text-biru1 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-biru2/20 focus:border-biru2 border-abubiru' : 'bg-slate-100/60 text-biru3 cursor-not-allowed border-abubiru')" />

                        <!-- Tombol Buka Link di kanan dalam input -->
                        <template x-if="linkValue && !linkError">
                            <div class="absolute inset-y-0 right-1.5 flex items-center">
                                <a :href="linkValue" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-birumuda hover:bg-birumuda/80 text-biru2 border border-abubiru text-xs font-bold transition shadow-2xs cursor-pointer select-none"
                                    title="Buka Link Google Drive">
                                    <span>Buka Link</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- Frontend Validation Warning -->
                    <div x-show="linkError" class="text-xs font-bold text-merah1 mt-1.5 flex items-center gap-1.5" x-cloak>
                        <svg class="w-3.5 h-3.5 shrink-0 text-merah1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span x-text="linkError"></span>
                    </div>

                    <!-- Backend Validation Error -->
                    @error('driveReviewLink')
                        <div class="text-xs font-bold text-merah1 mt-1.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 text-merah1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <p class="text-[11px] text-biru3 mt-1.5">
                        Masukkan link folder Google Drive publik atau yang dapat diakses oleh tim dan penilai eksternal untuk menyimpan rekap kesimpulan evaluasi SAQ per Kabupaten/Kota.
                    </p>
                </div>

                {{-- Action Buttons untuk Evaluator / Super Evaluator / Admin (Rata Kanan seperti Manage Panduan) --}}
                @if ($canEditDrive)
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        {{-- Mode Belum Edit: Tampilkan tombol Ubah Tautan di sebelah kanan --}}
                        <template x-if="!isEditing">
                            <button type="button" @click="isEditing = true"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-kuning1 hover:bg-kuning2 text-white text-xs sm:text-sm font-bold shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Ubah Tautan</span>
                            </button>
                        </template>

                        {{-- Mode Sedang Edit: Tampilkan tombol Batal & Simpan Tautan di sebelah kanan --}}
                        <template x-if="isEditing">
                            <div class="flex items-center justify-end gap-2.5">
                                <button type="button" @click="isEditing = false; linkValue = initialLink"
                                    class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-600 text-xs sm:text-sm font-bold transition-all duration-200 shadow-xs cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" wire:loading.attr="disabled" :disabled="!!linkError"
                                    :class="linkError ? 'opacity-50 cursor-not-allowed hover:translate-y-0' : 'hover:-translate-y-0.5 cursor-pointer'"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-biru1 hover:bg-biru2 text-white text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span wire:loading.remove wire:target="saveDriveReviewLink">Simpan Tautan</span>
                                    <span wire:loading wire:target="saveDriveReviewLink">Menyimpan...</span>
                                </button>
                            </div>
                        </template>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endif
