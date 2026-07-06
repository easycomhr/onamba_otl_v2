<?php
namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_table_exists(): void
    {
        $this->assertTrue(\Schema::hasTable('teams'));
    }

    public function test_team_sub_approvers_table_exists(): void
    {
        $this->assertTrue(\Schema::hasTable('team_sub_approvers'));
    }

    public function test_users_has_team_id_column(): void
    {
        $this->assertTrue(\Schema::hasColumn('users', 'team_id'));
    }

    public function test_can_create_team_with_fillable_fields(): void
    {
        $team = Team::create([
            'name' => 'Engineering',
            'description' => 'Dev team',
            'department' => 'IT',
            'approver_escalation_hours' => 24,
        ]);
        $this->assertDatabaseHas('teams', ['name' => 'Engineering']);
    }

    public function test_team_main_approver_relationship(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $team = Team::create(['name' => 'Test Team', 'main_approver_id' => $manager->id, 'approver_escalation_hours' => 24]);
        $this->assertEquals($manager->id, $team->mainApprover->id);
    }

    public function test_team_sub_approvers_relationship(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $team = Team::create(['name' => 'Test Team 2', 'approver_escalation_hours' => 24]);
        $team->subApprovers()->attach($manager->id);
        $this->assertTrue($team->subApprovers->contains($manager));
    }

    public function test_team_members_relationship(): void
    {
        $team = Team::create(['name' => 'Test Team 3', 'approver_escalation_hours' => 24]);
        $employee = User::factory()->create(['team_id' => $team->id]);
        $this->assertTrue($team->members->contains($employee));
    }

    public function test_user_belongs_to_team(): void
    {
        $team = Team::create(['name' => 'Test Team 4', 'approver_escalation_hours' => 24]);
        $employee = User::factory()->create(['team_id' => $team->id]);
        $this->assertEquals($team->id, $employee->team->id);
    }

    public function test_deleting_team_nullifies_user_team_id(): void
    {
        $team = Team::create(['name' => 'Test Team 5', 'approver_escalation_hours' => 24]);
        $employee = User::factory()->create(['team_id' => $team->id]);
        $team->delete();
        $this->assertNull($employee->fresh()->team_id);
    }
}
