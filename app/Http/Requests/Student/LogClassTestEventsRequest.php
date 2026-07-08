<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A batch of proctoring/behaviour events flushed from the student's browser
 * during a live attempt. Batched (not one request per event) to keep the exam
 * responsive under heavy behaviour logging.
 */
class LogClassTestEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'max:200'],
            'events.*.type' => ['required', 'string', 'max:60'],
            'events.*.severity' => ['nullable', Rule::in(['info', 'warning', 'violation'])],
            // occurredAt may be a ms-epoch number or an ISO string — parsed server-side.
            'events.*.occurredAt' => ['nullable'],
            'events.*.durationMs' => ['nullable', 'integer', 'min:0'],
            'events.*.questionId' => ['nullable', 'integer'],
            'events.*.meta' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        return (array) $this->input('events', []);
    }
}
