<?php

namespace Tests\Feature\Admin;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtRegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Nguyen Van A',
            'employee_code' => 'EMP001',
            'department' => 'IT',
        ]);
    }

    public function test_guest_redirected_to_login_on_create(): void
    {
        $response = $this->get(route('admin.register.ot.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_create(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.register.ot.create'));

        $response->assertForbidden();
    }

    public function test_create_page_returns_200_for_manager(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.ot.create'));

        $response->assertOk();
        $response->assertViewIs('admin.register.ot');
    }

    public function test_store_creates_ot_request_and_redirects(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'ot_date' => '2026-03-20',
                'hours' => 2.5,
                'reason' => 'Release support',
            ]);

        $response->assertRedirect(route('admin.approvals.ot.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ot_requests', [
            'user_id' => $this->employee->id,
            'reason' => 'Release support',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $otRequest = OtRequest::firstOrFail();
        $this->assertEquals('2026-03-20', $otRequest->ot_date->toDateString());
        $this->assertEquals('2.5', $otRequest->hours);
    }

    public function test_store_validation_fails_missing_employee_id(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.ot.create'))
            ->post(route('admin.register.ot.store'), [
                'ot_date' => '2026-03-20',
                'hours' => 2.5,
                'reason' => 'Release support',
            ]);

        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_store_validation_fails_missing_ot_date(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.ot.create'))
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'hours' => 2.5,
                'reason' => 'Release support',
            ]);

        $response->assertSessionHasErrors(['ot_date']);
    }

    public function test_store_validation_fails_hours_below_minimum(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.ot.create'))
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'ot_date' => '2026-03-20',
                'hours' => 0.4,
                'reason' => 'Release support',
            ]);

        $response->assertSessionHasErrors(['hours']);
    }

    public function test_store_validation_fails_hours_above_maximum(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.ot.create'))
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'ot_date' => '2026-03-20',
                'hours' => 25,
                'reason' => 'Release support',
            ]);

        $response->assertSessionHasErrors(['hours']);
    }

    public function test_store_rejects_duplicate_ot(): void
    {
        OtRequest::create([
            'user_id' => $this->employee->id,
            'ot_date' => '2026-03-20',
            'hours' => 2.0,
            'reason' => 'Existing OT',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->manager)
            ->from(route('admin.register.ot.create'))
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'ot_date' => '2026-03-20',
                'hours' => 3.0,
                'reason' => 'Duplicate attempt',
            ]);

        $response->assertSessionHasErrors(['ot_date']);
        $this->assertDatabaseCount('ot_requests', 1);
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $response = $this->post(route('admin.register.ot.store'), [
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-20',
            'hours' => 2.5,
            'reason' => 'Release support',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_store(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('admin.register.ot.store'), [
                'employee_id' => $this->employee->id,
                'ot_date' => '2026-03-20',
                'hours' => 2.5,
                'reason' => 'Release support',
            ]);

        $response->assertForbidden();
    }

    public function test_employees_search_returns_json_results(): void
    {
        $matchingEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Tran Thi B',
            'employee_code' => 'EMP002',
            'department' => 'HR',
        ]);

        User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'name' => 'Manager Match',
            'employee_code' => 'EMP00',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('admin.register.ot.employees', ['search' => 'EMP002']));

        $response->assertOk();
        $response->assertJson([
            'data' => [[
                'id' => $matchingEmployee->id,
                'name' => 'Tran Thi B',
                'employee_code' => 'EMP002',
                'department' => 'HR',
            ]],
        ]);
        $response->assertJsonStructure(['data', 'next_page_url']);
    }

    public function test_employees_search_requires_auth(): void
    {
        $response = $this->get(route('admin.register.ot.employees', ['search' => 'EMP001']));

        $this->assertContains($response->getStatusCode(), [302, 401]);
    }
}
