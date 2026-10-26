@props([
    'title' => 'Jadwal Kegiatan',
    'maxWidth' => 'max-w-2xl',
])

<div x-show="showJadwalModal" 
     x-transition:enter="transition ease-out duration-200" 
     x-transition:enter-start="opacity-0" 
     x-transition:enter-end="opacity-100" 
     x-transition:leave="transition ease-in duration-150" 
     x-transition:leave-start="opacity-100" 
     x-transition:leave-end="opacity-0" 
     class="fixed inset-0 z-50 overflow-y-auto flex items-start justify-center p-4 md:py-12" 
     style="display:none"
     x-cloak>
    <!-- Overlay -->
    <div class="fixed inset-0 bg-biru1/40 backdrop-blur-sm" @click="showJadwalModal = false"></div>
    
    <!-- Modal Content -->
    <div x-show="showJadwalModal" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="relative w-full {{ $maxWidth }} rounded-2xl bg-white border border-abubiru shadow-xl z-10 overflow-visible flex flex-col my-auto"
         @click.stop>
        
        <div class="px-6 py-4 bg-biru1 flex items-center justify-between rounded-t-2xl shrink-0">
            <h3 class="text-base font-bold text-white flex items-center gap-2.5">
                <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span x-text="typeof modalTitle !== 'undefined' ? modalTitle : '{{ $title }}'">{{ $title }}</span>
            </h3>
            <button @click="showJadwalModal = false" class="text-white/60 hover:text-white transition rounded-lg p-1 hover:bg-white/10 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="px-7 py-6 space-y-5 overflow-visible flex-1">
            {{ $slot }}
        </div>
        
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-abubiru/50 bg-white rounded-b-2xl shrink-0">
            <button type="button" @click="showJadwalModal = false" class="px-12 py-2.5 bg-white border border-abubiru hover:bg-biru2 hover:text-white text-biru2 rounded-lg text-sm font-semibold transition">Batal</button>
            <button type="submit" form="jadwal-form"
                :disabled="typeof saveDisabled !== 'undefined' ? saveDisabled : false"
                :title="typeof saveDisabled !== 'undefined' && saveDisabled ? 'Lengkapi data terlebih dahulu' : 'Simpan jadwal'"
                :class="(typeof saveDisabled !== 'undefined' && saveDisabled)
                    ? 'px-6 py-2.5 text-white rounded-lg text-sm font-bold transition shadow-sm bg-slate-300 cursor-not-allowed opacity-60'
                    : 'px-6 py-2.5 text-white rounded-lg text-sm font-bold transition shadow-sm bg-biru4 hover:bg-biru2 cursor-pointer'">
                Simpan Jadwal
            </button>
        </div>
    </div>
</div>
