<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class LeaderboardSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStudent();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'leaderboard_opt_in' => ['required', 'boolean'],
            'leaderboard_alias' => ['nullable', 'string', 'max:30'],
        ];
    }
}
