<?php

namespace App\Services;

use App\Models\InterviewEvaluator;
use App\Models\VisitasiEvaluator;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan nilai MILIK penilai yang sedang login. Satelit tidak pernah menghitung rata-rata,
 * mengubah status, atau memfinalisasi apa pun: itu tugas sistem pusat saat nilai diambil.
 *
 * Setiap simpan menaikkan revision dan menandai pending_sync, supaya pusat tahu ada yang perlu diambil
 * dan ack-nya aman dari balapan (edit baru setelah pull tidak ikut ter-ack).
 */
class ScoreRecorder
{
    public function saveVisitasi(string $idVisit, int $userId, ?float $score, ?string $catatan, bool $isDraft): VisitasiEvaluator
    {
        return $this->save(VisitasiEvaluator::class, ['id_visit' => $idVisit, 'id_user' => $userId], $score, $catatan, $isDraft);
    }

    public function saveInterview(int $idInterview, int $userId, ?float $score, ?string $catatan, bool $isDraft): InterviewEvaluator
    {
        return $this->save(InterviewEvaluator::class, ['id_interview' => $idInterview, 'id_user' => $userId], $score, $catatan, $isDraft);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function save(string $model, array $where, ?float $score, ?string $catatan, bool $isDraft)
    {
        return DB::transaction(function () use ($model, $where, $score, $catatan, $isDraft) {
            $row = $model::query()->where($where)->lockForUpdate()->firstOrFail();

            $row->forceFill([
                'score' => $score,
                'catatan' => $catatan,
                'is_draft' => $isDraft,
                'revision' => ((int) $row->revision) + 1,
                'pending_sync' => true,
                'saved_at' => now(),
            ])->save();

            return $row;
        });
    }
}
