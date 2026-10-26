{{--
    Komponen Modal Form Generik — Reusable untuk Tambah & Edit
    ===========================================================

    Cara pakai (Alpine.js x-data wrapper):

    <div x-data="{
        showFormModal: false,
        formMode: 'add',        // 'add' atau 'edit'
        formData: {},           // data yang sedang diedit

        openAdd() {
            this.formMode = 'add';
            this.formData = {};
            this.showFormModal = true;
        },
        openEdit(data) {
            this.formMode = 'edit';
            this.formData = data;
            this.showFormModal = true;
        }
    }">
        <!-- Tombol Tambah -->
        <button @click="openAdd()">Tambah</button>

        <!-- Tombol Edit (di loop) -->
        <button @click="openEdit({ id: {{ $item->id }}, name: '{{ $item->name }}' })">Edit</button>

        <!-- Panggil komponen ini SATU KALI -->
        <x-modals.form
            add-title="Tambah User"
            edit-title="Edit User"
            add-action="/admin/users"
            edit-action="/admin/users"
            size="md"
        >
            <!-- ISI FORM -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-biru3 mb-1.5">Nama</label>
                <input type="text" name="name" x-model="formData.name"
                    class="w-full border border-abubiru rounded-xl px-3 py-2.5 bg-white text-sm font-semibold text-biru1 shadow-sm
                           focus:ring-2 focus:ring-biru4/20 focus:border-biru4 transition"
                    placeholder="Masukkan nama">
            </div>
        </x-modals.form>
    </div>

    Props:
    ──────
    • add-title      → Judul modal saat mode tambah
    • edit-title      → Judul modal saat mode edit
    • add-action      → URL form action untuk tambah (POST)
    • edit-action      → Base URL form action untuk edit (PUT)
    • size            → Ukuran modal: 'sm' | 'md' | 'lg' | 'xl' (default: 'md')

    Slot:
    ──────
    • default         → Isi form (fields)
--}}

@props([
    'addTitle'    => 'Tambah Data',
    'editTitle'   => 'Edit Data',
    'addAction'   => '#',
    'editAction'  => '#',
    'size'        => 'md',
])

@php
    $sizeClass = match ($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

<x-modals.wrapper show="showFormModal" maxWidth="{{ $sizeClass }}" noPad>
    {{-- Header --}}
    <div class="px-6 py-4 bg-biru1 flex items-center justify-between">
        <h3 class="text-base font-bold text-white flex items-center gap-2.5">
            {{-- Icon Tambah --}}
            <template x-if="formMode === 'add'">
                <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </template>
            {{-- Icon Edit --}}
            <template x-if="formMode === 'edit'">
                <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </template>

            <span x-text="formMode === 'add' ? '{{ $addTitle }}' : '{{ $editTitle }}'"></span>
        </h3>
        <button @click="showFormModal = false" class="text-white/60 hover:text-white transition rounded-lg p-1 hover:bg-white/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Body —— form --}}
    <form
        :action="formMode === 'add' ? '{{ $addAction }}' : `{{ $editAction }}/${formData.id || ''}`"
        :method="formMode === 'add' ? 'POST' : 'POST'"
        {{ $attributes }}
    >
        @csrf
        <template x-if="formMode === 'edit'">
            <input type="hidden" name="_method" value="PUT">
        </template>

        <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-0 max-h-[75dvh] overflow-y-auto custom-scrollbar">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-4 sm:px-6 py-4 border-t border-abubiru/50 bg-white">
            <button type="button"
                @click="showFormModal = false"
                class="px-6 py-2.5 bg-white border border-abubiru hover:bg-putihbg text-biru1 rounded-lg text-sm font-semibold transition">
                Batal
            </button>
            <button type="submit"
                class="px-6 py-2.5 text-white rounded-lg text-sm font-bold transition shadow-sm"
                :class="(formMode === 'add' ? 'bg-biru1 hover:bg-biru2' : 'bg-oren2 hover:opacity-90') + (((typeof overlapMessage !== 'undefined' && overlapMessage) || (typeof hasLinkError !== 'undefined' && hasLinkError)) ? ' opacity-50 cursor-not-allowed' : '')"
                :disabled="(typeof overlapMessage !== 'undefined' && !!overlapMessage) || (typeof hasLinkError !== 'undefined' && !!hasLinkError)"
            >
                <span x-text="formMode === 'add' ? 'Tambah' : 'Perbarui'"></span>
            </button>
        </div>
    </form>
</x-modals.wrapper>

