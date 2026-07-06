<?php

namespace App\Services;

use App\Jobs\SendZaloNotificationJob;
use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OtService
{
    public function store(array $data, User $user): OtRequest
    {
        $request = DB::transaction(function () use ($data, $user) {
            $exists = OtRequest::where('user_id', $user->id)
                ->whereDate('ot_date', $data['ot_date'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \RuntimeException('Duplicate OT: user_id=' . $user->id . ' ot_date=' . $data['ot_date']);
            }

            return OtRequest::create([
                'user_id' => $user->id,
                'ot_date' => $data['ot_date'],
                'hours' => $data['hours'],
                'reason' => $data['reason'],
                'status' => OtRequest::STATUS_PENDING,
            ]);
        });

        $user->loadMissing('manager');

        if ($user->manager && $user->manager->zalo_user_id) {
            $message = implode("\n", [
                '[OTLMS] Yêu cầu OT mới',
                'Nhân viên: ' . $user->name . ' (' . $user->employee_code . ')',
                'Ngày OT: ' . $data['ot_date'],
                'Số giờ: ' . $data['hours'] . ' giờ',
                'Trạng thái: Chờ duyệt',
            ]);

            SendZaloNotificationJob::dispatch($user->manager->zalo_user_id, $message);
        }

        return $request;
    }
}
