<?php

namespace App\Http\Requests;

/**
 * Editing an existing submission. Identical to the store request except the
 * file is optional — metadata can be edited without re-uploading.
 */
class UpdateDocumentSubmissionRequest extends StoreDocumentSubmissionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['file'] = ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,txt,md,ppt,pptx', $this->uniqueFileRule()];

        return $rules;
    }
}
