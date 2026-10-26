@props(['headers' => [], 'title' => null, 'minHeight' => 'min-h-100', 'hasCards' => false])

<section {{ $attributes->merge(['class' => 'rounded-2xl bg-white border border-abubiru shadow-sm mb-6 relative z-10']) }}>
    <div class="px-4 sm:px-7 py-4 sm:py-6 flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 bg-biru2 border-b border-abubiru/30 rounded-t-2xl relative z-30">
        @if($title)
            <div class="shrink-0">
                <h2 class="text-xl sm:text-2xl font-bold text-white">{{ $title }}</h2>
            </div>
        @endif

        @if(isset($headerCenter))
            <div class="flex-1 w-full md:max-w-md md:mx-auto">
                {{ $headerCenter }}
            </div>
        @else
            <div class="hidden md:block"></div>
        @endif

        @if(isset($headerActions))
            <div class="flex items-center gap-3 w-full md:w-auto flex-wrap">
                {{ $headerActions }}
            </div>
        @endif
    </div>

    {{-- Mobile scroll hint --}}
    @if(!$hasCards)
        <div class="flex items-center gap-1.5 px-4 py-2 bg-birumuda/50 text-biru3 text-xs font-medium md:hidden border-b border-abubiru/30">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Geser kiri/kanan untuk melihat semua kolom
        </div>
    @endif

    {{-- Mobile Filters view --}}
    @if($hasCards && isset($mobileFilters))
        <div class="block md:hidden border-b border-abubiru bg-white px-4 py-3">
            {{ $mobileFilters }}
        </div>
    @endif

    {{-- Mobile Cards view --}}
    @if($hasCards && isset($cards))
        <div class="block md:hidden p-4 space-y-4 bg-putihbg/30 border-b border-abubiru/30 rounded-b-2xl">
            {{ $cards }}
        </div>
    @endif

    <div @class([
        'relative rounded-b-2xl',
        'hidden md:block' => $hasCards,
        $minHeight
    ])>
        {{-- Livewire Loading Overlay --}}
        <div wire:loading class="absolute inset-0 bg-slate-500/10 backdrop-blur-xs z-50 flex items-center justify-center transition-all duration-300 rounded-b-2xl">
            <div class="flex flex-col items-center gap-2 px-5 py-3 bg-white border border-abubiru/40 shadow-lg rounded-xl">
                <svg class="animate-spin h-5 w-5 text-biru2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-bold text-biru2 uppercase tracking-wider select-none">Memproses data...</span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-b-2xl {{ $minHeight }}">
            <table class="w-full min-w-225">
            @if(isset($thead))
                {{ $thead }}
            @else
                <thead class="bg-putihbg text-xs font-bold uppercase tracking-widest text-biru4 border-b border-abubiru">
                    <tr>
                        @foreach($headers as $header)
                            @php
                                $labelText = is_array($header) ? ($header['label'] ?? '') : (string) $header;
                                $isCenter = is_array($header) && isset($header['align'])
                                    ? $header['align'] === 'center'
                                    : (str_contains(strtolower($labelText), 'status') || str_contains(strtolower($labelText), 'penilaian') || str_contains(strtolower($labelText), 'aksi'));
                            @endphp
                            <th @class([
                                'px-4 sm:px-8 py-4 sm:py-5',
                                'text-left' => !$isCenter,
                                'text-center' => $isCenter,
                            ])>
                                {{ $labelText }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="text-sm" x-ref="tbody">
                {{ $slot }}
            </tbody>
        </table>
        </div>
    </div>

    @if(isset($pagination))
        <div class="px-4 sm:px-8 py-4 sm:py-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm text-biru3 font-semibold bg-white border-t border-abubiru">
            {{ $pagination }}
        </div>
    @endif
</section>
