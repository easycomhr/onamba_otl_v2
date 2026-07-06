<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService)
    {
    }

    public function index(Request $request)
    {
        $user          = Auth::user();
        $teamScopedIds = $this->getTeamScopedMemberIds($user);

        $leaveRequests = LeaveRequest::with('employee')
            ->when($teamScopedIds !== null, fn ($q) => $q->whereIn('user_id', $teamScopedIds))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->leave_type, fn ($q, $type) => $q->where('leave_type', $type))
            ->when($request->q, fn ($q, $search) =>
                $q->whereHas('employee', fn ($eq) =>
                    $eq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%")
                )
            )
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $leaveTypes = LeaveRequest::LEAVE_TYPES;

        return view('admin.approvals.leave-list', compact('leaveRequests', 'leaveTypes'));
    }

    public function show(int $id)
    {
        $leaveRequest = LeaveRequest::with(['employee', 'approvedBy'])->findOrFail($id);
        $this->authorizeTeamScope($leaveRequest->user_id);

        return view('admin.approvals.leave-detail', compact('leaveRequest'));
    }

    public function approve(Request $request, int $id)
    {
        $leaveRequest = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->findOrFail($id);
        $this->authorizeTeamScope($leaveRequest->user_id);

        try {
            $this->approvalService->ensureAdminCanActOnLeave($leaveRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $approver = User::findOrFail(Auth::id());

        $request->validate([
            'manager_note' => 'nullable|string|max:500',
        ]);

        $this->approvalService->approveLeave($leaveRequest, $approver, $request->manager_note);

        return back()->with('success', 'Đã duyệt đơn nghỉ phép.');
    }

    public function reject(Request $request, int $id)
    {
        $leaveRequest = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->findOrFail($id);
        $this->authorizeTeamScope($leaveRequest->user_id);

        try {
            $this->approvalService->ensureAdminCanActOnLeave($leaveRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $approver = User::findOrFail(Auth::id());

        $request->validate(['manager_note' => 'required|string|max:500']);

        $this->approvalService->rejectLeave($leaveRequest, $approver, $request->manager_note);

        return back()->with('success', 'Đã từ chối đơn nghỉ phép.');
    }

    private function getTeamScopedMemberIds(User $user): ?\Illuminate\Support\Collection
    {
        if ($user->isManager() || $user->isHr()) {
            return null;
        }

        return $user->managedTeamMemberIds();
    }

    private function authorizeTeamScope(int $requestOwnerId): void
    {
        $user = Auth::user();
        if ($user->isManager() || $user->isHr()) {
            return;
        }

        $allowed = $user->managedTeamMemberIds();
        if (!$allowed->contains($requestOwnerId)) {
            abort(403, 'Bạn không có quyền truy cập yêu cầu này.');
        }
    }
}
