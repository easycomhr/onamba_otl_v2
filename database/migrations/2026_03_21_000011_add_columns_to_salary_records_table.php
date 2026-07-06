<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            // ── Metadata (không mã hóa) ───────────────────────────────────────
            $table->string('group_name', 100)->nullable()->after('year');   // B: Nhóm
            $table->string('region', 100)->nullable()->after('group_name'); // C: Khu vực
            $table->text('bank_account')->nullable()->after('region');      // G: Tài khoản NH (encrypted)

            // ── Tăng ca bổ sung ───────────────────────────────────────────────
            $table->text('ot_hours_300')->nullable()->after('ot_hours_200'); // L: Tăng ca 300%

            // ── Phụ cấp bổ sung ───────────────────────────────────────────────
            $table->text('allowance_lunch')->nullable()->after('allowance_attendance');       // N: PC Cơm trưa
            $table->text('allowance_heavy_work')->nullable()->after('allowance_lunch');      // O: PC Nặng nhọc
            $table->text('allowance_fuel')->nullable()->after('allowance_seniority');        // Q: PC Xăng
            $table->text('allowance_distance')->nullable()->after('allowance_fuel');         // R: PC Đi làm xa
            $table->text('allowance_phone')->nullable()->after('allowance_distance');        // S: PC Điện thoại
            $table->text('allowance_machine')->nullable()->after('allowance_phone');         // T: PC Đứng máy
            $table->text('allowance_ot_meal')->nullable()->after('allowance_other');         // V: PC cơm OT
            $table->text('salary_13th_month')->nullable()->after('allowance_ot_meal');      // W: Lương tháng 13

            // ── Bảo hiểm bổ sung ─────────────────────────────────────────────
            $table->text('deduction_bhyt')->nullable()->after('deduction_bhxh');            // Z: BHYT (1.5%)
            $table->text('deduction_bhtn')->nullable()->after('deduction_bhyt');            // AA: BHTN (1%)
            $table->text('deduction_insurance_total')->nullable()->after('deduction_bhtn'); // AB: Tổng cộng (10.5%)

            // ── Khấu trừ bổ sung ─────────────────────────────────────────────
            $table->text('deduction_union_fee')->nullable()->after('deduction_insurance_total');           // AC: Đoàn phí
            $table->text('deduction_personal_exemption')->nullable()->after('deduction_union_fee');       // AD: Bản thân
            $table->text('deduction_dependent_exemption')->nullable()->after('deduction_personal_exemption'); // AE: Gia cảnh
            $table->text('deduction_unpaid_leave')->nullable()->after('deduction_dependent_exemption');   // AF: Nghỉ không lương
            $table->text('taxable_income')->nullable()->after('deduction_unpaid_leave');                  // AG: Thu nhập tính thuế
            $table->text('deduction_pit_regular')->nullable()->after('taxable_income');                   // AH: Thuế TNCN thường xuyên
            $table->text('deduction_pit_irregular')->nullable()->after('deduction_pit_regular');          // AI: Thuế TNCN KTX (10%)
            $table->text('income_after_tax')->nullable()->after('deduction_advance');                     // AK: Thu nhập sau thuế
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropColumn([
                'group_name', 'region', 'bank_account',
                'ot_hours_300',
                'allowance_lunch', 'allowance_heavy_work', 'allowance_fuel',
                'allowance_distance', 'allowance_phone', 'allowance_machine',
                'allowance_ot_meal', 'salary_13th_month',
                'deduction_bhyt', 'deduction_bhtn', 'deduction_insurance_total',
                'deduction_union_fee', 'deduction_personal_exemption',
                'deduction_dependent_exemption', 'deduction_unpaid_leave',
                'taxable_income', 'deduction_pit_regular', 'deduction_pit_irregular',
                'income_after_tax',
            ]);
        });
    }
};
