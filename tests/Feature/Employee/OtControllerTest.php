<?php

namespace Tests\Feature\Employee;

use App\Models\OtRequest;
use App\Models\User;
use App\Services\OtService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);

        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_code' => fake()->unique()->numerify('MGR#####'),
        ]);
    }

    public function test_guest_redirected_to_login_on_index(): void
    {
        $this->get(route('employee.ot.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_create(): void
    {
        $this->get(route('employee.ot.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $this->post(route('employee.ot.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_manager_cannot_access_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('employee.ot.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_create(): void
    {
        $this->actingAs($this->manager)
            ->get(route('employee.ot.create'))
            ->assertForbidden();
    }

    public function test_manager_cannot_store(): void
    {
        $this->actingAs($this->manager)
            ->post(route('employee.ot.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_employee_index_returns_200_with_correct_view(): void
    {
        $otRequest = $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-03-10',
            'hours' => 3.5,
            'reason' => 'Quarter-end support',
        ]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.ot.index'));

        $response->assertOk()
            ->assertViewIs('employee.ot.list')
            ->assertViewHas('requests', function ($requests) use ($otRequest) {
                $this->assertCount(1, $requests);
                $this->assertSame($otRequest->id, $requests->first()->id);

                return true;
            });
    }

    public function test_employee_create_returns_200_with_correct_view(): void
    {
        $this->actingAs($this->employee)
            ->get(route('employee.ot.create'))
            ->assertOk()
            ->assertViewIs('employee.ot.register');
    }

    public function test_employee_store_creates_ot_request_and_redirects_to_index(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('employee.ot.store'), $this->validPayload());

        $response->assertRedirect(route('employee.ot.index'));

        $this->assertDatabaseHas('ot_requests', [
            'user_id' => $this->employee->id,
            'reason' => 'Month-end release support',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $this->assertSame(
            '2026-03-12',
            OtRequest::query()->where('user_id', $this->employee->id)->sole()->ot_date->toDateString()
        );
    }

    public function test_employee_store_flashes_success_message(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('employee.ot.store'), $this->validPayload());

        $response->assertRedirect(route('employee.ot.index'))
            ->assertSessionHas('success', 'Đăng ký tăng ca thành công. Đang chờ duyệt.');
    }

    public function test_store_validation_fails_missing_ot_date(): void
    {
        $payload = $this->validPayload();
        unset($payload['ot_date']);

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $payload)
            ->assertSessionHasErrors(['ot_date']);
    }

    public function test_store_validation_fails_future_ot_date(): void
    {
        $payload = $this->validPayload();
        $payload['ot_date'] = now()->addDay()->toDateString();

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $payload)
            ->assertSessionHasErrors(['ot_date']);
    }

    public function test_store_validation_fails_hours_below_minimum(): void
    {
        $payload = $this->validPayload();
        $payload['hours'] = 0.4;

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $payload)
            ->assertSessionHasErrors(['hours']);
    }

    public function test_store_validation_fails_hours_above_maximum(): void
    {
        $payload = $this->validPayload();
        $payload['hours'] = 13;

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $payload)
            ->assertSessionHasErrors(['hours']);
    }

    public function test_store_validation_fails_missing_reason(): void
    {
        $payload = $this->validPayload();
        unset($payload['reason']);

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $payload)
            ->assertSessionHasErrors(['reason']);
    }

    public function test_store_validation_fails_duplicate_ot_same_date(): void
    {
        $this->makeOtRequest($this->employee, [
            'ot_date' => '2026-03-12',
        ]);

        $this->actingAs($this->employee)
            ->from(route('employee.ot.create'))
            ->post(route('employee.ot.store'), $this->validPayload())
            ->assertSessionHasErrors([
                'ot_date' => 'Bạn đã có lịch tăng ca vào ngày này.',
            ]);

        $this->assertDatabaseCount('ot_requests', 1);
    }

    public function test_store_service_exception_returns_error_response(): void
    {
        $service = $this->mock(OtService::class);
        $service->shouldReceive('store')
            ->once()
            ->andThrow(new \RuntimeException('Service failure'));

        $response = $this->from(route('employee.ot.create'))
            ->actingAs($this->employee)
            ->post(route('employee.ot.store'), $this->validPayload());

        $response->assertRedirect(route('employee.ot.create'))
            ->assertSessionHasErrors(['error']);
    }

    public function test_guest_redirected_to_login_on_destroy(): void
    {
        $otRequest = $this->makeOtRequest($this->employee);

        $this->delete(route('employee.ot.destroy', $otRequest))
            ->assertRedirect(route('login'));
    }

    public function test_manager_cannot_delete_ot_request(): void
    {
        $otRequest = $this->makeOtRequest($this->employee);

        $this->actingAs($this->manager)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertForbidden();
    }

    public function test_employee_cannot_delete_another_employees_ot_request(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
        ]);
        $otRequest = $this->makeOtRequest($other);

        $this->actingAs($this->employee)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertForbidden();
    }

    public function test_employee_cannot_delete_approved_ot_request(): void
    {
        $otRequest = $this->makeOtRequest($this->employee, ['status' => OtRequest::STATUS_APPROVED]);

        $this->actingAs($this->employee)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertStatus(422);
    }

    public function test_employee_cannot_delete_rejected_ot_request(): void
    {
        $otRequest = $this->makeOtRequest($this->employee, ['status' => OtRequest::STATUS_REJECTED]);

        $this->actingAs($this->employee)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertStatus(422);
    }

    public function test_employee_can_delete_own_pending_ot_request(): void
    {
        $otRequest = $this->makeOtRequest($this->employee);

        $this->actingAs($this->employee)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertRedirect(route('employee.ot.index'));

        $this->assertSoftDeleted('ot_requests', ['id' => $otRequest->id]);
    }

    public function test_delete_redirects_to_index_with_success_message(): void
    {
        $otRequest = $this->makeOtRequest($this->employee);

        $this->actingAs($this->employee)
            ->delete(route('employee.ot.destroy', $otRequest))
            ->assertRedirect(route('employee.ot.index'))
            ->assertSessionHas('success');
    }

    private function validPayload(): array
    {
        return [
            'ot_date' => '2026-03-12',
            'hours' => 2.5,
            'reason' => 'Month-end release support',
        ];
    }

    private function makeOtRequest(User $user, array $overrides = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'user_id' => $user->id,
            'ot_date' => '2026-03-11',
            'hours' => 2.0,
            'reason' => 'Existing OT request',
            'status' => OtRequest::STATUS_PENDING,
        ], $overrides));
    }
}
