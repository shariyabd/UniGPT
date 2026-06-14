<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::MANAGE_USER_ROLES) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:student,faculty,admin'],
        ];
    }
}
