<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ── role:manager,hr — allows both roles ───────────────────────────────────

    public function test_manager_can_access_dashboard(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_hr_can_access_dashboard(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_employee_cannot_access_dashboard(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ── role:manager — blocks hr ──────────────────────────────────────────────

    public function test_manager_can_access_salary_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('admin.salary.index'))
            ->assertOk();
    }

    public function test_hr_cannot_access_salary_index(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.salary.index'))
            ->assertForbidden();
    }

    public function test_employee_cannot_access_salary_index(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('admin.salary.index'))
            ->assertForbidden();
    }

    public function test_hr_cannot_access_salary_import_page(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.salary.import'))
            ->assertForbidden();
    }

    public function test_hr_cannot_access_salary_template(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.salary.template'))
            ->assertForbidden();
    }

    // ── Non-salary admin routes accessible by hr ──────────────────────────────

    public function test_hr_can_access_ot_approvals(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.approvals.ot.index'))
            ->assertOk();
    }

    public function test_hr_can_access_leave_approvals(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.approvals.leave.index'))
            ->assertOk();
    }

    public function test_hr_can_access_employees_index(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->get(route('admin.employees.index'))
            ->assertOk();
    }

    // ── Variadic middleware — single-role shorthand still works ───────────────

    public function test_single_role_still_works_for_manager_only_routes(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $hr      = User::factory()->create(['role' => 'hr']);

        $this->actingAs($manager)
            ->get(route('admin.salary.index'))
            ->assertOk();

        $this->actingAs($hr)
            ->get(route('admin.salary.index'))
            ->assertForbidden();
    }
}
