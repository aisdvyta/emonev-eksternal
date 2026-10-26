<x-layouts.app title="Manajemen Sub-Indikator KIP - HaloKIP" sidebar="{{ Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super_evaluator' : 'evaluator' }}">
    @php
        $rolePrefix = Auth::user()?->role?->nama_role === 'super_evaluator' ? 'super-evaluator' : 'evaluator';
        $subIndicatorsData = [];
        foreach ($subIndicators as $sub) {
            $subIndicatorsData[$sub->id_sub_indicators] = [
                'id' => $sub->id_sub_indicators,
                'id_indicators' => $sub->id_indicators,
                'nama' => $sub->nama_sub_indikator,
                'nama_indikator' => $sub->indikator->nama_indikator ?? '-',
            ];
        }
        $indicatorsMap = [];
        foreach ($indicators as $ind) {
            $indicatorsMap[$ind->id_indicators] = $ind->nama_indikator;
        }
    @endphp

    <div x-data="{
        showFormModal: {{ $errors->any() ? 'true' : 'false' }},
        formMode: {{ old('_method') === 'PUT' ? "'edit'" : "'add'" }},
        formData: {
            id: {{ old('id') ?? 'null' }},
            id_indicators: {{ json_encode(old('id_indicators') ?? ($activeTab ?? ($indicators->first()->id_indicators ?? ''))) }},
            nama: {{ json_encode(old('nama_sub_indikator') ?? '') }}
        },
        showDeleteModal: false,
        deleteTarget: null,
        deleteLabel: '',
        subIndicators: {{ json_encode($subIndicatorsData) }},
        indicatorsMap: {{ json_encode($indicatorsMap) }},
        openAdd() {
            this.formMode = 'add';
            this.formData = { id: null, id_indicators: {{ json_encode($activeTab ?? ($indicators->first()->id_indicators ?? '')) }}, nama: '' };
            this.showFormModal = true;
        },
        openEdit(id) {
            this.formMode = 'edit';
            const item = this.subIndicators[id];
            this.formData = { id: item.id, id_indicators: item.id_indicators, nama: item.nama };
            this.showFormModal = true;
        },
        openDelete(id) {
            this.deleteTarget = id;
            this.deleteLabel = this.subIndicators[id].nama;
            this.showDeleteModal = true;
        }
    }">
        <x-ui.page-header title="Manajemen Sub-Indikator KIP" description="Kelola daftar sub-indikator KIP berdasarkan indikator induk."
            :breadcrumb="['Master Kuesioner' => '#', 'Sub-Indikator' => '#']">
            <x-slot name="actions">
                @if (!$hasActivePeriod)
                    <button @click="openAdd()"
                        class="inline-flex items-center gap-2 rounded-lg bg-biru2 px-5 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Sub-Indikator
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

        <!-- Mobile Custom Dropdown Selection for Indicators (Visible on Mobile HP Only) -->
        <div class="block sm:hidden mb-4 relative" x-data="{ openIndSelect: false }">
            <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1.5">
                Pilih Indikator:
            </label>
            @php
                $activeIndObj = $indicators->firstWhere('id_indicators', $activeTab) ?? $indicators->first();
            @endphp
            <!-- Trigger Button -->
            <button type="button" @click="openIndSelect = !openIndSelect" @click.away="openIndSelect = false"
                class="w-full bg-biru4 text-white font-bold text-xs sm:text-sm rounded-xl border border-biru4 py-3 pl-3.5 pr-9 shadow-sm focus:outline-none flex items-center justify-between transition cursor-pointer relative">
                <span class="truncate pr-2 font-bold">{{ $activeIndObj->nama_indikator ?? 'Pilih Indikator' }} ({{ $activeIndObj->sub_indikators_count ?? 0 }} sub)</span>
                <svg class="w-4 h-4 shrink-0 text-white transition-transform duration-200" :class="openIndSelect ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Custom Dropdown Menu Panel -->
            <div x-show="openIndSelect" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                 class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-abubiru rounded-xl shadow-xl py-1.5 z-50 divide-y divide-abubiru/40 overflow-hidden max-w-full">
                @foreach ($indicators as $ind)
                    @php
                        $isActive = $ind->id_indicators == $activeTab;
                        $encryptedTabId = \App\Helpers\SecureId::encrypt($ind->id_indicators);
                        $url = url($rolePrefix . '/manage-sub-indikator') . '?tab=' . $encryptedTabId;
                    @endphp
                    <a href="{{ $url }}" wire:navigate.hover
                       @class([
                           'flex items-center justify-between px-4 py-2.5 text-xs font-semibold transition',
                           'bg-biru5/40 text-biru1 font-extrabold' => $isActive,
                           'text-biru2 hover:bg-putihbg hover:text-biru1' => !$isActive,
                       ])>
                        <span class="truncate pr-2">{{ $ind->nama_indikator }}</span>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-bold shrink-0',
                            'bg-biru2 text-white' => $isActive,
                            'bg-biru5 text-biru2' => !$isActive,
                        ])>
                            {{ $ind->sub_indikators_count }} sub
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Indicator Overview Tabs (Visible on Desktop/Tablet) — Interactive Horizontal Scroll with Arrow Controls -->
        <div x-data="{
            canScrollLeft: false,
            canScrollRight: false,
            updateScrollState() {
                const el = this.$refs.tabContainer;
                if (!el) return;
                this.canScrollLeft = el.scrollLeft > 2;
                this.canScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 2;
            },
            scrollLeft() {
                this.$refs.tabContainer.scrollBy({ left: -220, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.tabContainer.scrollBy({ left: 220, behavior: 'smooth' });
            }
        }" x-init="updateScrollState(); $nextTick(() => updateScrollState())"
           class="hidden sm:block relative mb-0 z-10 group">

            <!-- Left Arrow Button -->
            <button type="button" @click="scrollLeft()" x-show="canScrollLeft" x-cloak
                title="Geser ke kiri"
                class="absolute -left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Scrollable Tab Container -->
            <div x-ref="tabContainer"
                @scroll="updateScrollState()"
                @wheel.prevent="$refs.tabContainer.scrollLeft += $event.deltaY"
                class="flex items-stretch overflow-x-auto overflow-y-hidden w-full pb-0.5 -mb-px custom-scrollbar scrollbar-thin scrollbar-thumb-biru4/30 scrollbar-track-transparent scroll-smooth">
                @foreach ($indicators as $ind)
                    @php
                        $isActive = $ind->id_indicators == $activeTab;
                        $encryptedTabId = \App\Helpers\SecureId::encrypt($ind->id_indicators);
                    @endphp
                    <a href="{{ url($rolePrefix . '/manage-sub-indikator') }}?tab={{ $encryptedTabId }}" wire:navigate.hover
                        @class([
                            'shrink-0 min-w-35 md:min-w-[calc(100%/6)] relative px-3 py-3 text-center cursor-pointer transition-all select-none border-biru5 border-t border-l border-r overflow-hidden rounded-tl-xl rounded-tr-xl flex flex-col justify-center',
                            'bg-biru2 border-biru2 z-20 text-white shadow-sm -mb-px pb-3.5' => $isActive,
                            'bg-white border-abubiru hover:bg-putihbg text-biru2' => !$isActive,
                        ])
                        @if ($isActive) style="background: radial-gradient(ellipse 140% 140% at 100% 110%, #2563A0 0%, transparent 55%), #184D77;" @endif>
                        <div @class([
                            'text-sm md:text-sm font-bold leading-tight line-clamp-2',
                            'text-white' => $isActive,
                            'text-biru2' => !$isActive,
                        ])>
                            {{ $ind->nama_indikator }}
                        </div>
                        <div @class([
                            'text-xs md:text-sm mt-1 font-medium',
                            'text-white/80' => $isActive,
                            'text-biru4' => !$isActive,
                        ])>
                            {{ $ind->sub_indikators_count }} sub-indikator
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Right Arrow Button -->
            <button type="button" @click="scrollRight()" x-show="canScrollRight" x-cloak
                title="Geser ke kanan"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white border border-abubiru shadow-lg flex items-center justify-center text-biru2 hover:bg-biru5 transition hover:scale-110">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Panel Table -->
        <div class="rounded-2xl bg-white border border-abubiru sm:border-t-0 shadow-sm overflow-hidden mb-6 sm:rounded-tl-none">
            <div class="px-6 py-5 border-b border-abubiru bg-biru2 flex items-center justify-between">
                <div>
                    <h2 class="text-base uppercase font-bold text-white">
                        Daftar Sub-Indikator — {{ $activeInd->nama_indikator ?? '' }}
                    </h2>
                    <p class="text-xs text-white/80 font-medium mt-0.5">Total {{ $subIndicators->total() }} Sub-Indikator Terdaftar</p>
                </div>
            </div>

            <!-- Table (Desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-biru5 border-b border-abubiru">
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-14">NO</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-biru2 w-[45%]">NAMA SUB-INDIKATOR</th>
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-36">JUMLAH PERTANYAAN</th>
                            <th class="px-5 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-biru2 w-28">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-abubiru/60">
                        @forelse ($subIndicators as $sub)
                            <tr class="hover:bg-putihbg/70 transition">
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex w-7 h-7 rounded-full bg-biru5/50 text-biru2 font-bold items-center justify-center text-xs">
                                        {{ ($subIndicators->currentPage() - 1) * $subIndicators->perPage() + $loop->iteration }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-bold text-biru1">
                                    {{ $sub->nama_sub_indikator }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <a href="{{ route($rolePrefix . '.manage-kuesioner', ['tab' => \App\Helpers\SecureId::encrypt($activeInd->id_indicators)]) }}" wire:navigate.hover
                                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-biru2/10 text-biru2 hover:bg-biru2 hover:text-white transition duration-200 cursor-pointer">
                                        {{ $sub->pertanyaan_count }} Pertanyaan
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if (!$hasActivePeriod)
                                        <div class="flex items-center justify-center gap-2">
                                            <button title="Edit" @click="openEdit({{ $sub->id_sub_indicators }})"
                                                class="w-9 h-9 rounded-lg bg-oren2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button title="Hapus" @click="openDelete({{ $sub->id_sub_indicators }})"
                                                class="w-9 h-9 rounded-lg bg-merah2 flex items-center justify-center text-white hover:opacity-80 transition shadow-sm">
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
                                <td colspan="4" class="px-5 py-8 text-center text-biru3 italic">
                                    Belum ada data sub-indikator untuk indikator {{ $activeInd->nama_indikator ?? '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Card List (Mobile) -->
            <div class="block md:hidden divide-y divide-abubiru/60">
                @forelse ($subIndicators as $sub)
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-biru2 text-white font-bold text-xs flex items-center justify-center">
                                    {{ ($subIndicators->currentPage() - 1) * $subIndicators->perPage() + $loop->iteration }}
                                </span>
                                <h3 class="font-bold text-biru1 text-sm">{{ $sub->nama_sub_indikator }}</h3>
                            </div>
                            @if (!$hasActivePeriod)
                                <div class="flex items-center gap-1.5">
                                    <button title="Edit" @click="openEdit({{ $sub->id_sub_indicators }})"
                                        class="w-8 h-8 rounded-lg bg-oren2 flex items-center justify-center text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button title="Hapus" @click="openDelete({{ $sub->id_sub_indicators }})"
                                        class="w-8 h-8 rounded-lg bg-merah2 flex items-center justify-center text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <span class="text-xs font-semibold text-slate-400 italic">Terkunci</span>
                            @endif
                        </div>
                        <div class="pt-1">
                            <a href="{{ route($rolePrefix . '.manage-kuesioner', ['tab' => \App\Helpers\SecureId::encrypt($activeInd->id_indicators)]) }}" wire:navigate.hover
                               class="text-xs font-semibold bg-biru2/10 text-biru2 px-2.5 py-0.5 rounded-full hover:bg-biru2 hover:text-white transition">
                                {{ $sub->pertanyaan_count }} Pertanyaan
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-biru3 italic">
                        Belum ada data sub-indikator untuk indikator {{ $activeInd->nama_indikator ?? '' }}.
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-abubiru bg-white">
                <x-tables.pagination :paginator="$subIndicators" />
            </div>
        </div>

        <!-- Modal Form Tambah/Edit Sub-Indikator -->
        <x-modals.form add-title="Tambah Sub-Indikator KIP" edit-title="Edit Sub-Indikator KIP"
            add-action="{{ url($rolePrefix . '/sub-indikator') }}" edit-action="{{ url($rolePrefix . '/sub-indikator') }}"
            size="md">
            <div>
                <input type="hidden" name="id" x-model="formData.id">

                {{-- Indikator Induk --}}
                <div class="py-4 border-b border-abubiru/60 relative" x-data="{ openModalIndSelect: false }">
                    <label class="block text-sm font-bold text-biru1 mb-2">INDIKATOR INDUK</label>
                    <input type="hidden" name="id_indicators" x-model="formData.id_indicators" required>

                    <!-- Trigger Button -->
                    <button type="button" @click="openModalIndSelect = !openModalIndSelect" @click.away="openModalIndSelect = false"
                        class="w-full border border-abubiru rounded-xl px-3.5 py-2.5 bg-white text-sm font-semibold text-biru1 flex items-center justify-between transition cursor-pointer hover:border-biru4">
                        <span class="truncate pr-2" x-text="indicatorsMap[formData.id_indicators] || 'Pilih Indikator Induk'"></span>
                        <svg class="w-4 h-4 text-biru3 shrink-0 transition-transform duration-200" :class="openModalIndSelect ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Options List Panel -->
                    <div x-show="openModalIndSelect" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-abubiru rounded-xl shadow-xl py-1.5 z-100 max-h-56 overflow-y-auto divide-y divide-abubiru/40 max-w-full">
                        <template x-for="(name, id) in indicatorsMap" :key="id">
                            <div @click="formData.id_indicators = id; openModalIndSelect = false"
                                 :class="formData.id_indicators == id ? 'bg-biru5/40 text-biru1 font-extrabold' : 'text-biru2 hover:bg-putihbg hover:text-biru1'"
                                 class="px-4 py-2.5 text-xs font-semibold cursor-pointer transition flex items-center justify-between">
                                <span class="truncate pr-2" x-text="name"></span>
                                <svg x-show="formData.id_indicators == id" class="w-4 h-4 text-biru2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Nama Sub-Indikator --}}
                <div class="py-4">
                    <label class="block text-sm font-bold text-biru1 mb-2">NAMA SUB-INDIKATOR</label>
                    <input type="text" name="nama_sub_indikator" x-model="formData.nama" required
                        placeholder="Contoh: Informasi Wajib Berkala"
                        class="w-full border border-abubiru rounded-lg px-3 py-2.5 bg-white text-sm text-biru1 focus:ring-1 focus:ring-biru4/30 focus:border-biru4 outline-none transition" />
                </div>
            </div>
        </x-modals.form>

        <!-- Modal Konfirmasi Hapus -->
        <x-modals.confirm-delete title="Hapus Sub-Indikator"
            message="Apakah Anda yakin ingin menghapus sub-indikator ini? Menghapus sub-indikator akan mempengaruhi pertanyaan terkait."
            action-url="{{ url($rolePrefix . '/sub-indikator') }}" />
    </div>
</x-layouts.app>
