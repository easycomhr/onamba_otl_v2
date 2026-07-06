<?php

namespace Tests\Unit\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveReportService $service;
    private User $manager;
    private User $itEmployee;
    private User $hrEmployee;
    private User $financeEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveReportService();
        $this->manager = User::factory()->create([
            'role'       => User::ROLE_MANAGER,
            'department' => 'Executive',
        ]);

        $this->itEmployee = $this->makeEmployee([
            'name'          => 'Nguyen Van A',
            'employee_code' => 'EMP001',
            'department'    => 'IT',
        ]);

        $this->hrEmployee = $this->makeEmployee([
            'name'          => 'Tran Thi B',
            'employee_code' => 'EMP002',
            'department'    => 'HR',
        ]);

        $this->financeEmployee = $this->makeEmployee([
            'name'          => 'Le Van C',
            'employee_code' => 'EMP003',
            'department'    => 'Finance',
        ]);
    }

    private function makeEmployee(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_EMPLOYEE,
        ], $attrs));
    }

    private function makeLeaveRequest(User $employee, array $attrs = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id'     => $employee->id,
            'approved_by' => $this->manager->id,
            'leave_type'  => 'annual',
            'from_date'   => '2026-01-10',
            'to_date'     => '2026-01-10',
            'days'        => 1,
            'reason'      => 'Test leave request',
            'status'      => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ], $attrs));
    }

    public function test_get_data_returns_only_approved_requests(): void
    {
        $this->makeLeaveRequest($this->itEmployee, ['status' => LeaveRequest::STATUS_APPROVED]);
        $this->makeLeaveRequest($this->itEmployee, [
            'status'      => LeaveRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'from_date'   => '2026-01-11',
            'to_date'     => '2026-01-11',
        ]);
        $this->makeLeaveRequest($this->itEmployee, [
            'status'      => LeaveRequest::STATUS_REJECTED,
            'approved_at' => null,
            'rejected_at' => now(),
            'from_date'   => '2026-01-12',
            'to_date'     => '2026-01-12',
        ]);

        $result = $this->service->getData([]);

        $this->assertCount(1, $result);
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $result->first()->status);
    }

    public function test_get_data_true_overlap_includes_spanning_record(): void
    {
        $spanningLeave = $this->makeLeaveRequest($this->itEmployee, [
            'from_date' => '2026-01-01',
            'to_date'   => '2026-01-31',
            'days'      => 31,
        ]);

        $result = $this->service->getData([
            'from_date' => '2026-01-10',
            'to_date'   => '2026-01-20',
        ]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $spanningLeave->id));
    }

    public function test_get_data_excludes_non_overlapping_records(): void
    {
        $this->makeLeaveRequest($this->itEmployee, [
            'from_date' => '2026-01-01',
            'to_date'   => '2026-01-09',
            'days'      => 9,
        ]);

        $this->makeLeaveRequest($this->hrEmployee, [
            'from_date' => '2026-01-21',
            'to_date'   => '2026-01-25',
            'days'      => 5,
        ]);

        $result = $this->service->getData([
            'from_date' => '2026-01-10',
            'to_date'   => '2026-01-20',
        ]);

        $this->assertCount(0, $result);
    }

    public function test_get_data_filters_by_employee_id(): void
    {
        $this->makeLeaveRequest($this->itEmployee, ['from_date' => '2026-01-10', 'to_date' => '2026-01-10']);
        $this->makeLeaveRequest($this->hrEmployee, ['from_date' => '2026-01-10', 'to_date' => '2026-01-10']);

        $result = $this->service->getData(['employee_id' => $this->itEmployee->id]);

        $this->assertCount(1, $result);
        $this->assertSame($this->itEmployee->id, $result->first()->user_id);
    }

    public function test_get_data_filters_by_department(): void
    {
        $this->makeLeaveRequest($this->itEmployee);
        $this->makeLeaveRequest($this->hrEmployee);

        $result = $this->service->getData(['department' => 'IT']);

        $this->assertCount(1, $result);
        $this->assertSame($this->itEmployee->id, $result->first()->user_id);
        $this->assertSame('IT', $result->first()->employee->department);
    }

    public function test_get_data_filters_by_leave_type(): void
    {
        $this->makeLeaveRequest($this->itEmployee, ['leave_type' => 'annual']);
        $this->makeLeaveRequest($this->hrEmployee, [
            'leave_type' => 'sick',
            'from_date'  => '2026-01-11',
            'to_date'    => '2026-01-11',
        ]);

        $result = $this->service->getData(['leave_type' => 'sick']);

        $this->assertCount(1, $result);
        $this->assertSame('sick', $result->first()->leave_type);
    }

    public function test_get_data_with_all_filters_combined(): void
    {
        $matching = $this->makeLeaveRequest($this->itEmployee, [
            'leave_type' => 'personal',
            'from_date'  => '2026-01-12',
            'to_date'    => '2026-01-14',
            'days'       => 3,
        ]);

        $this->makeLeaveRequest($this->itEmployee, [
            'leave_type' => 'annual',
            'from_date'  => '2026-01-12',
            'to_date'    => '2026-01-14',
            'days'       => 3,
        ]);
        $this->makeLeaveRequest($this->hrEmployee, [
            'leave_type' => 'personal',
            'from_date'  => '2026-01-12',
            'to_date'    => '2026-01-14',
            'days'       => 3,
        ]);
        $this->makeLeaveRequest($this->itEmployee, [
            'leave_type' => 'personal',
            'from_date'  => '2026-01-01',
            'to_date'    => '2026-01-05',
            'days'       => 5,
        ]);

        $result = $this->service->getData([
            'from_date'   => '2026-01-10',
            'to_date'     => '2026-01-20',
            'employee_id' => $this->itEmployee->id,
            'department'  => 'IT',
            'leave_type'  => 'personal',
        ]);

        $this->assertCount(1, $result);
        $this->assertSame($matching->id, $result->first()->id);
    }

    public function test_get_data_returns_empty_when_no_matches(): void
    {
        $this->makeLeaveRequest($this->financeEmployee, [
            'leave_type' => 'unpaid',
            'from_date'  => '2026-02-01',
            'to_date'    => '2026-02-03',
            'days'       => 3,
        ]);

        $result = $this->service->getData([
            'from_date'  => '2026-01-01',
            'to_date'    => '2026-01-31',
            'department' => 'IT',
        ]);

        $this->assertCount(0, $result);
    }

    public function test_get_summary_aggregates_total_times_and_total_days(): void
    {
        $this->makeLeaveRequest($this->itEmployee, [
            'from_date' => '2026-01-01',
            'to_date'   => '2026-01-01',
            'days'      => 1,
        ]);
        $this->makeLeaveRequest($this->itEmployee, [
            'from_date' => '2026-01-02',
            'to_date'   => '2026-01-03',
            'days'      => 2,
        ]);
        $this->makeLeaveRequest($this->itEmployee, [
            'from_date' => '2026-01-06',
            'to_date'   => '2026-01-08',
            'days'      => 3,
        ]);

        $rows = $this->service->getData(['employee_id' => $this->itEmployee->id]);
        $summary = $this->service->getSummary($rows);

        $this->assertCount(1, $summary);
        $this->assertSame($this->itEmployee->id, $summary->first()['employee']->id);
        $this->assertSame(3, $summary->first()['total_times']);
        $this->assertSame(6, $summary->first()['total_days']);
    }

    public function test_get_summary_returns_empty_for_empty_collection(): void
    {
        $rows = $this->service->getData([
            'from_date' => '2026-03-01',
            'to_date'   => '2026-03-31',
        ]);

        $summary = $this->service->getSummary($rows);

        $this->assertCount(0, $summary);
    }

    public function test_get_departments_returns_distinct_sorted_list(): void
    {
        $this->makeEmployee(['department' => 'IT']);
        $this->makeEmployee(['department' => 'HR']);
        $this->makeEmployee(['department' => 'IT']);
        $this->makeEmployee(['department' => 'Finance']);

        $departments = $this->service->getDepartments();

        $this->assertSame(['Finance', 'HR', 'IT'], $departments);
    }

    public function test_get_departments_excludes_managers(): void
    {
        $departments = $this->service->getDepartments();

        $this->assertNotContains('Executive', $departments);
        $this->assertContains('Finance', $departments);
        $this->assertContains('HR', $departments);
        $this->assertContains('IT', $departments);
    }

    public function test_get_leave_types_returns_constants(): void
    {
        $this->assertSame(LeaveRequest::LEAVE_TYPES, $this->service->getLeaveTypes());
    }
}
