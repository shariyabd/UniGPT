<?php

namespace App\Http\Requests\Student;

use App\Models\SavedAnswer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSavedAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('savedAnswer');

        return $answer instanceof SavedAnswer && $answer->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'folder' => ['sometimes', 'string', 'max:100'],
            'starred' => ['sometimes', 'boolean'],
        ];
    }
}
