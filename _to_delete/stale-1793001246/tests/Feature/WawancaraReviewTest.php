<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class WawancaraReviewTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    public function test_assigned_user_sees_page_and_unassigned_gets_403(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->get($this->interviewUrl(30))->assertOk()->assertSee('BPS Kabupaten Contoh');
        $this->actingAs(User::find(101))->get($this->interviewUrl(30))->assertForbidden();
        $this->actingAs(User::find(101))->post($this->interviewUrl(30, '/save'), ['action' => 'draft'])->assertForbidden();
    }

    public function test_draft_may_be_partial(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->post($this->interviewUrl(30, '/save'), ['action' => 'draft', 'note' => 'baru catatan'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'score' => null, 'is_draft' => 1, 'revision' => 1, 'pending_sync' => 1]);
    }

    public function test_submit_requires_score_and_note(): void
    {
        $this->seedPortal();
        $user = User::find(100);

        $this->actingAs($user)->post($this->interviewUrl(30, '/save'), ['action' => 'submit', 'note' => 'ada catatan'])->assertSessionHasErrors('score');
        $this->actingAs($user)->post($this->interviewUrl(30, '/save'), ['action' => 'submit', 'score' => 90])->assertSessionHasErrors('note');
        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_submit_stores_final_score_and_marks_pending(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->post($this->interviewUrl(30, '/save'), ['action' => 'submit', 'score' => 91.25, 'note' => 'Paham alur layanan.'])
            ->assertRedirect(route('penilai-eksternal.monitor-wawancara'))->assertSessionHas('success');

        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'score' => 91.25, 'is_draft' => 0, 'pending_sync' => 1]);
        $this->assertDatabaseHas('submissions', ['id_submission' => 20, 'status' => 5]);
    }

    public function test_locked_before_interview_date(): void
    {
        $this->seedPortal(['interview_at' => today()->addDay()->toDateString()]);

        $this->actingAs(User::find(100))->post($this->interviewUrl(30, '/save'), ['action' => 'submit', 'score' => 80, 'note' => 'x'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('interview_evaluators', ['id_user' => 100, 'revision' => 0]);
    }

    public function test_locked_when_finalized_at_pusat(): void
    {
        $this->seedPortal(['is_final' => true]);

        $this->actingAs(User::find(100))->post($this->interviewUrl(30, '/save'), ['action' => 'submit', 'score' => 80, 'note' => 'x'])
            ->assertSessionHas('error');
        $this->actingAs(User::find(100))->get($this->interviewUrl(30))->assertOk()->assertDontSee('Kirim penilaian');
    }
}
