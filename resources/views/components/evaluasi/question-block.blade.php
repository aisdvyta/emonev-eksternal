@props([
    'number',
    'question',
    'name' => '',
    'required' => false,
    'value' => '',
    'linkPpid' => '',
    'linkDrive' => '',
    'docDrive' => '',
    'docPpid' => '',
    'doc_drive' => '',
    'doc_ppid' => '',
    'score' => '-',
    'note' => '',
    'mode' => 'fill', // fill, view, evaluate, verifikator
    'status' => 'success',
    'deskripsi' => 'Deskripsi detail untuk pertanyaan ini belum ditentukan.',
    'dokumen' => '',
    'maxScore' => 10,
    'subSubIndikator' => '',
    'verifikatorNote' => '',
    'questionId' => '',
])

@php
    $statusClasses = [
        'success' => 'border-hijau bg-hijau/5 text-hijau',
        'warning' => 'border-kuning1 bg-kuning1/5 text-kuning1',
        'danger' => 'border-merah1 bg-merah1/5 text-merah1',
    ];
    $currentStatusClass = $statusClasses[$status] ?? $statusClasses['success'];

    // Normalize links (some files use ppidLink, some use linkPpid)
    $ppid = $linkPpid ?: ($attributes->get('ppidLink') ?: '');
    $drive = $linkDrive ?: ($attributes->get('driveLink') ?: '');
    $evalNote = $note ?: ($attributes->get('evaluatorNote') ?: '');
@endphp

@php
    $initialAnswer = $value === 'Ya' || $value === true ? 'Ya' : ($value === 'Tidak' || $value === false ? 'Tidak' : '');
    $initialScore  = ($score !== '-' && $score !== null && $score !== '') ? (string)(float)$score : '';
