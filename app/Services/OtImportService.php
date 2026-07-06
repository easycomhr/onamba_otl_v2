<?php

namespace App\Services;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class OtImportService
{
    public function import(string $filePath): array
    {
        $rows = Excel::toCollection(null, $filePath)->first() ?? collect();

        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $rows->skip(1)->values()->each(function ($row, int $index) use (&$result) {
            $rowNumber = $index + 2;
            $data = $this->extractRowData($row);
            $validationError = $this->validateRow($data);

            if ($validationError !== null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $data['employee_code'],
                    'reason' => $validationError,
                ];

                return;
            }

            $user = User::query()
                ->where('employee_code', $data['employee_code'])
                ->where('role', User::ROLE_EMPLOYEE)
                ->first();

            if ($user === null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $data['employee_code'],
                    'reason' => 'Không tìm thấy nhân viên.',
                ];

                return;
            }

            $exists = OtRequest::query()
                ->where('user_id', $user->id)
                ->whereDate('ot_date', $data['ot_date'])
                ->exists();

            if ($exists) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'employee_code' => $data['employee_code'],
                    'reason' => 'Đã có lịch tăng ca vào ngày này.',
                ];

                return;
            }

            OtRequest::create([
                'user_id' => $user->id,
                'ot_date' => $data['ot_date'],
                'hours' => (float) $data['hours'],
                'reason' => $data['reason'],
                'status' => OtRequest::STATUS_PENDING,
            ]);

            $result['imported']++;
        });

        return $result;
    }

    private function extractRowData(mixed $row): array
    {
        $values = $row instanceof Collection ? $row->values() : collect($row)->values();

        return [
            'employee_code' => trim((string) ($values->get(0) ?? '')),
            'ot_date' => trim((string) ($values->get(1) ?? '')),
            'hours' => $values->get(2),
            'reason' => trim((string) ($values->get(3) ?? '')),
        ];
    }

    private function validateRow(array $row): ?string
    {
        if (
            $row['employee_code'] === '' &&
            $row['ot_date'] === '' &&
            ($row['hours'] === null || $row['hours'] === '') &&
            $row['reason'] === ''
        ) {
            return 'Dòng dữ liệu trống.';
        }

        if ($row['employee_code'] === '') {
            return 'Thiếu mã nhân viên.';
        }

        if (! $this->isValidDate($row['ot_date'])) {
            return 'Ngày tăng ca không đúng định dạng.';
        }

        if (! is_numeric($row['hours'])) {
            return 'Số giờ phải là số.';
        }

        $hours = (float) $row['hours'];

        if ($hours < 0.5 || $hours > 24) {
            return 'Số giờ phải từ 0.5 đến 24.';
        }

        if ($row['reason'] === '') {
            return 'Thiếu lý do.';
        }

        if (mb_strlen($row['reason']) > 500) {
            return 'Lý do vượt quá 500 ký tự.';
        }

        return null;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
