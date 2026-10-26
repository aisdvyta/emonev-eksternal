@props([
    'action' => '',
    'roles' => [],
    'satkers' => [],
])

@php
    // Serialize to JSON for safe embedding in JS
    $rolesCollection = $roles instanceof \Illuminate\Support\Collection ? $roles : collect($roles);
    $satkersCollection = $satkers instanceof \Illuminate\Support\Collection ? $satkers : collect($satkers);
    $rolesJson = json_encode($rolesCollection->pluck('name')->values()->toArray());
    $satkersJson = json_encode($satkersCollection->map(fn($s) => ['kode_satker' => $s->kode_satker, 'nama_satker' => $s->nama_satker])->values()->toArray());
@endphp

<div
    x-data="importUserHandler('{{ $action }}')"
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
    <div class="fixed inset-0 bg-biru1/50" @click="closeModal()"></div>

    {{-- Panel --}}
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 border border-abubiru/40 overflow-hidden flex flex-col max-h-[90vh]"
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
                <span>Import User dari Excel</span>
            </h3>
            <button @click="closeModal()" class="text-white/60 hover:text-white transition rounded-lg p-1 hover:bg-white/10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 overflow-y-auto flex-1 space-y-5 custom-scrollbar">
            {{-- Instructions and Download --}}
            <div class="rounded-2xl bg-birumuda border border-biru5 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0 mt-0.5 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-biru6">Petunjuk Pengisian</h4>
                        <p class="text-xs text-biru6/90 font-semibold leading-relaxed">
                            Pastikan kolom Excel yang Anda unggah memiliki header: <br>
                            <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-rose-600 font-mono font-bold">nama_user</code><span class="text-rose-600 font-bold">*</span>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-rose-600 font-mono font-bold">email</code><span class="text-rose-600 font-bold">*</span>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 font-mono">password</code>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 font-mono">nip_panjang</code>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 font-mono">nip_pendek</code>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-rose-600 font-mono font-bold">kode_satker</code><span class="text-rose-600 font-bold">*</span>, <code class="bg-white/80 px-1.5 py-0.5 rounded border border-biru5/40 text-rose-600 font-mono font-bold">role</code><span class="text-rose-600 font-bold">*</span>.<br>
                            Kolom <code class="font-bold text-biru1">nama_user</code>, <code class="font-bold text-biru1">email</code>, <code class="font-bold text-biru1">kode_satker</code>, dan <code class="font-bold text-biru1">role</code> bersifat wajib (<span class="text-rose-600 font-bold">*</span>).
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
                    <span>Unduh Template</span>
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
                            src="{{ asset('images/contoh_import_user.webp') }}" 
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
                        accept=".xlsx, .xls, .csv"
                        class="hidden"
                    />

                    <div class="w-12 h-12 rounded-xl bg-biru5 flex items-center justify-center text-biru2 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>

                    <div x-show="!fileName" class="space-y-1">
                        <p class="text-sm font-bold text-biru1">Tarik dan taruh file Excel di sini, atau <span class="text-biru2 hover:underline">pilih file</span></p>
                        <p class="text-xs text-biru4">Mendukung format .xlsx, .xls, atau .csv (Maks. 5MB)</p>
                    </div>

                    <div x-show="fileName" class="space-y-1">
                        <p class="text-sm font-bold text-biru1" x-text="fileName"></p>
                        <p class="text-xs text-biru3" x-text="fileSize"></p>
                        <button type="button" @click.stop="clearFile()" class="text-xs font-bold text-rose-600 hover:underline mt-2">Hapus File</button>
                    </div>
                </div>
            </div>

            {{-- Error Message --}}
            <div x-show="errorMessage" x-cloak class="p-3 bg-red-50 text-rose-600 text-xs font-semibold rounded-lg border border-red-200/50" x-text="errorMessage"></div>

            {{-- Preview Area --}}
            <div x-show="parsedData.length > 0" x-cloak class="space-y-3">
                <div class="flex justify-between items-center">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-biru1">Pratinjau Data (<span x-text="parsedData.length"></span> baris)</h4>
                        <p class="text-xs text-biru3/80 font-medium">Klik baris tabel untuk melihat detail status validasi</p>
                    </div>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-1 text-xs font-bold text-green-700 border border-green-200">
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
                    </div>
                </div>

                {{-- Preview Table --}}
                <div class="border border-abubiru rounded-xl overflow-hidden max-h-75 overflow-y-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-putihbg text-biru4 font-bold border-b border-abubiru sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2.5 bg-putihbg">Status</th>
                                <th class="px-4 py-2.5 bg-putihbg">Nama</th>
                                <th class="px-4 py-2.5 bg-putihbg">Email</th>
                                <th class="px-4 py-2.5 bg-putihbg">Password</th>
                                <th class="px-4 py-2.5 bg-putihbg">NIP</th>
                                <th class="px-4 py-2.5 bg-putihbg">Satker</th>
                                <th class="px-4 py-2.5 bg-putihbg">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-abubiru/30 bg-white font-medium text-biru1">
                            <template x-for="(row, idx) in parsedData" :key="idx">
                                <tr 
                                    @click="selectedRowIndex = (selectedRowIndex === idx ? null : idx)"
                                    :class="{
                                        'bg-red-50/20 hover:bg-red-50/40': row._status.type === 'error' && selectedRowIndex !== idx,
                                        'bg-amber-50/20 hover:bg-amber-50/40': row._status.type === 'warning' && selectedRowIndex !== idx,
                                        'bg-green-50/10 hover:bg-green-50/30': row._status.type === 'valid' && selectedRowIndex !== idx,
                                        'bg-biru5/30 ring-2 ring-biru2/30 ring-inset': selectedRowIndex === idx
                                    }"
                                    class="cursor-pointer transition-all duration-150"
                                >
                                    {{-- Status Badge --}}
                                    <td class="px-4 py-2.5">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-700 border-green-300': row._status.type === 'valid',
                                                'bg-amber-100 text-amber-700 border-amber-300': row._status.type === 'warning',
                                                'bg-red-100 text-red-700 border-red-300': row._status.type === 'error'
                                            }"
                                            class="inline-block border px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap"
                                            :title="row._status.messages.join('\n')"
                                            x-text="row._status.type.toUpperCase()"
                                        ></span>
                                    </td>
                                    {{-- Nama --}}
                                    <td class="px-4 py-2.5">
                                        <div x-text="row.nama_user || '-'" :class="{'text-red-500 font-bold': !row.nama_user}"></div>
                                    </td>
                                    {{-- Email --}}
                                    <td class="px-4 py-2.5">
                                        <div x-text="row.email || '-'" :class="{'text-red-500 font-bold': !row.email || !row._emailValid}"></div>
                                    </td>
                                    {{-- Password --}}
                                    <td class="px-4 py-2.5">
                                        <span x-text="row.password || 'password123'" :class="{'text-biru4 font-normal italic': !row.password}"></span>
                                    </td>
                                    {{-- NIP --}}
                                    <td class="px-4 py-2.5">
                                        <div>
                                            <span x-text="row.nip_panjang || '-'" class="font-mono text-xs"></span>
                                            <template x-if="row.nip_pendek">
                                                <span class="block font-mono text-xs text-biru4" x-text="row.nip_pendek"></span>
                                            </template>
                                        </div>
                                    </td>
                                    {{-- Satker --}}
                                    <td class="px-4 py-2.5">
                                        <div x-text="row.kode_satker || '-'"></div>
                                        <template x-if="row.kode_satker">
                                            <span
                                                x-text="getSatkerName(row.kode_satker)"
                                                :class="row._satkerValid ? 'text-biru3' : 'text-red-500 font-bold'"
                                                class="block text-xs"
                                            ></span>
                                        </template>
                                    </td>
                                    {{-- Role --}}
                                    <td class="px-4 py-2.5">
                                        <span
                                            x-text="(row.role || 'operator').toUpperCase().replace('_', ' ')"
                                            :class="row._roleValid ? 'text-biru1 bg-slate-100' : 'text-amber-700 bg-amber-50 font-bold'"
                                            class="inline-block px-1.5 py-0.5 rounded text-xs"
                                        ></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Detail Status Box --}}
                <div x-show="selectedRowIndex !== null && parsedData[selectedRowIndex]" 
                     x-transition
                     :class="{
                         'bg-red-50 border-red-200 text-red-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'error',
                         'bg-amber-50 border-amber-200 text-amber-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'warning',
                         'bg-green-50 border-green-200 text-green-950': parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'valid'
                     }"
                     class="p-4 border rounded-xl text-xs space-y-2 shadow-xs transition-all duration-200">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'error'">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>
                        <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'warning'">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>
                        <template x-if="parsedData[selectedRowIndex] && parsedData[selectedRowIndex]._status.type === 'valid'">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        <span>Pesan Validasi - Baris <span x-text="selectedRowIndex + 1"></span> (<span x-text="parsedData[selectedRowIndex]?.nama_user || 'Tanpa Nama'"></span>)</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 ml-1">
                        <template x-for="(msg, mIdx) in parsedData[selectedRowIndex]?._status.messages" :key="mIdx">
                            <li class="font-medium" x-text="msg"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-between items-center px-6 py-4 border-t border-abubiru/50 bg-white shrink-0">
            <div class="text-xs text-biru3 font-medium">
                <template x-if="stats.error > 0">
                    <span class="text-rose-600 font-bold">Harap perbaiki data error sebelum mengimpor.</span>
                </template>
                <template x-if="parsedData.length > 0 && stats.error === 0">
                    <span class="text-green-600 font-bold" x-text="stats.warning > 0 ? 'Data siap di-import (dengan beberapa catatan informasi).' : 'Semua data valid! Siap di-import.'"></span>
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
                    :disabled="isLoading || parsedData.length === 0 || stats.error > 0"
                    :class="(isLoading || parsedData.length === 0 || stats.error > 0) ? 'bg-hijau opacity-50 cursor-not-allowed text-white' : 'bg-hijau hover:opacity-90 text-white'"
                    class="px-6 py-2.5 rounded-lg text-sm font-bold transition shadow-sm cursor-pointer flex items-center gap-2"
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
            <!-- Close Button -->
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
                src="{{ asset('images/contoh_import_user.webp') }}" 
                alt="Contoh Format Pengisian Excel" 
                class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl border border-white/10"
                @click.stop
            >
        </div>
    </div>
