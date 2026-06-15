<?php

namespace App\Http\Requests\Faculty;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'entries.*.status' => ['required', Rule::in(AttendanceStatus::values())],
            'entries.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
