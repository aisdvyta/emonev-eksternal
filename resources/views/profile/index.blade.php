<x-layouts.app title="Profil Pengguna - HaloKIP">
    @php
        $isEksternal = $user && $user->role && $user->role->nama_role === 'penilai_eksternal';
        $roleName = $user && $user->role ? ucwords(str_replace('_', ' ', $user->role->nama_role)) : 'Pengguna';
        if ($user && $user->role && $user->role->nama_role === 'operator' && $user->kode_satker) {
            $roleName .= ' ' . $user->kode_satker;
        }

        $satkerName = 'BPS Provinsi Jawa Timur';
        if ($user && $user->masterSatker) {
            $satkerName = $user->masterSatker->nama_satker . ' (' . $user->kode_satker . ')';
        }
    @endphp

    <div class="w-full">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mb-4 p-3.5 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-4 p-3.5 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Unified Profile & Security Form Card --}}
        <div class="bg-white rounded-xl border border-abubiru shadow-xs overflow-hidden w-full">
            {{-- Header Card --}}
            <div class="px-6 py-4.5 md:px-7 md:py-5 flex items-center justify-between gap-4 border-b border-abubiru/50">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-birumuda flex items-center justify-center text-biru2 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-bold text-biru1 leading-tight">Pengaturan Profil & Keamanan</h1>
                        <p class="text-xs md:text-sm font-semibold text-biru3 mt-0.5">
                            Kelola data diri, NIP, serta kata sandi akun Anda dalam satu tempat.
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" autocomplete="off" class="px-6 py-5 md:px-7 md:py-6 space-y-5 md:space-y-5.5"
                x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                @csrf
                @method('PUT')

                {{-- Section 1: Data Profil Pengguna --}}
                <div class="space-y-3.5">
                    <div class="flex items-center gap-2 pb-0.5">
                        <span class="w-2 h-2 rounded-full bg-biru1 shrink-0"></span>
                        <h2 class="text-xs md:text-sm font-bold uppercase tracking-wider text-biru1">Data Profil Pengguna</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                        {{-- Nama Lengkap --}}
                        <div>
                            <label for="nama_user" class="block text-sm font-bold text-biru1 mb-1.5">
                                Nama Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="nama_user" name="nama_user"
                                value="{{ old('nama_user', $user->nama_user) }}" required
                                placeholder="Masukkan nama lengkap"
                                @class([
                                    'w-full border rounded-xl px-4 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                    'border-rose-400 ring-1 ring-rose-400' => $errors->has('nama_user'),
                                    'border-abubiru' => !$errors->has('nama_user'),
                                ])>
                            @error('nama_user')
                                <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-bold text-biru1 mb-1.5">
                                Alamat Email <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" id="email" name="email"
                                value="{{ old('email', $user->email) }}" required
                                placeholder="nama@bps.go.id"
                                @class([
                                    'w-full border rounded-xl px-4 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                    'border-rose-400 ring-1 ring-rose-400' => $errors->has('email'),
                                    'border-abubiru' => !$errors->has('email'),
                                ])>
                            @error('email')
                                <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if (!$isEksternal)
                        {{-- NIP Fields Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-5 pt-0.5">
                            {{-- NIP Panjang --}}
                            <div>
                                <label for="nip_panjang" class="block text-sm font-bold text-biru1 mb-1.5">
                                    NIP <span class="font-bold text-sm text-hijau">PANJANG</span> (18 DIGIT)
                                </label>
                                <input type="text" id="nip_panjang" name="nip_panjang" maxlength="18"
                                    value="{{ old('nip_panjang', $user->nip_panjang) }}"
                                    placeholder="Masukkan NIP (18 digit)"
                                    @class([
                                        'w-full border rounded-xl px-4 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                        'border-rose-400 ring-1 ring-rose-400' => $errors->has('nip_panjang'),
                                        'border-abubiru' => !$errors->has('nip_panjang'),
                                    ])>
                                @error('nip_panjang')
                                    <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- NIP Pendek --}}
                            <div>
                                <label for="nip_pendek" class="block text-sm font-bold text-biru1 mb-1.5">
                                    NIP <span class="font-bold text-sm text-biru4">PENDEK</span> (9 DIGIT)
                                </label>
                                <input type="text" id="nip_pendek" name="nip_pendek" maxlength="9"
                                    value="{{ old('nip_pendek', $user->nip_pendek) }}"
                                    placeholder="Masukkan NIP (9 digit)"
                                    @class([
                                        'w-full border rounded-xl px-4 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                        'border-rose-400 ring-1 ring-rose-400' => $errors->has('nip_pendek'),
                                        'border-abubiru' => !$errors->has('nip_pendek'),
                                    ])>
                                @error('nip_pendek')
                                    <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Ringkasan Penugasan & Satker (Read-only Badge Info) --}}
                        <div class="bg-putihbg rounded-xl border border-abubiru p-3.5 sm:p-4 space-y-2 mt-1">
                            <div class="text-xs font-bold uppercase tracking-wider text-biru3">Informasi Hak Akses & Penugasan</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-0.5">
                                <div class="bg-white rounded-lg p-3 border border-abubiru/60">
                                    <span class="text-xs text-biru3 font-semibold block">Peran (Role)</span>
                                    <span class="text-sm font-bold text-biru1">{{ $roleName }}</span>
                                </div>
                                <div class="bg-white rounded-lg p-3 border border-abubiru/60">
                                    <span class="text-xs text-biru3 font-semibold block">Satuan Kerja</span>
                                    <span class="text-sm font-bold text-biru1 truncate block" title="{{ $satkerName }}">{{ $satkerName }}</span>
                                </div>
                            </div>
                            <p class="text-[11px] text-biru3 font-medium italic pt-0.5">
                                * Perubahan peran dan satuan kerja dikelola oleh Administrator sistem.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Section 2: Keamanan Akun --}}
                <div class="space-y-3.5 pt-3.5 border-t border-abubiru/50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-0.5">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                            <h2 class="text-xs md:text-sm font-bold uppercase tracking-wider text-biru1">Keamanan Akun</h2>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-500 text-xs font-medium border border-slate-200/60 w-fit">
                            Opsional — Kosongkan jika tidak ingin mengubah kata sandi
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
                        {{-- Password Saat Ini --}}
                        @if (!empty($user->password))
                            <div>
                                <label for="current_password" class="block text-sm font-bold text-biru1 mb-1.5">
                                    Password Saat Ini
                                </label>
                                <div class="relative">
                                    <input :type="showCurrent ? 'text' : 'password'" id="current_password" name="current_password"
                                        placeholder="••••••••••"
                                        autocomplete="current-password"
                                        @class([
                                            'w-full border rounded-xl px-4 py-2.5 pr-11 bg-birumuda/25 text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                            'border-rose-400 ring-1 ring-rose-400' => $errors->has('current_password'),
                                            'border-abubiru' => !$errors->has('current_password'),
                                        ])>
                                    <button type="button" @click="showCurrent = !showCurrent"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-biru4 hover:text-biru1 transition cursor-pointer p-1">
                                        <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-bold text-biru1 mb-1.5">
                                    Password Saat Ini
                                </label>
                                <input type="text" disabled value="Belum diatur (Login SSO)"
                                    class="w-full border border-abubiru/60 rounded-xl px-4 py-2.5 bg-slate-100 text-sm font-semibold text-biru3 shadow-xs cursor-not-allowed">
                            </div>
                        @endif

                        {{-- Password Baru --}}
                        <div>
                            <label for="password" class="block text-sm font-bold text-biru1 mb-1.5">
                                Password Baru
                            </label>
                            <div class="relative">
                                <input :type="showNew ? 'text' : 'password'" id="password" name="password"
                                    placeholder="Minimal 6 karakter"
                                    autocomplete="new-password"
                                    @class([
                                        'w-full border rounded-xl px-4 py-2.5 pr-11 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition',
                                        'border-rose-400 ring-1 ring-rose-400' => $errors->has('password'),
                                        'border-abubiru' => !$errors->has('password'),
                                    ])>
                                <button type="button" @click="showNew = !showNew"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-biru4 hover:text-biru1 transition cursor-pointer p-1">
                                    <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi Password Baru --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-biru1 mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                                    placeholder="Ulangi password baru"
                                    autocomplete="new-password"
                                    class="w-full border border-abubiru rounded-xl px-4 py-2.5 pr-11 bg-white text-sm font-semibold text-biru1 shadow-xs focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition">
                                <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-biru4 hover:text-biru1 transition cursor-pointer p-1">
                                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tips Password Box --}}
                    <div class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-xl text-xs text-amber-900 space-y-0.5">
                        <div class="font-bold flex items-center gap-1.5 text-amber-800">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Ketentuan Kata Sandi
                        </div>
                        <p class="text-[11.5px] leading-relaxed text-amber-900/90 font-medium">
                            Minimal 6 karakter. Dianjurkan menggabungkan huruf kapital, angka, dan karakter khusus.
                        </p>
                    </div>
                </div>

                {{-- Tombol Simpan Terpadu --}}
                <div class="pt-2 flex items-center justify-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-biru1 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-biru2 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
