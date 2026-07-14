<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resilient provider that fails over across an ordered chat chain
 * (e.g. OpenAI → OpenRouter → Mock). On a failed chat/stream/OCR call it logs
 * the reason and advances to the next provider; the terminal Mock provider never
 * throws, so a result is always returned.
 *
 * Embeddings are NOT part of the chain: they delegate to a dedicated embedder
 * (the primary provider, else Mock) so retrieval only ever compares vectors from
 * one embedding model. OpenRouter — which has no embeddings endpoint — is never
 * used for embed().
 */
class FallbackProvider implements AIProviderInterface
{
    private string $lastServedBy;

    /**
     * @param  array<int, AIProviderInterface>  $chain  Ordered chat providers, first = primary.
     * @param  AIProviderInterface  $embedder  Provider used for all embedding calls.
     */
    public function __construct(
        private readonly array $chain,
        private readonly AIProviderInterface $embedder,
    ) {
        $this->lastServedBy = $this->chain[0]->name() ?? 'mock';
    }

    public function chat(array $messages, array $options = []): ChatResult
    {
        return $this->run('chat', fn (AIProviderInterface $provider) => $provider->chat($messages, $options));
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        $lastError = null;

        foreach ($this->chain as $provider) {
            // Track whether this provider emitted any text: once the user has
            // seen a partial answer we must NOT restart on another provider, or
            // its output would be duplicated on top of what already streamed.
            $emitted = false;

            try {
                $result = $provider->chatStream($messages, function (string $delta) use ($onDelta, &$emitted) {
                    $emitted = true;
                    $onDelta($delta);
                }, $options);

                $this->recordServedBy($provider);

                return $result;
            } catch (Throwable $e) {
                $lastError = $e;

                if ($emitted) {
                    // Partial output already streamed — surface the error rather
                    // than silently duplicating text via a fresh provider.
                    Log::warning("AI stream provider [{$provider->name()}] failed after emitting output; not failing over.", [
                        'reason' => $e->getMessage(),
                    ]);

                    throw $e;
                }

                Log::warning("AI stream provider [{$provider->name()}] failed before first token; failing over.", [
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        throw $lastError ?? new \RuntimeException('No AI provider available for streaming.');
    }

    public function extractText(string $imagePath, string $mimeType): string
    {
        return $this->run('extractText', fn (AIProviderInterface $provider) => $provider->extractText($imagePath, $mimeType));
    }

    public function embed(array $texts): array
    {
        return $this->embedder->embed($texts);
    }

    public function embeddingDimensions(): int
    {
        return $this->embedder->embeddingDimensions();
    }

    public function embeddingModel(): string
    {
        return $this->embedder->embeddingModel();
    }

    public function name(): string
    {
        return $this->lastServedBy;
    }

    public function isAvailable(): bool
    {
        foreach ($this->chain as $provider) {
            if ($provider->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempt $callback against each provider in order, logging failures and
     * advancing to the next. Returns the first success.
     *
     * @param  callable(AIProviderInterface): TReturn  $callback
     * @return TReturn
     *
     * @template TReturn
     */
    private function run(string $capability, callable $callback)
    {
        $lastError = null;

        foreach ($this->chain as $provider) {
            try {
                $result = $callback($provider);
                $this->recordServedBy($provider);

                return $result;
            } catch (Throwable $e) {
                $lastError = $e;
                Log::warning("AI provider [{$provider->name()}] failed on {$capability}; failing over.", [
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        throw $lastError ?? new \RuntimeException("No AI provider available for {$capability}.");
    }

    private function recordServedBy(AIProviderInterface $provider): void
    {
        $this->lastServedBy = $provider->name();

        // Note (not warn) when a non-primary tier served the request, so the
        // failover is visible in logs without being alarming.
        if ($provider !== $this->chain[0]) {
            Log::info("AI request served by fallback tier [{$provider->name()}].");
        }
    }
}
