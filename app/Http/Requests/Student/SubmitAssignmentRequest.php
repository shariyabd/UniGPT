<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,zip,png,jpg,jpeg,ppt,pptx,xls,xlsx'],
        ];
    }

    /**
     * A submission must carry written content, an attached file, or both.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (blank($this->input('content')) && ! $this->hasFile('file')) {
                $validator->errors()->add('content', 'Add a written response or attach a file before submitting.');
            }
        });
    }
}
