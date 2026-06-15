<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFaculty() || $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['lecture', 'slides', 'reading', 'assignment', 'video'])],
            'week' => ['nullable', 'integer', 'min:1', 'max:52'],
            'is_published' => ['boolean'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,txt,md,ppt,pptx,zip'],
        ];
    }
}
