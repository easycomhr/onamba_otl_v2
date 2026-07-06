<?php

namespace App\Http\Requests\Admin;

use App\Models\OtRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOtRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'ot_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->has('employee_id') && ! $validator->errors()->has('ot_date')) {
                    $exists = OtRequest::where('user_id', $this->employee_id)
                        ->whereDate('ot_date', $this->ot_date)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('ot_date', 'Nhân viên này đã có lịch tăng ca vào ngày được chọn.');
                    }
                }
            },
        ];
    }
}
