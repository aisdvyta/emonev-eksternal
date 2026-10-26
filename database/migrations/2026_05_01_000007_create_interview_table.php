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
        Schema::create('interview', function (Blueprint $table) {
            $table->id('id_interview');
            $table->unsignedBigInteger('id_result')->nullable();
            $table->string('meets_link')->nullable();
            $table->date('interview_at')->nullable();
            $table->float('interview_score')->nullable();
            $table->json('interview_data')->nullable();

            $table->foreign('id_result')->references('id_result')->on('result')->onDelete('cascade');

            $table->index('interview_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview');
    }
};
