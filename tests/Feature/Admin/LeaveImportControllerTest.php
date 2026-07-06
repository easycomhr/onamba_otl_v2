<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\LeaveImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeaveImportControllerTest extends TestCase
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
            'employee_code' => 'EMP001',
        ]);
    }

    public function test_guest_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('admin.import.leave'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_index(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.import.leave'));

        $response->assertForbidden();
    }

    public function test_index_returns_200_for_manager(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.import.leave'));

        $response->assertOk();
        $response->assertViewIs('admin.imports.leave');
    }

    public function test_template_download_returns_csv_with_correct_headers(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.import.leave.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="mau_import_nghi_phep.csv"');

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('employee_code,leave_type,from_date,to_date,reason', $content);
        $this->assertStringContainsString('NV12345,annual,2026-03-18,2026-03-19', $content);
        $this->assertStringContainsString('NV12346,sick,2026-03-20,2026-03-20', $content);
        $this->assertStringContainsString('NV12347,annual,2026-03-25,2026-03-26', $content);
    }

    public function test_store_validation_fails_no_file(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.leave'))
            ->post(route('admin.import.leave.store'), []);

        $response->assertRedirect(route('admin.import.leave'));
        $response->assertSessionHasErrors(['file']);
    }

    public function test_store_validation_fails_wrong_mime(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.txt', 'plain text content');

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.leave'))
            ->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.import.leave'));
        $response->assertSessionHasErrors(['file']);
    }

    public function test_store_with_valid_csv_redirects_back_with_import_result(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,leave_type,from_date,to_date,reason\n{$this->employee->employee_code},annual,2026-03-20,2026-03-21,Family trip\n"
        );

        $this->mock(LeaveImportService::class, function ($mock): void {
            $mock->shouldReceive('import')
                ->once()
                ->withArgs(fn (string $path): bool => str_contains($path, 'storage/app/imports/leave/'))
                ->andReturn([
                    'imported' => 1,
                    'skipped' => 0,
                    'errors' => [],
                ]);
        });

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.leave'))
            ->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.import.leave'));
        $response->assertSessionHas('importResult', function (array $result): bool {
            return $result['imported'] === 1
                && $result['skipped'] === 0
                && $result['errors'] === [];
        });
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_store_result_shows_error_rows_in_session(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,leave_type,from_date,to_date,reason\nMISSING,annual,2026-03-21,2026-03-22,Unknown employee\n"
        );

        $this->mock(LeaveImportService::class, function ($mock): void {
            $mock->shouldReceive('import')
                ->once()
                ->withArgs(fn (string $path): bool => str_contains($path, 'storage/app/imports/leave/'))
                ->andReturn([
                    'imported' => 0,
                    'skipped' => 1,
                    'errors' => [[
                        'row' => 2,
                        'employee_code' => 'MISSING',
                        'reason' => 'Employee not found.',
                    ]],
                ]);
        });

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.leave'))
            ->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertSessionHas('importResult', function (array $result): bool {
            return $result['imported'] === 0
                && $result['skipped'] === 1
                && count($result['errors']) === 1
                && $result['errors'][0]['employee_code'] === 'MISSING'
                && $result['errors'][0]['reason'] === 'Employee not found.';
        });
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_store_returns_generic_error_when_service_throws_exception(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,leave_type,from_date,to_date,reason\n{$this->employee->employee_code},annual,2026-03-20,2026-03-21,Family trip\n"
        );

        $this->mock(LeaveImportService::class, function ($mock): void {
            $mock->shouldReceive('import')
                ->once()
                ->andThrow(new \RuntimeException('Import failed'));
        });

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.leave'))
            ->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.import.leave'));
        $response->assertSessionHasErrors(['error' => 'Đã xảy ra lỗi khi import dữ liệu.']);
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,leave_type,from_date,to_date,reason\n{$this->employee->employee_code},annual,2026-03-20,2026-03-21,Family trip\n"
        );

        $response = $this->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_store(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,leave_type,from_date,to_date,reason\n{$this->employee->employee_code},annual,2026-03-20,2026-03-21,Family trip\n"
        );

        $response = $this->actingAs($this->employee)
            ->post(route('admin.import.leave.store'), ['file' => $file]);

        $response->assertForbidden();
    }
}
