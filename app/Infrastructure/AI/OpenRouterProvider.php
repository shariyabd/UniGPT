<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use App\Infrastructure\AI\Concerns\ParsesOpenAiChatWire;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenRouter provider — the automatic fallback tier for chat/completions.
 *
 * OpenRouter is OpenAI-wire compatible, so chat/streaming/tool-calling reuse the
 * same request/response shapes (see ParsesOpenAiChatWire). It exposes a large
 * catalogue of free models; we try a configured model chain in order and always
 * end at `openrouter/auto`, which lets OpenRouter pick any currently-available
 * model so a request still resolves when a specific free model is down.
 *
 * OpenRouter has NO embeddings endpoint: embed() throws, and the fallback layer
 * keeps embeddings pinned to the primary (OpenAI) provider so the vector space
 * stays consistent with already-stored embeddings.
 */
class OpenRouterProvider implements AIProviderInterface
{
    use ParsesOpenAiChatWire;

    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function chat(array $messages, array $options = []): ChatResult
    {
        $config = config('ai.providers.openrouter');
        $lastError = null;

        foreach ($this->models($options) as $model) {
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
                $lastError = 'OpenRouter chat request failed ('.$model.'): '.$response->body();

                continue;
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

        throw new RuntimeException($lastError ?? 'OpenRouter chat request failed: no models configured.');
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        $config = config('ai.providers.openrouter');
        $lastError = null;

        foreach ($this->models($options) as $model) {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => (int) ($options['max_tokens'] ?? $config['max_tokens']),
                'temperature' => (float) ($options['temperature'] ?? $config['temperature']),
                'stream' => true,
                'stream_options' => ['include_usage' => true],
            ];

            if (! empty($options['tools'])) {
                $payload['tools'] = $options['tools'];
                $payload['tool_choice'] = 'auto';
            }

            $response = $this->client()
                ->withOptions(['stream' => true])
                ->post(self::BASE_URL.'/chat/completions', $payload);

            // The status is known before the body is read, so an unavailable
            // model fails over to the next one before any delta is emitted.
            if ($response->failed()) {
                $lastError = 'OpenRouter chat stream request failed ('.$model.'): '.$response->body();

                continue;
            }

            $parsed = $this->consumeChatStream($response->toPsrResponse()->getBody(), $model, $onDelta);

            return new ChatResult(
                content: $parsed['content'],
                model: $parsed['model'],
                promptTokens: $parsed['prompt_tokens'],
                completionTokens: $parsed['completion_tokens'],
                raw: ['provider' => 'openrouter', 'streamed' => true],
                toolCalls: $parsed['tool_calls'],
            );
        }

        throw new RuntimeException($lastError ?? 'OpenRouter chat stream request failed: no models configured.');
    }

    public function extractText(string $imagePath, string $mimeType): string
    {
        $config = config('ai.providers.openrouter');
        $dataUrl = 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($imagePath));
        $lastError = null;

        foreach ($this->models() as $model) {
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
                $lastError = 'OpenRouter OCR request failed ('.$model.'): '.$response->body();

                continue;
            }

            return trim((string) ($response->json('choices.0.message.content') ?? ''));
        }

        throw new RuntimeException($lastError ?? 'OpenRouter OCR request failed: no models configured.');
    }

    public function embed(array $texts): array
    {
        // OpenRouter has no embeddings endpoint. Embeddings must stay on the
        // primary provider so retrieval only compares same-model vectors; the
        // fallback layer never routes embed() here.
        throw new RuntimeException('OpenRouter does not provide an embeddings endpoint.');
    }

    public function embeddingDimensions(): int
    {
        throw new RuntimeException('OpenRouter does not provide an embeddings endpoint.');
    }

    public function embeddingModel(): string
    {
        throw new RuntimeException('OpenRouter does not provide an embeddings endpoint.');
    }

    public function name(): string
    {
        return 'openrouter';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('ai.providers.openrouter.api_key'));
    }

    /**
     * The ordered model chain to attempt. An explicit `model` option wins;
     * otherwise the configured chain (which always ends at `openrouter/auto`).
     *
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function models(array $options = []): array
    {
        if (! empty($options['model'])) {
            return [(string) $options['model']];
        }

        $models = array_values(array_filter((array) config('ai.providers.openrouter.models', [])));

        return $models !== [] ? $models : ['openrouter/auto'];
    }

    private function client()
    {
        $config = config('ai.providers.openrouter');

        return Http::withToken((string) ($config['api_key'] ?? ''))
            ->withHeaders([
                // OpenRouter attributes free-tier usage via these headers.
                'HTTP-Referer' => (string) ($config['referer'] ?? ''),
                'X-Title' => (string) ($config['title'] ?? ''),
            ])
            ->timeout(60)
            ->acceptJson();
    }
}
