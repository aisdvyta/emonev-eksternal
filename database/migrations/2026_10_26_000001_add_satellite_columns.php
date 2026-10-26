<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan khusus SATELIT di atas skema sistem pusat (migrasi pusat disalin apa adanya).
 *
 *  - evaluators: revision/pending_sync/saved_at untuk menandai nilai yang belum diambil pusat.
 *  - visitasi/interview.is_final: pusat sudah memfinalisasi (satelit tidak boleh mengubah nilai lagi).
 *  - users.last_login_at.
 *  - sync_logs: jejak panggilan API sinkronisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['visitasi_evaluators', 'interview_evaluators'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedInteger('revision')->default(0);
                $t->boolean('pending_sync')->default(false)->index();
                $t->timestamp('saved_at')->nullable();
            });
        }

        foreach (['visitasi', 'interview'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_final')->default(false);
            });
        }

        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('last_login_at')->nullable();
        });

        // Peran yang sama dengan sistem pusat. Kode yang disalin dari pusat memanggil User::role([...])
        // untuk semua peran; tanpa baris peran, Spatie melempar RoleDoesNotExist.
        foreach (['admin', 'super_evaluator', 'evaluator', 'operator', 'verifikator', 'penilai_eksternal'] as $name) {
            if (! DB::table('roles')->where('name', $name)->where('guard_name', 'web')->exists()) {
                DB::table('roles')->insert(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 40);
            $table->boolean('ok')->default(true);
            $table->text('summary')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('last_login_at'));
        foreach (['visitasi', 'interview'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('is_final'));
        }
        foreach (['visitasi_evaluators', 'interview_evaluators'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['pending_sync']);
                $t->dropColumn(['revision', 'pending_sync', 'saved_at']);
            });
        }
    }
};
