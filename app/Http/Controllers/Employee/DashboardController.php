<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeeDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private EmployeeDashboardService $service) {}

    public function index()
    {
        try {
            $user = Auth::user();
            $data = $this->service->getDashboardData($user);
            $data['approverData'] = $this->service->getTeamApproverData($user);
            $data['isApprover'] = $this->service->isApprover($user->id);
            $data['pendingApprovalCount'] = $data['isApprover']
                ? $this->service->getPendingApprovalCount($user->id)
                : 0;

            return view('employee.dashboard', $data);
        } catch (\Throwable $e) {
            Log::error('Employee dashboard index error', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi tải dashboard.']);
        }
    }
}
