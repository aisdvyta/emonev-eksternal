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
        Schema::create('sk_sertifikat', function (Blueprint $table) {
            $table->id('id_sk_sertifikat');
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('id_periode')->nullable();
            $table->unsignedBigInteger('id_submission')->nullable();
            $table->foreign('id_periode')->references('id_periode')->on('periods')->onDelete('cascade');
            $table->foreign('id_submission')->references('id_submission')->on('submissions')->onDelete('cascade');

            $table->index(['id_periode', 'id_submission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk_sertifikat');
    }
};
