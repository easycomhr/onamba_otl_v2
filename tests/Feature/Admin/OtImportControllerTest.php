<?php

namespace Tests\Feature\Admin;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OtImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => 'EMP001',
        ]);
    }

    public function test_guest_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('admin.import.ot'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_index(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.import.ot'));

        $response->assertForbidden();
    }

    public function test_index_returns_200_for_manager(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.import.ot'));

        $response->assertOk();
        $response->assertViewIs('admin.imports.ot');
    }

    public function test_template_download_returns_csv_with_correct_headers(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.import.ot.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="mau_import_ot.csv"');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
        $this->assertStringContainsString('employee_code,ot_date,hours,reason', $response->streamedContent());
    }

    public function test_store_validation_fails_no_file(): void
    {
        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.ot'))
            ->post(route('admin.import.ot.store'), []);

        $response->assertRedirect(route('admin.import.ot'));
        $response->assertSessionHasErrors(['file']);
    }

    public function test_store_validation_fails_wrong_mime(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.txt', 'plain text content');

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.ot'))
            ->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.import.ot'));
        $response->assertSessionHasErrors(['file']);
    }

    public function test_store_with_valid_csv_redirects_back_with_import_result(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,ot_date,hours,reason\n{$this->employee->employee_code},2026-03-20,3,Test reason\n"
        );

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.ot'))
            ->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.import.ot'));
        $response->assertSessionHas('importResult', function (array $result): bool {
            return $result['imported'] === 1
                && $result['skipped'] === 0
                && $result['errors'] === [];
        });
        $this->assertDatabaseHas('ot_requests', [
            'user_id' => $this->employee->id,
            'reason' => 'Test reason',
            'status' => OtRequest::STATUS_PENDING,
        ]);
        $this->assertSame('2026-03-20', OtRequest::query()->firstOrFail()->ot_date->toDateString());
    }

    public function test_store_result_shows_imported_count_in_session(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,ot_date,hours,reason\n{$this->employee->employee_code},2026-03-21,2.5,Deployment support\n"
        );

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.ot'))
            ->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertSessionHas('importResult', function (array $result): bool {
            return array_key_exists('imported', $result)
                && array_key_exists('skipped', $result)
                && array_key_exists('errors', $result)
                && $result['imported'] === 1;
        });
    }

    public function test_store_result_shows_error_rows_in_session(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,ot_date,hours,reason\nMISSING,2026-03-21,2.5,Unknown employee\n"
        );

        $response = $this->actingAs($this->manager)
            ->from(route('admin.import.ot'))
            ->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertSessionHas('importResult', function (array $result): bool {
            return $result['imported'] === 0
                && $result['skipped'] === 1
                && count($result['errors']) === 1
                && $result['errors'][0]['employee_code'] === 'MISSING'
                && $result['errors'][0]['reason'] === 'Employee not found.';
        });
        $this->assertDatabaseCount('ot_requests', 0);
    }

    public function test_guest_redirected_to_login_on_store(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,ot_date,hours,reason\n{$this->employee->employee_code},2026-03-20,3,Test reason\n"
        );

        $response = $this->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_store(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "employee_code,ot_date,hours,reason\n{$this->employee->employee_code},2026-03-20,3,Test reason\n"
        );

        $response = $this->actingAs($this->employee)
            ->post(route('admin.import.ot.store'), ['file' => $file]);

        $response->assertForbidden();
    }
}
