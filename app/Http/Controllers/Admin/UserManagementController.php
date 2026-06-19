<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\Models\User;
use App\Domain\User\Services\UserManagementService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $users,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $paginated = $this->users->getPaginatedUsers(25)->withQueryString();
        $paginated->setCollection(
            $paginated->getCollection()->map(fn (User $u) => $this->present($u))
        );

        return Inertia::render('Admin/UserManagement', [
            'users' => $paginated,
            'roles' => Role::orderBy('level', 'desc')->get(['id', 'name', 'slug']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'stats' => $this->users->getUserStatistics(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = UserRole::from($validated['role']);
        unset($validated['role']);
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        $user = $this->users->createUser($validated, $role, $request->user());
        $this->activity->log('user.created', "Created user {$user->name}", $user, [], $request->user());

        return back()->with('success', 'User created.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // The System Administrator account must never be deactivated, regardless
        // of what the form submits.
        if ($user->isProtectedAdmin()) {
            unset($data['is_active']);
        }

        $this->users->updateUser($user, $data);
        $this->activity->log('user.updated', "Updated user {$user->name}", $user, [], $request->user());

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->isProtectedAdmin()) {
            return back()->with('error', 'The System Administrator account cannot be deleted.');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;

        try {
            $this->users->deleteUser($user);
        } catch (QueryException) {
            return back()->with('error', "Cannot delete {$name}: the account has related records. Deactivate it instead.");
        }

        $this->activity->log('user.deleted', "Deleted user {$name}", null, [], $request->user());

        return back()->with('success', 'User deleted.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->isProtectedAdmin()) {
            return back()->with('error', 'The System Administrator account cannot be deactivated.');
        }

        $this->users->updateUser($user, ['is_active' => ! $user->is_active]);
        $this->activity->log('user.toggled', ($user->is_active ? 'Deactivated' : 'Activated')." {$user->name}", $user, [], $request->user());

        return back()->with('success', 'User status updated.');
    }

    public function assignRole(AssignRoleRequest $request, User $user): RedirectResponse
    {
        if ($user->isProtectedAdmin()) {
            return back()->with('error', 'The System Administrator role cannot be changed.');
        }

        $validated = $request->validated();
        $user->syncRoles([UserRole::from($validated['role'])]);
        $this->activity->log('user.role_changed', "Set {$user->name} role to {$validated['role']}", $user, [], $request->user());

        return back()->with('success', 'Role updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=6366f1&color=fff',
            'role' => $user->getPrimaryRole()?->slug ?? 'student',
            'department' => $user->department?->name,
            'department_id' => $user->department_id,
            'semester' => $user->semester,
            'status' => $user->is_active ? 'active' : 'inactive',
            'isProtected' => $user->isProtectedAdmin(),
            'lastLogin' => $user->last_login_at?->diffForHumans(),
            'joinedDate' => $user->created_at?->toDateString(),
            'permissions' => $user->roles->flatMap->permissions->pluck('slug')->unique()->count(),
        ];
    }
}
