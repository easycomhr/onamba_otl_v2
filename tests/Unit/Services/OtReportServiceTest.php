<?php

namespace Tests\Unit\Services;

use App\Models\OtRequest;
use App\Models\User;
use App\Services\OtReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtReportService $service;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtReportService();
        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeEmployee(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_EMPLOYEE], $attrs));
    }

    private function makeOtRequest(User $employee, array $attrs = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'user_id'        => $employee->id,
            'ot_date'        => now()->toDateString(),
            'hours'          => 2.0,
            'approved_hours' => 2.0,
            'reason'         => 'Test reason',
            'status'         => OtRequest::STATUS_APPROVED,
            'approved_at'    => now(),
        ], $attrs));
    }

    // ── getData ──────────────────────────────────────────────────────────────

    public function test_get_data_returns_only_approved_requests(): void
    {
        $employee = $this->makeEmployee();

        $this->makeOtRequest($employee, ['status' => OtRequest::STATUS_APPROVED]);
        $this->makeOtRequest($employee, ['status' => OtRequest::STATUS_PENDING, 'approved_hours' => null]);
        $this->makeOtRequest($employee, ['status' => OtRequest::STATUS_REJECTED, 'approved_hours' => null]);

        $result = $this->service->getData([]);

        $this->assertCount(1, $result);
        $this->assertEquals(OtRequest::STATUS_APPROVED, $result->first()->status);
    }

    public function test_get_data_filters_by_date_range(): void
    {
        $employee = $this->makeEmployee();

        $this->makeOtRequest($employee, ['ot_date' => '2026-01-10']);
        $this->makeOtRequest($employee, ['ot_date' => '2026-01-20']);
        $this->makeOtRequest($employee, ['ot_date' => '2026-01-30']);

        $result = $this->service->getData([
            'from_date' => '2026-01-15',
            'to_date'   => '2026-01-25',
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('2026-01-20', $result->first()->ot_date->toDateString());
    }

    public function test_get_data_filters_by_employee_id(): void
    {
        $emp1 = $this->makeEmployee(['name' => 'Employee One']);
        $emp2 = $this->makeEmployee(['name' => 'Employee Two']);

        $this->makeOtRequest($emp1, ['ot_date' => '2026-02-01']);
        $this->makeOtRequest($emp2, ['ot_date' => '2026-02-01']);

        $result = $this->service->getData(['employee_id' => $emp1->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($emp1->id, $result->first()->user_id);
    }

    public function test_get_data_filters_by_department(): void
    {
        $itEmp  = $this->makeEmployee(['department' => 'IT']);
        $hrEmp  = $this->makeEmployee(['department' => 'HR']);

        $this->makeOtRequest($itEmp, ['ot_date' => '2026-02-01']);
        $this->makeOtRequest($hrEmp, ['ot_date' => '2026-02-01']);

        $result = $this->service->getData(['department' => 'IT']);

        $this->assertCount(1, $result);
        $this->assertEquals($itEmp->id, $result->first()->user_id);
    }

    public function test_get_data_returns_empty_when_no_matching(): void
    {
        $employee = $this->makeEmployee();
        $this->makeOtRequest($employee, ['ot_date' => '2026-01-05']);

        $result = $this->service->getData([
            'from_date' => '2026-03-01',
            'to_date'   => '2026-03-31',
        ]);

        $this->assertCount(0, $result);
    }

    public function test_get_data_orders_by_ot_date(): void
    {
        $employee = $this->makeEmployee();

        $this->makeOtRequest($employee, ['ot_date' => '2026-01-20']);
        $this->makeOtRequest($employee, ['ot_date' => '2026-01-05']);
        $this->makeOtRequest($employee, ['ot_date' => '2026-01-15']);

        $result = $this->service->getData([]);
        $dates  = $result->pluck('ot_date')->map(fn ($d) => $d->toDateString())->values()->toArray();

        $this->assertEquals(['2026-01-05', '2026-01-15', '2026-01-20'], $dates);
    }

    // ── getSummary ───────────────────────────────────────────────────────────

    public function test_get_summary_groups_by_employee(): void
    {
        $emp1 = $this->makeEmployee(['name' => 'Alice']);
        $emp2 = $this->makeEmployee(['name' => 'Bob']);

        $this->makeOtRequest($emp1, ['ot_date' => '2026-01-10', 'approved_hours' => 2.0]);
        $this->makeOtRequest($emp1, ['ot_date' => '2026-01-11', 'approved_hours' => 3.0]);
        $this->makeOtRequest($emp2, ['ot_date' => '2026-01-12', 'approved_hours' => 4.0]);

        $rows    = $this->service->getData([]);
        $summary = $this->service->getSummary($rows);

        $this->assertCount(2, $summary);

        $alice = $summary->firstWhere('employee.id', $emp1->id);
        $this->assertEquals(2, $alice['total_days']);
        $this->assertEquals(5.0, $alice['total_hours']);

        $bob = $summary->firstWhere('employee.id', $emp2->id);
        $this->assertEquals(1, $bob['total_days']);
        $this->assertEquals(4.0, $bob['total_hours']);
    }

    public function test_get_summary_returns_empty_for_empty_collection(): void
    {
        $rows    = $this->service->getData(['from_date' => '2025-01-01', 'to_date' => '2025-01-02']);
        $summary = $this->service->getSummary($rows);

        $this->assertCount(0, $summary);
    }

    // ── getDepartments ───────────────────────────────────────────────────────

    public function test_get_departments_returns_distinct_sorted_list(): void
    {
        $this->makeEmployee(['department' => 'IT']);
        $this->makeEmployee(['department' => 'HR']);
        $this->makeEmployee(['department' => 'IT']); // duplicate
        $this->makeEmployee(['department' => 'Finance']);

        $departments = $this->service->getDepartments();

        $this->assertEquals(['Finance', 'HR', 'IT'], $departments);
    }

    public function test_get_departments_excludes_null_and_empty(): void
    {
        $this->makeEmployee(['department' => 'IT']);
        $this->makeEmployee(['department' => null]);
        $this->makeEmployee(['department' => '']);

        $departments = $this->service->getDepartments();

        $this->assertEquals(['IT'], $departments);
    }

    public function test_get_departments_excludes_managers(): void
    {
        $this->makeEmployee(['department' => 'IT']);
        User::factory()->create(['role' => User::ROLE_MANAGER, 'department' => 'Executive']);

        $departments = $this->service->getDepartments();

        $this->assertNotContains('Executive', $departments);
        $this->assertContains('IT', $departments);
    }
}
