<?php

namespace Tests\Feature;

use App\Services\Drive\DriveSyncService;
use App\Services\Drive\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsPortal;
use Tests\Support\FakeDrive;
use Tests\TestCase;

class DriveSyncTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    private const SECRET = 'test-secret-value';

    private FakeDrive $drive;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'external.drive.enabled' => true,
            'external.drive.client_id' => 'cid',
            'external.drive.client_secret' => 'csec',
            'external.drive.refresh_token' => 'rt',
            'external.drive.folder_name' => 'halokip-sync',
        ]);
        Cache::flush();
        $this->drive = FakeDrive::install();
    }

    private function sealed(string $type, array $payload, string $secret = self::SECRET): string
    {
        return Envelope::seal(['v' => 1, 'type' => $type, 'created_at' => now()->toIso8601String(), 'payload' => $payload], $secret);
    }

    private function run_(): array
    {
        return app(DriveSyncService::class)->run();
    }

    private function saveVisitScore(array $ids): void
    {
        $this->actingAs(\App\Models\User::find(100))
            ->post($this->visitUrl($ids['visit'], '/save'), ['nilai_visitasi' => 80, 'catatan_visitasi' => 'baik', 'action' => 'submit'])
            ->assertRedirect();
    }

    public function test_envelope_roundtrip_wrong_secret_and_rotation(): void
    {
        $blob = Envelope::seal(['a' => 1], 'one');
        $this->assertStringStartsWith('HK1.', $blob);
        $this->assertStringNotContainsString('"a"', $blob);
        $this->assertSame(['a' => 1], Envelope::open($blob, 'one'));
        $this->assertSame(['a' => 1], Envelope::open($blob, ['new', 'one']));

        $this->expectException(\RuntimeException::class);
        Envelope::open($blob, 'two');
    }

    public function test_envelope_detects_tampering(): void
    {
        $blob = Envelope::seal(['a' => 1], 'one');
        $bad = substr($blob, 0, -3) . (substr($blob, -3) === 'AAA' ? 'BBB' : 'AAA');
        $this->expectException(\RuntimeException::class);
        Envelope::open($bad, 'one');
    }

    public function test_no_folder_yet_is_a_quiet_noop(): void
    {
        $res = $this->run_();
        $this->assertTrue($res['ok']);
        $this->assertSame(0, $res['snapshots']);
    }

    public function test_applies_snapshot_and_deletes_the_file(): void
    {
        $folder = $this->drive->addFolder();
        $this->drive->addFile($folder, 'snap-001.enc', $this->sealed('snapshot', $this->sampleSnapshot()));

        $res = $this->run_();

        $this->assertTrue($res['ok'], $res['message']);
        $this->assertSame(1, $res['snapshots']);
        $this->assertDatabaseHas('users', ['id_user' => 200]);
        $this->assertSame([], $this->drive->namesIn($folder));
    }

    public function test_snapshots_apply_in_name_order(): void
    {
        $folder = $this->drive->addFolder();
        $a = $this->sampleSnapshot();
        $b = $this->sampleSnapshot();
        $b['data']['users'][0]['name'] = 'Nama Terbaru';
        $this->drive->addFile($folder, 'snap-002.enc', $this->sealed('snapshot', $b));
        $this->drive->addFile($folder, 'snap-001.enc', $this->sealed('snapshot', $a));

        $this->run_();

        $this->assertDatabaseHas('users', ['id_user' => 200, 'name' => 'Nama Terbaru']);
    }

    public function test_foreign_or_wrong_secret_snapshot_is_discarded_and_logged(): void
    {
        $folder = $this->drive->addFolder();
        $this->drive->addFile($folder, 'snap-001.enc', $this->sealed('snapshot', $this->sampleSnapshot(), 'rahasia-orang-lain'));
        $this->drive->addFile($folder, 'snap-002.enc', 'sampah-bukan-envelope');

        $res = $this->run_();

        $this->assertTrue($res['ok']);
        $this->assertSame(0, $res['snapshots']);
        $this->assertDatabaseMissing('users', ['id_user' => 200]);
        $this->assertSame([], $this->drive->namesIn($folder));
        $this->assertSame(2, DB::table('sync_logs')->where('action', 'drive-snapshot')->where('ok', false)->count());
    }

    public function test_publishes_pending_scores_encrypted(): void
    {
        $ids = $this->seedPortal();
        $folder = $this->drive->addFolder();
        $this->saveVisitScore($ids);

        $res = $this->run_();

        $this->assertTrue($res['ok'], $res['message']);
        $this->assertSame(1, $res['published']);
        $blob = $this->drive->contentOf($folder, 'scores.enc');
        $this->assertNotNull($blob);
        $this->assertStringNotContainsString('baik', $blob);

        $msg = Envelope::open($blob, self::SECRET);
        $this->assertSame('scores', $msg['type']);
        $item = $msg['payload']['items'][0];
        $this->assertSame('visitasi', $item['type']);
        $this->assertSame($ids['visit'], $item['parent_id']);
        $this->assertEquals(80, $item['score']);
        $this->assertSame(1, $item['revision']);
    }

    public function test_unchanged_scores_are_not_uploaded_again(): void
    {
        $ids = $this->seedPortal();
        $folder = $this->drive->addFolder();
        $this->saveVisitScore($ids);
        $this->run_();
        $before = $this->drive->contentOf($folder, 'scores.enc');

        $second = $this->run_();

        $this->assertSame(0, $second['published']);
        $this->assertSame($before, $this->drive->contentOf($folder, 'scores.enc'));
    }

    public function test_ack_clears_pending_and_removes_scores_file(): void
    {
        $ids = $this->seedPortal();
        $folder = $this->drive->addFolder();
        $this->saveVisitScore($ids);
        $this->run_();

        $this->drive->addFile($folder, 'ack.enc', $this->sealed('ack', ['items' => [
            ['type' => 'visitasi', 'parent_id' => $ids['visit'], 'id_user' => 100, 'revision' => 1],
        ]]));
        $res = $this->run_();

        $this->assertSame(1, $res['acked']);
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'pending_sync' => 0]);
        $this->assertSame([], $this->drive->namesIn($folder), 'ack dan scores.enc harus bersih');
    }

    public function test_ack_with_old_revision_keeps_the_row_pending(): void
    {
        $ids = $this->seedPortal();
        $folder = $this->drive->addFolder();
        $this->saveVisitScore($ids);

        $this->drive->addFile($folder, 'ack.enc', $this->sealed('ack', ['items' => [
            ['type' => 'visitasi', 'parent_id' => $ids['visit'], 'id_user' => 100, 'revision' => 0],
        ]]));
        $res = $this->run_();

        $this->assertSame(0, $res['acked']);
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'pending_sync' => 1]);
        $this->assertNotNull($this->drive->contentOf($folder, 'scores.enc'), 'nilai tetap diterbitkan');
    }

    public function test_changed_score_replaces_published_file(): void
    {
        $ids = $this->seedPortal();
        $folder = $this->drive->addFolder();
        $this->saveVisitScore($ids);
        $this->run_();

        $this->actingAs(\App\Models\User::find(100))
            ->post($this->visitUrl($ids['visit'], '/save'), ['nilai_visitasi' => 90, 'catatan_visitasi' => 'revisi', 'action' => 'draft']);
        $res = $this->run_();

        $this->assertSame(1, $res['published']);
        $msg = Envelope::open($this->drive->contentOf($folder, 'scores.enc'), self::SECRET);
        $this->assertEquals(90, $msg['payload']['items'][0]['score']);
        $this->assertSame(1, count(array_filter($this->drive->namesIn($folder), fn ($n) => $n === 'scores.enc')));
    }

    public function test_missing_config_reports_failure_without_crashing(): void
    {
        config(['external.drive.refresh_token' => null]);
        $res = app(DriveSyncService::class)->run();
        $this->assertFalse($res['ok']);
        $this->assertDatabaseHas('sync_logs', ['action' => 'drive', 'ok' => 0]);
    }

    public function test_command_is_noop_when_disabled(): void
    {
        config(['external.drive.enabled' => false]);
        $this->artisan('drive:sync')->expectsOutputToContain('nonaktif')->assertExitCode(0);
        $this->assertSame(0, $this->drive->requests);
    }
}
