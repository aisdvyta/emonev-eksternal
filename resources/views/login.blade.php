<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — HaloKIP</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
    </noscript>
    <style>
        html {
            visibility: hidden;
            opacity: 0
        }
    </style>
    @vite('resources/js/app.js')
    <style>
        body {
            background-color: #f3f8fb;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        @keyframes floating {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-12px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-floating {
            animation: floating 5s ease-in-out infinite;
        }

        @keyframes fadeInRight {
            0% {
                opacity: 0;
                transform: translateX(-32px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in-right {
            animation: fadeInRight 0.85s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .animation-delay-150 {
            animation-delay: 150ms;
        }
    </style>
</head>

<body class="antialiased text-slate-800 relative min-h-screen flex flex-col justify-between">

    <!-- Dynamic Canvas Partikel Background -->
    <canvas id="particle-canvas" class="fixed inset-0 pointer-events-none z-0 opacity-60"></canvas>

@php
    $formatRange = function($start, $end) {
        if (!$start || !$end) return null;
        try {
            $startDate = \Carbon\Carbon::parse($start);
            $endDate = \Carbon\Carbon::parse($end);
            if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
                return $startDate->translatedFormat('d') . ' - ' . $endDate->translatedFormat('d M Y');
            } elseif ($startDate->format('Y') === $endDate->format('Y')) {
                return $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M Y');
            } else {
                return $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y');
            }
        } catch (\Exception $e) {
            return null;
        }
    };

    $saqDates = isset($activePeriod) ? $formatRange($activePeriod->saq_start, $activePeriod->saq_end) : null;
    $evalDates = isset($activePeriod) ? $formatRange($activePeriod->eval_start, $activePeriod->eval_end) : null;
    $visitDates = isset($activePeriod) ? $formatRange($activePeriod->visit_start, $activePeriod->visit_end) : null;
    $interviewDates = isset($activePeriod) ? $formatRange($activePeriod->interview_start, $activePeriod->interview_end) : null;
    $resultDates = isset($activePeriod) ? $formatRange($activePeriod->result_start, $activePeriod->result_end) : null;
@endphp

    <!-- Header Logos (Left) & Actions (Navbar + Unduh Excel SAQ) -->
    <header
        class="relative z-20 w-full max-w-full px-4 sm:px-6 md:px-10 pt-4 md:pt-6 flex items-center justify-between select-none">
        <div class="flex items-center pointer-events-none">
            <img src="{{ asset('images/bpsjatimIkon.webp') }}" alt="BPS Provinsi Jawa Timur"
                class="h-8.5 sm:h-12 md:h-14 lg:h-18 w-auto object-contain">
            <img src="{{ asset('images/pstjatimIkon.webp') }}" alt="Pelayanan Statistik Terpadu Jatim"
                class="h-8.5 sm:h-12 md:h-14 lg:h-18 w-auto object-contain">
        </div>

        <!-- Actions: Desktop Nav + Unduh SAQ Button (Icon-only di HP, full text di Desktop) -->
        <div class="flex items-center gap-2 sm:gap-5 pointer-events-auto">
            <!-- Desktop Navbar Header (Hidden di HP) -->
            <nav class="hidden md:flex items-center gap-2">
                <button type="button" id="nav-btn-home" onclick="switchNav('home')"
                    class="relative py-2 px-3 text-sm font-bold text-biru1 transition-colors cursor-pointer group">
                    <span>Beranda</span>
                    <span id="nav-underline-home" class="absolute bottom-0 left-2 right-2 h-0.75 bg-biru1 rounded-full transition-all duration-300"></span>
                </button>
                <button type="button" id="nav-btn-timeline" onclick="switchNav('timeline')"
                    class="relative py-2 px-3 text-sm font-bold text-slate-500 hover:text-biru1 transition-colors cursor-pointer group">
                    <span>Lihat Timeline</span>
                    <span id="nav-underline-timeline" class="absolute bottom-0 left-2 right-2 h-0.75 bg-biru1 rounded-full transition-all duration-300 opacity-0 scale-x-0"></span>
                </button>
            </nav>

            <span class="h-5 w-px bg-slate-300/80 hidden md:block"></span>

            <!-- Tombol Unduh SAQ: di HP ikon saja, di Desktop teks lengkap -->
            <a href="{{ route('public.template-saq') }}"
                class="inline-flex items-center justify-center gap-2 p-2 sm:p-2.5 md:px-4.5 md:py-2.5 rounded-xl bg-hijau hover:opacity-90 text-white font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer group shrink-0"
                title="Unduh Template Excel Kuesioner SAQ Kosongan"
                aria-label="Unduh Template Excel SAQ">
                <svg class="w-4 h-4 text-white transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="hidden md:inline">Unduh Excel SAQ</span>
            </a>
        </div>
    </header>

    <!-- Main Content Area: Smooth Horizontal Swipe Wrapper -->
    <main class="relative z-10 w-full flex-1 flex flex-col justify-start overflow-x-hidden min-h-[calc(100vh-140px)]">
        <!-- Horizontal Carousel Track (Pure X-Axis Slide without vertical jerk) -->
        <div id="views-slider-track" class="w-full flex items-start will-change-transform" style="transform: translateX(0%); transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
            
            <!-- SLIDE 1: Beranda -->
            <div id="view-main-login" class="w-full min-w-full shrink-0 flex items-center justify-center px-4 sm:px-6 py-6 pb-24 md:pb-6 min-h-[calc(100vh-160px)] transition-opacity duration-300 opacity-100">
        <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 gap-12 items-center">


            <!-- Left column: content card -->
            <div class="px-6 md:px-0">
                <div class="max-w-xl mx-auto">
                    <!-- Logo Header -->
                    <div class="mb-6 animate-fade-in-right">
                        <img src="{{ asset('images/halokip-logopanjang.svg') }}" alt="HaloKIP Logo"
                            class="h-24 md:h-32 lg:h-36 w-auto">
                    </div>

                    <!-- STATE 1: LANDING VIEW (Welcome & Tombol Masuk Aplikasi) -->
                    <div id="view-hero" class="transition-all duration-300 ease-in-out">
                        <div class="space-y-4">
                            <p
                                class="text-slate-600 text-sm md:text-base leading-relaxed font-normal animate-fade-in-right animation-delay-150">
                                Layanan pengelolaan dan evaluasi Keterbukaan Informasi Publik di lingkungan Badan Pusat
                                Statistik yang siap membantu Anda dengan akuntabel dan transparan.
                            </p>
                            <div class="pt-4">

                                <button type="button" onclick="showSelectionStep()"
                                    class="w-full sm:w-auto bg-biru2 hover:bg-biru1 text-white font-bold text-base px-8 py-4 rounded-2xl shadow-lg shadow-biru1/25 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3 cursor-pointer group">
                                    <svg class="w-5 h-5 text-white/90 group-hover:translate-x-1 transition-transform"
                                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Masuk Aplikasi</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STATE 2: MODAL SELECTION (Pilih Asal User BPS vs Eksternal) -->
                    <div id="view-selection" class="hidden transition-all duration-300 ease-in-out">
                        <div class="flex items-center gap-2 mb-4">
                            <button type="button" onclick="showHeroView()"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-biru1 transition-colors bg-white/80 border border-slate-200 px-3 py-1.5 rounded-full cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>Kembali</span>
                            </button>
                        </div>
                        <div>
                            <h3 class="text-slate-400 font-bold text-sm tracking-wide">Selamat datang</h3>
                            <h1 class="text-2xl md:text-3xl font-black text-biru2 tracking-tight mt-1">Dari mana Anda
                                berasal?</h1>
                        </div>

                        <div class="mt-6 space-y-4">
                            <!-- Option 1: Pegawai BPS (SSO) -->
                            <a href="{{ config('external.pusat_url') }}"
                                class="w-full bg-biru2 hover:bg-biru1 text-white p-4.5 rounded-2xl font-bold shadow-md shadow-biru1/20 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-between group cursor-pointer border border-biru1/30">

                                <div class="flex items-center gap-3.5">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            viewBox="0 0 24 24">
                                            <rect x="4" y="2" width="16" height="20" rx="2" />
                                            <path d="M9 6h2v2H9z" />
                                            <path d="M13 6h2v2h-2z" />
                                            <path d="M9 11h2v2H9z" />
                                            <path d="M13 11h2v2h-2z" />
                                            <path d="M10 22v-4h4v4" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-sm font-extrabold text-white">Badan Pusat Statistik</div>
                                        <div class="text-[11px] font-medium text-white/80">Login via Single Sign-On (SSO
                                            BPS)</div>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-white/80 group-hover:translate-x-1 transition-transform shrink-0"
                                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <!-- Option 2: Pihak Eksternal / Non-BPS -->
                            <button type="button" onclick="showFormView()"
                                class="w-full bg-white hover:bg-slate-50 border border-abubiru text-slate-800 p-4.5 rounded-2xl font-bold shadow-sm hover:shadow-md hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-between group cursor-pointer">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-sm font-extrabold text-slate-900">Selain Badan Pusat Statistik
                                        </div>
                                        <div class="text-[11px] font-medium text-slate-500">Badan Publik / Pengguna
                                            Eksternal</div>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-slate-400 group-hover:translate-x-1 transition-transform shrink-0"
                                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- STATE 3: FORM LOGIN ASLI (LOGIN BOX LAMA) -->
                    <div id="view-form" class="hidden transition-all duration-300 ease-in-out">
                        <div class="flex items-center gap-2 mb-4">
                            <button type="button" onclick="showSelectionStep()"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-biru1 transition-colors bg-white/80 border border-slate-200 px-3 py-1.5 rounded-full cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>Kembali</span>
                            </button>
                        </div>
                        <div>
                            <h3 class="text-slate-400 font-bold text-sm md:text-base tracking-wide">Selamat datang</h3>
                            <h1 class="text-3xl md:text-4xl font-black text-biru2 tracking-tight mt-1">Silahkan, Masuk!
                            </h1>
                        </div>

                        <!-- Login Form Box Original -->
                        <div class="mt-6 bg-white rounded-3xl border border-abubiru shadow-lg p-8">

                            @if (session('success'))
                                <div
                                    class="mb-5 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold shadow-sm">
                                    <span>{{ session('success') }}</span>
                                </div>
                            @endif

                            @if (session('error'))
                                <div
                                    class="mb-5 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold shadow-sm">
                                    <span>{{ session('error') }}</span>
                                </div>
                            @endif

                            <div id="login-error-container"
                                class="{{ $errors->any() ? '' : 'hidden' }} mb-5 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold shadow-sm">
                                <span id="login-error-message">{{ $errors->first() }}</span>
                            </div>

                            <form id="login-form" action="{{ route('login.post') }}" method="POST"
                                class="space-y-5" onsubmit="loginSPA(event)">
                                @csrf

                                <!-- Username Field -->
                                <div>
                                    <label class="sr-only">Username atau Email</label>
                                    <div
                                        class="flex items-center bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-3.5 focus-within:border-biru2 transition-all">
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                        <input type="text" name="nama_user" value="{{ old('nama_user') }}"
                                            required placeholder="Username atau Email"
                                            class="ml-3.5 w-full bg-transparent text-sm text-slate-700 font-medium placeholder-slate-400 outline-none" />
                                    </div>
                                </div>

                                <!-- Password Field -->
                                <div>
                                    <label class="sr-only">Password</label>
                                    <div
                                        class="flex items-center bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-3.5 focus-within:border-biru2 transition-all">
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="3" y="11" width="18" height="11" rx="2"
                                                ry="2" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7 11V7a5 5 0 0110 0v4" />
                                        </svg>
                                        <input type="password" name="password" id="password" required
                                            placeholder="Password"
                                            class="ml-3.5 w-full bg-transparent text-sm text-slate-700 font-medium placeholder-slate-400 outline-none" />
                                        <button type="button" onclick="togglePasswordVisibility()"
                                            class="ml-2 text-slate-400 hover:text-slate-600 focus:outline-none shrink-0"
                                            title="Tampilkan/Sembunyikan Password">
                                            <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <svg id="eye-closed" class="w-5 h-5 hidden" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-2">
                                    <button type="submit"
                                        class="w-full bg-biru1 hover:bg-biru2 text-white py-3.5 rounded-xl text-sm font-bold shadow-md shadow-biru1/10 hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                                        <span>Masuk</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: illustration (EXACT ORIGINAL FORM & CIRCLE LAYOUT) -->
            <div class="hidden md:flex items-center justify-center relative select-none">
                <!-- Glow background (Original) -->
                <div class="absolute right-[-10%] bottom-[-10%] w-96 h-96 rounded-full bg-kuning2 opacity-50 blur-3xl">
                </div>
                <!-- Large blue circle container (Original) -->
                <div
                    class="relative w-120 h-120 rounded-full bg-biru2 shadow-xl flex items-center justify-center overflow-hidden">
                </div>
                <!-- Illustration SVG overlaying the circle (Floating) -->
                <img src="{{ asset('images/orangLogin.svg') }}" alt="Ilustrasi Login"
                    class="absolute z-10 w-135 h-auto object-contain pointer-events-none drop-shadow-md animate-floating"
                    style="margin-top: 40px;" />
            </div>

        </div>
    </div>

        <!-- SLIDE 2: Timeline (Sits directly in the horizontal track) -->
        <div id="view-timeline" class="w-full min-w-full shrink-0 flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-8 md:py-14 pb-24 md:pb-14 min-h-[calc(100vh-90px)] transition-opacity duration-300 max-h-0 overflow-hidden opacity-0 pointer-events-none">
            <div class="w-full max-w-7xl mx-auto my-auto pt-6 md:pt-12">
        <!-- Headline -->
        <div class="text-center max-w-3xl mx-auto mb-6 md:mb-10 animate-fade-in-right">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-biru1 tracking-tight">
                Tahapan Keterbukaan Informasi Publik
            </h1>
            <p class="text-xs sm:text-sm md:text-base text-slate-500 font-medium mt-2">
                BPS Provinsi Jawa Timur — Periode Tahun {{ $activePeriod->tahun ?? date('Y') }}
            </p>
        </div>

        <!-- 3D Organic Wavy Ribbon Timeline (Desktop & Tablet: hidden md:block) -->
        <div class="hidden md:block relative w-full overflow-x-auto pb-10 pt-2 custom-scrollbar">
            <!-- Perspective Blueprint/Grid Plane Backdrop -->
            <div class="min-w-240 xl:min-w-0 relative px-4 py-8 rounded-3xl"
                style="background-image: linear-gradient(to right, rgba(147, 197, 253, 0.22) 1px, transparent 1px), linear-gradient(to bottom, rgba(147, 197, 253, 0.22) 1px, transparent 1px); background-size: 36px 36px;">

                <!-- SVG Wavy Ribbon Track (Meliuk lembut & mengalir tenang melewati 5 tahapan) -->
                <svg class="absolute inset-0 w-full h-85 pointer-events-none z-0" viewBox="0 0 1000 280" preserveAspectRatio="none" fill="none">
                    <defs>
                        <!-- Vibrant Blue to Golden Gradient on Curve -->
                        <linearGradient id="wavyRibbonGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#0284C7" />
                            <stop offset="28%" stop-color="#0EA5E9" />
                            <stop offset="55%" stop-color="#38BDF8" />
                            <stop offset="80%" stop-color="#0284C7" />
                            <stop offset="100%" stop-color="#F59E0B" />
                        </linearGradient>

                        <!-- Soft Drop Shadow untuk Kedalaman Liukan Pita -->
                        <filter id="ribbonGlow" x="-5%" y="-20%" width="110%" height="150%">
                            <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#0284C7" flood-opacity="0.20" />
                        </filter>
                    </defs>

                    <!-- Shadow Track Layer (Menambah sensasi melayang 3D lembut) -->
                    <path d="M 20,165 C 50,165 75,162 100,160 C 180,155 220,115 300,115 C 380,115 420,175 500,175 C 580,175 620,125 700,125 C 780,125 820,155 900,155 C 930,155 960,152 980,150"
                        stroke="#0f172a" stroke-opacity="0.06" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" transform="translate(0, 6)" />

                    <!-- Clean Single Wavy Ribbon Body (Gentle, smooth S-curves) -->
                    <path d="M 20,165 C 50,165 75,162 100,160 C 180,155 220,115 300,115 C 380,115 420,175 500,175 C 580,175 620,125 700,125 C 780,125 820,155 900,155 C 930,155 960,152 980,150"
                        stroke="url(#wavyRibbonGradient)" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" filter="url(#ribbonGlow)" />
                </svg>

                <!-- 5 Stages Grid dengan Ketinggian Mengikuti Liukan Ombak Landai yang Lembut -->
                <div class="relative z-10 grid grid-cols-5 gap-3 sm:gap-4 items-start text-center min-h-95">
                    
                    <!-- STAGE 1: Kuesioner SAQ (Mulai Santai) -->
                    <div class="flex flex-col items-center group cursor-pointer transition-transform duration-300 hover:-translate-y-2"
                        style="margin-top: 45px;">
                        <!-- Floating Pill Badge -->
                        <div class="mb-3.5 px-3 py-1 rounded-full bg-white shadow-md border border-slate-200/90 text-[11px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-4 h-4 rounded-full bg-biru2 text-white text-[10px] flex items-center justify-center font-black">1</span>
                            <span>Kuesioner SAQ</span>
                        </div>
                        <!-- 3D Isometric Tile (Tanpa Ring Putih) -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_12px_0_#071C30,0_20px_25px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300">
                            <!-- Clipboard Icon -->
                            <svg class="w-9 h-9 sm:w-11 sm:h-11 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <!-- Stage Subtitle & Dates -->
                        <div class="mt-4 px-1">
                            <h4 class="text-xs font-bold text-biru1">Pengisian Kuesioner</h4>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">
                                {{ $saqDates ?? 'Pengisian SAQ & Bukti' }}
                            </p>
                        </div>
                    </div>

                    <!-- STAGE 2: Evaluasi (Naik Lembut) -->
                    <div class="flex flex-col items-center group cursor-pointer transition-transform duration-300 hover:-translate-y-2"
                        style="margin-top: 0px;">
                        <div class="mb-3.5 px-3 py-1 rounded-full bg-white shadow-md border border-slate-200/90 text-[11px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-4 h-4 rounded-full bg-biru2 text-white text-[10px] flex items-center justify-center font-black">2</span>
                            <span>Pemeriksaan</span>
                        </div>
                        <!-- 3D Isometric Tile (Tanpa Ring Putih) -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_12px_0_#071C30,0_20px_25px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300">
                            <!-- Magnifier / Loupe Icon -->
                            <svg class="w-9 h-9 sm:w-11 sm:h-11 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <div class="mt-4 px-1">
                            <h4 class="text-xs font-bold text-biru1">Pemeriksaan SAQ</h4>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">
                                {{ $evalDates ?? 'Penilaian Evaluator' }}
                            </p>
                        </div>
                    </div>

                    <!-- STAGE 3: Wawancara (Turun Lembut) -->
                    <div class="flex flex-col items-center group cursor-pointer transition-transform duration-300 hover:-translate-y-2"
                        style="margin-top: 60px;">
                        <div class="mb-3.5 px-3 py-1 rounded-full bg-white shadow-md border border-slate-200/90 text-[11px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-4 h-4 rounded-full bg-biru2 text-white text-[10px] flex items-center justify-center font-black">3</span>
                            <span>Wawancara</span>
                        </div>
                        <!-- 3D Isometric Tile (Tanpa Ring Putih) -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_12px_0_#071C30,0_20px_25px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300">
                            <!-- Chat Bubbles Icon -->
                            <svg class="w-9 h-9 sm:w-11 sm:h-11 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div class="mt-4 px-1">
                            <h4 class="text-xs font-bold text-biru1">Presentasi & Wawancara</h4>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">
                                {{ $interviewDates ?? 'Presentasi & Komitmen' }}
                            </p>
                        </div>
                    </div>

                    <!-- STAGE 4: Visitasi (Naik Lembut) -->
                    <div class="flex flex-col items-center group cursor-pointer transition-transform duration-300 hover:-translate-y-2"
                        style="margin-top: 10px;">
                        <div class="mb-3.5 px-3 py-1 rounded-full bg-white shadow-md border border-slate-200/90 text-[11px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-4 h-4 rounded-full bg-biru2 text-white text-[10px] flex items-center justify-center font-black">4</span>
                            <span>Visitasi</span>
                        </div>
                        <!-- 3D Isometric Tile (Tanpa Ring Putih) -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_12px_0_#071C30,0_20px_25px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300">
                            <!-- Location Pin Icon -->
                            <svg class="w-9 h-9 sm:w-11 sm:h-11 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="mt-4 px-1">
                            <h4 class="text-xs font-bold text-biru1">Visitasi Lapangan</h4>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">
                                {{ $visitDates ?? 'Verifikasi Faktual' }}
                            </p>
                        </div>
                    </div>

                    <!-- STAGE 5: Penilaian Akhir (Melandai Halus - Amber Golden Finish) -->
                    <div class="flex flex-col items-center group cursor-pointer transition-transform duration-300 hover:-translate-y-2"
                        style="margin-top: 40px;">
                        <div class="mb-3.5 px-3 py-1 rounded-full bg-white shadow-md border border-amber-200 text-[11px] sm:text-xs font-bold text-amber-900 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-4 h-4 rounded-full bg-amber-500 text-white text-[10px] flex items-center justify-center font-black">5</span>
                            <span>Penilaian Akhir</span>
                        </div>
                        <!-- 3D Isometric Tile (Tanpa Ring Putih) -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-linear-to-b from-[#FBBF24] via-[#F59E0B] to-[#D97706] flex items-center justify-center text-slate-900 shadow-[0_12px_0_#92400E,0_20px_25px_rgba(245,158,11,0.35)] border-t border-amber-200 transition-all duration-300">
                            <!-- Solid Star Icon -->
                            <svg class="w-10 h-10 sm:w-12 sm:h-12 text-slate-900 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                            </svg>
                        </div>
                        <div class="mt-4 px-1">
                            <h4 class="text-xs font-bold text-amber-900">Penilaian Akhir</h4>
                            <p class="text-[11px] text-amber-700 font-medium mt-0.5 leading-snug">
                                {{ $resultDates ?? 'Hasil & Predikat' }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Vertical 3D Organic Wavy Ribbon Timeline (Mobile: md:hidden) -->
        <div class="md:hidden relative w-full max-w-sm mx-auto px-1 py-4">
            <!-- Perspective Blueprint/Grid Plane Backdrop -->
            <div class="relative px-3 py-6 rounded-3xl overflow-hidden"
                style="background-image: linear-gradient(to right, rgba(147, 197, 253, 0.22) 1px, transparent 1px), linear-gradient(to bottom, rgba(147, 197, 253, 0.22) 1px, transparent 1px); background-size: 32px 32px;">

                <!-- SVG Vertical Wavy Ribbon Track (Meliuk-liuk Halus & Smooth dari Atas ke Bawah) -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none z-0" viewBox="0 0 100 100" preserveAspectRatio="none" fill="none">
                    <defs>
                        <!-- Vertical Ribbon Gradient (Blue to Golden Amber) -->
                        <linearGradient id="verticalRibbonGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#0284C7" />
                            <stop offset="25%" stop-color="#0EA5E9" />
                            <stop offset="50%" stop-color="#38BDF8" />
                            <stop offset="75%" stop-color="#0284C7" />
                            <stop offset="100%" stop-color="#F59E0B" />
                        </linearGradient>

                        <!-- Soft Drop Shadow untuk Kedalaman Liukan Pita Vertikal -->
                        <filter id="verticalRibbonGlow" x="-20%" y="-10%" width="140%" height="120%">
                            <feDropShadow dx="0" dy="1" stdDeviation="1.5" flood-color="#0284C7" flood-opacity="0.25" />
                        </filter>
                    </defs>

                    <!-- Shadow Track Layer (Efek Bayangan Kedalaman 3D) -->
                    <path d="M 25,4 C 25,17 75,17 75,29 C 75,40 25,40 25,51 C 25,62 75,62 75,73 C 75,83 50,83 50,93"
                        stroke="#0f172a" stroke-opacity="0.06" stroke-width="4.8" stroke-linecap="round" stroke-linejoin="round" transform="translate(0, 0.8)" />

                    <!-- Main Organic Wavy Ribbon Body (Pita Meliuk Vertikal Halus) -->
                    <path d="M 25,4 C 25,17 75,17 75,29 C 75,40 25,40 25,51 C 25,62 75,62 75,73 C 75,83 50,83 50,93"
                        stroke="url(#verticalRibbonGradient)" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" filter="url(#verticalRibbonGlow)" />
                </svg>

                <!-- 5 Stages Grid Vertikal Berliuk (Alternating Left-Right S-Curve) -->
                <div class="relative z-10 flex flex-col space-y-9 py-2">
                    
                    <!-- STAGE 1: Kuesioner SAQ (Meliuk Kiri) -->
                    <div class="w-full flex justify-start pl-1 sm:pl-3">
                        <div class="flex flex-col items-center text-center w-38 group cursor-pointer transition-transform duration-300 active:scale-95">
                            <!-- Floating Pill Badge -->
                            <div class="mb-2.5 px-2.5 py-0.5 rounded-full bg-white shadow-md border border-slate-200/90 text-[10px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                                <span class="w-3.5 h-3.5 rounded-full bg-biru2 text-white text-[9px] flex items-center justify-center font-black">1</span>
                                <span>Kuesioner SAQ</span>
                            </div>
                            <!-- 3D Isometric Tile -->
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_10px_0_#071C30,0_18px_22px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300 group-hover:-translate-y-1">
                                <svg class="w-9 h-9 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <!-- Stage Subtitle & Dates -->
                            <div class="mt-3 px-1">
                                <h4 class="text-xs font-bold text-biru1">Pengisian Kuesioner</h4>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5 leading-snug">
                                    {{ $saqDates ?? 'Pengisian SAQ & Bukti' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STAGE 2: Evaluasi / Pemeriksaan (Meliuk Kanan) -->
                    <div class="w-full flex justify-end pr-1 sm:pr-3">
                        <div class="flex flex-col items-center text-center w-38 group cursor-pointer transition-transform duration-300 active:scale-95">
                            <div class="mb-2.5 px-2.5 py-0.5 rounded-full bg-white shadow-md border border-slate-200/90 text-[10px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                                <span class="w-3.5 h-3.5 rounded-full bg-biru2 text-white text-[9px] flex items-center justify-center font-black">2</span>
                                <span>Pemeriksaan</span>
                            </div>
                            <!-- 3D Isometric Tile -->
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_10px_0_#071C30,0_18px_22px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300 group-hover:-translate-y-1">
                                <svg class="w-9 h-9 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 px-1">
                                <h4 class="text-xs font-bold text-biru1">Pemeriksaan SAQ</h4>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5 leading-snug">
                                    {{ $evalDates ?? 'Penilaian Evaluator' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STAGE 3: Wawancara (Meliuk Kiri) -->
                    <div class="w-full flex justify-start pl-1 sm:pl-3">
                        <div class="flex flex-col items-center text-center w-38 group cursor-pointer transition-transform duration-300 active:scale-95">
                            <div class="mb-2.5 px-2.5 py-0.5 rounded-full bg-white shadow-md border border-slate-200/90 text-[10px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                                <span class="w-3.5 h-3.5 rounded-full bg-biru2 text-white text-[9px] flex items-center justify-center font-black">3</span>
                                <span>Wawancara</span>
                            </div>
                            <!-- 3D Isometric Tile -->
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_10px_0_#071C30,0_18px_22px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300 group-hover:-translate-y-1">
                                <svg class="w-9 h-9 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div class="mt-3 px-1">
                                <h4 class="text-xs font-bold text-biru1">Presentasi & Wawancara</h4>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5 leading-snug">
                                    {{ $interviewDates ?? 'Presentasi & Komitmen' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STAGE 4: Visitasi (Meliuk Kanan) -->
                    <div class="w-full flex justify-end pr-1 sm:pr-3">
                        <div class="flex flex-col items-center text-center w-38 group cursor-pointer transition-transform duration-300 active:scale-95">
                            <div class="mb-2.5 px-2.5 py-0.5 rounded-full bg-white shadow-md border border-slate-200/90 text-[10px] sm:text-xs font-bold text-biru1 flex items-center gap-1.5 whitespace-nowrap">
                                <span class="w-3.5 h-3.5 rounded-full bg-biru2 text-white text-[9px] flex items-center justify-center font-black">4</span>
                                <span>Visitasi</span>
                            </div>
                            <!-- 3D Isometric Tile -->
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-b from-[#184F7C] to-[#0D2F4E] flex items-center justify-center text-white shadow-[0_10px_0_#071C30,0_18px_22px_rgba(0,0,0,0.25)] border-t border-white/30 transition-all duration-300 group-hover:-translate-y-1">
                                <svg class="w-9 h-9 text-white drop-shadow-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 px-1">
                                <h4 class="text-xs font-bold text-biru1">Visitasi Lapangan</h4>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5 leading-snug">
                                    {{ $visitDates ?? 'Verifikasi Faktual' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STAGE 5: Penilaian Akhir (Meliuk ke Tengah - Golden Climax Finish) -->
                    <div class="w-full flex justify-center">
                        <div class="flex flex-col items-center text-center w-38 group cursor-pointer transition-transform duration-300 active:scale-95">
                            <div class="mb-2.5 px-2.5 py-0.5 rounded-full bg-white shadow-md border border-amber-200 text-[10px] sm:text-xs font-bold text-amber-900 flex items-center gap-1.5 whitespace-nowrap">
                                <span class="w-3.5 h-3.5 rounded-full bg-amber-500 text-white text-[9px] flex items-center justify-center font-black">5</span>
                                <span>Penilaian Akhir</span>
                            </div>
                            <!-- 3D Isometric Tile: Amber Golden Finish -->
                            <div class="w-20 h-20 rounded-2xl bg-linear-to-b from-[#FBBF24] via-[#F59E0B] to-[#D97706] flex items-center justify-center text-slate-900 shadow-[0_10px_0_#92400E,0_18px_22px_rgba(245,158,11,0.35)] border-t border-amber-200 transition-all duration-300 group-hover:-translate-y-1">
                                <svg class="w-9 h-9 text-slate-900 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                </svg>
                            </div>
                            <div class="mt-3 px-1">
                                <h4 class="text-xs font-bold text-amber-900">Penilaian Akhir</h4>
                                <p class="text-[10px] text-amber-700 font-medium mt-0.5 leading-snug">
                                    {{ $resultDates ?? 'Hasil & Predikat' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
            </div>
        </div>

        </div>
    </main>

    <!-- Mobile Bottom Navigation Bar (md:hidden, sama seperti bottom-nav setelah login) -->
    <nav id="mobile-bottom-navbar"
        class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_25px_rgba(15,23,42,0.08)]"
        style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="flex items-center justify-around h-15 px-8">
            <!-- Tab 1: Beranda -->
            <button type="button" id="mobile-nav-btn-home" onclick="switchNav('home')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 cursor-pointer group">
                <svg id="mobile-nav-icon-home" class="w-5.5 h-5.5 text-biru1 transition-colors" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span id="mobile-nav-label-home" class="text-xs font-extrabold tracking-tight text-biru1 mt-0.5 transition-colors">
                    Beranda
                </span>
                <span id="mobile-nav-dot-home" class="w-1.5 h-1.5 rounded-full bg-biru1 mt-0.5 transition-all"></span>
            </button>

            <!-- Divider vertikal pemisah halus -->
            <span class="h-6 w-px bg-slate-200/80"></span>

            <!-- Tab 2: Timeline -->
            <button type="button" id="mobile-nav-btn-timeline" onclick="switchNav('timeline')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 cursor-pointer group">
                <svg id="mobile-nav-icon-timeline" class="w-5.5 h-5.5 text-slate-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span id="mobile-nav-label-timeline" class="text-xs font-medium tracking-tight text-slate-400 mt-0.5 transition-colors">
                    Timeline
                </span>
                <span id="mobile-nav-dot-timeline" class="w-1.5 h-1.5 rounded-full bg-transparent mt-0.5 transition-all"></span>
            </button>
        </div>
    </nav>

    <!-- State Switcher Scripts -->
    <script>
        const viewHero = document.getElementById('view-hero');
        const viewSelection = document.getElementById('view-selection');
        const viewForm = document.getElementById('view-form');

        let currentNav = 'home';
        let isNavSwitching = false;

        function switchNav(target) {
            if (target === currentNav || isNavSwitching) return;
            isNavSwitching = true;

            const sliderTrack = document.getElementById('views-slider-track');
            const viewMainLogin = document.getElementById('view-main-login');
            const viewTimeline = document.getElementById('view-timeline');

            const navBtnHome = document.getElementById('nav-btn-home');
            const navBtnTimeline = document.getElementById('nav-btn-timeline');
            const underlineHome = document.getElementById('nav-underline-home');
            const underlineTimeline = document.getElementById('nav-underline-timeline');

            // Mobile Bottom Nav Elements
            const mIconHome = document.getElementById('mobile-nav-icon-home');
            const mIconTimeline = document.getElementById('mobile-nav-icon-timeline');
            const mLabelHome = document.getElementById('mobile-nav-label-home');
            const mLabelTimeline = document.getElementById('mobile-nav-label-timeline');
            const mDotHome = document.getElementById('mobile-nav-dot-home');
            const mDotTimeline = document.getElementById('mobile-nav-dot-timeline');

            if (target === 'timeline') {
                currentNav = 'timeline';

                // Desktop Navbar State
                if (navBtnHome) {
                    navBtnHome.classList.remove('text-biru1');
                    navBtnHome.classList.add('text-slate-500');
                    underlineHome?.classList.add('opacity-0', 'scale-x-0');
                }
                if (navBtnTimeline) {
                    navBtnTimeline.classList.remove('text-slate-500');
                    navBtnTimeline.classList.add('text-biru1');
                    underlineTimeline?.classList.remove('opacity-0', 'scale-x-0');
                }

                // Mobile Bottom Navbar State
                if (mIconHome) {
                    mIconHome.classList.remove('text-biru1');
                    mIconHome.classList.add('text-slate-400');
                }
                if (mLabelHome) {
                    mLabelHome.classList.remove('text-biru1', 'font-extrabold');
                    mLabelHome.classList.add('text-slate-400', 'font-medium');
                }
                if (mDotHome) {
                    mDotHome.classList.remove('bg-biru1');
                    mDotHome.classList.add('bg-transparent');
                }
                if (mIconTimeline) {
                    mIconTimeline.classList.remove('text-slate-400');
                    mIconTimeline.classList.add('text-biru1');
                }
                if (mLabelTimeline) {
                    mLabelTimeline.classList.remove('text-slate-400', 'font-medium');
                    mLabelTimeline.classList.add('text-biru1', 'font-extrabold');
                }
                if (mDotTimeline) {
                    mDotTimeline.classList.remove('bg-transparent');
                    mDotTimeline.classList.add('bg-biru1');
                }

                // 1. Prepare Timeline slide into the track
                viewTimeline.classList.remove('max-h-0', 'overflow-hidden', 'pointer-events-none');
                viewTimeline.classList.add('opacity-100', 'pointer-events-auto');

                // Force layout reflow so transition starts cleanly
                void sliderTrack.offsetWidth;

                // 2. Pure horizontal carousel slide along X-axis
                sliderTrack.style.transform = 'translateX(-100%)';
                viewMainLogin.classList.remove('opacity-100');
                viewMainLogin.classList.add('opacity-0', 'pointer-events-none');

                setTimeout(() => {
                    // 3. Slide complete: collapse Beranda height to avoid dead scroll area
                    viewMainLogin.classList.add('max-h-0', 'overflow-hidden');
                    isNavSwitching = false;
                }, 500);

                if (window.scrollY > 10) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } else {
                currentNav = 'home';

                // Desktop Navbar State
                if (navBtnTimeline) {
                    navBtnTimeline.classList.remove('text-biru1');
                    navBtnTimeline.classList.add('text-slate-500');
                    underlineTimeline?.classList.add('opacity-0', 'scale-x-0');
                }
                if (navBtnHome) {
                    navBtnHome.classList.remove('text-slate-500');
                    navBtnHome.classList.add('text-biru1');
                    underlineHome?.classList.remove('opacity-0', 'scale-x-0');
                }

                // Mobile Bottom Navbar State
                if (mIconTimeline) {
                    mIconTimeline.classList.remove('text-biru1');
                    mIconTimeline.classList.add('text-slate-400');
                }
                if (mLabelTimeline) {
                    mLabelTimeline.classList.remove('text-biru1', 'font-extrabold');
                    mLabelTimeline.classList.add('text-slate-400', 'font-medium');
                }
                if (mDotTimeline) {
                    mDotTimeline.classList.remove('bg-biru1');
                    mDotTimeline.classList.add('bg-transparent');
                }
                if (mIconHome) {
                    mIconHome.classList.remove('text-slate-400');
                    mIconHome.classList.add('text-biru1');
                }
                if (mLabelHome) {
                    mLabelHome.classList.remove('text-slate-400', 'font-medium');
                    mLabelHome.classList.add('text-biru1', 'font-extrabold');
                }
                if (mDotHome) {
                    mDotHome.classList.remove('bg-transparent');
                    mDotHome.classList.add('bg-biru1');
                }

                // 1. Prepare Beranda slide into the track
                viewMainLogin.classList.remove('max-h-0', 'overflow-hidden', 'pointer-events-none');
                viewMainLogin.classList.add('opacity-100', 'pointer-events-auto');

                // Force layout reflow
                void sliderTrack.offsetWidth;

                // 2. Pure horizontal carousel slide along X-axis
                sliderTrack.style.transform = 'translateX(0%)';
                viewTimeline.classList.remove('opacity-100');
                viewTimeline.classList.add('opacity-0', 'pointer-events-none');

                setTimeout(() => {
                    // 3. Slide complete: collapse Timeline height
                    viewTimeline.classList.add('max-h-0', 'overflow-hidden');
                    isNavSwitching = false;
                }, 500);

                if (window.scrollY > 10) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }

        // TOUCH SWIPE GESTURE SUPPORT (Swipe kiri/kanan di HP)
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;

        document.addEventListener('touchstart', (e) => {
            if (['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'].includes(e.target.tagName)) return;
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'].includes(e.target.tagName)) return;
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipeGesture();
        }, { passive: true });

        function handleSwipeGesture() {
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;
            // Deteksi swipe horizontal yang jelas (> 50px dan lebih besar dari pergeseran vertikal)
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                if (deltaX < 0 && currentNav === 'home') {
                    // Swipe ke kiri -> Pindah ke Timeline
                    switchNav('timeline');
                } else if (deltaX > 0 && currentNav === 'timeline') {
                    // Swipe ke kanan -> Kembali ke Beranda
                    switchNav('home');
                }
            }
        }

        function showHeroView() {
            switchNav('home');
            viewSelection.classList.add('hidden');
            viewForm.classList.add('hidden');
            viewHero.classList.remove('hidden');
        }

        function showSelectionStep() {
            switchNav('home');
            viewHero.classList.add('hidden');
            viewForm.classList.add('hidden');
            viewSelection.classList.remove('hidden');
        }

        function showFormView() {
            switchNav('home');
            viewHero.classList.add('hidden');
            viewSelection.classList.add('hidden');
            viewForm.classList.remove('hidden');
        }

        // Auto open form / selection jika ada alert error / session
        document.addEventListener('DOMContentLoaded', () => {
            @if ($errors->any())
                showFormView();
            @elseif (session('error') || session('success'))
                showSelectionStep();
            @endif
        });

        // Toggle Password Visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        // Login SPA Handler
        async function loginSPA(event) {
            event.preventDefault();

            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const submitBtnText = submitBtn.querySelector('span');
            const errorContainer = document.getElementById('login-error-container');
            const errorMessage = document.getElementById('login-error-message');

            errorContainer.classList.add('hidden');
            errorMessage.textContent = '';

            const originalText = submitBtnText.textContent;
            submitBtnText.textContent = 'Memproses...';
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    let errorText = 'Terjadi kesalahan. Silakan coba lagi.';
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey].length > 0) {
                            errorText = data.errors[firstKey][0];
                        }
                    } else if (data.message) {
                        errorText = data.message;
                    }

                    errorMessage.textContent = errorText;
                    errorContainer.classList.remove('hidden');

                    submitBtnText.textContent = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMessage.textContent = 'Koneksi gagal atau terjadi masalah pada server.';
                errorContainer.classList.remove('hidden');

                submitBtnText.textContent = originalText;
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        // Interactive Floating Bubbles Canvas
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');
        let bubbles = [];

        // Kode warna tema asli emonev-kip
        const bubbleColors = [
            'rgba(76, 132, 176, ', // biru4 (#4C84B0)
            'rgba(247, 183, 80, ', // kuning1 (#F7B750)
            'rgba(144, 204, 147, ' // hijau2 (#90CC93)
        ];

        // Lacak posisi kursor mouse untuk efek menghindar (Repulsion)
        let mouse = {
            x: null,
            y: null,
            radius: 120 // Jarak jangkauan sensor menghindar gelembung dari kursor (120px)
        };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        window.addEventListener('mouseleave', () => {
            mouse.x = null;
            mouse.y = null;
        });

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        class Bubble {
            constructor() {
                this.reset(true);
            }

            reset(initial = false) {
                this.x = Math.random() * canvas.width;
                this.y = initial ? Math.random() * canvas.height : canvas.height + Math.random() * 40;
                // Ukuran bubble (diameter bervariasi dari sedang hingga besar)
                this.radius = Math.random() * 16 + 6;
                // Kecepatan melayang naik ke atas secara konstan & mengapung
                this.speedY = Math.random() * 0.4 + 0.15;
                this.speedX = Math.random() * 0.3 - 0.15;
                this.wobble = Math.random() * Math.PI * 2;
                this.wobbleSpeed = Math.random() * 0.02 + 0.008;

                // Variabel dorongan gaya menghindar
                this.forceX = 0;
                this.forceY = 0;

                // Pilih warna dasar dari palet (biru4, kuning1, hijau2) dengan transparansi lembut
                this.colorBase = bubbleColors[Math.floor(Math.random() * bubbleColors.length)];
                this.alpha = Math.random() * 0.25 + 0.15; // Opacity 15% - 40%
            }

            update() {
                // Efek Menghindar Kursor Mouse (Repulsion Physics)
                if (mouse.x !== null && mouse.y !== null) {
                    const dx = this.x - mouse.x;
                    const dy = this.y - mouse.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouse.radius && distance > 0) {
                        const forceDirectionX = dx / distance;
                        const forceDirectionY = dy / distance;
                        const maxDistance = mouse.radius;
                        // Semakin dekat kursor, gaya terdorong semakin kuat
                        const force = (maxDistance - distance) / maxDistance;

                        this.forceX += forceDirectionX * force * 1.2;
                        this.forceY += forceDirectionY * force * 1.2;
                    }
                }

                // Terapkan gaya gesekan (damping) agar pergerakan menghindar terasa kenyal/smooth
                this.forceX *= 0.88;
                this.forceY *= 0.88;

                this.y -= (this.speedY - this.forceY); // Movement Y
                this.wobble += this.wobbleSpeed;
                this.x += this.speedX + Math.sin(this.wobble) * 0.35 + this.forceX; // Movement X

                // Reset jika gelembung sudah keluar dari atas layar
                if (this.y + this.radius < -20 || this.x + this.radius < -20 || this.x - this.radius > canvas.width +
                    20) {
                    this.reset(false);
                }
            }


            draw() {
                ctx.save();
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);

                // Efek Gelembung Transparan dengan Gradient & Kilauan Refleksi
                const gradient = ctx.createRadialGradient(
                    this.x - this.radius * 0.3,
                    this.y - this.radius * 0.3,
                    this.radius * 0.1,
                    this.x,
                    this.y,
                    this.radius
                );

                gradient.addColorStop(0, this.colorBase + (this.alpha + 0.15) + ')');
                gradient.addColorStop(0.7, this.colorBase + this.alpha + ')');
                gradient.addColorStop(1, this.colorBase + (this.alpha * 0.4) + ')');

                ctx.fillStyle = gradient;
                ctx.fill();

                // Lingkaran stroke tipis gelembung
                ctx.strokeStyle = this.colorBase + (this.alpha + 0.1) + ')';
                ctx.lineWidth = 0.8;
                ctx.stroke();

                // Bintik kilatan cahaya di atas gelembung (Bubble Reflection highlight)
                ctx.beginPath();
                ctx.arc(this.x - this.radius * 0.35, this.y - this.radius * 0.35, this.radius * 0.2, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255, 255, 255, 0.45)';
                ctx.fill();

                ctx.restore();
            }
        }

        function initBubbles() {
            bubbles = [];
            const count = Math.floor(window.innerWidth / 35); // Jumlah gelembung proporsional layar
            for (let i = 0; i < count; i++) {
                bubbles.push(new Bubble());
            }
        }

        function animateBubbles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < bubbles.length; i++) {
                bubbles[i].update();
                bubbles[i].draw();
            }
            requestAnimationFrame(animateBubbles);
        }

        window.addEventListener('resize', () => {
            resizeCanvas();
            initBubbles();
        });

        resizeCanvas();
        initBubbles();
        animateBubbles();
    </script>

    {{-- Toast Notification Global --}}
    <x-ui.toast-notification />

    @livewireScripts
</body>

</html>
