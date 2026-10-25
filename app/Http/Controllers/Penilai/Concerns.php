<?php

namespace App\Http\Controllers\Penilai;

use App\Models\Period;
use Illuminate\Http\Request;

/**
 * Pembantu bersama untuk controller penilai: pemilihan periode dan batas ukuran.
 */
trait Concerns
{
    /**
     * Periode yang dipilih lewat ?id_periode, jika tidak ada pakai periode berjalan/terbaru.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: ?Period}
     */
    protected function periodSelection(Request $request): array
    {
        $periods = Period::query()->orderByDesc('periode_start')->get();
        $selected = null;

        if ($request->filled('id_periode')) {
            $selected = $periods->firstWhere('id_periode', (int) $request->input('id_periode'));
        }

        return [$periods, $selected ?? Period::current()];
    }

    protected function periodLabel(?Period $p): string
    {
        if (! $p) {
            return '-';
        }

        return 'Periode '.($p->periode_start?->translatedFormat('Y') ?? $p->id_periode);
    }
}
