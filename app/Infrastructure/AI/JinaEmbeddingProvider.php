<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Jina AI embeddings provider — a free, multilingual embedding backend used as
 * the RAG embedding fallback when OpenAI is unavailable (OpenRouter has no
 * embeddings endpoint). This provider is EMBED-ONLY: the chat/vision methods
 * throw, because it is only ever wired in as the FallbackProvider's embedder.
 *
 * The vectors it produces are tagged with their own model name, so retrieval —
 * which filters stored embeddings by the current model — never mixes them with
 * vectors from a different embedding model. Re-embed the corpus after switching
 * (php artisan rag:reembed).
 */
class JinaEmbeddingProvider implements AIProviderInterface
{
    private const BASE_URL = 'https://api.jina.ai/v1/embeddings';

    public function chat(array $messages, array $options = []): ChatResult
    {
        throw new RuntimeException('JinaEmbeddingProvider is embed-only and cannot run chat completions.');
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        throw new RuntimeException('JinaEmbeddingProvider is embed-only and cannot run chat completions.');
    }

    public function extractText(string $imagePath, string $mimeType): string
    {
        throw new RuntimeException('JinaEmbeddingProvider is embed-only and cannot transcribe images.');
    }

    public function embed(array $texts): array
    {
        $config = config('ai.providers.jina');

        $response = Http::withToken((string) ($config['api_key'] ?? ''))
            ->timeout(60)
            ->acceptJson()
            ->post(self::BASE_URL, [
                'model' => (string) $config['model'],
                // Symmetric task: queries and passages embed into the same space,
                // matching the single embed() call the RAG pipeline uses for both.
                'task' => 'text-matching',
                'dimensions' => (int) $config['dimensions'],
                'input' => array_values($texts),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Jina embedding request failed: '.$response->body());
        }

        $data = $response->json('data') ?? [];

        return array_map(fn ($item) => array_map('floatval', $item['embedding']), $data);
    }

    public function embeddingDimensions(): int
    {
        return (int) config('ai.providers.jina.dimensions', 1024);
    }

    public function embeddingModel(): string
    {
        return (string) config('ai.providers.jina.model');
    }

    public function name(): string
    {
        return 'jina';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('ai.providers.jina.api_key'));
    }
}
