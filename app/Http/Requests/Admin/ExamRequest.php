<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExamType;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission(Permission::MANAGE_EXAMS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(ExamType::values())],
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'location' => ['nullable', 'string', 'max:150'],
            'total_marks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
