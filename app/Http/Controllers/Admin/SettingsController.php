<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'rag_top_k' => (int) config('rag.retrieval.top_k'),
                'rag_similarity_threshold' => (float) config('rag.retrieval.similarity_threshold'),
                'system_prompt' => is_string($saved['system_prompt'] ?? null) ? $saved['system_prompt'] : '',
            ],
            'providerOptions' => [
                ['value' => 'openai', 'label' => 'OpenAI'],
                ['value' => 'mock', 'label' => 'Mock (offline / no key)'],
            ],
            'providerStatus' => [
                'active' => app(AIProviderInterface::class)->name(),
                'openaiConfigured' => ! empty(config('ai.providers.openai.api_key')),
                'openaiKeyStored' => ! empty($saved['openai_api_key']),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['nullable', 'in:openai,mock'],
            'model' => ['nullable', 'string', 'max:100'],
            'embedding_model' => ['nullable', 'string', 'max:100'],
            'openai_api_key' => ['nullable', 'string', 'max:300'],
            'remove_openai_key' => ['nullable', 'boolean'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:32000'],
            'rag_top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rag_similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'system_prompt' => ['nullable', 'string', 'max:4000'],
        ]);

        $existing = (array) Setting::get('ai', []);

        // The API key is write-only: store encrypted only when a new value is
        // submitted; a blank field keeps the current key; an explicit remove clears it.
        $removeKey = (bool) ($validated['remove_openai_key'] ?? false);
        unset($validated['remove_openai_key']);

        if (! empty($validated['openai_api_key'])) {
            $validated['openai_api_key'] = encrypt(trim($validated['openai_api_key']));
        } else {
            unset($validated['openai_api_key']);
        }

        $merged = array_merge($existing, $validated);

        if ($removeKey) {
            unset($merged['openai_api_key']);
        }

        Setting::put('ai', $merged);

        return back()->with('success', 'AI settings saved.');
    }

    public function test(): JsonResponse
    {
        $provider = app(AIProviderInterface::class);

        return response()->json([
            'provider' => $provider->name(),
            'available' => $provider->isAvailable(),
            'message' => $provider->name() === 'mock'
                ? 'Using the built-in mock provider (no API key configured).'
                : 'Provider reachable.',
        ]);
    }
}
