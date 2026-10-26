{{-- Modal Riwayat Komentar --}}
<div id="modal-riwayat-komentar" wire:key="modal-riwayat-{{ $period->id_periode }}"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[85vh] border border-abubiru animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4.5 bg-biru2 text-white flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <svg class="w-4.5 h-4.5 text-kuning1" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Catatan Evaluasi
                </h3>
                <p class="text-white/75 text-xs mt-0.5">Daftar catatan & arahan yang pernah disimpan</p>
            </div>
            <button onclick="document.getElementById('modal-riwayat-komentar').classList.add('hidden')"
                class="text-white/70 hover:text-white transition p-1.5 rounded-lg hover:bg-white/10 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-3.5 divide-y divide-slate-100">
            @forelse ($komentars as $kom)
                <div class="pt-3.5 first:pt-0">
                    <div class="rounded-xl border border-abubiru/60 p-4 bg-slate-50/70 hover:bg-slate-50 transition">
                        <p class="text-xs sm:text-sm text-biru1 font-medium leading-relaxed whitespace-pre-line">
                            "{{ $kom->komentar }}"
                        </p>
                        <div
                            class="mt-3 pt-2.5 border-t border-abubiru/40 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($kom->user?->nama_user ?? 'U') }}&background=f08c1a&color=fff"
                                    class="w-5 h-5 rounded-full ring-1 ring-white" alt="avatar">
                                <span class="text-xs font-bold text-biru2">{{ $kom->user?->nama_user ?? '-' }}</span>
                            </div>
                            <span
                                class="text-xs font-semibold text-biru3 bg-white px-2 py-0.5 rounded-md border border-abubiru/50">
                                {{ \Carbon\Carbon::parse($kom->created_at)->format('d M Y, H:i') }} WIB
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <div
                        class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-biru3 mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-biru3">Belum ada riwayat catatan sebelumnya.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Row 1: Catatan Super Evaluator (Executive Quote Card) -->
<div id="card-catatan-kabag" wire:key="card-catatan-{{ $period->id_periode }}"
    class="rounded-2xl bg-kuning2/30 border border-kuning2 shadow-xs mb-6 relative overflow-hidden flex flex-col lg:flex-row transition-all duration-200"
    x-data="{
        isEditing: false,
        commentValue: @js($latestKabagKomentar ? $latestKabagKomentar->komentar : '')
    }">

    <!-- Trophy Illustration Side (Desktop) -->
    <div
        class="hidden lg:flex lg:w-48 xl:w-56 items-center justify-center shrink-0 pl-5 py-4 relative overflow-hidden border-r border-kuning2/40">
        <img src="{{ asset('images/catatanKabag.svg') }}" alt="Catatan"
            class="h-32 sm:h-36 xl:h-40 max-h-40 w-auto object-contain transform -rotate-6 filter drop-shadow-sm hover:scale-105 transition-transform duration-300">
    </div>

    <!-- Catatan Content Side -->
    <div class="flex-1 p-4 sm:p-6 flex flex-col justify-between">
        <div class="w-full">
            <!-- Header Badge & Category -->
            <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                <div
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-kuning2/70 border border-kuning1/50 text-biru1 text-xs font-extrabold shadow-2xs tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-kuning1 animate-pulse"></span>
                    <span>Catatan Penilaian Akhir Periode {{ $period->tahun }}</span>
                </div>
            </div>

            {{-- View Mode: Quote Content --}}
            <template x-if="!isEditing">
                <div class="relative my-2.5 py-0.5">
                    <p
                        class="text-xs sm:text-sm text-biru1 leading-relaxed font-semibold italic whitespace-pre-line">
                        "{{ $latestKabagKomentar ? $latestKabagKomentar->komentar : 'Belum ada catatan atau rekomendasi dari Super Evaluator untuk periode ini.' }}"
                    </p>
                </div>
            </template>

            {{-- Edit Mode Form (hanya untuk super_evaluator) --}}
            @role('super_evaluator')
                <template x-if="isEditing">
                    <form
                        action="{{ route('super-evaluator.penilaian.komentar', ['id' => \App\Helpers\SecureId::encrypt($period->id_periode)]) }}"
                        method="POST" class="space-y-3 mt-2">
                        @csrf
                        <textarea name="komentar" rows="3" x-model="commentValue"
                            placeholder="Tuliskan catatan evaluasi atau rekomendasi untuk periode ini..."
                            class="w-full rounded-xl border border-abubiru px-3.5 py-2.5 text-xs sm:text-sm font-semibold leading-relaxed transition bg-white resize-none focus:outline-none focus:ring-2 focus:ring-biru2/20 focus:border-biru2 shadow-inner"
                            required></textarea>
                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit"
                                class="px-4 py-2 text-white bg-biru1 hover:bg-biru2 rounded-xl text-xs font-bold transition shadow-sm hover:shadow flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Simpan Catatan</span>
                            </button>
                            <button type="button" @click="isEditing = false; commentValue = @js($latestKabagKomentar ? $latestKabagKomentar->komentar : '')"
                                class="px-4 py-2 text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                                Batal
                            </button>
                        </div>
                    </form>
                </template>
            @endrole
        </div>

        <!-- Footer: Profil & Tombol Aksi -->
        <div class="mt-3.5 pt-3 border-t border-amber-200/60 flex items-center justify-between flex-wrap gap-2.5">
            <!-- Avatar & Role info -->
            <div class="flex items-center gap-2.5 min-w-0">
                <div
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-full overflow-hidden shrink-0">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($latestKabagKomentar?->user?->nama_user ?? 'Super Evaluator') }}&background=f08c1a&color=fff"
                        alt="avatar" class="w-full h-full object-cover">
                </div>
                <div class="min-w-0">
                    <div class="font-extrabold text-biru1 text-xs sm:text-sm truncate">
                        {{ $latestKabagKomentar?->user?->nama_user ?? 'Super Evaluator' }}
                    </div>
                    <div class="text-xs text-biru3 font-bold uppercase tracking-wider truncate">
                        {{ $latestKabagKomentar?->user?->role?->nama_role === 'super_evaluator' ? 'Super Evaluator BPS Provinsi' : 'Evaluator BPS Provinsi' }}
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 ml-auto shrink-0">
                @if ($komentars && $komentars->count() > 0)
                    <button type="button"
                        onclick="document.getElementById('modal-riwayat-komentar').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-abubiru/80 bg-white hover:bg-slate-50 text-xs font-bold text-biru2 transition shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-biru2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Riwayat ({{ $komentars->count() }})</span>
                    </button>
                @endif
                @role('super_evaluator')
                    <template x-if="!isEditing">
                        <button type="button" @click="isEditing = true"
                            class="px-3 py-1.5 text-white bg-oren2 hover:bg-oren rounded-lg text-xs font-extrabold transition shadow-sm hover:shadow flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Ubah Catatan</span>
                        </button>
                    </template>
                @endrole
            </div>
        </div>
    </div>
</div>
