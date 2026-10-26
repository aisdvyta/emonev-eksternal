{{-- Modal Filter Export --}}
<div id="modal-export" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-7 py-5 bg-biru2 text-white flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-bold text-base">Unduh Laporan Excel</h3>
                <p class="text-white/70 text-xs mt-0.5">Pilih filter sebelum mengunduh</p>
            </div>
            <button onclick="document.getElementById('modal-export').classList.add('hidden')"
                class="text-white/70 hover:text-white transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="form-export" method="GET" action="{{ route($routePrefix . '.penilaian.export') }}"
            onsubmit="handleExportSubmit(event, this)" class="flex flex-col flex-1 min-h-0">
            <div class="p-7 space-y-6 overflow-y-auto flex-1">

                {{-- Filter Tahun/Periode --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-biru4 mb-2">Tahun / Periode</label>
                    <select name="id_periode"
                        class="w-full rounded-xl border border-abubiru bg-putihbg px-4 py-2.5 text-sm text-biru1 font-medium focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                        @foreach ($periods as $p)
                            <option value="{{ $p->id_periode }}"
                                {{ $period->id_periode == $p->id_periode ? 'selected' : '' }}>
                                {{ $p->nama ?? $p->tahun }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Kabupaten/Kota --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-biru4">Kabupaten / Kota</label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                            <input type="checkbox" id="check-all-kota" onchange="toggleAllKota(this)"
                                class="w-3.5 h-3.5 rounded accent-biru2" checked>
                            Pilih Semua
                        </label>
                    </div>
                    <!-- Search kota -->
                    <div class="relative mb-2">
                        <input type="text" id="search-kota" placeholder="Cari kab/kota..."
                            oninput="filterKotaList(this.value)"
                            class="w-full rounded-lg border border-abubiru bg-putihbg pl-8 pr-3 py-2 text-sm text-biru1 placeholder-biru4/50 focus:border-biru2 focus:outline-none focus:ring-2 focus:ring-biru2/20">
                        <svg class="w-4 h-4 text-biru4 absolute left-2.5 top-2.5 pointer-events-none" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <!-- List kota -->
                    <div id="list-kota"
                        class="border border-abubiru rounded-xl bg-putihbg max-h-44 overflow-y-auto px-3 py-2 space-y-1">
                        @foreach ($satkers as $satkerItem)
                            <label
                                class="kota-item flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition"
                                data-nama="{{ strtolower($satkerItem->nama_satker) }}">
                                <input type="checkbox" name="kota[]"
                                    value="{{ $satkerItem->nama_satker }}"
                                    class="kota-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                <span
                                    class="text-sm text-biru1 font-medium">{{ $satkerItem->nama_satker }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Filter Skala Penilaian --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-biru4">Skala Penilaian</label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-biru2">
                            <input type="checkbox" id="check-all-predikat" onchange="toggleAllPredikat(this)"
                                class="w-3.5 h-3.5 rounded accent-biru2" checked>
                            Pilih Semua
                        </label>
                    </div>
                    <div class="border border-abubiru rounded-xl bg-putihbg px-3 py-2 space-y-1">
                        @foreach ([
                            'INFORMATIF' => 'Informatif',
                            'MENUJU INFORMATIF' => 'Menuju Informatif',
                            'CUKUP INFORMATIF' => 'Cukup Informatif',
                            'KURANG INFORMATIF' => 'Kurang Informatif',
                            'TIDAK INFORMATIF' => 'Tidak Informatif',
                            'BELUM DINILAI' => 'Belum Dinilai',
                        ] as $val => $lbl)
                            <label
                                class="flex items-center gap-2.5 cursor-pointer hover:bg-biru5/20 rounded-lg px-2 py-1 transition">
                                <input type="checkbox" name="predikat[]" value="{{ $val }}"
                                    class="predikat-checkbox w-3.5 h-3.5 rounded accent-biru2" checked>
                                <span class="text-sm text-biru1 font-medium">{{ $lbl }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-7 py-5 border-t border-abubiru shrink-0">
                <button type="button" onclick="document.getElementById('modal-export').classList.add('hidden')"
                    class="px-5 py-2.5 rounded-xl border border-abubiru text-biru1 font-semibold text-sm bg-white hover:bg-putihbg transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-hijau text-white font-bold text-sm hover:opacity-90 transition shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Excel
                </button>
            </div>
        </form>
    </div>
</div>
