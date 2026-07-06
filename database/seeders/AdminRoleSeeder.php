<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $allModules    = array_keys(AdminRole::MODULES);
        $nonSalary     = array_values(array_filter($allModules, fn ($k) => $k !== 'salary'));
        $accountantMods = ['dashboard', 'reports.ot', 'reports.leave', 'salary'];

        $roles = [
            [
                'name'        => 'HR',
                'description' => 'Nhân sự — truy cập tất cả trừ bảng lương',
                'modules'     => $nonSalary,
            ],
            [
                'name'        => 'Accountant',
                'description' => 'Kế toán — xem báo cáo và quản lý bảng lương',
                'modules'     => $accountantMods,
            ],
        ];

        foreach ($roles as $data) {
            $role = AdminRole::firstOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description']]
            );

            // Re-sync permissions (idempotent)
            $role->permissions()->delete();
            $inserts = array_map(
                fn ($key) => [
                    'role_id'    => $role->id,
                    'module_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $data['modules']
            );
            AdminRolePermission::insert($inserts);
        }
    }
}
