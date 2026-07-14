<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One snapshot-evidence frame captured from the student's webcam at a flagged
 * moment (violation, face loss, phone/second-face detection, identity) or as a
 * randomised periodic sample. Small JPEGs — the whole point of this layer is
 * that it replaces continuous video with a few hundred KB per attempt.
 */
class UploadClassTestSnapshotRequest extends FormRequest
{
    public const TRIGGERS = ['violation', 'face_lost', 'phone_detected', 'multiple_faces', 'identity', 'periodic'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = max(1, (int) config('exam_security.snapshots.max_kb', 300));

        return [
            'trigger' => ['required', Rule::in(self::TRIGGERS)],
            'sequence' => ['required', 'integer', 'min:0'],
            'frame' => ['required', 'file', "max:{$maxKb}", 'mimetypes:image/jpeg,image/png,image/webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'frame.max' => 'The snapshot is too large.',
            'frame.mimetypes' => 'Unsupported snapshot format.',
        ];
    }
}
