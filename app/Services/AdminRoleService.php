<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRoleService
{
    public function list(): LengthAwarePaginator
    {
        return AdminRole::withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->paginate(20);
    }

    public function all(): Collection
    {
        return AdminRole::orderBy('name')->get(['id', 'name']);
    }

    public function find(int $id): AdminRole
    {
        return AdminRole::with('permissions')->findOrFail($id);
    }

    public function create(array $data, array $moduleKeys): AdminRole
    {
        return DB::transaction(function () use ($data, $moduleKeys) {
            $role = AdminRole::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncPermissions($role, $moduleKeys);

            return $role;
        });
    }

    public function update(AdminRole $role, array $data, array $moduleKeys): void
    {
        DB::transaction(function () use ($role, $data, $moduleKeys) {
            $role->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncPermissions($role, $moduleKeys);
            Cache::forget("admin_role_{$role->id}_modules");
        });
    }

    public function delete(AdminRole $role): void
    {
        DB::transaction(function () use ($role) {
            Cache::forget("admin_role_{$role->id}_modules");
            $role->delete();
        });
    }

    private function syncPermissions(AdminRole $role, array $moduleKeys): void
    {
        $validKeys = array_keys(AdminRole::MODULES);
        $filtered  = array_values(array_intersect($moduleKeys, $validKeys));

        $role->permissions()->delete();

        if (! empty($filtered)) {
            $inserts = array_map(
                fn ($key) => ['role_id' => $role->id, 'module_key' => $key, 'created_at' => now(), 'updated_at' => now()],
                $filtered
            );
            AdminRolePermission::insert($inserts);
        }
    }
}
