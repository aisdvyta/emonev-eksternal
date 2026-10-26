<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rows = DB::table('master_pertanyaan')->get();

        foreach ($rows as $row) {
            $updates = [];
            $doc = $row->doc_requirement ?? '';

            // 1. Ekstrak link_drive jika masih kosong
            if (empty($row->link_drive) && !empty($doc)) {
                if (preg_match('/link\s*drive\s*:\s*(.*?)(?=(?:link\s*ppid|\Z))/is', $doc, $mDrive)) {
                    $val = trim($mDrive[1]);
                    if ($val !== '-' && !empty($val)) {
                        $updates['link_drive'] = $val;
                    }
                }
            }

            // 2. Ekstrak link_ppid jika masih kosong
            if (empty($row->link_ppid) && !empty($doc)) {
                if (preg_match('/link\s*ppid(?:\/lainnya)?\s*:\s*(.*?)$/is', $doc, $mPpid)) {
                    $val = trim($mPpid[1]);
                    if ($val !== '-' && !empty($val)) {
                        $updates['link_ppid'] = $val;
                    }
                }
            }

            // 3. Bersihkan doc_requirement jika berisi template Contoh Pengisian
            if (str_contains($doc, 'Contoh Pengisian:') || str_contains($doc, 'Link Drive:') || str_contains($doc, 'Link PPID:')) {
                $updates['doc_requirement'] = null;
            }

            if (!empty($updates)) {
                DB::table('master_pertanyaan')
                    ->where('id_master_pertanyaan', $row->id_master_pertanyaan)
                    ->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-generate doc_requirement from link_drive & link_ppid if rolled back
        $rows = DB::table('master_pertanyaan')->get();

        foreach ($rows as $row) {
            if (empty($row->doc_requirement) && (!empty($row->link_drive) || !empty($row->link_ppid))) {
                $legacyDoc = "Contoh Pengisian:\n\nLink Drive:\n" . ($row->link_drive ?: '-') . "\n\nLink PPID:\n" . ($row->link_ppid ?: '-');
                DB::table('master_pertanyaan')
                    ->where('id_master_pertanyaan', $row->id_master_pertanyaan)
                    ->update(['doc_requirement' => $legacyDoc]);
            }
        }
    }
};
