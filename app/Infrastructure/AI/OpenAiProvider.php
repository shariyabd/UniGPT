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

        $response = $this->client()->post(self::BASE_URL.'/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $config['max_tokens']),
            'temperature' => (float) ($options['temperature'] ?? $config['temperature']),
        ]);

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
        );
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
