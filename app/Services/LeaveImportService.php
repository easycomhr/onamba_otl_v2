<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class LeaveImportService
{
    public function import(string $filePath): array
    {
        $sheet = Excel::toCollection(null, $filePath)->first() ?? collect();

        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $sheet->skip(1)->values()->each(function ($row, int $index) use (&$result) {
            $rowNumber = $index + 2;
            $values = $row instanceof Collection ? $row->values()->all() : collect($row)->values()->all();
            $employeeCode = trim((string) ($values[0] ?? ''));
            $leaveType = trim((string) ($values[1] ?? ''));
            $fromDate = trim((string) ($values[2] ?? ''));
            $toDate = trim((string) ($values[3] ?? ''));
            $reason = trim((string) ($values[4] ?? ''));

            $validationError = $this->validateRow($values, $rowNumber);

            if ($validationError !== null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $employeeCode,
                    'reason' => $validationError,
                ];

                return;
            }

            $user = User::query()
                ->where('employee_code', $employeeCode)
                ->where('role', 'employee')
                ->first();

            if ($user === null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $employeeCode,
                    'reason' => 'Employee not found.',
                ];

                return;
            }

            $exists = LeaveRequest::query()
                ->where('user_id', $user->id)
                ->whereDate('from_date', '<=', $toDate)
                ->whereDate('to_date', '>=', $fromDate)
                ->exists();

            if ($exists) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $employeeCode,
                    'reason' => 'Đã có lịch nghỉ phép trùng khoảng ngày này',
                ];

                return;
            }

            LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type' => $leaveType,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => $reason,
                'status' => LeaveRequest::STATUS_PENDING,
            ]);

            $result['imported']++;
        });

        return $result;
    }

    private function validateRow(array $row, int $rowNum): ?string
    {
        $employeeCode = trim((string) ($row[0] ?? ''));
        $leaveType = trim((string) ($row[1] ?? ''));
        $fromDate = trim((string) ($row[2] ?? ''));
        $toDate = trim((string) ($row[3] ?? ''));
        $reason = trim((string) ($row[4] ?? ''));

        if (
            $employeeCode === '' ||
            $leaveType === '' ||
            $fromDate === '' ||
            $toDate === '' ||
            $reason === ''
        ) {
            return 'Dòng thiếu dữ liệu';
        }

        if (! in_array($leaveType, array_keys(LeaveRequest::LEAVE_TYPES), true)) {
            return 'leave_type không hợp lệ';
        }

        if (strtotime($fromDate) === false) {
            return 'from_date không đúng định dạng';
        }

        if (strtotime($toDate) === false) {
            return 'to_date không đúng định dạng';
        }

        if (strtotime($toDate) < strtotime($fromDate)) {
            return 'to_date phải sau hoặc bằng from_date';
        }

        if (strlen($reason) > 500) {
            return 'reason vượt quá 500 ký tự';
        }

        return null;
    }
}
