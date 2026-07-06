<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;
    private User $mainApprover;
    private User $subApprover;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mainApprover = $this->makeEmployee('MAIN');
        $this->subApprover = $this->makeEmployee('SUB');

        $this->team = Team::factory()->create([
            'main_approver_id' => $this->mainApprover->id,
        ]);
        $this->team->subApprovers()->attach($this->subApprover->id);

        $this->member = $this->makeEmployee('MEMBER', [
            'team_id' => $this->team->id,
            'annual_leave_balance' => 12,
        ]);
    }

    public function test_main_approver_can_view_ot_show_page(): void
    {
        $otRequest = $this->makeOtRequest($this->member);

        $this->actingAs($this->mainApprover)
            ->get(route('employee.team-approvals.ot.show', $otRequest))
            ->assertOk();
    }

    public function test_approver_can_view_team_approval_index_with_pending_requests_only(): void
    {
        $pendingOt = $this->makeOtRequest($this->member);
        $approvedOt = $this->makeOtRequest($this->member, OtRequest::STATUS_APPROVED);
        $pendingLeave = $this->makeLeaveRequest($this->member, 2);
        $rejectedLeave = $this->makeLeaveRequest($this->member, 1, LeaveRequest::STATUS_REJECTED);

        $otherMainApprover = $this->makeEmployee('OTHERMAIN');
        $otherTeam = Team::factory()->create(['main_approver_id' => $otherMainApprover->id]);
        $otherMember = $this->makeEmployee('OTHERMEM', ['team_id' => $otherTeam->id]);
        $otherTeamOt = $this->makeOtRequest($otherMember);
        $otherTeamLeave = $this->makeLeaveRequest($otherMember, 1);

        $this->actingAs($this->mainApprover)
            ->get(route('employee.team-approvals.index'))
            ->assertOk()
            ->assertViewIs('employee.team_approvals.index')
            ->assertViewHas('pendingOtRequests', function ($paginator) use ($pendingOt, $approvedOt, $otherTeamOt) {
                $items = $paginator->items();
                $ids = collect($items)->pluck('id');

                if (!$ids->contains($pendingOt->id) || $ids->contains($approvedOt->id) || $ids->contains($otherTeamOt->id)) {
                    return false;
                }

                return collect($items)->every(fn ($item) => $item->relationLoaded('employee'));
            })
            ->assertViewHas('pendingLeaveRequests', function ($paginator) use ($pendingLeave, $rejectedLeave, $otherTeamLeave) {
                $items = $paginator->items();
                $ids = collect($items)->pluck('id');

                if (!$ids->contains($pendingLeave->id) || $ids->contains($rejectedLeave->id) || $ids->contains($otherTeamLeave->id)) {
                    return false;
                }

                return collect($items)->every(fn ($item) => $item->relationLoaded('employee'));
            });
    }

    public function test_non_approver_is_redirected_from_team_approval_index(): void
    {
        $nonApprover = $this->makeEmployee('NON');

        $this->actingAs($nonApprover)
            ->get(route('employee.team-approvals.index'))
            ->assertRedirect(route('employee.dashboard'))
            ->assertSessionHas('info');
    }

    public function test_sub_approver_can_view_ot_show_page(): void
    {
        $otRequest = $this->makeOtRequest($this->member);

        $this->actingAs($this->subApprover)
            ->get(route('employee.team-approvals.ot.show', $otRequest))
            ->assertOk();
    }

    public function test_non_approver_employee_gets_403_on_ot_show(): void
    {
        $nonApprover = $this->makeEmployee('NON');
        $otRequest = $this->makeOtRequest($this->member);

        $this->actingAs($nonApprover)
            ->get(route('employee.team-approvals.ot.show', $otRequest))
            ->assertForbidden();
    }

    public function test_cross_team_approver_gets_403_on_ot_approve(): void
    {
        $otherMainApprover = $this->makeEmployee('OTHERMAIN');
        $otherTeam = Team::factory()->create([
            'main_approver_id' => $otherMainApprover->id,
        ]);
        $otherMember = $this->makeEmployee('OTHERMEM', ['team_id' => $otherTeam->id]);
        $otRequest = $this->makeOtRequest($otherMember);

        $this->actingAs($this->mainApprover)
            ->post(route('employee.team-approvals.ot.approve', $otRequest), [
                'approved_hours' => 2,
                'manager_note' => 'Cross-team',
            ])
            ->assertForbidden();
    }

    public function test_main_approver_can_post_approve_ot(): void
    {
        $otRequest = $this->makeOtRequest($this->member);

        $this->actingAs($this->mainApprover)
            ->post(route('employee.team-approvals.ot.approve', $otRequest), [
                'approved_hours' => 2.5,
                'manager_note' => 'Approved',
            ])
            ->assertRedirect(route('employee.team-approvals.index'));

        $otRequest->refresh();
        $this->assertSame(OtRequest::STATUS_APPROVED, $otRequest->status);
        $this->assertSame($this->mainApprover->id, $otRequest->approved_by);
    }

    public function test_main_approver_can_post_reject_ot(): void
    {
        $otRequest = $this->makeOtRequest($this->member);

        $this->actingAs($this->mainApprover)
            ->post(route('employee.team-approvals.ot.reject', $otRequest), [
                'manager_note' => 'Need more details',
            ])
            ->assertRedirect(route('employee.team-approvals.index'));

        $otRequest->refresh();
        $this->assertSame(OtRequest::STATUS_REJECTED, $otRequest->status);
        $this->assertSame($this->mainApprover->id, $otRequest->approved_by);
    }

    public function test_approver_can_approve_leave_and_decrement_balance(): void
    {
        $leaveRequest = $this->makeLeaveRequest($this->member, 2);

        $this->actingAs($this->mainApprover)
            ->post(route('employee.team-approvals.leave.approve', $leaveRequest), [
                'manager_note' => 'Approved leave',
            ])
            ->assertRedirect(route('employee.team-approvals.index'));

        $leaveRequest->refresh();
        $this->member->refresh();

        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leaveRequest->status);
        $this->assertSame(10.0, $this->member->annual_leave_balance);
    }

    public function test_approver_can_reject_leave(): void
    {
        $leaveRequest = $this->makeLeaveRequest($this->member, 1);

        $this->actingAs($this->mainApprover)
            ->post(route('employee.team-approvals.leave.reject', $leaveRequest), [
                'manager_note' => 'Rejected',
            ])
            ->assertRedirect(route('employee.team-approvals.index'));

        $leaveRequest->refresh();
        $this->assertSame(LeaveRequest::STATUS_REJECTED, $leaveRequest->status);
        $this->assertSame($this->mainApprover->id, $leaveRequest->approved_by);
    }

    public function test_cannot_approve_already_approved_ot(): void
    {
        $otRequest = $this->makeOtRequest($this->member, OtRequest::STATUS_APPROVED);

        $this->actingAs($this->mainApprover)
            ->from(route('employee.dashboard'))
            ->post(route('employee.team-approvals.ot.approve', $otRequest), [
                'approved_hours' => 2,
                'manager_note' => 'Should fail',
            ])
            ->assertRedirect(route('employee.dashboard'))
            ->assertSessionHas('error');
    }

    private function makeEmployee(string $codePrefix, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify($codePrefix . '#####'),
        ], $overrides));
    }

    private function makeOtRequest(User $member, string $status = OtRequest::STATUS_PENDING, ?string $otDate = null): OtRequest
    {
        return OtRequest::create([
            'user_id' => $member->id,
            'ot_date' => $otDate ?? fake()->unique()->date('Y-m-d', '2026-03-31'),
            'hours' => 3.0,
            'reason' => 'Release support',
            'status' => $status,
        ]);
    }

    private function makeLeaveRequest(User $member, int $days, string $status = LeaveRequest::STATUS_PENDING): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $member->id,
            'leave_type' => array_key_first(LeaveRequest::LEAVE_TYPES),
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-25',
            'days' => $days,
            'reason' => 'Family matter',
            'status' => $status,
        ]);
    }
}
