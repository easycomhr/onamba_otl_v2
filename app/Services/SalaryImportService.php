<?php

namespace App\Services;

use App\Models\SalaryRecord;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalaryImportService
{
    // ─── Excel (legacy) column layout (0-based index) ───────────────────────
    // A(0)=STT  B(1)=Nhóm  C(2)=Khu vực  D(3)=Mã NV  E(4)=Họ tên  F(5)=Giới tính
    // G(6)=Tài khoản NH  H(7)=Lương căn bản
    // I(8)=Tăng ca Total  J(9)=TC 150%  K(10)=TC 200%  L(11)=TC 300%
    // M(12)=PC Chuyên cần  N(13)=PC Cơm trưa  O(14)=PC Nặng nhọc
    // P(15)=PC Thâm niên  Q(16)=PC Xăng  R(17)=PC Đi làm xa
    // S(18)=PC Điện thoại  T(19)=PC Đứng máy  U(20)=PC khác
    // V(21)=PC cơm OT  W(22)=Lương tháng 13  X(23)=Tổng thu nhập
    // Y(24)=BHXH 8%  Z(25)=BHYT 1.5%  AA(26)=BHTN 1%  AB(27)=Tổng BH 10.5%
    // AC(28)=Đoàn phí  AD(29)=Bản thân  AE(30)=Gia cảnh
    // AF(31)=Nghỉ không lương  AG(32)=Thu nhập tính thuế
    // AH(33)=Thuế TNCN TX  AI(34)=Thuế TNCN KTX 10%
    // AJ(35)=Tạm ứng  AK(36)=Thu nhập sau thuế  AL(37)=Hình thức TT  AM(38)=Thực nhận

    private const EXCEL_HEADER_ROWS = 11; // data starts at row 12

    // ─── CSV header names (must match template() in SalaryController) ────────
    private const CSV_COLUMNS = [
        'employee_code', 'month', 'year', 'payment_method',
        'base_salary', 'productivity_salary',
        'allowance_attendance', 'allowance_seniority', 'allowance_other', 'income_total',
        'working_days', 'ot_hours_150', 'ot_hours_200', 'ot_night_allowance', 'ot_total',
        'deduction_bhxh', 'deduction_penalty', 'deduction_advance', 'deductions_total', 'net',
    ];

    /**
     * @param  string  $filePath  Absolute path to uploaded file
     * @param  int     $month     Kỳ lương: tháng (1–12)
     * @param  int     $year      Kỳ lương: năm
     * @return array{success: int, skipped: int, errors: list<array{row:int, employee_code:string, reason:string}>}
     */
    public function import(string $filePath, int $month, int $year): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->importCsv($filePath, $month, $year);
        }

        return $this->importExcel($filePath, $month, $year);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV import — header-based, row 1 = headers, row 2+ = data
    // ─────────────────────────────────────────────────────────────────────────
    private function importCsv(string $filePath, int $month, int $year): array
    {
        $success = 0;
        $skipped = 0;
        $errors  = [];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['success' => 0, 'skipped' => 0, 'errors' => [['row' => 0, 'employee_code' => '', 'reason' => 'Không thể mở file.']]];
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return ['success' => 0, 'skipped' => 0, 'errors' => [['row' => 1, 'employee_code' => '', 'reason' => 'File CSV không có dòng header.']]];
        }

        // Trim headers
        $headers = array_map('trim', $headers);

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Map header => value
            $data         = array_combine($headers, $row) ?: [];
            $employeeCode = trim($data['employee_code'] ?? '');

            if (empty($employeeCode)) {
                $skipped++;
                continue;
            }

            $user = User::where('employee_code', $employeeCode)->first();
            if (! $user) {
                $errors[] = [
                    'row'           => $rowNum,
                    'employee_code' => $employeeCode,
                    'reason'        => 'Không tìm thấy nhân viên với mã này',
                ];
                continue;
            }

            try {
                SalaryRecord::updateOrCreate(
                    ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                    [
                        'payment_method'       => trim($data['payment_method']       ?? ''),
                        'base_salary'          => $this->n($data['base_salary']          ?? 0),
                        'productivity_salary'  => $this->n($data['productivity_salary']  ?? 0),
                        'allowance_attendance' => $this->n($data['allowance_attendance'] ?? 0),
                        'allowance_seniority'  => $this->n($data['allowance_seniority']  ?? 0),
                        'allowance_other'      => $this->n($data['allowance_other']      ?? 0),
                        'income_total'         => $this->n($data['income_total']         ?? 0),
                        'working_days'         => $this->n($data['working_days']         ?? 0),
                        'ot_hours_150'         => $this->n($data['ot_hours_150']         ?? 0),
                        'ot_hours_200'         => $this->n($data['ot_hours_200']         ?? 0),
                        'ot_night_allowance'   => $this->n($data['ot_night_allowance']   ?? 0),
                        'ot_total'             => $this->n($data['ot_total']             ?? 0),
                        'deduction_bhxh'       => $this->n($data['deduction_bhxh']       ?? 0),
                        'deduction_penalty'    => $this->n($data['deduction_penalty']    ?? 0),
                        'deduction_advance'    => $this->n($data['deduction_advance']    ?? 0),
                        'deductions_total'     => $this->n($data['deductions_total']     ?? 0),
                        'net'                  => $this->n($data['net']                  ?? 0),
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'           => $rowNum,
                    'employee_code' => $employeeCode,
                    'reason'        => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        return compact('success', 'skipped', 'errors');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Excel import — legacy column-index-based (11 header rows)
    // ─────────────────────────────────────────────────────────────────────────
    private function importExcel(string $filePath, int $month, int $year): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $all         = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        // Skip first 11 header rows (rows 1–11); data starts at row 12
        $rows    = array_slice($all, self::EXCEL_HEADER_ROWS);
        $success = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($rows as $index => $row) {
            $rowNum       = $index + self::EXCEL_HEADER_ROWS + 1;
            $employeeCode = trim((string) ($row[3] ?? '')); // col D

            if (empty($employeeCode)) {
                $skipped++;
                continue;
            }

            $user = User::where('employee_code', $employeeCode)->first();
            if (! $user) {
                $errors[] = [
                    'row'           => $rowNum,
                    'employee_code' => $employeeCode,
                    'reason'        => 'Không tìm thấy nhân viên với mã này',
                ];
                continue;
            }

            try {
                SalaryRecord::updateOrCreate(
                    ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                    [
                        'group_name'                    => trim((string) ($row[1]  ?? '')),
                        'region'                        => trim((string) ($row[2]  ?? '')),
                        'bank_account'                  => trim((string) ($row[6]  ?? '')),
                        'base_salary'                   => $this->n($row[7]),
                        'ot_total'                      => $this->n($row[8]),
                        'ot_hours_150'                  => $this->n($row[9]),
                        'ot_hours_200'                  => $this->n($row[10]),
                        'ot_hours_300'                  => $this->n($row[11]),
                        'allowance_attendance'          => $this->n($row[12]),
                        'allowance_lunch'               => $this->n($row[13]),
                        'allowance_heavy_work'          => $this->n($row[14]),
                        'allowance_seniority'           => $this->n($row[15]),
                        'allowance_fuel'                => $this->n($row[16]),
                        'allowance_distance'            => $this->n($row[17]),
                        'allowance_phone'               => $this->n($row[18]),
                        'allowance_machine'             => $this->n($row[19]),
                        'allowance_other'               => $this->n($row[20]),
                        'allowance_ot_meal'             => $this->n($row[21]),
                        'salary_13th_month'             => $this->n($row[22]),
                        'income_total'                  => $this->n($row[23]),
                        'deduction_bhxh'                => $this->n($row[24]),
                        'deduction_bhyt'                => $this->n($row[25]),
                        'deduction_bhtn'                => $this->n($row[26]),
                        'deduction_insurance_total'     => $this->n($row[27]),
                        'deduction_union_fee'           => $this->n($row[28]),
                        'deduction_personal_exemption'  => $this->n($row[29]),
                        'deduction_dependent_exemption' => $this->n($row[30]),
                        'deduction_unpaid_leave'        => $this->n($row[31]),
                        'taxable_income'                => $this->n($row[32]),
                        'deduction_pit_regular'         => $this->n($row[33]),
                        'deduction_pit_irregular'       => $this->n($row[34]),
                        'deduction_advance'             => $this->n($row[35]),
                        'income_after_tax'              => $this->n($row[36]),
                        'payment_method'                => trim((string) ($row[37] ?? '')),
                        'net'                           => $this->n($row[38]),
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'           => $rowNum,
                    'employee_code' => $employeeCode,
                    'reason'        => $e->getMessage(),
                ];
            }
        }

        return compact('success', 'skipped', 'errors');
    }

    private function n(mixed $value): float
    {
        return (float) str_replace([',', ' '], '', (string) ($value ?? 0));
    }
}
