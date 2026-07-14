<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::UPLOAD_DOCUMENT) ?? false;
    }

    /**
     * Accept single values for the multi-select fields too, so legacy callers
     * (and the tests) that send a scalar `visibility`/`department_ids` keep
     * working — everything is normalised to an array before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'visibility' => array_values(array_filter((array) $this->input('visibility', []))),
            'department_ids' => array_values(array_filter((array) $this->input('department_ids', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'category' => ['required', 'string', 'max:100'],
            'visibility' => ['required', 'array', 'min:1'],
            'visibility.*' => ['string', 'in:students,faculty,admins'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'file' => [
                'required', 'file', 'max:51200', 'mimes:pdf,doc,docx,txt,md,ppt,pptx',
                // Reject byte-for-byte duplicates of an existing (non-deleted)
                // document, even when re-uploaded under a different filename.
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $duplicate = Document::where('file_hash', hash_file('sha256', $value->getRealPath()))->first();

                    if ($duplicate) {
                        $fail("This file has already been uploaded as \"{$duplicate->title}\".");
                    }
                },
            ],
        ];
    }
}
