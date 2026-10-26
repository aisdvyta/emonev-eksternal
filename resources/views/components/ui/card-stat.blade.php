@props(['label', 'value', 'color' => '#123d74', 'bg' => 'bg-white'])

@php
    $textColors = [
        'primary' => 'text-biru2',
        'success' => 'text-hijau',
        'info' => 'text-biru4',
        'warning' => 'text-oren2',
    ];

    $textColor = $textColors[$color] ?? (str_starts_with($color, '#') ? '' : "text-$color");
    $style = str_starts_with($color, '#') ? "color: $color" : '';
    
    $bgClass = $bg;
    if ($bg === 'bg-white') {
        $bgClass = ($color === 'primary') ? 'border border-abubiru bg-biru4/10 sm:bg-white' : 'border border-abubiru bg-white';
    }
@endphp


<div
    {{ $attributes->merge(['class' => 'rounded-xl sm:rounded-2xl shadow-sm p-3.5 sm:p-5 md:p-7 relative overflow-hidden flex flex-col justify-center min-h-20 sm:min-h-28 md:min-h-35 ' . $bgClass]) }}>
    {{-- Gradasi kuning pojok kanan atas + biru muda pojok kanan bawah, bertumpuk --}}
    <div class="absolute inset-0 pointer-events-none hidden sm:block">
        @if($bg === 'bg-white')
            <div class="absolute -right-7.5 -bottom-7.5 h-36 w-36 rounded-full bg-biru5 opacity-90 blur-2xl"></div>
            <div class="absolute right-7.5 -top-12.5 h-36 w-36 rounded-full bg-kuning2 opacity-100 blur-2xl"></div>
        @elseif($bg === 'bg-biru4' || $bg === 'bg-biru5')
            <div class="absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-biru5 opacity-100 blur-3xl"></div>
            <div class="absolute -right-10 -top-20 h-50 w-50 rounded-full bg-kuning2 opacity-100 blur-3xl"></div>
        @else
            <div class="absolute -right-7.5 -bottom-7.5 h-36 w-36 rounded-full bg-white/5 opacity-40 blur-2xl"></div>
            <div class="absolute right-7.5 -top-12.5 h-36 w-36 rounded-full bg-white/5 opacity-20 blur-2xl"></div>
        @endif
    </div>
    <div class="relative z-10 flex items-start gap-1.5 sm:gap-2 mb-1.5 sm:mb-2 {{ $textColor }} font-extrabold text-xs sm:text-xs md:text-sm uppercase tracking-tight sm:tracking-wide leading-tight"
        style="{{ $style }}">
        @if(isset($icon))
            <div class="hidden sm:block shrink-0 mt-0.5">
                {{ $icon }}
            </div>
        @endif
        <span class="wrap-break-word leading-tight flex-1">{{ $label }}</span>
    </div>
    <div class="relative z-10 text-3xl sm:text-4xl md:text-5xl ml-0 sm:ml-4 md:ml-6 leading-none font-bold {{ $textColor }}" style="{{ $style }}">
        {{ $value }}</div>
</div>
