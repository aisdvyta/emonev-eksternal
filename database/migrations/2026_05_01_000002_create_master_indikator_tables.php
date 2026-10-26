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
        Schema::create('master_indikator', function (Blueprint $table) {
            $table->id('id_indicators');
            $table->string('nama_indikator');
            $table->string('deskripsi_indikator')->nullable();
        });

        Schema::create('master_sub_indikator', function (Blueprint $table) {
            $table->id('id_sub_indicators');
            $table->unsignedBigInteger('id_indicators');
            $table->string('nama_sub_indikator');

            $table->foreign('id_indicators')->references('id_indicators')->on('master_indikator')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_sub_indikator');
        Schema::dropIfExists('master_indikator');
    }
};
