<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\PendingScores;
use App\Services\ScoreAcknowledger;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    /**
     * GET /api/v1/sync/scores
     * Daftar nilai penilai eksternal yang belum diambil pusat (pending_sync=true).
     * Idempoten: dipanggil berulang mengembalikan hal yang sama sampai di-ack.
     */
    public function index(PendingScores $pending)
    {
        $result = $pending->collect();

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toIso8601String(),
            'has_more' => $result['has_more'],
            'items' => $result['items'],
        ]);
    }

    /**
     * POST /api/v1/sync/ack
     * Pusat memberi tahu nilai mana yang sudah tersimpan. Hanya baris yang revision-nya sama
     * yang dibersihkan, sehingga edit baru penilai setelah pull tidak ikut hilang.
     */
    public function ack(Request $request, ScoreAcknowledger $acknowledger)
    {
        $items = $request->json('items');
        if (! is_array($items)) {
            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 422);
        }

        $acked = $acknowledger->acknowledge($items);

        SyncLog::record('ack', true, "acked={$acked} dari ".count($items), $request->ip());

        return response()->json(['ok' => true, 'acked' => $acked]);
    }
}
