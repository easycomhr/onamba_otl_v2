<?php

namespace Tests\Unit\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeaveRegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveRegisterService $service;
    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveRegisterService();
        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);
    }

    public function test_store_creates_leave_request_with_pending_status(): void
    {
        $leaveRequest = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-16',
            'reason' => 'Personal work',
        ]);

        $this->assertEquals(LeaveRequest::STATUS_PENDING, $leaveRequest->status);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    public function test_store_returns_created_leave_request_with_correct_attributes(): void
    {
        $leaveRequest = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'sick',
            'from_date' => '2026-03-17',
            'to_date' => '2026-03-17',
            'reason' => 'Medical checkup',
        ]);

        $this->assertInstanceOf(LeaveRequest::class, $leaveRequest);
        $this->assertNotNull($leaveRequest->id);
        $this->assertEquals($this->employee->id, $leaveRequest->user_id);
        $this->assertEquals('sick', $leaveRequest->leave_type);
        $this->assertEquals('2026-03-17', $leaveRequest->from_date->toDateString());
        $this->assertEquals('2026-03-17', $leaveRequest->to_date->toDateString());
        $this->assertEquals('Medical checkup', $leaveRequest->reason);
        $this->assertEquals(LeaveRequest::STATUS_PENDING, $leaveRequest->status);
    }

    public function test_store_model_auto_sets_days_without_service_passing_it(): void
    {
        $leaveRequest = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-18',
            'reason' => 'Family trip',
        ]);

        $this->assertSame(3, $leaveRequest->days);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'days' => 3,
        ]);
    }

    public function test_store_throws_runtime_exception_on_exact_date_overlap(): void
    {
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
            'reason' => 'Existing leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Duplicate leave: user_id=' . $this->employee->id . ' 2026-03-20~2026-03-22'
        );

        $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
            'reason' => 'Duplicate exact range',
        ]);
    }

    public function test_store_throws_on_partial_overlap_when_new_start_is_within_existing_range(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-24');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-22',
            'to_date' => '2026-03-26',
            'reason' => 'Start overlaps existing leave',
        ]);
    }

    public function test_store_throws_on_partial_overlap_when_new_end_is_within_existing_range(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-24');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-18',
            'to_date' => '2026-03-22',
            'reason' => 'End overlaps existing leave',
        ]);
    }

    public function test_store_throws_on_spanning_overlap_when_new_range_encloses_existing_range(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-22');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-18',
            'to_date' => '2026-03-24',
            'reason' => 'Spanning overlap',
        ]);
    }

    public function test_store_allows_adjacent_non_overlapping_ranges(): void
    {
        $first = $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-22');

        $second = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-23',
            'to_date' => '2026-03-24',
            'reason' => 'Adjacent leave request',
        ]);

        $this->assertEquals($this->employee->id, $first->user_id);
        $this->assertEquals($this->employee->id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_allows_same_range_for_different_employees(): void
    {
        $employeeTwo = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);

        $first = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-26',
            'reason' => 'Employee one leave',
        ]);

        $second = $this->service->store([
            'employee_id' => $employeeTwo->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-26',
            'reason' => 'Employee two leave',
        ]);

        $this->assertNotEquals($first->user_id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_allows_different_non_overlapping_ranges_for_same_employee(): void
    {
        $first = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-10',
            'to_date' => '2026-03-11',
            'reason' => 'First leave request',
        ]);

        $second = $this->service->store([
            'employee_id' => $this->employee->id,
            'leave_type' => 'personal',
            'from_date' => '2026-03-15',
            'to_date' => '2026-03-16',
            'reason' => 'Second leave request',
        ]);

        $this->assertEquals($this->employee->id, $first->user_id);
        $this->assertEquals($this->employee->id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_dispatches_job_when_manager_has_zalo_user_id(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'zalo_user_id' => 'zalo_mgr_reg_001',
        ]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'manager_id' => $manager->id,
        ]);

        $this->service->store([
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-02',
            'reason' => 'Spring break',
        ]);

        Queue::assertPushed(SendZaloNotificationJob::class, function ($job) use ($manager, $employee) {
            return $job->zaloUserId === 'zalo_mgr_reg_001'
                && str_contains($job->message, $employee->name)
                && str_contains($job->message, $employee->employee_code)
                && str_contains($job->message, '2026-04-01')
                && str_contains($job->message, '2026-04-02')
                && str_contains($job->message, 'annual')
                && str_contains($job->message, 'Chờ duyệt');
        });
    }

    public function test_store_does_not_dispatch_job_when_employee_has_no_manager(): void
    {
        Queue::fake();

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'manager_id' => null,
        ]);

        $this->service->store([
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'from_date' => '2026-04-03',
            'to_date' => '2026-04-03',
            'reason' => 'Sick day',
        ]);

        Queue::assertNotPushed(SendZaloNotificationJob::class);
    }

    public function test_store_does_not_dispatch_job_when_manager_has_null_zalo_user_id(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'zalo_user_id' => null,
        ]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'manager_id' => $manager->id,
        ]);

        $this->service->store([
            'employee_id' => $employee->id,
            'leave_type' => 'personal',
            'from_date' => '2026-04-05',
            'to_date' => '2026-04-05',
            'reason' => 'Personal errand',
        ]);

        Queue::assertNotPushed(SendZaloNotificationJob::class);
    }

    private function makeLeaveRequest(User $employee, string $fromDate, string $toDate): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'reason' => 'Existing leave request',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }
}
