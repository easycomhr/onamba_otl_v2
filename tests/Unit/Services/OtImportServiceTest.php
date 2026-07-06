<?php

namespace Tests\Unit\Services;

use App\Models\OtRequest;
use App\Models\User;
use App\Services\OtImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtImportService $service;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OtImportService();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_imports_valid_csv_row_and_returns_imported_count(): void
    {
        $employee = $this->createEmployee('EMP001');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '2.5', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('ot_requests', [
            'user_id' => $employee->id,
            'status' => OtRequest::STATUS_PENDING,
            'reason' => 'Release support',
        ]);
    }

    public function test_skips_row_with_missing_employee_code(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            ['', '2026-03-20', '2.5', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, '', 'Thiếu mã nhân viên.');
    }

    public function test_skips_row_with_invalid_date(): void
    {
        $employee = $this->createEmployee('EMP002');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '03/20/2026', '2.5', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP002', 'Ngày tăng ca không đúng định dạng.');
    }

    public function test_skips_row_with_hours_below_minimum(): void
    {
        $employee = $this->createEmployee('EMP003');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '0.4', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP003', 'Số giờ phải từ 0.5 đến 24.');
    }

    public function test_skips_row_with_hours_above_maximum(): void
    {
        $employee = $this->createEmployee('EMP004');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '25', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP004', 'Số giờ phải từ 0.5 đến 24.');
    }

    public function test_skips_row_with_missing_reason(): void
    {
        $employee = $this->createEmployee('EMP005');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '2.5', ''],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP005', 'Thiếu lý do.');
    }

    public function test_skips_row_with_reason_exceeding_500_chars(): void
    {
        $employee = $this->createEmployee('EMP006');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '2.5', str_repeat('a', 501)],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP006', 'Lý do vượt quá 500 ký tự.');
    }

    public function test_skips_row_with_nonexistent_employee_code(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            ['EMP404', '2026-03-20', '2.5', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP404', 'Không tìm thấy nhân viên.');
    }

    public function test_skips_duplicate_ot_same_user_same_date(): void
    {
        $employee = $this->createEmployee('EMP007');

        OtRequest::create([
            'user_id' => $employee->id,
            'ot_date' => '2026-03-20',
            'hours' => 2.0,
            'reason' => 'Existing OT',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '3', 'Duplicate attempt'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP007', 'Đã có lịch tăng ca vào ngày này.', 1);
        $this->assertDatabaseCount('ot_requests', 1);
    }

    public function test_imports_same_date_different_employees(): void
    {
        $firstEmployee = $this->createEmployee('EMP008');
        $secondEmployee = $this->createEmployee('EMP009');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$firstEmployee->employee_code, '2026-03-20', '2', 'First employee OT'],
            [$secondEmployee->employee_code, '2026-03-20', '3.5', 'Second employee OT'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseCount('ot_requests', 2);
    }

    public function test_imports_same_employee_different_dates(): void
    {
        $employee = $this->createEmployee('EMP010');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', '2', 'First OT'],
            [$employee->employee_code, '2026-03-21', '3', 'Second OT'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseCount('ot_requests', 2);
    }

    public function test_mixed_valid_and_invalid_rows_returns_correct_summary(): void
    {
        $validEmployee = $this->createEmployee('EMP011');
        $duplicateEmployee = $this->createEmployee('EMP012');

        OtRequest::create([
            'user_id' => $duplicateEmployee->id,
            'ot_date' => '2026-03-21',
            'hours' => 2.0,
            'reason' => 'Existing OT',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$validEmployee->employee_code, '2026-03-20', '2.5', 'Valid import'],
            ['', '2026-03-20', '2.5', 'Missing employee code'],
            [$duplicateEmployee->employee_code, '2026-03-21', '3', 'Duplicate row'],
            ['EMP404', '2026-03-22', '4', 'Unknown employee'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(3, $result['skipped']);
        $this->assertCount(3, $result['errors']);
        $this->assertDatabaseHas('ot_requests', [
            'user_id' => $validEmployee->id,
            'reason' => 'Valid import',
        ]);
        $importedRequest = OtRequest::query()
            ->where('user_id', $validEmployee->id)
            ->where('reason', 'Valid import')
            ->firstOrFail();
        $this->assertSame('2026-03-20', $importedRequest->ot_date->toDateString());
        $this->assertDatabaseCount('ot_requests', 2);
    }

    public function test_empty_file_returns_zero_imported_zero_skipped(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame([
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ], $result);
        $this->assertDatabaseCount('ot_requests', 0);
    }

    public function test_skips_row_that_is_completely_empty(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [' ', ' ', '', ' '],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, '', 'Dòng dữ liệu trống.');
    }

    public function test_skips_row_with_non_numeric_hours(): void
    {
        $employee = $this->createEmployee('EMP013');
        $filePath = $this->createCsvFile([
            ['employee_code', 'ot_date', 'hours', 'reason'],
            [$employee->employee_code, '2026-03-20', 'abc', 'Release support'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP013', 'Số giờ phải là số.');
    }

    private function createEmployee(string $employeeCode): User
    {
        return User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => $employeeCode,
        ]);
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    private function createCsvFile(array $rows): string
    {
        $basePath = tempnam(sys_get_temp_dir(), 'ot_import_');
        $path = $basePath . '.csv';
        rename($basePath, $path);
        $handle = fopen($path, 'w');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->tempFiles[] = $path;

        return $path;
    }

    private function assertSkippedResult(
        array $result,
        int $row,
        string $employeeCode,
        string $reason,
        int $expectedDatabaseCount = 0
    ): void {
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame([[
            'row' => $row,
            'employee_code' => $employeeCode,
            'reason' => $reason,
        ]], $result['errors']);
        $this->assertDatabaseCount('ot_requests', $expectedDatabaseCount);
    }
}
