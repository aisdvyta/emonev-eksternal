<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class VisitasiReviewTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    private function payload(array $o = []): array
    {
        return array_merge(['nilai_visitasi' => 85, 'catatan_visitasi' => 'Dokumen lengkap.', 'action' => 'submit'], $o);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $ids = $this->seedPortal();

        $this->get($this->visitUrl($ids['visit']))->assertRedirect('/login');
        $this->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertRedirect('/login');
    }

    public function test_assigned_user_sees_review_page(): void
    {
        $ids = $this->seedPortal();

        $this->actingAs(User::find(100))->get($this->visitUrl($ids['visit']))
            ->assertOk()->assertSee('BPS Kabupaten Contoh')->assertSee('Kirim penilaian');
    }

    public function test_user_not_assigned_gets_403(): void
    {
        $ids = $this->seedPortal();
        $other = User::find(101);

        $this->actingAs($other)->get($this->visitUrl($ids['visit']))->assertForbidden();
        $this->actingAs($other)->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertForbidden();
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'pending_sync' => 0, 'revision' => 0]);
    }

    public function test_draft_then_submit_marks_pending_and_bumps_revision(): void
    {
        $ids = $this->seedPortal();
        $user = User::find(100);

        $this->actingAs($user)->post($this->visitUrl($ids['visit'], '/save'), $this->payload(['action' => 'draft', 'nilai_visitasi' => 70]))
            ->assertRedirect(route('penilai-eksternal.monitor-visitasi'))->assertSessionHas('success');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'score' => 70, 'is_draft' => 1, 'revision' => 1, 'pending_sync' => 1]);

        $this->actingAs($user)->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertSessionHas('success');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'score' => 85, 'is_draft' => 0, 'revision' => 2, 'pending_sync' => 1]);
    }

    public function test_satelit_never_touches_aggregate_or_status(): void
    {
        $ids = $this->seedPortal();

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertSessionHas('success');

        $this->assertDatabaseHas('submissions', ['id_submission' => 20, 'status' => 5]);
        $this->assertDatabaseHas('visitasi', ['id_visit' => $ids['visit'], 'is_final' => 0]);
    }

    public function test_validation_rejects_out_of_range_and_missing_note(): void
    {
        $ids = $this->seedPortal();
        $user = User::find(100);

        $this->actingAs($user)->post($this->visitUrl($ids['visit'], '/save'), $this->payload(['nilai_visitasi' => 101]))
            ->assertSessionHasErrors('nilai_visitasi');
        $this->actingAs($user)->post($this->visitUrl($ids['visit'], '/save'), $this->payload(['catatan_visitasi' => '']))
            ->assertSessionHasErrors('catatan_visitasi');
        $this->actingAs($user)->post($this->visitUrl($ids['visit'], '/save'), $this->payload(['action' => 'hapus']))
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_locked_when_finalized_at_pusat(): void
    {
        $ids = $this->seedPortal(['is_final' => true]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())
            ->assertSessionHas('error');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0, 'pending_sync' => 0]);

        $this->actingAs(User::find(100))->get($this->visitUrl($ids['visit']))
            ->assertOk()->assertSee('difinalisasi')->assertDontSee('Kirim penilaian');
    }

    public function test_locked_before_visit_stage_starts(): void
    {
        $ids = $this->seedPortal(['period' => ['visit_start' => today()->addDays(3)->toDateString(), 'visit_end' => today()->addDays(9)->toDateString()]]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())
            ->assertSessionHas('error');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_locked_after_deadline(): void
    {
        $ids = $this->seedPortal(['period' => ['visit_start' => today()->subDays(20)->toDateString(), 'visit_end' => today()->subDay()->toDateString()]]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())
            ->assertSessionHas('error');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_offline_visit_cannot_be_scored_before_visit_date(): void
    {
        $ids = $this->seedPortal(['visit_at' => today()->addDay()->toDateString()]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertSessionHas('error');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_online_upload_mode_ignores_visit_date(): void
    {
        $ids = $this->seedPortal(['visit_mode' => 'online_upload', 'visit_at' => null]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertSessionHas('success');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 1]);
    }

    #[DataProvider('statusesOutsideVisitStage')]
    public function test_locked_when_submission_is_not_in_visit_stage(int $status): void
    {
        $ids = $this->seedPortal(['status' => $status]);

        $this->actingAs(User::find(100))->post($this->visitUrl($ids['visit'], '/save'), $this->payload())->assertSessionHas('error');
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public static function statusesOutsideVisitStage(): array
    {
        return ['sebelum visitasi (4)' => [4], 'selesai (7)' => [7], 'gugur (8)' => [8]];
    }

    public function test_deactivated_account_is_logged_out_on_next_request(): void
    {
        $ids = $this->seedPortal();
        DB::table('users')->where('id_user', 100)->update(['is_active' => false]);

        $this->actingAs(User::find(100))->get($this->visitUrl($ids['visit']))->assertRedirect('/login');
    }

    public function test_monitor_lists_only_own_assignments(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->get('/penilai-eksternal/monitor-visitasi')->assertOk()->assertSee('BPS Kabupaten Contoh');
        $this->actingAs(User::find(101))->get('/penilai-eksternal/monitor-visitasi')->assertOk()->assertDontSee('BPS Kabupaten Contoh');
    }
}
