<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema satelit penilai eksternal.
 *
 * Prinsip:
 *  - Semua primary key tabel hasil sinkronisasi memakai ID dari sistem pusat (bukan auto increment),
 *    supaya nilai yang dikirim balik bisa dipetakan 1:1.
 *  - Sengaja TANPA foreign key antar tabel sinkronisasi: urutan kedatangan data dari pusat tidak dijamin
 *    dan pemangkasan (prune) tidak boleh gagal karena relasi.
 *  - Nilai/catatan evaluator lain TIDAK pernah ada di sini. Hanya baris milik penilai eksternal itu sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user')->primary();
            $table->string('nama_user', 191)->nullable()->unique();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('periods', function (Blueprint $table) {
            $table->unsignedBigInteger('id_periode')->primary();
            $table->date('periode_start')->nullable();
            $table->date('periode_end')->nullable();
            $table->date('saq_start')->nullable();
            $table->date('saq_end')->nullable();
            $table->date('eval_start')->nullable();
            $table->date('eval_end')->nullable();
            $table->date('visit_start')->nullable();
            $table->date('visit_end')->nullable();
            $table->date('interview_start')->nullable();
            $table->date('interview_end')->nullable();
            $table->date('result_start')->nullable();
            $table->date('result_end')->nullable();
        });

        Schema::create('master_satker', function (Blueprint $table) {
            $table->string('kode_satker', 50)->primary();
            $table->string('nama_satker')->nullable();
        });

        Schema::create('master_indikator', function (Blueprint $table) {
            $table->unsignedBigInteger('id_indicators')->primary();
            $table->string('nama_indikator');
            $table->string('deskripsi_indikator')->nullable();
        });

        Schema::create('master_sub_indikator', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sub_indicators')->primary();
            $table->unsignedBigInteger('id_indicators')->index();
            $table->string('nama_sub_indikator');
        });

        Schema::create('master_pertanyaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_master_pertanyaan')->primary();
            $table->integer('order_no')->nullable();
            $table->unsignedBigInteger('id_sub_indicators')->nullable()->index();
            $table->unsignedBigInteger('id_indicators')->nullable()->index();
            $table->text('ques_text')->nullable();
            $table->text('ques_description')->nullable();
            $table->text('doc_requirement')->nullable();
            $table->text('link_drive')->nullable();
            $table->text('link_ppid')->nullable();
            $table->float('bobot_pertanyaan')->nullable();
        });

        Schema::create('result', function (Blueprint $table) {
            $table->unsignedBigInteger('id_result')->primary();
            $table->string('kode_satker', 50)->nullable()->index();
            $table->unsignedBigInteger('id_periode')->nullable()->index();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('id_submission')->primary();
            $table->unsignedBigInteger('id_result')->nullable()->index();
            // 0=draft,1=submitted,2=returned,3=verified,4=evaluated,5=visitasi,6=wawancara_done,7=completed,8=gugur
            $table->integer('status')->default(0);
        });

        // Jawaban satker (untuk tampilan SAQ hanya-lihat). TANPA skor/catatan evaluator.
        Schema::create('answer', function (Blueprint $table) {
            $table->uuid('id_answer')->primary();
            $table->unsignedBigInteger('id_submission')->index();
            $table->unsignedBigInteger('id_question');
            $table->unsignedBigInteger('id_indicators')->nullable();
            $table->boolean('answer')->nullable();
            $table->text('link_ppid')->nullable();
            $table->text('link_drive')->nullable();
            $table->unique(['id_submission', 'id_question']);
        });

        Schema::create('submission_evaluators', function (Blueprint $table) {
            $table->unsignedBigInteger('id_submission');
            $table->unsignedBigInteger('id_user');
            $table->primary(['id_submission', 'id_user']);
            $table->index('id_user');
        });

        Schema::create('visitasi', function (Blueprint $table) {
            $table->uuid('id_visit')->primary();
            $table->unsignedBigInteger('id_result')->nullable()->index();
            $table->string('visit_mode', 30)->nullable();
            $table->string('link_meet_visitasi')->nullable();
            $table->date('visit_at')->nullable();
            $table->string('link_buktidukung')->nullable();
            // true = sudah difinalisasi di pusat; satelit tidak boleh mengubah lagi.
            $table->boolean('is_final')->default(false);
        });

        Schema::create('interview', function (Blueprint $table) {
            $table->unsignedBigInteger('id_interview')->primary();
            $table->unsignedBigInteger('id_result')->nullable()->index();
            $table->string('link_meet_interview')->nullable();
            $table->date('interview_at')->nullable();
            $table->boolean('is_final')->default(false);
        });

        Schema::create('visitasi_evaluators', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_visit');
            $table->unsignedBigInteger('id_user');
            $table->decimal('score', 6, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_draft')->default(true);
            // Dinaikkan setiap penyimpanan lokal. Dipakai untuk ack yang aman dari balapan (race).
            $table->unsignedInteger('revision')->default(0);
            // true = ada perubahan lokal yang belum diambil/di-ack pusat.
            $table->boolean('pending_sync')->default(false)->index();
            $table->timestamp('saved_at')->nullable();
            $table->unique(['id_visit', 'id_user']);
            $table->index('id_user');
        });

        Schema::create('interview_evaluators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_interview');
            $table->unsignedBigInteger('id_user');
            $table->decimal('score', 6, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->unsignedInteger('revision')->default(0);
            $table->boolean('pending_sync')->default(false)->index();
            $table->timestamp('saved_at')->nullable();
            $table->unique(['id_interview', 'id_user']);
            $table->index('id_user');
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 40);
            $table->boolean('ok')->default(true);
            $table->text('summary')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        foreach ([
            'sync_logs', 'interview_evaluators', 'visitasi_evaluators', 'interview', 'visitasi',
            'submission_evaluators', 'answer', 'submissions', 'result', 'master_pertanyaan',
            'master_sub_indikator', 'master_indikator', 'master_satker', 'periods', 'users',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
