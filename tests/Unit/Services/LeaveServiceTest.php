<?php

namespace Tests\Unit\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveService();
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);
    }

    public function test_store_creates_leave_request_with_pending_status(): void
    {
        $leaveRequest = $this->service->store($this->validData(), $this->employee);

        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->status);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    public function test_store_returns_leave_request_with_correct_attributes(): void
    {
        $leaveRequest = $this->service->store([
            'leave_type' => 'sick',
            'from_date' => '2026-03-17',
            'to_date' => '2026-03-17',
            'reason' => 'Medical checkup',
        ], $this->employee);

        $this->assertInstanceOf(LeaveRequest::class, $leaveRequest);
        $this->assertNotNull($leaveRequest->id);
        $this->assertSame($this->employee->id, $leaveRequest->user_id);
        $this->assertSame('sick', $leaveRequest->leave_type);
        $this->assertSame('2026-03-17', $leaveRequest->from_date->toDateString());
        $this->assertSame('2026-03-17', $leaveRequest->to_date->toDateString());
        $this->assertSame('Medical checkup', $leaveRequest->reason);
        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->status);
    }

    public function test_store_does_not_set_days_manually(): void
    {
        $leaveRequest = $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-18',
            'reason' => 'Family trip',
        ], $this->employee);

        $this->assertSame(3, $leaveRequest->days);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'days' => 3,
        ]);
    }

    public function test_store_sets_code_automatically(): void
    {
        $leaveRequest = $this->service->store($this->validData(), $this->employee);

        $this->assertNotEmpty($leaveRequest->code);
        $this->assertMatchesRegularExpression('/^LV-\d{6}-\d{2}$/', $leaveRequest->code);
    }

    public function test_store_throws_exception_when_exact_overlap(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-22');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Duplicate leave: user_id=' . $this->employee->id . ' from=2026-03-20 to=2026-03-22'
        );

        $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
            'reason' => 'Duplicate exact range',
        ], $this->employee);
    }

    public function test_store_throws_exception_when_partial_overlap_left(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-24');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-18',
            'to_date' => '2026-03-22',
            'reason' => 'Overlap on left edge',
        ], $this->employee);
    }

    public function test_store_throws_exception_when_partial_overlap_right(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-24');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-22',
            'to_date' => '2026-03-26',
            'reason' => 'Overlap on right edge',
        ], $this->employee);
    }

    public function test_store_throws_exception_when_contains_existing(): void
    {
        $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-22');

        $this->expectException(\RuntimeException::class);

        $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-18',
            'to_date' => '2026-03-24',
            'reason' => 'Contains existing leave',
        ], $this->employee);
    }

    public function test_store_allows_adjacent_dates_no_overlap(): void
    {
        $first = $this->makeLeaveRequest($this->employee, '2026-03-20', '2026-03-22');

        $second = $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-23',
            'to_date' => '2026-03-24',
            'reason' => 'Adjacent leave request',
        ], $this->employee);

        $this->assertSame($this->employee->id, $first->user_id);
        $this->assertSame($this->employee->id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_different_user_same_dates_is_allowed(): void
    {
        $otherEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);

        $first = $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-26',
            'reason' => 'Employee one leave',
        ], $this->employee);

        $second = $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-26',
            'reason' => 'Employee two leave',
        ], $otherEmployee);

        $this->assertNotSame($first->user_id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_same_user_non_overlapping_dates_is_allowed(): void
    {
        $first = $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-03-10',
            'to_date' => '2026-03-11',
            'reason' => 'First leave request',
        ], $this->employee);

        $second = $this->service->store([
            'leave_type' => 'personal',
            'from_date' => '2026-03-15',
            'to_date' => '2026-03-16',
            'reason' => 'Second leave request',
        ], $this->employee);

        $this->assertSame($this->employee->id, $first->user_id);
        $this->assertSame($this->employee->id, $second->user_id);
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_store_dispatches_job_when_manager_has_zalo_user_id(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'zalo_user_id' => 'zalo_mgr_001',
        ]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'manager_id' => $manager->id,
        ]);

        $this->service->store([
            'leave_type' => 'annual',
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-02',
            'reason' => 'Spring break',
        ], $employee);

        Queue::assertPushed(SendZaloNotificationJob::class, function ($job) use ($manager, $employee) {
            return $job->zaloUserId === 'zalo_mgr_001'
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
            'leave_type' => 'sick',
            'from_date' => '2026-04-03',
            'to_date' => '2026-04-03',
            'reason' => 'Sick day',
        ], $employee);

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
            'leave_type' => 'personal',
            'from_date' => '2026-04-05',
            'to_date' => '2026-04-05',
            'reason' => 'Personal errand',
        ], $employee);

        Queue::assertNotPushed(SendZaloNotificationJob::class);
    }

    private function validData(): array
    {
        return [
            'leave_type' => 'annual',
            'from_date' => '2026-03-16',
            'to_date' => '2026-03-16',
            'reason' => 'Personal work',
        ];
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
