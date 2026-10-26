@props([
    'action' => '',
    'templateUrl' => '',
    'submission' => null,
    'indicators' => [],
])

@php
    $questionsList = [];
    $orderCounter = 1;
    if (!empty($indicators)) {
        foreach ($indicators as $ind) {
            foreach ($ind->subIndikators as $subInd) {
                foreach ($subInd->pertanyaans as $q) {
                    $questionsList[] = [
                        'id_question' => $q->id_question,
                        'id_indicators' => $ind->id_indicators,
                        'no' => $orderCounter,
                        'order_no' => $q->order_no ?? $orderCounter,
                        'indikator' => $ind->nama_indikator,
                        'sub_indikator' => $subInd->nama_sub_indikator,
                        'pertanyaan' => $q->ques_text,
                    ];
                    $orderCounter++;
                }
            }
        }
    }
    $questionsJson = json_encode($questionsList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

<div
    x-data="importJawabanSaqHandler('{{ $action }}', '{{ $templateUrl }}', '{{ $submission->id_submission ?? '' }}')"
    x-show="showImportModal"
    x-cloak
    @keydown.escape.window="closeModal()"
    @close-import-modal.window="showImportModal = false"
    class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto p-4"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-biru1/50 backdrop-blur-xs" @click="closeModal()"></div>

    {{-- Panel --}}
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl relative z-10 border border-abubiru/40 overflow-hidden flex flex-col max-h-[90vh]"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        @click.stop
    >
        {{-- Header --}}
        <div class="px-6 py-4 bg-biru1 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-white flex items-center gap-2.5">
                <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Import Jawaban Kuesioner SAQ dari Excel</span>
            </h3>
            <button @click="closeModal()" class="text-white/60 hover:text-white transition rounded-lg p-1 hover:bg-white/10 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 overflow-y-auto flex-1 space-y-5 custom-scrollbar">
            {{-- Instructions and Download --}}
            <div class="rounded-2xl bg-birumuda border border-biru5 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-5 shadow-xs">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0 mt-0.5 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-biru6">Petunjuk Pengisian</h4>
                        <p class="text-xs text-biru6/90 font-medium leading-relaxed">
                            Unduh template Excel resmi terlebih dahulu. Template telah terisi daftar pertanyaan periode aktif.<br>
                            Operator hanya perlu mengisi kolom <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-biru1 font-bold">Jawaban Operator</code> (<span class="font-bold text-hijau">Ya</span> / <span class="font-bold text-slate-600">Tidak</span>), serta <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-biru1 font-bold">Link PPID</code> dan <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-biru1 font-bold">Link Drive</code> (wajib jika memilih Ya). Pertanyaan yang belum diisi dapat dikosongkan dan dilewati saat import.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="downloadTemplate()"
                    class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-biru2 hover:bg-biru1 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Template Excel</span>
                </button>
            </div>

            {{-- Grid: Acuan Format (Kiri) & File Uploader (Kanan) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-stretch">
                {{-- Acuan Format Pengisian --}}
                <div class="border border-abubiru/60 rounded-xl p-4 bg-white space-y-3 flex flex-col justify-between shadow-xs">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-biru1 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-biru3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Acuan Format Pengisian</span>
                        </h4>
                        <span class="text-xs text-biru3 font-semibold bg-slate-100 px-2 py-0.5 rounded border border-slate-200">Pratinjau</span>
                    </div>

                    <div
                        @click="showZoom = true"
                        class="w-full flex-1 rounded-lg overflow-hidden bg-slate-50 border border-dashed border-abubiru flex flex-col items-center justify-center p-2 text-center min-h-42.5 cursor-pointer hover:bg-slate-100/55 transition"
                        title="Klik untuk memperbesar gambar"
                    >
                        <img
                            src="{{ asset('images/contoh_import_jawaban_saq.webp') }}"
                            alt="Contoh Format Pengisian Excel"
                            class="max-w-full max-h-47.5 object-contain rounded-lg border border-abubiru/50 shadow-xs hover:scale-[1.02] transition-transform"
                        >
                    </div>
                </div>

                {{-- File Uploader Zone --}}
                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleFileDrop($event)"
                    :class="{'border-biru2 bg-biru5/10': isDragging, 'border-abubiru bg-white hover:bg-putihbg/30': !isDragging}"
                    class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition flex flex-col items-center justify-center min-h-52.5 shadow-xs"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        type="file"
                        x-ref="fileInput"
                        @change="handleFileSelect($event)"
                        accept=".xlsx, .xls"
                        class="hidden"
                    />

                    <div class="w-12 h-12 rounded-xl bg-biru5 flex items-center justify-center text-biru2 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>

                    <div x-show="!fileName" class="space-y-1">
                        <p class="text-sm font-bold text-biru1">Tarik dan taruh file Excel di sini, atau <span class="text-biru2 hover:underline">pilih file</span></p>
                        <p class="text-xs text-biru4">Mendukung format .xlsx atau .xls (Maks. 5MB)</p>
                    </div>

                    <div x-show="fileName" class="space-y-1">
                        <p class="text-sm font-bold text-biru1" x-text="fileName"></p>
                        <p class="text-xs text-biru3" x-text="fileSize"></p>
                        <button type="button" @click.stop="clearFile()" class="text-xs font-bold text-rose-600 hover:underline mt-2 cursor-pointer">Hapus File</button>
                    </div>
                </div>
            </div>

            {{-- Error Message Alert --}}
            <div x-show="errorMessage" x-cloak class="p-3 bg-red-50 text-rose-600 text-xs font-semibold rounded-lg border border-red-200/50" x-text="errorMessage"></div>

            {{-- Success Message Alert --}}
            <div x-show="successMessage" x-cloak class="p-3 bg-green-50 text-green-700 text-xs font-semibold rounded-lg border border-green-200/50" x-text="successMessage"></div>

            {{-- Preview Area --}}
            <div x-show="parsedData.length > 0" x-cloak class="space-y-3">
                <div class="flex justify-between items-center">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-biru1">
                            Pratinjau Data (<span x-text="parsedData.length"></span> baris)
                        </h4>
                        <p class="text-xs text-biru3/90 font-medium">
                            Klik baris tabel atau tombol <span class="font-bold text-biru1">Detail</span> untuk melihat rincian status validasi setiap pertanyaan
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-1 text-xs font-bold text-green-700 border border-green-200" x-show="stats.valid > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            Valid: <span x-text="stats.valid"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 border border-amber-200" x-show="stats.warning > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Peringatan: <span x-text="stats.warning"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-1 text-xs font-bold text-red-700 border border-red-200" x-show="stats.error > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Error: <span x-text="stats.error"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700 border border-slate-200" x-show="stats.empty > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            Belum Diisi: <span x-text="stats.empty"></span>
                        </span>
                    </div>
                </div>

                {{-- Preview Table --}}
                <div class="border border-abubiru rounded-xl overflow-hidden max-h-85 overflow-y-auto shadow-xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-birumuda/70 text-biru1 uppercase tracking-wider text-[11px] font-bold border-b border-abubiru sticky top-0 z-10 backdrop-blur-xs">
                            <tr>
                                <th class="px-4 py-3 text-center w-24">Status</th>
                                <th class="px-3 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 min-w-[220px]">Pertanyaan</th>
                                <th class="px-4 py-3 text-center w-24">Jawaban</th>
                                <th class="px-4 py-3 min-w-[160px]">Link PPID</th>
                                <th class="px-4 py-3 min-w-[160px]">Link Drive</th>
                                <th class="px-3 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-abubiru/40 bg-white">
                            <template x-for="(row, idx) in parsedData" :key="idx">
                                <tr
                                    @click="toggleRow(idx)"
                                    :class="selectedRowIndex === idx ? 'bg-sky-50 border-l-4 border-biru2' : 'hover:bg-slate-50'"
                                    class="cursor-pointer transition-colors"
                                >
                                    {{-- Status Badge --}}
                                    <td class="px-4 py-2.5 text-center align-top">
                                        <span
                                            x-text="row._status.type === 'warning' ? 'PERINGATAN' : (row._status.type === 'empty' ? 'BELUM DIISI' : row._status.type.toUpperCase())"
                                            :class="{
                                                'bg-red-50 text-rose-700 border border-red-200': row._status.type === 'error',
                                                'bg-amber-50 text-amber-700 border border-amber-200': row._status.type === 'warning',
                                                'bg-slate-100 text-slate-700 border border-slate-200': row._status.type === 'empty',
                                                'bg-green-50 text-green-700 border border-green-200': row._status.type === 'valid'
                                            }"
                                            class="inline-block px-2 py-0.5 rounded font-bold text-[10px]"
                                        ></span>
                                    </td>

                                    {{-- No --}}
                                    <td class="px-3 py-2.5 text-center font-bold text-biru1 align-top">
                                        <span x-text="row.no"></span>
                                    </td>

                                    {{-- Pertanyaan --}}
                                    <td class="px-4 py-2.5 align-top">
                                        <div class="font-medium text-biru1" :title="row.pertanyaan" x-text="row.pertanyaan"></div>
                                        <template x-if="!row._pertanyaanValid">
                                            <span class="block text-[11px] font-bold text-rose-600 mt-0.5">Pertanyaan tidak sesuai template</span>
                                        </template>
                                        <span class="text-[10px] text-biru3/80 block mt-0.5" x-text="row.sub_indikator"></span>

                                        {{-- Inline Detail Callout saat baris dipilih --}}
                                        <div
                                            x-show="selectedRowIndex === idx"
                                            x-cloak
                                            :class="{
                                                'bg-red-50/90 border-red-300 text-red-950': row._status.type === 'error',
                                                'bg-amber-50/90 border-amber-300 text-amber-950': row._status.type === 'warning',
                                                'bg-slate-100/90 border-slate-300 text-slate-900': row._status.type === 'empty',
                                                'bg-green-50/90 border-green-300 text-green-950': row._status.type === 'valid'
                                            }"
                                            class="mt-2.5 p-3 rounded-lg border text-xs space-y-1.5 shadow-xs"
                                        >
                                            <div class="font-bold flex items-center justify-between">
                                                <div class="flex items-center gap-1.5">
                                                    <template x-if="row._status.type === 'error'">
                                                        <span class="text-rose-600 font-bold flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                            Penyebab Error:
                                                        </span>
                                                    </template>
                                                    <template x-if="row._status.type === 'warning'">
                                                        <span class="text-amber-700 font-bold flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                            Catatan Peringatan:
                                                        </span>
                                                    </template>
                                                    <template x-if="row._status.type === 'empty'">
                                                        <span class="text-slate-600 font-bold flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Status Pengisian:
                                                        </span>
                                                    </template>
                                                    <template x-if="row._status.type === 'valid'">
                                                        <span class="text-green-700 font-bold flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Status Valid:
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                            <ul class="list-disc list-inside space-y-1 ml-0.5 leading-relaxed font-medium">
                                                <template x-for="(msg, mIdx) in row._status.messages" :key="mIdx">
                                                    <li x-text="msg"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </td>

                                    {{-- Jawaban --}}
                                    <td class="px-4 py-2.5 text-center align-top">
                                        <template x-if="row.jawaban === 'Ya'">
                                            <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-800 border border-green-300">
                                                Ya
                                            </span>
                                        </template>
                                        <template x-if="row.jawaban === 'Tidak'">
                                            <div>
                                                <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                                    Tidak
                                                </span>
                                                <template x-if="row.link_ppid || row.link_drive">
                                                    <span class="block text-[10px] font-bold text-amber-600 mt-1">
                                                        ⚠️ Cek jawaban
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!row.jawaban && row._status.type === 'empty'">
                                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium text-slate-400 italic">
                                                Belum diisi
                                            </span>
                                        </template>
                                        <template x-if="!row.jawaban && row._status.type === 'error'">
                                            <div>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-rose-700 border border-red-300" x-text="row.jawaban_raw || 'Kosong'"></span>
                                                <span class="block text-[10px] font-bold text-rose-600 mt-0.5">Wajib diisi</span>
                                            </div>
                                        </template>
                                    </td>

                                    {{-- Link PPID --}}
                                    <td class="px-4 py-2.5 align-top">
                                        <div class="truncate max-w-[170px]">
                                            <template x-if="row.link_ppid">
                                                <a :href="row.link_ppid" target="_blank" rel="noopener noreferrer" @click.stop class="text-biru2 hover:underline truncate block" x-text="row.link_ppid"></a>
                                            </template>
                                            <template x-if="!row.link_ppid && row.jawaban === 'Ya'">
                                                <span class="text-rose-600 font-bold block">Wajib diisi</span>
                                            </template>
                                            <template x-if="!row.link_ppid && row.jawaban !== 'Ya'">
                                                <span class="text-slate-400 italic">-</span>
                                            </template>
                                        </div>
                                        <template x-if="row.link_ppid && !row._ppidValid">
                                            <span class="block text-[10px] font-bold text-rose-600 mt-0.5">Isikan Link PPID dengan benar</span>
                                        </template>
                                        <template x-if="row.link_ppid && row.jawaban === 'Tidak'">
                                            <span class="block text-[10px] font-semibold text-amber-600 mt-0.5 leading-tight">
                                                ⚠️ Tidak perlu URL jika 'Tidak'
                                            </span>
                                        </template>
                                    </td>

                                    {{-- Link Drive --}}
                                    <td class="px-4 py-2.5 align-top">
                                        <div class="truncate max-w-[170px]">
                                            <template x-if="row.link_drive">
                                                <a :href="row.link_drive" target="_blank" rel="noopener noreferrer" @click.stop class="text-biru2 hover:underline truncate block" x-text="row.link_drive"></a>
                                            </template>
                                            <template x-if="!row.link_drive && row.jawaban === 'Ya'">
                                                <span class="text-rose-600 font-bold block">Wajib diisi</span>
                                            </template>
                                            <template x-if="!row.link_drive && row.jawaban !== 'Ya'">
                                                <span class="text-slate-400 italic">-</span>
                                            </template>
                                        </div>
                                        <template x-if="row.link_drive && !row._driveValid">
                                            <span class="block text-[10px] font-bold text-rose-600 mt-0.5">Isikan Link Drive dengan benar</span>
                                        </template>
                                        <template x-if="row.link_drive && row.jawaban === 'Tidak'">
                                            <span class="block text-[10px] font-semibold text-amber-600 mt-0.5 leading-tight">
                                                ⚠️ Tidak perlu URL jika 'Tidak'
                                            </span>
                                        </template>
                                    </td>

                                    {{-- Aksi / Toggle Detail --}}
                                    <td class="px-3 py-2.5 text-center align-top">
                                        <button
                                            type="button"
                                            @click.stop="toggleRow(idx)"
                                            :class="selectedRowIndex === idx ? 'bg-biru1 text-white shadow-xs' : 'bg-slate-100 hover:bg-birumuda text-biru1'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold transition cursor-pointer"
                                            title="Klik untuk melihat/menutup rincian status"
                                        >
                                            <span x-text="selectedRowIndex === idx ? 'Tutup' : 'Detail'"></span>
                                            <svg
                                                :class="{'rotate-180': selectedRowIndex === idx}"
                                                class="w-3 h-3 transition-transform duration-200"
                                                fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Detail Status Box (Panel bawah yang tetap sinkron) --}}
                <div x-show="selectedRowIndex !== null && parsedData[selectedRowIndex]"
                     x-transition
                     :class="{
                         'bg-red-50 border-red-200 text-red-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'error',
                         'bg-amber-50 border-amber-200 text-amber-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'warning',
                         'bg-slate-100 border-slate-300 text-slate-900': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'empty',
                         'bg-green-50 border-green-200 text-green-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'valid'
                     }"
                     class="p-4 border rounded-xl text-xs space-y-2 shadow-xs transition-all duration-200">
                    <div class="flex items-center justify-between font-bold text-sm">
                        <div class="flex items-center gap-2">
                            <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'error'">
                                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </template>
                            <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'warning'">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </template>
                            <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'empty'">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'valid'">
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <span>
                                <template x-if="parsedData[selectedRowIndex]?._status.type === 'error'">
                                    <span>Pesan Galat / Error</span>
                                </template>
                                <template x-if="parsedData[selectedRowIndex]?._status.type === 'warning'">
                                    <span>Pesan Peringatan (Perlu Perhatian)</span>
                                </template>
                                <template x-if="parsedData[selectedRowIndex]?._status.type === 'empty'">
                                    <span>Status Pertanyaan Belum Diisi</span>
                                </template>
                                <template x-if="parsedData[selectedRowIndex]?._status.type === 'valid'">
                                    <span>Pesan Validasi</span>
                                </template>
                                – Baris <span x-text="parsedData[selectedRowIndex]?.rowNumber || (selectedRowIndex + 1)"></span>
                                (Pertanyaan No. <span x-text="parsedData[selectedRowIndex]?.no"></span>)
                            </span>
                        </div>
                        <button type="button" @click="selectedRowIndex = null" class="text-xs text-slate-400 hover:text-slate-600 cursor-pointer">
                            Tutup ✕
                        </button>
                    </div>
                    <ul class="list-disc list-inside space-y-1.5 ml-1 leading-relaxed">
                        <template x-for="(msg, mIdx) in parsedData[selectedRowIndex]?._status.messages" :key="mIdx">
                            <li class="font-medium" x-text="msg"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-between items-center px-6 py-4 border-t border-abubiru/50 bg-white shrink-0">
            <div class="text-xs font-medium">
                <template x-if="stats.error > 0">
                    <span class="text-rose-600 font-bold">Harap perbaiki data error sebelum mengimpor.</span>
                </template>
                <template x-if="stats.error === 0 && (stats.valid + stats.warning > 0)">
                    <span class="text-green-600 font-bold" x-text="`Siap di-import! (${stats.valid + stats.warning} jawaban akan disimpan${stats.empty > 0 ? ', ' + stats.empty + ' pertanyaan belum diisi dilewati' : ''}).`"></span>
                </template>
                <template x-if="stats.error === 0 && (stats.valid + stats.warning === 0)">
                    <span class="text-slate-500 font-bold">Belum ada jawaban yang diisi pada file Excel.</span>
                </template>
            </div>
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="closeModal()"
                    class="px-6 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru1 rounded-lg text-sm font-semibold transition cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="submitImport()"
                    :disabled="isLoading || stats.error > 0 || (stats.valid + stats.warning === 0)"
                    :class="(isLoading || stats.error > 0 || (stats.valid + stats.warning === 0)) ? 'bg-hijau opacity-50 cursor-not-allowed text-white' : 'bg-hijau hover:opacity-90 text-white cursor-pointer'"
                    class="px-6 py-2.5 rounded-lg text-sm font-bold transition shadow-sm flex items-center gap-2"
                >
                    <svg x-show="isLoading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Proses Import</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Zoom Image Lightbox Overlay --}}
    <div
        x-show="showZoom"
        x-cloak
        class="fixed inset-0 flex items-center justify-center z-60 bg-black/80 p-4 transition-opacity duration-300"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showZoom = false"
    >
        <div class="relative max-w-4xl max-h-[90vh] flex items-center justify-center">
            <button
                type="button"
                class="absolute -top-12 right-0 text-white hover:text-gray-300 flex items-center gap-1 bg-black/40 px-3 py-1.5 rounded-lg border border-white/20 transition cursor-pointer"
                @click.stop="showZoom = false"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="text-xs font-bold">Tutup</span>
            </button>
            <img
                src="{{ asset('images/contoh_import_jawaban_saq.webp') }}"
                alt="Contoh Format Pengisian Excel"
                class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl border border-white/10"
                @click.stop
            >
        </div>
    </div>
