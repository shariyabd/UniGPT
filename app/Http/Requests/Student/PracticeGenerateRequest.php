<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class PracticeGenerateRequest extends FormRequest
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
            'topic' => ['required', 'string', 'max:120'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'question_count' => ['nullable', 'integer', 'min:3', 'max:15'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
        ];
    }
}
