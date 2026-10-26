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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id('id_submission');
            $table->unsignedBigInteger('id_result')->nullable();
            $table->text('id_operator')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->string('verification_note')->nullable();
            $table->integer('status')->default(0); // 0=draft, 1=submitted, 2=returned, 3=verified, 4=evaluated, 5=visitasi, 6=wawancara_done, 7=completed, 8=gugur
            $table->dateTime('draft_saved_at')->nullable();
            $table->string('interview_status')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->json('evaluated_by')->nullable();
            $table->dateTime('evaluated_at')->nullable();

            $table->foreign('id_result')->references('id_result')->on('result')->onDelete('cascade');
            $table->foreign('verified_by')->references('id_user')->on('users')->onDelete('set null');

            $table->index('status');
            $table->index(['id_result', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
