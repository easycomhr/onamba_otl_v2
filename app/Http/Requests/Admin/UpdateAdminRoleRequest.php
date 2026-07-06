<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('admin_role')?->id ?? $this->route('admin_role');

        return [
            'name'         => ['required', 'string', 'max:100', Rule::unique('admin_roles', 'name')->ignore($roleId)],
            'description'  => 'nullable|string|max:255',
            'modules'      => 'nullable|array',
            'modules.*'    => ['string', Rule::in(array_keys(AdminRole::MODULES))],
        ];
    }
}
