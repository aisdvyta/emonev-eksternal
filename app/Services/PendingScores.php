<?php

namespace App\Services;

use App\Models\InterviewEvaluator;
use App\Models\VisitasiEvaluator;

/**
 * Nilai penilai yang belum diambil pusat (pending_sync = true). Dipakai API (GET /sync/scores) dan jalur Drive.
 */
class PendingScores
{
    /**
     * @return array{items: array<int, array>, has_more: bool}
     */
    public function collect(?int $limit = null): array
    {
        $limit ??= (int) config('external.sync.scores_page_size', 200);

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

        return ['items' => $items, 'has_more' => $hasMore];
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
