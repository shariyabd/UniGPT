<?php

declare(strict_types=1);

namespace App\Domain\Chat\DataObjects;

/**
 * Outcome of one chat-tool invocation: what the model gets back (data), and
 * what the user sees in the chat UI (label/summary/link + status).
 */
class ToolExecution
{
    public const STATUS_OK = 'ok';

    public const STATUS_ERROR = 'error';

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $status,
        public readonly string $summary,
        public readonly array $data = [],
        public readonly ?string $link = null,
        public readonly ?string $linkLabel = null,
    ) {}

    /**
     * The persisted/streamed activity entry rendered by the chat UI.
     *
     * @return array<string, mixed>
     */
    public function toActivity(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'status' => $this->status,
            'summary' => $this->summary,
            'link' => $this->link,
            'linkLabel' => $this->linkLabel,
        ];
    }

    /**
     * The JSON payload handed back to the model as the tool-role message.
     */
    public function toModelPayload(): string
    {
        return (string) json_encode(
            $this->status === self::STATUS_OK
                ? ['status' => 'ok', 'summary' => $this->summary, 'data' => $this->data]
                : ['status' => 'error', 'error' => $this->summary],
        );
    }
}
