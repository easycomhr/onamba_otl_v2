<?php

namespace Tests\Unit\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveImportService $service;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveImportService();
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
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [' EMP001 ', ' annual ', ' 2026-03-20 ', ' 2026-03-21 ', ' Family trip '],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'reason' => 'Family trip',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
        $leaveRequest = LeaveRequest::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-03-20', $leaveRequest->from_date->toDateString());
        $this->assertSame('2026-03-21', $leaveRequest->to_date->toDateString());
    }

    public function test_skips_row_with_missing_required_data(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            ['', 'annual', '2026-03-20', '2026-03-21', 'Family trip'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, '', 'Dòng thiếu dữ liệu');
    }

    public function test_skips_row_with_invalid_leave_type(): void
    {
        $employee = $this->createEmployee('EMP002');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'maternity', '2026-03-20', '2026-03-21', 'Invalid leave type'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP002', 'leave_type không hợp lệ');
    }

    public function test_skips_row_with_invalid_from_date(): void
    {
        $employee = $this->createEmployee('EMP003');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', 'not-a-date', '2026-03-21', 'Bad from date'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP003', 'from_date không đúng định dạng');
    }

    public function test_skips_row_with_invalid_to_date(): void
    {
        $employee = $this->createEmployee('EMP004');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', '2026-03-20', 'not-a-date', 'Bad to date'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP004', 'to_date không đúng định dạng');
    }

    public function test_skips_row_when_to_date_is_before_from_date(): void
    {
        $employee = $this->createEmployee('EMP005');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', '2026-03-21', '2026-03-20', 'Date order invalid'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP005', 'to_date phải sau hoặc bằng from_date');
    }

    public function test_skips_row_with_reason_exceeding_500_chars(): void
    {
        $employee = $this->createEmployee('EMP006');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', '2026-03-20', '2026-03-21', str_repeat('a', 501)],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP006', 'reason vượt quá 500 ký tự');
    }

    public function test_skips_row_with_nonexistent_employee_code(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            ['EMP404', 'annual', '2026-03-20', '2026-03-21', 'Unknown employee'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP404', 'Employee not found.');
    }

    public function test_skips_row_when_leave_range_overlaps_existing_request(): void
    {
        $employee = $this->createEmployee('EMP007');

        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
            'reason' => 'Existing leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', '2026-03-21', '2026-03-23', 'Overlap leave'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSkippedResult($result, 2, 'EMP007', 'Đã có lịch nghỉ phép trùng khoảng ngày này', 1);
    }

    public function test_imports_adjacent_non_overlapping_ranges_for_same_employee(): void
    {
        $employee = $this->createEmployee('EMP008');

        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-20',
            'to_date' => '2026-03-22',
            'reason' => 'Existing leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'personal', '2026-03-23', '2026-03-24', 'Adjacent leave'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseCount('leave_requests', 2);
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'personal',
            'reason' => 'Adjacent leave',
        ]);
    }

    public function test_skips_second_row_when_rows_in_same_file_overlap(): void
    {
        $employee = $this->createEmployee('EMP009');
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$employee->employee_code, 'annual', '2026-03-20', '2026-03-21', 'First leave'],
            [$employee->employee_code, 'annual', '2026-03-21', '2026-03-22', 'Second overlapping leave'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame([[
            'row' => 3,
            'employee_code' => 'EMP009',
            'reason' => 'Đã có lịch nghỉ phép trùng khoảng ngày này',
        ]], $result['errors']);
        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_mixed_valid_and_invalid_rows_returns_correct_summary(): void
    {
        $validEmployee = $this->createEmployee('EMP010');
        $duplicateEmployee = $this->createEmployee('EMP011');

        LeaveRequest::create([
            'user_id' => $duplicateEmployee->id,
            'leave_type' => 'annual',
            'from_date' => '2026-03-25',
            'to_date' => '2026-03-26',
            'reason' => 'Existing leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
            [$validEmployee->employee_code, 'annual', '2026-03-20', '2026-03-21', 'Valid import'],
            ['', 'annual', '2026-03-20', '2026-03-21', 'Missing employee code'],
            [$duplicateEmployee->employee_code, 'annual', '2026-03-26', '2026-03-27', 'Duplicate leave'],
            ['EMP404', 'annual', '2026-03-28', '2026-03-29', 'Unknown employee'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(3, $result['skipped']);
        $this->assertCount(3, $result['errors']);
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $validEmployee->id,
            'reason' => 'Valid import',
        ]);
        $importedRequest = LeaveRequest::query()
            ->where('user_id', $validEmployee->id)
            ->where('reason', 'Valid import')
            ->firstOrFail();
        $this->assertSame('2026-03-20', $importedRequest->from_date->toDateString());
        $this->assertSame('2026-03-21', $importedRequest->to_date->toDateString());
        $this->assertDatabaseCount('leave_requests', 2);
    }

    public function test_empty_file_returns_zero_imported_zero_skipped(): void
    {
        $filePath = $this->createCsvFile([
            ['employee_code', 'leave_type', 'from_date', 'to_date', 'reason'],
        ]);

        $result = $this->service->import($filePath);

        $this->assertSame([
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ], $result);
        $this->assertDatabaseCount('leave_requests', 0);
    }

    private function createEmployee(string $employeeCode): User
    {
        return User::factory()->create([
            'employee_code' => $employeeCode,
            'role' => User::ROLE_EMPLOYEE,
        ]);
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    private function createCsvFile(array $rows): string
    {
        $basePath = tempnam(sys_get_temp_dir(), 'leave_import_');
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
        $this->assertDatabaseCount('leave_requests', $expectedDatabaseCount);
    }
}
