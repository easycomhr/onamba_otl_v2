<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ChangePasswordRequest;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $service) {}

    public function show()
    {
        $user         = Auth::user();
        $leaveBalance = $user->annual_leave_balance;

        return view('employee.profile', compact('user', 'leaveBalance'));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $this->service->changePassword(Auth::user(), $request->validated()['password']);

            return back()->with('success', 'Đổi mật khẩu thành công.');
        } catch (\Throwable $e) {
            Log::error('Employee change password error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi đổi mật khẩu.']);
        }
    }
}
