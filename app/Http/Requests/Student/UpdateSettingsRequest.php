<?php

namespace App\Http\Requests\Student;

use App\Domain\Chat\Support\AiSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
        $languageCodes = array_column(app(AiSettings::class)->supportedLanguages(), 'code');

        return [
            'theme' => ['required', 'in:light,dark,system'],
            'notifications' => ['required', 'boolean'],
            'email_digest' => ['required', 'boolean'],
            'language' => ['required', 'string', Rule::in($languageCodes)],
        ];
    }
}
