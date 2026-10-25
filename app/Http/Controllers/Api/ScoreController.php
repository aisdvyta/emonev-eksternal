<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewEvaluator;
use App\Models\SyncLog;
use App\Models\VisitasiEvaluator;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    /**
     * GET /api/v1/sync/scores
     * Daftar nilai penilai eksternal yang belum diambil pusat (pending_sync=true).
     * Idempoten: dipanggil berulang mengembalikan hal yang sama sampai di-ack.
     */
    public function index(Request $request)
    {
        $limit = (int) config('external.sync.scores_page_size', 200);

        $visitasi = VisitasiEvaluator::query()->where('pending_sync', true)
            ->orderBy('saved_at')->orderBy('id')->limit($limit + 1)->get();
        $interview = InterviewEvaluator::query()->where('pending_sync', true)
            ->orderBy('saved_at')->orderBy('id')->limit($limit + 1)->get();

        $hasMore = $visitasi->count() > $limit || $interview->count() > $limit;

        $items = [];
        foreach ($visitasi->take($limit) as $r) {
            $items[] = $this->format('visitasi', $r->id_visit, $r);
        }
        foreach ($interview->take($limit) as $r) {
            $items[] = $this->format('interview', $r->id_interview, $r);
        }

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toIso8601String(),
            'has_more' => $hasMore,
            'items' => $items,
        ]);
    }

    /**
     * POST /api/v1/sync/ack
     * Pusat memberi tahu nilai mana yang sudah tersimpan. Hanya baris yang revision-nya sama
     * yang dibersihkan, sehingga edit baru penilai setelah pull tidak ikut hilang.
     */
    public function ack(Request $request)
    {
        $items = $request->json('items');
        if (! is_array($items)) {
            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 422);
        }

        $acked = 0;
        foreach ($items as $item) {
            if (! is_array($item)
                || ! isset($item['type'], $item['parent_id'], $item['id_user'], $item['revision'])
                || ! is_numeric($item['id_user']) || ! is_numeric($item['revision'])) {
                continue;
            }

            if ($item['type'] === 'visitasi') {
                $acked += VisitasiEvaluator::query()
                    ->where('id_visit', (string) $item['parent_id'])
                    ->where('id_user', (int) $item['id_user'])
                    ->where('revision', (int) $item['revision'])
                    ->where('pending_sync', true)
                    ->update(['pending_sync' => false]);
            } elseif ($item['type'] === 'interview') {
                $acked += InterviewEvaluator::query()
                    ->where('id_interview', (int) $item['parent_id'])
                    ->where('id_user', (int) $item['id_user'])
                    ->where('revision', (int) $item['revision'])
                    ->where('pending_sync', true)
                    ->update(['pending_sync' => false]);
            }
        }

        SyncLog::record('ack', true, "acked={$acked} dari ".count($items), $request->ip());

        return response()->json(['ok' => true, 'acked' => $acked]);
    }

    private function format(string $type, mixed $parentId, $row): array
    {
        return [
            'type' => $type,
            'parent_id' => $parentId,
            'id_user' => (int) $row->id_user,
            'score' => $row->score,
            'catatan' => $row->catatan,
            'is_draft' => (bool) $row->is_draft,
            'revision' => (int) $row->revision,
            'saved_at' => $row->saved_at?->toIso8601String(),
        ];
    }
}
