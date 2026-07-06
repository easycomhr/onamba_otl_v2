<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function approveOt(OtRequest $ot, User $approver, array $data): void
    {
        $this->ensurePendingOt($ot);

        $ot->update([
            'status' => OtRequest::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_hours' => $data['approved_hours'],
            'manager_note' => $data['manager_note'] ?? null,
            'approved_at' => now(),
        ]);
    }

    public function rejectOt(OtRequest $ot, User $approver, string $note): void
    {
        $this->ensurePendingOt($ot);

        $ot->update([
            'status' => OtRequest::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'manager_note' => $note,
            'rejected_at' => now(),
        ]);
    }

    public function approveLeave(LeaveRequest $leave, User $approver, ?string $note = null): void
    {
        $this->ensurePendingLeave($leave);

        DB::transaction(function () use ($leave, $approver, $note) {
            $leave->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'manager_note' => $note,
                'approved_at' => now(),
            ]);

            $leave->loadMissing('employee');
            $leave->employee->decrement('annual_leave_balance', $leave->days);
        });
    }

    public function rejectLeave(LeaveRequest $leave, User $approver, string $note): void
    {
        $this->ensurePendingLeave($leave);

        $leave->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'manager_note' => $note,
            'rejected_at' => now(),
        ]);
    }

    private function ensurePendingOt(OtRequest $ot): void
    {
        if ($ot->status !== OtRequest::STATUS_PENDING) {
            throw new \RuntimeException('OT request is not pending.');
        }
    }

    private function ensurePendingLeave(LeaveRequest $leave): void
    {
        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            throw new \RuntimeException('Leave request is not pending.');
        }
    }

    public function ensureAdminCanActOnOt(OtRequest $ot): void
    {
        if ($ot->isOverdue() && !$ot->hasSos()) {
            throw new \RuntimeException('Đơn OT đã quá ' . OtRequest::SOS_HOURS . 'h nhưng chưa có yêu cầu SOS. Admin không thể duyệt/từ chối.');
        }
    }

    public function ensureAdminCanActOnLeave(LeaveRequest $leave): void
    {
        if ($leave->isOverdue() && !$leave->hasSos()) {
            throw new \RuntimeException('Đơn nghỉ phép đã quá ' . LeaveRequest::SOS_HOURS . 'h nhưng chưa có yêu cầu SOS. Admin không thể duyệt/từ chối.');
        }
    }

    public function ensureTeamApproverCanActOnOt(OtRequest $ot): void
    {
        if ($ot->isOverdue()) {
            throw new \RuntimeException('Đơn OT đã quá ' . OtRequest::SOS_HOURS . 'h. Vui lòng yêu cầu nhân viên gửi SOS để Admin xử lý.');
        }
    }

    public function ensureTeamApproverCanActOnLeave(LeaveRequest $leave): void
    {
        if ($leave->isOverdue()) {
            throw new \RuntimeException('Đơn nghỉ phép đã quá ' . LeaveRequest::SOS_HOURS . 'h. Vui lòng yêu cầu nhân viên gửi SOS để Admin xử lý.');
        }
    }
}
