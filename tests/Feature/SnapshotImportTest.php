<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SnapshotImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class SnapshotImportTest extends TestCase
{
    use SeedsPortal;

    public function test_import_creates_assessor_who_can_login_like_pusat(): void
    {
        app(SnapshotImporter::class)->import($this->sampleSnapshot());

        $this->assertDatabaseHas('users', ['id_user' => 200, 'is_eksternal' => 1, 'nama_user' => 'penilai200']);
        $this->assertTrue(User::find(200)->hasRole('penilai_eksternal'));
        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 200]);

        $this->post('/login', ['nama_user' => 'penilai200', 'password' => 'rahasia123'])
            ->assertRedirect(route('penilai-eksternal.index'));
        $this->assertAuthenticated();
    }

    public function test_wrong_password_and_non_assessor_are_rejected(): void
    {
        app(SnapshotImporter::class)->import($this->sampleSnapshot());

        $this->post('/login', ['nama_user' => 'penilai200', 'password' => 'salah'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_reimport_keeps_pending_scores(): void
    {
        $ids = $this->seedPortal();
        DB::table('visitasi_evaluators')->where('id_user', 100)->update([
            'pending_sync' => 1, 'revision' => 2, 'score' => 77, 'is_draft' => 0, 'saved_at' => now(),
        ]);

        $snap = $this->sampleSnapshot();
        $snap['data']['visitasi_evaluators'] = [['id_visit' => $ids['visit'], 'id_user' => 100, 'score' => 10, 'is_draft' => true]];
        app(SnapshotImporter::class)->import($snap);

        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'pending_sync' => 1, 'revision' => 2]);
        $this->assertEquals(77, DB::table('visitasi_evaluators')->where('id_user', 100)->value('score'));
    }

    public function test_saving_score_marks_pending_and_bumps_revision(): void
    {
        $ids = $this->seedPortal();
        $this->actingAs(User::find(100))
            ->post($this->visitUrl($ids['visit'], '/save'), ['nilai_visitasi' => 80, 'catatan_visitasi' => 'ok', 'action' => 'submit'])
            ->assertRedirect();

        $this->assertDatabaseHas('visitasi_evaluators', ['id_user' => 100, 'pending_sync' => 1, 'revision' => 1, 'is_draft' => 0]);
    }

    public function test_unassigned_assessor_cannot_score(): void
    {
        $ids = $this->seedPortal();
        $this->actingAs(User::find(101))
            ->post($this->visitUrl($ids['visit'], '/save'), ['nilai_visitasi' => 80, 'catatan_visitasi' => 'ok', 'action' => 'submit']);

        $this->assertSame(0, DB::table('visitasi_evaluators')->where('id_user', 101)->count());
    }
}
