@props([
    'title'     => 'Konfirmasi Hapus',
    'message'   => 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    'actionUrl' => '#',
    'method'    => 'DELETE',
])

@php
    $itemName = str_replace('Hapus ', '', $title);
@endphp

@props([
    'title'     => 'Konfirmasi Hapus',
    'message'   => 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    'actionUrl' => '#',
    'method'    => 'DELETE',
])

@php
    $itemName = str_replace('Hapus ', '', $title);
@endphp

<x-modals.wrapper show="showDeleteModal" maxWidth="max-w-120">
    {{-- Body --}}
    <div class="flex flex-col items-center text-center">
        {{-- Warning Triangle Icon --}}
        <svg class="w-14 h-14 text-merah1 mb-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        {{-- Title --}}
        <h3 class="text-2xl font-bold text-merah1 tracking-tight mb-4">{{ $title }}</h3>

        {{-- Message --}}
        <p class="text-sm text-biru2 font-medium leading-relaxed max-w-sm mb-6">
            Anda yakin <span class="text-merah1">menghapus</span> <strong class="font-bold text-biru1">{{ $itemName }}</strong> tersebut?<br>
            Aksi tersebut tidak dapat dibatalkan.
        </p>

        {{-- Target label --}}
        <div class="w-full bg-merah1/5 border border-merah1/15 rounded-2xl px-6 py-5 mb-8 text-center" x-show="deleteLabel">
            <span class="text-sm text-merah1 font-bold block mb-2">{{ $itemName }} yang akan dihapus:</span>
            <div class="text-sm font-medium text-biru1 leading-relaxed" x-text="deleteLabel"></div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex justify-center gap-4 max-w-xs mx-auto">
        <button type="button"
            @click="showDeleteModal = false"
            class="flex-1 py-3 bg-white border border-biru4 text-biru2 rounded-xl text-sm font-bold hover:bg-putihbg transition shadow-sm text-center cursor-pointer">
            Batal
        </button>

        <form :action="`{{ $actionUrl }}/${deleteTarget}`" method="POST" class="flex-1 inline" {{ $attributes }}>
            @csrf
            @method($method)
            <button type="submit"
                class="w-full py-3 bg-merah1 text-white rounded-xl text-sm font-bold hover:opacity-90 transition shadow-sm text-center cursor-pointer">
                Hapus
            </button>
        </form>
    </div>
</x-modals.wrapper>

