<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Support\AiSettings;
use App\Http\Controllers\Controller;
use App\Infrastructure\AI\OpenRouterProvider;
use App\Jobs\ReembedCorpusJob;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $saved = (array) Setting::get('ai', []);

        // Effective values come from config (which the service provider has
        // already overlaid with stored settings). API keys are NEVER sent to the
        // client — only a boolean indicating whether each is configured.
        return Inertia::render('Admin/AISettings', [
            'settings' => [
                'provider' => config('ai.default_provider'),
                'model' => config('ai.providers.openai.model'),
                'temperature' => (float) config('ai.providers.openai.temperature'),
                'max_tokens' => (int) config('ai.providers.openai.max_tokens'),
                'embedding_model' => config('ai.embedding.model'),
                'embedding_provider' => config('ai.embedding.provider') ?: 'openai',
                'embedding_fallback_enabled' => (bool) config('ai.embedding.fallback.enabled'),
                'embedding_fallback_secondary' => config('ai.embedding.fallback.secondary') ?: 'jina',
                // The embedding model actually in effect (Jina uses its own model).
                'embedding_model_active' => app(AIProviderInterface::class)->embeddingModel(),
                'rag_top_k' => (int) config('rag.retrieval.top_k'),
                'rag_similarity_threshold' => (float) config('rag.retrieval.similarity_threshold'),
                'system_prompt' => is_string($saved['system_prompt'] ?? null) ? $saved['system_prompt'] : '',
                'supported_languages' => app(AiSettings::class)->supportedLanguages(),
                'fallback_enabled' => (bool) config('ai.fallback.enabled'),
                'fallback_primary' => config('ai.fallback.primary') === 'openrouter' ? 'openrouter' : 'openai',
                // One model per line for the textarea; the openrouter/auto safety
                // net is always appended server-side so it need not be shown here.
                'openrouter_models' => implode("\n", (array) config('ai.providers.openrouter.models', [])),
            ],
            'providerOptions' => [
                ['value' => 'openai', 'label' => 'OpenAI'],
                ['value' => 'mock', 'label' => 'Mock (offline / no key)'],
            ],
            'providerStatus' => [
                'active' => app(AIProviderInterface::class)->name(),
                'openaiConfigured' => ! empty(config('ai.providers.openai.api_key')),
                'openaiKeyStored' => ! empty($saved['openai_api_key']),
                'openrouterConfigured' => ! empty(config('ai.providers.openrouter.api_key')),
                'openrouterKeyStored' => ! empty($saved['openrouter_api_key']),
                'jinaConfigured' => ! empty(config('ai.providers.jina.api_key')),
                'jinaKeyStored' => ! empty($saved['jina_api_key']),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['nullable', 'in:openai,mock'],
            'model' => ['nullable', 'string', 'max:100'],
            'embedding_model' => ['nullable', 'string', 'max:100'],
            'embedding_provider' => ['nullable', 'in:openai,jina,mock'],
            'embedding_fallback_enabled' => ['nullable', 'boolean'],
            'embedding_fallback_secondary' => ['nullable', 'in:openai,jina,mock'],
            'openai_api_key' => ['nullable', 'string', 'max:300'],
            'remove_openai_key' => ['nullable', 'boolean'],
            'jina_api_key' => ['nullable', 'string', 'max:300'],
            'remove_jina_key' => ['nullable', 'boolean'],
            'fallback_enabled' => ['nullable', 'boolean'],
            'fallback_primary' => ['nullable', 'in:openai,openrouter'],
            'openrouter_api_key' => ['nullable', 'string', 'max:300'],
            'remove_openrouter_key' => ['nullable', 'boolean'],
            'openrouter_models' => ['nullable', 'string', 'max:1000'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:32000'],
            'rag_top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rag_similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'system_prompt' => ['nullable', 'string', 'max:4000'],
            'supported_languages' => ['nullable', 'array', 'min:1'],
            'supported_languages.*.code' => ['required', 'string', 'max:10'],
            'supported_languages.*.name' => ['required', 'string', 'max:50'],
        ]);

        // Normalise supported languages: trim, drop blanks, de-duplicate by code.
        if (array_key_exists('supported_languages', $validated)) {
            $languages = [];
            foreach ((array) $validated['supported_languages'] as $entry) {
                $code = trim((string) ($entry['code'] ?? ''));
                $name = trim((string) ($entry['name'] ?? ''));
                if ($code !== '' && $name !== '') {
                    $languages[$code] = ['code' => $code, 'name' => $name];
                }
            }
            $validated['supported_languages'] = array_values($languages);
        }

        $existing = (array) Setting::get('ai', []);

        // Normalise the OpenRouter model chain: one model per line, trimmed and
        // de-duplicated, always ending at the openrouter/auto safety net.
        if (array_key_exists('openrouter_models', $validated)) {
            $models = [];
            foreach (preg_split('/[\r\n,]+/', (string) $validated['openrouter_models']) ?: [] as $line) {
                $model = trim($line);
                if ($model !== '') {
                    $models[$model] = $model;
                }
            }
            $models['openrouter/auto'] = 'openrouter/auto';
            $validated['openrouter_models'] = array_values($models);
        }

        if (array_key_exists('fallback_enabled', $validated)) {
            $validated['fallback_enabled'] = (bool) $validated['fallback_enabled'];
        }
        if (array_key_exists('embedding_fallback_enabled', $validated)) {
            $validated['embedding_fallback_enabled'] = (bool) $validated['embedding_fallback_enabled'];
        }

        // API keys are write-only: store encrypted only when a new value is
        // submitted; a blank field keeps the current key; an explicit remove clears it.
        $removeKeys = [
            'openai_api_key' => (bool) ($validated['remove_openai_key'] ?? false),
            'openrouter_api_key' => (bool) ($validated['remove_openrouter_key'] ?? false),
            'jina_api_key' => (bool) ($validated['remove_jina_key'] ?? false),
        ];
        unset($validated['remove_openai_key'], $validated['remove_openrouter_key'], $validated['remove_jina_key']);

        foreach (['openai_api_key', 'openrouter_api_key', 'jina_api_key'] as $keyField) {
            if (! empty($validated[$keyField])) {
                $validated[$keyField] = encrypt(trim($validated[$keyField]));
            } else {
                unset($validated[$keyField]);
            }
        }

        $merged = array_merge($existing, $validated);

        foreach ($removeKeys as $keyField => $shouldRemove) {
            if ($shouldRemove) {
                unset($merged[$keyField]);
            }
        }

        Setting::put('ai', $merged);

        // Changing the embeddings backend means stored vectors no longer match
        // the active model — re-index the corpus automatically so RAG keeps
        // working without anyone running rag:reembed by hand.
        if ($this->embeddingConfigChanged($existing, $merged)) {
            $this->applyEmbeddingConfig($merged);

            try {
                ReembedCorpusJob::dispatchSync();

                return back()->with('success', 'AI settings saved. The document corpus was re-indexed with the new embedding model.');
            } catch (\Throwable $e) {
                Log::warning('Auto re-embed after settings change failed: '.$e->getMessage());

                return back()->with('warning', 'AI settings saved, but automatic re-indexing failed — run "php artisan rag:reembed" manually. ('.$e->getMessage().')');
            }
        }

        return back()->with('success', 'AI settings saved.');
    }

    /**
     * Whether the change touches the embeddings backend (provider, fallback, or
     * the Jina key) — the cases that require re-indexing the corpus.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $merged
     */
    private function embeddingConfigChanged(array $existing, array $merged): bool
    {
        foreach (['embedding_provider', 'embedding_fallback_enabled', 'embedding_fallback_secondary', 'jina_api_key'] as $key) {
            if (($existing[$key] ?? null) !== ($merged[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Overlay the just-saved embedding settings onto this request's config so the
     * synchronous re-index uses the NEW backends (the boot-time overlay ran with
     * the old values).
     *
     * @param  array<string, mixed>  $ai
     */
    private function applyEmbeddingConfig(array $ai): void
    {
        if (! empty($ai['embedding_provider'])) {
            config(['ai.embedding.provider' => $ai['embedding_provider']]);
        }
        if (isset($ai['embedding_fallback_enabled'])) {
            config(['ai.embedding.fallback.enabled' => (bool) $ai['embedding_fallback_enabled']]);
        }
        if (! empty($ai['embedding_fallback_secondary'])) {
            config(['ai.embedding.fallback.secondary' => $ai['embedding_fallback_secondary']]);
        }
        if (! empty($ai['jina_api_key'])) {
            try {
                config(['ai.providers.jina.api_key' => decrypt($ai['jina_api_key'])]);
            } catch (\Throwable) {
                // Undecryptable (APP_KEY rotated) — leave config as-is.
            }
        }
    }

    public function test(Request $request): JsonResponse
    {
        // A targeted test of the OpenRouter fallback tier: it has no embeddings
        // endpoint, so verify it with a minimal live chat round-trip instead.
        if ($request->input('provider') === 'openrouter') {
            return $this->testOpenRouter();
        }

        // Verify the embeddings backend specifically (Jina / OpenAI) — RAG depends
        // on it independently of the chat provider.
        if ($request->input('provider') === 'embedding') {
            return $this->testEmbedding();
        }

        $provider = app(AIProviderInterface::class);

        // The mock provider needs no network — report it without a round-trip.
        if ($provider->name() === 'mock') {
            return response()->json([
                'provider' => 'mock',
                'available' => true,
                'message' => 'Using the built-in mock provider (no API key configured).',
            ]);
        }

        // For a real provider, actually reach the API with a minimal embedding
        // call so "reachable" reflects a verified round-trip, not just a key check.
        try {
            $provider->embed(['ping']);

            return response()->json([
                'provider' => $provider->name(),
                'available' => true,
                'message' => 'Provider reachable — test request succeeded.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'provider' => $provider->name(),
                'available' => false,
                'message' => 'Provider unreachable: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify the embeddings backend with a minimal live embed round-trip. RAG
     * retrieval and ingestion depend on this working even when chat is elsewhere.
     */
    private function testEmbedding(): JsonResponse
    {
        $provider = app(AIProviderInterface::class);
        $model = $provider->embeddingModel();

        try {
            $vector = $provider->embed(['ping']);
            $dimensions = isset($vector[0]) ? count($vector[0]) : 0;

            return response()->json([
                'provider' => 'embedding',
                'available' => true,
                'message' => "Embeddings reachable — model {$model} returned {$dimensions}-dim vectors.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'provider' => 'embedding',
                'available' => false,
                'message' => 'Embeddings unreachable: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify the OpenRouter fallback tier with a minimal live chat request.
     */
    private function testOpenRouter(): JsonResponse
    {
        $provider = new OpenRouterProvider;

        if (! $provider->isAvailable()) {
            return response()->json([
                'provider' => 'openrouter',
                'available' => false,
                'message' => 'No OpenRouter API key configured. Save one first, then test.',
            ], 422);
        }

        try {
            $result = $provider->chat(
                [['role' => 'user', 'content' => 'ping']],
                ['max_tokens' => 5],
            );

            return response()->json([
                'provider' => 'openrouter',
                'available' => true,
                'message' => 'OpenRouter reachable — answered with model '.($result->model ?: 'unknown').'.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'provider' => 'openrouter',
                'available' => false,
                'message' => 'OpenRouter unreachable: '.$e->getMessage(),
            ], 422);
        }
    }
}
