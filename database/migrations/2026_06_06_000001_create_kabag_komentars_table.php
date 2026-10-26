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
        Schema::create('kabag_komentars', function (Blueprint $table) {
            $table->id('id_komentar_kabag');
            $table->unsignedBigInteger('id_periode');
            $table->unsignedBigInteger('id_user');
            $table->text('komentar');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_periode')->references('id_periode')->on('periods')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kabag_komentars');
    }
};
