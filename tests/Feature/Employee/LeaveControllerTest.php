<?php

namespace Tests\Feature\Employee;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'annual_leave_balance' => 8,
        ]);

        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_code' => fake()->unique()->numerify('MGR#####'),
        ]);
    }

    public function test_guest_redirected_to_login_on_index(): void
    {
        $this->get(route('employee.leave.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_create(): void
    {
        $this->get(route('employee.leave.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $this->post(route('employee.leave.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_manager_cannot_access_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('employee.leave.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_create(): void
    {
        $this->actingAs($this->manager)
            ->get(route('employee.leave.create'))
            ->assertForbidden();
    }

    public function test_manager_cannot_store(): void
    {
        $this->actingAs($this->manager)
            ->post(route('employee.leave.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_employee_index_returns_200_with_correct_view(): void
    {
        $leaveRequest = $this->makeLeaveRequest($this->employee, [
            'leave_type' => 'personal',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-21',
            'reason' => 'Personal errand',
        ]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.leave.index'));

        $response->assertOk()
            ->assertViewIs('employee.leave.list')
            ->assertViewHas('leaveBalance', 8)
            ->assertViewHas('requests', function ($requests) use ($leaveRequest) {
                $this->assertCount(1, $requests);
                $this->assertSame($leaveRequest->id, $requests->first()->id);

                return true;
            });
    }

    public function test_employee_create_returns_200_with_correct_view(): void
    {
        $this->actingAs($this->employee)
            ->get(route('employee.leave.create'))
            ->assertOk()
            ->assertViewIs('employee.leave.register')
            ->assertViewHas('leaveBalance', 8);
    }

    public function test_employee_store_creates_leave_request_and_redirects_to_index(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('employee.leave.store'), $this->validPayload());

        $response->assertRedirect(route('employee.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'reason' => 'Family event',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    public function test_employee_store_flashes_success_message(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('employee.leave.store'), $this->validPayload());

        $response->assertRedirect(route('employee.leave.index'))
            ->assertSessionHas('success', 'Đơn xin nghỉ đã được gửi. Đang chờ duyệt.');
    }

    public function test_store_validation_fails_missing_leave_type(): void
    {
        $payload = $this->validPayload();
        unset($payload['leave_type']);

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['leave_type']);
    }

    public function test_store_validation_fails_invalid_leave_type(): void
    {
        $payload = $this->validPayload();
        $payload['leave_type'] = 'bereavement';

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['leave_type']);
    }

    public function test_store_validation_fails_missing_from_date(): void
    {
        $payload = $this->validPayload();
        unset($payload['from_date']);

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['from_date']);
    }

    public function test_store_validation_fails_to_date_before_from_date(): void
    {
        $payload = $this->validPayload();
        $payload['from_date'] = '2026-03-18';
        $payload['to_date'] = '2026-03-17';

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['to_date']);
    }

    public function test_store_validation_fails_missing_reason(): void
    {
        $payload = $this->validPayload();
        unset($payload['reason']);

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['reason']);
    }

    public function test_store_validation_fails_overlap(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
        ]);

        $payload = $this->validPayload();
        $payload['from_date'] = '2026-03-22';
        $payload['to_date'] = '2026-03-24';

        $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), $payload)
            ->assertSessionHasErrors(['from_date']);

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_store_allows_annual_leave_within_remaining_balance(): void
    {
        $this->employee->update(['annual_leave_balance' => 6]);

        $response = $this->actingAs($this->employee)
            ->post(route('employee.leave.store'), [
                'leave_type' => 'annual',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-23',
                'reason' => 'Family trip',
            ]);

        $response->assertRedirect(route('employee.leave.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'reason' => 'Family trip',
        ]);
    }

    public function test_store_rejects_annual_leave_exceeding_remaining_balance(): void
    {
        $this->employee->update(['annual_leave_balance' => 6]);

        $response = $this->actingAs($this->employee)
            ->from(route('employee.leave.create'))
            ->post(route('employee.leave.store'), [
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

        $response = $this->actingAs($this->employee)
            ->post(route('employee.leave.store'), [
                'leave_type' => 'sick',
                'from_date' => '2026-03-16',
                'to_date' => '2026-03-24',
                'reason' => 'Medical treatment',
            ]);

        $response->assertRedirect(route('employee.leave.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'sick',
            'reason' => 'Medical treatment',
        ]);
    }

    public function test_store_service_exception_returns_error_response(): void
    {
        $service = $this->mock(LeaveService::class);
        $service->shouldReceive('store')
            ->once()
            ->andThrow(new \RuntimeException('Service failure'));

        $response = $this->from(route('employee.leave.create'))
            ->actingAs($this->employee)
            ->post(route('employee.leave.store'), $this->validPayload());

        $response->assertRedirect(route('employee.leave.create'))
            ->assertSessionHasErrors(['error']);
    }

    private function validPayload(): array
    {
        return [
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-17',
            'reason' => 'Family event',
        ];
    }

    private function makeLeaveRequest(User $user, array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id' => $user->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-17',
            'reason' => 'Existing leave request',
            'status' => LeaveRequest::STATUS_PENDING,
        ], $overrides));
    }
}
