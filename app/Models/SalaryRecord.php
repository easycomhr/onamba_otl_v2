<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'month', 'year',
        'group_name', 'region',        // B, C — plain text metadata
        'bank_account',                // G — encrypted
        'payment_method',              // AL — plain text
        // ── Thu nhập ─────────────────────────────────────────────────────
        'base_salary',                 // H  — Lương căn bản
        'allowance_position',          //    — PC vị trí (positionallowance)
        'allowance_proficiency',       //    — PC thành thạo (proficiencyallowance)
        'allowance_steering',          //    — PC chỉ đạo/kiêm nhiệm (steeringcapabilityallowance)
        'ot_total',                    // I  — Tăng ca tổng
        'ot_hours_150',                // J  — TC 150%
        'ot_hours_200',                // K  — TC 200%
        'ot_hours_300',                // L  — TC 300%
        'allowance_attendance',        // M  — PC chuyên cần
        'allowance_lunch',             // N  — PC cơm trưa
        'allowance_heavy_work',        // O  — PC nặng nhọc (harmfulnessallowance)
        'allowance_environment',       //    — PC môi trường (environmentalallowance)
        'allowance_special_work',      //    — PC công việc đặc biệt (specialworkallowance)
        'allowance_scene',             //    — PC hiện trường (sceneallowance)
        'allowance_housing',           //    — PC thiếu nhà ở (housingshortage)
        'allowance_lead',              //    — Khoản thu/tiền chì (leadamount)
        'allowance_seniority',         // P  — PC thâm niên (seniorityallowance)
        'allowance_fuel',              // Q  — PC xăng
        'allowance_distance',          // R  — PC đi làm xa
        'allowance_phone',             // S  — PC điện thoại
        'allowance_machine',           // T  — PC đứng máy
        'allowance_other',             // U  — PC khác
        'allowance_slippage',          //    — PC trượt giá (slippageallowance)
        'allowance_skill',             //    — PC kỹ năng (skill)
        'allowance_language',          //    — PC ngôn ngữ (languageallowance)
        'allowance_ot_meal',           // V  — PC cơm OT
        'salary_13th_month',           // W  — Lương tháng 13
        'income_total',                // X  — Cộng thu nhập
        // ── Bảo hiểm ─────────────────────────────────────────────────────
        'deduction_bhxh',              // Y  — BHXH 8%
        'deduction_bhyt',              // Z  — BHYT 1.5%
        'deduction_bhtn',              // AA — BHTN 1%
        'deduction_insurance_total',   // AB — Tổng BH 10.5%
        'h_insurance_included',        //    — Cờ BHXH (h_insurance_included)
        's_insurance_included',        //    — Cờ BHYT (s_insurance_included)
        'j_insurance_included',        //    — Cờ BHTN (j_insurance_included)
        // ── Khấu trừ ─────────────────────────────────────────────────────
        'deduction_union_fee',         // AC — Đoàn phí (isunion)
        'deduction_personal_exemption',   // AD — Giảm trừ bản thân
        'deduction_dependent_exemption',  // AE — Giảm trừ gia cảnh
        'deduction_unpaid_leave',      // AF — Nghỉ không lương
        'taxable_income',              // AG — Thu nhập tính thuế
        'deduction_pit_regular',       // AH — Thuế TNCN thường xuyên
        'deduction_pit_irregular',     // AI — Thuế TNCN KTX 10%
        'deduction_advance',           // AJ — Tạm ứng
        'income_after_tax',            // AK — Thu nhập sau thuế
        'net',                         // AM — Thực nhận
        // ── Khoản khác ───────────────────────────────────────────────────
        'regular_amount',              //    — Khoản thường xuyên (rglamt)
        'adjustment',                  //    — Điều chỉnh (adjust)
        'parking_free',                //    — Miễn phí đậu xe (parkingfree)
        // ── Kept for backward compat ──────────────────────────────────────
        'productivity_salary',
        'working_days',
        'ot_night_allowance',
        'deduction_penalty',
        'deductions_total',
    ];

    protected function casts(): array
    {
        return [
            // ── Encrypted columns (AES-256-CBC via APP_KEY) ───────────────
            'bank_account'                  => 'encrypted',
            'base_salary'                   => 'encrypted',
            // Phụ cấp từ legacy tblsalaryhistory
            'allowance_position'            => 'encrypted',
            'allowance_proficiency'         => 'encrypted',
            'allowance_steering'            => 'encrypted',
            'allowance_environment'         => 'encrypted',
            'allowance_special_work'        => 'encrypted',
            'allowance_scene'               => 'encrypted',
            'allowance_housing'             => 'encrypted',
            'allowance_lead'                => 'encrypted',
            'allowance_slippage'            => 'encrypted',
            'allowance_skill'               => 'encrypted',
            'allowance_language'            => 'encrypted',
            // Tăng ca
            'ot_total'                      => 'encrypted',
            'ot_hours_150'                  => 'encrypted',
            'ot_hours_200'                  => 'encrypted',
            'ot_hours_300'                  => 'encrypted',
            // Phụ cấp khác
            'allowance_attendance'          => 'encrypted',
            'allowance_lunch'               => 'encrypted',
            'allowance_heavy_work'          => 'encrypted',
            'allowance_seniority'           => 'encrypted',
            'allowance_fuel'                => 'encrypted',
            'allowance_distance'            => 'encrypted',
            'allowance_phone'               => 'encrypted',
            'allowance_machine'             => 'encrypted',
            'allowance_other'               => 'encrypted',
            'allowance_ot_meal'             => 'encrypted',
            'salary_13th_month'             => 'encrypted',
            'income_total'                  => 'encrypted',
            // Bảo hiểm
            'deduction_bhxh'                => 'encrypted',
            'deduction_bhyt'                => 'encrypted',
            'deduction_bhtn'                => 'encrypted',
            'deduction_insurance_total'     => 'encrypted',
            'h_insurance_included'          => 'integer',
            's_insurance_included'          => 'integer',
            'j_insurance_included'          => 'integer',
            // Khấu trừ
            'deduction_union_fee'           => 'encrypted',
            'deduction_personal_exemption'  => 'encrypted',
            'deduction_dependent_exemption' => 'encrypted',
            'deduction_unpaid_leave'        => 'encrypted',
            'taxable_income'                => 'encrypted',
            'deduction_pit_regular'         => 'encrypted',
            'deduction_pit_irregular'       => 'encrypted',
            'deduction_advance'             => 'encrypted',
            'income_after_tax'              => 'encrypted',
            'net'                           => 'encrypted',
            // Khoản khác
            'regular_amount'                => 'encrypted',
            'adjustment'                    => 'encrypted',
            'parking_free'                  => 'boolean',
            // ── Backward compat ───────────────────────────────────────────
            'productivity_salary'           => 'encrypted',
            'working_days'                  => 'encrypted',
            'ot_night_allowance'            => 'encrypted',
            'deduction_penalty'             => 'encrypted',
            'deductions_total'              => 'encrypted',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
