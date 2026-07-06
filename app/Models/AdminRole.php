<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * All grantable admin modules.
     * key   = module_key stored in admin_role_permissions
     * value = display label (Vietnamese)
     */
    public const MODULES = [
        'dashboard'       => 'Dashboard',
        'approvals.ot'    => 'Duyệt OT',
//        'approvals.leave' => 'Duyệt Nghỉ phép',
        'register.ot'     => 'Đăng ký OT hộ NV',
//        'register.leave'  => 'Đăng ký Nghỉ hộ NV',
        'employees'       => 'Quản lý Nhân viên',
        'import.ot'       => 'Import OT',
//        'import.leave'    => 'Import Nghỉ phép',
        'reports.ot'      => 'Báo cáo OT',
//        'reports.leave'   => 'Báo cáo Nghỉ phép',
        'teams'           => 'Quản lý Team',
//        'salary'          => 'Bảng lương',
    ];

    public function permissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    /** Returns array of allowed module_key strings for this role. */
    public function moduleKeys(): array
    {
        return $this->permissions->pluck('module_key')->toArray();
    }
}
