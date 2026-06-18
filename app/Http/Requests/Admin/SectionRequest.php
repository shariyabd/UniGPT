<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    /**
     * The allowed section labels — a fixed, alphabetical A–J set. Restricting
     * labels to a known list keeps the data consistent (no "A" vs "a" drift) and
     * lets the UI offer a dropdown instead of free text.
     *
     * @var list<string>
     */
    public const LABELS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Normalise the label to an uppercase, trimmed value before validation so a
     * stray lower-case or padded entry can never reach the database.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('label')) {
            $this->merge(['label' => strtoupper(trim((string) $this->input('label')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => [
                'required',
                Rule::in(self::LABELS),
                // A label must be unique within a course's offerings for a term
                // (one Section A of CS301 in Summer 2026, not two).
                Rule::unique('sections', 'label')
                    ->where(fn ($query) => $query
                        ->where('course_id', $this->courseId())
                        ->where('term_id', $this->effectiveTermId()))
                    ->ignore($this->route('section')?->id),
            ],
            'term_id' => ['nullable', 'exists:terms,id'],
            'faculty_id' => ['nullable', 'exists:users,id'],
            'max_enrollment' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'schedule' => ['nullable', 'array'],
            'schedule.lectures' => ['nullable', 'string', 'max:255'],
            'schedule.classroom' => ['nullable', 'string', 'max:255'],
            'schedule.office_hours' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.in' => 'The section label must be one of: '.implode(', ', self::LABELS).'.',
            'label.unique' => 'That section label already exists for this course in the selected term.',
        ];
    }

    /**
     * The course the section belongs to — from the route on create
     * (/courses/{course}/sections) or the existing section on update.
     */
    private function courseId(): ?int
    {
        $course = $this->route('course');

        return $course?->id ?? $this->route('section')?->course_id;
    }

    /**
     * The term the uniqueness check applies to: the submitted term, else the
     * section's current term (update), else the current term (the create
     * default in CourseManagementService).
     */
    private function effectiveTermId(): ?int
    {
        return $this->input('term_id')
            ?? $this->route('section')?->term_id
            ?? Term::query()->where('is_current', true)->value('id');
    }
}
