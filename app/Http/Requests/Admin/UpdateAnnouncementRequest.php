<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
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
            // Targets the broadcast group; the audience is fixed once sent.
            'created_at' => ['required', 'date'],
            'original_title' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
