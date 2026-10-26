<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\InterviewEvaluator;
use App\Models\Submission;
use App\Models\Visitasi;
use App\Models\VisitasiEvaluator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * VERSI SATELIT dari App\Services\EvaluationService (pusat).
 *
 * Tanda tangan metode dan teks pesan SAMA dengan pusat supaya controller yang disalin dari pusat berjalan
 * apa adanya. Bedanya: satelit hanya menyimpan nilai MILIK penilai yang login. Rata-rata, perubahan status,
 * dan finalisasi dilakukan pusat saat nilai diambil (pusat memvalidasi ulang semua aturan di bawah).
 * Penandaan "perlu disinkronkan" (revision/pending_sync) dilakukan oleh model *Evaluator.
 */
class EvaluationService
{
    private const VISIT_NOTE_MAX = 255;

    /**
     * Alasan Visitasi tidak boleh dinilai/diubah sekarang, atau null jika boleh.
     * Urutan pengecekan dan teks pesan identik dengan VisitasiController::saveReview versi lama.
     */
    public function visitasiLockReason(Visitasi $visitasi): ?string
    {
        $visitasi->loadMissing(['result.period', 'result.submission']);
        $submission = $visitasi->submission;

        if (! $submission || $submission->status < 5 || $submission->status == 8) {
            return 'Submission tidak dalam tahap visitasi.';
        }
        if ($submission->status >= 7) {
            return 'Tahapan penilaian akhir untuk submission ini telah selesai dan tidak dapat diubah lagi.';
        }

        if ($visitasi->is_final) {
            return 'Penilaian visitasi ini telah difinalisasi dan tidak dapat diubah lagi.';
        }

        $period = $visitasi->period;
        $visitStart = $period?->visit_start ? Carbon::parse($period->visit_start)->startOfDay() : null;
        if ($visitStart && now()->startOfDay()->lt($visitStart)) {
            return 'Tahapan visitasi belum dimulai. Penilaian baru dapat dilakukan mulai tanggal ' . $visitStart->translatedFormat('d M Y') . '.';
        }

        $visitEnd = $period?->visit_end ? Carbon::parse($period->visit_end)->endOfDay() : null;
        if ($visitEnd && now()->greaterThan($visitEnd)) {
            return 'Batas waktu pengisian/pengeditan nilai visitasi periode ini telah berakhir pada tanggal ' . $visitEnd->translatedFormat('d M Y') . '.';
        }

        $visitMode = $visitasi->visit_mode ?? 'offline';
        if ($visitMode !== 'online_upload') {
            $visitDate = $visitasi->visit_at ? Carbon::parse($visitasi->visit_at)->startOfDay() : null;
            if (! $visitDate || now()->startOfDay()->lt($visitDate)) {
                return 'Penilaian visitasi hanya dapat dilakukan pada atau setelah tanggal kunjungan yang dijadwalkan.';
            }
        }

        return null;
    }

