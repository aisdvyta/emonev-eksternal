<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class SyncSignatureTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    public function test_valid_signature_is_accepted(): void
    {
        $this->signedCall('GET', '/api/v1/ping')->assertOk()->assertJson(['ok' => true, 'api_version' => 1]);
    }

    public function test_missing_headers_are_rejected(): void
    {
        $this->signedCall('GET', '/api/v1/ping', null, ['omit' => ['HTTP_X_SYNC_SIGNATURE']])
            ->assertStatus(401)->assertJson(['error' => 'bad_headers']);
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $this->signedCall('GET', '/api/v1/ping', null, ['secret' => 'rahasia-salah'])
            ->assertStatus(401)->assertJson(['error' => 'bad_signature']);
    }

    public function test_tampered_body_is_rejected(): void
    {
        $this->signedCall('POST', '/api/v1/sync/snapshot', ['data' => []], ['sign_body' => '{"data":{"users":[]}}'])
            ->assertStatus(401)->assertJson(['error' => 'bad_signature']);
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $this->signedCall('GET', '/api/v1/ping', null, ['ts' => time() - 3600])
            ->assertStatus(401)->assertJson(['error' => 'stale_timestamp']);
    }

    public function test_replayed_nonce_is_rejected(): void
    {
        $nonce = bin2hex(random_bytes(16));

        $this->signedCall('GET', '/api/v1/ping', null, ['nonce' => $nonce])->assertOk();
        $this->signedCall('GET', '/api/v1/ping', null, ['nonce' => $nonce])
            ->assertStatus(401)->assertJson(['error' => 'replayed_nonce']);
    }

    public function test_previous_secret_is_still_accepted_during_rotation(): void
    {
        config(['external.sync.secrets' => ['rahasia-baru-rahasia-baru', 'test-secret-value']]);

        $this->signedCall('GET', '/api/v1/ping', null, ['secret' => 'test-secret-value'])->assertOk();
        $this->signedCall('GET', '/api/v1/ping', null, ['secret' => 'rahasia-baru-rahasia-baru'])->assertOk();
    }

    public function test_not_configured_returns_503(): void
    {
        config(['external.sync.secrets' => []]);

        $this->signedCall('GET', '/api/v1/ping')->assertStatus(503)->assertJson(['error' => 'sync_not_configured']);
    }

    public function test_ip_allowlist_blocks_other_addresses(): void
    {
        config(['external.sync.allowed_ips' => ['203.0.113.9']]);

        $this->signedCall('GET', '/api/v1/ping')->assertStatus(403)->assertJson(['error' => 'ip_not_allowed']);
    }

    public function test_api_is_not_reachable_without_signature(): void
    {
        $this->getJson('/api/v1/sync/scores')->assertStatus(401);
        $this->postJson('/api/v1/sync/snapshot', ['data' => []])->assertStatus(401);
    }
}
