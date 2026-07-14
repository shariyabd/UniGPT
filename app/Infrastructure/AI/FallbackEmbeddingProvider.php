<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Embed-only provider that fails over across an ordered list of embedding
 * backends (e.g. OpenAI → Jina). At query time, if the primary embeddings API
 * fails, it retries the next backend and remembers which one served the request,
 * so embeddingModel()/embeddingDimensions() report the SAME model the vectors
 * were produced with — keeping retrieval's per-model filter consistent.
 *
 * This only keeps RAG working if the corpus was dual-embedded (documents carry
 * vectors from every backend); see EmbeddingService::embedDocument, which embeds
 * with each backend so both models' vectors exist.
 *
 * Chat methods throw — this provider is only ever wired in as an embedder.
 */
class FallbackEmbeddingProvider implements AIProviderInterface
{
    private ?AIProviderInterface $active = null;

    /**
     * @param  array<int, AIProviderInterface>  $backends  Ordered, non-empty; primary first.
     */
    public function __construct(private readonly array $backends) {}

    public function chat(array $messages, array $options = []): ChatResult
    {
        throw new RuntimeException('FallbackEmbeddingProvider is embed-only and cannot run chat completions.');
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        throw new RuntimeException('FallbackEmbeddingProvider is embed-only and cannot run chat completions.');
    }

    public function extractText(string $imagePath, string $mimeType): string
    {
        throw new RuntimeException('FallbackEmbeddingProvider is embed-only and cannot transcribe images.');
    }

    public function embed(array $texts): array
    {
        $lastError = null;

        foreach ($this->backends as $backend) {
            try {
                $vectors = $backend->embed($texts);
                $this->active = $backend;

                return $vectors;
            } catch (Throwable $e) {
                $lastError = $e;
                Log::warning("Embedding backend [{$backend->name()}] failed; failing over to the next.", [
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        throw $lastError ?? new RuntimeException('No embedding backend is available.');
    }

    public function embeddingDimensions(): int
    {
        return $this->current()->embeddingDimensions();
    }

    public function embeddingModel(): string
    {
        return $this->current()->embeddingModel();
    }

    public function name(): string
    {
        return $this->current()->name();
    }

    public function isAvailable(): bool
    {
        foreach ($this->backends as $backend) {
            if ($backend->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The backend that last served an embed() — or the primary before the first
     * call — so model/dimension reporting matches the vectors just produced.
     */
    private function current(): AIProviderInterface
    {
        return $this->active ?? $this->backends[0];
    }
}
