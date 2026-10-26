@props(['href', 'label', 'active' => false])

@php
    $isActive = $active || Request::is(ltrim($href, '/'));
    $classes = $isActive ? 'bg-biru5/20 text-biru1 font-semibold' : 'text-biru3 hover:text-biru1 hover:bg-putihbg';
    $dotClasses = $isActive ? 'bg-biru2' : 'bg-abubiru';
@endphp


<a href="{{ $href }}" wire:navigate.hover
    {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-lg px-2.5 py-3 transition ' . $classes]) }}
    :class="sidebarCollapsed ? 'hover:bg-putihbg mx-2' : ''">
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotClasses }}" x-show="!sidebarCollapsed"></span>
    <span class="text-sm font-medium tracking-tight leading-none flex items-center whitespace-nowrap">{!! $label !!}</span>
</a>
