<?php

namespace Tests\Feature\Api;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApprovedOtControllerTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INTEGRATION_API_KEY=' . self::API_KEY);
        config()->set('services.integration_api_key', self::API_KEY);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/approved-ot');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_wrong_api_key_returns_401(): void
    {
        $response = $this->withHeaders([
            'X-Api-Key' => 'wrong-key',
        ])->getJson('/api/approved-ot');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_valid_key_returns_200_with_data_structure(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager(['name' => 'Approver']);
        $record = $this->makeOtRequest($employee, $approver, [
            'ot_date' => now()->toDateString(),
        ]);

        $response = $this->apiGet('/api/approved-ot');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'month',
                'year',
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($record->ot_date->month, $response->json('month'));
        $this->assertSame($record->ot_date->year, $response->json('year'));
    }

    public function test_returns_only_approved_ot_records(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager();

        $approved = $this->makeOtRequest($employee, $approver, [
            'ot_date' => '2026-02-10',
            'status' => OtRequest::STATUS_APPROVED,
        ]);
        $this->makeOtRequest($employee, $approver, [
            'ot_date' => '2026-02-11',
            'status' => OtRequest::STATUS_PENDING,
            'approved_hours' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $this->makeOtRequest($employee, $approver, [
            'ot_date' => '2026-02-12',
            'status' => OtRequest::STATUS_REJECTED,
            'approved_hours' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $response = $this->apiGet('/api/approved-ot?month=2&year=2026');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($approved->employee->employee_code, $response->json('data.0.employee_code'));
        $this->assertSame('2026-02-10', $response->json('data.0.ot_date'));
    }

    public function test_filter_by_month_year_returns_correct_records(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager();

        $matchingOne = $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-01-05']);
        $matchingTwo = $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-01-20']);
        $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-02-01']);

        $response = $this->apiGet('/api/approved-ot?month=1&year=2026');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $dates = collect($response->json('data'))->pluck('ot_date')->all();
        $this->assertEqualsCanonicalizing([
            $matchingOne->ot_date->toDateString(),
            $matchingTwo->ot_date->toDateString(),
        ], $dates);
    }

    public function test_records_outside_month_year_not_returned(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager();

        $this->makeOtRequest($employee, $approver, ['ot_date' => '2025-03-10']);
        $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-04-10']);
        $matching = $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-03-15']);

        $response = $this->apiGet('/api/approved-ot?month=3&year=2026');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($matching->ot_date->toDateString(), $response->json('data.0.ot_date'));
    }

    public function test_empty_month_returns_empty_data_array(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager();

        $this->makeOtRequest($employee, $approver, ['ot_date' => '2026-01-10']);

        $response = $this->apiGet('/api/approved-ot?month=12&year=2026');

        $response->assertOk()
            ->assertJson([
                'data' => [],
                'month' => 12,
                'year' => 2026,
            ]);
    }

    public function test_default_month_year_when_params_omitted(): void
    {
        $employee = $this->makeEmployee();
        $approver = $this->makeManager();
        $now = now();

        $this->makeOtRequest($employee, $approver, ['ot_date' => $now->toDateString()]);
        $this->makeOtRequest($employee, $approver, ['ot_date' => $now->copy()->subMonth()->toDateString()]);

        $response = $this->apiGet('/api/approved-ot');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'month', 'year']);
        $this->assertSame($now->month, $response->json('month'));
        $this->assertSame($now->year, $response->json('year'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_response_contains_correct_field_mapping(): void
    {
        $employee = $this->makeEmployee([
            'name' => 'Nguyen Van A',
            'employee_code' => 'EMP001',
            'department' => 'IT',
            'position' => 'Developer',
        ]);
        $approver = $this->makeManager(['name' => 'Tran Thi B']);

        $record = $this->makeOtRequest($employee, $approver, [
            'ot_date' => '2026-02-15',
            'hours' => 3.5,
            'approved_hours' => 3.0,
            'approved_at' => '2026-02-16 08:30:00',
        ]);

        $response = $this->apiGet('/api/approved-ot?month=2&year=2026');

        $response->assertOk();
        $response->assertJsonPath('data.0.employee_code', 'EMP001');
        $response->assertJsonPath('data.0.employee_name', 'Nguyen Van A');
        $response->assertJsonPath('data.0.department', 'IT');
        $response->assertJsonPath('data.0.position', 'Developer');
        $response->assertJsonPath('data.0.ot_date', '2026-02-15');
        $this->assertEquals((float) $record->hours, $response->json('data.0.hours'));
        $this->assertEquals((float) $record->approved_hours, $response->json('data.0.approved_hours'));
        $response->assertJsonPath('data.0.approved_at', $record->approved_at->toDateTimeString());
        $response->assertJsonPath('data.0.approved_by_name', 'Tran Thi B');
    }

    public function test_returns_500_json_when_unexpected_error_occurs(): void
    {
        Log::spy();
        Schema::drop('ot_requests');

        $response = $this->apiGet('/api/approved-ot');

        $response->assertStatus(500)
            ->assertJson(['message' => 'Lỗi hệ thống']);

        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context): bool {
            return $message === 'Failed to fetch approved OT records.'
                && isset($context['error'], $context['exception']);
        });
    }

    private function apiGet(string $uri)
    {
        return $this->withHeaders([
            'X-Api-Key' => self::API_KEY,
        ])->getJson($uri);
    }

    private function makeEmployee(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_EMPLOYEE,
            'department' => 'IT',
            'position' => 'Staff',
        ], $attributes));
    }

    private function makeManager(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_MANAGER,
            'department' => 'Management',
            'position' => 'Manager',
        ], $attributes));
    }

    private function makeOtRequest(User $employee, User $approver, array $attributes = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'user_id' => $employee->id,
            'approved_by' => $approver->id,
            'ot_date' => now()->toDateString(),
            'hours' => 2.0,
            'approved_hours' => 2.0,
            'reason' => 'Test OT',
            'status' => OtRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ], $attributes));
    }
}
