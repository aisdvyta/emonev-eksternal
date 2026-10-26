@props(['title' => 'Detail Jadwal Kegiatan'])

{{-- Modal Detail Jadwal (Google Calendar Integration) --}}
<div x-show="showDetailModal" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display:none" x-cloak>
    <!-- Overlay -->
    <div class="absolute inset-0 bg-biru1/40 backdrop-blur-sm" @click="showDetailModal = false"></div>
    <!-- Modal Content -->
    <div x-show="showDetailModal" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md rounded-2xl bg-white border border-abubiru shadow-2xl overflow-hidden">
        <div class="px-6 py-4.5 border-b border-abubiru/30 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-birumuda flex items-center justify-center text-biru2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-biru1">{{ $title }}</h3>
            </div>
            <button @click="showDetailModal = false"
                class="w-8 h-8 rounded-lg hover:bg-abubiru/20 flex items-center justify-center text-biru4 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-6 py-6 space-y-4">
            <div class="space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kabupaten / Kota</div>
                <div class="text-base font-extrabold text-biru1 animate-pulse" x-show="!detailEvent.title">&nbsp;</div>
                <div class="text-base font-extrabold text-biru1" x-show="detailEvent.title"
                    x-text="detailEvent.title"></div>
            </div>
            <div class="space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Waktu Pelaksanaan</div>
                <div class="text-sm font-semibold text-biru2" x-text="detailEvent.waktu"></div>
            </div>

            <div class="space-y-1" x-show="detailEvent.petugas">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Petugas</div>
                <div class="text-sm font-semibold text-biru2" x-text="detailEvent.petugas"></div>
            </div>

            <div class="space-y-1" x-show="detailEvent.link">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Link Meeting</div>
                <div class="text-sm font-semibold text-biru2 pt-0.5">
                    <a :href="detailEvent.link" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-biru4 hover:underline flex items-center gap-1 w-fit">
                        <span>Gabung Meeting</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</div>
                <div class="pt-0.5">
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide"
                        :class="{
                            'bg-hijau2/10 text-hijau': detailEvent.status === 'selesai',
                            'bg-biru4/10 text-biru4': detailEvent.status === 'aktif',
                            'bg-abubiru text-biru4': detailEvent.status === 'belum'
                        }"
                        x-text="detailEvent.status">
                    </span>
                </div>
            </div>
        </div>
        <div class="px-6 py-4.5 border-t border-abubiru/30 flex items-center justify-end gap-3 bg-slate-50">
            <button @click="showDetailModal = false"
                class="px-4.5 py-2.5 rounded-xl border border-abubiru text-xs font-bold text-biru3 hover:bg-abubiru/20 transition">Tutup</button>
            <a :href="detailEvent.gcalUrl" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-2 px-4.5 py-2.5 rounded-xl bg-[#4285F4] text-xs font-bold text-white shadow-md hover:bg-[#357ae8] transition">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                </svg>
                <span>+ Google Calendar</span>
            </a>
        </div>
    </div>
</div>
