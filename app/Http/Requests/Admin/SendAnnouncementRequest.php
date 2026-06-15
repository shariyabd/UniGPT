<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission(Permission::SEND_NOTIFICATIONS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::in(['all', 'student', 'faculty', 'admin'])],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
