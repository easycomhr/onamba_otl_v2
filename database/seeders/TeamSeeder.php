<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('role', 'manager')->first();

        $teams = [
            ['name' => 'Team Xưởng A',        'description' => 'Nhóm sản xuất dây chuyền xưởng A',          'escalate' => 24],
            ['name' => 'Team Xưởng B',        'description' => 'Nhóm sản xuất dây chuyền xưởng B',          'escalate' => 24],
            ['name' => 'Team Kho Vận',        'description' => 'Nhóm quản lý kho và vận chuyển',             'escalate' => 48],
            ['name' => 'Team QC',             'description' => 'Nhóm kiểm soát chất lượng sản phẩm',        'escalate' => 24],
            ['name' => 'Team Kỹ thuật',       'description' => 'Nhóm kỹ thuật bảo trì máy móc',             'escalate' => 12],
            ['name' => 'Team Kinh doanh',     'description' => 'Nhóm kinh doanh và phát triển khách hàng',  'escalate' => 48],
            ['name' => 'Team Hành chính',     'description' => 'Nhóm hành chính nhân sự',                   'escalate' => 48],
            ['name' => 'Team IT',             'description' => 'Nhóm công nghệ thông tin',                  'escalate' => 8],
            ['name' => 'Team Kế toán',        'description' => 'Nhóm tài chính kế toán',                    'escalate' => 48],
            ['name' => 'Team Ban Giám đốc',   'description' => 'Nhóm hỗ trợ ban giám đốc',                  'escalate' => 24],
        ];

        foreach ($teams as $data) {
            Team::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description'               => $data['description'],
                    'main_approver_id'          => $manager?->id,
                    'approver_escalation_hours' => $data['escalate'],
                ]
            );
        }
    }
}
