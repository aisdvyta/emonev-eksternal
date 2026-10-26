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
        Schema::create('answer', function (Blueprint $table) {
            $table->uuid('id_answer')->primary();
            $table->unsignedBigInteger('id_question')->nullable();
            $table->unsignedBigInteger('id_submission')->nullable();
            $table->unsignedBigInteger('id_indicators')->nullable();
            $table->boolean('answer')->nullable();
            $table->text('link_ppid')->nullable();
            $table->text('link_drive')->nullable();
            $table->float('answer_score')->nullable();
            $table->string('evaluator_note')->nullable();
            $table->text('verifikator_note')->nullable();
            $table->dateTime('evaluator_at')->nullable();

            $table->foreign('id_question')->references('id_master_pertanyaan')->on('master_pertanyaan')->onDelete('cascade');
            $table->foreign('id_submission')->references('id_submission')->on('submissions')->onDelete('cascade');
            $table->foreign('id_indicators')->references('id_indicators')->on('master_indikator')->onDelete('cascade');

            $table->unique(['id_submission', 'id_question']);
            $table->index(['id_submission', 'id_indicators', 'answer_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer');
    }
};
