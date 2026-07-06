<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    public function getEmployeeList(?string $search, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'employee_code', 'name', 'email', 'department', 'position', 'annual_leave_balance'])
            ->where('role', User::ROLE_EMPLOYEE)
            ->when($search, fn ($q) =>
                $q->where(fn ($sub) =>
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                )
            )
            ->orderBy('employee_code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findEmployee(int $id): User
    {
        return User::query()
            ->select(['id', 'employee_code', 'name', 'email', 'department', 'position', 'annual_leave_balance', 'manager_id', 'team_id', 'admin_role_id', 'zalo_user_id'])
            ->where('role', User::ROLE_EMPLOYEE)
            ->findOrFail($id);
    }

    public function getManagerList(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->select(['id', 'name', 'employee_code'])
            ->where('role', User::ROLE_MANAGER)
            ->orderBy('name')
            ->get();
    }

    public function updateEmployee(User $employee, array $data): void
    {
        $employee->update($data);
    }

    public function changePassword(User $employee, string $plainPassword): void
    {
        // User model has 'password' => 'hashed' cast — no Hash::make() needed
        $employee->update(['password' => $plainPassword]);
    }

    public function createEmployee(array $data): User
    {
        return User::create(array_merge($data, ['role' => User::ROLE_EMPLOYEE]));
    }

    public function deleteEmployee(User $employee): void
    {
        $employee->delete();
    }
}
