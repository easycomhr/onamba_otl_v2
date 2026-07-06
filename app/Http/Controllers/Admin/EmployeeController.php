<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\AdminRole;
use App\Models\Team;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employeeService) {}

    public function index(Request $request)
    {
        $employees = $this->employeeService->getEmployeeList($request->q);

        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $managers   = $this->employeeService->getManagerList();
        $teams      = Team::select(['id', 'name'])->orderBy('name')->get();
        $adminRoles = AdminRole::orderBy('name')->get(['id', 'name']);

        return view('admin.employees.create', compact('managers', 'teams', 'adminRoles'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->employeeService->createEmployee($request->validated());
            DB::commit();

            return redirect()->route('admin.employees.index')
                ->with('success', __('ui.employee.created'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee create failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('ui.employee.create_failed'));
        }
    }

    public function show(int $id)
    {
        $employee   = $this->employeeService->findEmployee($id);
        $otCount    = $employee->otRequests()->count();
        $leaveCount = $employee->leaveRequests()->count();

        return view('admin.employees.show', compact('employee', 'otCount', 'leaveCount'));
    }

    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();
            $employee = $this->employeeService->findEmployee($id);
            $this->employeeService->deleteEmployee($employee);
            DB::commit();

            return redirect()->route('admin.employees.index')
                ->with('success', __('ui.employee.deleted'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return back()->with('error', __('ui.employee.delete_failed'));
        }
    }

    public function edit(int $id)
    {
        $employee   = $this->employeeService->findEmployee($id);
        $managers   = $this->employeeService->getManagerList();
        $teams      = Team::select(['id', 'name'])->orderBy('name')->get();
        $adminRoles = AdminRole::orderBy('name')->get(['id', 'name']);

        return view('admin.employees.edit', compact('employee', 'managers', 'teams', 'adminRoles'));
    }

    public function update(UpdateEmployeeRequest $request, int $id)
    {
        try {
            $employee = $this->employeeService->findEmployee($id);
            $this->employeeService->updateEmployee($employee, $request->validated());

            return redirect()->route('admin.employees.index')
                ->with('success', 'Đã cập nhật thông tin nhân viên.');
        } catch (\Throwable $e) {
            Log::error('Employee update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Cập nhật thất bại, vui lòng thử lại.');
        }
    }

    public function changePassword(ChangePasswordRequest $request, int $id)
    {
        try {
            $employee = $this->employeeService->findEmployee($id);
            $this->employeeService->changePassword($employee, $request->validated('password'));

            return back()->with('success', 'Đã đổi mật khẩu thành công.');
        } catch (\Throwable $e) {
            Log::error('Employee password change failed', ['id' => $id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Đổi mật khẩu thất bại, vui lòng thử lại.');
        }
    }
}
