<?php

namespace Tests\Feature\Admin;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeaveReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;
    private User $hrEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create([
            'role'          => User::ROLE_EMPLOYEE,
            'name'          => 'Nguyen Van A',
            'employee_code' => 'EMP001',
            'department'    => 'IT',
        ]);
        $this->hrEmployee = User::factory()->create([
            'role'          => User::ROLE_EMPLOYEE,
            'name'          => 'Tran Thi B',
            'employee_code' => 'EMP002',
            'department'    => 'HR',
        ]);
    }

    private function makeLeaveRequest(User $employee, array $attrs = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id'     => $employee->id,
            'approved_by' => $this->manager->id,
            'leave_type'  => 'annual',
            'from_date'   => '2026-02-10',
            'to_date'     => '2026-02-10',
            'days'        => 1,
            'reason'      => 'Test leave',
            'status'      => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ], $attrs));
    }

    public function test_guest_is_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('admin.reports.leave'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_index(): void
    {
        $response = $this->actingAs($this->employee)->get(route('admin.reports.leave'));

        $response->assertForbidden();
    }

    public function test_index_shows_form_without_results_when_no_dates(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.reports.leave'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.leave');
        $response->assertViewHas('employees');
        $response->assertViewHas('departments');
        $response->assertViewHas('leaveTypes');

        $leaveRequests = $response->viewData('leaveRequests');
        $summary = $response->viewData('summary');

        $this->assertCount(0, $leaveRequests);
        $this->assertCount(0, $summary);
    }

    public function test_index_returns_results_when_dates_provided(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'from_date' => '2026-02-10',
            'to_date'   => '2026-02-12',
            'days'      => 3,
        ]);

        $response = $this->actingAs($this->manager)->get(route('admin.reports.leave', [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertSee('Nguyen Van A');

        $leaveRequests = $response->viewData('leaveRequests');
        $summary = $response->viewData('summary');

        $this->assertCount(1, $leaveRequests);
        $this->assertCount(1, $summary);
    }

    public function test_index_filters_by_department(): void
    {
        $this->makeLeaveRequest($this->employee, ['from_date' => '2026-02-10', 'to_date' => '2026-02-10']);
        $this->makeLeaveRequest($this->hrEmployee, ['from_date' => '2026-02-10', 'to_date' => '2026-02-10']);

        $response = $this->actingAs($this->manager)->get(route('admin.reports.leave', [
            'from_date'  => '2026-02-01',
            'to_date'    => '2026-02-28',
            'department' => 'IT',
        ]));

        $response->assertOk();

        $leaveRequests = $response->viewData('leaveRequests');

        $this->assertCount(1, $leaveRequests);
        $this->assertSame($this->employee->id, $leaveRequests->first()->user_id);
    }

    public function test_index_filters_by_leave_type(): void
    {
        $this->makeLeaveRequest($this->employee, [
            'leave_type' => 'annual',
            'from_date'  => '2026-02-10',
            'to_date'    => '2026-02-10',
        ]);
        $this->makeLeaveRequest($this->hrEmployee, [
            'leave_type' => 'sick',
            'from_date'  => '2026-02-11',
            'to_date'    => '2026-02-11',
        ]);

        $response = $this->actingAs($this->manager)->get(route('admin.reports.leave', [
            'from_date'  => '2026-02-01',
            'to_date'    => '2026-02-28',
            'leave_type' => 'sick',
        ]));

        $response->assertOk();

        $leaveRequests = $response->viewData('leaveRequests');

        $this->assertCount(1, $leaveRequests);
        $this->assertSame('sick', $leaveRequests->first()->leave_type);
        $this->assertSame($this->hrEmployee->id, $leaveRequests->first()->user_id);
    }

    public function test_index_validation_fails_when_to_date_before_from_date(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.reports.leave', [
            'from_date' => '2026-02-28',
            'to_date'   => '2026-02-01',
        ]));

        $response->assertSessionHasErrors(['to_date']);
    }

    public function test_export_xlsx_downloads_file(): void
    {
        Excel::fake();

        $this->makeLeaveRequest($this->employee);

        $this->actingAs($this->manager)->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        Excel::assertDownloaded('leave-report.xlsx');
    }

    public function test_export_csv_downloads_file(): void
    {
        Excel::fake();

        $this->makeLeaveRequest($this->employee);

        $this->actingAs($this->manager)->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'csv',
        ]);

        Excel::assertDownloaded('leave-report.csv');
    }

    public function test_export_pdf_returns_download_response(): void
    {
        $this->makeLeaveRequest($this->employee);

        $response = $this->actingAs($this->manager)->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'pdf',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('Content-Type', '')));
    }

    public function test_export_fails_validation_without_dates(): void
    {
        $response = $this->actingAs($this->manager)->post(route('admin.reports.leave.export'), [
            'format' => 'xlsx',
        ]);

        $response->assertSessionHasErrors(['from_date', 'to_date']);
    }

    public function test_export_fails_validation_with_invalid_format(): void
    {
        $response = $this->actingAs($this->manager)->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'docx',
        ]);

        $response->assertSessionHasErrors(['format']);
    }

    public function test_guest_is_redirected_to_login_on_export(): void
    {
        $response = $this->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_export(): void
    {
        $response = $this->actingAs($this->employee)->post(route('admin.reports.leave.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        $response->assertForbidden();
    }
}
