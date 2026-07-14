<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Infrastructure\AI\AIProviderManager;
use App\Infrastructure\AI\FallbackEmbeddingProvider;
use App\Infrastructure\AI\FallbackProvider;
use App\Infrastructure\AI\JinaEmbeddingProvider;
use App\Infrastructure\AI\MockProvider;
use App\Infrastructure\AI\OpenAiProvider;
use App\Infrastructure\AI\OpenRouterProvider;
use App\Jobs\ReembedCorpusJob;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * OpenRouter fallback layer: the OpenRouter provider speaks the OpenAI wire
 * format, the FallbackProvider fails over OpenAI → OpenRouter → Mock for chat
 * while keeping embeddings pinned off OpenRouter, and the admin settings surface
 * stores the key encrypted and tests the tier live.
 */
class OpenRouterFallbackTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.providers.openai.api_key' => 'sk-openai-test',
            'ai.providers.openai.model' => 'gpt-4o',
            'ai.providers.openai.max_tokens' => 128,
            'ai.providers.openai.temperature' => 0.3,
            'ai.providers.openrouter.api_key' => 'sk-or-test',
            'ai.providers.openrouter.models' => ['meta-llama/llama-3.3-70b-instruct:free', 'openrouter/auto'],
            'ai.providers.openrouter.max_tokens' => 128,
            'ai.providers.openrouter.temperature' => 0.3,
        ]);
    }

    private function chatCompletion(string $content, string $model): array
    {
        return [
            'model' => $model,
            'choices' => [['message' => ['content' => $content, 'role' => 'assistant']]],
            'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 5],
        ];
    }

    // --- 7.1 OpenRouterProvider ------------------------------------------------

    public function test_openrouter_provider_chat_returns_normalized_result(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->chatCompletion('Hi from OpenRouter', 'meta-llama/llama-3.3-70b-instruct:free')),
        ]);

        $result = (new OpenRouterProvider)->chat([['role' => 'user', 'content' => 'hello']]);

        $this->assertSame('Hi from OpenRouter', $result->content);
        $this->assertSame('meta-llama/llama-3.3-70b-instruct:free', $result->model);
        $this->assertSame(8, $result->totalTokens());

        // Verify the OpenRouter attribution headers were sent.
        Http::assertSent(fn ($request) => $request->hasHeader('X-Title') && str_contains($request->url(), 'openrouter.ai'));
    }

    public function test_openrouter_provider_advances_model_chain_on_failure(): void
    {
        // First model 429s, the auto fallback answers.
        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push(['error' => 'rate limited'], 429)
                ->push($this->chatCompletion('Routed via auto', 'openrouter/auto')),
        ]);

        $result = (new OpenRouterProvider)->chat([['role' => 'user', 'content' => 'hello']]);

        $this->assertSame('Routed via auto', $result->content);
        $this->assertSame('openrouter/auto', $result->model);
    }

    public function test_openrouter_provider_parses_tool_calls(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'model' => 'meta-llama/llama-3.3-70b-instruct:free',
                'choices' => [['message' => [
                    'content' => '',
                    'tool_calls' => [[
                        'id' => 'call_1',
                        'function' => ['name' => 'get_upcoming_deadlines', 'arguments' => '{"days":7}'],
                    ]],
                ]]],
            ]),
        ]);

        $result = (new OpenRouterProvider)->chat(
            [['role' => 'user', 'content' => 'deadlines?']],
            ['tools' => [['type' => 'function', 'function' => ['name' => 'get_upcoming_deadlines']]]],
        );

        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('get_upcoming_deadlines', $result->toolCalls[0]['name']);
        $this->assertSame(['days' => 7], $result->toolCalls[0]['arguments']);
    }

    public function test_openrouter_provider_streams_deltas(): void
    {
        $sse = "data: {\"model\":\"openrouter/auto\",\"choices\":[{\"delta\":{\"content\":\"Hel\"}}]}\n"
            ."data: {\"choices\":[{\"delta\":{\"content\":\"lo\"}}]}\n"
            ."data: {\"usage\":{\"prompt_tokens\":2,\"completion_tokens\":1}}\n"
            ."data: [DONE]\n";

        Http::fake(['openrouter.ai/*' => Http::response($sse, 200)]);

        $deltas = '';
        $result = (new OpenRouterProvider)->chatStream(
            [['role' => 'user', 'content' => 'hi']],
            function (string $piece) use (&$deltas) {
                $deltas .= $piece;
            },
        );

        $this->assertSame('Hello', $deltas);
        $this->assertSame('Hello', $result->content);
    }

    public function test_openrouter_embed_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new OpenRouterProvider)->embed(['anything']);
    }

    // --- 7.2 FallbackProvider --------------------------------------------------

    private function fallback(): FallbackProvider
    {
        return new FallbackProvider(
            [new OpenAiProvider, new OpenRouterProvider, new MockProvider],
            new OpenAiProvider,
        );
    }

    public function test_fallback_uses_openrouter_when_openai_fails(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'quota exceeded'], 429),
            'openrouter.ai/*' => Http::response($this->chatCompletion('Answered by OpenRouter', 'openrouter/auto')),
        ]);

        $provider = $this->fallback();
        $result = $provider->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame('Answered by OpenRouter', $result->content);
        $this->assertSame('openrouter/auto', $result->model);
        $this->assertSame('openrouter', $provider->name());
    }

    public function test_fallback_falls_through_to_mock_when_both_real_providers_fail(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'down'], 500),
            'openrouter.ai/*' => Http::response(['error' => 'down'], 500),
        ]);

        $provider = $this->fallback();
        $result = $provider->chat([['role' => 'user', 'content' => 'hi']]);

        // Mock never throws — a demo answer is always produced.
        $this->assertSame('mock-chat-v1', $result->model);
        $this->assertSame('mock', $provider->name());
    }

    public function test_fallback_embeddings_never_route_to_openrouter(): void
    {
        // No HTTP fake for embeddings: the embedder is Mock, so nothing is sent.
        Http::fake(['openrouter.ai/*' => Http::response(['error' => 'should not be called'], 500)]);

        $provider = new FallbackProvider(
            [new OpenRouterProvider, new MockProvider],
            new MockProvider,
        );

        $vectors = $provider->embed(['hello world']);

        $this->assertCount(1, $vectors);
        $this->assertCount(256, $vectors[0]);
        $this->assertSame('mock-embedding-v1', $provider->embeddingModel());
        Http::assertNothingSent();
    }

    public function test_fallback_stream_fails_over_before_first_token(): void
    {
        $sse = "data: {\"model\":\"openrouter/auto\",\"choices\":[{\"delta\":{\"content\":\"Fallback stream\"}}]}\n"
            ."data: [DONE]\n";

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'boom'], 500),
            'openrouter.ai/*' => Http::response($sse, 200),
        ]);

        $deltas = '';
        $provider = $this->fallback();
        $result = $provider->chatStream(
            [['role' => 'user', 'content' => 'hi']],
            function (string $piece) use (&$deltas) {
                $deltas .= $piece;
            },
        );

        $this->assertSame('Fallback stream', $deltas);
        $this->assertSame('Fallback stream', $result->content);
        $this->assertSame('openrouter', $provider->name());
    }

    public function test_manager_orders_chain_by_configured_primary(): void
    {
        config(['ai.fallback.enabled' => true, 'ai.fallback.primary' => 'openrouter']);

        // OpenRouter is primary: it answers and OpenAI is never contacted.
        Http::fake([
            'openrouter.ai/*' => Http::response($this->chatCompletion('Primary is OpenRouter', 'openrouter/auto')),
            'api.openai.com/*' => Http::response(['error' => 'should not be called'], 500),
        ]);

        $provider = app(AIProviderManager::class)->provider();
        $result = $provider->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame('Primary is OpenRouter', $result->content);
        $this->assertSame('openrouter', $provider->name());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    public function test_openrouter_primary_falls_back_to_openai(): void
    {
        config(['ai.fallback.enabled' => true, 'ai.fallback.primary' => 'openrouter']);

        // OpenRouter (primary) fails on every model, OpenAI (fallback) answers.
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => 'down'], 500),
            'api.openai.com/*' => Http::response($this->chatCompletion('Backed up by OpenAI', 'gpt-4o')),
        ]);

        $provider = app(AIProviderManager::class)->provider();
        $result = $provider->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame('Backed up by OpenAI', $result->content);
        $this->assertSame('openai', $provider->name());
    }

    // --- Jina embedding fallback ----------------------------------------------

    public function test_jina_provider_embeds_via_api(): void
    {
        config([
            'ai.providers.jina.api_key' => 'jina-test',
            'ai.providers.jina.model' => 'jina-embeddings-v3',
            'ai.providers.jina.dimensions' => 1024,
        ]);

        Http::fake([
            'api.jina.ai/*' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => array_fill(0, 1024, 0.01)],
                ],
            ]),
        ]);

        $provider = new JinaEmbeddingProvider;
        $vectors = $provider->embed(['hello']);

        $this->assertCount(1, $vectors);
        $this->assertCount(1024, $vectors[0]);
        $this->assertSame('jina-embeddings-v3', $provider->embeddingModel());
        $this->assertSame(1024, $provider->embeddingDimensions());

        // Symmetric task + bearer auth were sent.
        Http::assertSent(fn ($request) => $request['task'] === 'text-matching'
            && str_contains($request->url(), 'api.jina.ai'));
    }

    public function test_jina_provider_cannot_chat(): void
    {
        $this->expectException(RuntimeException::class);
        (new JinaEmbeddingProvider)->chat([['role' => 'user', 'content' => 'hi']]);
    }

    public function test_manager_routes_embeddings_to_jina_while_chat_uses_openrouter(): void
    {
        config([
            'ai.fallback.enabled' => true,
            'ai.fallback.primary' => 'openrouter',
            'ai.embedding.provider' => 'jina',
            'ai.providers.jina.api_key' => 'jina-test',
            'ai.providers.jina.model' => 'jina-embeddings-v3',
            'ai.providers.jina.dimensions' => 1024,
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response($this->chatCompletion('chat via openrouter', 'openrouter/auto')),
            'api.jina.ai/*' => Http::response(['data' => [['index' => 0, 'embedding' => array_fill(0, 1024, 0.02)]]]),
            'api.openai.com/*' => Http::response(['error' => 'subscription ended'], 401),
        ]);

        $provider = app(AIProviderManager::class)->provider();

        // Chat resolves via OpenRouter; embeddings resolve via Jina — OpenAI (dead)
        // is contacted for neither.
        $this->assertSame('jina-embeddings-v3', $provider->embeddingModel());
        $this->assertCount(1024, $provider->embed(['ping'])[0]);
        $this->assertSame('chat via openrouter', $provider->chat([['role' => 'user', 'content' => 'hi']])->content);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    // --- Embedding fallback (dual-index) --------------------------------------

    public function test_embedding_backends_lists_primary_and_secondary_when_fallback_enabled(): void
    {
        config([
            'ai.embedding.provider' => 'openai',
            'ai.embedding.fallback.enabled' => true,
            'ai.embedding.fallback.secondary' => 'jina',
            'ai.providers.jina.api_key' => 'jina-test',
        ]);

        $names = array_map(fn ($b) => $b->name(), app(AIProviderManager::class)->embeddingBackends());

        $this->assertSame(['openai', 'jina'], $names);
    }

    public function test_fallback_embedding_provider_fails_over_and_reports_active_model(): void
    {
        config([
            'ai.embedding.model' => 'text-embedding-3-small',
            'ai.providers.jina.api_key' => 'jina-test',
            'ai.providers.jina.model' => 'jina-embeddings-v3',
            'ai.providers.jina.dimensions' => 1024,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'quota'], 429),
            'api.jina.ai/*' => Http::response(['data' => [['index' => 0, 'embedding' => array_fill(0, 1024, 0.03)]]]),
        ]);

        $embedder = new FallbackEmbeddingProvider([
            new OpenAiProvider,
            new JinaEmbeddingProvider,
        ]);

        // Before any call it reports the primary; after OpenAI fails it reports
        // the model that actually produced the vectors.
        $this->assertSame('text-embedding-3-small', $embedder->embeddingModel());
        $vectors = $embedder->embed(['hi']);
        $this->assertCount(1024, $vectors[0]);
        $this->assertSame('jina-embeddings-v3', $embedder->embeddingModel());
        $this->assertSame('jina', $embedder->name());
    }

    public function test_manager_embedder_fails_over_openai_to_jina(): void
    {
        config([
            'ai.embedding.provider' => 'openai',
            'ai.embedding.fallback.enabled' => true,
            'ai.embedding.fallback.secondary' => 'jina',
            'ai.providers.jina.api_key' => 'jina-test',
            'ai.providers.jina.model' => 'jina-embeddings-v3',
            'ai.providers.jina.dimensions' => 1024,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'quota'], 429),
            'api.jina.ai/*' => Http::response(['data' => [['index' => 0, 'embedding' => array_fill(0, 1024, 0.04)]]]),
        ]);

        $provider = app(AIProviderManager::class)->provider();
        $vector = $provider->embed(['ping'])[0];

        $this->assertCount(1024, $vector);
        $this->assertSame('jina-embeddings-v3', $provider->embeddingModel());
    }

    public function test_changing_embedding_provider_dispatches_reembed(): void
    {
        Bus::fake();

        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'embedding_provider' => 'jina',
                'jina_api_key' => 'jina-secret',
            ])
            ->assertRedirect();

        Bus::assertDispatchedSync(ReembedCorpusJob::class);
    }

    // --- 7.3 Admin settings ----------------------------------------------------

    private function admin()
    {
        $admin = User::where('email', 'admin@university.edu')->first();
        if (! $admin) {
            $this->markTestSkipped('Demo admin not seeded; run php artisan db:seed.');
        }

        return $admin;
    }

    public function test_settings_stores_openrouter_key_encrypted_and_never_returns_it(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'fallback_enabled' => true,
                'openrouter_api_key' => 'sk-or-secret-value',
                'openrouter_models' => "meta-llama/llama-3.3-70b-instruct:free\ngoogle/gemini-2.0-flash-exp:free",
            ])
            ->assertRedirect();

        $ai = (array) Setting::get('ai', []);

        // Stored encrypted (not plaintext), and openrouter/auto is appended.
        $this->assertArrayHasKey('openrouter_api_key', $ai);
        $this->assertNotSame('sk-or-secret-value', $ai['openrouter_api_key']);
        $this->assertSame('sk-or-secret-value', decrypt($ai['openrouter_api_key']));
        $this->assertTrue($ai['fallback_enabled']);
        $this->assertContains('openrouter/auto', $ai['openrouter_models']);

        // The index page must never expose the key, only a "stored" flag.
        $this->actingAs($this->admin())
            ->get(route('admin.settings'))
            ->assertInertia(fn ($page) => $page
                ->where('providerStatus.openrouterKeyStored', true)
                ->missing('settings.openrouter_api_key'));
    }

    public function test_test_connection_openrouter_branch(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response($this->chatCompletion('pong', 'openrouter/auto')),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.settings.test'), ['provider' => 'openrouter'])
            ->assertOk()
            ->assertJson(['provider' => 'openrouter', 'available' => true]);
    }

    public function test_test_connection_openrouter_without_key_reports_unavailable(): void
    {
        config(['ai.providers.openrouter.api_key' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.settings.test'), ['provider' => 'openrouter'])
            ->assertStatus(422)
            ->assertJson(['provider' => 'openrouter', 'available' => false]);
    }
}
