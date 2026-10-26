<div>
    <x-tables.monitoring-table title="Daftar Pengguna" hasCards="true">
        <x-slot name="headerCenter">
            {{-- Search Input --}}
            <div class="relative" wire:key="user-search-wrapper">
                <input type="text" wire:model.live.debounce.300ms="search"
                    id="user-table-search-input"
                    wire:key="user-table-search-input"
                    placeholder="{{ $hideNip ? 'Cari nama, email...' : 'Cari nama, email, NIP...' }}"
                    class="w-full rounded-lg border border-white/30 bg-white/20 shadow-sm pl-9 pr-4 py-2 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-white/50 transition">
                <div class="absolute left-3 top-2.5 text-white/70">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </x-slot>

        <x-slot name="thead">
            <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                <tr>
                    @if (!$hideNip)
                        <th class="px-8 py-4 text-left">NIP</th>
                    @endif
                    <th class="px-8 py-4 text-left">NAMA</th>
                    <th class="px-8 py-4 text-left">EMAIL</th>
                    
                    {{-- ROLE header with Excel-style Filter --}}
                    <th class="px-8 py-4 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer" onclick="toggleExcelFilter('role')">
                                <span>ROLE</span>
                                <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-role"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('role', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-role" onchange="toggleExcelAll('role', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-role" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($roles as $r)
                                        <label class="excel-item-role flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower(str_replace('_', ' ', $r->name)) }}">
                                            <input type="checkbox" class="excel-cb-role accent-biru2" value="{{ $r->name }}" checked>
                                            {{ strtoupper(str_replace('_', ' ', $r->name)) }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('role')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('role')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- KODE SATKER header with Excel-style Filter --}}
                    <th class="px-8 py-4 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer" onclick="toggleExcelFilter('kode_satker')">
                                <span>KODE SATKER</span>
                                <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-kode_satker"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-52 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('kode_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-kode_satker" onchange="toggleExcelAll('kode_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-kode_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label class="excel-item-kode_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ $sat->kode_satker }}">
                                            <input type="checkbox" class="excel-cb-kode_satker accent-biru2" value="{{ $sat->kode_satker }}" checked>
                                            {{ $sat->kode_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('kode_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    {{-- NAMA SATKER header with Excel-style Filter --}}
                    <th class="px-8 py-4 text-left">
                        <div class="relative inline-block text-left">
                            <div class="flex items-center gap-1.5 cursor-pointer" onclick="toggleExcelFilter('nama_satker')">
                                <span>NAMA SATKER</span>
                                <svg class="w-3.5 h-3.5 text-biru3 hover:text-biru1 transition" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <div id="excel-filter-nama_satker"
                                class="hidden absolute left-0 top-full mt-1 z-40 bg-white border border-abubiru rounded-xl shadow-lg w-64 p-3 text-biru1 font-medium normal-case text-left">
                                <input type="text" placeholder="Cari..."
                                    oninput="filterExcelList('nama_satker', this.value)"
                                    class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                                <label class="flex items-center gap-2 px-1 py-1 text-xs font-semibold text-biru2 cursor-pointer mb-1">
                                    <input type="checkbox" id="excel-all-nama_satker" onchange="toggleExcelAll('nama_satker', this)" checked class="accent-biru2">
                                    (Select All)
                                </label>
                                <div id="excel-list-nama_satker" class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($satkers as $sat)
                                        <label class="excel-item-nama_satker flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-biru5/20 cursor-pointer text-xs" data-value="{{ strtolower($sat->nama_satker) }}">
                                            <input type="checkbox" class="excel-cb-nama_satker accent-biru2" value="{{ $sat->kode_satker }}" checked>
                                            {{ $sat->nama_satker }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2 border-t border-abubiru">
                                    <button type="button" onclick="closeExcelFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-semibold border border-abubiru rounded-md hover:bg-slate-50 transition">Batal</button>
                                    <button type="button" onclick="applyExcelFilter('nama_satker')"
                                        class="px-3 py-1 text-xs font-bold bg-hijau text-white rounded-md hover:opacity-90 transition">OK</button>
                                </div>
                            </div>
                        </div>
                    </th>

                    <th class="px-8 py-4 text-center">AKSI</th>
                </tr>
            </thead>
        </x-slot>

        @php
            $roleColors = [
                'operator' => 'bg-hijau/10 text-hijau border border-hijau/30',
                'verifikator' => 'bg-oren2/10 text-oren2 border border-oren2/30',
                'evaluator' => 'bg-biru2/10 text-biru2 border border-biru2/30',
                'super_evaluator' => 'bg-biru1/10 text-biru1 border border-biru1/30',
                'admin' => 'bg-merah1/10 text-merah1 border border-merah1/30',
            ];
        @endphp

        <x-slot name="cards">
            @forelse ($users as $user)
                <div wire:key="user-card-{{ $user->id_user }}" class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="font-bold text-biru1 text-sm leading-snug">{{ $user->nama_user }}</div>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold shrink-0 {{ $roleColors[$user->role->nama_role ?? ''] ?? 'bg-abubiru text-biru3 border border-abubiru' }}">
                            {{ strtoupper(str_replace('_', ' ', $user->role->nama_role ?? '-')) }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-2 pt-2 border-t border-abubiru/30 text-xs font-semibold text-biru3">
                        @if (!$hideNip)
                            <div class="flex justify-between items-center gap-2">
                                <span>NIP:</span>
                                <span class="font-mono text-biru1 text-right">{{ $user->nip_panjang ?? '-' }}</span>
                            </div>
                            @if ($user->nip_pendek)
                                <div class="flex justify-between items-center gap-2">
                                    <span>NIP Pendek:</span>
                                    <span class="font-mono text-biru4 text-right">{{ $user->nip_pendek }}</span>
                                </div>
                            @endif
                        @endif
                        <div class="flex justify-between items-center gap-2">
                            <span>Email:</span>
                            <span class="text-biru1 text-right break-all">{{ $user->email }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="shrink-0">Satker:</span>
                            <span class="text-right text-biru1 leading-snug">
                                {{ $user->masterSatker->nama_satker ?? '-' }}
                                <br>
                                <span class="text-xs text-biru4 font-medium">Kode: {{ $user->kode_satker }}</span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-abubiru/30">
                        {{-- Edit --}}
                        <button title="Edit"
                            @click="openEdit({
                                id: '{{ $user->id_user }}',
                                nip_panjang: '{{ $user->nip_panjang }}',
                                nip_pendek: '{{ $user->nip_pendek }}',
                                nama_user: '{{ $user->nama_user }}',
                                email: '{{ $user->email }}',
                                id_role: '{{ $user->id_role }}',
                                kode_satker: '{{ $user->kode_satker }}',
                                foto_url: '{{ $user->foto_url }}'
                            })"
                            class="flex items-center gap-1.5 rounded-lg bg-oren2 border border-oren2/30 px-3 py-1.5 text-xs text-white hover:bg-oren2/20 hover:text-oren2 transition font-bold cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                           m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Edit</span>
                        </button>

                        {{-- Hapus --}}
                        <button title="Hapus"
                            @click="showDeleteModal = true; deleteTarget = '{{ $user->id_user }}'; deleteLabel = '{{ $user->nama_user }}'"
                            class="flex items-center gap-1.5 rounded-lg bg-merah1 border border-merah1/30 px-3 py-1.5 text-xs text-white hover:bg-merah1/20 hover:text-merah1 transition font-bold cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7
                                           m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            @empty
                <div wire:key="user-card-empty" class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                    Data user tidak ditemukan.
                </div>
            @endforelse
        </x-slot>

        @forelse ($users as $user)
            <tr wire:key="user-row-{{ $user->id_user }}" class="border-t border-abubiru/30 hover:bg-abubiru/10 transition">
                {{-- NIP --}}
                @if (!$hideNip)
                    <td class="px-8 py-5">
                        <div class="text-sm font-bold text-biru1">{{ $user->nip_panjang ?? '-' }}</div>
                        @if ($user->nip_pendek)
                            <div class="text-xs font-semibold text-biru4 mt-0.5">{{ $user->nip_pendek }}</div>
                        @endif
                    </td>
                @endif

                {{-- NAMA --}}
                <td class="px-8 py-5">
                    <div class="font-bold text-biru1 text-sm">{{ $user->nama_user }}</div>
                </td>

                {{-- EMAIL --}}
                <td class="px-8 py-5">
                    <div class="text-sm text-biru3">{{ $user->email }}</div>
                </td>

                {{-- ROLE badge --}}
                <td class="px-8 py-5">
                    <span
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $roleColors[$user->role->nama_role ?? ''] ?? 'bg-abubiru text-biru3 border border-abubiru' }}">
                        {{ strtoupper(str_replace('_', ' ', $user->role->nama_role ?? '-')) }}
                    </span>
                </td>

                {{-- KODE SATKER --}}
                <td class="px-8 py-5">
                    <div class="text-sm font-bold text-biru1">{{ $user->kode_satker ?? '-' }}</div>
                </td>

                {{-- NAMA SATKER --}}
                <td class="px-8 py-5">
                    <div class="text-sm font-semibold text-biru1">{{ $user->masterSatker->nama_satker ?? '-' }}</div>
                </td>

                {{-- AKSI --}}
                <td class="px-8 py-5 text-center">
                    <div class="flex items-center justify-center gap-2">
                        {{-- Edit --}}
                        <button title="Edit"
                            @click="openEdit({
                                id: '{{ $user->id_user }}',
                                nip_panjang: '{{ $user->nip_panjang }}',
                                nip_pendek: '{{ $user->nip_pendek }}',
                                nama_user: '{{ $user->nama_user }}',
                                email: '{{ $user->email }}',
                                id_role: '{{ $user->id_role }}',
                                kode_satker: '{{ $user->kode_satker }}',
                                foto_url: '{{ $user->foto_url }}'
                            })"
                            class="w-9 h-9 rounded-lg bg-oren2 border border-oren2/30 flex items-center justify-center text-oren2 hover:bg-oren2/20 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                           m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>

                        {{-- Hapus --}}
                        <button title="Hapus"
                            @click="showDeleteModal = true; deleteTarget = '{{ $user->id_user }}'; deleteLabel = '{{ $user->nama_user }}'"
                            class="w-9 h-9 rounded-lg bg-merah1 border border-merah1/30 flex items-center justify-center text-merah1 hover:bg-merah1/20 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7
                                           m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr wire:key="user-row-empty">
                <td colspan="7" class="px-8 py-5 text-center text-biru3 font-medium bg-white">Data user tidak ditemukan.</td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4 w-full text-sm text-biru3 font-semibold bg-white">
                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 text-center sm:text-left w-full lg:w-auto">
                    <div class="whitespace-nowrap">Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} data</div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-bold text-biru3 uppercase whitespace-nowrap">Tampilkan:</span>
                        <select wire:model.live="perPage"
                            class="rounded-lg border border-abubiru bg-white px-2.5 py-1 text-xs text-biru1 font-bold focus:outline-none cursor-pointer">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-1.5 w-full lg:w-auto flex-wrap">
                    @if ($users->onFirstPage())
                        <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @endif

                    @php
                        $startPage = max(1, $users->currentPage() - 2);
                        $endPage = min($users->lastPage(), $users->currentPage() + 2);
                    @endphp
                    @for ($p = $startPage; $p <= $endPage; $p++)
                        @if ($p == $users->currentPage())
                            <span class="w-9 h-9 rounded-lg bg-biru1 text-white font-bold flex items-center justify-center shadow-md">{{ $p }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $p }})" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru3 font-bold hover:bg-putihbg flex items-center justify-center transition cursor-pointer">{{ $p }}</button>
                        @endif
                    @endfor

                    @if ($users->hasMorePages())
                        <button type="button" wire:click="nextPage" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-tables.monitoring-table>

    <script>
        function applyExcelFilter(column) {
            if (column === 'role') {
                const checked = [...document.querySelectorAll('.excel-cb-role:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-role');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterRoles', filterVal);
            } else if (column === 'kode_satker') {
                const checked = [...document.querySelectorAll('.excel-cb-kode_satker:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-kode_satker');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterKodes', filterVal);
            } else if (column === 'nama_satker') {
                const checked = [...document.querySelectorAll('.excel-cb-nama_satker:checked')].map(cb => cb.value);
                const all = document.querySelectorAll('.excel-cb-nama_satker');
                const filterVal = checked.length === all.length ? [] : checked;
                @this.call('setFilterKabkots', filterVal);
            }
            closeExcelFilter(column);
        }
    </script>
</div>
