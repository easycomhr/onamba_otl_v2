<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('main_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('approver_escalation_hours')->default(24);
            $table->string('ms_teams_webhook_url', 500)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('teams'); }
};
