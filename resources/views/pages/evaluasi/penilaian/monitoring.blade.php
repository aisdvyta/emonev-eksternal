@php
    $user = Auth::user();
    $userRole = $user?->role?->nama_role;
    $isExternal = $userRole === 'penilai_eksternal';
    $isSuperEval = $userRole === 'super_evaluator';
@endphp
<x-layouts.app title="Penilaian Akhir - HaloKIP">

    {{-- Alert Messages --}}
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

    {{-- Info Banner for External Evaluator --}}
    @if ($isExternal)
        <div class="hidden sm:flex rounded-xl bg-birumuda border border-biru5 px-6 py-4.5 mb-8 items-center gap-4 shadow-sm">
            <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-bold text-biru6 text-sm mb-0.5">Informasi</div>
                <p class="text-sm text-biru6 font-medium leading-relaxed">Halaman ini menampilkan rekap penilaian akhir seluruh Kabupaten/Kota se-Jawa Timur. Data ini bersifat <strong>view-only</strong> untuk Penilai Eksternal, dan nilai yang belum selesai/final berstatus <strong>Pending</strong>.</p>
            </div>
        </div>
    @endif

    @if ($period)
        {{-- Livewire Penilaian Table --}}
        <livewire:evaluasi.penilaian-monitoring-table :periodeId="$period->id_periode" />
    @else
        <div class="rounded-2xl bg-white border border-abubiru p-8 text-center shadow-sm">
            <svg class="w-12 h-12 text-abubiru mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-base font-bold text-biru1 mb-1">Belum Ada Penilaian Akhir yang Final</h3>
            <p class="text-sm text-biru3">Periode evaluasi saat ini masih dalam proses berjalan. Rekapitulasi penilaian akhir hanya dapat dilihat setelah periode dinyatakan selesai dan nilai telah fiks.</p>
        </div>
    @endif

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            .leaflet-container {
                background: #f8fafc;
            }

            .custom-tooltip {
                background: white !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                padding: 12px 16px !important;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
                color: #0f172a !important;
                opacity: 1 !important;
            }

            .custom-tooltip::before {
                border-top-color: white !important;
            }

            @media print {
                aside, header, footer {
                    display: none !important;
                }
                body, html {
                    overflow: visible !important;
                    height: auto !important;
                }
                .h-screen {
                    height: auto !important;
                }
                main {
                    display: block !important;
                    width: 100% !important;
                    overflow: visible !important;
                }
                main>div {
                    overflow: visible !important;
                    height: auto !important;
                    padding: 1rem 2rem !important;
                }
                .btn-print-hidden {
                    display: none !important;
                }
                * {
                    overflow: visible !important;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script>
            /* ============================================================
             * HELPER: Output canvas to clipboard (no download fallback)
             * ============================================================ */
            function _outputCanvas(canvas, filename, label, btn) {
                console.log('[Salin Gambar] _outputCanvas dipanggil.', {
                    filename: filename,
                    label: label,
                    btn: btn
                });
                canvas.toBlob(function(blob) {
                    if (!blob) {
                        console.log('[Salin Gambar] _outputCanvas - Gagal membuat blob');
                        if (label) label.textContent = 'Gagal!';
                        if (btn) btn.disabled = false;
                        setTimeout(function() {
                            if (label) label.textContent = 'Salin Gambar';
                        }, 2000);
                        return;
                    }
                    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
                        try {
                            console.log('[Salin Gambar] _outputCanvas - Menulis gambar ke clipboard');
                            navigator.clipboard.write([new ClipboardItem({
                                'image/png': blob
                            })]).then(function() {
                                console.log(
                                    '[Salin Gambar] _outputCanvas - Berhasil menyalin gambar ke clipboard');
                                if (label) label.textContent = '\u2713 Tersalin!';
                                if (btn) btn.style.color = '#388E3C';
                                setTimeout(function() {
                                    if (label) label.textContent = 'Salin Gambar';
                                    if (btn) {
                                        btn.style.color = '';
                                        btn.disabled = false;
                                    }
                                }, 2500);
                            }).catch(function(err) {
                                console.warn('[_outputCanvas] clipboard write failed:', err);
                                if (label) label.textContent = 'Gagal salin!';
                                if (btn) btn.disabled = false;
                                setTimeout(function() {
                                    if (label) label.textContent = 'Salin Gambar';
                                }, 2500);
                            });
                        } catch (e) {
                            if (label) label.textContent = 'Gagal!';
                            if (btn) btn.disabled = false;
                            setTimeout(function() {
                                if (label) label.textContent = 'Salin Gambar';
                            }, 2000);
                        }
                    } else {
                        if (label) label.textContent = 'Tidak didukung';
                        if (btn) btn.disabled = false;
                        setTimeout(function() {
                            if (label) label.textContent = 'Salin Gambar';
                        }, 2500);
                    }
                }, 'image/png');
            }
        </script>
        @include('components.charts.penilaian-histogram')
        @include('components.charts.evaluator-map')
    @endpush

</x-layouts.app>