@endphp
<div x-data="{
    answer: @js($initialAnswer),
    linkPpid: @js($ppid),
    linkDrive: @js($drive),
    showDescModal: false,
    showDocModal: false,
    score: @js($initialScore),
    note: @js($evalNote),
    maxScore: {{ (float) $maxScore }},
    showVerifNote: @js((bool) $verifikatorNote),
    verifNote: @js($verifikatorNote),
    handleScoreChange() {
        if (this.score === '' || this.score === null) {
            this.note = '';
            return;
        }
        const val = parseFloat((this.score + '').replace(',', '.'));
        if (isNaN(val)) return;

        const max = this.maxScore || 2.5;
        const ratio = max > 0 ? (val / max) : 0;

        if (val === 0) {
            this.note = 'Dokumen bukti dukung belum tersedia atau belum sesuai dengan ketentuan indikator.';
        } else if (ratio < 0.3) {
            this.note = 'Dokumen bukti dukung sangat minim, perlu perbaikan dan kelengkapan yang signifikan.';
        } else if (ratio < 0.5) {
            this.note = 'Dokumen bukti dukung baru memenuhi sebagian kecil kriteria, bukti pendukung belum lengkap.';
        } else if (ratio < 0.7) {
            this.note = 'Dokumen bukti dukung telah memenuhi sebagian kriteria, perlu melengkapi beberapa dokumen pendukung.';
        } else if (ratio < 1.0) {
            this.note = 'Dokumen bukti dukung hampir lengkap dan telah memenuhi sebagian besar kriteria indikator.';
        } else {
            this.note = 'Good job! sudah lengkap, dipertahankan.';
        }
    },
    get ppidError() {
        if (this.answer !== 'Ya' || !this.linkPpid.trim()) return '';
        return /^https?:\/\//i.test(this.linkPpid.trim()) ? '' : 'Isikan Link PPID dengan benar';
    },
    get driveError() {
        if (this.answer !== 'Ya' || !this.linkDrive.trim()) return '';
        return /^https?:\/\//i.test(this.linkDrive.trim()) ? '' : 'Isikan Link Drive dengan benar';
    }
}" {{ $attributes->merge(['class' => 'px-4 py-4 sm:px-6 sm:py-6 border-b border-abubiru bg-white']) }}>
    <div class="flex items-start justify-between mb-4 gap-3">
        <label class="text-sm font-bold text-biru1 leading-snug flex-1">
            {{ $number }}. {{ $question }} @if ($required && $mode === 'fill')
                <span class="text-merah1">*</span>
            @endif
        </label>
        <div class="flex gap-2 shrink-0 items-center">
            @if ($mode === 'verifikator')
                <button type="button" @click.stop="showVerifNote = !showVerifNote; if (!showVerifNote) verifNote = ''"
                    :class="showVerifNote ? 'bg-kuning1 text-white border-kuning1' : 'bg-kuning2/20 text-kuning1 border-kuning1/40 hover:bg-kuning2/40'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-bold shadow-sm transition"
                    :title="showVerifNote ? 'Hapus komentar' : 'Tambah komentar'">
                    <svg x-show="!showVerifNote" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg x-show="showVerifNote" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span x-text="showVerifNote ? 'Hapus' : 'Tambah'"></span>
                </button>
            @endif
            <button type="button" @click.stop="showDescModal = true"
                class="w-7 h-7 rounded-full bg-kuning1 text-white flex items-center justify-center shadow-sm hover:opacity-90 transition"
                title="Informasi Pertanyaaan">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <button type="button" @click.stop="showDocModal = true"
                class="w-7 h-7 rounded-full bg-hijau text-white flex items-center justify-center shadow-sm hover:opacity-90 transition"
                title="Informasi Dokumen">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Answer Options -->
    <div class="space-y-2.5 mb-5 pl-1">
        @if ($mode === 'fill')
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="{{ $name }}" value="Ya" x-model="answer"
                    @checked($value === 'Ya' || $value === true) class="w-4 h-4 text-biru2 border-abubiru focus:ring-biru2 bg-putihbg">
                <span class="ml-3 text-sm text-biru2">Ya</span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="{{ $name }}" value="Tidak" x-model="answer"
                    @checked($value === 'Tidak' || $value === false) class="w-4 h-4 text-biru2 border-abubiru focus:ring-biru2 bg-putihbg">
                <span class="ml-3 text-sm text-biru2">Tidak</span>
            </label>
        @else
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-biru5/20 border border-biru5/50 w-fit">
                <div class="w-2 h-2 rounded-full {{ $value === 'Ya' || $value === true ? 'bg-hijau' : 'bg-biru4' }}">
                </div>
                <span class="text-sm font-bold text-biru1">{{ $value ?: '-' }}</span>
            </div>
        @endif
    </div>

    <!-- Links and Evaluator Feedback -->
    @if ($mode !== 'fill' || $ppid !== false || $drive !== false)
        <div x-show="answer === 'Ya' || @json($mode === 'evaluate' || $mode === 'view') || showVerifNote || @json((bool)$verifikatorNote)" x-transition
            class="space-y-4 bg-putihbg p-4 rounded-lg border border-abubiru overflow-hidden min-w-0">
            @if ($ppid !== false)
                <div x-show="answer === 'Ya'" class="min-w-0">
                    <label class="text-xs text-biru3 font-black uppercase tracking-widest mb-1.5 block">Link Bukti
                        Dukung PPID</label>
                    @if ($mode === 'fill')
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    class="h-4 w-4 text-biru4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                    </path>
                                </svg></div>
                            <input type="url" name="{{ $name }}_ppid" x-model="linkPpid"
                                placeholder="https://..."
                                pattern="https?://.+"
                                title="Isikan Link dengan benar"
                                :class="ppidError ? 'border-merah1 focus:ring-merah1/20 focus:border-merah1' : 'border-abubiru focus:ring-biru2/20 focus:border-biru2'"
                                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm outline-none transition">
                        </div>
                        <div x-show="ppidError" class="text-xs font-bold text-merah1 mt-1.5 flex items-center gap-1" x-cloak>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span x-text="ppidError"></span>
                        </div>
                    @else
                        @if (!empty($ppid))
                            <a href="{{ $ppid }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-start gap-2 text-sm text-biru2 font-bold hover:underline max-w-full break-all">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                                <span class="break-all min-w-0">{{ $ppid }}</span>
                            </a>
                        @else
                            <span class="text-sm text-biru3 italic">Tidak ada link</span>
                        @endif
                    @endif
                </div>
            @endif

            @if ($drive !== false)
                <div x-show="answer === 'Ya'" class="min-w-0">
                    <label class="text-xs text-biru3 font-black uppercase tracking-widest mb-1.5 block">Link Bukti
                        Dukung Drive</label>
                    @if ($mode === 'fill')
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    class="h-4 w-4 text-biru4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                    </path>
                                </svg></div>
                            <input type="url" name="{{ $name }}_drive" x-model="linkDrive"
                                placeholder="https://..."
                                pattern="https?://.+"
                                title="Isikan Link dengan benar"
                                :class="driveError ? 'border-merah1 focus:ring-merah1/20 focus:border-merah1' : 'border-abubiru focus:ring-biru2/20 focus:border-biru2'"
                                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm outline-none transition">
                        </div>
                        <div x-show="driveError" class="text-xs font-bold text-merah1 mt-1.5 flex items-center gap-1" x-cloak>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span x-text="driveError"></span>
                        </div>
                    @else
                        @if (!empty($drive))
                            <a href="{{ $drive }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-start gap-2 text-sm text-biru2 font-bold hover:underline max-w-full break-all">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                                <span class="break-all min-w-0">{{ $drive }}</span>
                            </a>
                        @else
                            <span class="text-sm text-biru3 italic">Tidak ada link</span>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Catatan Verifikator (tampil di role operator jika ada catatan) --}}
            @if ($mode !== 'verifikator' && !empty($verifikatorNote))
                <div class="rounded-xl border border-amber-300 bg-amber-50/70 p-3.5 shadow-xs overflow-hidden">
                    <div class="flex items-center gap-1.5 font-bold text-xs uppercase tracking-wider mb-1.5 text-amber-800">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <span>Catatan Verifikator</span>
                    </div>
                    <div class="max-h-36 overflow-y-auto overflow-x-hidden pr-1.5">
                        <p class="text-sm font-semibold text-biru1 leading-relaxed whitespace-pre-wrap wrap-anywhere [word-break:break-word]">{{ $verifikatorNote }}</p>
                    </div>
                </div>
            @endif

            @if ($mode === 'view' || $mode === 'evaluate')
                @if ($mode === 'evaluate' || ($score !== '-' && $score !== '' && $score !== null) || !empty($evalNote))
                    <div class="flex flex-col sm:flex-row gap-3 pt-1">
                        @if ($mode === 'evaluate' || ($score !== '-' && $score !== '' && $score !== null))
                            <div class="flex flex-col items-center shrink-0">
                                <div
                                    class="border rounded-xl p-2.5 sm:p-3 flex flex-col justify-center items-center w-32 transition-all duration-300"
                                    :class="parseFloat((score + '').replace(',', '.')) > {{ $maxScore }}
                                        ? 'border-merah1 bg-merah1/5 text-merah1'
                                        : ((score !== '' && score !== '-')
                                            ? 'border-hijau bg-hijau/5 text-hijau shadow-2xs'
                                            : 'border-abubiru bg-white text-biru3 hover:border-biru2/60 shadow-2xs')">
                                    <div class="font-bold text-[10px] uppercase tracking-wider mb-0.5"
                                        :class="(score !== '' && score !== '-') ? 'text-hijau opacity-85' : 'text-biru3/80'">
                                        Nilai
                                    </div>
                                    @if ($mode === 'evaluate')
                                        @php
                                            $scoreStepOptions = [];
                                            $maxS = (float) $maxScore;
                                            for ($s = 0.0; $s <= ($maxS + 0.0001); $s += 0.5) {
                                                $valFormatted = (string) (round($s, 1) == (int)$s ? (int)$s : round($s, 1));
                                                $scoreStepOptions[] = $valFormatted;
                                            }
                                        @endphp
                                        <div class="relative w-full flex items-center justify-center my-0.5">
                                            <select name="{{ $name }}_score" x-model="score" data-max="{{ $maxScore }}"
                                                @change="handleScoreChange()"
                                                :class="(score !== '' && score !== '-') ? 'text-hijau text-xl sm:text-2xl font-black' : 'text-slate-600 hover:text-biru1 text-xs sm:text-sm font-bold'"
                                                class="score-input w-full text-center bg-transparent border-none outline-none focus:ring-0 p-0 cursor-pointer appearance-none transition-colors">
                                                <option value="" class="text-xs text-slate-500 font-semibold">Pilih Nilai</option>
                                                @foreach ($scoreStepOptions as $opt)
                                                    <option value="{{ $opt }}" class="text-sm font-bold text-slate-800">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                            <div x-show="score === '' || score === '-'" class="pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-slate-400">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-xl sm:text-2xl font-black leading-none my-0.5">{{ $score }}</div>
                                    @endif
                                    <div class="text-[10px] font-semibold mt-0.5 text-center whitespace-nowrap"
                                        :class="(score !== '' && score !== '-') ? 'text-hijau/75' : 'text-slate-400'">
                                        Nilai Maksimal: {{ $maxScore }}
                                    </div>
                                </div>
                                @if ($mode === 'evaluate')
                                    <div x-show="parseFloat((score + '').replace(',', '.')) > {{ $maxScore }}"
                                        class="text-xs font-bold text-merah1 mt-1 text-center max-w-32 leading-tight"
                                        x-cloak>
                                        Nilai tidak boleh lebih dari {{ $maxScore }}!
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($mode === 'evaluate' || !empty($evalNote))
                            <div class="border rounded-xl p-3 flex-1 transition-all duration-300"
                                :class="(note !== '') ? 'border-hijau bg-hijau/5 text-hijau' : 'border-abubiru bg-white text-biru3'">
                                <div class="font-black text-xs uppercase tracking-widest mb-1 opacity-80">Catatan Evaluator
                                </div>
                                @if ($mode === 'evaluate')
                                    <textarea name="{{ $name }}_note" x-model="note"
                                        class="note-input w-full bg-transparent border-none outline-none focus:ring-0 p-0 text-sm font-medium resize-none wrap-anywhere max-h-36 overflow-y-auto"
                                        rows="2" placeholder="Tambahkan catatan..."></textarea>
                                @else
                                    <div class="max-h-36 overflow-y-auto overflow-x-hidden pr-1.5">
                                        <div class="text-sm font-medium leading-relaxed whitespace-pre-wrap wrap-anywhere [word-break:break-word]">{{ $evalNote }}</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Catatan Verifikator (hanya mode verifikator) --}}
            @if ($mode === 'verifikator')
                <div x-show="showVerifNote"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="mt-3"
                    x-cloak>
                    <div class="border border-amber-300 bg-amber-50/60 focus-within:border-amber-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-amber-200/60 rounded-xl p-3.5 w-full transition-all duration-200 shadow-xs">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1.5 font-bold text-xs uppercase tracking-wider text-amber-800">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Catatan Verifikator</span>
                            </div>
                            <span class="text-xs font-bold text-amber-700 bg-amber-100/90 px-2 py-0.5 rounded-md">Untuk Operator</span>
                        </div>
                        <textarea x-model="verifNote"
                            class="w-full bg-transparent border-none outline-none focus:ring-0 p-0 text-sm font-medium resize-none text-biru1 placeholder-amber-800/40 leading-relaxed wrap-anywhere max-h-36 overflow-y-auto"
                            rows="2"
                            placeholder="Tambahkan catatan untuk soal ini..."></textarea>
                        <input type="hidden" name="verifikator_note[{{ $questionId }}]" :value="verifNote">
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Description Modal Popup -->
    <template x-if="showDescModal">
        <div x-show="showDescModal"
            class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-biru1/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2" @click.away="showDescModal = false">

                {{-- Tombol X pojok kanan atas --}}
                <button type="button" @click="showDescModal = false"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full border border-abubiru bg-white flex items-center justify-center text-abu hover:text-biru1 hover:bg-putihbg transition z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Konten --}}
                <div class="px-8 pt-8 pb-8 text-center">
                    {{-- Judul --}}
                    <div class="flex items-center justify-center gap-2.5 mb-5">
                        <svg class="w-8 h-8 text-kuning1 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <h3 class="text-2xl font-extrabold text-kuning1">Keterangan Pertanyaan</h3>
                    </div>

                    @if ($subSubIndikator)
                        <div class="text-xs font-bold text-kuning1 uppercase tracking-wider mb-4 px-3 py-1 bg-kuning1/10 rounded-full w-fit mx-auto">
                            {{ $subSubIndikator }}
                        </div>
                    @endif

                    {{-- Isi deskripsi — angka bernomor dipisah per baris --}}
                    @php
                        $desc = trim($deskripsi);
                        // Insert newline sebelum setiap "N. " yang ada di tengah teks (didahului spasi atau koma+spasi)
                        $formatted = preg_replace('/[,;\s]+(\d+\.\s)/', "\n$1", $desc);
                        $lines = array_filter(array_map('trim', explode("\n", $formatted)));
                        $isNumbered = count($lines) > 1;
                    @endphp
                    <div
                        class="text-base text-abu leading-relaxed text-left @if ($isNumbered) space-y-1 @endif">
                        @foreach ($lines as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Required Documents Modal Popup -->
    <template x-if="showDocModal">
        <div x-show="showDocModal"
            class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-biru1/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2" @click.away="showDocModal = false">

                {{-- Tombol X pojok kanan atas --}}
                <button type="button" @click="showDocModal = false"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full border border-abubiru bg-white flex items-center justify-center text-abu hover:text-biru1 hover:bg-putihbg transition z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Konten --}}
                <div class="px-8 pt-8 pb-8 text-center">
                    {{-- Judul --}}
                    <div class="flex items-center justify-center gap-2.5 mb-5">
                        <svg class="w-8 h-8 text-hijau shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd" />
                        </svg>
                        <h3 class="text-2xl font-extrabold text-hijau">Dokumen yang Diperlukan</h3>
                    </div>

                    @php
                        $parsedDrive = $docDrive ?: ($doc_drive ?: ($attributes->get('docDrive') ?: ($attributes->get('doc-drive') ?: ($attributes->get('doc_drive') ?: ''))));
                        $parsedPpid  = $docPpid ?: ($doc_ppid ?: ($attributes->get('docPpid') ?: ($attributes->get('doc-ppid') ?: ($attributes->get('doc_ppid') ?: ''))));

                        if (empty($parsedDrive) && empty($parsedPpid) && !empty($dokumen)) {
                            if (preg_match('/link\s*drive\s*:\s*(.*?)(?=(?:link\s*ppid|\Z))/is', $dokumen, $mDrive)) {
                                $parsedDrive = trim($mDrive[1]);
                                if ($parsedDrive === '-') $parsedDrive = '';
                            }
                            if (preg_match('/link\s*ppid(?:\/lainnya)?\s*:\s*(.*?)$/is', $dokumen, $mPpid)) {
                                $parsedPpid = trim($mPpid[1]);
                                if ($parsedPpid === '-') $parsedPpid = '';
                            }
                        }
                        $hasExampleLinks = !empty($parsedDrive) || !empty($parsedPpid);

                        $rawDokumen = trim($dokumen ?? '');
                        $hasCustomDoc = !empty($rawDokumen) && 
                                        !str_contains($rawDokumen, 'Contoh Pengisian:') && 
                                        $rawDokumen !== 'Dokumen bukti dukung yang diperlukan untuk pertanyaan ini belum ditentukan.' && 
                                        $rawDokumen !== 'Dokumen pendukung untuk pertanyaan ini belum ditentukan.';
                    @endphp

                    @if ($hasCustomDoc)
                        <div class="text-left mb-5">
                            <p class="text-sm font-medium text-biru1 whitespace-pre-line leading-relaxed">
                                {{ $rawDokumen }}
                            </p>
                        </div>
                    @endif

                    @if ($hasExampleLinks)
                        <div class="text-left space-y-4">
                            <div class="flex items-center justify-center">
                                <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-hijau/10 text-hijau border border-hijau/20">
                                    Contoh Pengisian
                                </span>
                            </div>

                            {{-- Link Drive --}}
                            <div class="text-base text-abu leading-relaxed">
                                <span class="block text-xs font-bold uppercase tracking-wider text-hijau mb-1">
                                    Link Drive:
                                </span>
                                @if (!empty($parsedDrive) && $parsedDrive !== '-')
                                    @if (str_starts_with($parsedDrive, 'http://') || str_starts_with($parsedDrive, 'https://'))
                                        <a href="{{ $parsedDrive }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-start gap-1.5 text-sm font-semibold text-biru2 hover:underline break-all">
                                            <svg class="w-4 h-4 shrink-0 text-biru4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            <span class="break-all">{{ $parsedDrive }}</span>
                                        </a>
                                    @else
                                        <p class="text-sm font-medium text-biru1 break-words leading-relaxed">
                                            {{ $parsedDrive }}
                                        </p>
                                    @endif
                                @else
                                    <p class="text-sm text-abumuda italic">-</p>
                                @endif
                            </div>

                            {{-- Link PPID --}}
                            <div class="text-base text-abu leading-relaxed">
                                <span class="block text-xs font-bold uppercase tracking-wider text-hijau mb-1">
                                    Link PPID:
                                </span>
                                @if (!empty($parsedPpid) && $parsedPpid !== '-')
                                    @if (str_starts_with($parsedPpid, 'http://') || str_starts_with($parsedPpid, 'https://'))
                                        <a href="{{ $parsedPpid }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-start gap-1.5 text-sm font-semibold text-biru2 hover:underline break-all">
                                            <svg class="w-4 h-4 shrink-0 text-biru4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            <span class="break-all">{{ $parsedPpid }}</span>
                                        </a>
                                    @else
                                        <p class="text-sm font-medium text-biru1 break-words leading-relaxed">
                                            {{ $parsedPpid }}
                                        </p>
                                    @endif
                                @else
                                    <p class="text-sm text-abumuda italic">-</p>
                                @endif
                            </div>
                        </div>
                    @elseif (!$hasCustomDoc)
                        <div class="text-sm text-abumuda italic text-center py-4">
                            Dokumen bukti dukung yang diperlukan untuk pertanyaan ini belum ditentukan.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
