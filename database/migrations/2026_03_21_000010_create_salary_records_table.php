<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('month')->unsigned(); // 1–12
            $table->smallInteger('year')->unsigned();

            $table->string('payment_method', 50)->nullable();

            // ── Thu nhập & Phụ cấp (encrypted) ──────────────────────────────
            $table->text('base_salary')->nullable();           // Lương cơ bản
            $table->text('productivity_salary')->nullable();   // Lương năng suất
            $table->text('allowance_attendance')->nullable();  // Phụ cấp chuyên cần
            $table->text('allowance_seniority')->nullable();   // Phụ cấp thâm niên
            $table->text('allowance_other')->nullable();       // Phụ cấp khác
            $table->text('income_total')->nullable();          // Cộng thu nhập

            // ── Chấm công & Tăng ca (encrypted) ─────────────────────────────
            $table->text('working_days')->nullable();          // Ngày công thực tế
            $table->text('ot_hours_150')->nullable();          // Tăng ca 1.5x (giờ)
            $table->text('ot_hours_200')->nullable();          // Tăng ca 2.0x (giờ)
            $table->text('ot_night_allowance')->nullable();    // Phụ cấp ca đêm
            $table->text('ot_total')->nullable();              // Cộng tiền tăng ca

            // ── Khấu trừ & Tạm ứng (encrypted) ──────────────────────────────
            $table->text('deduction_bhxh')->nullable();        // BHXH NV đóng
            $table->text('deduction_penalty')->nullable();     // Phạt / trừ khác
            $table->text('deduction_advance')->nullable();     // Tạm ứng
            $table->text('deductions_total')->nullable();      // Tổng khấu trừ

            // ── Thực nhận (encrypted) ─────────────────────────────────────────
            $table->text('net')->nullable();

            $table->timestamps();

            // 1 kỳ lương / NV / tháng
            $table->unique(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
