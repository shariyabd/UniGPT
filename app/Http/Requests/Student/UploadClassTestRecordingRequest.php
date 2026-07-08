<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One chunk of a webcam/screen recording streamed from the student's browser
 * (MediaRecorder timeslice output). Chunks arrive in `sequence` order so a
 * mid-exam disconnect still preserves the earlier footage.
 */
class UploadClassTestRecordingRequest extends FormRequest
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
        $maxKb = max(1, (int) config('exam_security.recording.max_chunk_mb', 25)) * 1024;

        return [
            'kind' => ['required', Rule::in(['webcam', 'screen'])],
            'sequence' => ['required', 'integer', 'min:0'],
            'chunk' => [
                'required',
                'file',
                "max:{$maxKb}",
                // Browsers emit webm; some report the generic octet-stream for a
                // partial media blob — accept both, reject anything else.
                'mimetypes:video/webm,audio/webm,application/octet-stream',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'chunk.max' => 'The recording chunk is too large.',
            'chunk.mimetypes' => 'Unsupported recording format.',
        ];
    }
}
