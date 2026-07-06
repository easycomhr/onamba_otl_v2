<?php

namespace Tests\Feature\Employee;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\User;
use App\Services\EmployeeDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'annual_leave_balance' => 9,
        ]);

        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        OtRequest::create([
            'user_id' => $this->employee->id,
            'ot_date' => now()->toDateString(),
            'hours' => 2.5,
            'approved_hours' => null,
            'reason' => 'Pending OT request',
            'status' => OtRequest::STATUS_PENDING,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        OtRequest::create([
            'user_id' => $this->employee->id,
            'ot_date' => now()->subDay()->toDateString(),
            'hours' => 3.0,
            'approved_hours' => 2.0,
            'reason' => 'Approved OT request',
            'status' => OtRequest::STATUS_APPROVED,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDay()->toDateString(),
            'days' => 2,
            'reason' => 'Pending leave request',
            'status' => LeaveRequest::STATUS_PENDING,
            'created_at' => now()->subMinutes(8),
            'updated_at' => now()->subMinutes(8),
        ]);

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'from_date' => now()->subDays(2)->toDateString(),
            'to_date' => now()->subDay()->toDateString(),
            'days' => 2,
            'reason' => 'Approved leave request',
            'status' => LeaveRequest::STATUS_APPROVED,
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get(route('employee.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_manager_cannot_access_employee_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('employee.dashboard'));

        $response->assertForbidden();
    }

    public function test_employee_gets_200_and_correct_view(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertOk()
            ->assertViewIs('employee.dashboard');
    }

    public function test_employee_dashboard_contains_ot_summary_keys(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertOk()
            ->assertViewHas('otSummary', function (array $otSummary) {
                $this->assertSame(1, $otSummary['pending_count']);
                $this->assertSame(1, $otSummary['approved_count']);
                $this->assertEquals(2.0, $otSummary['approved_hours_month']);

                return array_keys($otSummary) === [
                    'pending_count',
                    'approved_count',
                    'approved_hours_month',
                ];
            });
    }

    public function test_employee_dashboard_contains_leave_summary_keys(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertOk()
            ->assertViewHas('leaveSummary', function (array $leaveSummary) {
                $this->assertSame(1, $leaveSummary['pending_count']);
                $this->assertSame(1, $leaveSummary['approved_count']);
                $this->assertSame(2, $leaveSummary['approved_days_year']);

                return array_keys($leaveSummary) === [
                    'pending_count',
                    'approved_count',
                    'approved_days_year',
                ];
            });
    }

    public function test_employee_dashboard_contains_recent_requests_key(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertOk()
            ->assertViewHas('recentRequests', function ($recentRequests) {
                $this->assertCount(4, $recentRequests);
                $this->assertTrue($recentRequests->contains(fn (array $request) => $request['type'] === 'ot'));
                $this->assertTrue($recentRequests->contains(fn (array $request) => $request['type'] === 'leave'));

                return true;
            });
    }

    public function test_employee_dashboard_contains_leave_balance(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertOk()
            ->assertViewHas('leaveBalance', 9);
    }

    public function test_service_exception_returns_error_response(): void
    {
        $service = $this->mock(EmployeeDashboardService::class);
        $service->shouldReceive('getDashboardData')
            ->once()
            ->andThrow(new \RuntimeException('Service failure'));

        $response = $this->from(route('employee.dashboard'))
            ->actingAs($this->employee)
            ->get(route('employee.dashboard'));

        $response->assertRedirect(route('employee.dashboard'))
            ->assertSessionHasErrors(['error']);
    }
}
