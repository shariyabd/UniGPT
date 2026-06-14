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
        $saved = Setting::get('ai', []);

        return Inertia::render('Admin/AISettings', [
            'settings' => array_merge([
                'provider' => config('ai.default_provider'),
                'model' => config('ai.providers.openai.model'),
                'temperature' => (float) config('ai.providers.openai.temperature'),
                'max_tokens' => (int) config('ai.providers.openai.max_tokens'),
                'embedding_model' => config('ai.embedding.model'),
                'rag_top_k' => (int) config('rag.retrieval.top_k'),
                'rag_similarity_threshold' => (float) config('rag.retrieval.similarity_threshold'),
                'system_prompt' => '',
            ], is_array($saved) ? $saved : []),
            'providerStatus' => [
                'active' => app(AIProviderInterface::class)->name(),
                'openaiConfigured' => ! empty(config('ai.providers.openai.api_key')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:32000'],
            'rag_top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rag_similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'system_prompt' => ['nullable', 'string', 'max:4000'],
        ]);

        Setting::put('ai', array_merge((array) Setting::get('ai', []), $validated));

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
