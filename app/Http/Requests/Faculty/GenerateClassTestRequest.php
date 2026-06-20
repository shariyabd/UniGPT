<?php

declare(strict_types=1);

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateClassTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already gated by permission:manage_class_tests.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'questionCount' => ['nullable', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'types' => ['nullable', 'array'],
            'types.*' => [Rule::in(['mcq', 'true_false'])],
            'marks' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
