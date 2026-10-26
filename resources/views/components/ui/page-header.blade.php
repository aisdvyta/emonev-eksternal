@props(['title' => null, 'description' => null, 'breadcrumb' => []])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 lg:mb-8 gap-3 relative z-30']) }}>
    <div>
        @if(!empty($breadcrumb))
            <div class="mb-3 text-sm text-biru3 flex items-center flex-wrap gap-1">
                @foreach($breadcrumb as $label => $link)
                    @if($loop->last)
                        <span class="font-bold text-biru1">{{ $label }}</span>
                    @elseif($link === '#')
                        <span class="text-biru3 cursor-default">{{ $label }}</span>
                        <span class="mx-1">\</span>
                    @else
                        <a href="{{ $link }}" wire:navigate.hover class="hover:text-biru1 transition">{{ $label }}</a>
                        <span class="mx-1">\</span>
                    @endif
                @endforeach
            </div>
        @endif

        @if(!empty($title))
            <h1 class="text-2xl md:text-3xl font-bold text-biru1 mb-2 tracking-tight">{{ $title }}</h1>
        @endif

        @if($description)
            <p class="text-base lg:text-lg text-biru3 font-semibold">{{ $description }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 sm:pt-8 shrink-0 w-full sm:w-auto">
            {{ $actions }}
        </div>
    @endif
</div>
