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
        Schema::create('result', function (Blueprint $table) {
            $table->id('id_result');
            $table->string('kode_satker', 50)->nullable();
            $table->unsignedBigInteger('id_periode')->nullable();
            $table->float('ques_score')->nullable();
            $table->float('visit_score')->nullable();
            $table->float('interview_score')->nullable();
            $table->float('final_score')->nullable();
            $table->string('index_label')->nullable();
            $table->dateTime('published_at')->nullable();

            $table->foreign('kode_satker')->references('kode_satker')->on('master_satker')->onDelete('cascade');
            $table->foreign('id_periode')->references('id_periode')->on('periods')->onDelete('cascade');

            $table->index(['kode_satker', 'id_periode']);
            $table->index(['id_periode', 'final_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result');
    }
};
