<?php

namespace App\Infrastructure\AI\Concerns;

use Psr\Http\Message\StreamInterface;

/**
 * Shared parsing for OpenAI-wire-compatible chat APIs (OpenAI and OpenRouter):
 * tool-call normalization and Server-Sent-Events stream consumption. Keeping it
 * in one place avoids the two providers drifting apart on subtle stream/tool
 * accumulation details.
 */
trait ParsesOpenAiChatWire
{
    /**
     * Normalize the non-streaming tool_calls shape into
     * [{id, name, arguments(array)}], dropping malformed entries.
     *
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @return array<int, array{id: string, name: string, arguments: array<string, mixed>}>
     */
    private function normalizeToolCalls(array $toolCalls): array
    {
        $normalized = [];

        foreach ($toolCalls as $call) {
            $name = (string) ($call['function']['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $arguments = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);

            $normalized[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => $name,
                'arguments' => is_array($arguments) ? $arguments : [],
            ];
        }

        return $normalized;
    }

    /**
     * Assemble tool calls accumulated from stream fragments.
     *
     * @param  array<int, array{id: string, name: string, arguments: string}>  $parts
     * @return array<int, array{id: string, name: string, arguments: array<string, mixed>}>
     */
    private function normalizeStreamedToolCalls(array $parts): array
    {
        ksort($parts);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part['name'] === '') {
                continue;
            }

            $arguments = json_decode($part['arguments'] !== '' ? $part['arguments'] : '{}', true);

            $normalized[] = [
                'id' => $part['id'],
                'name' => $part['name'],
                'arguments' => is_array($arguments) ? $arguments : [],
            ];
        }

        return $normalized;
    }

    /**
     * Consume an OpenAI-compatible SSE chat stream, invoking $onDelta for each
     * content fragment and accumulating model/usage/tool-call data.
     *
     * @return array{content: string, model: string, prompt_tokens: int, completion_tokens: int, tool_calls: array<int, array{id: string, name: string, arguments: array<string, mixed>}>}
     */
    private function consumeChatStream(StreamInterface $body, string $model, callable $onDelta): array
    {
        $content = '';
        $modelUsed = $model;
        $promptTokens = 0;
        $completionTokens = 0;
        $buffer = '';
        // Streamed tool calls arrive as fragments keyed by index: the id and
        // function name in the first fragment, the JSON arguments split across
        // the rest. Accumulate per index and decode once the stream ends.
        $toolCallParts = [];

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            // The SSE payload arrives as "data: {json}\n" lines; process every
            // complete line and keep the remainder buffered.
            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newline));
                $buffer = substr($buffer, $newline + 1);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '' || $payload === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($payload, true);
                if (! is_array($chunk)) {
                    continue;
                }

                $modelUsed = (string) ($chunk['model'] ?? $modelUsed);

                $delta = (string) ($chunk['choices'][0]['delta']['content'] ?? '');
                if ($delta !== '') {
                    $content .= $delta;
                    $onDelta($delta);
                }

                foreach ($chunk['choices'][0]['delta']['tool_calls'] ?? [] as $fragment) {
                    $index = (int) ($fragment['index'] ?? 0);
                    $toolCallParts[$index] ??= ['id' => '', 'name' => '', 'arguments' => ''];
                    if (! empty($fragment['id'])) {
                        $toolCallParts[$index]['id'] = (string) $fragment['id'];
                    }
                    if (! empty($fragment['function']['name'])) {
                        $toolCallParts[$index]['name'] .= (string) $fragment['function']['name'];
                    }
                    if (isset($fragment['function']['arguments'])) {
                        $toolCallParts[$index]['arguments'] .= (string) $fragment['function']['arguments'];
                    }
                }

                if (isset($chunk['usage'])) {
                    $promptTokens = (int) ($chunk['usage']['prompt_tokens'] ?? 0);
                    $completionTokens = (int) ($chunk['usage']['completion_tokens'] ?? 0);
                }
            }
        }

        return [
            'content' => $content,
            'model' => $modelUsed,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'tool_calls' => $this->normalizeStreamedToolCalls($toolCallParts),
        ];
    }
}
