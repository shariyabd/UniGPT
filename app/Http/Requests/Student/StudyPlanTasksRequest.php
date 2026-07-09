<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudyPlanTasksRequest extends FormRequest
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
            'sessions' => ['required', 'array', 'min:1', 'max:60'],
            'sessions.*.title' => ['required', 'string', 'max:150'],
            'sessions.*.date' => ['nullable', 'date'],
            'sessions.*.focus' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
