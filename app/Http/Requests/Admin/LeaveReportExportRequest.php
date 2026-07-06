<?php

namespace App\Http\Requests\Admin;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class LeaveReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date'   => ['required', 'date'],
            'to_date'     => ['required', 'date', 'after_or_equal:from_date'],
            'format'      => ['required', 'in:xlsx,csv,pdf'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'department'  => ['nullable', 'string', 'max:255'],
            'leave_type'  => ['nullable', 'string', 'in:' . implode(',', array_keys(LeaveRequest::LEAVE_TYPES))],
        ];
    }
}
