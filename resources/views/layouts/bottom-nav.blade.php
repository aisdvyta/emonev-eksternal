@props([
    'sidebar' => null,
])

@php
    $navConfig = \App\Services\NavigationService::getBottomNavConfig($sidebar);
    $sheets = $navConfig['sheets'] ?? [];
    $tabs = $navConfig['tabs'] ?? [];
@endphp

@if (!empty($tabs))
<nav id="bottom-navbar"
    class="fixed bottom-0 left-0 right-0 z-70 lg:hidden bg-white/95 backdrop-blur-md border-t border-abubiru shadow-[0_-4px_20px_rgba(6,48,81,0.06)]"
    style="padding-bottom: env(safe-area-inset-bottom, 0px);"
    x-data="{
        openSheet: null,
        toggleSheet(name) {
            this.openSheet = this.openSheet === name ? null : name;
        },
        closeSheet() { this.openSheet = null; }
    }">

    {{-- Bottom Sheets --}}
    @foreach ($sheets as $sheetKey => $sheetData)
        <div x-show="openSheet === '{{ $sheetKey }}'" x-cloak
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            @click.away="closeSheet()"
            class="absolute bottom-full left-0 right-0 mb-0 bg-white border-t border-abubiru rounded-t-2xl shadow-2xl px-4 pt-4 pb-4 max-h-[70vh] overflow-y-auto custom-scrollbar"
            style="display:none;">
            <div class="w-10 h-1 bg-abubiru rounded-full mx-auto mb-4"></div>

            @if (isset($sheetData['sections']))
                @foreach ($sheetData['sections'] as $sIndex => $section)
                    <p class="text-xs font-bold text-biru3 uppercase tracking-wider px-1 {{ $sIndex > 0 ? 'mt-4 mb-2' : 'mb-2' }}">
                        {{ $section['title'] }}
                    </p>
                    @foreach ($section['links'] as $link)
                        @php
                            $patterns = is_array($link['pattern']) ? $link['pattern'] : explode(',', $link['pattern']);
                            $isLinkActive = \Illuminate\Support\Facades\Request::is($patterns);
                        @endphp
                        <a href="{{ $link['href'] }}" wire:navigate
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $isLinkActive ? 'bg-birumuda text-biru1 font-semibold' : 'text-biru3 hover:bg-putihbg hover:text-biru1' }}">
                            <span class="w-2 h-2 rounded-full {{ $isLinkActive ? 'bg-biru2' : 'bg-abubiru' }}"></span>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                @endforeach
            @elseif (isset($sheetData['links']))
                <p class="text-xs font-bold text-biru3 uppercase tracking-wider px-1 mb-2">{{ $sheetData['title'] }}</p>
                @foreach ($sheetData['links'] as $link)
                    @php
                        $patterns = is_array($link['pattern']) ? $link['pattern'] : explode(',', $link['pattern']);
                        $isLinkActive = \Illuminate\Support\Facades\Request::is($patterns);
                    @endphp
                    <a href="{{ $link['href'] }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $isLinkActive ? 'bg-birumuda text-biru1 font-semibold' : 'text-biru3 hover:bg-putihbg hover:text-biru1' }}">
                        <span class="w-2 h-2 rounded-full {{ $isLinkActive ? 'bg-biru2' : 'bg-abubiru' }}"></span>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            @endif

            <div class="h-2"></div>
        </div>
    @endforeach

    {{-- Backdrop --}}
    @if (!empty($sheets))
        <div x-show="openSheet !== null" x-cloak
            @click="closeSheet()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-biru1/40 backdrop-blur-xs z-[-1]"
            style="display:none;"></div>
    @endif

    {{-- Tab Items --}}
    <div class="flex items-end justify-around h-16 px-1 pb-1.5">
        @foreach ($tabs as $tab)
            @if ($tab['type'] === 'fab')
                <div class="flex flex-col items-center justify-center flex-1 py-1 relative">
                    <button type="button" @click="toggleSheet('{{ $tab['sheet'] }}')"
                        class="flex items-center justify-center w-12 h-12 -mt-5 rounded-full bg-biru1 text-white shadow-lg ring-4 ring-white transition-all duration-200 active:scale-95 cursor-pointer {{ $tab['active'] ? 'bg-biru2 ring-birumuda' : 'hover:bg-biru2' }}"
                        aria-label="{{ $tab['label'] }}">
                        <x-ui.icon :name="$tab['icon']" class="w-5.5 h-5.5 text-white" />
                    </button>
                    <span class="text-[11px] font-semibold leading-tight mt-1 text-center truncate max-w-full px-0.5 {{ $tab['active'] ? 'text-biru2 font-bold' : 'text-biru3' }}">
                        {{ $tab['label'] }}
                    </span>
                    <span class="w-1 h-1 rounded-full mt-0.5 {{ $tab['active'] ? 'bg-biru2' : 'bg-transparent' }}"></span>
                </div>
            @elseif ($tab['type'] === 'sheet')
                <button type="button" @click="toggleSheet('{{ $tab['sheet'] }}')"
                    class="flex flex-col items-center justify-center flex-1 py-1 transition group cursor-pointer"
                    aria-label="{{ $tab['label'] }}">
                    <div class="w-6 h-6 flex items-center justify-center">
                        <x-ui.icon :name="$tab['icon']" class="w-5 h-5 {{ $tab['active'] ? 'text-biru2' : 'text-biru3/80 group-hover:text-biru1' }}" />
                    </div>
                    <span class="text-[11px] font-semibold leading-tight mt-1 text-center truncate max-w-full px-0.5 {{ $tab['active'] ? 'text-biru2 font-bold' : 'text-biru3/80 group-hover:text-biru1' }}">
                        {{ $tab['label'] }}
                    </span>
                    <span class="w-1 h-1 rounded-full mt-0.5 {{ $tab['active'] ? 'bg-biru2' : 'bg-transparent' }}"></span>
                </button>
            @elseif ($tab['type'] === 'logout')
                <button type="button" onclick="document.getElementById('logout-form').submit();"
                    class="flex flex-col items-center justify-center flex-1 py-1 transition group hover:text-merah1 cursor-pointer"
                    aria-label="{{ $tab['label'] }}">
                    <div class="w-6 h-6 flex items-center justify-center">
                        <x-ui.icon name="logout" class="w-5 h-5 text-biru3/80 group-hover:text-merah1 transition-colors" />
                    </div>
                    <span class="text-[11px] font-semibold leading-tight mt-1 text-center truncate max-w-full px-0.5 text-biru3/80 group-hover:text-merah1 transition-colors">
                        {{ $tab['label'] }}
                    </span>
                    <span class="w-1 h-1 rounded-full mt-0.5 bg-transparent"></span>
                </button>
            @else
                <a href="{{ $tab['href'] }}"
                    @if (($tab['target'] ?? '') !== '_blank') wire:navigate @else target="_blank" rel="noopener noreferrer" @endif
                    class="flex flex-col items-center justify-center flex-1 py-1 transition group"
                    aria-label="{{ $tab['label'] }}">
                    <div class="w-6 h-6 flex items-center justify-center">
                        <x-ui.icon :name="$tab['icon']" class="w-5 h-5 {{ $tab['active'] ? 'text-biru2' : 'text-biru3/80 group-hover:text-biru1' }}" />
                    </div>
                    <span class="text-[11px] font-semibold leading-tight mt-1 text-center truncate max-w-full px-0.5 {{ $tab['active'] ? 'text-biru2 font-bold' : 'text-biru3/80 group-hover:text-biru1' }}">
                        {{ $tab['label'] }}
                    </span>
                    <span class="w-1 h-1 rounded-full mt-0.5 {{ $tab['active'] ? 'bg-biru2' : 'bg-transparent' }}"></span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
@endif
