<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Menerapkan snapshot data dari sistem pusat ke database satelit.
 *
 * Aturan penting:
 *  1. Idempoten: snapshot yang sama dikirim dua kali menghasilkan kondisi yang sama.
 *  2. Tabel evaluator (visitasi_evaluators / interview_evaluators): baris dengan pending_sync=true
 *     adalah nilai yang ditulis penilai dan BELUM diambil pusat. Baris itu tidak pernah ditimpa
 *     atau dihapus oleh snapshot.
 *  3. Hanya kolom di whitelist yang diterima; kolom lain diabaikan.
 *  4. Seluruh snapshot berjalan dalam satu transaksi: sukses semua atau tidak sama sekali.
 */
class SnapshotImporter
{
    /**
     * Definisi tabel: pk = kolom kunci, cols = kolom yang boleh diisi pusat, dates/datetimes untuk normalisasi.
     * Urutan array = urutan penerapan.
     */
    private const TABLES = [
        'periods' => [
            'pk' => ['id_periode'],
            'cols' => ['periode_start', 'periode_end', 'saq_start', 'saq_end', 'eval_start', 'eval_end',
                'visit_start', 'visit_end', 'interview_start', 'interview_end', 'result_start', 'result_end'],
            'dates' => ['periode_start', 'periode_end', 'saq_start', 'saq_end', 'eval_start', 'eval_end',
                'visit_start', 'visit_end', 'interview_start', 'interview_end', 'result_start', 'result_end'],
        ],
        'master_satker' => [
            'pk' => ['kode_satker'],
            'cols' => ['nama_satker'],
        ],
        'master_indikator' => [
            'pk' => ['id_indicators'],
            'cols' => ['nama_indikator', 'deskripsi_indikator'],
        ],
        'master_sub_indikator' => [
            'pk' => ['id_sub_indicators'],
            'cols' => ['id_indicators', 'nama_sub_indikator'],
        ],
        'master_pertanyaan' => [
            'pk' => ['id_master_pertanyaan'],
            'cols' => ['order_no', 'id_sub_indicators', 'id_indicators', 'ques_text', 'ques_description',
                'doc_requirement', 'link_drive', 'link_ppid', 'bobot_pertanyaan'],
        ],
        'result' => [
            'pk' => ['id_result'],
            'cols' => ['kode_satker', 'id_periode'],
        ],
        'submissions' => [
            'pk' => ['id_submission'],
            'cols' => ['id_result', 'status'],
        ],
        'answer' => [
            'pk' => ['id_answer'],
            'cols' => ['id_submission', 'id_question', 'id_indicators', 'answer', 'link_ppid', 'link_drive'],
        ],
        'submission_evaluators' => [
            'pk' => ['id_submission', 'id_user'],
            'cols' => [],
        ],
        'visitasi' => [
            'pk' => ['id_visit'],
            'cols' => ['id_result', 'visit_mode', 'link_meet_visitasi', 'visit_at', 'link_buktidukung', 'is_final'],
            'dates' => ['visit_at'],
        ],
        'interview' => [
            'pk' => ['id_interview'],
            'cols' => ['id_result', 'link_meet_interview', 'interview_at', 'is_final'],
            'dates' => ['interview_at'],
        ],
    ];

    /** Tabel nilai milik penilai: kunci komposit [kolom induk, id_user]. */
    private const EVALUATOR_TABLES = [
        'visitasi_evaluators' => 'id_visit',
        'interview_evaluators' => 'id_interview',
    ];

    /** Tabel yang boleh di-prune (hapus baris yang tidak ada di payload). Users sengaja tidak termasuk. */
    private const PRUNABLE = [
        'periods', 'master_satker', 'master_indikator', 'master_sub_indikator', 'master_pertanyaan',
        'result', 'submissions', 'answer', 'submission_evaluators', 'visitasi', 'interview',
        'visitasi_evaluators', 'interview_evaluators',
    ];

