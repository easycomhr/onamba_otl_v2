<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_get_index_returns_200(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.teams.index'));

        $response->assertOk();
    }

    public function test_get_create_returns_200(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.teams.create'));

        $response->assertOk();
    }

    public function test_post_store_with_valid_data_redirects_to_index(): void
    {
        $mainApprover = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $subApprover = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $response = $this->actingAs($this->manager)->post(route('admin.teams.store'), [
            'name' => 'Mobile Team',
            'description' => 'Handles mobile products',
            'department' => 'Engineering',
            'main_approver_id' => $mainApprover->id,
            'sub_approver_ids' => [$subApprover->id],
            'approver_escalation_hours' => 24,
            'ms_teams_webhook_url' => 'https://example.com/webhook',
        ]);

        $response->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseHas('teams', ['name' => 'Mobile Team']);
    }

    public function test_post_store_with_invalid_data_returns_validation_errors(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.teams.create'))
            ->post(route('admin.teams.store'), [
                'name' => '',
                'approver_escalation_hours' => 0,
                'ms_teams_webhook_url' => 'not-a-url',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $response->assertSessionHasErrors(['name', 'approver_escalation_hours', 'ms_teams_webhook_url']);
    }

    public function test_get_edit_returns_200(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAs($this->manager)->get(route('admin.teams.edit', $team));

        $response->assertOk();
    }

    public function test_put_update_redirects(): void
    {
        $team = Team::factory()->create(['name' => 'Old Team']);
        $mainApprover = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $subApprover = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $response = $this->actingAs($this->manager)
            ->put(route('admin.teams.update', $team), [
                'name' => 'Updated Team',
                'description' => 'Updated description',
                'department' => 'IT',
                'main_approver_id' => $mainApprover->id,
                'sub_approver_ids' => [$subApprover->id],
                'approver_escalation_hours' => 36,
                'ms_teams_webhook_url' => 'https://example.com/new-webhook',
            ]);

        $response->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Updated Team']);
    }

    public function test_delete_destroy_redirects(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAs($this->manager)
            ->delete(route('admin.teams.destroy', $team));

        $response->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }
}
