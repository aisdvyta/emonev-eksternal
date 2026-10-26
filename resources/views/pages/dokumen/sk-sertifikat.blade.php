<x-layouts.app title="SK & Sertifikat - HaloKIP">
    <div>
        <x-ui.page-header :breadcrumb="[
            'SK & Sertifikat' => '#',
        ]">
        </x-ui.page-header>

        <!-- Hero Banner -->
        <section
            class="rounded-xl bg-biru4/80 text-white shadow-sm px-6 py-6 md:px-10 md:py-8 relative overflow-hidden min-h-52 md:min-h-64 flex items-center mb-6 md:mb-8">
            <div
                class="absolute right-20 -bottom-20 h-80 w-80 rounded-full bg-biru5 opacity-100 blur-3xl pointer-events-none hidden lg:block">
            </div>
            <div
                class="absolute right-60 -top-15 h-80 w-80 rounded-full bg-kuning2 opacity-80 blur-3xl pointer-events-none hidden lg:block">
            </div>
            <div class="absolute right-40 bottom-3 hidden lg:block z-10">
                <img src="{{ asset('images/orangSKSertif.svg') }}" alt="SK & Sertifikat"
                    class="h-auto w-75 object-contain">
            </div>
            <div class="relative z-10">
                <h2 class="text-3xl md:text-3xl font-black text-kuning2 leading-tight mb-4 tracking-tight">Unduh SK &
                    Sertifikat</h2>
                <p class="text-base leading-relaxed text-white font-normal max-w-xl">Lihat dan unduh file SK serta
                    Sertifikat hasil evaluasi KIP.</p>
            </div>
        </section>

        @if (session('success'))
            <div
                class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3 animate-fade-in">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-xl text-sm font-semibold shadow-xs flex items-center gap-3">
                {{ session('error') }}
            </div>
        @endif

        <x-tables.monitoring-table title="Daftar SK & Sertifikat KIP" :hasCards="true">
            <x-slot name="cards">
                @forelse ($periods as $index => $p)
                    @php
                        $sub = $submissions->get($p->id_periode);
                        $result = $sub ? $sub->result : null;
                        $specificCert = $sub && $sub->skSertifikat && $sub->skSertifikat->has_sertifikat ? $sub->skSertifikat : null;
                    @endphp
                    <div class="bg-white rounded-xl border border-abubiru p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-3 border-b border-abubiru/30 pb-2.5">
                            <div>
                                <div class="font-bold text-biru1 text-sm leading-snug">{{ $p->nama }}</div>
                                <span class="text-xs font-semibold text-biru3">Periode Monev</span>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-bold shrink-0">
                                {{ $p->tahun ?? \Carbon\Carbon::parse($p->periode_start)->year }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1 text-xs">
                            {{-- SK --}}
                            <div class="bg-slate-50 rounded-xl p-2.5 border border-abubiru/40">
                                <span class="text-xs font-extrabold uppercase text-biru4 block mb-1.5">Berkas SK</span>
                                @if ($p->skSertifikat?->has_sk)
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('sk.download', ['uuid' => $p->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer"
                                            title="Pratinjau SK"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-abubiru text-biru2 hover:text-biru1 rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat
                                        </a>
                                        <a href="{{ route('sk.download', ['uuid' => $p->skSertifikat->uuid, 'download' => 1]) }}"
                                            title="Unduh SK"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-biru4 hover:bg-biru2 text-white rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 font-semibold italic block text-center py-1">Belum tersedia</span>
                                @endif
                            </div>

                            {{-- Sertifikat --}}
                            <div class="bg-slate-50 rounded-xl p-2.5 border border-abubiru/40">
                                <span class="text-xs font-extrabold uppercase text-biru4 block mb-1.5">Sertifikat</span>
                                @if ($specificCert && $result)
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid]) }}" target="_blank" rel="noopener noreferrer" title="Pratinjau Sertifikat"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-abubiru text-oren2 hover:text-oren1 rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat
                                        </a>
                                        <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid, 'download' => 1]) }}" title="Unduh Sertifikat"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-linear-to-r from-kuning1 to-oren2 text-white rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh
                                        </a>
                                    </div>
                                @elseif ($p->skSertifikat?->has_sertifikat)
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('sertifikat.download', ['uuid' => $p->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer" title="Pratinjau Sertifikat"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-abubiru text-oren2 hover:text-oren1 rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat
                                        </a>
                                        <a href="{{ route('sertifikat.download', ['uuid' => $p->skSertifikat->uuid, 'download' => 1]) }}" title="Unduh Sertifikat"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-linear-to-r from-kuning1 to-oren2 text-white rounded-lg transition text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 font-semibold italic block text-center py-1">Belum tersedia</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-biru3 border border-dashed border-abubiru rounded-xl bg-white font-medium text-xs">
                        Belum ada berkas SK atau Sertifikat.
                    </div>
                @endforelse
            </x-slot>

            <x-slot name="thead">
                <thead class="bg-putihbg text-xs font-bold uppercase tracking-wider text-biru4 border-b border-abubiru">
                    <tr>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20 w-16">No</th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">Tahun / Periode</th>
                        <th class="px-8 py-5 text-left border-r border-abubiru/20">SK</th>
                        <th class="px-8 py-5 text-center">Sertifikat</th>
                    </tr>
                </thead>
            </x-slot>

            @forelse ($periods as $index => $p)
                    @php
                        $sub = $submissions->get($p->id_periode);
                        $result = $sub ? $sub->result : null;
                        $specificCert =
                            $sub && $sub->skSertifikat && $sub->skSertifikat->has_sertifikat ? $sub->skSertifikat : null;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition duration-200">
                        <td class="px-8 py-5 font-bold text-biru3 border-r border-abubiru/10">{{ $index + 1 }}</td>
                        <td class="px-8 py-5 font-bold text-biru1 border-r border-abubiru/10">
                            {{ $p->tahun ?? \Carbon\Carbon::parse($p->periode_start)->year }}
                            <span class="block text-xs font-semibold text-biru3 mt-0.5">{{ $p->nama }}</span>
                        </td>
                        <td class="px-8 py-5 border-r border-abubiru/10">
                            @if ($p->skSertifikat?->has_sk)
                                <div class="flex items-center gap-2">
                                    <!-- Preview SK -->
                                    <a href="{{ route('sk.download', ['uuid' => $p->skSertifikat->uuid]) }}" target="_blank" rel="noopener noreferrer"
                                        title="Pratinjau SK"
                                        class="inline-flex items-center justify-center p-2 bg-white border border-abubiru text-biru2 hover:text-biru1 hover:bg-slate-50 rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <!-- Download SK -->
                                    <a href="{{ route('sk.download', ['uuid' => $p->skSertifikat->uuid, 'download' => 1]) }}"
                                        title="Unduh SK"
                                        class="inline-flex items-center justify-center p-2 bg-biru4 hover:bg-biru2 text-white rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 font-semibold italic">Belum tersedia</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-center">
                            @if ($specificCert && $result)
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Preview Sertifikat -->
                                    <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid]) }}"
                                        target="_blank" rel="noopener noreferrer" title="Pratinjau Sertifikat"
                                        class="inline-flex items-center justify-center p-2 bg-white border border-abubiru text-oren2 hover:text-oren1 hover:bg-slate-50 rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <!-- Download Sertifikat -->
                                    <a href="{{ route('sertifikat.download', ['uuid' => $specificCert->uuid, 'download' => 1]) }}"
                                        title="Unduh Sertifikat"
                                        class="inline-flex items-center justify-center p-2 bg-linear-to-r from-kuning1 to-oren2 hover:from-oren2 hover:to-kuning1 text-white rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            @elseif ($p->skSertifikat?->has_sertifikat)
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Preview Sertifikat Global -->
                                    <a href="{{ route('sertifikat.download', ['uuid' => $p->skSertifikat->uuid]) }}"
                                        target="_blank" rel="noopener noreferrer" title="Pratinjau Sertifikat"
                                        class="inline-flex items-center justify-center p-2 bg-white border border-abubiru text-oren2 hover:text-oren1 hover:bg-slate-50 rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <!-- Download Sertifikat Global -->
                                    <a href="{{ route('sertifikat.download', ['uuid' => $p->skSertifikat->uuid, 'download' => 1]) }}"
                                        title="Unduh Sertifikat"
                                        class="inline-flex items-center justify-center p-2 bg-linear-to-r from-kuning1 to-oren2 hover:from-oren2 hover:to-kuning1 text-white rounded-xl transition duration-200 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 font-semibold italic">Belum tersedia</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-8 py-12 text-center text-sm font-semibold text-biru3 italic">Tidak ada data periode.</td>
                    </tr>
                @endforelse
        </x-tables.monitoring-table>
    </div>
</x-layouts.app>
