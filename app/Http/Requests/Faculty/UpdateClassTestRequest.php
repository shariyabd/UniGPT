<?php

declare(strict_types=1);

namespace App\Http\Requests\Faculty;

/**
 * Editing an existing test: the section is fixed (the test is already bound to
 * one), so only the body + questions are validated. Section ownership is enforced
 * by the controller via the CoursePolicy.
 */
class UpdateClassTestRequest extends StoreClassTestRequest
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
        return $this->testRules();
    }
}
