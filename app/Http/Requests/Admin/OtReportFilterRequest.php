<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OtReportFilterRequest extends FormRequest
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
            'status'      => ['nullable', 'in:pending,approved,rejected'],
        ];
    }
}
