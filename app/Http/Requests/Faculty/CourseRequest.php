<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFaculty() || $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($courseId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'credits' => ['required', 'integer', 'min:1', 'max:12'],
            'max_enrollment' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'schedule' => ['nullable', 'array'],
            'schedule.lectures' => ['nullable', 'string', 'max:255'],
            'schedule.classroom' => ['nullable', 'string', 'max:255'],
            'schedule.office_hours' => ['nullable', 'string', 'max:255'],
        ];
    }
}
