<?php

namespace App\Http\Controllers\Student;

use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tasks = Task::where('user_id', $user->id)
            ->with('course:id,code')
            ->orderBy('is_completed')
            ->orderByRaw('due_date is null, due_date asc')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Task $task) => $this->present($task));

        return Inertia::render('Student/Tasks', [
            'tasks' => $tasks,
            'courses' => $user->enrolledCourses()->wherePivotNotIn('status', ['pending'])->get(['courses.id', 'courses.code', 'courses.name']),
            'priorities' => array_map(
                fn (TaskPriority $p) => ['value' => $p->value, 'label' => $p->getLabel()],
                TaskPriority::cases(),
            ),
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->validated());

        return back()->with('success', 'Task added.');
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $data = $request->validated();
        $data['completed_at'] = ($data['is_completed'] ?? false) ? ($task->completed_at ?? now()) : null;

        $task->update($data);

        return back()->with('success', 'Task updated.');
    }

    public function toggle(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $completed = ! $task->is_completed;
        $task->update([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $task->delete();

        return back()->with('success', 'Task deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'dueDate' => $task->due_date?->toDateString(),
            'priority' => $task->priority->value,
            'isCompleted' => $task->is_completed,
            'courseId' => $task->course_id,
            'courseCode' => $task->course?->code,
            'isOverdue' => $task->due_date !== null
                && ! $task->is_completed
                && $task->due_date->isPast(),
        ];
    }

    private function authorizeOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 403);
    }
}
