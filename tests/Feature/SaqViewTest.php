<?php

namespace Tests\Feature;

use App\Helpers\SecureId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class SaqViewTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    private function seedQuestionnaire(): void
    {
        DB::table('master_indikator')->insert(['id_indicators' => 1, 'nama_indikator' => 'Indikator Kelembagaan']);
        DB::table('master_sub_indikator')->insert(['id_sub_indicators' => 1, 'id_indicators' => 1, 'nama_sub_indikator' => 'Sub Struktur']);
        DB::table('master_pertanyaan')->insert([
            ['id_master_pertanyaan' => 1, 'order_no' => 1, 'id_sub_indicators' => 1, 'id_indicators' => 1, 'ques_text' => 'Apakah ada SOP layanan?'],
            ['id_master_pertanyaan' => 2, 'order_no' => 2, 'id_sub_indicators' => 1, 'id_indicators' => 1, 'ques_text' => 'Apakah ada daftar informasi publik?'],
        ]);
        DB::table('answer')->insert([
            ['id_answer' => '33333333-3333-3333-3333-333333333331', 'id_submission' => 20, 'id_question' => 1, 'id_indicators' => 1, 'answer' => 1, 'link_drive' => 'https://drive.example.test/sop'],
            ['id_answer' => '33333333-3333-3333-3333-333333333332', 'id_submission' => 20, 'id_question' => 2, 'id_indicators' => 1, 'answer' => 1, 'link_ppid' => 'javascript:alert(1)'],
        ]);
    }

    private function url(int $id = 20): string
    {
        return '/penilai-eksternal/review-kues/'.SecureId::encrypt($id);
    }

    public function test_assigned_user_sees_answers_read_only(): void
    {
        $this->seedPortal();
        $this->seedQuestionnaire();

        $this->actingAs(User::find(100))->get($this->url())
            ->assertOk()
            ->assertSee('Apakah ada SOP layanan?')
            ->assertSee('Indikator Kelembagaan')
            ->assertSee('href="https://drive.example.test/sop"', false)
            ->assertDontSee('<textarea', false)
            ->assertDontSee('Kirim penilaian');
    }

    public function test_unsafe_scheme_is_not_rendered_as_link(): void
    {
        $this->seedPortal();
        $this->seedQuestionnaire();

        $this->actingAs(User::find(100))->get($this->url())
            ->assertOk()
            ->assertDontSee('href="javascript:', false);
    }

    public function test_unassigned_user_gets_403(): void
    {
        $this->seedPortal();
        $this->seedQuestionnaire();

        $this->actingAs(User::find(101))->get($this->url())->assertForbidden();
    }

    public function test_submission_not_yet_verified_is_forbidden(): void
    {
        $this->seedPortal(['status' => 2]);

        $this->actingAs(User::find(100))->get($this->url())->assertForbidden();
    }

    public function test_there_is_no_route_to_write_saq(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->post('/penilai-eksternal/review-kues/save', ['id_submission' => 20])->assertStatus(405);
        $this->actingAs(User::find(100))->post($this->url(), [])->assertStatus(405);
    }

    public function test_monitor_lists_assigned_submission(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->get('/penilai-eksternal/monitor-kues')->assertOk()->assertSee('BPS Kabupaten Contoh');
        $this->actingAs(User::find(101))->get('/penilai-eksternal/monitor-kues')->assertOk()->assertDontSee('BPS Kabupaten Contoh');
    }
}
