@props(['paginator', 'livewire' => false])

<div class="flex flex-col lg:flex-row items-center justify-between gap-4 w-full text-sm text-biru3 font-semibold bg-white">
    <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 text-center sm:text-left w-full lg:w-auto">
        <div class="whitespace-nowrap">Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data</div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-bold text-biru3 uppercase whitespace-nowrap">Tampilkan:</span>
            @if($livewire)
                <select wire:model.live="perPage" class="rounded-lg border border-abubiru bg-white px-2.5 py-1 text-xs text-biru1 font-bold focus:outline-none cursor-pointer">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            @else
                <select onchange="
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', this.value);
                    url.searchParams.set('page', '1');
                    if (window.Livewire) {
                        Livewire.navigate(url.toString());
                    } else {
                        window.location.href = url.toString();
                    }
                " class="rounded-lg border border-abubiru bg-white px-2.5 py-1 text-xs text-biru1 font-bold focus:outline-none cursor-pointer">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                </select>
            @endif
        </div>
    </div>
    <div class="flex items-center justify-center gap-1.5 w-full lg:w-auto flex-wrap">
        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </span>
        @elseif($livewire)
            <button wire:click="previousPage" wire:loading.attr="disabled" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        @else
            <a href="{{ $paginator->appends(request()->query())->previousPageUrl() }}" wire:navigate.hover class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        @endif

        {{-- Page numbers --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);
        @endphp

        @if($livewire)
            @for ($page = $start; $page <= $end; $page++)
                @if ($page === $currentPage)
                    <span class="w-9 h-9 rounded-lg bg-biru1 text-white font-bold flex items-center justify-center shadow-md">{{ $page }}</span>
                @else
                    <button wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru3 font-bold hover:bg-putihbg flex items-center justify-center transition cursor-pointer">
                        {{ $page }}
                    </button>
                @endif
            @endfor
        @else
            @foreach ($paginator->appends(request()->query())->getUrlRange($start, $end) as $page => $url)
                @if ($page === $currentPage)
                    <span class="w-9 h-9 rounded-lg bg-biru1 text-white font-bold flex items-center justify-center shadow-md">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" wire:navigate.hover class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru3 font-bold hover:bg-putihbg flex items-center justify-center transition">{{ $page }}</a>
                @endif
            @endforeach
        @endif

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            @if($livewire)
                <button wire:click="nextPage" wire:loading.attr="disabled" class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition cursor-pointer">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <a href="{{ $paginator->appends(request()->query())->nextPageUrl() }}" wire:navigate.hover class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center hover:bg-putihbg transition">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @endif
        @else
            <span class="w-9 h-9 rounded-lg border border-abubiru bg-white text-biru4 flex items-center justify-center cursor-not-allowed opacity-50">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        @endif
    </div>
</div>
