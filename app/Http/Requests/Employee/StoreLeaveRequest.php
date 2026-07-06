<?php

namespace App\Http\Requests\Employee;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
                ! $validator->errors()->has('from_date') &&
                ! $validator->errors()->has('to_date')
            ) {
                $days = Carbon::parse($this->from_date)->diffInWeekdays($this->to_date) + 1;
                $balance = Auth::user()?->annual_leave_balance ?? 0;

                if ($days > $balance) {
                    $validator->errors()->add(
                        'from_date',
                        "Số ngày nghỉ phép vượt quỹ phép còn lại ({$balance} ngày)."
                    );
                }
            }

            if (! $validator->errors()->has('from_date') && ! $validator->errors()->has('to_date')) {
                $exists = LeaveRequest::where('user_id', Auth::id())
                    ->whereDate('from_date', '<=', $this->to_date)
                    ->whereDate('to_date', '>=', $this->from_date)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('from_date', 'Bạn đã có lịch nghỉ phép trùng khoảng ngày này.');
                }
            }
        });
    }
}
