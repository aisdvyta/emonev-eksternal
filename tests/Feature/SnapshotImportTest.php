<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class SnapshotImportTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    private function push(array $payload)
    {
        return $this->signedCall('POST', '/api/v1/sync/snapshot', $payload);
    }

    public function test_full_snapshot_creates_all_rows(): void
    {
        $this->push($this->sampleSnapshot())->assertOk()->assertJson(['ok' => true, 'sync_id' => 'test-1']);

        $this->assertDatabaseHas('master_satker', ['kode_satker' => '3510']);
        $this->assertDatabaseHas('submissions', ['id_submission' => 60, 'status' => 5]);
        $this->assertDatabaseHas('visitasi', ['id_visit' => '22222222-2222-2222-2222-222222222222', 'visit_at' => '2026-06-10']);
        $this->assertDatabaseHas('interview', ['id_interview' => 70]);
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 200, 'pending_sync' => 0, 'revision' => 0]);
        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 200, 'is_draft' => 1]);
        $this->assertDatabaseHas('periods', ['id_periode' => 5, 'visit_start' => '2026-06-01']);
        $this->assertDatabaseHas('users', ['id_user' => 200, 'email' => 'baru@example.test', 'is_active' => 1]);
    }

    public function test_snapshot_is_idempotent(): void
    {
        $this->push($this->sampleSnapshot())->assertOk();
        $this->push($this->sampleSnapshot())->assertOk();

        $this->assertSame(1, DB::table('submissions')->count());
        $this->assertSame(1, DB::table('answer')->count());
        $this->assertSame(1, DB::table('visitasi_evaluators')->count());
        $this->assertSame(1, DB::table('users')->where('id_user', 200)->count());
    }

    public function test_snapshot_never_overwrites_unsynced_local_scores(): void
    {
        $ids = $this->seedPortal();
        DB::table('visitasi_evaluators')->where('id_user', 100)->update([
            'score' => 88, 'catatan' => 'ditulis penilai', 'is_draft' => 0, 'revision' => 3, 'pending_sync' => 1,
        ]);

        $this->push(['mode' => 'delta', 'data' => [
            'visitasi_evaluators' => [['id_visit' => $ids['visit'], 'id_user' => 100, 'score' => 10, 'catatan' => 'dari pusat', 'is_draft' => true]],
        ]])->assertOk()->assertJsonPath('summary.visitasi_evaluators.kept_local', 1);

        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'score' => 88, 'catatan' => 'ditulis penilai', 'revision' => 3]);
    }

    public function test_snapshot_updates_rows_that_are_already_synced(): void
    {
        $ids = $this->seedPortal();

        $this->push(['mode' => 'delta', 'data' => [
            'interview_evaluators' => [['id_interview' => 30, 'id_user' => 100, 'score' => 75.5, 'catatan' => 'sinkron pusat', 'is_draft' => false]],
        ]])->assertOk();

        $this->assertDatabaseHas('interview_evaluators', ['id_interview' => 30, 'id_user' => 100, 'score' => 75.5, 'is_draft' => 0]);
    }

    public function test_unknown_table_is_rejected_without_changes(): void
    {
        $this->push(['data' => ['pg_catalog' => [['x' => 1]]]])->assertStatus(422);
        $this->push(['data' => ['users' => [['id_user' => 300, 'email' => 'x@example.test', 'name' => 'X']], 'tabel_ngawur' => []]])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['id_user' => 300]);
    }

    public function test_user_without_hash_gets_unusable_password(): void
    {
        $this->push($this->sampleSnapshot())->assertOk();

        $user = DB::table('users')->where('id_user', 200)->first();
        $this->assertNotEmpty($user->password);
        $this->assertFalse(Hash::check('', $user->password));
    }

    public function test_user_with_bcrypt_hash_can_login_and_plaintext_is_rejected(): void
    {
        $snapshot = $this->sampleSnapshot();
        $snapshot['data']['users'][0]['password_hash'] = Hash::make('Rahasia-12345');
        $snapshot['data']['users'][] = ['id_user' => 201, 'name' => 'Teks Biasa', 'email' => 'plain@example.test', 'password_hash' => 'rahasia-teks-biasa'];
        $this->push($snapshot)->assertOk();

        $this->assertTrue(Hash::check('Rahasia-12345', DB::table('users')->where('id_user', 200)->value('password')));
        $this->assertDatabaseMissing('users', ['id_user' => 201]);
    }

    public function test_pusat_can_deactivate_user(): void
    {
        $this->seedPortal();

        $this->push(['mode' => 'delta', 'data' => ['users' => [
            ['id_user' => 100, 'email' => 'penilai100@example.test', 'name' => 'Penilai Satu', 'is_active' => false],
        ]]])->assertOk();

        $this->assertDatabaseHas('users', ['id_user' => 100, 'is_active' => 0]);
    }

    public function test_prune_removes_missing_rows_but_keeps_pending_scores(): void
    {
        $ids = $this->seedPortal();
        DB::table('visitasi_evaluators')->where('id_user', 100)->update(['pending_sync' => 1, 'revision' => 1, 'score' => 70, 'is_draft' => 0]);

        $this->push(['mode' => 'full', 'prune' => ['visitasi_evaluators', 'submission_evaluators', 'answer'], 'data' => [
            'visitasi_evaluators' => [],
            'submission_evaluators' => [],
            'answer' => [],
        ]])->assertOk();

        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100]); // pending: dilindungi
        $this->assertSame(0, DB::table('submission_evaluators')->count());
    }

    public function test_deletions_remove_rows_and_skip_pending(): void
    {
        $ids = $this->seedPortal();
        DB::table('interview_evaluators')->update(['pending_sync' => 1]);

        $this->push(['mode' => 'delta', 'data' => [], 'deletions' => [
            'submission_evaluators' => [[20, 100]],
            'interview_evaluators' => [[30, 100]],
            'result' => [10],
        ]])->assertOk();

        $this->assertSame(0, DB::table('submission_evaluators')->count());
        $this->assertSame(1, DB::table('interview_evaluators')->count());
        $this->assertSame(0, DB::table('result')->count());
    }

    public function test_prune_is_ignored_for_delta_mode(): void
    {
        $this->seedPortal();

        $this->push(['mode' => 'delta', 'prune' => ['master_satker'], 'data' => ['master_satker' => []]])->assertOk();

        $this->assertDatabaseHas('master_satker', ['kode_satker' => '3501']);
    }

    public function test_prune_of_users_is_refused(): void
    {
        $this->push(['mode' => 'full', 'prune' => ['users'], 'data' => ['users' => []]])->assertStatus(422);
    }

    public function test_invalid_json_payload_is_rejected(): void
    {
        $this->push(['foo' => 'bar'])->assertStatus(422)->assertJson(['error' => 'invalid_payload']);
    }
}
