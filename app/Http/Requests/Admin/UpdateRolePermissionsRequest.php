<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ($this->user()?->hasPermission(Permission::MANAGE_PERMISSIONS) ?? false)) {
            return false;
        }

        // The admin role always holds every permission; editing it is blocked to
        // prevent an admin from locking themselves (and all admins) out.
        $role = $this->route('role');

        return ! ($role instanceof Role && $role->slug === 'admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
