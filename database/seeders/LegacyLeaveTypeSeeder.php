<?php

namespace Database\Seeders;

use App\Services\LegacyLeaveTypeMapper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class LegacyLeaveTypeSeeder extends Seeder
{
    /**
     * tblleavetypes relevant records (from actual schema):
     *   1  = Nghỉ phép năm       (paidrate 100%)
     *   4  = Nghỉ không lương    (paidrate 0%)
     *   5  = Nghỉ đột xuất       (paidrate 100%)
     *  23  = Nghỉ hưởng BHXH    (paidrate 0%)  — long-term sick via social insurance
     */
    public function run(): void
    {
        $this->command->info('=== Legacy Leave Type Mapping ===');
        $this->command->table(
            ['New system key', 'Legacy leavetypeid', 'Legacy name'],
            [
                ['annual',   1, 'Nghỉ phép năm'],
                ['sick',     5, 'Nghỉ đột xuất (100% paid)'],
                ['personal', 5, 'Nghỉ đột xuất (100% paid) — no distinct personal type in legacy'],
                ['unpaid',   4, 'Nghỉ không lương (0% paid)'],
            ]
        );

        Log::info('Legacy leave type mapping loaded', LegacyLeaveTypeMapper::MAPPING);
    }
}
