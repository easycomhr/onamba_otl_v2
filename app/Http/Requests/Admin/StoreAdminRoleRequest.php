<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:100|unique:admin_roles,name',
            'description'  => 'nullable|string|max:255',
            'modules'      => 'nullable|array',
            'modules.*'    => ['string', Rule::in(array_keys(AdminRole::MODULES))],
        ];
    }
}
