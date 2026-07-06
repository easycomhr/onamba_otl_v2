<?php

namespace App\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveRegisterService
{
    public function store(array $data): LeaveRequest
    {
        $request = DB::transaction(function () use ($data) {
            $exists = LeaveRequest::where('user_id', $data['employee_id'])
                ->whereDate('from_date', '<=', $data['to_date'])
                ->whereDate('to_date', '>=', $data['from_date'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \RuntimeException(
                    'Duplicate leave: user_id=' . $data['employee_id'] . ' ' . $data['from_date'] . '~' . $data['to_date']
                );
            }

            return LeaveRequest::create([
                'user_id' => $data['employee_id'],
                'leave_type' => $data['leave_type'],
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'reason' => $data['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
            ]);
        });

        $notifyUserId = config('services.zalo.notify_user_id');

        if ($notifyUserId) {
            $employee = User::find($data['employee_id']);
            $name     = $employee ? "{$employee->name} ({$employee->employee_code})" : 'N/A';

            $message = "[OTLMS] Yêu cầu nghỉ phép mới\n"
                . "Nhân viên: {$name}\n"
                . "Từ ngày: {$request->from_date->toDateString()}\n"
                . "Đến ngày: {$request->to_date->toDateString()}\n"
                . "Loại nghỉ: {$data['leave_type']}\n"
                . "Trạng thái: Chờ duyệt";

            SendZaloNotificationJob::dispatch($notifyUserId, $message);
        }

        return $request;
    }
}
