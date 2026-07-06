<?php

namespace Tests\Feature\Admin;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Nguyen Van A',
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'department' => 'IT',
            'annual_leave_balance' => 8,
        ]);
    }

    public function test_guest_redirected_to_login_on_create(): void
    {
        $response = $this->get(route('admin.register.leave.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_create(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.register.leave.create'));

        $response->assertForbidden();
    }

    public function test_create_page_returns_200_for_manager(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.leave.create'));

        $response->assertOk();
        $response->assertViewIs('admin.register.leave');
        $response->assertViewHas('leaveTypes', LeaveRequest::LEAVE_TYPES);
    }

    public function test_store_creates_leave_request_and_redirects_to_approval_list(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-17',
                'reason' => 'Family event',
            ]);

        $response->assertRedirect(route('admin.approvals.leave.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'reason' => 'Family event',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $leaveRequest = LeaveRequest::firstOrFail();
        $this->assertEquals('2026-03-16', $leaveRequest->from_date->toDateString());
        $this->assertEquals('2026-03-17', $leaveRequest->to_date->toDateString());
    }

    public function test_store_validation_fails_missing_employee_id(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-17',
                'reason' => 'Family event',
            ]);

        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_store_validation_fails_missing_from_date(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'to_date' => '2026-03-17',
                'reason' => 'Family event',
            ]);

        $response->assertSessionHasErrors(['from_date']);
    }

    public function test_store_validation_fails_to_date_before_from_date(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-17',
                'to_date' => '2026-03-16',
                'reason' => 'Family event',
            ]);

        $response->assertSessionHasErrors(['to_date']);
    }

    public function test_store_validation_fails_invalid_leave_type(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'bereavement',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-17',
                'reason' => 'Family event',
            ]);

        $response->assertSessionHasErrors(['leave_type']);
    }

    public function test_store_rejects_overlapping_leave_for_same_employee(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
        ]);

        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-22',
                'to_date' => '2026-03-24',
                'reason' => 'Overlapping leave attempt',
            ]);

        $response->assertSessionHasErrors(['from_date']);
        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_store_allows_adjacent_non_overlapping_leave_same_employee(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-23',
                'to_date' => '2026-03-24',
                'reason' => 'Adjacent leave request',
            ]);

        $response->assertRedirect(route('admin.approvals.leave.index'));
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_allows_annual_leave_within_remaining_balance(): void
    {
        $this->employee->update(['annual_leave_balance' => 6]);

        $response = $this->actingAs($this->manager)
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-23',
                'reason' => 'Family trip',
            ]);

        $response->assertRedirect(route('admin.approvals.leave.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'reason' => 'Family trip',
        ]);
    }

    public function test_store_rejects_annual_leave_exceeding_remaining_balance(): void
    {
        $this->employee->update(['annual_leave_balance' => 6]);

        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.leave.create'))
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-24',
                'reason' => 'Long vacation',
            ]);

        $response->assertSessionHasErrors([
            'from_date' => 'Số ngày nghỉ phép vượt quỹ phép còn lại (6 ngày).',
        ]);
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_store_ignores_remaining_balance_for_non_annual_leave(): void
    {
        $this->employee->update(['annual_leave_balance' => 0]);

        $response = $this->actingAs($this->manager)
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'personal',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-24',
                'reason' => 'Personal matters',
            ]);

        $response->assertRedirect(route('admin.approvals.leave.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'personal',
            'reason' => 'Personal matters',
        ]);
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $response = $this->post(route('admin.register.leave.store'), [
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-17',
            'reason' => 'Family event',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_store(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('admin.register.leave.store'), [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-17',
                'reason' => 'Family event',
            ]);

        $response->assertForbidden();
    }

    public function test_employees_search_returns_json_with_data_key(): void
    {
        $matchingEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Tran Thi B',
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'department' => 'HR',
        ]);

        User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'name' => 'Manager Match',
            'employee_code' => fake()->unique()->numerify('MGR#####'),
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.leave.employees', ['search' => $matchingEmployee->employee_code]));

        $response->assertOk();
        $response->assertJsonStructure(['data', 'next_page_url']);
        $response->assertJsonFragment([
            'id' => $matchingEmployee->id,
            'name' => 'Tran Thi B',
            'employee_code' => $matchingEmployee->employee_code,
            'department' => 'HR',
        ]);
    }

    public function test_employees_search_requires_auth(): void
    {
        $response = $this->get(route('admin.register.leave.employees', ['search' => $this->employee->employee_code]));

        $response->assertRedirect(route('login'));
    }

    public function test_employees_search_filters_by_name(): void
    {
        $matchingEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Pham Thi Filter',
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'department' => 'Finance',
        ]);

        User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Unmatched Person',
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'department' => 'Finance',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.leave.employees', ['search' => 'Pham Thi']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matchingEmployee->id);
        $response->assertJsonPath('data.0.name', 'Pham Thi Filter');
    }

    public function test_employees_search_filters_by_employee_code(): void
    {
        $matchingEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Code Match',
            'employee_code' => 'EMP-CODE-123',
            'department' => 'Support',
        ]);

        User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Other Employee',
            'employee_code' => 'EMP-CODE-999',
            'department' => 'Support',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.leave.employees', ['search' => 'EMP-CODE-123']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matchingEmployee->id);
        $response->assertJsonPath('data.0.employee_code', 'EMP-CODE-123');
    }

    private function makeLeaveRequest(User $employee, array $attrs = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-20',
            'reason' => 'Existing leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ], $attrs));
    }
}
