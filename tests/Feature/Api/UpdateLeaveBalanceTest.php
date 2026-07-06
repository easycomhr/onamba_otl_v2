<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateLeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.integration_api_key' => 'test-api-key']);

        $this->user = User::factory()->create([
            'employee_code'        => 'EMP-10001',
            'annual_leave_balance' => 10,
            'other_balance'        => 5.0,
            'women_balance'        => 3.0,
            'hard_balance'         => 2.0,
            'compensation_balance'  => 1.0,
        ]);
    }

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-api-key'];
    }

    private function url(): string
    {
        return '/api/v1/users/update-leave-balances';
    }

    public function test_success_partial_update(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'               => 'EMP-10001',
            'annual_leave_balance' => 8,
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Updated successfully')
            ->assertJsonPath('data.employee_code', 'EMP-10001')
            ->assertJsonPath('data.annual_leave_balance', 8);

        $this->assertDatabaseHas('users', [
            'employee_code'        => 'EMP-10001',
            'annual_leave_balance' => 8,
            'other_balance'        => 5.0,
        ]);
    }

    public function test_success_all_fields(): void
    {
        $payload = [
            'employee_code'               => 'EMP-10001',
            'annual_leave_balance' => 12,
            'other_balance'        => 6.0,
            'women_balance'        => 4.0,
            'hard_balance'         => 3.0,
            'compensation_balance'  => 2.5,
        ];

        $response = $this->patchJson($this->url(), $payload, $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.annual_leave_balance', 12)
            ->assertJsonPath('data.other_balance', 6)
            ->assertJsonPath('data.women_balance', 4)
            ->assertJsonPath('data.hard_balance', 3)
            ->assertJsonPath('data.compensation_balance', 2.5);
    }

    public function test_success_zero_value(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'               => 'EMP-10001',
            'annual_leave_balance' => 0,
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.annual_leave_balance', 0);

        $this->assertDatabaseHas('users', [
            'employee_code'        => 'EMP-10001',
            'annual_leave_balance' => 0,
        ]);
    }

    public function test_error_user_not_found(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'               => 'EMP-NONEXISTENT',
            'annual_leave_balance' => 5,
        ], $this->apiHeaders());

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_error_negative_value(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'      => 'EMP-10001',
            'other_balance' => -1,
        ], $this->apiHeaders());

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_error_no_fields(): void
    {
        $response = $this->patchJson($this->url(), ['employee_code' => 'EMP-10001'], $this->apiHeaders());

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No updatable fields provided');
    }

    public function test_error_missing_employee_code(): void
    {
        $response = $this->patchJson($this->url(), ['other_balance' => 5.0], $this->apiHeaders());

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The employee_code field is required.');
    }

    public function test_error_invalid_api_key(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'      => 'EMP-10001',
            'other_balance' => 5.0,
        ]);

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized']);
    }

    public function test_error_non_numeric_value(): void
    {
        $response = $this->patchJson($this->url(), [
            'employee_code'      => 'EMP-10001',
            'other_balance' => 'abc',
        ], $this->apiHeaders());

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }
}
