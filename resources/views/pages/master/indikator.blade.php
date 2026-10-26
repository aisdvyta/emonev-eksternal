<x-layouts.app title="Manajemen Indikator KIP - HaloKIP" sidebar="{{ Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super_evaluator' : 'evaluator' }}">
    @php
        $rolePrefix = Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super-evaluator' : 'evaluator';
        $indicatorsData = [];
        foreach ($indicators as $ind) {
            $indicatorsData[$ind->id_indicators] = [
                'id' => $ind->id_indicators,
                'nama' => $ind->nama_indikator,
                'deskripsi' => $ind->deskripsi_indikator ?? '',
            ];
        }
    @endphp

    <div x-data="{
        showFormModal: {{ $errors->any() ? 'true' : 'false' }},
        formMode: {{ old('_method') === 'PUT' ? "'edit'" : "'add'" }},
        formData: {
            id: {{ old('id') ?? 'null' }},
            nama: {{ json_encode(old('nama_indikator') ?? '') }},
            deskripsi: {{ json_encode(old('deskripsi_indikator') ?? '') }}
        },
        showDeleteModal: false,
        deleteTarget: null,
        deleteLabel: '',
        indicators: {{ json_encode($indicatorsData) }},
        openAdd() {
            this.formMode = 'add';
            this.formData = { id: null, nama: '', deskripsi: '' };
            this.showFormModal = true;
        },
        openEdit(id) {
            this.formMode = 'edit';
            const item = this.indicators[id];
            this.formData = { id: item.id, nama: item.nama, deskripsi: item.deskripsi };
            this.showFormModal = true;
        },
        openDelete(id) {
            this.deleteTarget = id;
            this.deleteLabel = this.indicators[id].nama;
            this.showDeleteModal = true;
        }
    }">
        <x-ui.page-header title="Manajemen Indikator KIP" description="Kelola daftar indikator utama KIP."
            :breadcrumb="['Master Kuesioner' => '#', 'Indikator' => '#']">
            <x-slot name="actions">
                @if (!$hasActivePeriod)
                    <button @click="openAdd()"
                        class="inline-flex items-center gap-2 rounded-lg bg-biru2 px-5 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Indikator
                    </button>
                @else
                    <button disabled title="Master data dikunci karena periode KIP sedang aktif"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-5 py-3 text-sm font-bold text-slate-500 cursor-not-allowed shadow-none border border-slate-300">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Master Dikunci
                    </button>
                @endif
            </x-slot>
        </x-ui.page-header>

        <!-- Active Period Lock Warning Banner -->
        @if ($hasActivePeriod)
            <div class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-4 flex items-center gap-3.5 mb-6 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-sm font-bold text-amber-900">Master Data Dikunci (Read-Only)</span>
                    <p class="text-xs sm:text-sm text-amber-800 font-medium mt-0.5">
                        Terdapat periode KIP yang sedang aktif ({{ $activePeriod?->nama ?? 'Periode Aktif' }}). Pengubahan, penambahan, dan penghapusan master data ditangguhkan selama periode berlangsung.
                    </p>
                </div>
            </div>
        @endif

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="rounded-xl bg-hijau/10 border border-hijau/30 px-5 py-4 flex items-center gap-3 mb-6 shadow-sm">
                <svg class="w-5 h-5 text-hijau shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-sm font-semibold text-hijau">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl bg-merah2/10 border border-merah2/30 px-5 py-4 flex items-center gap-3 mb-6 shadow-sm">
                <svg class="w-5 h-5 text-merah2 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <p class="text-sm font-semibold text-merah2">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Panel Table -->
        <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-abubiru bg-biru2 flex items-center justify-between">
                <div>
                    <h2 class="text-base uppercase font-bold text-white">Daftar Indikator KIP</h2>
                    <p class="text-xs text-white/80 font-medium mt-0.5">Total {{ $indicators->total() }} Indikator Terdaftar</p>
                </div>
            </div>

            <!-- Table (Desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-biru5 border-b border-abubiru">
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-14">NO</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2 w-[28%]">NAMA INDIKATOR</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2">DESKRIPSI</th>
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-32">SUB-INDIKATOR</th>
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-32">PERTANYAAN</th>
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-28">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-abubiru/60">
                        @forelse ($indicators as $index => $indicator)
                            <tr class="hover:bg-putihbg/70 transition">
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex w-7 h-7 rounded-full bg-biru5/50 text-biru2 font-bold items-center justify-center text-xs">
                                        {{ $indicators->firstItem() + $index }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-bold text-biru1">{{ $indicator->nama_indikator }}</td>
                                <td class="px-5 py-4 text-xs text-biru3 leading-relaxed">{{ $indicator->deskripsi_indikator ?? '-' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <a href="{{ route($rolePrefix . '.manage-sub-indikator', ['tab' => \App\Helpers\SecureId::encrypt($indicator->id_indicators)]) }}" wire:navigate.hover
                                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-biru5 text-biru2 hover:bg-biru2 hover:text-white transition duration-200 cursor-pointer">
                                        {{ $indicator->sub_indikators_count }} Sub
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <a href="{{ route($rolePrefix . '.manage-kuesioner', ['tab' => \App\Helpers\SecureId::encrypt($indicator->id_indicators)]) }}" wire:navigate.hover
                                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-biru2/10 text-biru2 hover:bg-biru2 hover:text-white transition duration-200 cursor-pointer">
                                        {{ $indicator->pertanyaan_count }} Pertanyaan
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if (!$hasActivePeriod)
                                        <div class="flex items-center justify-center gap-2">
                                            <button title="Edit" @click="openEdit({{ $indicator->id_indicators }})"
                                                class="w-9 h-9 rounded-lg bg-oren2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button title="Hapus" @click="openDelete({{ $indicator->id_indicators }})"
                                                class="w-9 h-9 rounded-lg bg-merah2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400 italic">Terkunci</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-biru3 italic">
                                    Belum ada data indikator.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Card List (Mobile) -->
            <div class="block md:hidden divide-y divide-abubiru/60">
                @forelse ($indicators as $index => $indicator)
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-biru2 text-white font-bold text-xs flex items-center justify-center">
                                    {{ $indicators->firstItem() + $index }}
                                </span>
                                <h3 class="font-bold text-biru1 text-sm">{{ $indicator->nama_indikator }}</h3>
                            </div>
                            @if (!$hasActivePeriod)
                                <div class="flex items-center gap-1.5">
                                    <button title="Edit" @click="openEdit({{ $indicator->id_indicators }})"
                                        class="w-8 h-8 rounded-lg bg-oren2 flex items-center justify-center text-white cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button title="Hapus" @click="openDelete({{ $indicator->id_indicators }})"
                                        class="w-8 h-8 rounded-lg bg-merah2 flex items-center justify-center text-white cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <span class="text-xs font-semibold text-slate-400 italic">Terkunci</span>
                            @endif
                        </div>
                        @if ($indicator->deskripsi_indikator)
                            <p class="text-xs text-biru3">{{ $indicator->deskripsi_indikator }}</p>
                        @endif
                        <div class="flex items-center gap-2 pt-1">
                            <a href="{{ route($rolePrefix . '.manage-sub-indikator', ['tab' => \App\Helpers\SecureId::encrypt($indicator->id_indicators)]) }}" wire:navigate.hover
                               class="text-xs font-semibold bg-biru5 text-biru2 px-2.5 py-0.5 rounded-full hover:bg-biru2 hover:text-white transition">
                                {{ $indicator->sub_indikators_count }} Sub-Indikator
                            </a>
                            <a href="{{ route($rolePrefix . '.manage-kuesioner', ['tab' => \App\Helpers\SecureId::encrypt($indicator->id_indicators)]) }}" wire:navigate.hover
                               class="text-xs font-semibold bg-biru2/10 text-biru2 px-2.5 py-0.5 rounded-full hover:bg-biru2 hover:text-white transition">
                                {{ $indicator->pertanyaan_count }} Pertanyaan
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-biru3 italic text-sm">
                        Belum ada data indikator.
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-abubiru bg-white">
                <x-tables.pagination :paginator="$indicators" />
            </div>
        </div>

        <!-- Modal Form Tambah/Edit Indikator -->
        <x-modals.form add-title="Tambah Indikator KIP" edit-title="Edit Indikator KIP"
            add-action="{{ url($rolePrefix . '/indikator') }}" edit-action="{{ url($rolePrefix . '/indikator') }}"
            size="md">
            <div>
                <input type="hidden" name="id" x-model="formData.id">

                <div class="py-4 border-b border-abubiru/60">
                    <label class="block text-sm font-bold text-biru1 mb-2">NAMA INDIKATOR</label>
                    <input type="text" name="nama_indikator" x-model="formData.nama" required
                        placeholder="Contoh: Kualitas Informasi"
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition" />
                </div>

                <div class="py-4">
                    <label class="block text-sm font-bold text-biru1 mb-2">DESKRIPSI INDIKATOR</label>
                    <textarea name="deskripsi_indikator" x-model="formData.deskripsi" rows="3"
                        placeholder="Ketikkan deskripsi indikator disini..."
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition resize-none placeholder:text-abumuda"></textarea>
                </div>
            </div>
        </x-modals.form>

        <!-- Modal Konfirmasi Hapus -->
        <x-modals.confirm-delete title="Hapus Indikator"
            message="Apakah Anda yakin ingin menghapus indikator ini? Menghapus indikator akan mempengaruhi sub-indikator dan pertanyaan terkait."
            action-url="{{ url($rolePrefix . '/indikator') }}" />
    </div>
</x-layouts.app>
