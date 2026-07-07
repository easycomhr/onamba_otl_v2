<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Services\TeamsWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $service) {

    }

    public function index()
    {
        $user         = Auth::user();
        $requests     = \App\Models\LeaveRequest::where('user_id', $user->id)->latest()->get();
        $leaveBalance = $user->annual_leave_balance;

        return view('employee.leave.list', compact('requests', 'leaveBalance'));
    }

    public function create()
    {
        $leaveBalance = Auth::user()->annual_leave_balance;
        $leaveTypes = LeaveRequest::LEAVE_TYPES;

        return view('employee.leave.register', compact('leaveBalance', 'leaveTypes'));
    }

    public function store(StoreLeaveRequest $request)
    {
        try {
            $leaveRequest = $this->service->store($request->validated(), Auth::user());
            $user = auth()->user()->load('team');
            if ($user->team && $user->team->ms_teams_webhook_url) {
                app(TeamsWebhookService::class)->sendLeaveNotification($user->team, $leaveRequest);
            }

            return redirect()->route('employee.leave.index')
                ->with('success', 'Đơn xin nghỉ đã được gửi. Đang chờ duyệt.');
        } catch (\Throwable $e) {
            Log::error('Employee leave store error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Đã xảy ra lỗi khi gửi đơn nghỉ phép.']);
        }
    }

    public function sos(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->user_id === Auth::id(), 403);

        if (!$leaveRequest->isSosEligible()) {
            return back()->with('error', 'Không thể gửi SOS cho đơn này.');
        }

        $request->validate([
            'sos_reason' => 'required|string|max:500',
        ]);

        try {
            $leaveRequest->update([
                'sos_requested_at' => now(),
                'sos_reason'       => $request->sos_reason,
            ]);

            app(TeamsWebhookService::class)->sendAdminSosLeave($leaveRequest);

            return back()->with('success', 'Đã gửi yêu cầu SOS. Admin sẽ xem xét và hỗ trợ bạn.');
        } catch (\Throwable $e) {
            Log::error('Employee Leave SOS error', ['leave_id' => $leaveRequest->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Đã xảy ra lỗi khi gửi SOS.');
        }
    }
}
