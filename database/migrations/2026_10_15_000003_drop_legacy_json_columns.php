<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop legacy JSON columns that have been migrated to dedicated relational tables:
     * - submissions.evaluated_by -> submission_evaluators
     * - interview.interview_data -> interview_evaluators
     * - visitasi.visit_data      -> visitasi_evaluators
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'evaluated_by')) {
                $table->dropColumn('evaluated_by');
            }
        });

        Schema::table('interview', function (Blueprint $table) {
            if (Schema::hasColumn('interview', 'interview_data')) {
                $table->dropColumn('interview_data');
            }
        });

        Schema::table('visitasi', function (Blueprint $table) {
            if (Schema::hasColumn('visitasi', 'visit_data')) {
                $table->dropColumn('visit_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'evaluated_by')) {
                $table->json('evaluated_by')->nullable();
            }
        });

        Schema::table('interview', function (Blueprint $table) {
            if (!Schema::hasColumn('interview', 'interview_data')) {
                $table->json('interview_data')->nullable();
            }
        });

        Schema::table('visitasi', function (Blueprint $table) {
            if (!Schema::hasColumn('visitasi', 'visit_data')) {
                $table->json('visit_data')->nullable();
            }
        });
    }
};
