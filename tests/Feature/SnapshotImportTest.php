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

    public function test_recreated_account_with_new_id_replaces_the_old_one(): void
    {
        app(SnapshotImporter::class)->import($this->sampleSnapshot());

        // Pusat dibersihkan lalu akun yang sama dibuat ulang dengan id baru (email & nama_user sama).
        $snap = $this->sampleSnapshot();
        $snap['data']['users'][0]['id_user'] = 300;
        $snap['data']['submission_evaluators'] = [['id_submission' => 60, 'id_user' => 300]];
        $snap['data']['visitasi_evaluators'] = [['id_visit' => '22222222-2222-2222-2222-222222222222', 'id_user' => 300]];
        $snap['data']['interview_evaluators'] = [['id_interview' => 70, 'id_user' => 300]];
        app(SnapshotImporter::class)->import($snap + ['prune' => []]);

        $this->assertDatabaseMissing('users', ['id_user' => 200]);
        $this->assertDatabaseHas('users', ['id_user' => 300, 'nama_user' => 'penilai200']);
        $this->assertTrue(User::find(300)->hasRole('penilai_eksternal'));
        $this->assertSame(0, DB::table('visitasi_evaluators')->where('id_user', 200)->count());
    }

    public function test_full_snapshot_prunes_rows_removed_at_pusat(): void
    {
        app(SnapshotImporter::class)->import($this->sampleSnapshot());
        $this->assertDatabaseHas('interview', ['id_interview' => 70]);
        $this->assertDatabaseHas('answer', ['id_submission' => 60]);

        // Pusat dibersihkan: snapshot penuh berikutnya hanya berisi data master.
        $prune = ['periods', 'master_satker', 'master_indikator', 'master_sub_indikator', 'master_pertanyaan',
            'master_pembobotan', 'master_link', 'result', 'submissions', 'submission_evaluators',
            'visitasi', 'interview', 'visitasi_evaluators', 'interview_evaluators'];
        $empty = array_fill_keys($prune, []);
        $snap = ['mode' => 'full', 'prune' => $prune, 'data' => $empty + ['users' => []]];
        app(SnapshotImporter::class)->import($snap);

        foreach (['result', 'submissions', 'visitasi', 'interview', 'visitasi_evaluators', 'interview_evaluators', 'answer'] as $t) {
            $this->assertSame(0, DB::table($t)->count(), $t);
        }
    }
}
