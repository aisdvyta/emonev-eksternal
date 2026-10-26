@props(['name'])

<section
    {{ $attributes->merge(['class' => 'rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8']) }}>
    <div
        class="absolute right-20 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block">
    </div>
    <div
        class="absolute right-60 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block">
    </div>

    <div class="absolute right-40 bottom-3.5 hidden lg:block z-10">
        <img src="{{ asset('images/orangHomeIcon.svg') }}" alt="Beranda" class="h-55 w-auto object-contain">
    </div>

    <div class="relative z-10 max-w-full md:max-w-160">
        <div class="text-2xl md:text-2xl font-semibold mb-2">Selamat Datang,</div>
        <div
            class="text-4xl md:text-5xl lg:text-6xl font-bold text-kuning2 leading-tight tracking-tight mb-4 md:mb-6 wrap-break-word">
            {{ $name }}
        </div>
        <p class="max-w-full md:max-w-115 text-sm md:text-base leading-relaxed text-white font-medium">
            {{ $slot }}
        </p>
    </div>
</section>
