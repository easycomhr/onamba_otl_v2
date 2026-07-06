<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_requests', function (Blueprint $table) {
            $table->timestamp('sos_requested_at')->nullable()->after('rejected_at');
            $table->text('sos_reason')->nullable()->after('sos_requested_at');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->timestamp('sos_requested_at')->nullable()->after('rejected_at');
            $table->text('sos_reason')->nullable()->after('sos_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('ot_requests', function (Blueprint $table) {
            $table->dropColumn(['sos_requested_at', 'sos_reason']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['sos_requested_at', 'sos_reason']);
        });
    }
};
