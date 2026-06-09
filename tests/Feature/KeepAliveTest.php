<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeepAliveTest extends TestCase
{
    use RefreshDatabase;

    public function test_keep_alive_returns_ok_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/keep-alive');

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_keep_alive_redirects_unauthenticated_user(): void
    {
        $response = $this->getJson('/keep-alive');

        $response->assertUnauthorized();
    }

    public function test_keep_alive_touches_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/keep-alive');

        $response->assertOk();
        $this->assertNotNull(session('last_keep_alive'));
    }
}
