<?php

namespace App\Http\Requests\Student;

use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStudent();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(TaskPriority::values())],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'is_completed' => ['boolean'],
        ];
    }
}
