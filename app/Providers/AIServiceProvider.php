<?php

namespace App\Providers;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Infrastructure\AI\AIProviderManager;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIProviderManager::class);

        $this->app->bind(AIProviderInterface::class, function ($app) {
            return $app->make(AIProviderManager::class)->provider();
        });
    }

    public function boot(): void
    {
        $this->applyStoredSettings();
    }

    /**
     * Overlay admin-configured AI settings (stored in the settings table) on top
     * of the static config/env defaults, so changes made in the admin UI take
     * effect at runtime without touching .env. Sensitive keys are stored
     * encrypted; everything is guarded so console/boot before migration is safe.
     */
    private function applyStoredSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $ai = Setting::get('ai');
        } catch (Throwable) {
            return;
        }

        if (! is_array($ai)) {
            return;
        }

        $overrides = [];

        if (! empty($ai['provider'])) {
            $overrides['ai.default_provider'] = $ai['provider'];
        }
        if (! empty($ai['model'])) {
            $overrides['ai.providers.openai.model'] = $ai['model'];
        }
        if (! empty($ai['embedding_model'])) {
            $overrides['ai.embedding.model'] = $ai['embedding_model'];
        }
        if (isset($ai['temperature'])) {
            $overrides['ai.providers.openai.temperature'] = (float) $ai['temperature'];
        }
        if (isset($ai['max_tokens'])) {
            $overrides['ai.providers.openai.max_tokens'] = (int) $ai['max_tokens'];
        }
        if (isset($ai['rag_top_k'])) {
            $overrides['rag.retrieval.top_k'] = (int) $ai['rag_top_k'];
        }
        if (isset($ai['rag_similarity_threshold'])) {
            $overrides['rag.retrieval.similarity_threshold'] = (float) $ai['rag_similarity_threshold'];
        }

        foreach (['openai' => 'openai_api_key', 'gemini' => 'gemini_api_key'] as $provider => $field) {
            if (! empty($ai[$field])) {
                try {
                    $overrides["ai.providers.{$provider}.api_key"] = decrypt($ai[$field]);
                } catch (Throwable) {
                    // Ignore an undecryptable key (e.g. APP_KEY rotated); fall back to env.
                }
            }
        }

        if ($overrides !== []) {
            config($overrides);
        }
    }
}
