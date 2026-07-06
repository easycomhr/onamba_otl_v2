<?php

namespace App\Http\Requests\Admin;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class LeaveReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date'   => ['sometimes', 'required_with:to_date', 'date'],
            'to_date'     => ['sometimes', 'required_with:from_date', 'date', 'after_or_equal:from_date'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'department'  => ['nullable', 'string', 'max:255'],
            'leave_type'  => ['nullable', 'string', 'in:' . implode(',', array_keys(LeaveRequest::LEAVE_TYPES))],
        ];
    }
}
