{{--
    Komponen Toast Notification — Reusable di seluruh halaman
    =========================================================

    CARA 1: Via Laravel Session Flash (otomatis muncul setelah redirect)
    ─────────────────────────────────────────────────────────────────────
    Di Controller:
        return redirect()->back()->with('toast', [
            'type'    => 'success',               // success | error | warning | info
            'message' => 'User berhasil ditambah',
        ]);

    Di layout (SEKALI saja, taruh di layouts/app.blade.php):
        <x-ui.toast-notification />


    CARA 2: Via JavaScript (trigger manual dari client-side):
    ─────────────────────────────────────────────────────────
    window.dispatchEvent(new CustomEvent('show-toast', {
        detail: { type: 'success', message: 'Data berhasil disimpan' }
    }));

    Props:
    ──────
    • duration   → durasi tampil dalam ms (default: 4000)
    • position   → posisi: 'top-right' | 'top-center' | 'bottom-right' (default: 'top-right')
--}}

@props([
    'duration' => 4000,
    'position' => 'top-right',
])

@php
    $positionClasses = match ($position) {
        'top-right' => 'top-6 right-6',
        'top-center' => 'top-6 left-1/2 -translate-x-1/2',
        'bottom-right' => 'bottom-6 right-6',
        default => 'top-6 right-6',
    };
@endphp

<div x-data="{
    toasts: [],
    duration: {{ $duration }},
    nextId: 0,

    init() {
        // Cek pending toast dari sessionStorage (misal setelah window.location.reload)
        try {
            const pendingToast = sessionStorage.getItem('pending_toast');
            if (pendingToast) {
                sessionStorage.removeItem('pending_toast');
                this.addToast(JSON.parse(pendingToast));
            }
        } catch (e) {}

        // Cek Laravel session flash
        @if (session('toast')) 
            this.addToast({{ json_encode(session('toast')) }}); 
        @endif
        @if (session('success') && !session('toast'))
            this.addToast({
                type: 'success',
                message: {{ json_encode(session('success')) }}
            });
        @endif
        @if (session('warning') && !session('toast'))
            this.addToast({
                type: 'warning',
                message: {{ json_encode(session('warning')) }}
            });
        @endif
        @if (session('error') && !session('toast'))
            this.addToast({
                type: 'error',
                message: {{ json_encode(session('error')) }}
            });
        @endif

        // Cek Laravel validation errors
        @if ($errors->any()) @foreach ($errors->all() as $error)
                    this.addToast({
                        type: 'error',
                        message: {{ json_encode($error) }}
                    });
                @endforeach @endif
    },

    addToast(data) {
        const id = this.nextId++;
        this.toasts.push({
            id,
            type: data.type || 'info',
            message: data.message || '',
            show: false,
        });

        // Trigger entrance animation
        this.$nextTick(() => {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.show = true;
        });

        // Auto dismiss
        setTimeout(() => this.removeToast(id), this.duration);
    },

    removeToast(id) {
        const t = this.toasts.find(t => t.id === id);
        if (t) t.show = false;
        setTimeout(() => {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }, 300);
    },

    getIcon(type) {
        const icons = {
            success: `<path stroke-linecap='round' stroke-linejoin='round' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/>`,
            error: `<path stroke-linecap='round' stroke-linejoin='round' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'/>`,
            warning: `<path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'/>`,
            info: `<path stroke-linecap='round' stroke-linejoin='round' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/>`,
        };
        return icons[type] || icons.info;
    },

    getColors(type) {
        const colors = {
            success: { bg: 'bg-hijau', icon: 'bg-white/20 text-white', ring: 'ring-hijau/30', text: 'text-white' },
            error: { bg: 'bg-merah1', icon: 'bg-white/20 text-white', ring: 'ring-merah1/30', text: 'text-white' },
            warning: { bg: 'bg-oren2', icon: 'bg-white/20 text-white', ring: 'ring-oren2/30', text: 'text-white' },
            info: { bg: 'bg-biru4', icon: 'bg-white/20 text-white', ring: 'ring-biru4/30', text: 'text-white' },
        };
        return colors[type] || colors.info;
    },

    getLabel(type) {
        const labels = { success: 'Berhasil', error: 'Gagal', warning: 'Peringatan', info: 'Informasi' };
        return labels[type] || 'Informasi';
    }
}" x-on:show-toast.window="addToast($event.detail)"
    class="fixed {{ $positionClasses }} z-9999 flex flex-col gap-3 pointer-events-none"
    style="min-width: 340px; max-width: 420px;">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-95"
            class="pointer-events-auto rounded-xl shadow-2xl ring-1 overflow-hidden"
            :class="`${getColors(toast.type).ring}`">
            <div class="flex items-start gap-3 px-5 py-4" :class="getColors(toast.type).bg">
                {{-- Icon --}}
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                    :class="getColors(toast.type).icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        x-html="getIcon(toast.type)"></svg>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider opacity-80"
                        :class="getColors(toast.type).text" x-text="getLabel(toast.type)"></div>
                    <div class="text-sm font-semibold mt-0.5 leading-snug" :class="getColors(toast.type).text"
                        x-text="toast.message"></div>
                </div>

                {{-- Close Button --}}
                <button @click="removeToast(toast.id)"
                    class="shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-white/70 hover:text-white hover:bg-white/10 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Progress bar (auto countdown) --}}
            <div class="h-1 w-full opacity-30" :class="getColors(toast.type).bg">
                <div class="h-full bg-white rounded-full"
                    :style="`animation: toast-countdown ${duration}ms linear forwards`"></div>
            </div>
        </div>
    </template>

    <style>
        @keyframes toast-countdown {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }
    </style>
</div>
