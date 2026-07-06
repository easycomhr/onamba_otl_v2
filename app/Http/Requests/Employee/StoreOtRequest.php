<?php

namespace App\Http\Requests\Employee;

use App\Models\OtRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreOtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ot_date' => ['required', 'date', 'after_or_equal:today'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->has('ot_date')) {
                $exists = OtRequest::where('user_id', Auth::id())
                    ->whereDate('ot_date', $this->ot_date)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('ot_date', 'Bạn đã có lịch tăng ca vào ngày này.');
                }
            }
        });
    }
}
