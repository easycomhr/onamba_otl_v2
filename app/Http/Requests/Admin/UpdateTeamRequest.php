<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('teams', 'name')->ignore($this->route('team'))],
            'description' => 'nullable|string|max:500',
            'main_approver_id' => 'nullable|exists:users,id',
            'sub_approver_ids' => 'nullable|array',
            'sub_approver_ids.*' => 'exists:users,id',
            // 'approver_escalation_hours' => 'required|integer|min:1|max:168',
            'ms_teams_webhook_url' => 'nullable|url|max:500',
        ];
    }
}
