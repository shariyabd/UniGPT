<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
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
            'content' => ['nullable', 'string', 'max:10000'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'is_pinned' => ['boolean'],
        ];
    }
}
