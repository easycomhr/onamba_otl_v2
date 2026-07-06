<?php

namespace Tests\Feature\Admin;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class OtReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;

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
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeOtRequest(User $employee, array $attrs = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'user_id'        => $employee->id,
            'ot_date'        => '2026-02-15',
            'hours'          => 2.0,
            'approved_hours' => 2.0,
            'reason'         => 'Test OT',
            'status'         => OtRequest::STATUS_APPROVED,
            'approved_at'    => now(),
        ], $attrs));
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.reports.ot'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_role_cannot_access(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.reports.ot'));

        $response->assertForbidden();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_form_without_results_when_no_dates(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.reports.ot'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.ot');
        $response->assertViewHas('employees');
        $response->assertViewHas('departments');

        $otRequests = $response->viewData('otRequests');
        $this->assertCount(0, $otRequests);
    }

    public function test_index_returns_results_when_dates_provided(): void
    {
        $this->makeOtRequest($this->employee, ['ot_date' => '2026-02-10']);

        $response = $this->actingAs($this->manager)->get(route('admin.reports.ot', [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertSee('Nguyen Van A');

        $otRequests = $response->viewData('otRequests');
        $this->assertCount(1, $otRequests);
    }

    public function test_index_filters_by_department(): void
    {
        $hrEmployee = User::factory()->create([
            'role'          => User::ROLE_EMPLOYEE,
            'name'          => 'Tran Thi B',
            'employee_code' => 'EMP002',
            'department'    => 'HR',
        ]);

        $this->makeOtRequest($this->employee, ['ot_date' => '2026-02-10']); // IT
        $this->makeOtRequest($hrEmployee, ['ot_date' => '2026-02-10']);     // HR

        $response = $this->actingAs($this->manager)->get(route('admin.reports.ot', [
            'from_date'  => '2026-02-01',
            'to_date'    => '2026-02-28',
            'department' => 'IT',
        ]));

        $response->assertOk();
        $otRequests = $response->viewData('otRequests');
        $this->assertCount(1, $otRequests);
        $this->assertEquals($this->employee->id, $otRequests->first()->user_id);
    }

    public function test_index_validation_fails_when_to_date_before_from_date(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.reports.ot', [
            'from_date' => '2026-02-28',
            'to_date'   => '2026-02-01',
        ]));

        $response->assertSessionHasErrors(['to_date']);
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function test_export_xlsx_downloads_file(): void
    {
        Excel::fake();

        $this->makeOtRequest($this->employee, ['ot_date' => '2026-02-10']);

        $response = $this->actingAs($this->manager)->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        Excel::assertDownloaded('ot-report.xlsx');
    }

    public function test_export_csv_downloads_file(): void
    {
        Excel::fake();

        $this->makeOtRequest($this->employee, ['ot_date' => '2026-02-10']);

        $response = $this->actingAs($this->manager)->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'csv',
        ]);

        Excel::assertDownloaded('ot-report.csv');
    }

    public function test_export_pdf_returns_download_response(): void
    {
        $this->makeOtRequest($this->employee, ['ot_date' => '2026-02-10']);

        $response = $this->actingAs($this->manager)->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'pdf',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('Content-Type', '')));
    }

    public function test_export_fails_validation_without_dates(): void
    {
        $response = $this->actingAs($this->manager)->post(route('admin.reports.ot.export'), [
            'format' => 'xlsx',
        ]);

        $response->assertSessionHasErrors(['from_date', 'to_date']);
    }

    public function test_export_fails_validation_with_invalid_format(): void
    {
        $response = $this->actingAs($this->manager)->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'docx',
        ]);

        $response->assertSessionHasErrors(['format']);
    }

    public function test_export_employee_role_cannot_access(): void
    {
        $response = $this->actingAs($this->employee)->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        $response->assertForbidden();
    }

    public function test_export_guest_is_redirected_to_login(): void
    {
        $response = $this->post(route('admin.reports.ot.export'), [
            'from_date' => '2026-02-01',
            'to_date'   => '2026-02-28',
            'format'    => 'xlsx',
        ]);

        $response->assertRedirect(route('login'));
    }
}
