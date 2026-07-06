<?php

namespace Tests\Unit\Services;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\EmployeeDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeDashboardService $service;
    private User $employee;
    private User $otherEmployee;
    private User $manager;
    private int $otSequence = 0;
    private int $leaveSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-13 10:00:00'));

        $this->service = new EmployeeDashboardService();
        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'annual_leave_balance' => 10,
        ]);
        $this->otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeOtRequest(User $employee, array $attrs = []): OtRequest
    {
        $this->otSequence++;

        $timestamps = [];
        if (array_key_exists('created_at', $attrs)) {
            $timestamps['created_at'] = $attrs['created_at'];
            unset($attrs['created_at']);
        }
        if (array_key_exists('updated_at', $attrs)) {
            $timestamps['updated_at'] = $attrs['updated_at'];
            unset($attrs['updated_at']);
        }

        $request = OtRequest::create(array_merge([
            'user_id' => $employee->id,
            'approved_by' => $this->manager->id,
            'code' => 'OT-' . strtoupper((string) str()->random(8)),
            'ot_date' => Carbon::now()->copy()->addDays($this->otSequence)->toDateString(),
            'hours' => 2.0,
            'approved_hours' => 2.0,
            'reason' => 'Test OT request',
            'status' => OtRequest::STATUS_APPROVED,
            'approved_at' => Carbon::now(),
        ], $attrs));

        if ($timestamps !== []) {
            $request->forceFill(array_merge([
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ], $timestamps))->saveQuietly();
        }

        return $request;
    }

    private function makeLeaveRequest(User $employee, array $attrs = []): LeaveRequest
    {
        $this->leaveSequence++;

        $timestamps = [];
        if (array_key_exists('created_at', $attrs)) {
            $timestamps['created_at'] = $attrs['created_at'];
            unset($attrs['created_at']);
        }
        if (array_key_exists('updated_at', $attrs)) {
            $timestamps['updated_at'] = $attrs['updated_at'];
            unset($attrs['updated_at']);
        }

        $request = LeaveRequest::create(array_merge([
            'user_id' => $employee->id,
            'approved_by' => $this->manager->id,
            'code' => 'LV-' . strtoupper((string) str()->random(8)),
            'leave_type' => 'annual',
            'from_date' => Carbon::now()->copy()->addDays($this->leaveSequence)->toDateString(),
            'to_date' => Carbon::now()->copy()->addDays($this->leaveSequence)->toDateString(),
            'days' => 1,
            'reason' => 'Test leave request',
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => Carbon::now(),
        ], $attrs));

        if ($timestamps !== []) {
            $request->forceFill(array_merge([
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ], $timestamps))->saveQuietly();
        }

        return $request;
    }

    public function test_get_ot_summary_pending_count_correct(): void
    {
        $this->makeOtRequest($this->employee, [
            'status' => OtRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->makeOtRequest($this->employee, [
            'status' => OtRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->makeOtRequest($this->employee);
        $this->makeOtRequest($this->otherEmployee, [
            'status' => OtRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(2, $result['otSummary']['pending_count']);
    }

    public function test_get_ot_summary_approved_count_correct(): void
    {
        $this->makeOtRequest($this->employee);
        $this->makeOtRequest($this->employee, ['status' => OtRequest::STATUS_REJECTED, 'approved_at' => null]);
        $this->makeOtRequest($this->employee);
        $this->makeOtRequest($this->otherEmployee);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(2, $result['otSummary']['approved_count']);
    }

    public function test_get_ot_summary_approved_hours_only_current_month(): void
    {
        $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-03-05',
            'approved_hours' => 2.5,
        ]);
        $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-03-10',
            'approved_hours' => 3.5,
        ]);
        $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-02-28',
            'approved_hours' => 4.0,
        ]);
        $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-03-15',
            'status' => OtRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'approved_hours' => 5.0,
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertEquals(6.0, $result['otSummary']['approved_hours_month']);
    }

    public function test_get_leave_summary_pending_count_correct(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'status' => LeaveRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->makeLeaveRequest($this->employee, [
            'status' => LeaveRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->makeLeaveRequest($this->employee);
        $this->makeLeaveRequest($this->otherEmployee, [
            'status' => LeaveRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(2, $result['leaveSummary']['pending_count']);
    }

    public function test_get_leave_summary_approved_count_correct(): void
    {
        $this->makeLeaveRequest($this->employee);
        $this->makeLeaveRequest($this->employee);
        $this->makeLeaveRequest($this->employee, [
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_at' => null,
        ]);
        $this->makeLeaveRequest($this->otherEmployee);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(2, $result['leaveSummary']['approved_count']);
    }

    public function test_get_leave_summary_approved_days_only_current_year(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-01-10',
            'to_date' => '2026-01-12',
            'days' => 3,
        ]);
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2025-12-30',
            'to_date' => '2026-01-02',
            'days' => 4,
        ]);
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-12-31',
            'to_date' => '2027-01-02',
            'days' => 3,
        ]);
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2025-12-01',
            'to_date' => '2025-12-05',
            'days' => 5,
        ]);
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-03',
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(10, $result['leaveSummary']['approved_days_year']);
    }

    public function test_get_recent_requests_returns_5_sorted_by_created_at_desc(): void
    {
        $this->makeOtRequest($this->employee, [
            'code' => 'OT-1',
            'created_at' => Carbon::parse('2026-03-13 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 09:00:00'),
        ]);
        $this->makeLeaveRequest($this->employee, [
            'code' => 'LV-2',
            'created_at' => Carbon::parse('2026-03-13 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 10:00:00'),
        ]);
        $this->makeOtRequest($this->employee, [
            'code' => 'OT-3',
            'created_at' => Carbon::parse('2026-03-13 11:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 11:00:00'),
        ]);
        $this->makeLeaveRequest($this->employee, [
            'code' => 'LV-4',
            'created_at' => Carbon::parse('2026-03-13 12:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 12:00:00'),
        ]);
        $this->makeOtRequest($this->employee, [
            'code' => 'OT-5',
            'created_at' => Carbon::parse('2026-03-13 13:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 13:00:00'),
        ]);
        $this->makeLeaveRequest($this->employee, [
            'code' => 'LV-6',
            'created_at' => Carbon::parse('2026-03-13 14:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 14:00:00'),
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertCount(5, $result['recentRequests']);
        $this->assertSame(['LV-6', 'OT-5', 'LV-4', 'OT-3', 'LV-2'], $result['recentRequests']->pluck('code')->all());
    }

    public function test_get_recent_requests_merges_ot_and_leave(): void
    {
        $this->makeOtRequest($this->employee, [
            'code' => 'OT-MERGE',
            'hours' => 3.5,
            'created_at' => Carbon::parse('2026-03-13 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 09:00:00'),
        ]);
        $this->makeLeaveRequest($this->employee, [
            'code' => 'LV-MERGE',
            'days' => 2,
            'created_at' => Carbon::parse('2026-03-13 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-13 10:00:00'),
        ]);

        $result = $this->service->getDashboardData($this->employee);

        $this->assertEqualsCanonicalizing(['leave', 'ot'], $result['recentRequests']->pluck('type')->all());
        $this->assertEqualsCanonicalizing(['2 ngày', '3.5 giờ'], $result['recentRequests']->pluck('meta')->all());
    }

    public function test_get_recent_requests_empty_when_no_requests(): void
    {
        $result = $this->service->getDashboardData($this->employee);

        $this->assertCount(0, $result['recentRequests']);
    }

    public function test_get_dashboard_data_returns_all_keys(): void
    {
        $result = $this->service->getDashboardData($this->employee);

        $this->assertSame(
            ['otSummary', 'leaveSummary', 'recentRequests', 'leaveBalance'],
            array_keys($result)
        );
    }

    public function test_leave_balance_reflects_user_annual_leave_balance(): void
    {
        $this->employee->update(['annual_leave_balance' => 7]);

        $result = $this->service->getDashboardData($this->employee->fresh());

        $this->assertSame(7, $result['leaveBalance']);
    }

    // ──────────────────────────────────────────────────────────────
    // isApprover
    // ──────────────────────────────────────────────────────────────

    public function test_is_approver_returns_false_for_non_approver(): void
    {
        $this->assertFalse($this->service->isApprover($this->employee->id));
    }

    public function test_is_approver_returns_true_for_main_approver(): void
    {
        Team::factory()->create(['main_approver_id' => $this->employee->id]);

        $this->assertTrue($this->service->isApprover($this->employee->id));
    }

    public function test_is_approver_returns_true_for_sub_approver(): void
    {
        $team = Team::factory()->create(['main_approver_id' => $this->manager->id]);
        $team->subApprovers()->attach($this->employee->id);

        $this->assertTrue($this->service->isApprover($this->employee->id));
    }

    // ──────────────────────────────────────────────────────────────
    // getPendingApprovalCount
    // ──────────────────────────────────────────────────────────────

    public function test_get_pending_approval_count_returns_zero_when_no_teams(): void
    {
        $this->assertSame(0, $this->service->getPendingApprovalCount($this->employee->id));
    }

    public function test_get_pending_approval_count_returns_zero_when_team_has_no_members(): void
    {
        Team::factory()->create(['main_approver_id' => $this->employee->id]);

        $this->assertSame(0, $this->service->getPendingApprovalCount($this->employee->id));
    }

    public function test_get_pending_approval_count_sums_pending_ot_and_leave(): void
    {
        $team = Team::factory()->create(['main_approver_id' => $this->employee->id]);
        $member = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'team_id' => $team->id]);

        $this->makeOtRequest($member, ['status' => OtRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);
        $this->makeOtRequest($member, ['status' => OtRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);
        $this->makeLeaveRequest($member, ['status' => LeaveRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);

        $this->assertSame(3, $this->service->getPendingApprovalCount($this->employee->id));
    }

    public function test_get_pending_approval_count_excludes_approved_and_rejected(): void
    {
        $team = Team::factory()->create(['main_approver_id' => $this->employee->id]);
        $member = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'team_id' => $team->id]);

        $this->makeOtRequest($member);                                                              // approved
        $this->makeOtRequest($member, ['status' => OtRequest::STATUS_REJECTED, 'approved_at' => null]);
        $this->makeOtRequest($member, ['status' => OtRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);
        $this->makeLeaveRequest($member);                                                           // approved
        $this->makeLeaveRequest($member, ['status' => LeaveRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);

        $this->assertSame(2, $this->service->getPendingApprovalCount($this->employee->id));
    }

    public function test_get_pending_approval_count_only_counts_own_team_members(): void
    {
        $team = Team::factory()->create(['main_approver_id' => $this->employee->id]);
        $member = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'team_id' => $team->id]);

        // Other team member — should not be counted
        $otherTeam = Team::factory()->create(['main_approver_id' => $this->manager->id]);
        $outsider = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'team_id' => $otherTeam->id]);

        $this->makeOtRequest($member, ['status' => OtRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);
        $this->makeOtRequest($outsider, ['status' => OtRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);

        $this->assertSame(1, $this->service->getPendingApprovalCount($this->employee->id));
    }

    public function test_get_pending_approval_count_includes_sub_approver_teams(): void
    {
        $team = Team::factory()->create(['main_approver_id' => $this->manager->id]);
        $team->subApprovers()->attach($this->employee->id);
        $member = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'team_id' => $team->id]);

        $this->makeLeaveRequest($member, ['status' => LeaveRequest::STATUS_PENDING, 'approved_by' => null, 'approved_at' => null]);

        $this->assertSame(1, $this->service->getPendingApprovalCount($this->employee->id));
    }
}
