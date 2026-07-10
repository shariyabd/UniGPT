<?php

namespace App\Http\Requests\Faculty;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::CREATE_ASSIGNMENT) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'total_points' => ['required', 'integer', 'min:1', 'max:1000'],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,published'],
            'peer_review_enabled' => ['nullable', 'boolean'],
            'rubric' => ['nullable', 'array'],
            'rubric.*.criterion' => ['required_with:rubric', 'string', 'max:255'],
            'rubric.*.points' => ['required_with:rubric', 'numeric', 'min:0'],
        ];
    }
}
