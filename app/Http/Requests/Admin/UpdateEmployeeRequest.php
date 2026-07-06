<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:manager middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'email'                => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($this->route('id'))],
            'department'           => 'required|string|max:255',
            'position'             => 'required|string|max:255',
            'annual_leave_balance' => 'required|integer|min:0|max:365',
            'team_id'              => 'nullable|exists:teams,id',
            'admin_role_id'        => 'nullable|exists:admin_roles,id',
        ];
    }
}
