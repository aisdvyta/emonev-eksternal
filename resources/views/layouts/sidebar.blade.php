@props([
    'sidebar' => null,
])

@php
    $sidebarItems = \App\Services\NavigationService::getSidebarItems($sidebar);
@endphp

@foreach ($sidebarItems as $index => $item)
    @if ($item['type'] === 'dropdown')
        <div class="relative {{ $index > 0 ? 'mt-2' : '' }}" x-data="{ openPopover: false }"
            @mouseenter="openPopover = true" @mouseleave="openPopover = false">
            <x-ui.sidebar-link :label="$item['label']" :clickable="false" :active="$item['active']">
                <x-ui.icon :name="$item['icon']" class="w-5 h-5" />
            </x-ui.sidebar-link>

            <div :class="sidebarCollapsed ?
                'absolute left-[95%] top-0 w-56 bg-white border border-abubiru rounded-xl shadow-xl py-2 z-50 flex flex-col' :
                'mt-1 pl-5 space-y-0.5'"
                x-show="!sidebarCollapsed || openPopover" x-cloak
                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-x-2"
                x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-2">
                <div class="px-4 py-1.5 text-xs font-bold text-biru1 border-b border-abubiru mb-1"
                    x-show="sidebarCollapsed">
                    {{ $item['label'] }}
                </div>
                @foreach ($item['children'] as $child)
                    <x-ui.sidebar-sublink :href="$child['href']" :label="$child['label']" :active="$child['active']" />
                @endforeach
            </div>
        </div>
    @else
        <div class="{{ $index > 0 ? 'mt-2' : '' }}">
            <x-ui.sidebar-link :href="$item['href']" :label="$item['label']" :active="$item['active']" :target="$item['target'] ?? null"
                :rel="isset($item['target']) && $item['target'] === '_blank' ? 'noopener noreferrer' : null">
                <x-ui.icon :name="$item['icon']" class="w-5 h-5" />
            </x-ui.sidebar-link>
        </div>
    @endif
@endforeach
