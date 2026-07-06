<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaveRegisterRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaveRegisterController extends Controller
{
    public function __construct(private LeaveRegisterService $service) {}

    public function create()
    {
        $leaveTypes = LeaveRequest::LEAVE_TYPES;

        return view('admin.register.leave', compact('leaveTypes'));
    }

    public function store(StoreLeaveRegisterRequest $request)
    {
        try {
            $this->service->store($request->validated());
        } catch (\Throwable $e) {
            Log::error('LeaveRegister store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Đã xảy ra lỗi khi đăng ký nghỉ phép. Vui lòng thử lại.']);
        }

        return redirect()->route('admin.approvals.leave.index')->with('success', 'Đã đăng ký nghỉ phép cho nhân viên.');
    }

    public function employees(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $employees = User::where('role', 'employee')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('employee_code', 'like', '%' . $search . '%');
            }))
            ->select('id', 'name', 'employee_code', 'department')
            ->orderBy('name')
            ->paginate(15);

        return response()->json($employees);
    }
}
