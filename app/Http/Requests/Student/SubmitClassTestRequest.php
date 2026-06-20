<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitClassTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Answers arrive as a map of question_id => selected answer (string), plus an
     * optional disqualified flag set by the proctoring layer.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:50'],
            'disqualified' => ['boolean'],
        ];
    }

    /**
     * @return array<int, string|null>
     */
    public function answerMap(): array
    {
        $answers = (array) $this->input('answers', []);

        // Normalise keys to ints (question ids) so the grader can match them.
        $map = [];
        foreach ($answers as $questionId => $value) {
            $map[(int) $questionId] = $value === '' ? null : $value;
        }

        return $map;
    }
}
