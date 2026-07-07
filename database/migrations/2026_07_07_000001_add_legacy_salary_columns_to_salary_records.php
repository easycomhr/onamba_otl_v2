<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm các cột phụ cấp từ bảng legacy tblsalaryhistory
 * chưa có trong salary_records hiện tại.
 *
 * tblsalaryhistory → salary_records
 * ──────────────────────────────────────────────────────────────────
 * positionallowance          → allowance_position         (PC vị trí)
 * proficiencyallowance       → allowance_proficiency      (PC thành thạo)
 * steeringcapabilityallowance→ allowance_steering         (PC chỉ đạo/kiêm nhiệm)
 * harmfulnessallowance       → (đã map vào allowance_heavy_work)
 * environmentalallowance     → allowance_environment      (PC môi trường)
 * specialworkallowance       → allowance_special_work     (PC công việc đặc biệt)
 * sceneallowance             → allowance_scene            (PC hiện trường)
 * housingshortage            → allowance_housing          (PC thiếu nhà ở)
 * leadamount                 → allowance_lead             (Khoản thu/tiền chì)
 * rglamt                     → regular_amount             (Khoản thường xuyên)
 * slippageallowance          → allowance_slippage         (PC trượt giá)
 * adjust                     → adjustment                 (Khoản điều chỉnh)
 * skill                      → allowance_skill            (PC kỹ năng)
 * languageallowance          → allowance_language         (PC ngôn ngữ)
 * parkingfree                → parking_free               (Miễn phí đậu xe - boolean)
 * h_insurance_included       → h_insurance_included       (BHXH - có tham gia không)
 * s_insurance_included       → s_insurance_included       (BHYT - có tham gia không)
 * j_insurance_included       → j_insurance_included       (BHTN - có tham gia không)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {

            // ── Phụ cấp bổ sung từ tblsalaryhistory ────────────────────────
            $table->text('allowance_position')->nullable()->after('base_salary');            // PC vị trí
            $table->text('allowance_proficiency')->nullable()->after('allowance_position');  // PC thành thạo
            $table->text('allowance_steering')->nullable()->after('allowance_proficiency');  // PC chỉ đạo/kiêm nhiệm
            $table->text('allowance_environment')->nullable()->after('allowance_heavy_work'); // PC môi trường
            $table->text('allowance_special_work')->nullable()->after('allowance_environment'); // PC công việc đặc biệt
            $table->text('allowance_scene')->nullable()->after('allowance_special_work');    // PC hiện trường
            $table->text('allowance_housing')->nullable()->after('allowance_scene');         // PC thiếu nhà ở
            $table->text('allowance_lead')->nullable()->after('allowance_housing');          // Khoản thu/tiền chì
            $table->text('allowance_slippage')->nullable()->after('allowance_other');        // PC trượt giá
            $table->text('allowance_skill')->nullable()->after('allowance_slippage');        // PC kỹ năng
            $table->text('allowance_language')->nullable()->after('allowance_skill');        // PC ngôn ngữ

            // ── Khoản khác ───────────────────────────────────────────────────
            $table->text('regular_amount')->nullable()->after('net');        // Khoản thường xuyên (rglamt)
            $table->text('adjustment')->nullable()->after('regular_amount'); // Điều chỉnh (adjust)

            // ── Trạng thái bảo hiểm (boolean flags) ─────────────────────────
            $table->tinyInteger('h_insurance_included')->unsigned()->default(1)->after('adjustment'); // BHXH có tham gia
            $table->tinyInteger('s_insurance_included')->unsigned()->default(1)->after('h_insurance_included'); // BHYT có tham gia
            $table->tinyInteger('j_insurance_included')->unsigned()->default(1)->after('s_insurance_included'); // BHTN có tham gia

            // ── Đậu xe ──────────────────────────────────────────────────────
            $table->boolean('parking_free')->default(false)->after('j_insurance_included'); // Miễn phí đậu xe
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropColumn([
                'allowance_position',
                'allowance_proficiency',
                'allowance_steering',
                'allowance_environment',
                'allowance_special_work',
                'allowance_scene',
                'allowance_housing',
                'allowance_lead',
                'allowance_slippage',
                'allowance_skill',
                'allowance_language',
                'regular_amount',
                'adjustment',
                'h_insurance_included',
                's_insurance_included',
                'j_insurance_included',
                'parking_free',
            ]);
        });
    }
};
