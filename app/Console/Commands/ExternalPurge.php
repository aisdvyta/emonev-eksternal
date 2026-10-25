<?php

namespace App\Console\Commands;

use App\Models\InterviewEvaluator;
use App\Models\VisitasiEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus data satker dari satelit setelah periode selesai (minimisasi data di hosting publik).
 * Akun penilai (users) TIDAK dihapus. Menolak berjalan bila masih ada nilai yang belum diambil pusat.
 */
class ExternalPurge extends Command
{
    protected $signature = 'external:purge
        {id_periode? : Hapus hanya data periode ini. Kosong = semua periode}
        {--force : Tetap hapus walau masih ada nilai belum tersinkron (nilai itu AKAN HILANG)}
        {--yes : Lewati konfirmasi}';

    protected $description = 'Menghapus data satker/penilaian dari satelit setelah periode selesai dan nilai sudah diambil pusat.';

    public function handle(): int
    {
        $pending = VisitasiEvaluator::where('pending_sync', true)->count()
            + InterviewEvaluator::where('pending_sync', true)->count();

        if ($pending > 0 && ! $this->option('force')) {
            $this->error("Masih ada {$pending} nilai yang belum diambil sistem pusat. Selesaikan sinkronisasi dulu (atau pakai --force bila sengaja).");

            return self::FAILURE;
        }

        $periodId = $this->argument('id_periode');
        $label = $periodId ? "periode {$periodId}" : 'SEMUA periode';

        if (! $this->option('yes') && ! $this->confirm("Hapus data satker & penilaian untuk {$label}?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($periodId) {
            $resultIds = DB::table('result')
                ->when($periodId, fn ($q) => $q->where('id_periode', (int) $periodId))
                ->pluck('id_result')->all();

            foreach (array_chunk($resultIds, 500) as $chunk) {
                $subIds = DB::table('submissions')->whereIn('id_result', $chunk)->pluck('id_submission')->all();
                $visitIds = DB::table('visitasi')->whereIn('id_result', $chunk)->pluck('id_visit')->all();
                $interviewIds = DB::table('interview')->whereIn('id_result', $chunk)->pluck('id_interview')->all();

                DB::table('answer')->whereIn('id_submission', $subIds)->delete();
                DB::table('submission_evaluators')->whereIn('id_submission', $subIds)->delete();
                DB::table('visitasi_evaluators')->whereIn('id_visit', $visitIds)->delete();
                DB::table('interview_evaluators')->whereIn('id_interview', $interviewIds)->delete();
                DB::table('submissions')->whereIn('id_submission', $subIds)->delete();
                DB::table('visitasi')->whereIn('id_visit', $visitIds)->delete();
                DB::table('interview')->whereIn('id_interview', $interviewIds)->delete();
                DB::table('result')->whereIn('id_result', $chunk)->delete();
            }
        });

        $this->info("Data {$label} dihapus.");

        return self::SUCCESS;
    }
}
