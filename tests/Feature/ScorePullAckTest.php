<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class ScorePullAckTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    public function test_scores_lists_only_pending_rows_and_is_repeatable(): void
    {
        $ids = $this->seedPortal();

        $this->signedCall('GET', '/api/v1/sync/scores')->assertOk()->assertJson(['items' => [], 'has_more' => false]);

        $this->actingAs(\App\Models\User::find(100))
            ->post($this->visitUrl($ids['visit'], '/save'), [
                'nilai_visitasi' => 82.5, 'catatan_visitasi' => 'baik', 'action' => 'submit',
            ])->assertRedirect();

        for ($i = 0; $i < 2; $i++) {
            $this->signedCall('GET', '/api/v1/sync/scores')
                ->assertOk()
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.type', 'visitasi')
                ->assertJsonPath('items.0.parent_id', $ids['visit'])
                ->assertJsonPath('items.0.id_user', 100)
                ->assertJsonPath('items.0.score', 82.5)
                ->assertJsonPath('items.0.is_draft', false)
                ->assertJsonPath('items.0.revision', 1);
        }
    }

    public function test_ack_clears_only_matching_revision(): void
    {
        $ids = $this->seedPortal();
        DB::table('interview_evaluators')->where('id_user', 100)->update(['pending_sync' => 1, 'revision' => 4, 'score' => 70, 'is_draft' => 0]);

        // revision lama (penilai menyimpan lagi setelah pull) -> tidak boleh di-ack
        $this->signedCall('POST', '/api/v1/sync/ack', ['items' => [
            ['type' => 'interview', 'parent_id' => 30, 'id_user' => 100, 'revision' => 3],
        ]])->assertOk()->assertJson(['acked' => 0]);
        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'pending_sync' => 1]);

        $this->signedCall('POST', '/api/v1/sync/ack', ['items' => [
            ['type' => 'interview', 'parent_id' => 30, 'id_user' => 100, 'revision' => 4],
        ]])->assertOk()->assertJson(['acked' => 1]);
        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'pending_sync' => 0]);

        $this->signedCall('GET', '/api/v1/sync/scores')->assertOk()->assertJson(['items' => []]);
    }

    public function test_ack_rejects_malformed_payload(): void
    {
        $this->signedCall('POST', '/api/v1/sync/ack', ['bukan' => 'items'])->assertStatus(422);
    }

    public function test_has_more_flag_when_over_page_size(): void
    {
        $ids = $this->seedPortal();
        config(['external.sync.scores_page_size' => 1]);

        // dua baris visitasi pending (user 100 dan 101) + satu baris wawancara pending
        DB::table('visitasi_evaluators')->insert(['id_visit' => $ids['visit'], 'id_user' => 101]);
        DB::table('visitasi_evaluators')->update(['pending_sync' => 1, 'revision' => 1, 'saved_at' => now()]);
        DB::table('interview_evaluators')->update(['pending_sync' => 1, 'revision' => 1, 'saved_at' => now()]);

        $this->signedCall('GET', '/api/v1/sync/scores')
            ->assertOk()
            ->assertJsonPath('has_more', true)
            ->assertJsonCount(2, 'items'); // 1 visitasi (batas halaman) + 1 wawancara
    }
}
