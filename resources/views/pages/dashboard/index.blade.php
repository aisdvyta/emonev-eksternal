<x-layouts.app :title="$pageTitle ?? 'Beranda - HaloKIP'" :sidebar="$role">
    @if ($role === 'admin')
        @include('pages.dashboard.partials.admin')
    @elseif (in_array($role, ['evaluator', 'super_evaluator']))
        @include('pages.dashboard.partials.evaluator')
    @elseif ($role === 'operator')
        @include('pages.dashboard.partials.operator')
    @elseif ($role === 'verifikator')
        @include('pages.dashboard.partials.verifikator')
    @elseif ($role === 'penilai_eksternal')
        @include('pages.dashboard.partials.penilai-eksternal')
    @else
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-6 text-amber-700 text-sm font-semibold">
            Role pengguna tidak dikenali.
        </div>
    @endif
</x-layouts.app>
