<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_code'        => 'required|string|max:50|unique:users,employee_code',
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email|max:255|unique:users,email',
            'password'             => 'required|string|min:8|confirmed',
            'department'           => 'required|string|max:255',
            'position'             => 'required|string|max:255',
            'annual_leave_balance' => 'required|integer|min:0|max:365',
            'team_id'              => 'nullable|exists:teams,id',
            'admin_role_id'        => 'nullable|exists:admin_roles,id',
        ];
    }
}
