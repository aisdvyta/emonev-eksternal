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
        Schema::create('master_satker', function (Blueprint $table) {
            $table->string('kode_satker', 50)->primary();
            $table->string('nama_satker')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('kode_satker', 50)->nullable();
            $table->string('nama_user')->unique();
            $table->string('name')->nullable();
            $table->string('instansi')->nullable();
            $table->boolean('is_eksternal')->default(false);
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('nip_pendek')->nullable();
            $table->bigInteger('nip_panjang')->nullable();
            $table->string('profile_pic')->nullable();
            $table->string('foto_url')->nullable();
            $table->rememberToken();
            $table->softDeletes();

            $table->foreign('kode_satker')->references('kode_satker')->on('master_satker')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('master_satker');
    }
};
