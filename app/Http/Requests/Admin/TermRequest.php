<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Term;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TermRequest extends FormRequest
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
        // A term's name is one of the three universal standards, and only one of
        // each may exist (so there can never be more than three terms).
        return [
            'name' => ['required', Rule::in(Term::STANDARD_NAMES), Rule::unique('terms', 'name')],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.in' => 'A term must be one of: '.implode(', ', Term::STANDARD_NAMES).'.',
            'name.unique' => 'That term already exists.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (Term::count() >= count(Term::STANDARD_NAMES)) {
                $validator->errors()->add('name', 'All standard terms already exist.');
            }
        });
    }
}
