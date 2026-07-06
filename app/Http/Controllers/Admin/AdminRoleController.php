<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRoleRequest;
use App\Http\Requests\Admin\UpdateAdminRoleRequest;
use App\Models\AdminRole;
use App\Services\AdminRoleService;
use Illuminate\Support\Facades\Log;

class AdminRoleController extends Controller
{
    public function __construct(private AdminRoleService $service) {}

    public function index()
    {
        $roles   = $this->service->list();
        $modules = AdminRole::MODULES;

        return view('admin.admin-roles.index', compact('roles', 'modules'));
    }

    public function create()
    {
        $modules = AdminRole::MODULES;

        return view('admin.admin-roles.create', compact('modules'));
    }

    public function store(StoreAdminRoleRequest $request)
    {
        try {
            $this->service->create(
                $request->only('name', 'description'),
                $request->input('modules', [])
            );

            return redirect()->route('admin.admin-roles.index')
                ->with('success', __('ui.admin_role.created'));
        } catch (\Throwable $e) {
            Log::error('AdminRoleController::store failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('ui.flash.error_generic'));
        }
    }

    public function edit(AdminRole $adminRole)
    {
        $adminRole->load('permissions');
        $modules     = AdminRole::MODULES;
        $checkedKeys = $adminRole->moduleKeys();

        return view('admin.admin-roles.edit', compact('adminRole', 'modules', 'checkedKeys'));
    }

    public function update(UpdateAdminRoleRequest $request, AdminRole $adminRole)
    {
        try {
            $this->service->update(
                $adminRole,
                $request->only('name', 'description'),
                $request->input('modules', [])
            );

            return redirect()->route('admin.admin-roles.index')
                ->with('success', __('ui.admin_role.updated'));
        } catch (\Throwable $e) {
            Log::error('AdminRoleController::update failed', ['id' => $adminRole->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('ui.flash.error_generic'));
        }
    }

    public function destroy(AdminRole $adminRole)
    {
        try {
            $this->service->delete($adminRole);

            return redirect()->route('admin.admin-roles.index')
                ->with('success', __('ui.admin_role.deleted'));
        } catch (\Throwable $e) {
            Log::error('AdminRoleController::destroy failed', ['id' => $adminRole->id, 'error' => $e->getMessage()]);

            return back()->with('error', __('ui.flash.error_generic'));
        }
    }
}
