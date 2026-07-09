<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlashcardGenerateRequest extends FormRequest
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
            'topic' => ['required', 'string', 'max:200'],
            'title' => ['nullable', 'string', 'max:150'],
            'count' => ['required', 'integer', 'min:1', 'max:30'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }
}
