<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SectionRequest extends FormRequest
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
        return [
            'label' => ['required', 'string', 'max:10'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'faculty_id' => ['nullable', 'exists:users,id'],
            'max_enrollment' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'schedule' => ['nullable', 'array'],
            'schedule.lectures' => ['nullable', 'string', 'max:255'],
            'schedule.classroom' => ['nullable', 'string', 'max:255'],
            'schedule.office_hours' => ['nullable', 'string', 'max:255'],
        ];
    }
}
