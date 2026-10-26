@props(['href' => null, 'label', 'active' => false, 'disabled' => false, 'clickable' => true])

@php
    $isActive = !$disabled && ($active || ($href && Request::is(trim($href, '/'))));

    if ($disabled) {
        $classes = 'text-biru4/50 bg-transparent cursor-not-allowed opacity-50';
        $iconClasses = 'text-biru4/40';
    } elseif (!$clickable) {
        $classes = $isActive ? 'text-biru1 font-semibold select-none' : 'text-biru3 select-none';
        $iconClasses = $isActive ? 'text-biru1' : 'text-biru4';
    } else {
        $classes = $isActive ? 'bg-biru5/20 text-biru1 font-semibold' : 'text-biru3 hover:bg-putihbg hover:text-biru1';
        $iconClasses = $isActive ? 'text-biru1' : 'text-biru4';
    }
@endphp

@if ($disabled)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg py-2.5 transition duration-300 ' . $classes]) }}
        :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'">
        <div class="{{ $iconClasses }} w-5 h-5 flex items-center justify-center shrink-0">
            {{ $slot }}
        </div>
        <span class="text-sm font-semibold whitespace-nowrap leading-none flex items-center" x-show="!sidebarCollapsed"
            x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">{{ $label }}</span>
    </div>
@elseif (!$clickable)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg py-2.5 transition duration-300 ' . $classes]) }}
        :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'">
        <div class="{{ $iconClasses }} w-5 h-5 flex items-center justify-center shrink-0">
            {{ $slot }}
        </div>
        <span class="text-sm font-semibold whitespace-nowrap leading-none flex items-center" x-show="!sidebarCollapsed"
            x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">{{ $label }}</span>
    </div>
@else
    <a href="{{ $href }}" @if ($attributes->get('target') !== '_blank') wire:navigate.hover @endif
        {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg py-2.5 transition duration-300 ' . $classes]) }}
        :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'">
        <div class="{{ $iconClasses }} w-5 h-5 flex items-center justify-center shrink-0">
            {{ $slot }}
        </div>
        <span class="text-sm font-semibold whitespace-nowrap leading-none flex items-center" x-show="!sidebarCollapsed"
            x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">{{ $label }}</span>
    </a>
@endif
