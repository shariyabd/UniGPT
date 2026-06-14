<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(\App\Enums\Permission::UPLOAD_DOCUMENT) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'category' => ['required', 'string', 'max:100'],
            'visibility' => ['required', 'in:public,students,faculty,admins'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,txt,md,ppt,pptx'],
        ];
    }
}
