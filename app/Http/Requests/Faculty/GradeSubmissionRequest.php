<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
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
        return [
            'grade' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:4000'],
            'rubric_scores' => ['nullable', 'array'],
        ];
    }
}
