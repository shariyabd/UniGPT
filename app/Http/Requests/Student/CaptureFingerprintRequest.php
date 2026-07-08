<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Browser fingerprint captured once at the start of a proctored attempt. The
 * `components` object is free-form (user agent, screen, timezone, canvas/WebGL
 * hashes, hardware concurrency, platform …) — the service hashes whatever it is
 * given, so we only bound its size here.
 */
class CaptureFingerprintRequest extends FormRequest
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
            'components' => ['required', 'array', 'max:40'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function components(): array
    {
        return (array) $this->input('components', []);
    }
}
