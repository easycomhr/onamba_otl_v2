<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'annual_leave_balance' => 7,
            'department' => 'Production',
            'position' => 'Operator',
            'password' => Hash::make('old-password'),
        ]);

        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_code' => fake()->unique()->numerify('MGR#####'),
        ]);
    }

    public function test_guest_redirected_to_login_on_show(): void
    {
        $this->get(route('employee.profile'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_change_password(): void
    {
        $this->post(route('employee.change-password'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_manager_cannot_access_show(): void
    {
        $this->actingAs($this->manager)
            ->get(route('employee.profile'))
            ->assertForbidden();
    }

    public function test_manager_cannot_change_password(): void
    {
        $this->actingAs($this->manager)
            ->post(route('employee.change-password'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_employee_show_returns_200_with_correct_view(): void
    {
        $this->actingAs($this->employee)
            ->get(route('employee.profile'))
            ->assertOk()
            ->assertViewIs('employee.profile');
    }

    public function test_employee_show_passes_user_and_leave_balance_to_view(): void
    {
        $this->actingAs($this->employee)
            ->get(route('employee.profile'))
            ->assertOk()
            ->assertViewHas('user', fn (User $user) => $user->is($this->employee))
            ->assertViewHas('leaveBalance', 7);
    }

    public function test_change_password_success_redirects_back_with_flash(): void
    {
        $response = $this->from(route('employee.profile'))
            ->actingAs($this->employee)
            ->post(route('employee.change-password'), $this->validPayload());

        $response->assertRedirect(route('employee.profile'))
            ->assertSessionHas('success', 'Đổi mật khẩu thành công.');

        $this->employee->refresh();
        $this->assertTrue(Hash::check('new-password', $this->employee->password));
    }

    public function test_change_password_fails_wrong_current_password(): void
    {
        $payload = $this->validPayload();
        $payload['current_password'] = 'wrong-password';

        $this->actingAs($this->employee)
            ->from(route('employee.profile'))
            ->post(route('employee.change-password'), $payload)
            ->assertSessionHasErrors(['current_password']);
    }

    public function test_change_password_validation_fails_missing_current_password(): void
    {
        $payload = $this->validPayload();
        unset($payload['current_password']);

        $this->actingAs($this->employee)
            ->from(route('employee.profile'))
            ->post(route('employee.change-password'), $payload)
            ->assertSessionHasErrors(['current_password']);
    }

    public function test_change_password_validation_fails_password_too_short(): void
    {
        $payload = $this->validPayload();
        $payload['password'] = 'short';
        $payload['password_confirmation'] = 'short';

        $this->actingAs($this->employee)
            ->from(route('employee.profile'))
            ->post(route('employee.change-password'), $payload)
            ->assertSessionHasErrors(['password']);
    }

    public function test_change_password_validation_fails_password_confirmation_mismatch(): void
    {
        $payload = $this->validPayload();
        $payload['password_confirmation'] = 'different-password';

        $this->actingAs($this->employee)
            ->from(route('employee.profile'))
            ->post(route('employee.change-password'), $payload)
            ->assertSessionHasErrors(['password']);
    }

    public function test_change_password_service_exception_returns_error_response(): void
    {
        $service = $this->mock(ProfileService::class);
        $service->shouldReceive('changePassword')
            ->once()
            ->andThrow(new \RuntimeException('Service failure'));

        $response = $this->from(route('employee.profile'))
            ->actingAs($this->employee)
            ->post(route('employee.change-password'), $this->validPayload());

        $response->assertRedirect(route('employee.profile'))
            ->assertSessionHasErrors(['error']);
    }

    private function validPayload(): array
    {
        return [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];
    }
}
