<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeDashboardService
{
    public function getDashboardData(User $user): array
    {
        return [
            'otSummary'      => $this->getOtSummary($user),
            'leaveSummary'   => $this->getLeaveSummary($user),
            'recentRequests' => $this->getRecentRequests($user),
            'leaveBalance'   => $user->annual_leave_balance,
        ];
    }

    private function getOtSummary(User $user): array
    {
        $startOfMonth = Carbon::now()->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = Carbon::now()->copy()->endOfMonth()->toDateTimeString();

        return [
            'pending_count' => OtRequest::where('user_id', $user->id)->pending()->count(),
            'approved_count' => OtRequest::where('user_id', $user->id)->approved()->count(),
            'approved_hours_month' => (float) OtRequest::where('user_id', $user->id)
                ->approved()
                ->whereBetween('ot_date', [$startOfMonth, $endOfMonth])
                ->sum('approved_hours'),
        ];
    }

    private function getLeaveSummary(User $user): array
    {
        $startOfYear = Carbon::now()->copy()->startOfYear()->toDateTimeString();
        $endOfYear = Carbon::now()->copy()->endOfYear()->toDateTimeString();

        return [
            'pending_count' => LeaveRequest::where('user_id', $user->id)->pending()->count(),
            'approved_count' => LeaveRequest::where('user_id', $user->id)->approved()->count(),
            'approved_days_year' => (int) LeaveRequest::where('user_id', $user->id)
                ->approved()
                ->where('from_date', '<=', $endOfYear)
                ->where('to_date', '>=', $startOfYear)
                ->sum('days'),
        ];
    }

    private function getRecentRequests(User $user): Collection
    {
        $otRequests = OtRequest::where('user_id', $user->id)
            ->select('id', 'code', 'status', 'ot_date', 'hours', 'created_at')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn (OtRequest $request) => [
                'type' => 'ot',
                'code' => $request->code,
                'status' => $request->status,
                'date' => $request->ot_date,
                'meta' => $request->hours . ' giờ',
                'created_at' => $request->created_at,
            ]);

        $leaveRequests = LeaveRequest::where('user_id', $user->id)
            ->select('id', 'code', 'status', 'from_date', 'days', 'created_at')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn (LeaveRequest $request) => [
                'type' => 'leave',
                'code' => $request->code,
                'status' => $request->status,
                'date' => $request->from_date,
                'meta' => $request->days . ' ngày',
                'created_at' => $request->created_at,
            ]);

        return collect($otRequests)
            ->merge($leaveRequests)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();
    }

    public function isApprover(int $userId): bool
    {
        return Team::where('main_approver_id', $userId)
            ->orWhereHas('subApprovers', fn ($q) => $q->where('users.id', $userId))
            ->exists();
    }

    public function getPendingApprovalCount(int $userId): int
    {
        $teamIds = Team::where('main_approver_id', $userId)
            ->orWhereHas('subApprovers', fn ($q) => $q->where('users.id', $userId))
            ->pluck('id');

        if ($teamIds->isEmpty()) {
            return 0;
        }

        $memberIds = User::whereIn('team_id', $teamIds)->pluck('id');

        if ($memberIds->isEmpty()) {
            return 0;
        }

        $otCount = OtRequest::whereIn('user_id', $memberIds)
            ->where('status', OtRequest::STATUS_PENDING)
            ->count();

        $leaveCount = LeaveRequest::whereIn('user_id', $memberIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        return $otCount + $leaveCount;
    }

    public function getTeamApproverData(User $user): array
    {
        $teams = Team::with(['members'])
            ->where('main_approver_id', $user->id)
            ->orWhereHas('subApprovers', fn ($q) => $q->where('users.id', $user->id))
            ->get();

        if ($teams->isEmpty()) {
            return [
                'isTeamApprover' => false,
                'pendingOtRequests' => collect(),
                'pendingLeaveRequests' => collect(),
            ];
        }

        $memberIds = $teams->flatMap(fn ($team) => $team->members->pluck('id'))->unique();

        $pendingOtRequests = OtRequest::with('employee:id,name,employee_code')
            ->whereIn('user_id', $memberIds)
            ->where('status', OtRequest::STATUS_PENDING)
            ->select(['id', 'user_id', 'code', 'ot_date', 'hours', 'reason', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $pendingLeaveRequests = LeaveRequest::with('employee:id,name,employee_code')
            ->whereIn('user_id', $memberIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->select(['id', 'user_id', 'code', 'leave_type', 'from_date', 'to_date', 'days', 'reason', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return [
            'isTeamApprover' => true,
            'pendingOtRequests' => $pendingOtRequests,
            'pendingLeaveRequests' => $pendingLeaveRequests,
        ];
    }
}
