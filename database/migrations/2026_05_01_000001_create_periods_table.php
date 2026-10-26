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
        Schema::create('periods', function (Blueprint $table) {
            $table->id('id_periode');
            $table->date('periode_start')->nullable();
            $table->date('periode_end')->nullable();
            $table->date('saq_start')->nullable();
            $table->date('saq_end')->nullable();
            $table->date('eval_start')->nullable();
            $table->date('eval_end')->nullable();
            $table->date('visit_start')->nullable();
            $table->date('visit_end')->nullable();
            $table->date('interview_start')->nullable();
            $table->date('interview_end')->nullable();
            $table->date('result_start')->nullable();
            $table->date('result_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
