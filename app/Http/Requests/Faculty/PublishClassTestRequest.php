<?php

declare(strict_types=1);

namespace App\Http\Requests\Faculty;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Publishes an AI-generated quiz into the interactive Class Test engine. Only the
 * auto-gradable question types (MCQ + True/False) are accepted; the section is
 * resolved server-side from the course and the authenticated faculty member.
 */
class PublishClassTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::MANAGE_CLASS_TESTS) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', Rule::in(['mcq', 'true_false'])],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.marks' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.correct_answer' => ['required', 'string'],
            'questions.*.options' => ['array'],
            'questions.*.options.*.key' => ['nullable', 'string', 'max:10'],
            'questions.*.options.*.text' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mirror the integrity checks the manual authoring form enforces: MCQ needs at
     * least two options and a correct key among them; True/False must be true|false.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('questions', []) as $i => $question) {
                $type = $question['type'] ?? null;
                $correct = (string) ($question['correct_answer'] ?? '');

                if ($type === 'true_false' && ! in_array($correct, ['true', 'false'], true)) {
                    $validator->errors()->add("questions.$i.correct_answer", 'Select True or False as the answer.');
                }

                if ($type === 'mcq') {
                    $options = (array) ($question['options'] ?? []);
                    $keys = array_column($options, 'key');

                    if (count($options) < 2) {
                        $validator->errors()->add("questions.$i.options", 'Provide at least two options.');
                    }

                    if (! in_array($correct, $keys, true)) {
                        $validator->errors()->add("questions.$i.correct_answer", 'The correct answer must be one of the options.');
                    }
                }
            }
        });
    }
}
