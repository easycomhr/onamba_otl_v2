<?php

namespace App\Services;

use App\Models\SalaryRecord;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalaryImportService
{
    // Excel column layout (0-based index):
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

    private const HEADER_ROWS = 11; // data starts at row 12

    /**
     * @param  string  $filePath  Absolute path to uploaded file
     * @param  int     $month     Kỳ lương: tháng (1–12)
     * @param  int     $year      Kỳ lương: năm
     * @return array{success: int, skipped: int, errors: list<array{row:int, employee_code:string, reason:string}>}
     */
    public function import(string $filePath, int $month, int $year): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $all         = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        // Skip first 11 header rows (rows 1–11); data starts at row 12
        $rows    = array_slice($all, self::HEADER_ROWS);
        $success = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($rows as $index => $row) {
            $rowNum       = $index + self::HEADER_ROWS + 1; // 1-based excel row number
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
                        // Metadata
                        'group_name'                    => trim((string) ($row[1] ?? '')),  // B
                        'region'                        => trim((string) ($row[2] ?? '')),  // C
                        'bank_account'                  => trim((string) ($row[6] ?? '')),  // G
                        // Thu nhập
                        'base_salary'                   => $this->n($row[7]),   // H
                        'ot_total'                      => $this->n($row[8]),   // I
                        'ot_hours_150'                  => $this->n($row[9]),   // J
                        'ot_hours_200'                  => $this->n($row[10]),  // K
                        'ot_hours_300'                  => $this->n($row[11]),  // L
                        'allowance_attendance'          => $this->n($row[12]),  // M
                        'allowance_lunch'               => $this->n($row[13]),  // N
                        'allowance_heavy_work'          => $this->n($row[14]),  // O
                        'allowance_seniority'           => $this->n($row[15]),  // P
                        'allowance_fuel'                => $this->n($row[16]),  // Q
                        'allowance_distance'            => $this->n($row[17]),  // R
                        'allowance_phone'               => $this->n($row[18]),  // S
                        'allowance_machine'             => $this->n($row[19]),  // T
                        'allowance_other'               => $this->n($row[20]),  // U
                        'allowance_ot_meal'             => $this->n($row[21]),  // V
                        'salary_13th_month'             => $this->n($row[22]),  // W
                        'income_total'                  => $this->n($row[23]),  // X
                        // Bảo hiểm
                        'deduction_bhxh'                => $this->n($row[24]),  // Y
                        'deduction_bhyt'                => $this->n($row[25]),  // Z
                        'deduction_bhtn'                => $this->n($row[26]),  // AA
                        'deduction_insurance_total'     => $this->n($row[27]),  // AB
                        // Khấu trừ
                        'deduction_union_fee'           => $this->n($row[28]),  // AC
                        'deduction_personal_exemption'  => $this->n($row[29]),  // AD
                        'deduction_dependent_exemption' => $this->n($row[30]),  // AE
                        'deduction_unpaid_leave'        => $this->n($row[31]),  // AF
                        'taxable_income'                => $this->n($row[32]),  // AG
                        'deduction_pit_regular'         => $this->n($row[33]),  // AH
                        'deduction_pit_irregular'       => $this->n($row[34]),  // AI
                        'deduction_advance'             => $this->n($row[35]),  // AJ
                        'income_after_tax'              => $this->n($row[36]),  // AK
                        'payment_method'                => trim((string) ($row[37] ?? '')), // AL
                        'net'                           => $this->n($row[38]),  // AM
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
