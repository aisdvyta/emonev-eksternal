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
        // 1. Rename meets_link -> link_meet_interview pada tabel interview
        if (Schema::hasTable('interview') && Schema::hasColumn('interview', 'meets_link')) {
            Schema::table('interview', function (Blueprint $table) {
                $table->renameColumn('meets_link', 'link_meet_interview');
            });
        }

        // 2. Rename link_meeting -> link_meet_visitasi pada tabel visitasi
        if (Schema::hasTable('visitasi') && Schema::hasColumn('visitasi', 'link_meeting')) {
            Schema::table('visitasi', function (Blueprint $table) {
                $table->renameColumn('link_meeting', 'link_meet_visitasi');
            });
        }

        // 3. Rename scores -> bobot_pertanyaan pada tabel master_pertanyaan
        if (Schema::hasTable('master_pertanyaan') && Schema::hasColumn('master_pertanyaan', 'scores')) {
            Schema::table('master_pertanyaan', function (Blueprint $table) {
                $table->renameColumn('scores', 'bobot_pertanyaan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('interview') && Schema::hasColumn('interview', 'link_meet_interview')) {
            Schema::table('interview', function (Blueprint $table) {
                $table->renameColumn('link_meet_interview', 'meets_link');
            });
        }

        if (Schema::hasTable('visitasi') && Schema::hasColumn('visitasi', 'link_meet_visitasi')) {
            Schema::table('visitasi', function (Blueprint $table) {
                $table->renameColumn('link_meet_visitasi', 'link_meeting');
            });
        }

        if (Schema::hasTable('master_pertanyaan') && Schema::hasColumn('master_pertanyaan', 'bobot_pertanyaan')) {
            Schema::table('master_pertanyaan', function (Blueprint $table) {
                $table->renameColumn('bobot_pertanyaan', 'scores');
            });
        }
    }
};
