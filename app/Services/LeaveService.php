<?php

namespace App\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function store(array $data, User $user): LeaveRequest
    {
        $request = DB::transaction(function () use ($data, $user) {
            $exists = LeaveRequest::where('user_id', $user->id)
                ->whereDate('from_date', '<=', $data['to_date'])
                ->whereDate('to_date', '>=', $data['from_date'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \RuntimeException(
                    'Duplicate leave: user_id=' . $user->id . ' from=' . $data['from_date'] . ' to=' . $data['to_date']
                );
            }

            return LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type' => $data['leave_type'],
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'reason' => $data['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
            ]);
        });

        $user->loadMissing('manager');

        if ($user->manager && $user->manager->zalo_user_id) {
            $message = "[OTLMS] Yêu cầu nghỉ phép mới\n"
                . "Nhân viên: {$user->name} ({$user->employee_code})\n"
                . "Từ ngày: {$request->from_date->toDateString()}\n"
                . "Đến ngày: {$request->to_date->toDateString()}\n"
                . "Loại nghỉ: {$data['leave_type']}\n"
                . "Trạng thái: Chờ duyệt";

            SendZaloNotificationJob::dispatch($user->manager->zalo_user_id, $message);
        }

        return $request;
    }
}
