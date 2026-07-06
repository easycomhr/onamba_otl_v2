<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Services\ApprovalService;
use App\Services\TeamsWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TeamApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvalService)
    {
    }

    private function getApproverTeamIds(int $userId): Collection
    {
        return Team::where('main_approver_id', $userId)
            ->orWhereHas('subApprovers', fn ($q) => $q->where('users.id', $userId))
            ->pluck('id');
    }

    public function index(Request $request)
    {
        try {
            $teamIds = $this->getApproverTeamIds((int) auth()->id());

            if ($teamIds->isEmpty()) {
                return redirect()->route('employee.dashboard')->with('info', 'Bạn không thuộc nhóm phê duyệt nào.');
            }

            $type = in_array($request->query('type'), ['ot', 'leave'], true)
                ? $request->query('type')
                : null;

            if ($type !== 'leave') {
                $pendingOtRequests = OtRequest::whereHas('employee', fn ($q) => $q->whereIn('team_id', $teamIds))
                    ->where('status', OtRequest::STATUS_PENDING)
                    ->with(['employee:id,name,employee_code'])
                    ->select(['id', 'code', 'user_id', 'ot_date', 'hours', 'reason', 'status', 'created_at'])
                    ->latest('created_at')
                    ->paginate(15);
            } else {
                $pendingOtRequests = OtRequest::whereRaw('0 = 1')->paginate(15);
            }

            if ($type !== 'ot') {
                $pendingLeaveRequests = LeaveRequest::whereHas('employee', fn ($q) => $q->whereIn('team_id', $teamIds))
                    ->where('status', LeaveRequest::STATUS_PENDING)
                    ->with(['employee:id,name,employee_code'])
                    ->select(['id', 'code', 'user_id', 'from_date', 'to_date', 'days', 'reason', 'status', 'created_at'])
                    ->latest('created_at')
                    ->paginate(15);
            } else {
                $pendingLeaveRequests = LeaveRequest::whereRaw('0 = 1')->paginate(15);
            }

            return view('employee.team_approvals.index', compact('pendingOtRequests', 'pendingLeaveRequests', 'type'));
        } catch (\Throwable $e) {
            Log::error('TeamApproval index failed', ['error' => $e->getMessage()]);

            return redirect()->route('employee.dashboard')->with('error', 'Không thể tải danh sách yêu cầu chờ duyệt.');
        }
    }

    public function showOt(OtRequest $otRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($otRequest->employee->team_id), 403);

        $otRequest->load('employee');

        return view('employee.team_approvals.ot_show', compact('otRequest'));
    }

    public function approveOt(Request $request, OtRequest $otRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($otRequest->employee->team_id), 403);

        try {
            $this->approvalService->ensureTeamApproverCanActOnOt($otRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate([
            'manager_note' => 'nullable|string|max:500',
        ]);

        try {
            $this->approvalService->approveOt($otRequest, auth()->user(), [
                'approved_hours' => $otRequest->hours,
                'manager_note'   => $request->manager_note,
            ]);

            return redirect()->route('employee.team-approvals.index')->with('success', 'Đã duyệt yêu cầu OT.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval approveOt failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Duyệt thất bại: ' . $e->getMessage());
        }
    }

    public function rejectOt(Request $request, OtRequest $otRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($otRequest->employee->team_id), 403);

        try {
            $this->approvalService->ensureTeamApproverCanActOnOt($otRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate(['manager_note' => 'nullable|string|max:500']);

        try {
            $this->approvalService->rejectOt($otRequest, auth()->user(), $request->input('manager_note', ''));

            return redirect()->route('employee.team-approvals.index')->with('success', 'Đã từ chối yêu cầu OT.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval rejectOt failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Từ chối thất bại: ' . $e->getMessage());
        }
    }

    public function showLeave(LeaveRequest $leaveRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($leaveRequest->employee->team_id), 403);

        $leaveRequest->load('employee');

        return view('employee.team_approvals.leave_show', compact('leaveRequest'));
    }

    public function approveLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($leaveRequest->employee->team_id), 403);

        try {
            $this->approvalService->ensureTeamApproverCanActOnLeave($leaveRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate(['manager_note' => 'nullable|string|max:500']);

        try {
            $this->approvalService->approveLeave($leaveRequest, auth()->user(), $request->input('manager_note', ''));

            return redirect()->route('employee.team-approvals.index')->with('success', 'Đã duyệt yêu cầu nghỉ phép.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval approveLeave failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Duyệt thất bại: ' . $e->getMessage());
        }
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($leaveRequest->employee->team_id), 403);

        try {
            $this->approvalService->ensureTeamApproverCanActOnLeave($leaveRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate(['manager_note' => 'nullable|string|max:500']);

        try {
            $this->approvalService->rejectLeave($leaveRequest, auth()->user(), $request->input('manager_note', ''));

            return redirect()->route('employee.team-approvals.index')->with('success', 'Đã từ chối yêu cầu nghỉ phép.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval rejectLeave failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Từ chối thất bại: ' . $e->getMessage());
        }
    }

    public function sosOt(Request $request, OtRequest $otRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($otRequest->employee->team_id), 403);

        if (! $otRequest->isSosEligible()) {
            return back()->with('error', 'Không thể gửi SOS cho đơn này.');
        }

        $request->validate(['sos_reason' => 'required|string|max:500']);

        try {
            $otRequest->update([
                'sos_requested_at' => now(),
                'sos_reason'       => $request->sos_reason,
            ]);

            app(TeamsWebhookService::class)->sendAdminSosOt($otRequest);

            return back()->with('success', 'Đã gửi SOS. Admin sẽ xem xét và hỗ trợ duyệt đơn này.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval sosOt failed', ['ot_id' => $otRequest->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Đã xảy ra lỗi khi gửi SOS.');
        }
    }

    public function sosLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $teamIds = $this->getApproverTeamIds((int) auth()->id());
        abort_unless($teamIds->contains($leaveRequest->employee->team_id), 403);

        if (! $leaveRequest->isSosEligible()) {
            return back()->with('error', 'Không thể gửi SOS cho đơn này.');
        }

        $request->validate(['sos_reason' => 'required|string|max:500']);

        try {
            $leaveRequest->update([
                'sos_requested_at' => now(),
                'sos_reason'       => $request->sos_reason,
            ]);

            app(TeamsWebhookService::class)->sendAdminSosLeave($leaveRequest);

            return back()->with('success', 'Đã gửi SOS. Admin sẽ xem xét và hỗ trợ duyệt đơn này.');
        } catch (\Throwable $e) {
            Log::error('TeamApproval sosLeave failed', ['leave_id' => $leaveRequest->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Đã xảy ra lỗi khi gửi SOS.');
        }
    }
}
