<x-layouts.app title="Manajemen User - HaloKIP">
    @php
        $rolesJson = $roles->map(function($r) {
            return [
                'id' => $r->id,
                'slug' => strtolower($r->name),
                'name' => strtoupper(str_replace('_', ' ', $r->name))
            ];
        });
        $satkersJson = $satkers->map(function($s) {
            return ['kode' => $s->kode_satker, 'name' => $s->nama_satker . ' (' . $s->kode_satker . ')'];
        });
        $userRole = Auth::user()?->role?->nama_role;
        $isSuperEvaluator = $userRole === 'super_evaluator';
        $homeRoute = $isSuperEvaluator ? route('super-evaluator.index') : route('admin.index');
        $userBaseUrl = url('/users');
    @endphp

    <div x-data="{
        showFormModal: false,
        formMode: 'add',
        formData: {},
        rolesList: {{ $rolesJson }},
        showDeleteModal: false,
        deleteTarget: null,
        deleteLabel: '',
        showImportModal: false,
        get isExternalRole() {
            const r = this.rolesList.find(x => x.id == this.formData.id_role);
            return r ? (r.slug.includes('eksternal') || r.name.toLowerCase().includes('eksternal')) : false;
        },
        openAdd() {
            this.formMode = 'add';
            this.formData = {};
            this.showFormModal = true;
        },
        openEdit(d) {
            this.formMode = 'edit';
            this.formData = { ...d };
            this.showFormModal = true;
        }
    }">
        <x-ui.page-header title="Manajemen User" description="Kelola akun pengguna sistem HaloKIP." :breadcrumb="['Beranda' => $homeRoute, 'Manajemen User' => '#']">
            <x-slot name="actions">
                <button @click="showImportModal = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-hijau px-5 py-3 text-sm font-semibold text-white shadow-md hover:opacity-90 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Import Excel
                </button>
                <button @click="openAdd()"
                    class="inline-flex items-center gap-2 rounded-lg bg-biru2 px-5 py-3 text-sm font-semibold text-white shadow-md hover:opacity-90 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah User
                </button>
            </x-slot>
        </x-ui.page-header>

        <livewire:master.user-table :hide-nip="$isSuperEvaluator" />

        {{-- Modal Form Tambah/Edit User --}}
        <x-modals.form add-title="Tambah User" edit-title="Edit User" add-action="{{ $userBaseUrl }}"
            edit-action="{{ $userBaseUrl }}" size="lg" autocomplete="off">
            <!-- Decoy inputs to prevent browser credentials autofill -->
            <input style="position: absolute; opacity: 0; pointer-events: none; height: 0; width: 0; margin: 0; padding: 0; border: 0;" type="text" name="fake_usernameremembered" tabindex="-1" autocomplete="username">
            <input style="position: absolute; opacity: 0; pointer-events: none; height: 0; width: 0; margin: 0; padding: 0; border: 0;" type="password" name="fake_passwordremembered" tabindex="-1" autocomplete="current-password">

            <div class="grid grid-cols-1 gap-4" x-data="{ activeDropdown: null }">
                <div>
                    <label class="block text-sm font-bold text-biru1 mb-2">NIP 
                        <span class="font-bold text-sm text-hijau">PANJANG</span> (18 DIGIT)
                        <span class="text-merah2 font-bold">*</span>
                    </label>
                    <input type="text" name="nip_panjang" x-model="formData.nip_panjang"
                        placeholder="Masukkan NIP (18 digit)" autocomplete="off"
                        class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-biru1 mb-2">NIP 
                        <span class="font-bold text-sm text-oren2">PENDEK</span> (9 DIGIT)</label>
                    <input type="text" name="nip_pendek" x-model="formData.nip_pendek"
                        placeholder="Masukkan NIP Pendek (9 digit)" autocomplete="off"
                        class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-biru1 mb-2">USERNAME 
                        <span class="text-[11px] font-normal text-abu">(Opsional untuk SSO BPS)</span></label>
                    <input type="text" name="nama_user" x-model="formData.nama_user"
                        placeholder="Masukkan username (opsional untuk SSO BPS)" autocomplete="off"
                        class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-biru1 mb-2">EMAIL <span class="text-merah2 font-bold">*</span>
                        <span class="text-[11px] font-normal text-abu">(Email @bps.go.id untuk SSO)</span>
                    </label>
                    <input type="email" name="email" x-model="formData.email" placeholder="nama@bps.go.id" required autocomplete="off"
                        class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                </div>
                <div>
                    <div x-data="{
                        search: '',
                        selectedVal: formData.id_role || '',
                        options: {{ $rolesJson }},
                        get selectedName() {
                            const found = this.options.find(o => o.id == this.selectedVal);
                            return found ? found.name : 'Pilih Role';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                        }
                    }" x-effect="selectedVal = formData.id_role || ''" class="relative">
                        <label class="block text-sm font-bold text-biru1 mb-2">ROLE <span class="text-merah2 font-bold">*</span></label>
                        <input type="hidden" name="id_role" :value="selectedVal" required>
                        <button type="button" @click="activeDropdown = (activeDropdown === 'role' ? null : 'role')"
                            :class="selectedVal ? 'text-biru1' : 'text-slate-400'"
                            class="w-full flex items-center justify-between border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                            <span x-text="selectedName"></span>
                            <svg class="w-4 h-4 text-biru3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeDropdown === 'role'" @click.away="if (activeDropdown === 'role') activeDropdown = null" x-cloak
                            class="absolute left-0 right-0 mt-1 z-50 bg-white border border-abubiru rounded-xl shadow-lg p-3 text-biru1 font-medium text-left">
                            <input type="text" placeholder="Cari..." x-model="search"
                                class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                            <div class="max-h-40 overflow-y-auto space-y-1 custom-scrollbar">
                                <template x-for="opt in filteredOptions" :key="opt.id">
                                    <button type="button" @click="selectedVal = opt.id; formData.id_role = opt.id; activeDropdown = null; search = '';"
                                        class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition flex items-center justify-between"
                                        :class="selectedVal == opt.id ? 'bg-biru5/40 font-bold text-biru1' : ''">
                                        <span x-text="opt.name"></span>
                                        <svg x-show="selectedVal == opt.id" class="w-3.5 h-3.5 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="filteredOptions.length === 0" class="text-xs text-center text-biru3 py-2 italic">
                                    Tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div x-data="{
                        search: '',
                        selectedVal: formData.kode_satker || '',
                        options: {{ $satkersJson }},
                        get selectedName() {
                            const found = this.options.find(o => o.kode == this.selectedVal);
                            return found ? found.name : 'Pilih Satuan Kerja (Kosongkan jika bukan Operator/Verifikator)';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                        }
                    }" x-effect="selectedVal = formData.kode_satker || ''" class="relative">
                        <label class="block text-sm font-bold text-biru1 mb-2">SATUAN KERJA</label>
                        <input type="hidden" name="kode_satker" :value="selectedVal">
                        <button type="button" @click="activeDropdown = (activeDropdown === 'satker' ? null : 'satker')"
                            :class="selectedVal ? 'text-biru1' : 'text-slate-400'"
                            class="w-full flex items-center justify-between border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition text-left">
                            <span x-text="selectedName" class="truncate"></span>
                            <svg class="w-4 h-4 text-biru3 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeDropdown === 'satker'" @click.away="if (activeDropdown === 'satker') activeDropdown = null" x-cloak
                            class="absolute left-0 right-0 mt-1 z-50 bg-white border border-abubiru rounded-xl shadow-lg p-3 text-biru1 font-medium text-left">
                            <input type="text" placeholder="Cari..." x-model="search"
                                class="w-full rounded-lg border border-abubiru px-3 py-1.5 text-xs mb-2 focus:outline-none focus:border-biru2">
                            <div class="max-h-40 overflow-y-auto space-y-1 custom-scrollbar">
                                <button type="button" @click="selectedVal = ''; formData.kode_satker = ''; activeDropdown = null; search = '';"
                                    class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition flex items-center justify-between text-biru3 italic"
                                    :class="selectedVal === '' ? 'bg-biru5/40 font-bold' : ''">
                                    <span>Pilih Satuan Kerja (Kosongkan jika bukan Operator/Verifikator)</span>
                                    <svg x-show="selectedVal === ''" class="w-3.5 h-3.5 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <template x-for="opt in filteredOptions" :key="opt.kode">
                                    <button type="button" @click="selectedVal = opt.kode; formData.kode_satker = opt.kode; activeDropdown = null; search = '';"
                                        class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-biru5/20 text-xs transition flex items-center justify-between"
                                        :class="selectedVal == opt.kode ? 'bg-biru5/40 font-bold text-biru1' : ''">
                                        <span x-text="opt.name"></span>
                                        <svg x-show="selectedVal == opt.kode" class="w-3.5 h-3.5 text-biru2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="filteredOptions.length === 0" class="text-xs text-center text-biru3 py-2 italic">
                                    Tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-data="{ showPassword: false }">
                    <label class="block text-sm font-bold text-biru1 mb-2">
                        PASSWORD 
                        <span x-show="isExternalRole" class="text-merah2 font-bold">*</span>
                        <span x-show="!isExternalRole" class="text-xs font-normal text-abu">(Opsional untuk SSO BPS)</span>
                        <span x-show="isExternalRole" class="text-xs font-semibold text-merah2">(Wajib untuk Penilai Eksternal)</span>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password"
                            :placeholder="isExternalRole ? 'Masukkan password (wajib untuk Penilai Eksternal)' : 'Masukkan password (opsional untuk SSO BPS)'"
                            :required="isExternalRole && formMode === 'add'"
                            autocomplete="new-password"
                            class="w-full border border-abubiru rounded-xl pl-3 pr-10 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-biru3 hover:text-biru1 transition">
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <p x-show="isExternalRole" class="text-[11px] text-merah2 font-medium mt-1">Wajib diisi untuk akun Penilai Eksternal karena login menggunakan email & password (bukan SSO BPS).</p>
                    <p x-show="!isExternalRole" class="text-[11px] text-abu mt-1">Boleh dikosongkan jika user login menggunakan SSO BPS.</p>
                </div>
                <div x-show="formData.id_role == 3 || formData.id_role == 5" x-cloak>
                    <label class="block text-sm font-bold text-biru1 mb-2">FOTO URL (Opsional)</label>
                    <input type="text" name="foto_url" x-model="formData.foto_url"
                        placeholder="https://domain.com/path/foto.jpg" autocomplete="off"
                        class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                </div>
            </div>
        </x-modals.form>

        {{-- Modal Konfirmasi Hapus User --}}
        <x-modals.confirm-delete title="Hapus Pengguna"
            message="Apakah Anda yakin ingin menghapus akun pengguna ini? Tindakan ini tidak dapat dibatalkan."
            action-url="{{ $userBaseUrl }}" />

        {{-- Modal Import User Excel --}}
        <x-modals.import-user
            action="{{ route('users.import') }}"
            :satkers="$satkers"
            :roles="$roles"
        />
    </div>
</x-layouts.app>