    /**
     * @param  array  $payload  isi request: mode, data, deletions, prune
     * @return array ringkasan per tabel
     */
    public function import(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $deletions = $payload['deletions'] ?? [];
        $mode = $payload['mode'] ?? 'delta';
        $prune = $mode === 'full' ? array_values((array) ($payload['prune'] ?? [])) : [];

        if (! is_array($data) || ! is_array($deletions)) {
            throw new InvalidArgumentException('data dan deletions harus berupa objek.');
        }

        $known = array_merge(['users'], array_keys(self::TABLES), array_keys(self::EVALUATOR_TABLES));
        foreach (array_keys($data) as $key) {
            if (! in_array($key, $known, true)) {
                throw new InvalidArgumentException("Tabel tidak dikenal pada data: {$key}");
            }
        }
        foreach ($prune as $t) {
            if (! in_array($t, self::PRUNABLE, true)) {
                throw new InvalidArgumentException("Tabel tidak boleh di-prune: {$t}");
            }
        }

        $max = (int) config('external.sync.max_rows_per_table', 20000);
        foreach ($data as $table => $rows) {
            if (! is_array($rows)) {
                throw new InvalidArgumentException("Data {$table} harus berupa array.");
            }
            if (count($rows) > $max) {
                throw new InvalidArgumentException("Data {$table} melebihi batas {$max} baris.");
            }
        }

        $summary = [];

        DB::transaction(function () use ($data, $deletions, $prune, &$summary) {
            if (isset($data['users'])) {
                $summary['users'] = $this->importUsers($data['users']);
            }

            foreach (self::TABLES as $table => $def) {
                if (! isset($data[$table])) {
                    continue;
                }
                $summary[$table] = $this->upsertPlain($table, $def, $data[$table]);
            }

            foreach (self::EVALUATOR_TABLES as $table => $parentCol) {
                if (isset($data[$table])) {
                    $summary[$table] = $this->importEvaluators($table, $parentCol, $data[$table]);
                }
            }

            foreach ($deletions as $table => $keys) {
                $summary[$table]['deleted'] = ($summary[$table]['deleted'] ?? 0) + $this->applyDeletions($table, (array) $keys);
            }

            foreach ($prune as $table) {
                if (! array_key_exists($table, $data)) {
                    continue; // tidak ada daftar acuan = tidak ada yang dipangkas
                }
                $summary[$table]['pruned'] = $this->prune($table, $data[$table]);
            }
        });

        return $summary;
    }

    // ---------------------------------------------------------------- users

