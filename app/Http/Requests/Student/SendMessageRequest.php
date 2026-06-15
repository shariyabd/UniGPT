<?php

namespace App\Http\Requests\Student;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:4000'],
            'session_id' => ['nullable', 'integer'],
            'mode' => ['nullable', 'string'],
        ];
    }
}
