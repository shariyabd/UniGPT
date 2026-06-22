<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin AI settings (the `ai` row in the settings table) from the
 * environment, so a fresh install boots with a working OpenAI configuration
 * without anyone re-entering values in the admin UI.
 *
 * The OpenAI key is read from OPENAI_API_KEY (via config) and stored encrypted,
 * exactly as SettingsController@update does — AIServiceProvider decrypts it at
 * boot. When no key is present the provider falls back to the offline mock.
 *
 * Idempotent: Setting::put() upserts the single `ai` row, so re-running the
 * seeder refreshes the values in place rather than duplicating them.
 */
class AISettingsSeeder extends Seeder
{
    /**
     * Standard RAG retrieval profile, calibrated for text-embedding-3-small.
     *
     * These are the known-good values that make grounded answers reliable:
     *  - top_k 6                  — enough passages for multi-hop questions.
     *  - similarity_threshold 0.35 — 3-small's relevant cosines sit ~0.3–0.65;
     *                                the previous 0.7 floor matched nothing and
     *                                collapsed retrieval to a single document.
     *
     * config('rag.retrieval.*') (env-overridable) takes precedence so admins can
     * still tune values, but these constants guarantee the seeder writes a
     * non-failing configuration even if config/env is missing or stale.
     */
    private const STANDARD_TOP_K = 6;

    private const STANDARD_SIMILARITY_THRESHOLD = 0.35;

    public function run(): void
    {
        $this->command->info('Seeding AI settings...');

        // Read the key straight from the environment, NOT from
        // config('ai.providers.openai.api_key'): AIServiceProvider::boot() has
        // already overlaid the stored (possibly stale) DB key onto that config
        // path, so reading config here would re-persist the old value and the
        // corrected .env would never take effect.
        $apiKey = trim((string) env('OPENAI_API_KEY'));
        $hasKey = $apiKey !== '';

        $settings = [
            'provider' => $hasKey ? 'openai' : 'mock',
            'model' => config('ai.providers.openai.model'),
            'embedding_model' => config('ai.embedding.model'),
            'temperature' => (float) config('ai.providers.openai.temperature'),
            'max_tokens' => (int) config('ai.providers.openai.max_tokens'),
            'rag_top_k' => (int) config('rag.retrieval.top_k', self::STANDARD_TOP_K),
            'rag_similarity_threshold' => (float) config(
                'rag.retrieval.similarity_threshold',
                self::STANDARD_SIMILARITY_THRESHOLD,
            ),
        ];

        // The key is stored encrypted (write-only, never exposed to the client).
        // Re-encrypt and store fresh so a rotated APP_KEY can't leave a stale,
        // undecryptable value behind.
        if ($hasKey) {
            $settings['openai_api_key'] = encrypt($apiKey);
        }

        Setting::put('ai', $settings);

        $this->command->info(
            $hasKey
                ? '   ✓ AI settings seeded (provider: openai, key from OPENAI_API_KEY).'
                : '   ✓ AI settings seeded (provider: mock — OPENAI_API_KEY not set).'
        );
    }
}
