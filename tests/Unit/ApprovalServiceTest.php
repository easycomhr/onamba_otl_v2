<?php

namespace Tests\Unit;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalService $service;
    private User $approver;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ApprovalService();
        $this->approver = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_code' => fake()->unique()->numerify('MGR#####'),
        ]);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'annual_leave_balance' => 12,
        ]);
    }

    public function test_approve_ot_sets_approved_fields(): void
    {
        $ot = $this->makeOtRequest(status: OtRequest::STATUS_PENDING);

        $this->service->approveOt($ot, $this->approver, [
            'approved_hours' => 3.5,
            'manager_note' => 'Approved for release support',
        ]);

        $ot->refresh();

        $this->assertSame(OtRequest::STATUS_APPROVED, $ot->status);
        $this->assertSame($this->approver->id, $ot->approved_by);
        $this->assertSame('3.5', $ot->approved_hours);
        $this->assertSame('Approved for release support', $ot->manager_note);
        $this->assertNotNull($ot->approved_at);
    }

    public function test_reject_ot_sets_rejected_status(): void
    {
        $ot = $this->makeOtRequest(status: OtRequest::STATUS_PENDING);

        $this->service->rejectOt($ot, $this->approver, 'Not enough justification');

        $ot->refresh();

        $this->assertSame(OtRequest::STATUS_REJECTED, $ot->status);
        $this->assertSame($this->approver->id, $ot->approved_by);
        $this->assertSame('Not enough justification', $ot->manager_note);
        $this->assertNotNull($ot->rejected_at);
    }

    public function test_approve_ot_throws_when_request_is_not_pending(): void
    {
        $ot = $this->makeOtRequest(status: OtRequest::STATUS_APPROVED);

        $this->expectException(\RuntimeException::class);

        $this->service->approveOt($ot, $this->approver, [
            'approved_hours' => 2.0,
            'manager_note' => null,
        ]);
    }

    public function test_approve_leave_sets_approved_status_and_decrements_balance(): void
    {
        $leave = $this->makeLeaveRequest(days: 2, status: LeaveRequest::STATUS_PENDING);

        $this->service->approveLeave($leave, $this->approver, 'Approved');

        $leave->refresh();
        $this->employee->refresh();

        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leave->status);
        $this->assertSame($this->approver->id, $leave->approved_by);
        $this->assertSame('Approved', $leave->manager_note);
        $this->assertNotNull($leave->approved_at);
        $this->assertSame(10.0, $this->employee->annual_leave_balance);
    }

    public function test_reject_leave_sets_rejected_status(): void
    {
        $leave = $this->makeLeaveRequest(days: 1, status: LeaveRequest::STATUS_PENDING);

        $this->service->rejectLeave($leave, $this->approver, 'Team workload peak');

        $leave->refresh();

        $this->assertSame(LeaveRequest::STATUS_REJECTED, $leave->status);
        $this->assertSame($this->approver->id, $leave->approved_by);
        $this->assertSame('Team workload peak', $leave->manager_note);
        $this->assertNotNull($leave->rejected_at);
    }

    public function test_approve_leave_does_not_decrement_if_already_approved(): void
    {
        $leave = $this->makeLeaveRequest(days: 3, status: LeaveRequest::STATUS_APPROVED);

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->approveLeave($leave, $this->approver, 'Should fail');
        } finally {
            $leave->refresh();
            $this->employee->refresh();

            $this->assertSame(LeaveRequest::STATUS_APPROVED, $leave->status);
            $this->assertSame(12.0, $this->employee->annual_leave_balance);
        }
    }

    private function makeOtRequest(string $status): OtRequest
    {
        return OtRequest::create([
            'user_id' => $this->employee->id,
            'ot_date' => '2026-03-25',
            'hours' => 4.0,
            'reason' => 'Sprint wrap-up',
            'status' => $status,
        ]);
    }

    private function makeLeaveRequest(int $days, string $status): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-25',
            'days' => $days,
            'reason' => 'Family event',
            'status' => $status,
        ]);
    }
}
