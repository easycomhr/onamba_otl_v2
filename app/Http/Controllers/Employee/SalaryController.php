<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalaryController extends Controller
{
    public function index(): View
    {
        $salaries = SalaryRecord::where('user_id', Auth::id())
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (SalaryRecord $r) => [
                'month'          => $r->month,
                'year'           => $r->year,
                'total_income'   => (float) $r->income_total,
                'advance'        => (float) $r->deduction_advance,
                'net'            => (float) $r->net,
                'status'         => 'paid',
                'payment_method' => $r->payment_method,
            ]);

        return view('employee.salary.history', compact('salaries'));
    }

    public function show(int $month, int $year): View
    {
        $r = SalaryRecord::where('user_id', Auth::id())
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();

        $salary = [
            'month'          => $r->month,
            'year'           => $r->year,
            'net'            => (float) $r->net,
            'payment_method' => $r->payment_method,

            // ── Thu nhập & Phụ cấp ───────────────────────────────────────────
            'income' => [
                ['label' => 'Lương căn bản',           'value' => (float) $r->base_salary,            'is_money' => true],
                // Phụ cấp từ tblsalaryhistory
                ['label' => 'PC Vị trí',               'value' => (float) $r->allowance_position,     'is_money' => true],
                ['label' => 'PC Thành thạo',           'value' => (float) $r->allowance_proficiency,  'is_money' => true],
                ['label' => 'PC Chỉ đạo/Kiêm nhiệm',  'value' => (float) $r->allowance_steering,     'is_money' => true],
                ['label' => 'PC Môi trường',           'value' => (float) $r->allowance_environment,  'is_money' => true],
                ['label' => 'PC Công việc đặc biệt',   'value' => (float) $r->allowance_special_work, 'is_money' => true],
                ['label' => 'PC Hiện trường',          'value' => (float) $r->allowance_scene,        'is_money' => true],
                ['label' => 'PC Thiếu nhà ở',          'value' => (float) $r->allowance_housing,      'is_money' => true],
                ['label' => 'Khoản thu (Lead)',         'value' => (float) $r->allowance_lead,         'is_money' => true],
                ['label' => 'PC Trượt giá',            'value' => (float) $r->allowance_slippage,     'is_money' => true],
                ['label' => 'PC Kỹ năng',              'value' => (float) $r->allowance_skill,        'is_money' => true],
                ['label' => 'PC Ngôn ngữ',             'value' => (float) $r->allowance_language,     'is_money' => true],
                // Phụ cấp hiện tại
                ['label' => 'Tăng ca 150%',            'value' => (float) $r->ot_hours_150,           'is_money' => true],
                ['label' => 'Tăng ca 200%',            'value' => (float) $r->ot_hours_200,           'is_money' => true],
                ['label' => 'Tăng ca 300%',            'value' => (float) $r->ot_hours_300,           'is_money' => true],
                ['label' => 'PC Chuyên cần',           'value' => (float) $r->allowance_attendance,   'is_money' => true],
                ['label' => 'PC Cơm trưa',             'value' => (float) $r->allowance_lunch,        'is_money' => true],
                ['label' => 'PC Nặng nhọc',            'value' => (float) $r->allowance_heavy_work,   'is_money' => true],
                ['label' => 'PC Thâm niên',            'value' => (float) $r->allowance_seniority,    'is_money' => true],
                ['label' => 'PC Xăng',                 'value' => (float) $r->allowance_fuel,         'is_money' => true],
                ['label' => 'PC Đi làm xa',            'value' => (float) $r->allowance_distance,     'is_money' => true],
                ['label' => 'PC Điện thoại',           'value' => (float) $r->allowance_phone,        'is_money' => true],
                ['label' => 'PC Đứng máy',             'value' => (float) $r->allowance_machine,      'is_money' => true],
                ['label' => 'PC Khác',                 'value' => (float) $r->allowance_other,        'is_money' => true],
                ['label' => 'PC Cơm OT',               'value' => (float) $r->allowance_ot_meal,      'is_money' => true],
                ['label' => 'Lương tháng 13',          'value' => (float) $r->salary_13th_month,      'is_money' => true],
                ['label' => 'Khoản thường xuyên',      'value' => (float) $r->regular_amount,         'is_money' => true],
                ['label' => 'Điều chỉnh',              'value' => (float) $r->adjustment,             'is_money' => true],
            ],
            'income_total'  => (float) $r->income_total,

            // ── Tăng ca tổng ─────────────────────────────────────────────────
            'ot' => [
                ['label' => 'Tổng tiền tăng ca', 'value' => (float) $r->ot_total, 'is_money' => true],
            ],
            'ot_total'      => (float) $r->ot_total,

            // ── Khấu trừ & Tạm ứng ───────────────────────────────────────────
            'deductions' => [
                ['label' => 'BHXH (8%)',                  'value' => (float) $r->deduction_bhxh],
                ['label' => 'BHYT (1.5%)',                'value' => (float) $r->deduction_bhyt],
                ['label' => 'BHTN (1%)',                  'value' => (float) $r->deduction_bhtn],
                ['label' => 'Đoàn phí',                   'value' => (float) $r->deduction_union_fee],
                ['label' => 'Nghỉ không lương',           'value' => (float) $r->deduction_unpaid_leave],
                ['label' => 'Thuế TNCN thường xuyên',    'value' => (float) $r->deduction_pit_regular],
                ['label' => 'Thuế TNCN không TX (10%)',  'value' => (float) $r->deduction_pit_irregular],
                ['label' => 'Tạm ứng',                    'value' => (float) $r->deduction_advance],
            ],
            'deductions_total' => (float) ($r->deduction_insurance_total + $r->deduction_union_fee
                + $r->deduction_unpaid_leave + $r->deduction_pit_regular
                + $r->deduction_pit_irregular + $r->deduction_advance),

            // ── Thông tin bảo hiểm ────────────────────────────────────────────
            'insurance' => [
                'h_included' => (bool) $r->h_insurance_included,
                's_included' => (bool) $r->s_insurance_included,
                'j_included' => (bool) $r->j_insurance_included,
            ],
            'parking_free' => (bool) $r->parking_free,
        ];

        return view('employee.salary.detail', compact('salary'));
    }
}
