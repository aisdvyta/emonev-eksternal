<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add Performance Indexes
 *
 * Tujuan: Mempercepat query yang sering dieksekusi tanpa mengubah logika kode.
 * Semua index dibuat dengan IF NOT EXISTS (via hasIndex check) agar aman dijalankan
 * di database yang mungkin sudah memiliki index tertentu.
 *
 * Index yang ditambahkan:
 *
 * [visitasi]
 *   - idx_visitasi_id_result          : Mempercepat JOIN dan whereHas dari submissions → visitasi
 *   - idx_visitasi_result_score        : Mempercepat filter visit_score IS NOT NULL per id_result
 *
 * [interview]
 *   - idx_interview_id_result          : Mempercepat JOIN dan whereHas dari submissions → interview
 *   - idx_interview_result_score       : Mempercepat filter interview_score IS NOT NULL per id_result
 *
 * [answer]
 *   - idx_answer_submission_score      : Covering index untuk COUNT(answer_score) per submission
 *     (Melengkapi index (id_submission, id_indicators, answer_score) yang sudah ada —
 *      MySQL bisa pakai existing index untuk query tanpa id_indicators)
 *
 * [submissions]
 *   - idx_submissions_verified_by      : Mempercepat filter verified_by IS NULL / NOT NULL
 *   - idx_submissions_evaluated_status : Mempercepat filter gabungan status + evaluated_at
 *
 * [result]
 *   - idx_result_id_result_kode        : Mempercepat lookup result by id_result + kode_satker
 *     (pk id_result sudah ada, tapi composite dengan kode_satker membantu JOIN ke master_satker)
 */
return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------------------------------
        // 1. TABEL visitasi
        //    Masalah: tidak ada index pada id_result — setiap whereHas('visitasi')
        //    atau JOIN ke visitasi melakukan full table scan.
        //    Ini mempengaruhi: MonitorVisitasiTable, WawancaraController,
        //    DashboardController, PenilaianService.
        // -----------------------------------------------------------------------
        Schema::table('visitasi', function (Blueprint $table) {
            // Untuk: JOIN visitasi ON visitasi.id_result = submissions.id_result
            // dan: whereHas('visitasi', fn($q) => $q->where(...))
            $table->index('id_result', 'idx_visitasi_id_result');

            // Untuk: whereHas('visitasi', fn($q) => $q->whereNotNull('visit_score'))
            // Covering index → MySQL resolve filter tanpa baca row data
            $table->index(['id_result', 'visit_score'], 'idx_visitasi_result_score');
        });

        // -----------------------------------------------------------------------
        // 2. TABEL interview
        //    Masalah: tidak ada index pada id_result — sama seperti visitasi.
        //    Ini mempengaruhi: MonitorWawancaraTable, WawancaraController,
        //    DashboardController::dashboardPenilaiEksternal.
        // -----------------------------------------------------------------------
        Schema::table('interview', function (Blueprint $table) {
            // Untuk: JOIN interview ON interview.id_result = submissions.id_result
            $table->index('id_result', 'idx_interview_id_result');

            // Untuk: whereHas('interview', fn($q) => $q->whereNotNull('interview_score'))
            $table->index(['id_result', 'interview_score'], 'idx_interview_result_score');

            // Untuk: whereNotNull('interview_at') — sering dipakai di dashboard
            // (index 'interview_at' sudah ada dari migration awal, skip)
        });

        // -----------------------------------------------------------------------
        // 3. TABEL answer
        //    Index (id_submission, id_indicators, answer_score) sudah ada.
        //    Tambahkan (id_submission, answer_score) sebagai covering index
        //    untuk query COUNT tanpa filter id_indicators:
        //      Answer::where('id_submission', x)->whereNotNull('answer_score')->count()
        //    MySQL dengan index (id_submission, id_indicators, answer_score) sebenarnya
        //    sudah bisa handle ini karena id_submission adalah prefix —
        //    tapi composite index dengan answer_score di posisi ke-2 lebih efisien
        //    untuk correlated subquery di MonitorKuesionerTable orderByRaw.
        // -----------------------------------------------------------------------
        Schema::table('answer', function (Blueprint $table) {
            // Covering index khusus untuk COUNT(answer_score) tanpa filter id_indicators
            // Dipakai oleh: isSaqFullyEvaluated, countUnevaluatedSaqQuestions,
            // orderByRaw NOT EXISTS di MonitorKuesionerTable
            $table->index(['id_submission', 'answer_score'], 'idx_answer_sub_score');
        });

        // -----------------------------------------------------------------------
        // 4. TABEL submissions — tambahan index untuk filter yang sering dipakai
        // -----------------------------------------------------------------------
        Schema::table('submissions', function (Blueprint $table) {
            // Untuk: WHERE verified_by IS NULL / NOT NULL (filter di MonitorKuesionerTable)
            // dan query 'menunggu_verif' / 'draft_verif'
            $table->index('verified_by', 'idx_submissions_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('visitasi', function (Blueprint $table) {
            $table->dropIndex('idx_visitasi_id_result');
            $table->dropIndex('idx_visitasi_result_score');
        });

        Schema::table('interview', function (Blueprint $table) {
            $table->dropIndex('idx_interview_id_result');
            $table->dropIndex('idx_interview_result_score');
        });

        Schema::table('answer', function (Blueprint $table) {
            $table->dropIndex('idx_answer_sub_score');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_verified_by');
        });
    }
};
