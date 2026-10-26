<x-layouts.app title="Review Kuesioner SAQ - HaloKIP">
    <div class="space-y-6">
        <x-ui.page-header title="Review Kuesioner SAQ"
            description="Pantau visualisasi skor per indikator, peta sebaran lolos/gugur SAQ, dan arsip Google Drive review SAQ seluruh Kabupaten/Kota."
            :breadcrumb="[
                'Monitoring Kegiatan' => '#',
                'Kuesioner SAQ' => route('penilai-eksternal.monitor-kues'),
            ]">
        </x-ui.page-header>

        {{-- Info Banner for External Evaluator --}}
        <div class="hidden sm:flex rounded-xl bg-birumuda border border-biru5 px-6 py-4 items-center gap-4 shadow-2xs">
            <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-bold text-biru6 text-xs sm:text-sm mb-0.5">Informasi Penilai Eksternal</div>
                <p class="text-xs sm:text-sm text-biru6 font-medium leading-relaxed">
                    Halaman ini menampilkan visualisasi skor capaian SAQ per indikator, peta sebaran kelulusan KKM SAQ, serta berkas laporan Google Drive Review SAQ untuk penilai eksternal.
                </p>
            </div>
        </div>

        {{-- Livewire Saq Review Section --}}
        <livewire:evaluasi.saq-review-section :periodeId="$period?->id_periode" />
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    @endpush
</x-layouts.app>
