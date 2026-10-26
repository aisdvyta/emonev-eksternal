@props(['value' => 0, 'color' => 'success'])

@php
    $colorClasses = [
        'success' => 'bg-hijau',
        'info' => 'bg-biru3',
        'warning' => 'bg-oren',
        'primary' => 'bg-biru1',
        'slate' => 'bg-abu',
    ];
    $colorClass = $colorClasses[$color] ?? $colorClasses['success'];
@endphp

<div class="flex items-center gap-3">
    <div class="flex-1 h-2 rounded-full bg-abubiru overflow-hidden max-w-30">
        <div class="h-full {{ $colorClass }} transition-all duration-500" style="width: {{ $value }}%"></div>
    </div>
    <span class="text-sm font-bold text-biru1">{{ $value }}%</span>
</div>
