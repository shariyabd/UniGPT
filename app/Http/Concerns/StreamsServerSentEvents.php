<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-sent-events plumbing for streaming AI responses: a correctly-headed
 * StreamedResponse plus an emitter that flushes each event to the client
 * immediately (past PHP's and any proxy's output buffering).
 */
trait StreamsServerSentEvents
{
    /**
     * @param  callable(): void  $handler  Emits events via sseSend().
     */
    protected function sseResponse(callable $handler): StreamedResponse
    {
        return response()->stream($handler, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sseSend(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }
}
