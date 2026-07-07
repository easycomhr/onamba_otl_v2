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
    // M(12)=PC Chuyên cần  N(13)=PC Cơm trưa  O(14)=PC Nặng nhọc (harmfulnessallowance)
    // P(15)=PC Thâm niên  Q(16)=PC Xăng  R(17)=PC Đi làm xa
    // S(18)=PC Điện thoại  T(19)=PC Đứng máy  U(20)=PC khác
    // V(21)=PC cơm OT  W(22)=Lương tháng 13  X(23)=Tổng thu nhập
    // Y(24)=BHXH 8%  Z(25)=BHYT 1.5%  AA(26)=BHTN 1%  AB(27)=Tổng BH 10.5%
    // AC(28)=Đoàn phí  AD(29)=Bản thân  AE(30)=Gia cảnh
    // AF(31)=Nghỉ không lương  AG(32)=Thu nhập tính thuế
    // AH(33)=Thuế TNCN TX  AI(34)=Thuế TNCN KTX 10%
    // AJ(35)=Tạm ứng  AK(36)=Thu nhập sau thuế  AL(37)=Hình thức TT  AM(38)=Thực nhận
    //
    // Các cột legacy từ tblsalaryhistory (không có trong Excel hiện tại, chỉ import qua CSV):
    // allowance_position (PC vị trí), allowance_proficiency (PC thành thạo),
    // allowance_steering (PC chỉ đạo), allowance_environment (PC môi trường),
    // allowance_special_work (PC đặc biệt), allowance_scene (PC hiện trường),
    // allowance_housing (PC thiếu nhà ở), allowance_lead (leadamount),
    // allowance_slippage (PC trượt giá), allowance_skill (PC kỹ năng),
    // allowance_language (PC ngôn ngữ), regular_amount (rglamt), adjustment, parking_free

    private const EXCEL_HEADER_ROWS = 11; // data starts at row 12

    // ─── Mánh xạ: field_name => nhãn tiếng Việt (dùng trong CSV template và import) ───
    // Key   = tên cột nội bộ (field name trong DB / $data[...])
    // Value = tiêu đề tiếng Việt hiển thị trong file CSV mẫu
    private const CSV_COLUMN_MAP = [
        // ─ Thông tin cơ bản ───────────────────────────────────────────────
        'employee_code'                 => 'Mã nhân viên',
        'month'                         => 'Tháng',
        'year'                          => 'Năm',
        'payment_method'                => 'Hình thức thanh toán',
        // ─ Thu nhập cơ bản ──────────────────────────────────────────
        'base_salary'                   => 'Lương căn bản',
        // ─ Phụ cấp legacy (từ tblsalaryhistory) ───────────────────────
        'allowance_position'            => 'PC Vị trí',
        'allowance_proficiency'         => 'PC Thành thạo / Tinh thông',
        'allowance_steering'            => 'PC Chỉ đạo / Kiêm nhiệm',
        'allowance_environment'         => 'PC Môi trường độc hại',
        'allowance_special_work'        => 'PC Công việc đặc biệt',
        'allowance_scene'               => 'PC Hiện trường',
        'allowance_housing'             => 'PC Thiếu nhà ở',
        'allowance_lead'                => 'Khoản Lead',
        'allowance_slippage'            => 'PC Trượt giá',
        'allowance_skill'               => 'PC Kỹ năng',
        'allowance_language'            => 'PC Ngoại ngữ',
        // ─ Phụ cấp hiện tại ────────────────────────────────────────
        'allowance_attendance'          => 'PC Chuyên cần',
        'allowance_lunch'               => 'PC Cơm trưa',
        'allowance_heavy_work'          => 'PC Nặng nhọc độc hại',
        'allowance_seniority'           => 'PC Thâm niên',
        'allowance_fuel'                => 'PC Xăng xe',
        'allowance_distance'            => 'PC Đi làm xa',
        'allowance_phone'               => 'PC Điện thoại',
        'allowance_machine'             => 'PC Đứng máy',
        'allowance_other'               => 'PC Khác',
        'allowance_ot_meal'             => 'PC Cơm tăng ca / OT',
        'salary_13th_month'             => 'Lương tháng 13',
        // ─ Tăng ca ──────────────────────────────────────────────────
        'ot_hours_150'                  => 'Tiền tăng ca 150%',
        'ot_hours_200'                  => 'Tiền tăng ca 200%',
        'ot_hours_300'                  => 'Tiền tăng ca 300%',
        'ot_total'                      => 'Tổng tiền tăng ca',
        // ─ Tổng thu nhập ───────────────────────────────────────────
        'income_total'                  => 'Cộng tổng thu nhập',
        // ─ Bảo hiểm ──────────────────────────────────────────────
        'deduction_bhxh'                => 'BHXH (8%)',
        'deduction_bhyt'                => 'BHYT (1.5%)',
        'deduction_bhtn'                => 'BHTN (1%)',
        'deduction_insurance_total'     => 'Tổng bảo hiểm (10.5%)',
        'h_insurance_included'          => 'Tham gia BHXH (1=có/0=không)',
        's_insurance_included'          => 'Tham gia BHYT (1=có/0=không)',
        'j_insurance_included'          => 'Tham gia BHTN (1=có/0=không)',
        // ─ Khấu trừ thuế / giảm trừ ──────────────────────────────
        'deduction_union_fee'           => 'Đoàn phí',
        'deduction_personal_exemption'  => 'Giảm trừ bản thân',
        'deduction_dependent_exemption' => 'Giảm trừ gia cảnh',
        'deduction_unpaid_leave'        => 'Nghỉ không lương',
        'taxable_income'                => 'Thu nhập chịu thuế',
        'deduction_pit_regular'         => 'Thuế TNCN thường xuyên',
        'deduction_pit_irregular'       => 'Thuế TNCN không thường xuyên (10%)',
        'deduction_advance'             => 'Tạm ứng',
        'income_after_tax'              => 'Thu nhập sau thuế',
        // ─ Khoản khác ──────────────────────────────────────────────
        'regular_amount'                => 'Khoản thường xuyên',
        'adjustment'                    => 'Điều chỉnh (tăng / giảm)',
        'parking_free'                  => 'Miễn phí đậu xe (1=có/0=không)',
        // ─ Thực nhận ───────────────────────────────────────────────
        'net'                           => 'Thực nhận',
        // ─ Backward compat ─────────────────────────────────────────
        'productivity_salary'           => 'Lương năng suất (cũ)',
        'working_days'                  => 'Số ngày công',
        'ot_night_allowance'            => 'PC Tăng ca đêm (cũ)',
        'deduction_penalty'             => 'Trừ phạt',
        'deductions_total'              => 'Tổng khấu trừ',
    ];

    /**
     * Trả về mapping field => label để controller dùng sinh CSV template.
     */
    public static function columnMap(): array
    {
        return self::CSV_COLUMN_MAP;
    }

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
        $rawHeaders = fgetcsv($handle);
        if ($rawHeaders === false) {
            fclose($handle);
            return ['success' => 0, 'skipped' => 0, 'errors' => [['row' => 1, 'employee_code' => '', 'reason' => 'File CSV không có dòng header.']]];
        }

        // Trim headers
        $rawHeaders = array_map('trim', $rawHeaders);

        // Build reverse map: label_tiếng_Việt => field_name
        // Hỗ trợ cả header tiếng Việt (mẫu mới) lẫn field name tiếng Anh (backward compat)
        $labelToField = array_flip(self::CSV_COLUMN_MAP);   // label => field
        $fieldNames   = array_keys(self::CSV_COLUMN_MAP);   // danh sách field hợp lệ

        // Map mỗi vị trí header sang tên field nội bộ
        $mappedHeaders = array_map(function (string $h) use ($labelToField, $fieldNames): string {
            if (isset($labelToField[$h])) {
                return $labelToField[$h];   // header tiếng Việt → field name
            }
            if (in_array($h, $fieldNames, true)) {
                return $h;                  // field name trực tiếp (backward compat)
            }
            return $h;                      // giữ nguyên nếu không khớp
        }, $rawHeaders);

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Map header => value (dùng field name nội bộ)
            $data         = array_combine($mappedHeaders, $row) ?: [];
            $employeeCode = trim($data['employee_code'] ?? '');

            // Bỏ qua hàng 2 — hàng gợi ý tên field (vd: "[employee_code]")
            // Được sinh ra bởi template mới; không phải dữ liệu thực
            if (str_starts_with($employeeCode, '[') && str_ends_with($employeeCode, ']')) {
                continue;
            }

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
                        'payment_method'                    => trim($data['payment_method']                    ?? ''),
                        // Thu nhập cơ bản
                        'base_salary'                       => $this->n($data['base_salary']                       ?? 0),
                        // Phụ cấp legacy (tblsalaryhistory)
                        'allowance_position'                => $this->n($data['allowance_position']                ?? 0),
                        'allowance_proficiency'             => $this->n($data['allowance_proficiency']             ?? 0),
                        'allowance_steering'                => $this->n($data['allowance_steering']                ?? 0),
                        'allowance_environment'             => $this->n($data['allowance_environment']             ?? 0),
                        'allowance_special_work'            => $this->n($data['allowance_special_work']            ?? 0),
                        'allowance_scene'                   => $this->n($data['allowance_scene']                   ?? 0),
                        'allowance_housing'                 => $this->n($data['allowance_housing']                 ?? 0),
                        'allowance_lead'                    => $this->n($data['allowance_lead']                    ?? 0),
                        'allowance_slippage'                => $this->n($data['allowance_slippage']                ?? 0),
                        'allowance_skill'                   => $this->n($data['allowance_skill']                   ?? 0),
                        'allowance_language'                => $this->n($data['allowance_language']                ?? 0),
                        // Phụ cấp hiện tại
                        'allowance_attendance'              => $this->n($data['allowance_attendance']              ?? 0),
                        'allowance_lunch'                   => $this->n($data['allowance_lunch']                   ?? 0),
                        'allowance_heavy_work'              => $this->n($data['allowance_heavy_work']              ?? 0),
                        'allowance_seniority'               => $this->n($data['allowance_seniority']               ?? 0),
                        'allowance_fuel'                    => $this->n($data['allowance_fuel']                    ?? 0),
                        'allowance_distance'                => $this->n($data['allowance_distance']                ?? 0),
                        'allowance_phone'                   => $this->n($data['allowance_phone']                   ?? 0),
                        'allowance_machine'                 => $this->n($data['allowance_machine']                 ?? 0),
                        'allowance_other'                   => $this->n($data['allowance_other']                   ?? 0),
                        'allowance_ot_meal'                 => $this->n($data['allowance_ot_meal']                 ?? 0),
                        'salary_13th_month'                 => $this->n($data['salary_13th_month']                 ?? 0),
                        // Tăng ca
                        'ot_hours_150'                      => $this->n($data['ot_hours_150']                      ?? 0),
                        'ot_hours_200'                      => $this->n($data['ot_hours_200']                      ?? 0),
                        'ot_hours_300'                      => $this->n($data['ot_hours_300']                      ?? 0),
                        'ot_total'                          => $this->n($data['ot_total']                          ?? 0),
                        // Tổng thu nhập
                        'income_total'                      => $this->n($data['income_total']                      ?? 0),
                        // Bảo hiểm
                        'deduction_bhxh'                    => $this->n($data['deduction_bhxh']                    ?? 0),
                        'deduction_bhyt'                    => $this->n($data['deduction_bhyt']                    ?? 0),
                        'deduction_bhtn'                    => $this->n($data['deduction_bhtn']                    ?? 0),
                        'deduction_insurance_total'         => $this->n($data['deduction_insurance_total']         ?? 0),
                        'h_insurance_included'              => (int) ($data['h_insurance_included']              ?? 1),
                        's_insurance_included'              => (int) ($data['s_insurance_included']              ?? 1),
                        'j_insurance_included'              => (int) ($data['j_insurance_included']              ?? 1),
                        // Khấu trừ
                        'deduction_union_fee'               => $this->n($data['deduction_union_fee']               ?? 0),
                        'deduction_personal_exemption'      => $this->n($data['deduction_personal_exemption']      ?? 0),
                        'deduction_dependent_exemption'     => $this->n($data['deduction_dependent_exemption']     ?? 0),
                        'deduction_unpaid_leave'            => $this->n($data['deduction_unpaid_leave']            ?? 0),
                        'taxable_income'                    => $this->n($data['taxable_income']                    ?? 0),
                        'deduction_pit_regular'             => $this->n($data['deduction_pit_regular']             ?? 0),
                        'deduction_pit_irregular'           => $this->n($data['deduction_pit_irregular']           ?? 0),
                        'deduction_advance'                 => $this->n($data['deduction_advance']                 ?? 0),
                        'income_after_tax'                  => $this->n($data['income_after_tax']                  ?? 0),
                        // Khoản khác
                        'regular_amount'                    => $this->n($data['regular_amount']                    ?? 0),
                        'adjustment'                        => $this->n($data['adjustment']                        ?? 0),
                        'parking_free'                      => (bool) ($data['parking_free']                      ?? false),
                        // Thực nhận
                        'net'                               => $this->n($data['net']                               ?? 0),
                        // Backward compat
                        'productivity_salary'               => $this->n($data['productivity_salary']               ?? 0),
                        'working_days'                      => $this->n($data['working_days']                      ?? 0),
                        'ot_night_allowance'                => $this->n($data['ot_night_allowance']                ?? 0),
                        'deduction_penalty'                 => $this->n($data['deduction_penalty']                 ?? 0),
                        'deductions_total'                  => $this->n($data['deductions_total']                  ?? 0),
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
                        // Các cột legacy không có trong Excel — set null (sẽ được import qua CSV)
                        'allowance_position'            => null,
                        'allowance_proficiency'         => null,
                        'allowance_steering'            => null,
                        'allowance_environment'         => null,
                        'allowance_special_work'        => null,
                        'allowance_scene'               => null,
                        'allowance_housing'             => null,
                        'allowance_lead'                => null,
                        'allowance_slippage'            => null,
                        'allowance_skill'               => null,
                        'allowance_language'            => null,
                        'regular_amount'                => null,
                        'adjustment'                    => null,
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
