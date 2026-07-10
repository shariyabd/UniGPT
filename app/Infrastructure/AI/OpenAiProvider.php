<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real OpenAI provider implemented against the REST API via Laravel's HTTP
 * client (no SDK dependency). Reads credentials/models from config/ai.php.
 */
class OpenAiProvider implements AIProviderInterface
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function chat(array $messages, array $options = []): ChatResult
    {
        $config = config('ai.providers.openai');
        $model = $options['model'] ?? $config['model'];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $config['max_tokens']),
            'temperature' => (float) ($options['temperature'] ?? $config['temperature']),
        ];

        if (! empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client()->post(self::BASE_URL.'/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI chat request failed: '.$response->body());
        }

        $data = $response->json();

        return new ChatResult(
            content: (string) ($data['choices'][0]['message']['content'] ?? ''),
            model: (string) ($data['model'] ?? $model),
            promptTokens: (int) ($data['usage']['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['usage']['completion_tokens'] ?? 0),
            raw: $data,
            toolCalls: $this->normalizeToolCalls($data['choices'][0]['message']['tool_calls'] ?? []),
        );
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        $config = config('ai.providers.openai');
        $model = $options['model'] ?? $config['model'];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $config['max_tokens']),
            'temperature' => (float) ($options['temperature'] ?? $config['temperature']),
            'stream' => true,
            // The final stream chunk then carries token usage, keeping
            // per-user AI usage accounting intact on the streaming path.
            'stream_options' => ['include_usage' => true],
        ];

        if (! empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client()
            ->withOptions(['stream' => true])
            ->post(self::BASE_URL.'/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI chat stream request failed: '.$response->body());
        }

        $body = $response->toPsrResponse()->getBody();

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

        return new ChatResult(
            content: $content,
            model: $modelUsed,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            raw: ['provider' => 'openai', 'streamed' => true],
            toolCalls: $this->normalizeStreamedToolCalls($toolCallParts),
        );
    }

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

    public function extractText(string $imagePath, string $mimeType): string
    {
        $config = config('ai.providers.openai');
        // gpt-4o is multimodal — the configured chat model reads the image directly.
        $model = $config['model'];

        $dataUrl = 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($imagePath));

        $response = $this->client()->post(self::BASE_URL.'/chat/completions', [
            'model' => $model,
            'max_tokens' => (int) $config['max_tokens'],
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an OCR engine. Transcribe the handwritten or printed text in the image exactly, '
                        .'preserving line breaks and structure. Output only the transcribed text with no commentary.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Transcribe all text in this image.'],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI OCR request failed: '.$response->body());
        }

        return trim((string) ($response->json('choices.0.message.content') ?? ''));
    }

    public function embed(array $texts): array
    {
        $response = $this->client()->post(self::BASE_URL.'/embeddings', [
            'model' => config('ai.embedding.model'),
            'input' => array_values($texts),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI embedding request failed: '.$response->body());
        }

        $data = $response->json('data') ?? [];

        return array_map(fn ($item) => array_map('floatval', $item['embedding']), $data);
    }

    public function embeddingDimensions(): int
    {
        return (int) config('ai.embedding.dimensions', 1536);
    }

    public function embeddingModel(): string
    {
        return (string) config('ai.embedding.model');
    }

    public function name(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('ai.providers.openai.api_key'));
    }

    private function client()
    {
        return Http::withToken((string) config('ai.providers.openai.api_key'))
            ->timeout(60)
            ->acceptJson();
    }
}
