<?php

namespace App\Http\Requests\Admin;

use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'leave_type' => ['required', 'string', 'in:' . implode(',', array_keys(LeaveRequest::LEAVE_TYPES))],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->input('leave_type') === 'annual' &&
                ! $validator->errors()->has('employee_id') &&
                ! $validator->errors()->has('from_date') &&
                ! $validator->errors()->has('to_date')
            ) {
                $days = Carbon::parse($this->from_date)->diffInWeekdays($this->to_date) + 1;
                $balance = User::find($this->employee_id)?->annual_leave_balance ?? 0;

                if ($days > $balance) {
                    $validator->errors()->add(
                        'from_date',
                        "Số ngày nghỉ phép vượt quỹ phép còn lại ({$balance} ngày)."
                    );
                }
            }

            if (
                ! $validator->errors()->has('employee_id') &&
                ! $validator->errors()->has('from_date') &&
                ! $validator->errors()->has('to_date')
            ) {
                $exists = LeaveRequest::where('user_id', $this->employee_id)
                    ->whereDate('from_date', '<=', $this->to_date)
                    ->whereDate('to_date', '>=', $this->from_date)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('from_date', 'Nhân viên này đã có lịch nghỉ phép trùng với khoảng ngày được chọn.');
                }
            }
        });
    }
}
