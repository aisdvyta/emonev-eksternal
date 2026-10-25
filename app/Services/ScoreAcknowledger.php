<?php

namespace App\Services;

use App\Models\InterviewEvaluator;
use App\Models\VisitasiEvaluator;

/**
 * Pusat memberi tahu nilai mana yang sudah tersimpan. Hanya baris yang revision-nya sama yang dibersihkan,
 * sehingga edit baru penilai setelah pull tidak ikut hilang.
 */
class ScoreAcknowledger
{
    /** @param  array<int, mixed>  $items  @return int jumlah baris yang dibersihkan */
    public function acknowledge(array $items): int
    {
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

        return $acked;
    }
}
