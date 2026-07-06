<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        config(['jwt.secret' => 'test-secret-key-at-least-32-chars-long-for-hs256']);

        $this->user = User::factory()->create([
            'employee_code' => 'EMP001',
            'password' => 'secret123',
        ]);
    }

    public function test_login_success(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'EMP001',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user_info',
                '_token',
            ]);

        $this->assertIsArray($response->json('user_info'));
        $this->assertNotEmpty($response->json('_token'));
    }

    public function test_login_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'EMP001',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_wrong_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'NOEXIST',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_missing_userid(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'EMP001',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_password_too_long(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'EMP001',
            'password' => str_repeat('a', 101),
        ]);

        $response->assertStatus(422);
    }

    public function test_user_info_is_array(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'userid' => 'EMP001',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);

        $userInfo = $response->json('user_info');
        $this->assertIsArray($userInfo);
        $this->assertArrayHasKey(0, $userInfo);
    }
}
