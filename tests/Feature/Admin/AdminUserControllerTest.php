<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role'                 => User::ROLE_EMPLOYEE,
            'name'                 => 'Nguyen Van A',
            'employee_code'        => 'EMP001',
            'department'           => 'IT',
            'position'             => 'Developer',
            'annual_leave_balance' => 12,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                 => $this->employee->name,
            'department'           => $this->employee->department,
            'position'             => $this->employee->position,
            'annual_leave_balance' => $this->employee->annual_leave_balance,
        ], $overrides);
    }

    public function test_admin_can_update_manager_id_for_employee(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->manager)
            ->put(route('admin.employees.update', $this->employee->id), $this->validPayload([
                'manager_id' => $this->manager->id,
            ]));

        $response->assertRedirect(route('admin.employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'         => $this->employee->id,
            'manager_id' => $this->manager->id,
        ]);
    }

    public function test_admin_can_update_zalo_user_id_for_manager(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->manager)
            ->put(route('admin.employees.update', $this->employee->id), $this->validPayload([
                'zalo_user_id' => '9876543210',
            ]));

        $response->assertRedirect(route('admin.employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'           => $this->employee->id,
            'zalo_user_id' => '9876543210',
        ]);
    }

    public function test_validation_rejects_nonexistent_manager_id(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->manager)
            ->put(route('admin.employees.update', $this->employee->id), $this->validPayload([
                'manager_id' => 99999,
            ]));

        $response->assertSessionHasErrors(['manager_id']);
    }
}
