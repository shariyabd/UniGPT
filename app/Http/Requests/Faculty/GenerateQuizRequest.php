<?php

namespace App\Http\Requests\Faculty;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class GenerateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::USE_AI_CHAT) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questionCount' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
