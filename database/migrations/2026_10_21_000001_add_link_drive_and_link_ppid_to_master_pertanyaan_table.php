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
        Schema::table('master_pertanyaan', function (Blueprint $table) {
            if (!Schema::hasColumn('master_pertanyaan', 'link_drive')) {
                $table->text('link_drive')->nullable()->after('doc_requirement');
            }
            if (!Schema::hasColumn('master_pertanyaan', 'link_ppid')) {
                $table->text('link_ppid')->nullable()->after('link_drive');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pertanyaan', function (Blueprint $table) {
            if (Schema::hasColumn('master_pertanyaan', 'link_drive')) {
                $table->dropColumn('link_drive');
            }
            if (Schema::hasColumn('master_pertanyaan', 'link_ppid')) {
                $table->dropColumn('link_ppid');
            }
        });
    }
};
