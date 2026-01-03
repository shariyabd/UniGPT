<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Domain\User\Services\UserManagementService;

class AuthenticationController extends Controller
{

    public function __construct(
        private UserManagementService $userService
    ) {}

    public function create(): Response
    {
        $roles = Role::all();
        $departments = Department::all();
        return Inertia::render('Auth/Login',[
            'roles' => $roles,
            'departments' => $departments
        ]);
    }

    public function store(Request $request): RedirectResponse
    {

        $this->ensureIsNotRateLimited($request);


        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:student,faculty,admin'],
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();



        if (!$user || !$user->is_active) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }


        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }


        if (!$user->hasRole($credentials['role'])) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'role' => 'You do not have access to the selected role.',
            ]);
        }


        RateLimiter::clear($this->throttleKey($request));
        Auth::login($user, $remember);

        $user->updateLastLogin();
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($credentials['role']);
    }


    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                function ($attribute, $value, $fail) {

                    if (
                        !str_contains($value, 'university.edu') &&
                        !str_contains($value, '.edu') &&
                        !str_contains($value, 'gmail.com')
                    ) {
                        $fail('Please use a valid university email address.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:student,faculty,admin'],
            'department_id' => ['required', 'max:50'],
            'semester' => ['nullable', 'string', 'max:50'],
            'student_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:users',
                'required_if:role,student'
            ],
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:users',
                'required_if:role,faculty,admin'
            ],
            'terms' => ['required', 'accepted'],
        ]);
        $roleEnum = match ($validated['role']) {
            'student' => UserRole::STUDENT,
            'faculty' => UserRole::FACULTY,
            'admin' => UserRole::ADMIN,
        };
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'semester' => $validated['semester'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'email_verified_at' => now(),
            'is_active' => true,
        ];

        $user = $this->userService->createUser($userData, $roleEnum);
        Auth::login($user);


        $user->updateLastLogin();

        return $this->redirectBasedOnRole($validated['role']);
    }


    public function demoLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:student,faculty,admin']
        ]);


        $demoCredentials = [
            'student' => [
                'email' => 'student@university.edu',
                'password' => 'demo123',
            ],
            'faculty' => [
                'email' => 'prof.smith@university.edu',
                'password' => 'demo123',
            ],
            'admin' => [
                'email' => 'admin@university.edu',
                'password' => 'demo123',
            ]
        ];

        $credentials = $demoCredentials[$validated['role']];

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            // If demo user doesn't exist, run the seeder
            \Artisan::call('db:seed', ['--class' => 'RBACSeeder']);
            $user = User::where('email', $credentials['email'])->first();
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'role' => 'Demo user not found. Please ensure the database is seeded.',
            ]);
        }


        Auth::login($user);
        $user->updateLastLogin();

        $request->session()->regenerate();

        return $this->redirectBasedOnRole($validated['role']);
    }


    public function destroy(Request $request): RedirectResponse
    {

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out successfully.');
    }

    private function redirectBasedOnRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->intended(route('admin.dashboard')),
            'faculty' => redirect()->intended(route('faculty.dashboard')),
            'student' => redirect()->intended(route('dashboard')),
            default => redirect()->intended(route('login'))
        };
    }


    private function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')) . '|' . $request->ip()
        );
    }
}
