<?php

namespace App\Http\Requests\Faculty;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class GenerateAssignmentRequest extends FormRequest
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
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', 'max:100'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
