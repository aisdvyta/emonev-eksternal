<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel pivot penugasan evaluator SAQ
        if (!Schema::hasTable('submission_evaluators')) {
            Schema::create('submission_evaluators', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_submission');
                $table->unsignedBigInteger('id_user');

                $table->foreign('id_submission')->references('id_submission')->on('submissions')->onDelete('cascade');
                $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');

                $table->unique(['id_submission', 'id_user']);
                $table->index('id_user');
            });
        }

        // 2. Tabel relasi penilaian per evaluator sesi wawancara
        if (!Schema::hasTable('interview_evaluators')) {
            Schema::create('interview_evaluators', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_interview');
                $table->unsignedBigInteger('id_user');
                $table->decimal('score', 6, 2)->nullable();
                $table->text('catatan')->nullable();
                $table->boolean('is_draft')->default(true);

                $table->foreign('id_interview')->references('id_interview')->on('interview')->onDelete('cascade');
                $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');

                $table->unique(['id_interview', 'id_user']);
                $table->index('id_user');
                $table->index(['id_interview', 'is_draft']);
            });
        }

        // 3. Tabel relasi penilaian per evaluator sesi visitasi
        if (!Schema::hasTable('visitasi_evaluators')) {
            Schema::create('visitasi_evaluators', function (Blueprint $table) {
                $table->id();
                $table->uuid('id_visit');
                $table->unsignedBigInteger('id_user');
                $table->decimal('score', 6, 2)->nullable();
                $table->text('catatan')->nullable();
                $table->boolean('is_draft')->default(true);

                $table->foreign('id_visit')->references('id_visit')->on('visitasi')->onDelete('cascade');
                $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');

                $table->unique(['id_visit', 'id_user']);
                $table->index('id_user');
                $table->index(['id_visit', 'is_draft']);
            });
        }

        // 4. Tabel pengaturan link resmi per periode kegiatan
        if (!Schema::hasTable('master_link')) {
            Schema::create('master_link', function (Blueprint $table) {
                $table->id('id_link');
                $table->unsignedBigInteger('id_periode')->unique();
                $table->string('link_panduan', 500)->nullable();
                $table->string('link_drive_review_saq', 500)->nullable();

                $table->foreign('id_periode')->references('id_periode')->on('periods')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_link');
        Schema::dropIfExists('visitasi_evaluators');
        Schema::dropIfExists('interview_evaluators');
        Schema::dropIfExists('submission_evaluators');
    }
};
