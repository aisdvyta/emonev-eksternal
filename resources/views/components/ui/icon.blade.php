{{--
    Komponen Icon Blade — SVG Helper Reusable
    =========================================
    Cara pakai:
        <x-ui.icon name="close" class="w-5 h-5 text-biru3" />
        <x-ui.icon name="plus" class="w-4 h-4" />

    Supported names:
    'close', 'plus', 'edit', 'delete', 'download', 'upload', 'chevron-down',
    'chevron-left', 'chevron-right', 'check', 'warning', 'info', 'search',
    'user', 'dashboard', 'file', 'logout', 'eye', 'eye-off'
--}}

@props([
    'name'  => 'info',
    'strokeWidth' => '2',
])

@php
    $svgClass = $attributes->get('class', 'w-5 h-5');
@endphp

@switch($name)
    @case('close')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        @break

    @case('plus')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        @break

    @case('edit')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        @break

    @case('delete')
    @case('trash')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        @break

    @case('download')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        @break

    @case('upload')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        @break

    @case('chevron-down')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
        @break

    @case('chevron-left')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        @break

    @case('chevron-right')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
        @break

    @case('check')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        @break

    @case('warning')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        @break

    @case('search')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        @break

    @case('user')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        @break

    @case('logout')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        @break

    @case('home')
    @case('dashboard')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
        @break

    @case('users')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        @break

    @case('calendar')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        @break

    @case('database')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        @break

    @case('clipboard-list')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        @break

    @case('chart-bar')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        @break

    @case('document')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        @break

    @case('book-open')
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v6h6" />
        </svg>
        @break

    @default
        <svg {{ $attributes->merge(['class' => $svgClass]) }} fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
@endswitch
