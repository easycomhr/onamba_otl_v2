<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_requests', function (Blueprint $table) {
            $table->unique(['user_id', 'ot_date'], 'ot_requests_user_id_ot_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ot_requests', function (Blueprint $table) {
            $table->dropUnique('ot_requests_user_id_ot_date_unique');
        });
    }
};
