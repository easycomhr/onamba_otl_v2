<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->double('other_balance')->nullable()->default(null);
            $table->double('women_balance')->nullable()->default(null);
            $table->double('hard_balance')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'other_balance',
                'women_balance',
                'hard_balance',
            ]);
        });
    }
};
