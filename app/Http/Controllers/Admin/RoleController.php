<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(): Response
    {
        $roles = Role::with('permissions:id')
            ->orderByDesc('level')
            ->get(['id', 'name', 'slug', 'description']);

        $permissions = Permission::orderBy('category')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'category']);

        return Inertia::render('Admin/RolePermissions', [
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'locked' => $role->slug === 'admin',
                'permissionIds' => $role->permissions->pluck('id')->all(),
            ])->all(),
            'permissionsByCategory' => $permissions->groupBy('category')->map(
                fn ($group) => $group->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'slug' => $permission->slug,
                    'name' => $permission->name,
                ])->values()
            ),
            'totalPermissions' => $permissions->count(),
        ]);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $permissionIds = $request->validated()['permissions'] ?? [];

        $role->permissions()->sync($permissionIds);

        $this->activity->log(
            'role.permissions_updated',
            "Updated permissions for the {$role->name} role",
            $role,
            ['count' => count($permissionIds)],
            $request->user(),
        );

        return back()->with('success', "Permissions updated for the {$role->name} role.");
    }
}