</div>

<script>
    // Dynamically load SheetJS (with styles support) if not already loaded
    if (typeof window.XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';
        script.crossOrigin = 'anonymous';
        script.async = true;
        document.head.appendChild(script);
    }

    function importUserHandler(actionUrl) {
        return {
            action: actionUrl,
            isDragging: false,
            showZoom: false,
            fileName: '',
            fileSize: '',
            parsedData: [],
            errorMessage: '',
            isLoading: false,
            roles: {!! $rolesJson !!},
            satkers: {!! $satkersJson !!},
            stats: {
                valid: 0,
                warning: 0,
                error: 0
            },
            selectedRowIndex: null,

            closeModal() {
                this.$dispatch('close-import-modal');
                this.clearFile();
            },

            clearFile() {
                this.fileName = '';
                this.fileSize = '';
                this.parsedData = [];
                this.errorMessage = '';
                this.$refs.fileInput.value = '';
                this.stats = { valid: 0, warning: 0, error: 0 };
                this.selectedRowIndex = null;
            },

            handleFileDrop(e) {
                this.isDragging = false;
                const file = e.dataTransfer.files[0];
                if (file) {
                    this.processFile(file);
                }
            },

            handleFileSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    this.processFile(file);
                }
            },

            processFile(file) {
                const allowedExtensions = ['xlsx', 'xls', 'csv'];
                const fileExt = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExt)) {
                    this.errorMessage = 'Format file tidak valid. Silakan unggah file Excel (.xlsx, .xls) atau CSV.';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    this.errorMessage = 'Ukuran file terlalu besar. Batas maksimal adalah 5MB.';
                    return;
                }

                this.fileName = file.name;
                this.fileSize = (file.size / 1024).toFixed(1) + ' KB';
                this.errorMessage = '';
                this.parsedData = [];
                this.selectedRowIndex = null;

                if (typeof XLSX === 'undefined') {
                    this.errorMessage = 'Library SheetJS sedang dimuat. Silakan coba sesaat lagi.';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        
                        // Parse as Array of Objects
                        const json = XLSX.utils.sheet_to_json(worksheet, { defval: "", raw: false });
                        
                        if (json.length === 0) {
                            this.errorMessage = 'File Excel kosong atau tidak berisi data.';
                            return;
                        }

                        this.processParsedData(json);
                    } catch (err) {
                        console.error(err);
                        this.errorMessage = 'Gagal membaca file Excel. Pastikan file tidak rusak.';
                    }
                };
                reader.readAsArrayBuffer(file);
            },

            processParsedData(rawData) {
                // Filter out completely empty rows
                const filteredRawData = rawData.filter(row => {
                    const values = Object.values(row).map(v => String(v !== undefined && v !== null ? v : '').trim());
                    return values.some(v => v !== '');
                });

                if (filteredRawData.length === 0) {
                    this.errorMessage = 'File Excel tidak berisi data yang dapat diproses.';
                    return;
                }

                // roles is a plain array of role name strings e.g. ["admin","operator",...]
                const validRolesList = this.roles.length > 0
                    ? this.roles.map(r => String(r).toLowerCase())
                    : ['operator', 'verifikator', 'evaluator', 'super_evaluator', 'admin'];
                const validSatkerCodes = this.satkers.map(s => String(s.kode_satker));
                
                const mappedData = filteredRawData.map((row) => {
                    // Normalize keys (handle case-insensitive and spacing differences)
                    const normalizedRow = {};
                    Object.keys(row).forEach(key => {
                        const cleanKey = key.trim().toLowerCase().replace(/\s+/g, '_');
                        normalizedRow[cleanKey] = String(row[key] !== undefined && row[key] !== null ? row[key] : '').trim();
                    });

                    // Mapping properties
                    const nama_user = normalizedRow.nama_user || normalizedRow.nama || normalizedRow.nama_lengkap || '';
                    const email = normalizedRow.email || '';
                    const password = normalizedRow.password || 'password123';
                    const nip_panjang = normalizedRow.nip_panjang || normalizedRow.nip || '';
                    const nip_pendek = normalizedRow.nip_pendek || '';
                    const kode_satker = normalizedRow.kode_satker || '';
                    const role = normalizedRow.role || '';

                    // Validate properties
                    const _emailValid = this.validateEmail(email);
                    const _satkerValid = !!kode_satker && validSatkerCodes.includes(kode_satker);
                    const normalizedRole = role.toLowerCase().replace(/\s+/g, '_');
                    const _roleValid = !!role && validRolesList.includes(normalizedRole);

                    // Determine Status Type & messages
                    let statusType = 'valid';
                    const messages = [];

                    if (!nama_user) {
                        statusType = 'error';
                        messages.push('Nama Lengkap wajib diisi.');
                    }
                    if (!email) {
                        statusType = 'error';
                        messages.push('Email wajib diisi.');
                    } else if (!_emailValid) {
                        statusType = 'error';
                        messages.push('Format email tidak valid.');
                    }
                    if (!kode_satker) {
                        statusType = 'error';
                        messages.push('Kode Satker wajib diisi.');
                    } else if (!_satkerValid) {
                        statusType = 'error';
                        messages.push(`Kode Satker '${kode_satker}' tidak ditemukan di master data.`);
                    }
                    if (!role) {
                        statusType = 'error';
                        messages.push('Role wajib diisi.');
                    } else if (!_roleValid) {
                        statusType = 'error';
                        messages.push(`Role '${role}' tidak dikenal.`);
                    }

                    // Check warnings for optional / default values
                    if (statusType !== 'error') {
                        if (!normalizedRow.password) {
                            statusType = 'warning';
                            messages.push('Password kosong (akan otomatis diset default: password123).');
                        }
                        if (!nip_panjang) {
                            statusType = 'warning';
                            messages.push('NIP Panjang tidak diisi (opsional).');
                        }
                        if (!nip_pendek) {
                            statusType = 'warning';
                            messages.push('NIP Pendek tidak diisi (opsional).');
                        }
                    }

                    return {
                        nama_user,
                        email,
                        password,
                        password_is_default: !normalizedRow.password,
                        nip_panjang,
                        nip_pendek,
                        kode_satker,
                        role: normalizedRole || role,
                        _emailValid,
                        _satkerValid,
                        _roleValid,
                        _status: {
                            type: statusType,
                            messages: messages.length > 0 ? messages : ['Data valid dan lengkap']
                        }
                    };
                });

                // Calculate statistics
                let validCount = 0;
                let warningCount = 0;
                let errorCount = 0;

                mappedData.forEach(row => {
                    if (row._status.type === 'valid') validCount++;
                    else if (row._status.type === 'warning') warningCount++;
                    else if (row._status.type === 'error') errorCount++;
                });

                this.stats = { valid: validCount, warning: warningCount, error: errorCount };
                this.parsedData = mappedData;

                // Auto-select first row with error or warning
                const firstIssueIdx = mappedData.findIndex(row => row._status.type === 'error' || row._status.type === 'warning');
                this.selectedRowIndex = firstIssueIdx !== -1 ? firstIssueIdx : null;
            },

            validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            },

            getSatkerName(code) {
                const satker = this.satkers.find(s => String(s.kode_satker) === String(code));
                return satker ? satker.nama_satker : 'Satker tidak ditemukan';
            },

            downloadTemplate() {
                if (typeof XLSX === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';
                    script.onload = () => this.downloadTemplate();
                    document.head.appendChild(script);
                    return;
                }

                const headers = ['nama_user', 'email', 'password', 'nip_panjang', 'nip_pendek', 'kode_satker', 'role'];
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.aoa_to_sheet([headers]);

                // Set column widths
                ws['!cols'] = [
                    { wch: 25 }, // nama_user
                    { wch: 30 }, // email
                    { wch: 18 }, // password
                    { wch: 25 }, // nip_panjang
                    { wch: 16 }, // nip_pendek
                    { wch: 16 }, // kode_satker
                    { wch: 18 }, // role
                ];

                // Style headers
                for (let c = 0; c < headers.length; c++) {
                    const cellAddress = XLSX.utils.encode_cell({ r: 0, c: c });
                    if (ws[cellAddress]) {
                        ws[cellAddress].s = {
                            font: {
                                name: 'Calibri',
                                sz: 11,
                                bold: true,
                                color: { rgb: "000000" }
                            },
                            fill: {
                                fgColor: { rgb: "D9E2EC" } // Light blue-gray background
                            },
                            alignment: {
                                horizontal: "center",
                                vertical: "center"
                            },
                            border: {
                                top: { style: "thin", color: { rgb: "D1D8E0" } },
                                bottom: { style: "thin", color: { rgb: "D1D8E0" } },
                                left: { style: "thin", color: { rgb: "D1D8E0" } },
                                right: { style: "thin", color: { rgb: "D1D8E0" } }
                            }
                        };
                    }
                }

                // Pre-format rows 1-500 as explicit Text (@) so Excel treats cells as Text
                for (let r = 1; r <= 500; r++) {
                    for (let c = 0; c < headers.length; c++) {
                        const cellAddress = XLSX.utils.encode_cell({ r: r, c: c });
                        ws[cellAddress] = {
                            t: 's',
                            v: '',
                            z: '@' // Force Excel Text format (@)
                        };
                    }
                }

                // Update sheet range reference
                ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: 500, c: headers.length - 1 } });

                XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                XLSX.writeFile(wb, 'template_import_user.xlsx');
            },

            submitImport() {
                if (this.parsedData.length === 0 || this.stats.error > 0) return;
                
                this.isLoading = true;
                
                // Filter only valid/warning records (which are procedable)
                const payload = this.parsedData.map(row => ({
                    nama_user: row.nama_user,
                    email: row.email,
                    password: row.password,
                    nip_panjang: row.nip_panjang,
                    nip_pendek: row.nip_pendek,
                    kode_satker: row.kode_satker,
                    role: row.role
                }));

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ users: payload })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => { throw new Error(data.message || 'Gagal menyimpan data import.') });
                    }
                    return response.json();
                })
                .then(data => {
                    this.isLoading = false;
                    this.closeModal();
                    
                    // Trigger Livewire table refresh
                    if (window.Livewire) {
                        window.Livewire.dispatch('refreshTable');
                    } else if (window.livewire) {
                        window.livewire.emit('refreshTable');
                    }

                    // Show success toast
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { 
                            type: 'success', 
                            message: data.message || `Berhasil mengimpor ${data.imported} user.` 
                        }
                    }));
                })
                .catch(error => {
                    this.isLoading = false;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'error', message: error.message || 'Terjadi kesalahan sistem saat menyimpan.' }
                    }));
                });
            }
        };
    }
</script>
