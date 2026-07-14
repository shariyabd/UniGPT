<?php

declare(strict_types=1);

namespace App\Http\Requests\Messenger;

use App\Policies\ConversationPolicy;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an outgoing direct message. Authorization (participant check) is
 * handled in the controller via the {@see ConversationPolicy},
 * since it needs the route-bound conversation.
 */
class StoreMessageRequest extends FormRequest
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
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
