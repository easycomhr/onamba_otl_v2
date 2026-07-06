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
        'base_salary',                 // H
        'ot_total',                    // I  (Tăng ca - Total)
        'ot_hours_150',                // J
        'ot_hours_200',                // K
        'ot_hours_300',                // L
        'allowance_attendance',        // M
        'allowance_lunch',             // N
        'allowance_heavy_work',        // O
        'allowance_seniority',         // P
        'allowance_fuel',              // Q
        'allowance_distance',          // R
        'allowance_phone',             // S
        'allowance_machine',           // T
        'allowance_other',             // U
        'allowance_ot_meal',           // V
        'salary_13th_month',           // W
        'income_total',                // X
        // ── Bảo hiểm ─────────────────────────────────────────────────────
        'deduction_bhxh',              // Y
        'deduction_bhyt',              // Z
        'deduction_bhtn',              // AA
        'deduction_insurance_total',   // AB
        // ── Khấu trừ ─────────────────────────────────────────────────────
        'deduction_union_fee',         // AC
        'deduction_personal_exemption',   // AD
        'deduction_dependent_exemption',  // AE
        'deduction_unpaid_leave',      // AF
        'taxable_income',              // AG
        'deduction_pit_regular',       // AH
        'deduction_pit_irregular',     // AI
        'deduction_advance',           // AJ
        'income_after_tax',            // AK
        'net',                         // AM
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
            // Encrypted columns (AES-256-CBC via APP_KEY)
            'bank_account'                  => 'encrypted',
            'base_salary'                   => 'encrypted',
            'ot_total'                      => 'encrypted',
            'ot_hours_150'                  => 'encrypted',
            'ot_hours_200'                  => 'encrypted',
            'ot_hours_300'                  => 'encrypted',
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
            'deduction_bhxh'                => 'encrypted',
            'deduction_bhyt'                => 'encrypted',
            'deduction_bhtn'                => 'encrypted',
            'deduction_insurance_total'     => 'encrypted',
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
            // Backward compat
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
