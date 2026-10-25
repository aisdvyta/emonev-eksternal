<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\Visitasi;

/**
 * Aturan kapan penilai eksternal boleh mengisi nilai. Mencerminkan aturan di sistem pusat
 * (VisitasiController::saveReview dan WawancaraController::saveReview). Pusat TETAP memvalidasi
 * ulang saat nilai diambil, jadi aturan di sini adalah pagar kenyamanan, bukan satu-satunya pagar.
 *
 * Mengembalikan null bila boleh, atau pesan alasan bila terkunci.
 */
class EvaluationRules
{
    public function visitasiLockReason(Visitasi $visitasi): ?string
    {
        $visitasi->loadMissing('result.submission', 'result.period');
        $submission = $visitasi->result?->submission;
        $period = $visitasi->result?->period;

        if (! $submission || $submission->status < 5 || $submission->status == 8) {
            return 'Submission tidak dalam tahap visitasi.';
        }
        if ($submission->status >= 7) {
            return 'Tahapan penilaian akhir untuk submission ini telah selesai dan tidak dapat diubah lagi.';
        }
        if ($visitasi->is_final) {
            return 'Penilaian visitasi ini telah difinalisasi dan tidak dapat diubah lagi.';
        }

        $today = today();
        if ($period?->visit_start && $today->lt($period->visit_start->copy()->startOfDay())) {
            return 'Tahapan visitasi belum dimulai. Penilaian baru dapat dilakukan mulai tanggal '
                .$period->visit_start->translatedFormat('d M Y').'.';
        }
        if ($period?->visit_end && $today->gt($period->visit_end->copy()->startOfDay())) {
            return 'Batas waktu pengisian/pengeditan nilai visitasi periode ini telah berakhir pada tanggal '
                .$period->visit_end->translatedFormat('d M Y').'.';
        }

        if (($visitasi->visit_mode ?? 'offline') !== 'online_upload') {
            if (! $visitasi->visit_at || $today->lt($visitasi->visit_at->copy()->startOfDay())) {
                return 'Penilaian visitasi hanya dapat dilakukan pada atau setelah tanggal kunjungan yang dijadwalkan.';
            }
        }

        return null;
    }

    public function interviewLockReason(Interview $interview): ?string
    {
        $interview->loadMissing('result.submission', 'result.period');
        $submission = $interview->result?->submission;
        $period = $interview->result?->period;

        if (! $submission || $submission->status < 5 || $submission->status == 8) {
            return 'Submission tidak dalam tahap wawancara.';
        }
        if ($submission->status >= 7) {
            return 'Tahapan penilaian akhir untuk submission ini telah selesai dan tidak dapat diubah lagi.';
        }
        if ($interview->is_final) {
            return 'Penilaian wawancara ini telah difinalisasi dan tidak dapat diubah lagi.';
        }

        $today = today();
        if ($period?->interview_start && $today->lt($period->interview_start->copy()->startOfDay())) {
            return 'Tahapan wawancara belum dimulai. Penilaian baru dapat dilakukan mulai tanggal '
                .$period->interview_start->translatedFormat('d M Y').'.';
        }
        if ($period?->interview_end && $today->gt($period->interview_end->copy()->startOfDay())) {
            return 'Batas waktu pengisian/pengeditan nilai wawancara periode ini telah berakhir pada tanggal '
                .$period->interview_end->translatedFormat('d M Y').'.';
        }
        if (! $interview->interview_at || $today->lt($interview->interview_at->copy()->startOfDay())) {
            return 'Penilaian hanya dapat dilakukan pada atau setelah jadwal wawancara.';
        }

        return null;
    }
}
