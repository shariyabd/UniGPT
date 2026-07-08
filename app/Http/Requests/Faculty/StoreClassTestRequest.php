<?php

declare(strict_types=1);

namespace App\Http\Requests\Faculty;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Faculty may only author a test for a section they actually teach.
        return $this->user()
            ?->teachingSectionIds()
            ->contains((int) $this->input('section_id')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')],
            ...$this->testRules(),
        ];
    }

    /**
     * Shared rules for the test body + its questions (reused on update).
     *
     * @return array<string, mixed>
     */
    protected function testRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'pass_marks' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],

            // Warning threshold + the per-test security-layer selection. The map
            // is a { layerKey: bool }; the ExamSecurityService normalises it to
            // the globally-available layers on save, so no key allow-list here.
            'max_warnings' => ['nullable', 'integer', 'min:0', 'max:20'],
            'security_config' => ['nullable', 'array'],
            'security_config.*' => ['boolean'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', Rule::in(['mcq', 'true_false'])],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.marks' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.correct_answer' => ['required', 'string'],
            // Options are only meaningful for MCQ — True/False carries none. The
            // per-option content is validated for MCQ in withValidator() so empty
            // True/False option scaffolding never trips a hidden nested rule.
            'questions.*.options' => ['array'],
            'questions.*.options.*.key' => ['nullable', 'string', 'max:10'],
            'questions.*.options.*.text' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Human-readable field names for default validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'section_id' => 'section',
            'duration_minutes' => 'duration',
            'pass_marks' => 'pass marks',
            'available_from' => 'available from',
            'available_until' => 'available until',
        ];
    }

    /**
     * Friendly, question-numbered messages so faculty never see raw keys like
     * "questions.0.question_text is required". ":position" is the 1-based index.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'section_id.required' => 'Select the section this test belongs to.',
            'title.required' => 'Give the class test a title.',
            'duration_minutes.required' => 'Set how many minutes students have.',
            'status.in' => 'Status must be either draft or published.',
            'available_until.after_or_equal' => 'The "available until" time must be after the "available from" time.',

            'questions.required' => 'Add at least one question.',
            'questions.array' => 'Add at least one question.',
            'questions.min' => 'Add at least one question.',

            'questions.*.type.required' => 'Choose a type for question :position.',
            'questions.*.type.in' => 'Question :position has an unsupported type.',
            'questions.*.question_text.required' => 'Enter the question text for question :position.',
            'questions.*.question_text.max' => 'Question :position is too long (max 2000 characters).',
            'questions.*.marks.required' => 'Enter the marks for question :position.',
            'questions.*.marks.integer' => 'Marks for question :position must be a whole number.',
            'questions.*.marks.min' => 'Marks for question :position must be at least 1.',
            'questions.*.marks.max' => 'Marks for question :position cannot exceed 100.',
            'questions.*.correct_answer.required' => 'Select the correct answer for question :position.',
            'questions.*.options.*.text.max' => 'An option in question :position is too long (max 500 characters).',
        ];
    }

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

                    foreach ($options as $j => $option) {
                        if (trim((string) ($option['text'] ?? '')) === '') {
                            $validator->errors()->add("questions.$i.options.$j.text", 'Option text is required.');
                        }
                    }

                    if (! in_array($correct, $keys, true)) {
                        $validator->errors()->add("questions.$i.correct_answer", 'The correct answer must be one of the options.');
                    }
                }
            }
        });
    }
}
