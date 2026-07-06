<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOtRegisterRequest;
use App\Models\User;
use App\Services\OtRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtRegisterController extends Controller
{
    public function __construct(private OtRegisterService $service) {}

    public function create()
    {
        return view('admin.register.ot');
    }

    public function store(StoreOtRegisterRequest $request)
    {
        try {
            $this->service->store($request->validated());
        } catch (\Throwable $e) {
            Log::error('OtRegister store error', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['error' => 'Đã xảy ra lỗi khi đăng ký tăng ca.']);
        }

        return redirect()->route('admin.approvals.ot.index')->with('success', 'Đã đăng ký tăng ca cho nhân viên.');
    }

    public function employees(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $employees = User::where('role', 'employee')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('employee_code', 'like', '%' . $search . '%');
            }))
            ->orderBy('name')
            ->select('id', 'name', 'employee_code', 'department')
            ->paginate(15);

        return response()->json([
            'data' => $employees->items(),
            'next_page_url' => $employees->nextPageUrl(),
        ]);
    }
}
