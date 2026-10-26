<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class PagesSmokeTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    public function test_login_page_renders_like_pusat(): void
    {
        $this->get('/')->assertOk()->assertSee('Silahkan, Masuk!', false);
    }

    public function test_every_external_page_renders(): void
    {
        $ids = $this->seedPortal();
        $this->actingAs(User::find(100));
        $bad = [];

        foreach ([
            '/penilai-eksternal',
            '/penilai-eksternal/monitor-kues',
            '/penilai-eksternal/monitor-visitasi',
            '/penilai-eksternal/monitor-wawancara',
            '/penilai-eksternal/penilaian',
            $this->visitUrl($ids['visit']),
            $this->interviewUrl($ids['interview']),
            '/penilai-eksternal/review-kues/'.\App\Helpers\SecureId::encrypt($ids['submission']),
            '/profil',
        ] as $url) {
            $res = $this->get($url);
            $msg = $res->exception ? substr($res->exception->getMessage(), 0, 300).' @ '.basename($res->exception->getFile()).':'.$res->exception->getLine() : '';
            $bad[] = in_array($res->getStatusCode(), [200], true) ? null : "{$url} -> {$res->getStatusCode()} {$msg}";
        }
        $this->assertSame([], array_values(array_filter($bad)));
    }
}
