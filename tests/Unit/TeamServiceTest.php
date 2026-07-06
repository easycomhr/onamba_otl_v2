<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TeamService();
    }

    public function test_list_returns_paginator(): void
    {
        Team::factory()->count(2)->create();

        $result = $this->service->list();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(2, $result->total());
    }

    public function test_create_persists_team_and_syncs_sub_approvers(): void
    {
        $mainApprover = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $sub1 = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $sub2 = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $team = $this->service->create([
            'name' => 'Core Team',
            'description' => 'Core engineering',
            'department' => 'Engineering',
            'main_approver_id' => $mainApprover->id,
            'approver_escalation_hours' => 24,
            'ms_teams_webhook_url' => 'https://example.com/hook',
            'sub_approver_ids' => [$sub1->id, $sub2->id],
        ]);

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Core Team']);
        $this->assertDatabaseHas('team_sub_approvers', ['team_id' => $team->id, 'user_id' => $sub1->id]);
        $this->assertDatabaseHas('team_sub_approvers', ['team_id' => $team->id, 'user_id' => $sub2->id]);
    }

    public function test_update_changes_data_and_syncs_sub_approvers(): void
    {
        $oldSub = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $newSub = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $mainApprover = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $team = Team::factory()->create([
            'name' => 'Ops Team',
            'approver_escalation_hours' => 24,
        ]);
        $team->subApprovers()->sync([$oldSub->id]);

        $updated = $this->service->update($team, [
            'name' => 'Ops Team Updated',
            'description' => 'Updated',
            'department' => 'Operation',
            'main_approver_id' => $mainApprover->id,
            'approver_escalation_hours' => 48,
            'ms_teams_webhook_url' => 'https://example.com/new-hook',
            'sub_approver_ids' => [$newSub->id],
        ]);

        $this->assertSame('Ops Team Updated', $updated->name);
        $this->assertSame($mainApprover->id, $updated->main_approver_id);
        $this->assertDatabaseHas('team_sub_approvers', ['team_id' => $team->id, 'user_id' => $newSub->id]);
        $this->assertDatabaseMissing('team_sub_approvers', ['team_id' => $team->id, 'user_id' => $oldSub->id]);
    }

    public function test_delete_removes_record(): void
    {
        $team = Team::factory()->create();

        $this->service->delete($team);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_get_manager_users_returns_manager_and_employee_users(): void
    {
        User::factory()->create(['name' => 'Alice', 'role' => User::ROLE_MANAGER]);
        User::factory()->create(['name' => 'Bob', 'role' => User::ROLE_EMPLOYEE]);
        User::factory()->create(['name' => 'Charlie', 'role' => 'admin']);

        $users = $this->service->getManagerUsers();

        $this->assertSame(['Alice', 'Bob'], $users->pluck('name')->all());
        $this->assertSame([User::ROLE_MANAGER, User::ROLE_EMPLOYEE], $users->pluck('role')->all());
    }

    public function test_get_departments_returns_distinct_departments(): void
    {
        User::factory()->create(['department' => 'Engineering']);
        User::factory()->create(['department' => 'HR']);
        User::factory()->create(['department' => 'Engineering']);
        User::factory()->create(['department' => null]);
        User::factory()->create(['department' => '']);

        $departments = $this->service->getDepartments();

        $this->assertSame(['Engineering', 'HR'], $departments->all());
    }
}