    /**
     * Alasan Wawancara tidak boleh dinilai/diubah sekarang, atau null jika boleh.
     * Urutan pengecekan dan teks pesan identik dengan WawancaraController::saveReview versi lama.
     */
    public function interviewLockReason(Interview $interview): ?string
    {
        $interview->loadMissing(['result.period', 'result.submission']);
        $submission = $interview->submission;

        if (! $submission || $submission->status < 5 || $submission->status == 8) {
            return 'Submission tidak dalam tahap wawancara.';
        }

        if ($submission->status >= 7) {
            return 'Tahapan penilaian akhir untuk submission ini telah selesai dan tidak dapat diubah lagi.';
        }

        if ($interview->is_final) {
            return 'Penilaian wawancara ini telah difinalisasi dan tidak dapat diubah lagi.';
        }

        $period = $interview->period;
        $interviewStart = $period?->interview_start ? Carbon::parse($period->interview_start)->startOfDay() : null;
        if ($interviewStart && now()->startOfDay()->lt($interviewStart)) {
            return 'Tahapan wawancara belum dimulai. Penilaian baru dapat dilakukan mulai tanggal ' . $interviewStart->translatedFormat('d M Y') . '.';
        }

        $interviewEnd = $period?->interview_end ? Carbon::parse($period->interview_end)->endOfDay() : null;
        if ($interviewEnd && now()->greaterThan($interviewEnd)) {
            return 'Batas waktu pengisian/pengeditan nilai wawancara periode ini telah berakhir pada tanggal ' . $interviewEnd->translatedFormat('d M Y') . '.';
        }

        $interviewDate = $interview->interview_at ? Carbon::parse($interview->interview_at)->startOfDay() : null;
        if (! $interviewDate || now()->startOfDay()->lt($interviewDate)) {
            return 'Penilaian hanya dapat dilakukan pada atau setelah jadwal wawancara.';
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Visitasi
    // ------------------------------------------------------------------

    public function saveVisitasiScore(string $visitId, int $userId, float $score, ?string $note, bool $isDraft, bool $submit): EvaluationResult
    {
        return DB::transaction(function () use ($visitId, $userId, $score, $note, $isDraft, $submit) {
            $visitasi = Visitasi::with(['result.period', 'result.submission'])->lockForUpdate()->findOrFail($visitId);

            if ($reason = $this->visitasiLockReason($visitasi)) {
                return EvaluationResult::rejected($reason);
            }

            $row = VisitasiEvaluator::where('id_visit', $visitasi->id_visit)->where('id_user', $userId)->first();
            if (! $row) {
                return EvaluationResult::rejected('Anda tidak ditugaskan sebagai verifikator untuk visitasi ini.');
            }

            $row->fill([
                'score' => $score,
                'catatan' => $note === null ? null : mb_substr($note, 0, 5000),
                'is_draft' => $isDraft,
            ])->save();

            if ($submit) {
                return EvaluationResult::ok('Penilaian Anda berhasil disubmit dan akan diteruskan ke pusat.');
            }

            return EvaluationResult::ok('Draft hasil visitasi berhasil disimpan.');
        });
    }

    // ------------------------------------------------------------------
    // Wawancara
    // ------------------------------------------------------------------

    public function saveInterviewScore(int $interviewId, int $userId, ?float $score, ?string $note, bool $isDraft, bool $submit): EvaluationResult
    {
        return DB::transaction(function () use ($interviewId, $userId, $score, $note, $isDraft, $submit) {
            $interview = Interview::with(['result.period', 'result.submission'])->lockForUpdate()->findOrFail($interviewId);

            if ($reason = $this->interviewLockReason($interview)) {
                return EvaluationResult::rejected($reason);
            }

            $row = InterviewEvaluator::where('id_interview', $interview->id_interview)->where('id_user', $userId)->first();
            if (! $row) {
                return EvaluationResult::rejected('Anda tidak ditugaskan sebagai pewawancara untuk satker ini.');
            }

            $isDraft = $isDraft || $score === null;

            if (! $isDraft) {
                if ($score < 0 || $score > 100) {
                    return EvaluationResult::rejected('Nilai wawancara harus berupa angka 0-100.');
                }
                if ($note === null || trim($note) === '') {
                    return EvaluationResult::rejected('Catatan wawancara wajib diisi sebelum mengirim penilaian.');
                }
            }

            $row->fill([
                'score' => $score,
                'catatan' => $note === null ? null : mb_substr($note, 0, 5000),
                'is_draft' => $isDraft,
            ])->save();

            return $this->finalizeInterview($interview, $interview->submission, [], $submit);
        });
    }

    /**
     * Di pusat metode ini menghitung rata-rata & memfinalisasi. Di satelit tidak ada yang dihitung:
     * nilai sudah tersimpan oleh pemanggil dan akan divalidasi + difinalisasi pusat saat sinkronisasi.
     *
     * @param  array<int, mixed>  $data  tidak dipakai di satelit (tanda tangan disamakan dengan pusat)
     */
    public function finalizeInterview(Interview $interview, Submission $submission, array $data, bool $finalizeRequested): EvaluationResult
    {
        if ($finalizeRequested) {
            return EvaluationResult::ok('Penilaian berhasil disubmit dan akan diteruskan ke pusat.');
        }

        return EvaluationResult::ok('Draft penilaian wawancara berhasil disimpan.');
    }
}
