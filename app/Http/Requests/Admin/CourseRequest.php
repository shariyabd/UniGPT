<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
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
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'exists:courses,id'],
        ];
    }
}
