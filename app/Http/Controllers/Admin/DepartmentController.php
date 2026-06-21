<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $departments = Department::withCount(['users', 'courses'])
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Department $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
                'isActive' => $d->is_active,
                'users' => $d->users_count,
                'courses' => $d->courses_count,
            ]);

        return Inertia::render('Admin/Departments', [
            'departments' => $departments,
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $department = Department::create($data);

        $this->activity->log('department.created', "Created department {$department->name}", $department, [], $request->user());

        return back()->with('success', 'Department created.');
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $department->update($data);

        $this->activity->log('department.updated', "Updated department {$department->name}", $department, [], $request->user());

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Don't orphan users or courses — block deletion while either references it.
        if ($department->users()->exists() || $department->courses()->exists()) {
            return back()->with('error', 'Cannot delete a department that still has users or courses.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}
