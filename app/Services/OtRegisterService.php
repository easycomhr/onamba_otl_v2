<?php

namespace App\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OtRegisterService
{
    public function store(array $data): OtRequest
    {
        $request = DB::transaction(function () use ($data) {
            $exists = OtRequest::where('user_id', $data['employee_id'])
                ->whereDate('ot_date', $data['ot_date'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \RuntimeException(
                    'Duplicate OT: user_id=' . $data['employee_id'] . ' ot_date=' . $data['ot_date']
                );
            }

            return OtRequest::create([
                'user_id' => $data['employee_id'],
                'ot_date' => $data['ot_date'],
                'hours' => $data['hours'],
                'reason' => $data['reason'],
                'status' => OtRequest::STATUS_PENDING,
            ]);
        });

        $notifyUserId = config('services.zalo.notify_user_id');

        if ($notifyUserId) {
            $employee = User::find($data['employee_id']);
            $name     = $employee ? $employee->name . ' (' . $employee->employee_code . ')' : 'N/A';

            $message = implode("\n", [
                '[OTLMS] Yêu cầu OT mới',
                'Nhân viên: ' . $name,
                'Ngày OT: ' . $data['ot_date'],
                'Số giờ: ' . $data['hours'] . ' giờ',
                'Trạng thái: Chờ duyệt',
            ]);

            SendZaloNotificationJob::dispatch($notifyUserId, $message);
        }

        return $request;
    }
}
