<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamViewTest extends TestCase
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

    public function test_get_admin_teams_returns_200_and_contains_title(): void
    {
        $response = $this->actingAs($this->manager)->get('/admin/teams');

        $response->assertOk();
        $response->assertSeeText('Quản lý Team');
    }

    public function test_get_admin_teams_create_returns_200_and_contains_title(): void
    {
        $response = $this->actingAs($this->manager)->get('/admin/teams/create');

        $response->assertOk();
        $response->assertSeeText('Tạo Team mới');
    }

    public function test_get_admin_teams_contains_team_name_when_data_exists(): void
    {
        $team = Team::factory()->create([
            'name' => 'Team QA Automation',
        ]);

        $response = $this->actingAs($this->manager)->get('/admin/teams');

        $response->assertOk();
        $response->assertSeeText($team->name);
    }
}
