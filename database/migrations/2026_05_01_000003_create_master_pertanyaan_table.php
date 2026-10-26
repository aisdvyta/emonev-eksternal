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
        Schema::create('master_pertanyaan', function (Blueprint $table) {
            $table->id('id_master_pertanyaan');
            $table->integer('order_no')->nullable();
            $table->unsignedBigInteger('id_sub_indicators')->nullable();
            $table->unsignedBigInteger('id_indicators')->nullable();
            $table->text('ques_text')->nullable();
            $table->text('ques_description')->nullable();
            $table->text('doc_requirement')->nullable();
            $table->text('link_drive')->nullable();
            $table->text('link_ppid')->nullable();
            $table->float('scores')->nullable();

            $table->foreign('id_sub_indicators')->references('id_sub_indicators')->on('master_sub_indikator')->onDelete('set null');
            $table->foreign('id_indicators')->references('id_indicators')->on('master_indikator')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pertanyaan');
    }
};
