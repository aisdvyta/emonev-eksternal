{{--
    Komponen Modal Wrapper — Base layer untuk semua modal
    =====================================================

    Cara pakai:
        <x-modals.wrapper show="showMyModal" @close="showMyModal = false">
            <div class="p-8">
                Konten modal di sini
            </div>
        </x-modals.wrapper>

    Props:
    ──────
    • show        → Alpine expression yang mengontrol visibilitas (string)
    • maxWidth    → max-width panel (default: 'max-w-120')
    • fullHeight  → panel memanjang full height screen (boolean, default: false)
    • noPad      → tidak tambahkan padding di panel (boolean, default: false)

    Slots:
    ──────
    • default → konten panel modal
--}}

@props([
    'show'       => 'showModal',
    'maxWidth'   => 'max-w-130',
    'fullHeight' => false,
    'noPad'      => false,
])

{{-- Backdrop + Panel container --}}
<div
    x-show="{{ $show }}"
    x-cloak
    @keydown.escape.window="{{ $show }} = false"
    class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-biru1/60 backdrop-blur-xs"
        @click="{{ $show }} = false"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        {{ $attributes->merge(['class' => "relative z-10 bg-white rounded-3xl shadow-2xl w-full mx-4 border border-abubiru/40 overflow-hidden " . $maxWidth . ($fullHeight ? ' max-h-[90vh] overflow-y-auto' : '') . ($noPad ? '' : ' p-8')]) }}
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        @click.stop
    >
        {{ $slot }}
    </div>
</div>
