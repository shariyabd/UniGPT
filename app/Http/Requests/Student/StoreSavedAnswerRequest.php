<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chat_message_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'folder' => ['nullable', 'string', 'max:100'],
        ];
    }
}
