<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the active AI provider from config. When the OpenRouter fallback is
 * enabled and configured, wraps the configured chat chain in a FallbackProvider
 * (OpenAI → OpenRouter → Mock); otherwise resolves a single provider, falling
 * back to the keyless MockProvider whenever the configured one is unavailable.
 */
class AIProviderManager
{
    private ?AIProviderInterface $resolved = null;

    public function provider(): AIProviderInterface
    {
        if ($this->resolved instanceof AIProviderInterface) {
            return $this->resolved;
        }

        $key = (string) config('ai.default_provider', 'mock');
        $embedder = $this->resolveEmbedder();

        // Fallback chain only kicks in when explicitly enabled AND an OpenRouter
        // key exists; otherwise behavior is identical to before (single provider).
        if (config('ai.fallback.enabled') && ! empty(config('ai.providers.openrouter.api_key'))) {
            return $this->resolved = $this->buildFallback($embedder);
        }

        $provider = $this->make($key);

        if (! $provider->isAvailable()) {
            if ($key !== 'mock') {
                Log::warning("AI provider [{$key}] is unavailable (missing credentials); falling back to mock.");
            }
            $provider = new MockProvider;
        }

        // When embeddings come from a different provider than chat (e.g. chat on
        // mock/OpenRouter, embeddings on Jina), or an embedding fallback is
        // active, compose so embed() uses the dedicated embedder while chat
        // behavior is unchanged (single-element chain: a chat failure still
        // surfaces, exactly as before).
        if ($embedder->name() !== $provider->name() || config('ai.embedding.fallback.enabled')) {
            return $this->resolved = new FallbackProvider([$provider], $embedder);
        }

        return $this->resolved = $provider;
    }

    /**
     * Build a FallbackProvider whose chat chain leads with the admin-selected
     * primary (config('ai.fallback.primary')) and falls back to the other real
     * provider, then the terminal Mock which never fails. The embedder is
     * resolved separately (see resolveEmbedder) — OpenRouter has no embeddings
     * endpoint, so embeddings never route through the chat chain.
     */
    private function buildFallback(AIProviderInterface $embedder): AIProviderInterface
    {
        $primaryKey = config('ai.fallback.primary') === 'openrouter' ? 'openrouter' : 'openai';

        // The two real chat tiers, primary first and the other as its fallback.
        $order = $primaryKey === 'openrouter'
            ? ['openrouter', 'openai']
            : ['openai', 'openrouter'];

        $chain = [];
        foreach ($order as $providerKey) {
            $provider = $this->make($providerKey);
            if ($provider->isAvailable()) {
                $chain[$providerKey] = $provider;
            }
        }

        // The terminal tier must never fail, so a result is always produced.
        $chain['mock'] ??= new MockProvider;

        return new FallbackProvider(array_values($chain), $embedder);
    }

    /**
     * Resolve the embeddings provider — independent of chat, so RAG keeps working
     * when the chat provider (OpenAI) is down. When an embedding fallback is
     * configured with more than one available backend, returns a
     * FallbackEmbeddingProvider (primary → secondary); otherwise the single
     * backend.
     */
    private function resolveEmbedder(): AIProviderInterface
    {
        $backends = $this->embeddingBackends();

        return count($backends) > 1
            ? new FallbackEmbeddingProvider($backends)
            : $backends[0];
    }

    /**
     * The ordered, concrete embedding backends to use: the primary
     * (config('ai.embedding.provider')) and, when the embedding fallback is
     * enabled, the secondary. Only available backends (key present) are kept,
     * de-duplicated by name; degrades to the keyless mock embedder when none are
     * available so ingestion/retrieval never hard-fail. Used both to compose the
     * query-time fallback embedder and to DUAL-EMBED documents at ingest.
     *
     * @return array<int, AIProviderInterface>
     */
    public function embeddingBackends(): array
    {
        $keys = [(string) config('ai.embedding.provider', 'openai')];

        if (config('ai.embedding.fallback.enabled')) {
            $keys[] = (string) config('ai.embedding.fallback.secondary', 'jina');
        }

        $backends = [];
        foreach ($keys as $key) {
            $backend = $this->make($key);
            if ($backend->isAvailable() && ! isset($backends[$backend->name()])) {
                $backends[$backend->name()] = $backend;
            }
        }

        return $backends !== [] ? array_values($backends) : [new MockProvider];
    }

    private function make(string $key): AIProviderInterface
    {
        return match ($key) {
            'openai' => new OpenAiProvider,
            'openrouter' => new OpenRouterProvider,
            'jina' => new JinaEmbeddingProvider,
            default => new MockProvider,
        };
    }
}
