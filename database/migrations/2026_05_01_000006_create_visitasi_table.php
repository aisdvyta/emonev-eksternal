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
        Schema::create('visitasi', function (Blueprint $table) {
            $table->uuid('id_visit')->primary();
            $table->unsignedBigInteger('id_result')->nullable();
            $table->string('visit_mode', 30)->nullable();
            $table->string('link_meeting')->nullable();
            $table->date('visit_at')->nullable();
            $table->float('visit_score')->nullable();
            $table->string('visit_note')->nullable();
            $table->string('link_buktidukung')->nullable();
            $table->json('visit_data')->nullable();

            $table->foreign('id_result')->references('id_result')->on('result')->onDelete('cascade');

            $table->index('visit_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitasi');
    }
};
