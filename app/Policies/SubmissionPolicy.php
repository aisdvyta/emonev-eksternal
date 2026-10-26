<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat data Submission.
     */
    public function view(User $user, Submission $submission): bool
    {
        // Admin, Super Evaluator, dan Evaluator memiliki akses ke seluruh satker
        // Catatan: role 'kabag' telah dihapus; user kabag kini ber-role super_evaluator
        if ($user->hasAnyRole(['admin', 'super_evaluator', 'evaluator'])) {
            return true;
        }

        // Operator dan Verifikator hanya boleh melihat data satkernya sendiri
        return $submission->result && (string) $submission->result->kode_satker === (string) $user->kode_satker;
    }

    /**
     * Tentukan apakah pengguna dapat mengedit/memodifikasi data Submission.
     */
    public function update(User $user, Submission $submission): bool
    {
        // Admin, Super Evaluator, dan Evaluator memiliki akses update penuh
        if ($user->hasAnyRole(['admin', 'super_evaluator', 'evaluator'])) {
            return true;
        }

        // Operator dan Verifikator hanya boleh mengupdate data satkernya sendiri
        return $submission->result && (string) $submission->result->kode_satker === (string) $user->kode_satker;
    }

    /**
     * Tentukan apakah pengguna dapat mendownload sertifikat/SK milik Submission.
     */
    public function downloadSertifikat(User $user, Submission $submission): bool
    {
        if ($user->hasAnyRole(['admin', 'super_evaluator', 'evaluator'])) {
            return true;
        }

        return $submission->result && (string) $submission->result->kode_satker === (string) $user->kode_satker;
    }

    /**
     * Tentukan apakah pengguna dapat mereview atau mengekspor kuesioner SAQ milik Submission.
     */
    public function review(User $user, Submission $submission): bool
    {
        // Kuesioner harus sudah lolos verifikasi (status >= 3)
        if ($submission->status < 3) {
            return false;
        }

        // Admin dan Super Evaluator memiliki akses review penuh
        if ($user->hasAnyRole(['admin', 'super_evaluator'])) {
            return true;
        }

        // Evaluator dan Penilai Eksternal hanya dapat mereview kuesioner yang ditugaskan kepadanya
        $evaluators = $submission->relationLoaded('evaluators')
            ? $submission->evaluators
            : $submission->evaluators()->get();

        return $evaluators
            ->pluck('id_user')
            ->map(fn ($id) => (int) $id)
            ->contains((int) $user->id_user);
    }
}