</div>

<script>
    // Dynamically load SheetJS if not already loaded
    if (typeof window.XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';
        script.crossOrigin = 'anonymous';
        script.async = true;
        document.head.appendChild(script);
    }

    function importJawabanSaqHandler(actionUrl, templateUrl, submissionId) {
        return {
            action: actionUrl,
            templateUrl: templateUrl,
            submissionId: submissionId,
            isDragging: false,
            showZoom: false,
            isLoading: false,
            selectedFile: null,
            fileName: '',
            fileSize: '',
            errorMessage: '',
            successMessage: '',
            warnings: [],
            masterQuestions: {!! $questionsJson !!},
            parsedData: [],
            stats: {
                valid: 0,
                warning: 0,
                error: 0,
                empty: 0
            },
            selectedRowIndex: null,

            toggleRow(idx) {
                this.selectedRowIndex = this.selectedRowIndex === idx ? null : idx;
            },

            closeModal() {
                this.showZoom = false;
                this.clearFile();
                this.errorMessage = '';
                this.successMessage = '';
                this.warnings = [];
                window.dispatchEvent(new CustomEvent('close-import-modal'));
            },

            downloadTemplate() {
                window.location.href = this.templateUrl;
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.processFile(file);
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file) this.processFile(file);
            },

            processFile(file) {
                const allowedExtensions = ['xlsx', 'xls'];
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    this.errorMessage = 'Format file tidak valid. Silakan unggah file Excel (.xlsx atau .xls).';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    this.errorMessage = 'Ukuran file terlalu besar. Batas maksimal adalah 5MB.';
                    return;
                }

                this.selectedFile = file;
                this.fileName = file.name;
                this.fileSize = (file.size / 1024).toFixed(1) + ' KB';
                this.errorMessage = '';
                this.successMessage = '';
                this.parsedData = [];
                this.selectedRowIndex = null;

                if (typeof XLSX === 'undefined') {
                    this.errorMessage = 'Library pembaca Excel sedang dimuat. Silakan coba kembali dalam beberapa detik.';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];

                        const rawRows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: "" });

                        if (!rawRows || rawRows.length === 0) {
                            this.errorMessage = 'File Excel kosong atau tidak berisi data.';
                            return;
                        }

                        this.processParsedData(rawRows);
                    } catch (err) {
                        console.error('Error parsing excel:', err);
                        this.errorMessage = 'Gagal membaca file Excel. Pastikan file tidak rusak atau terproteksi.';
                    }
                };
                reader.readAsArrayBuffer(file);
            },

            processParsedData(rawRows) {
                // Find header row
                let headerRowIdx = -1;
                let colMap = {
                    no: 0,
                    status: 1,
                    indikator: 2,
                    sub_indikator: 3,
                    pertanyaan: 4,
                    jawaban: 5,
                    link_ppid: 6,
                    link_drive: 7
                };

                for (let i = 0; i < Math.min(rawRows.length, 15); i++) {
                    const row = rawRows[i];
                    if (!Array.isArray(row)) continue;
                    const lowerCells = row.map(c => String(c || '').toLowerCase().trim());
                    if (lowerCells.some(c => c.includes('pertanyaan')) && lowerCells.some(c => c.includes('jawaban'))) {
                        headerRowIdx = i;
                        lowerCells.forEach((c, cIdx) => {
                            if (c === 'no' || c.startsWith('no.')) colMap.no = cIdx;
                            else if (c.includes('status')) colMap.status = cIdx;
                            else if (c.includes('sub')) colMap.sub_indikator = cIdx;
                            else if (c.includes('indikator')) colMap.indikator = cIdx;
                            else if (c.includes('pertanyaan') && !c.includes('deskripsi')) colMap.pertanyaan = cIdx;
                            else if (c.includes('jawaban')) colMap.jawaban = cIdx;
                            else if (c.includes('ppid')) colMap.link_ppid = cIdx;
                            else if (c.includes('drive') || c.includes('dukung')) colMap.link_drive = cIdx;
                        });
                        break;
                    }
                }

                const startIdx = headerRowIdx !== -1 ? headerRowIdx + 1 : 1;
                const dataRows = [];

                for (let i = startIdx; i < rawRows.length; i++) {
                    const row = rawRows[i];
                    if (!Array.isArray(row)) continue;

                    const hasValues = row.some(val => val !== undefined && val !== null && String(val).trim() !== '');
                    if (!hasValues) continue;

                    dataRows.push({
                        rawIndex: i + 1,
                        no: String(row[colMap.no] ?? '').trim(),
                        indikator: String(row[colMap.indikator] ?? '').trim(),
                        sub_indikator: String(row[colMap.sub_indikator] ?? '').trim(),
                        pertanyaan: String(row[colMap.pertanyaan] ?? '').trim(),
                        jawaban_raw: String(row[colMap.jawaban] ?? '').trim(),
                        link_ppid: String(row[colMap.link_ppid] ?? '').trim(),
                        link_drive: String(row[colMap.link_drive] ?? '').trim(),
                    });
                }

                if (dataRows.length === 0) {
                    this.errorMessage = 'Tidak ditemukan baris pertanyaan dalam file Excel yang diunggah.';
                    return;
                }

                const mappedData = dataRows.map((item, idx) => {
                    // Match with master question
                    let matchedQ = null;
                    const num = parseInt(item.no);
                    if (!isNaN(num) && num > 0) {
                        matchedQ = this.masterQuestions.find(q => q.no === num || q.order_no === num);
                    }
                    if (!matchedQ && item.pertanyaan) {
                        const normItemText = this.normalizeText(item.pertanyaan);
                        matchedQ = this.masterQuestions.find(q => this.normalizeText(q.pertanyaan) === normItemText);
                    }

                    const pertanyaanText = matchedQ ? matchedQ.pertanyaan : (item.pertanyaan || `Pertanyaan Baris ${idx + 1}`);
                    const noDisplay = matchedQ ? matchedQ.no : (item.no || (idx + 1));
                    const indikatorDisplay = matchedQ ? matchedQ.indikator : item.indikator;
                    const subIndikatorDisplay = matchedQ ? matchedQ.sub_indikator : item.sub_indikator;

                    // Normalize Jawaban
                    let normalizedJawaban = '';
                    const rawLower = item.jawaban_raw.toLowerCase().trim();
                    const isYa = ['ya', 'y', '1', 'true', 'yes'].includes(rawLower);
                    const isTidak = ['tidak', 't', '0', 'false', 'no'].includes(rawLower);

                    if (isYa) normalizedJawaban = 'Ya';
                    else if (isTidak) normalizedJawaban = 'Tidak';

                    let statusType = 'valid';
                    const errors = [];
                    const warnings = [];

                    // Question template validation
                    let _pertanyaanValid = true;
                    if (!matchedQ) {
                        _pertanyaanValid = false;
                        errors.push(`Pertanyaan tidak cocok dengan template kuesioner periode aktif.`);
                    }

                    let _jawabanValid = true;
                    let _ppidValid = true;
                    let _driveValid = true;

                    // KONDISI 1: Baris Kosong Murni (Belum Diisi / Parsial)
                    const isEmptyRow = !item.jawaban_raw && !item.link_ppid && !item.link_drive;

                    if (isEmptyRow) {
                        statusType = 'empty';
                    }
                    // KONDISI 2: Tautan terisi tetapi jawaban kosong
                    else if (!item.jawaban_raw && (item.link_ppid || item.link_drive)) {
                        _jawabanValid = false;
                        errors.push("Tautan bukti terisi, tetapi Jawaban belum ditentukan ('Ya' atau 'Tidak').");
                    }
                    // KONDISI 3: Jawaban terisi selain Ya atau Tidak
                    else if (!isYa && !isTidak) {
                        _jawabanValid = false;
                        errors.push(`Jawaban '${item.jawaban_raw}' tidak valid. Harus berupa 'Ya' atau 'Tidak'.`);
                    }
                    // KONDISI 4: Jawaban YA
                    else if (isYa) {
                        if (!item.link_ppid) {
                            _ppidValid = false;
                            errors.push("Link PPID wajib diisi jika jawaban 'Ya'.");
                        } else if (!this.isValidUrl(item.link_ppid)) {
                            _ppidValid = false;
                            errors.push("Isikan Link PPID dengan benar.");
                        }

                        if (!item.link_drive) {
                            _driveValid = false;
                            errors.push("Link Bukti Dukung (Drive) wajib diisi jika jawaban 'Ya'.");
                        } else if (!this.isValidUrl(item.link_drive)) {
                            _driveValid = false;
                            errors.push("Isikan Link Drive dengan benar.");
                        }
                    }
                    // KONDISI 5: Jawaban TIDAK
                    else if (isTidak) {
                        if (item.link_ppid || item.link_drive) {
                            const filledCols = [];
                            if (item.link_ppid) filledCols.push('Link PPID');
                            if (item.link_drive) filledCols.push('Link Bukti Dukung (Drive)');

                            warnings.push(`Jawaban terisi 'Tidak', namun kolom [${filledCols.join(' & ')}] berisi tautan bukti.`);
                            warnings.push(`Saran: Mohon periksa kembali apakah jawaban pertanyaan ini seharusnya 'Ya' (pastikan bukan kesalahan ketik/human error).`);
                            warnings.push(`Jika jawaban memang 'Tidak', kolom tautan bukti tidak perlu diisi (data baris ini tetap dapat diproses import).`);
                        }
                    }

                    if (statusType !== 'empty') {
                        if (errors.length > 0) {
                            statusType = 'error';
                        } else if (warnings.length > 0) {
                            statusType = 'warning';
                        }
                    }

                    let messageList = [];
                    if (statusType === 'empty') {
                        messageList = ['Pertanyaan belum diisi (akan otomatis dilewati saat proses import).'];
                    } else if (errors.length > 0) {
                        messageList = errors;
                    } else if (warnings.length > 0) {
                        messageList = warnings;
                    } else {
                        messageList = ['Data jawaban dan bukti dukung valid dan lengkap'];
                    }

                    return {
                        rowNumber: item.rawIndex,
                        no: noDisplay,
                        id_question: matchedQ ? matchedQ.id_question : null,
                        indikator: indikatorDisplay,
                        sub_indikator: subIndikatorDisplay,
                        pertanyaan: pertanyaanText,
                        jawaban: normalizedJawaban,
                        jawaban_raw: item.jawaban_raw,
                        link_ppid: item.link_ppid,
                        link_drive: item.link_drive,
                        _pertanyaanValid,
                        _jawabanValid,
                        _ppidValid,
                        _driveValid,
                        _status: {
                            type: statusType,
                            messages: messageList
                        }
                    };
                });

                // Calculate stats
                let validCount = 0;
                let warningCount = 0;
                let errorCount = 0;
                let emptyCount = 0;

                mappedData.forEach(row => {
                    if (row._status.type === 'valid') validCount++;
                    else if (row._status.type === 'warning') warningCount++;
                    else if (row._status.type === 'empty') emptyCount++;
                    else if (row._status.type === 'error') errorCount++;
                });

                this.stats = { valid: validCount, warning: warningCount, error: errorCount, empty: emptyCount };
                this.parsedData = mappedData;
                this.selectedRowIndex = null;
            },

            normalizeText(text) {
                if (!text) return '';
                return String(text).toLowerCase().replace(/[^a-z0-9]/gi, ' ').replace(/\s+/g, ' ').trim();
            },

            isValidUrl(string) {
                if (!string) return false;
                const str = String(string).trim();
                return /^https?:\/\/.+/i.test(str);
            },

            clearFile() {
                this.selectedFile = null;
                this.fileName = '';
                this.fileSize = '';
                this.parsedData = [];
                this.stats = { valid: 0, warning: 0, error: 0, empty: 0 };
                this.selectedRowIndex = null;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            },

            async submitImport() {
                if (!this.selectedFile || this.stats.error > 0 || (this.stats.valid + this.stats.warning === 0)) return;

                this.isLoading = true;
                this.errorMessage = '';
                this.successMessage = '';
                this.warnings = [];

                const formData = new FormData();
                formData.append('excel_file', this.selectedFile);
                if (this.submissionId) {
                    formData.append('id_submission', this.submissionId);
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        }
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        this.errorMessage = result.message || 'Terjadi kesalahan saat mengimpor data.';
                        if (result.errors && Array.isArray(result.errors)) {
                            this.errorMessage = result.errors.join(', ');
                        }
                    } else {
                        this.successMessage = result.message || 'Berhasil mengimpor jawaban!';
                        if (result.warnings && result.warnings.length > 0) {
                            this.warnings = result.warnings;
                        }

                        // Auto reload form after short delay so operator sees the imported answers
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    }
                } catch (err) {
                    console.error('Import error:', err);
                    this.errorMessage = 'Terjadi kesalahan jaringan atau server saat mengunggah file.';
                } finally {
                    this.isLoading = false;
                }
            }
        };
    }
</script>
