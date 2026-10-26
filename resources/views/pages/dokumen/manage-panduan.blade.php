<x-layouts.app title="Manajemen Panduan - HaloKIP">
<x-ui.page-header :breadcrumb="[
        'Upload Panduan' => '#',
    ]">
</x-ui.page-header>

<!-- Hero Banner -->
<section
    class="rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8">
    <div
        class="absolute right-20 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block">
    </div>
    <div class="absolute right-60 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block">
    </div>
    <div class="absolute right-45 bottom-1.5 hidden lg:block z-10">
        <img src="{{ asset('images/orangPanduan.svg') }}" alt="Manajemen Panduan" class="h-auto w-63 object-contain">
    </div>
    <div class="relative z-10">
        <h2 class="text-3xl md:text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Manajemen Panduan</h2>
        <p class="text-base leading-relaxed text-white font-normal max-w-xl">Kelola satu tautan/link Google Drive panduan resmi yang berlaku untuk semua periode kegiatan KIP.</p>
    </div>
</section>

<div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3 animate-fade-in">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-xl text-sm font-semibold shadow-xs">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Info Box: Desktop (static, selalu terbuka) -->
    <div class="hidden sm:flex rounded-2xl bg-birumuda border border-biru5 px-6 py-4.5 mb-8 items-center gap-4 shadow-sm">
        <div class="w-9 h-9 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="text-left">
            <div class="font-bold text-biru6 text-sm mb-0.5">Satu Link untuk Semua Periode</div>
            <p class="text-sm text-biru6 font-medium leading-relaxed">Tautan panduan yang Anda simpan di sini akan menjadi tujuan langsung ketika <strong>Operator Kabupaten/Kota</strong> dan <strong>Verifikator</strong> mengeklik menu <strong>"Panduan"</strong> pada sidebar mereka — berlaku untuk semua periode tanpa perlu mengatur per tahun.</p>
        </div>
    </div>

    <!-- Info Box: Mobile (collapsible) -->
    <div x-data="{ open: false }" class="sm:hidden rounded-2xl bg-birumuda border border-biru5 mb-6 shadow-sm overflow-hidden">
        <button type="button" @click="open = !open"
            class="w-full flex items-center gap-3.5 p-4 cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-biru6 flex items-center justify-center text-white shrink-0">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1 text-left">
                <div class="font-bold text-biru6 text-xs">Satu Link untuk Semua Periode</div>
                <div class="text-xs text-biru6/70 font-medium" x-text="open ? 'Tap untuk tutup' : 'Tap untuk baca'"></div>
            </div>
            <svg class="w-4 h-4 text-biru6 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="px-4 pb-4 text-left"
            style="display: none;">
            <p class="text-xs text-biru6 font-medium leading-relaxed">Tautan panduan yang Anda simpan di sini akan menjadi tujuan langsung ketika <strong>Operator Kabupaten/Kota</strong> dan <strong>Verifikator</strong> mengeklik menu <strong>"Panduan"</strong> pada sidebar mereka — berlaku untuk semua periode tanpa perlu mengatur per tahun.</p>
        </div>
    </div>

    <!-- Main Container Card -->
    <div class="rounded-2xl bg-white border border-abubiru shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
        <!-- Header Section styled like monitoring-table -->
        <div class="px-7 py-5 flex items-center bg-biru2 border-b border-abubiru/30">
            <h3 class="text-lg font-bold text-white flex items-center gap-2.5">
                <svg class="w-5.5 h-5.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Tautan Global Panduan Kegiatan
            </h3>
        </div>

        <div class="p-8">
            <p class="text-xs text-biru3 font-semibold mb-6">
                * Link ini akan langsung dipakai oleh semua pengguna (semua periode) tanpa perlu konfigurasi ulang setiap tahun.
            </p>

            <!-- Form -->
            <form action="{{ Route::has('dokumen.manage-panduan.save') ? route('dokumen.manage-panduan.save') : route('super-evaluator.manage-panduan.save') }}" method="POST" class="space-y-6"
                  @submit="if (linkError) { $event.preventDefault(); return false; }"
                  x-data="{
                      isEditing: {{ $linkPanduan ? 'false' : 'true' }},
                      linkValue: '{{ old('link_panduan', $linkPanduan) }}',
                      get linkError() {
                          if (!this.linkValue || !this.linkValue.trim()) return '';
                          return /^https?:\/\//i.test(this.linkValue.trim()) ? '' : 'Isikan Link dengan benar';
                      }
                  }">
                @csrf

                <div>
                    <label class="block text-md font-bold uppercase tracking-wider text-biru2 mb-1">Tautan / Link Google Drive Panduan</label>
                    <p class="text-sm text-slate-400 font-medium mb-2">Masukkan URL lengkap folder Google Drive atau dokumen panduan resmi.</p>
                    <div class="relative flex items-center border rounded-2xl p-1 px-4 transition group"
                         :class="linkError ? 'border-merah1 focus-within:border-merah1 bg-red-50/20' : (isEditing ? 'border-slate-200 focus-within:border-biru2 bg-slate-50/30 focus-within:bg-white' : 'border-abubiru/60 bg-slate-100/50 cursor-not-allowed')">
                        <svg class="w-5 h-5 text-slate-400 group-focus-within:text-biru2 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <input type="url" name="link_panduan" x-model="linkValue" :readonly="!isEditing"
                            placeholder="https://drive.google.com/drive/folders/..."
                            pattern="https?://.+"
                            title="Isikan Link dengan benar"
                            class="w-full bg-transparent px-3 py-3 text-sm text-biru1 font-semibold focus:outline-none placeholder:text-slate-400"
                            :class="!isEditing && 'cursor-not-allowed text-biru3'">

                        <!-- Open Link Button when not editing -->
                        <template x-if="!isEditing && linkValue && !linkError">
                            <a :href="linkValue" target="_blank" rel="noopener noreferrer" class="text-biru2 hover:text-biru1 transition p-2 ml-2 flex items-center gap-1.5 font-bold text-xs shrink-0 bg-birumuda rounded-xl border border-abubiru shadow-2xs" title="Buka Link">
                                <span>Buka Link</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </template>
                    </div>

                    <!-- Frontend Validation Warning -->
                    <div x-show="linkError" class="text-xs font-bold text-merah1 mt-2 flex items-center gap-1.5" x-cloak>
                        <svg class="w-4 h-4 shrink-0 text-merah1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span x-text="linkError"></span>
                    </div>

                    <!-- Backend Validation Error -->
                    @error('link_panduan')
                        <div class="text-xs font-bold text-merah1 mt-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0 text-merah1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Footer/Actions Form -->
                <div class="flex justify-end pt-6 border-t border-abubiru/50">
                    <!-- Cancel Button (Only visible in edit mode and when there was an existing link) -->
                    <template x-if="isEditing && '{{ $linkPanduan }}'">
                        <button type="button" @click="isEditing = false; linkValue = '{{ $linkPanduan }}'"
                            class="px-6 py-3.5 text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl text-sm font-bold transition-all duration-200 shadow-sm flex items-center gap-2 cursor-pointer mr-3">
                            Batal
                        </button>
                    </template>

                    <!-- Edit Button (Only visible when not editing) -->
                    <template x-if="!isEditing">
                        <button type="button" @click="isEditing = true"
                            class="px-6 py-3.5 text-white bg-kuning1 hover:bg-kuning2 rounded-xl text-sm font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 cursor-pointer hover:-translate-y-0.5">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Ubah Link Panduan
                        </button>
                    </template>

                    <!-- Save Button (Only visible in edit mode) -->
                    <template x-if="isEditing">
                        <button type="submit" :disabled="!!linkError"
                            :class="linkError ? 'opacity-50 cursor-not-allowed' : 'hover:-translate-y-0.5 cursor-pointer'"
                            class="px-6 py-3.5 text-white bg-biru1 hover:bg-biru2 rounded-xl text-sm font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Link Panduan
                        </button>
                    </template>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layouts.app>
