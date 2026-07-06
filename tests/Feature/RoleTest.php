<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    // ── User::isHr() ────────────────────────────────────────────────────────

    public function test_isHr_returns_true_for_hr_role(): void
    {
        $user = User::factory()->make(['role' => 'hr']);

        $this->assertTrue($user->isHr());
    }

    public function test_isHr_returns_false_for_manager_role(): void
    {
        $user = User::factory()->make(['role' => 'manager']);

        $this->assertFalse($user->isHr());
    }

    public function test_isHr_returns_false_for_employee_role(): void
    {
        $user = User::factory()->make(['role' => 'employee']);

        $this->assertFalse($user->isHr());
    }

    public function test_role_hr_constant_equals_hr_string(): void
    {
        $this->assertSame('hr', User::ROLE_HR);
    }

    // ── Login redirect ────────────────────────────────────────────────────────

    public function test_hr_user_is_redirected_to_admin_dashboard_after_login(): void
    {
        $hr = User::factory()->create([
            'role'     => 'hr',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'employee_id' => $hr->employee_code,
            'password'    => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_manager_is_redirected_to_admin_dashboard_after_login(): void
    {
        $manager = User::factory()->create([
            'role'     => 'manager',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'employee_id' => $manager->employee_code,
            'password'    => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_employee_is_redirected_to_employee_dashboard_after_login(): void
    {
        $employee = User::factory()->create([
            'role'     => 'employee',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'employee_id' => $employee->employee_code,
            'password'    => 'password',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_wrong_password_does_not_redirect(): void
    {
        $hr = User::factory()->create([
            'role'     => 'hr',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'employee_id' => $hr->employee_code,
            'password'    => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('employee_id');
    }

    // ── Migration: hr enum value persists in DB ───────────────────────────────

    public function test_hr_user_can_be_persisted_and_retrieved(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->assertDatabaseHas('users', ['id' => $hr->id, 'role' => 'hr']);
        $this->assertSame('hr', $hr->fresh()->role);
    }
}
