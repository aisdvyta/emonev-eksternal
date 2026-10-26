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
        Schema::create('master_pembobotan', function (Blueprint $table) {
            $table->id('id_pembobotan');
            $table->unsignedBigInteger('id_periode')->nullable()->unique();
            $table->decimal('weight_saq', 5, 2)->default(30.00);
            $table->decimal('weight_visitasi', 5, 2)->default(40.00);
            $table->decimal('weight_wawancara', 5, 2)->default(30.00);
            $table->decimal('threshold_saq', 5, 2)->default(80.00);
            $table->text('bobot_indikator')->nullable();
            $table->json('stage_order')->nullable();

            $table->foreign('id_periode')->references('id_periode')->on('periods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pembobotan');
    }
};
