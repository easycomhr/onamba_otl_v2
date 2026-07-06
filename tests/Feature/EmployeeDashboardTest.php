<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_employee_dashboard_contains_team_approver_block(): void
    {
        [$approver] = $this->makeApproverContext();

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Quản lý yêu cầu team');
    }

    public function test_regular_employee_dashboard_does_not_contain_team_approver_block(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($employee)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Quản lý yêu cầu team');
    }

    // ──────────────────────────────────────────────────────────────
    // Approver summary card (Task 4)
    // ──────────────────────────────────────────────────────────────

    public function test_approval_panel_visible_when_user_is_team_approver(): void
    {
        [$approver] = $this->makeApproverContext();

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Duyệt OT/Nghỉ phép')
            ->assertSeeText('Xem danh sách');
    }

    public function test_approval_panel_hidden_when_user_is_not_approver(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Duyệt OT/Nghỉ phép');
    }

    public function test_approval_panel_shows_correct_pending_count(): void
    {
        [$approver, $member] = $this->makeApproverContext();

        OtRequest::create([
            'user_id' => $member->id,
            'code' => 'OT-COUNT-001',
            'ot_date' => '2026-03-27',
            'hours' => 2.0,
            'reason' => 'Deployment',
            'status' => OtRequest::STATUS_PENDING,
        ]);
        OtRequest::create([
            'user_id' => $member->id,
            'code' => 'OT-COUNT-002',
            'ot_date' => '2026-03-28',
            'hours' => 1.5,
            'reason' => 'Deployment',
            'status' => OtRequest::STATUS_PENDING,
        ]);
        LeaveRequest::create([
            'user_id' => $member->id,
            'code' => 'LV-COUNT-001',
            'leave_type' => array_key_first(LeaveRequest::LEAVE_TYPES),
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-01',
            'days' => 1,
            'reason' => 'Personal',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('3');
    }

    public function test_approval_panel_count_excludes_non_pending(): void
    {
        [$approver, $member] = $this->makeApproverContext();

        OtRequest::create([
            'user_id' => $member->id,
            'code' => 'OT-APPROVED-001',
            'ot_date' => '2026-03-27',
            'hours' => 2.0,
            'approved_hours' => 2.0,
            'reason' => 'Work',
            'status' => OtRequest::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
        OtRequest::create([
            'user_id' => $member->id,
            'code' => 'OT-PENDING-001',
            'ot_date' => '2026-03-28',
            'hours' => 1.0,
            'reason' => 'Work',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Duyệt OT/Nghỉ phép')
            ->assertSeeText('yêu cầu chờ')
            // Approved OT must not inflate the count; only 1 pending remains
            ->assertDontSeeText('2 yêu cầu chờ');
    }

    public function test_approver_sees_pending_ot_request_in_team_block(): void
    {
        [$approver, $member] = $this->makeApproverContext();

        $otRequest = OtRequest::create([
            'user_id' => $member->id,
            'code' => 'OT-TEAM-001',
            'ot_date' => '2026-03-27',
            'hours' => 2.5,
            'reason' => 'Support deployment',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Yêu cầu OT chờ duyệt')
            ->assertSeeText($otRequest->code)
            ->assertSeeText($member->name);
    }

    public function test_approver_sees_pending_leave_request_in_team_block(): void
    {
        [$approver, $member] = $this->makeApproverContext();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $member->id,
            'code' => 'LV-TEAM-001',
            'leave_type' => array_key_first(LeaveRequest::LEAVE_TYPES),
            'from_date' => '2026-03-27',
            'to_date' => '2026-03-28',
            'days' => 2,
            'reason' => 'Family event',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($approver)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Yêu cầu nghỉ phép chờ duyệt')
            ->assertSeeText($leaveRequest->code)
            ->assertSeeText($member->name);
    }

    private function makeApproverContext(): array
    {
        $approver = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => 'APPROVER-001',
        ]);

        $team = Team::factory()->create([
            'main_approver_id' => $approver->id,
        ]);

        $member = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'team_id' => $team->id,
            'employee_code' => 'MEMBER-001',
        ]);

        return [$approver, $member, $team];
    }
}
