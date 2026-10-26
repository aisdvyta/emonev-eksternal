<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait SeedsPortal
{
    public const PASSWORD = 'Rahasia-12345';

    /**
     * Skenario dasar: periode berjalan, 1 satker status visitasi (5), visitasi & wawancara terjadwal kemarin,
     * penilai 100 ditugaskan, penilai 101 (juga penilai eksternal) TIDAK ditugaskan.
     *
     * @param  array  $o  override: period(array), visit_at, interview_at, status, is_final
     */
    protected function seedPortal(array $o = []): array
    {
        \Illuminate\Support\Facades\Cache::forget('periods_desc');
        $r = new \ReflectionProperty(\App\Models\Period::class, 'activePeriodCache'); $r->setAccessible(true); $r->setValue(null, null);
        $today = today();
        $visitId = (string) Str::uuid();

        DB::table('periods')->insert(array_merge([
            'id_periode' => 1,
            'periode_start' => $today->copy()->subMonths(2)->toDateString(),
            'periode_end' => $today->copy()->addMonths(2)->toDateString(),
            'saq_start' => $today->copy()->subMonths(2)->toDateString(),
            'saq_end' => $today->copy()->subDays(20)->toDateString(),
            'eval_start' => $today->copy()->subDays(19)->toDateString(),
            'eval_end' => $today->copy()->subDays(11)->toDateString(),
            'visit_start' => $today->copy()->subDays(10)->toDateString(),
            'visit_end' => $today->copy()->addDays(10)->toDateString(),
            'interview_start' => $today->copy()->subDays(10)->toDateString(),
            'interview_end' => $today->copy()->addDays(10)->toDateString(),
            'result_start' => $today->copy()->addDays(11)->toDateString(),
            'result_end' => $today->copy()->addDays(30)->toDateString(),
        ], $o['period'] ?? []));

        DB::table('master_satker')->insert(['kode_satker' => '3501', 'nama_satker' => 'BPS Kabupaten Contoh']);
        DB::table('result')->insert(['id_result' => 10, 'kode_satker' => '3501', 'id_periode' => 1]);
        DB::table('submissions')->insert(['id_submission' => 20, 'id_result' => 10, 'status' => $o['status'] ?? 5]);

        DB::table('master_indikator')->insert(['id_indicators' => 1, 'nama_indikator' => 'Indikator Satu']);
        DB::table('master_sub_indikator')->insert(['id_sub_indicators' => 1, 'id_indicators' => 1, 'nama_sub_indikator' => 'Sub Satu']);
        DB::table('master_pertanyaan')->insert([
            'id_master_pertanyaan' => 1, 'order_no' => 1, 'id_sub_indicators' => 1, 'id_indicators' => 1,
            'ques_text' => 'Apakah ada SOP?', 'bobot_pertanyaan' => 10,
        ]);
        DB::table('answer')->insert([
            'id_answer' => (string) Str::uuid(), 'id_submission' => 20, 'id_question' => 1, 'id_indicators' => 1,
            'answer' => 1, 'link_ppid' => 'https://contoh.test/ppid',
        ]);

        DB::table('visitasi')->insert([
            'id_visit' => $visitId, 'id_result' => 10, 'visit_mode' => $o['visit_mode'] ?? 'offline',
            'visit_at' => $o['visit_at'] ?? $today->copy()->subDay()->toDateString(),
            'is_final' => $o['is_final'] ?? false,
        ]);
        DB::table('interview')->insert([
            'id_interview' => 30, 'id_result' => 10,
            'interview_at' => $o['interview_at'] ?? $today->copy()->subDay()->toDateString(),
            'is_final' => $o['is_final'] ?? false,
        ]);

        $role = Role::findOrCreate('penilai_eksternal', 'web');
        foreach ([100 => 'Penilai Satu', 101 => 'Penilai Dua'] as $id => $name) {
            DB::table('users')->insert([
                'id_user' => $id, 'name' => $name, 'nama_user' => 'penilai'.$id, 'email' => "penilai{$id}@example.test",
                'password' => Hash::make(self::PASSWORD), 'is_eksternal' => 1,
            ]);
            DB::table('model_has_roles')->insert(['role_id' => $role->id, 'model_type' => \App\Models\User::class, 'model_id' => $id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('visitasi_evaluators')->insert(['id_visit' => $visitId, 'id_user' => 100]);
        DB::table('interview_evaluators')->insert(['id_interview' => 30, 'id_user' => 100]);
        DB::table('submission_evaluators')->insert(['id_submission' => 20, 'id_user' => 100]);

        return ['visit' => $visitId, 'interview' => 30, 'submission' => 20, 'user' => 100, 'other' => 101];
    }

    protected function visitUrl(string $visitId, string $suffix = ''): string
    {
        return '/penilai-eksternal/review-visitasi/'.\App\Helpers\SecureId::encrypt($visitId).$suffix;
    }

    protected function interviewUrl(int $id, string $suffix = ''): string
    {
        return '/penilai-eksternal/review-wawancara/'.\App\Helpers\SecureId::encrypt($id).$suffix;
    }

    /**
     * Panggil API sinkronisasi dengan tanda tangan HMAC yang benar (atau sengaja salah lewat $o).
     *
     * @param  array  $o  secret, ts, nonce, sign_body (body yang ditandatangani, jika beda dari yang dikirim), omit (header)
     */
    protected function signedCall(string $method, string $uri, ?array $body = null, array $o = [])
    {
        $content = $body === null ? '' : json_encode($body);
        $ts = (string) ($o['ts'] ?? time());
        $nonce = $o['nonce'] ?? bin2hex(random_bytes(16));
        $secret = $o['secret'] ?? 'test-secret-value';
        $signedBody = $o['sign_body'] ?? $content;

        $canonical = implode("\n", [strtoupper($method), $uri, $ts, $nonce, hash('sha256', $signedBody)]);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SYNC_TIMESTAMP' => $ts,
            'HTTP_X_SYNC_NONCE' => $nonce,
            'HTTP_X_SYNC_SIGNATURE' => 'v1='.hash_hmac('sha256', $canonical, $secret),
        ];
        foreach ($o['omit'] ?? [] as $header) {
            unset($server[$header]);
        }

        return $this->call($method, $uri, [], [], [], $server, $content);
    }

    /** Snapshot minimal yang valid (skema pusat). */
    protected function sampleSnapshot(): array
    {
        return [
            'sync_id' => 'test-1',
            'mode' => 'full',
            'data' => [
                'users' => [[
                    'id_user' => 200, 'name' => 'Penilai Baru', 'email' => 'Baru@Example.test',
                    'nama_user' => 'penilai200', 'instansi' => 'Univ X', 'is_active' => true,
                    'password_hash' => password_hash('rahasia123', PASSWORD_BCRYPT),
                ]],
                'periods' => [[
                    'id_periode' => 5, 'periode_start' => '2026-01-01', 'periode_end' => '2026-12-31',
                    'visit_start' => '2026-06-01T00:00:00+07:00', 'visit_end' => '2026-06-30',
                ]],
                'master_satker' => [['kode_satker' => '3510', 'nama_satker' => 'BPS Kabupaten Baru']],
                'master_indikator' => [['id_indicators' => 1, 'nama_indikator' => 'Indikator 1']],
                'master_sub_indikator' => [['id_sub_indicators' => 1, 'id_indicators' => 1, 'nama_sub_indikator' => 'Sub 1']],
                'master_pertanyaan' => [[
                    'id_master_pertanyaan' => 1, 'order_no' => 1, 'id_sub_indicators' => 1, 'id_indicators' => 1,
                    'ques_text' => 'Apakah ada SOP?', 'bobot_pertanyaan' => 5,
                ]],
                'result' => [['id_result' => 50, 'kode_satker' => '3510', 'id_periode' => 5]],
                'submissions' => [['id_submission' => 60, 'id_result' => 50, 'status' => 5]],
                'answer' => [[
                    'id_answer' => '11111111-1111-1111-1111-111111111111', 'id_submission' => 60, 'id_question' => 1,
                    'id_indicators' => 1, 'answer' => true, 'link_drive' => 'https://drive.example.test/x',
                ]],
                'submission_evaluators' => [['id_submission' => 60, 'id_user' => 200]],
                'visitasi' => [[
                    'id_visit' => '22222222-2222-2222-2222-222222222222', 'id_result' => 50, 'visit_mode' => 'offline',
                    'visit_at' => '2026-06-10', 'is_final' => false,
                ]],
                'interview' => [['id_interview' => 70, 'id_result' => 50, 'interview_at' => '2026-06-20', 'is_final' => false]],
                'visitasi_evaluators' => [['id_visit' => '22222222-2222-2222-2222-222222222222', 'id_user' => 200]],
                'interview_evaluators' => [['id_interview' => 70, 'id_user' => 200]],
            ],
        ];
    }
}
