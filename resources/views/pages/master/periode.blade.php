<x-layouts.app title="Manajemen Periode - HaloKIP" sidebar="evaluator">
<div x-data="{
    periods: @js($periods),
    showFormModal: false,
    formMode: 'add',
    formData: {},
    showDeleteModal: false,
    deleteTarget: null,
    deleteLabel: '',
    expandedPeriods: [],
    stageDefs: {
        saq: { key: 'saq', name: 'Pengisian SAQ' },
        eval: { key: 'eval', name: 'Evaluasi Data' },
        visit: { key: 'visit', name: 'Visitasi & Verif. Faktual' },
        interview: { key: 'interview', name: 'Wawancara' },
        result: { key: 'result', name: 'Penilaian Akhir' },
    },
    resolveStageKeys(stageOrder) {
        const order = stageOrder || ['saq', 'visitasi', 'wawancara'];
        const keys = [];
        for (const stg of order) {
            if (stg === 'saq') {
                keys.push('saq', 'eval');
            } else if (stg === 'visitasi') {
                keys.push('visit');
            } else if (stg === 'wawancara') {
                keys.push('interview');
            }
        }
        keys.push('result');
        return keys;
    },
    getStageKeys() {
        return this.formData.stage_keys || ['saq', 'eval', 'visit', 'interview', 'result'];
    },
    formatIndoDate(dateStr) {
        if (!dateStr) return '';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const d = parseInt(parts[2], 10);
        const m = months[parseInt(parts[1], 10) - 1];
        const y = parts[0];
        return `${d} ${m} ${y}`;
    },
    getRomanSuffix(p) {
        const year = p.periode_start.split('-')[0];
        const sameYearPeriods = this.periods
            .filter(item => item.periode_start.split('-')[0] === year)
            .sort((a, b) => a.periode_start.localeCompare(b.periode_start));
            
        const idx = sameYearPeriods.findIndex(item => item.id_periode === p.id_periode);
        const index = idx === -1 ? sameYearPeriods.length + 1 : idx + 1;
        
        const romanMap = {
            1: '',
            2: ' II',
            3: ' III',
            4: ' IV',
            5: ' V',
            6: ' VI',
            7: ' VII',
            8: ' VIII',
            9: ' IX',
            10: ' X'
        };
        return romanMap[index] ?? ' ' + index;
    },
    get overlapMessage() {
        // 1. Validasi rentang tanggal periode utama
        if (this.formData.start && this.formData.end && this.formData.end < this.formData.start) {
            return 'Tanggal selesai periode tidak boleh lebih awal dari tanggal mulai.';
        }

        // 2. Validasi urutan kronologis tahapan sesuai urutan aktif (stage_order)
        const keys = this.getStageKeys();
        for (let i = 0; i < keys.length; i++) {
            const key = keys[i];
            const name = this.stageDefs[key]?.name || key;
            const start = this.formData[`${key}_start`];
            const end = this.formData[`${key}_end`];

            // A. Tanggal selesai tidak boleh lebih awal dari tanggal mulai tahap tersebut
            if (start && end && end < start) {
                return `Tanggal selesai untuk ${name} tidak boleh lebih awal dari tanggal mulai.`;
            }

            // B. Tahapan pertama tidak boleh mendahului tanggal mulai periode global
            if (i === 0 && start && this.formData.start && start < this.formData.start) {
                return `Tanggal mulai ${name} tidak boleh mendahului tanggal mulai periode (${this.formatIndoDate(this.formData.start)}).`;
            }

            // C. Tahapan ke-i tidak boleh sebelum tahapan ke-(i-1) selesai
            if (i > 0) {
                const prevKey = keys[i - 1];
                const prevName = this.stageDefs[prevKey]?.name || prevKey;
                const prevStart = this.formData[`${prevKey}_start`];
                const prevEnd = this.formData[`${prevKey}_end`];

                if (start && prevEnd && start <= prevEnd) {
                    return `Tahapan ke-${i + 1} (${name}) tidak boleh dimulai sebelum atau bersamaan dengan Tahapan ke-${i} (${prevName}) selesai (harus setelah ${this.formatIndoDate(prevEnd)}).`;
                }

                if (start && prevStart && start <= prevStart) {
                    return `Tahapan ke-${i + 1} (${name}) tidak boleh dimulai sebelum Tahapan ke-${i} (${prevName}).`;
                }
            }

            // D. Tahapan terakhir tidak boleh melebihi tanggal selesai periode global
            if (i === keys.length - 1 && end && this.formData.end && end > this.formData.end) {
                return `Tanggal selesai ${name} tidak boleh melebihi tanggal selesai periode (${this.formatIndoDate(this.formData.end)}).`;
            }
        }

        // 3. Validasi tumpang tindih dengan periode lain di database
        if (!this.formData.start) return null;
        const start = this.formData.start;
        const end = this.formData.end || start;
        const currentId = this.formData.id_periode || null;
        
        const conflictingPeriod = this.periods.find(p => {
            if (currentId && p.id_periode == currentId) return false;
            return p.periode_start <= end && start <= p.periode_end;
        });
        
        if (conflictingPeriod) {
            const pYear = conflictingPeriod.periode_start.split('-')[0];
            const suffix = this.getRomanSuffix(conflictingPeriod);
            const name = `Evaluasi KIP${suffix} ${pYear}`;
            return `Tanggal berbenturan dengan ${name} (${this.formatIndoDate(conflictingPeriod.periode_start)} s.d. ${this.formatIndoDate(conflictingPeriod.periode_end)})`;
        }
        return null;
    },
    openAddPeriode() {
        this.formMode = 'add';
        const defaultOrder = ['saq', 'visitasi', 'wawancara'];
        const stageKeys = this.resolveStageKeys(defaultOrder);
        this.formData = {
            id_periode: null,
            name: '', start: '', end: '',
            saq_start: '', saq_end: '',
            eval_start: '', eval_end: '',
            visit_start: '', visit_end: '',
            interview_start: '', interview_end: '',
            result_start: '', result_end: '',
            stage_order: defaultOrder,
            stage_keys: stageKeys
        };
        this.showFormModal = true;
    },
    openEditPeriode(d) {
        this.formMode = 'edit';
        const stageKeys = d.stage_keys || this.resolveStageKeys(d.stage_order);
        this.formData = {
            ...d,
            id_periode: d.id,
            start: d.start_raw || d.start || '',
            end: d.end_raw || d.end || '',
            stage_order: d.stage_order,
            stage_keys: stageKeys
        };
        this.showFormModal = true;
    },
    syncGlobalDates() {
        const keys = this.getStageKeys();
        if (!keys || keys.length === 0) return;

        let minStart = this.formData.start;
        let maxEnd = this.formData.end;

        keys.forEach(k => {
            const s = this.formData[k + '_start'];
            const e = this.formData[k + '_end'];
            if (s && (!minStart || s < minStart)) minStart = s;
            if (e && (!maxEnd || e > maxEnd)) maxEnd = e;
        });

        if (minStart) this.formData.start = minStart;
        if (maxEnd) this.formData.end = maxEnd;
    },
    init() {
        // Inisialisasi
    },
    async submitFormAjax(e) {
        this.syncGlobalDates();
        if (this.overlapMessage) {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: this.overlapMessage }
            }));
            return;
        }

        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type=submit]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': formData.get('_token')
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                sessionStorage.setItem('pending_toast', JSON.stringify({
                    type: 'success',
                    message: data.message
                }));
                this.showFormModal = false;
                window.location.reload();
            } else {
                let errorText = data.message || 'Terjadi kesalahan.';
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && data.errors[firstKey].length > 0) {
                        errorText = data.errors[firstKey][0];
                    }
                }
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: errorText }
                }));
            }
        } catch (err) {
            console.error(err);
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: 'Koneksi gagal atau terjadi kesalahan.' }
            }));
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    },
    async submitDeleteAjax(e) {
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type=submit]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': formData.get('_token')
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                sessionStorage.setItem('pending_toast', JSON.stringify({
                    type: 'success',
                    message: data.message
                }));
                this.showDeleteModal = false;
                window.location.reload();
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: data.message || 'Gagal menghapus periode.' }
                }));
            }
        } catch (err) {
            console.error(err);
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: 'Koneksi gagal atau terjadi kesalahan.' }
            }));
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }
}">
    <x-ui.page-header title="Manajemen Jadwal" description="Kelola daftar jadwal kegiatan evaluasi KIP disini."
        :breadcrumb="['Manajemen Jadwal' => '#']">
        <x-slot name="actions">
            <button @click="openAddPeriode()"
                class="inline-flex items-center gap-2 rounded-lg bg-biru2 px-5 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Periode
            </button>
        </x-slot>
    </x-ui.page-header>

    <!-- Stat Cards -->
    <section class="grid grid-cols-3 gap-2.5 sm:gap-4 md:gap-6 mb-6">
        <x-ui.card-stat label="TOTAL PERIODE" :value="$totalPeriode" color="primary">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-ui.card-stat>
        <x-ui.card-stat label="PERIODE AKTIF" :value="$periodeAktif" color="success">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-ui.card-stat>
        <x-ui.card-stat label="PERIODE SELESAI" :value="$periodeSelesai" color="info">
            <x-slot name="icon">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </x-slot>
        </x-ui.card-stat>
    </section>

    <div class="rounded-2xl bg-birumuda border border-biru5 px-6 py-5 mb-8 flex items-center gap-4 shadow-sm">
        <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <div class="font-bold text-biru6 text-sm mb-0.5">Informasi</div>
            <p class="text-sm text-biru6">1 Periode evaluasi terdiri dari 5 tahapan kegiatan.</p>
        </div>
    </div>

    <!-- Table -->
    <x-tables.monitoring-table title="Daftar Periode Evaluasi" :hasCards="true">
        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                <tr>
                    <th class="px-8 py-4 text-left">NAMA PERIODE</th>
                    <th class="px-8 py-4 text-left">RENTANG KEGIATAN</th>
                    <th class="px-8 py-4 text-center">STATUS</th>
                    <th class="px-8 py-4 text-center">AKSI</th>
                </tr>
            </thead>
        </x-slot>

        @php
            $mappedPeriods = $periods->map(function($p) {
                $name = $p->nama;
                $pStageOrder = \App\Models\MasterPembobotan::getActiveStageOrder($p->id_periode);

                $stageMap = [
                    'saq' => [
                        [
                            'key' => 'saq',
                            'name' => 'Pengisian SAQ',
                            'start' => $p->saq_start ? \Carbon\Carbon::parse($p->saq_start)->translatedFormat('d M Y') : '-',
                            'end' => $p->saq_end ? \Carbon\Carbon::parse($p->saq_end)->translatedFormat('d M Y') : '-',
                            'duration' => $p->saq_start && $p->saq_end ? \Carbon\Carbon::parse($p->saq_start)->diffInDays(\Carbon\Carbon::parse($p->saq_end)) + 1 . ' hari' : '-',
                            'status' => ($p->saq_start && $p->saq_end) ? ((now()->toDateString() > $p->saq_end) ? 'selesai' : ((now()->toDateString() >= $p->saq_start) ? 'aktif' : 'belum')) : 'belum',
                        ],
                        [
                            'key' => 'eval',
                            'name' => 'Evaluasi Data',
                            'start' => $p->eval_start ? \Carbon\Carbon::parse($p->eval_start)->translatedFormat('d M Y') : '-',
                            'end' => $p->eval_end ? \Carbon\Carbon::parse($p->eval_end)->translatedFormat('d M Y') : '-',
                            'duration' => $p->eval_start && $p->eval_end ? \Carbon\Carbon::parse($p->eval_start)->diffInDays(\Carbon\Carbon::parse($p->eval_end)) + 1 . ' hari' : '-',
                            'status' => ($p->eval_start && $p->eval_end) ? ((now()->toDateString() > $p->eval_end) ? 'selesai' : ((now()->toDateString() >= $p->eval_start) ? 'aktif' : 'belum')) : 'belum',
                        ],
                    ],
                    'visitasi' => [
                        [
                            'key' => 'visit',
                            'name' => 'Visitasi & Verif. Faktual',
                            'start' => $p->visit_start ? \Carbon\Carbon::parse($p->visit_start)->translatedFormat('d M Y') : '-',
                            'end' => $p->visit_end ? \Carbon\Carbon::parse($p->visit_end)->translatedFormat('d M Y') : '-',
                            'duration' => $p->visit_start && $p->visit_end ? \Carbon\Carbon::parse($p->visit_start)->diffInDays(\Carbon\Carbon::parse($p->visit_end)) + 1 . ' hari' : '-',
                            'status' => ($p->visit_start && $p->visit_end) ? ((now()->toDateString() > $p->visit_end) ? 'selesai' : ((now()->toDateString() >= $p->visit_start) ? 'aktif' : 'belum')) : 'belum',
                        ],
                    ],
                    'wawancara' => [
                        [
                            'key' => 'interview',
                            'name' => 'Wawancara',
                            'start' => $p->interview_start ? \Carbon\Carbon::parse($p->interview_start)->translatedFormat('d M Y') : '-',
                            'end' => $p->interview_end ? \Carbon\Carbon::parse($p->interview_end)->translatedFormat('d M Y') : '-',
                            'duration' => $p->interview_start && $p->interview_end ? \Carbon\Carbon::parse($p->interview_start)->diffInDays(\Carbon\Carbon::parse($p->interview_end)) + 1 . ' hari' : '-',
                            'status' => ($p->interview_start && $p->interview_end) ? ((now()->toDateString() > $p->interview_end) ? 'selesai' : ((now()->toDateString() >= $p->interview_start) ? 'aktif' : 'belum')) : 'belum',
                        ],
                    ],
                ];

                $stages = [];
                $stageKeys = [];
                foreach ($pStageOrder as $stgKey) {
                    if (isset($stageMap[$stgKey])) {
                        foreach ($stageMap[$stgKey] as $stgItem) {
                            $stages[] = $stgItem;
                            $stageKeys[] = $stgItem['key'];
                        }
                    }
                }
                $stages[] = [
                    'key' => 'result',
                    'name' => 'Penilaian Akhir',
                    'start' => $p->result_start ? \Carbon\Carbon::parse($p->result_start)->translatedFormat('d M Y') : '-',
                    'end' => $p->result_end ? \Carbon\Carbon::parse($p->result_end)->translatedFormat('d M Y') : '-',
                    'duration' => $p->result_start && $p->result_end ? \Carbon\Carbon::parse($p->result_start)->diffInDays(\Carbon\Carbon::parse($p->result_end)) + 1 . ' hari' : '-',
                    'status' => ($p->result_start && $p->result_end) ? ((now()->toDateString() > $p->result_end) ? 'selesai' : ((now()->toDateString() >= $p->result_start) ? 'aktif' : 'belum')) : 'belum',
                ];
                $stageKeys[] = 'result';

                return [
                    'id' => $p->id_periode,
                    'name' => $name,
                    'start' => $p->periode_start ? \Carbon\Carbon::parse($p->periode_start)->translatedFormat('d M Y') : '-',
                    'start_raw' => $p->periode_start,
                    'end' => $p->periode_end ? \Carbon\Carbon::parse($p->periode_end)->translatedFormat('d M Y') : '-',
                    'end_raw' => $p->periode_end,
                    'saq_start' => $p->saq_start,
                    'saq_end' => $p->saq_end,
                    'eval_start' => $p->eval_start,
                    'eval_end' => $p->eval_end,
                    'visit_start' => $p->visit_start,
                    'visit_end' => $p->visit_end,
                    'interview_start' => $p->interview_start,
                    'interview_end' => $p->interview_end,
                    'result_start' => $p->result_start,
                    'result_end' => $p->result_end,
                    'stages' => $stages,
                    'stage_order' => $pStageOrder,
                    'stage_keys' => $stageKeys,
                ];
            })->toArray();
        @endphp

        <x-slot name="cards">
            @php
                $stageColors = [
                    'selesai' => ['badge' => 'bg-biru4/10 text-biru4', 'dot' => 'bg-biru4', 'num' => 'bg-biru4 text-white'],
                    'aktif' => ['badge' => 'bg-hijau/10 text-hijau', 'dot' => 'bg-hijau', 'num' => 'bg-hijau text-white'],
                    'belum' => ['badge' => 'bg-abubiru text-abu', 'dot' => 'bg-abubiru', 'num' => 'bg-abubiru text-biru3'],
                ];
            @endphp
            @forelse ($mappedPeriods as $p)
                @php
                    $today = now()->toDateString();
                    if ($p['start_raw'] <= $today && $p['end_raw'] >= $today) {
                        $status = 'aktif';
                    } elseif ($p['start_raw'] > $today) {
                        $status = 'belum';
                    } else {
                        $status = 'selesai';
                    }
                @endphp
                <div class="bg-white rounded-2xl border border-abubiru p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button @click="expandedPeriods.includes({{ $p['id'] }}) ? expandedPeriods = expandedPeriods.filter(id => id !== {{ $p['id'] }}) : expandedPeriods.push({{ $p['id'] }})"
                                class="text-biru3 hover:text-biru2 transition p-1 hover:bg-abubiru/20 rounded-lg shrink-0 cursor-pointer">
                                <svg class="w-4 h-4 transform transition-transform" :class="expandedPeriods.includes({{ $p['id'] }}) ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            @if ($status === 'aktif')
                                <span class="w-2.5 h-2.5 rounded-full bg-hijau animate-pulse shrink-0"></span>
                            @elseif ($status === 'selesai')
                                <span class="w-2.5 h-2.5 rounded-full bg-biru4 shrink-0"></span>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-biru4/40 shrink-0"></span>
                            @endif
                            <div class="font-bold text-biru1 text-sm leading-snug">{{ $p['name'] }}</div>
                        </div>
                        @if ($status === 'aktif')
                            <span class="inline-flex items-center gap-1.5 bg-hijau/10 text-hijau px-2.5 py-0.5 rounded-full text-xs font-bold uppercase shrink-0">
                                Aktif
                            </span>
                        @elseif ($status === 'selesai')
                            <span class="inline-flex items-center gap-1.5 bg-biru4/10 text-biru4 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase shrink-0">
                                Selesai
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-abubiru text-biru4 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase shrink-0">
                                Belum
                            </span>
                        @endif
                    </div>

                    <div class="text-xs text-biru3 font-medium flex items-center justify-between border-t border-abubiru/30 pt-2.5">
                        <span class="text-biru4 font-semibold">Rentang Kegiatan:</span>
                        <span class="font-bold text-biru1">{{ $p['start'] }} — {{ $p['end'] }}</span>
                    </div>

                    <div class="flex items-center justify-between pt-2.5 border-t border-abubiru/30">
                        <button 
                            type="button"
                            @click="expandedPeriods.includes({{ $p['id'] }}) ? expandedPeriods = expandedPeriods.filter(id => id !== {{ $p['id'] }}) : expandedPeriods.push({{ $p['id'] }})"
                            class="text-xs font-bold text-biru2 hover:underline flex items-center gap-1 cursor-pointer"
                        >
                            <span x-text="expandedPeriods.includes({{ $p['id'] }}) ? 'Sembunyikan Tahapan' : 'Lihat 5 Tahapan'"></span>
                        </button>

                        <div class="flex items-center gap-2">
                            {{-- Edit --}}
                            <button title="Edit"
                                @click="openEditPeriode(@js($p))"
                                class="w-8 h-8 rounded-lg bg-oren2 border border-oren2/30 flex items-center justify-center text-white hover:bg-oren2/80 transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            {{-- Hapus --}}
                            <button title="Hapus"
                                @click="showDeleteModal = true; deleteTarget = {{ $p['id'] }}; deleteLabel = '{{ addslashes($p['name']) }}'"
                                class="w-8 h-8 rounded-lg bg-merah1 border border-merah1/30 flex items-center justify-center text-white hover:bg-merah1/80 transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Detail Tahapan Mobile --}}
                    <div x-show="expandedPeriods.includes({{ $p['id'] }})" x-cloak class="pt-3 border-t border-abubiru/30 space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-biru3 mb-2">Detail Jadwal Tahapan</h4>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach ($p['stages'] as $stg)
                                @php
                                    $sc = $stageColors[$stg['status']];
                                @endphp
                                <div class="p-2.5 rounded-lg border border-abubiru/20 bg-gray-50/70 flex flex-col gap-1.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-xs font-black {{ $sc['num'] }} shrink-0">
                                                {{ $loop->iteration }}
                                            </span>
                                            <span class="text-xs font-bold text-biru1 uppercase tracking-wide leading-none">{{ $stg['name'] }}</span>
                                        </div>
                                        <span class="inline-flex rounded-full px-2 py-0.5 font-bold uppercase {{ $sc['badge'] }} text-xs shrink-0">
                                            {{ $stg['status'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-biru3 font-medium">
                                        <span>{{ $stg['start'] }} s.d. {{ $stg['end'] }}</span>
                                        <span class="font-bold text-biru2">Durasi: {{ $stg['duration'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm font-semibold text-biru3 italic bg-white rounded-xl border border-abubiru">
                    Belum ada data periode evaluasi.
                </div>
            @endforelse
        </x-slot>

        @foreach ($mappedPeriods as $p)
            @php
                $today = now()->toDateString();
                if ($p['start_raw'] <= $today && $p['end_raw'] >= $today) {
                    $status = 'aktif';
                } elseif ($p['start_raw'] > $today) {
                    $status = 'belum';
                } else {
                    $status = 'selesai';
                }
            @endphp
            <tr class="border-t border-abubiru/30 {{ $status === 'aktif' ? 'bg-yellow-50 hover:bg-yellow-100/70' : 'hover:bg-abubiru/10' }} transition">
                <td class="px-8 py-5">
                    <div class="flex items-center gap-3">
                        <!-- Chevron expand toggle -->
                        <button @click="expandedPeriods.includes({{ $p['id'] }}) ? expandedPeriods = expandedPeriods.filter(id => id !== {{ $p['id'] }}) : expandedPeriods.push({{ $p['id'] }})"
                            class="text-biru3 hover:text-biru2 transition p-1 hover:bg-abubiru/20 rounded-lg shrink-0">
                            <svg class="w-4 h-4 transform transition-transform" :class="expandedPeriods.includes({{ $p['id'] }}) ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        @if ($status === 'aktif')
                            <span class="w-2 h-2 rounded-full bg-hijau animate-pulse shrink-0"></span>
                        @elseif ($status === 'selesai')
                            <span class="w-2 h-2 rounded-full bg-biru4 shrink-0"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-biru4/40 shrink-0"></span>
                        @endif
                        <span class="font-bold text-biru1">{{ $p['name'] }}</span>
                    </div>
                </td>
                <td class="px-8 py-5 text-biru3 font-medium">
                    {{ $p['start'] }} — {{ $p['end'] }}
                </td>
                <td class="px-8 py-5 text-center">
                    @if ($status === 'aktif')
                        <span
                            class="inline-flex items-center gap-1.5 bg-hijau/10 text-hijau px-3 py-1 rounded-full text-xs font-bold uppercase">
                            Aktif
                        </span>
                    @elseif ($status === 'selesai')
                        <span
                            class="inline-flex items-center gap-1.5 bg-biru4/10 text-biru4 px-3 py-1 rounded-full text-xs font-bold uppercase">
                            Selesai
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-1.5 bg-abubiru text-biru4 px-3 py-1 rounded-full text-xs font-bold uppercase">
                            Belum
                        </span>
                    @endif
                </td>
                <td class="px-8 py-5 text-center">
                    <div class="flex items-center justify-center gap-2">
                        {{-- Edit --}}
                        <button title="Edit"
                            @click="openEditPeriode(@js($p))"
                            class="w-9 h-9 rounded-lg bg-oren2 border border-oren2/30 flex items-center justify-center text-oren2 hover:bg-oren2/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                                                           m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>

                        {{-- Hapus --}}
                        <button title="Hapus"
                            @click="showDeleteModal = true; deleteTarget = {{ $p['id'] }}; deleteLabel = '{{ addslashes($p['name']) }}'"
                            class="w-9 h-9 rounded-lg bg-merah1 border border-merah1/30 flex items-center justify-center text-merah1 hover:bg-merah1/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="white" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7
                                                                           m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            @php
                $stageColors = [
                    'selesai' => ['badge' => 'bg-biru4/10 text-biru4', 'dot' => 'bg-biru4', 'num' => 'bg-biru4 text-white'],
                    'aktif' => ['badge' => 'bg-hijau/10 text-hijau', 'dot' => 'bg-hijau', 'num' => 'bg-hijau text-white'],
                    'belum' => ['badge' => 'bg-abubiru text-abu', 'dot' => 'bg-abubiru', 'num' => 'bg-abubiru text-biru3'],
                ];
            @endphp
            <tr x-show="expandedPeriods.includes({{ $p['id'] }})" x-cloak class="border-t border-abubiru/30 bg-gray-50/50">
                <td colspan="4" class="px-8 py-4 border-t border-b border-abubiru/30">
                     <div class="rounded-xl border border-abubiru/30 bg-white p-4 shadow-sm">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-biru3 mb-3">Detail Jadwal Tahapan</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                            @foreach ($p['stages'] as $stg)
                                @php
                                    $sc = $stageColors[$stg['status']];
                                @endphp
                                <div class="p-3 rounded-lg border border-abubiru/20 bg-gray-50/50 hover:bg-white transition flex flex-col justify-between h-full">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-black {{ $sc['num'] }} shrink-0">
                                                {{ $loop->iteration }}
                                            </span>
                                            <span class="text-xs font-bold text-biru1 uppercase tracking-wide leading-none">{{ $stg['name'] }}</span>
                                        </div>
                                        <div class="text-xs text-biru3 font-medium">
                                            {{ $stg['start'] }} s.d. {{ $stg['end'] }}
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between border-t border-abubiru/20 pt-2 text-xs">
                                        <span class="font-bold text-biru2">durasi kegiatan : {{ $stg['duration'] }}</span>
                                        <span class="inline-flex rounded-full px-2 py-0.5 font-bold uppercase {{ $sc['badge'] }} text-xs shrink-0">
                                            {{ $stg['status'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-tables.monitoring-table>

    {{-- Modal Form Edit Periode --}}
    <x-modals.form add-title="Tambah Periode" edit-title="Edit Periode" add-action="{{ url('/super-evaluator/periode') }}" edit-action="{{ url('/super-evaluator/periode') }}" size="2xl" @submit.prevent="submitFormAjax($event)">
        <!-- Highlighted Header: Global Schedule -->
        <div class="mb-5 bg-biru1/5 p-3.5 sm:p-5 rounded-2xl border border-biru4/20 shadow-sm space-y-3 sm:space-y-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                <div class="flex-1 w-full">
                    <h4 class="text-xs font-extrabold uppercase tracking-widest text-biru4">Jadwal Utama Periode (Global)</h4>
                    <div class="text-lg sm:text-xl font-black text-biru1 mt-1">
                        <span x-text="formData.start ? 'Evaluasi KIP' + getRomanSuffix({ id_periode: formData.id_periode || null, periode_start: formData.start }) + ' ' + formData.start.split('-')[0] : 'Evaluasi KIP -'"></span>
                    </div>
                    <p class="text-xs text-biru3/80 mt-1">Nama periode dihasilkan otomatis dari tahun Tanggal Mulai.</p>
                </div>
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                    <div class="w-full min-w-0 sm:w-35">
                        <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1">Mulai</label>
                        <input type="date" name="start" x-model="formData.start"
                            class="w-full min-w-0 border border-abubiru rounded-xl px-2 sm:px-3 py-1.5 sm:py-2 bg-white text-xs sm:text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                    </div>
                    <div class="w-full min-w-0 sm:w-35">
                        <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1">Selesai</label>
                        <input type="date" name="end" x-model="formData.end" :min="formData.start"
                            class="w-full min-w-0 border border-abubiru rounded-xl px-2 sm:px-3 py-1.5 sm:py-2 bg-white text-xs sm:text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overlap Warning Banner -->
        <template x-if="overlapMessage">
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 sm:px-5 py-3.5 sm:py-4 rounded-2xl flex items-center gap-3 text-xs sm:text-sm font-semibold shadow-sm">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span x-text="overlapMessage"></span>
            </div>
        </template>

        <!-- Stage Schedules (Ordered by stage_order) -->
        <div class="space-y-3 sm:space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-widest text-biru3 border-b border-abubiru/30 pb-2 mb-2 sm:mb-3">Pengaturan Jadwal Tahapan Kegiatan</h4>
            
            <template x-for="(k, idx) in getStageKeys()" :key="k">
                <div class="p-3 sm:p-0 rounded-xl bg-slate-50/80 sm:bg-transparent border border-slate-200/60 sm:border-0 sm:border-b sm:border-abubiru/10 flex flex-col sm:flex-row sm:items-center justify-between py-2 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-biru2 text-white font-bold text-xs flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                        <span class="text-xs sm:text-sm font-bold text-biru1" x-text="stageDefs[k]?.name || k"></span>
                    </div>
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <div class="w-full min-w-0 sm:w-35">
                            <span class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-0.5 sm:hidden">Mulai</span>
                            <input type="date" :name="k + '_start'" x-model="formData[k + '_start']" @change="syncGlobalDates()"
                                class="w-full min-w-0 border border-abubiru rounded-xl px-2 sm:px-3 py-1.5 sm:py-2 bg-white text-xs sm:text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                        </div>
                        <div class="w-full min-w-0 sm:w-35">
                            <span class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-0.5 sm:hidden">Selesai</span>
                            <input type="date" :name="k + '_end'" x-model="formData[k + '_end']" :min="formData[k + '_start']" @change="syncGlobalDates()"
                                class="w-full min-w-0 border border-abubiru rounded-xl px-2 sm:px-3 py-1.5 sm:py-2 bg-white text-xs sm:text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-modals.form>

    {{-- Modal Konfirmasi Hapus Periode --}}
    <x-modals.confirm-delete
        title="Hapus Periode"
        message="Apakah Anda yakin ingin menghapus periode ini? Semua data tahapan dan jadwal terkait akan ikut terhapus."
        action-url="{{ url('/super-evaluator/periode') }}"
        @submit.prevent="submitDeleteAjax($event)"
    />
</div>

</x-layouts.app>
