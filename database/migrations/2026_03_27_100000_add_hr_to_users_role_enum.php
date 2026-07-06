<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not enforce ENUM types; only MySQL requires ALTER COLUMN.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee', 'manager', 'hr') NOT NULL DEFAULT 'employee'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Move any 'hr' users back to 'employee' before removing the enum value.
            DB::statement("UPDATE users SET role = 'employee' WHERE role = 'hr'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee', 'manager') NOT NULL DEFAULT 'employee'");
        }
    }
};
