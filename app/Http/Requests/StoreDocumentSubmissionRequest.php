<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Document;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Faculty / student document submission. Same storage pipeline as the admin
 * upload, but the audience (visibility) is decided by the controller from the
 * uploader's role rather than picked in the form.
 */
class StoreDocumentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission(Permission::UPLOAD_DOCUMENT);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'department_ids' => array_values(array_filter((array) $this->input('department_ids', []))),
            'tags' => array_values(array_filter((array) $this->input('tags', []))),
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
            'category' => ['required', 'string', 'max:100'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,txt,md,ppt,pptx', $this->uniqueFileRule()],
        ];
    }

    /**
     * Reject byte-for-byte duplicates of an existing document, ignoring the
     * document currently being edited (so re-saving without a new file is fine).
     */
    protected function uniqueFileRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $ignoreId = $this->route('document')?->id;

            $duplicate = Document::where('file_hash', hash_file('sha256', $value->getRealPath()))
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->first();

            if ($duplicate) {
                $fail("This file has already been uploaded as \"{$duplicate->title}\".");
            }
        };
    }
}
