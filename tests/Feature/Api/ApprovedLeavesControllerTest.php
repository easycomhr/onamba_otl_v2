<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LegacyLeaveTypeMapper;
use Database\Seeders\LegacyLeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApprovedLeavesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.integration_api_key', 'test-api-key');
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/approved-leaves');

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized']);
    }

    public function test_wrong_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/approved-leaves', [
            'X-Api-Key' => 'wrong-key',
        ]);

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized']);
    }

    public function test_valid_key_returns_200_with_data_structure(): void
    {
        $employee = $this->createEmployee();
        $manager = $this->createManager();
        $leave = $this->createLeaveRequest($employee, $manager, [
            'leave_type' => 'annual',
            'from_date' => '2026-03-10',
            'to_date' => '2026-03-12',
            'days' => 3,
            'reason' => 'Family trip',
        ]);

        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('month', 3)
            ->assertJsonPath('year', 2026)
            ->assertJsonPath('data.0.legacy_leavetype_id', 1)
            ->assertJsonPath('data.0.leave_type_key', 'annual')
            ->assertJsonPath('data.0.leave_type_label', LeaveRequest::LEAVE_TYPES['annual'])
            ->assertJsonPath('data.0.employee_code', $employee->employee_code)
            ->assertJsonPath('data.0.employee_name', $employee->name)
            ->assertJsonPath('data.0.department', $employee->department)
            ->assertJsonPath('data.0.position', $employee->position)
            ->assertJsonPath('data.0.from_date', '2026-03-10')
            ->assertJsonPath('data.0.to_date', '2026-03-12')
            ->assertJsonPath('data.0.days', 3)
            ->assertJsonPath('data.0.reason', 'Family trip')
            ->assertJsonPath('data.0.approved_at', $leave->approved_at?->toDateTimeString())
            ->assertJsonPath('data.0.approved_by_name', $manager->name)
            ->assertJsonPath('data.0.otlms_code', $leave->code);
    }

    public function test_returns_only_approved_leave_records(): void
    {
        $employee = $this->createEmployee();
        $manager = $this->createManager();
        $approved = $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-APPROVED-01',
            'status' => LeaveRequest::STATUS_APPROVED,
            'from_date' => '2026-03-05',
            'to_date' => '2026-03-05',
        ]);
        $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-PENDING-01',
            'status' => LeaveRequest::STATUS_PENDING,
            'approved_at' => null,
            'from_date' => '2026-03-06',
            'to_date' => '2026-03-06',
        ]);
        $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-REJECTED-01',
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_at' => null,
            'from_date' => '2026-03-07',
            'to_date' => '2026-03-07',
        ]);

        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.otlms_code', $approved->code);
    }

    public function test_filter_by_month_year_on_from_date(): void
    {
        $employee = $this->createEmployee();
        $manager = $this->createManager();
        $matching = $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-MARCH-01',
            'from_date' => '2026-03-15',
            'to_date' => '2026-03-16',
        ]);
        $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-APRIL-01',
            'from_date' => '2026-04-15',
            'to_date' => '2026-04-16',
        ]);

        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.otlms_code', $matching->code);
    }

    public function test_filter_includes_records_spanning_into_month(): void
    {
        $employee = $this->createEmployee();
        $manager = $this->createManager();
        $spanning = $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-SPAN-01',
            'from_date' => '2026-02-27',
            'to_date' => '2026-03-02',
        ]);
        $this->createLeaveRequest($employee, $manager, [
            'code' => 'LV-APRIL-ONLY-01',
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-02',
        ]);

        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.otlms_code', $spanning->code)
            ->assertJsonPath('data.0.from_date', '2026-02-27')
            ->assertJsonPath('data.0.to_date', '2026-03-02');
    }

    public function test_empty_result_returns_empty_data_array(): void
    {
        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertExactJson([
                'data' => [],
                'month' => 3,
                'year' => 2026,
            ]);
    }

    public function test_legacy_leavetype_id_annual_is_1(): void
    {
        $this->assertSame(1, LegacyLeaveTypeMapper::toLegacyId('annual'));
    }

    public function test_legacy_leavetype_id_sick_is_2(): void
    {
        $this->assertSame(2, LegacyLeaveTypeMapper::toLegacyId('sick'));
    }

    public function test_legacy_leavetype_id_personal_is_3(): void
    {
        $this->assertSame(3, LegacyLeaveTypeMapper::toLegacyId('personal'));
    }

    public function test_legacy_leavetype_id_unpaid_is_4(): void
    {
        $this->assertSame(4, LegacyLeaveTypeMapper::toLegacyId('unpaid'));
    }

    public function test_response_contains_tbluserleaves_fields(): void
    {
        $employee = $this->createEmployee();
        $manager = $this->createManager();
        $this->createLeaveRequest($employee, $manager, [
            'leave_type' => 'personal',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-21',
            'days' => 2,
            'reason' => 'Personal work',
        ]);

        $response = $this->getJson('/api/approved-leaves?month=3&year=2026', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'legacy_leavetype_id',
                    'leave_type_key',
                    'leave_type_label',
                    'employee_code',
                    'employee_name',
                    'department',
                    'position',
                    'from_date',
                    'to_date',
                    'days',
                    'reason',
                    'approved_at',
                    'approved_by_name',
                    'otlms_code',
                ]],
                'month',
                'year',
            ]);
    }

    public function test_from_legacy_id_returns_key_and_empty_string_when_not_found(): void
    {
        $this->assertSame('annual', LegacyLeaveTypeMapper::fromLegacyId(1));
        $this->assertSame('', LegacyLeaveTypeMapper::fromLegacyId(999));
    }

    public function test_legacy_leave_type_seeder_logs_mapping(): void
    {
        Log::spy();

        $this->seed(LegacyLeaveTypeSeeder::class);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Legacy leave type mapping', LegacyLeaveTypeMapper::MAPPING);
    }

    private function apiHeaders(): array
    {
        return [
            'X-Api-Key' => 'test-api-key',
        ];
    }

    private function createEmployee(): User
    {
        return User::factory()->create([
            'name' => 'Nguyen Van A',
            'employee_code' => 'EMP-10001',
            'department' => 'Engineering',
            'position' => 'Developer',
            'role' => User::ROLE_EMPLOYEE,
        ]);
    }

    private function createManager(): User
    {
        return User::factory()->create([
            'name' => 'Tran Thi B',
            'employee_code' => 'EMP-20001',
            'department' => 'Management',
            'position' => 'Manager',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    private function createLeaveRequest(User $employee, User $manager, array $attributes = []): LeaveRequest
    {
        static $sequence = 1;

        return LeaveRequest::create(array_merge([
            'user_id' => $employee->id,
            'approved_by' => $manager->id,
            'code' => sprintf('LV-202603-%02d', $sequence++),
            'leave_type' => 'annual',
            'from_date' => '2026-03-10',
            'to_date' => '2026-03-10',
            'days' => 1,
            'reason' => 'Default reason',
            'manager_note' => 'Approved',
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => '2026-03-01 08:30:00',
        ], $attributes));
    }
}