    private function importUsers(array $rows): array
    {
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $id = $row['id_user'] ?? null;
            $email = isset($row['email']) ? mb_strtolower(trim((string) $row['email'])) : '';
            if (! is_numeric($id) || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            $id = (int) $id;

            $attrs = [
                'name' => (string) ($row['name'] ?? $row['nama_user'] ?? $email),
                'nama_user' => isset($row['nama_user']) && $row['nama_user'] !== '' ? (string) $row['nama_user'] : null,
                'email' => $email,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
                'updated_at' => now(),
            ];

            // Hash kata sandi hanya diterima bila berformat bcrypt ($2y$); tidak pernah menerima teks biasa.
            $hash = $row['password_hash'] ?? null;
            if (is_string($hash) && $hash !== '') {
                if (! preg_match('/^\$2y\$/', $hash)) {
                    $skipped++;
                    continue;
                }
                $attrs['password'] = $hash;
            }

            $existing = DB::table('users')->where('id_user', $id)->first();
            if ($existing) {
                DB::table('users')->where('id_user', $id)->update($attrs);
            } else {
                $attrs += [
                    'id_user' => $id,
                    // Tanpa hash dari pusat: kata sandi acak yang tidak bisa ditebak (set lewat artisan atau sinkron berikutnya).
                    'password' => $attrs['password'] ?? Hash::make(Str::random(48)),
                    'created_at' => now(),
                ];
                DB::table('users')->insert($attrs);
            }
            $count++;
        }

        return ['upserted' => $count, 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------- tabel biasa

    private function upsertPlain(string $table, array $def, array $rows): array
    {
        $pk = $def['pk'];
        $cols = $def['cols'];
        $clean = [];
        $skipped = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;
                continue;
            }
            $out = [];
            $hasKey = true;
            foreach ($pk as $p) {
                if (! isset($row[$p]) || $row[$p] === '') {
                    $hasKey = false;
                    break;
                }
                $out[$p] = $row[$p];
            }
            if (! $hasKey) {
                $skipped++;
                continue;
            }
            foreach ($cols as $c) {
                $out[$c] = array_key_exists($c, $row) ? $row[$c] : null;
            }
            foreach ($def['dates'] ?? [] as $d) {
                $out[$d] = $this->normalizeDate($out[$d] ?? null);
            }
            if ($table === 'submissions' && isset($out['status'])) {
                $out['status'] = (int) $out['status'];
            }
            foreach (['is_final', 'answer'] as $boolCol) {
                if (array_key_exists($boolCol, $out) && $out[$boolCol] !== null) {
                    $out[$boolCol] = (bool) $out[$boolCol] ? 1 : 0;
                }
            }
            $clean[] = $out;
        }

        if ($clean === []) {
            return ['upserted' => 0, 'skipped' => $skipped];
        }

        if ($cols === []) {
            // Tabel hanya-kunci (submission_evaluators): insert yang belum ada.
            foreach (array_chunk($clean, 500) as $chunk) {
                DB::table($table)->insertOrIgnore($chunk);
            }
        } else {
            foreach (array_chunk($clean, 500) as $chunk) {
                DB::table($table)->upsert($chunk, $pk, $cols);
            }
        }

        return ['upserted' => count($clean), 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------- tabel nilai

    private function importEvaluators(string $table, string $parentCol, array $rows): array
    {
        $applied = 0;
        $keptLocal = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $parent = $row[$parentCol] ?? null;
            $userId = $row['id_user'] ?? null;
            if ($parent === null || $parent === '' || ! is_numeric($userId)) {
                $skipped++;
                continue;
            }

            $score = array_key_exists('score', $row) && $row['score'] !== null && $row['score'] !== ''
                ? round((float) $row['score'], 2)
                : null;
            $values = [
                'score' => $score,
                'catatan' => $row['catatan'] ?? null,
                'is_draft' => array_key_exists('is_draft', $row) ? ((bool) $row['is_draft'] ? 1 : 0) : 1,
            ];

            $where = [$parentCol => $parent, 'id_user' => (int) $userId];
            $existing = DB::table($table)->where($where)->first();

            if (! $existing) {
                DB::table($table)->insert($where + $values + ['revision' => 0, 'pending_sync' => 0, 'saved_at' => null]);
                $applied++;
            } elseif ($existing->pending_sync) {
                $keptLocal++; // perubahan lokal belum diambil pusat: jangan ditimpa
            } else {
                DB::table($table)->where($where)->update($values);
                $applied++;
            }
        }

        return ['applied' => $applied, 'kept_local' => $keptLocal, 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------- hapus & prune

    private function applyDeletions(string $table, array $keys): int
    {
        $deleted = 0;

        if (isset(self::EVALUATOR_TABLES[$table])) {
            $parentCol = self::EVALUATOR_TABLES[$table];
            foreach ($keys as $key) {
                if (! is_array($key) || count($key) !== 2) {
                    continue;
                }
                [$parent, $userId] = array_values($key);
                $deleted += DB::table($table)
                    ->where($parentCol, $parent)->where('id_user', (int) $userId)
                    ->where('pending_sync', false)
                    ->delete();
            }

            return $deleted;
        }

        if (! isset(self::TABLES[$table])) {
            return 0; // users tidak dihapus lewat deletions; nonaktifkan dengan is_active=false
        }

        $pk = self::TABLES[$table]['pk'];
        foreach ($keys as $key) {
            $q = DB::table($table);
            if (count($pk) === 1) {
                $q->where($pk[0], is_array($key) ? ($key[0] ?? null) : $key);
            } else {
                if (! is_array($key) || count($key) !== count($pk)) {
                    continue;
                }
                foreach ($pk as $i => $col) {
                    $q->where($col, array_values($key)[$i]);
                }
            }
            $deleted += $q->delete();
        }

        return $deleted;
    }

    private function prune(string $table, array $rows): int
    {
        $deleted = 0;

        if (isset(self::EVALUATOR_TABLES[$table])) {
            $parentCol = self::EVALUATOR_TABLES[$table];
            $keep = [];
            foreach ($rows as $r) {
                $keep[($r[$parentCol] ?? '').'|'.($r['id_user'] ?? '')] = true;
            }
            $existing = DB::table($table)->where('pending_sync', false)->get([$parentCol, 'id_user']);
            foreach ($existing as $e) {
                if (! isset($keep[$e->{$parentCol}.'|'.$e->id_user])) {
                    $deleted += DB::table($table)->where($parentCol, $e->{$parentCol})
                        ->where('id_user', $e->id_user)->where('pending_sync', false)->delete();
                }
            }

            return $deleted;
        }

        $pk = self::TABLES[$table]['pk'];
        $keep = [];
        foreach ($rows as $r) {
            $keep[implode('|', array_map(fn ($c) => (string) ($r[$c] ?? ''), $pk))] = true;
        }
        $existing = DB::table($table)->get($pk);
        foreach ($existing as $e) {
            $k = implode('|', array_map(fn ($c) => (string) $e->{$c}, $pk));
            if (! isset($keep[$k])) {
                $q = DB::table($table);
                foreach ($pk as $c) {
                    $q->where($c, $e->{$c});
                }
                $deleted += $q->delete();
            }
        }

        return $deleted;
    }

    // ---------------------------------------------------------------- util

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
