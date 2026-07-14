<?php

namespace Database\Seeders;

use App\Infrastructure\AI\AIProviderManager;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin AI settings (the `ai` row in the settings table) from the
 * environment, so a fresh install boots with a working, resilient configuration
 * without anyone re-entering values in the admin UI.
 *
 * Provider keys are read straight from env (OPENAI_API_KEY, OPENROUTER_API_KEY,
 * JINA_API_KEY) and stored encrypted, exactly as SettingsController@update does.
 * The config is chosen to keep BOTH chat and RAG working even when the OpenAI
 * quota is exhausted:
 *  - Chat: enable the OpenRouter fallback (free models) whenever an OpenRouter
 *    key exists, so a dead OpenAI key never breaks chat.
 *  - Embeddings: prefer Jina (free, multilingual) when a Jina key exists, so the
 *    KnowledgeBaseSeeder that runs right after this can embed + APPROVE documents
 *    instead of marking them FAILED on a dead OpenAI embeddings endpoint.
 *
 * The chosen values are ALSO applied to the live config in-process (boot's
 * settings overlay ran before this row existed) and the provider manager is
 * reset, so later seeders in the same run pick up this configuration.
 *
 * Idempotent: Setting::put() upserts the single `ai` row.
 */
class AISettingsSeeder extends Seeder
{
    /**
     * Standard RAG retrieval profile. similarity_threshold 0.35 suits both
     * text-embedding-3-small and jina-embeddings-v3 (relevant cosines ~0.4–0.7);
     * top_k 6 gives enough passages for multi-hop questions.
     */
    private const STANDARD_TOP_K = 6;

    private const STANDARD_SIMILARITY_THRESHOLD = 0.35;

    public function run(): void
    {
        $this->command->info('Seeding AI settings...');

        // Read keys straight from the environment, NOT from config('ai.*'):
        // AIServiceProvider::boot() may have overlaid a stale DB value onto that
        // config path, so reading config here could re-persist the old value.
        $openaiKey = trim((string) env('OPENAI_API_KEY'));
        $openrouterKey = trim((string) env('OPENROUTER_API_KEY'));
        $jinaKey = trim((string) env('JINA_API_KEY'));

        // Embeddings: Jina when available (survives an OpenAI outage), else
        // OpenAI, else the offline mock. This is what the document seeder needs.
        $embeddingProvider = $jinaKey !== '' ? 'jina' : ($openaiKey !== '' ? 'openai' : 'mock');

        // Chat fallback kicks in whenever an OpenRouter key exists. Lead with
        // OpenRouter as the primary so a dead OpenAI quota never blocks chat.
        $fallbackEnabled = $openrouterKey !== '';
        $fallbackPrimary = $openrouterKey !== '' ? 'openrouter' : 'openai';

        $settings = [
            'provider' => $openaiKey !== '' ? 'openai' : 'mock',
            'model' => config('ai.providers.openai.model'),
            'embedding_model' => config('ai.embedding.model'),
            'embedding_provider' => $embeddingProvider,
            'temperature' => (float) config('ai.providers.openai.temperature'),
            'max_tokens' => (int) config('ai.providers.openai.max_tokens'),
            'rag_top_k' => (int) config('rag.retrieval.top_k', self::STANDARD_TOP_K),
            'rag_similarity_threshold' => (float) config(
                'rag.retrieval.similarity_threshold',
                self::STANDARD_SIMILARITY_THRESHOLD,
            ),
            'fallback_enabled' => $fallbackEnabled,
            'fallback_primary' => $fallbackPrimary,
        ];

        // Keys are stored encrypted (write-only, never exposed to the client).
        // Re-encrypt fresh so a rotated APP_KEY can't leave a stale value behind.
        if ($openaiKey !== '') {
            $settings['openai_api_key'] = encrypt($openaiKey);
        }
        if ($openrouterKey !== '') {
            $settings['openrouter_api_key'] = encrypt($openrouterKey);
        }
        if ($jinaKey !== '') {
            $settings['jina_api_key'] = encrypt($jinaKey);
        }

        Setting::put('ai', $settings);

        $this->applyToRuntimeConfig($embeddingProvider, $fallbackEnabled, $fallbackPrimary, $openrouterKey, $jinaKey);

        $this->command->info(
            "   ✓ AI settings seeded (chat: {$this->chatLabel($openaiKey, $openrouterKey)}, embeddings: {$embeddingProvider})."
        );
    }

    /**
     * Overlay the seeded settings onto the live config for the rest of THIS seed
     * run (boot's overlay ran before this row existed) and drop the cached
     * provider so later seeders — e.g. KnowledgeBaseSeeder — embed with the
     * configured provider rather than the stale env default.
     */
    private function applyToRuntimeConfig(
        string $embeddingProvider,
        bool $fallbackEnabled,
        string $fallbackPrimary,
        string $openrouterKey,
        string $jinaKey,
    ): void {
        config([
            'ai.embedding.provider' => $embeddingProvider,
            'ai.fallback.enabled' => $fallbackEnabled,
            'ai.fallback.primary' => $fallbackPrimary,
        ]);

        if ($openrouterKey !== '') {
            config(['ai.providers.openrouter.api_key' => $openrouterKey]);
        }
        if ($jinaKey !== '') {
            config(['ai.providers.jina.api_key' => $jinaKey]);
        }

        // The manager caches the resolved provider; forget it so it rebuilds
        // against the config just applied.
        app()->forgetInstance(AIProviderManager::class);
    }

    private function chatLabel(string $openaiKey, string $openrouterKey): string
    {
        if ($openrouterKey !== '') {
            return $openaiKey !== '' ? 'openai + openrouter fallback' : 'openrouter';
        }

        return $openaiKey !== '' ? 'openai' : 'mock';
    }
}
