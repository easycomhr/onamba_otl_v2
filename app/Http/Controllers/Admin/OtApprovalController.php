<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtRequest;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtResultMail;

class OtApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService)
    {
    }

    public function index(Request $request)
    {
        $user             = Auth::user();
        $teamScopedIds    = $this->getTeamScopedMemberIds($user);

        $otRequests = OtRequest::with('employee')
            ->when($teamScopedIds !== null, fn ($q) => $q->whereIn('user_id', $teamScopedIds))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
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

        return view('admin.approvals.ot-list', compact('otRequests'));
    }

    public function show(int $id)
    {
        $otRequest = OtRequest::with(['employee', 'approvedBy'])->findOrFail($id);
        $this->authorizeTeamScope($otRequest->user_id);

        return view('admin.approvals.ot-detail', compact('otRequest'));
    }

    public function approve(Request $request, int $id)
    {
        $otRequest = OtRequest::where('status', OtRequest::STATUS_PENDING)->findOrFail($id);
        $this->authorizeTeamScope($otRequest->user_id);

        try {
            $this->approvalService->ensureAdminCanActOnOt($otRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $approver = User::findOrFail(Auth::id());

        $request->validate([
            'manager_note' => 'nullable|string|max:500',
        ]);

        $this->approvalService->approveOt($otRequest, $approver, [
            'approved_hours' => $otRequest->hours,
            'manager_note'   => $request->manager_note,
        ]);

        if ($otRequest->employee && $otRequest->employee->email) {
            Mail::to($otRequest->employee->email)->send(new OtResultMail($otRequest));
        }

        return back()->with('success', 'Đã duyệt đơn tăng ca.');
    }

    public function reject(Request $request, int $id)
    {
        $otRequest = OtRequest::where('status', OtRequest::STATUS_PENDING)->findOrFail($id);
        $this->authorizeTeamScope($otRequest->user_id);

        try {
            $this->approvalService->ensureAdminCanActOnOt($otRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $approver = User::findOrFail(Auth::id());

        $request->validate(['manager_note' => 'required|string|max:500']);

        $this->approvalService->rejectOt($otRequest, $approver, $request->manager_note);

        if ($otRequest->employee && $otRequest->employee->email) {
            Mail::to($otRequest->employee->email)->send(new OtResultMail($otRequest));
        }

        return back()->with('success', 'Đã từ chối đơn tăng ca.');
    }

    /**
     * Trả về danh sách user_id mà admin hiện tại được phép thấy.
     * null = full access (manager/hr), Collection = team-scoped.
     */
    private function getTeamScopedMemberIds(User $user): ?\Illuminate\Support\Collection
    {
        if ($user->isManager() || $user->isHr() || $user->isMasterAdmin()) {
            return null;
        }

        return $user->managedTeamMemberIds();
    }

    /**
     * Kiểm tra team approver không được truy cập request ngoài team.
     */
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
