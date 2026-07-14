## Context

AI features resolve a single `AIProviderInterface` from the container. Binding
lives in `app/Providers/AIServiceProvider.php`, which delegates to
`AIProviderManager::provider()`. The manager reads `config('ai.default_provider')`,
constructs one provider (`OpenAiProvider` or `MockProvider`), and — if that
provider's `isAvailable()` is false — swaps in `MockProvider`. There is **no
retry on a failed live response**: an available-but-erroring OpenAI (expired key,
429, 5xx, timeout) throws `RuntimeException` up through `RagChatService`, and the
user gets an error or (in some paths) the degraded mock answer.

Admin settings persist in the `settings` table (`Setting::get/put('ai', ...)`),
overlaid onto config at boot by `AIServiceProvider::applyStoredSettings()`. API
keys are stored `encrypt()`ed and are write-only in the UI
(`resources/js/pages/Admin/AISettings.vue` + `Admin/SettingsController`).

The provider contract (`app/Domain/Chat/Contracts/AIProviderInterface.php`)
exposes: `chat`, `chatStream`, `extractText`, `embed`, `embeddingDimensions`,
`embeddingModel`, `name`, `isAvailable`. OpenRouter is OpenAI-wire-compatible for
chat/streaming/tools but has **no embeddings endpoint**.

## Goals / Non-Goals

**Goals:**
- Transparent per-request failover OpenAI → OpenRouter → Mock for chat, streaming,
  tools, and OCR.
- A drop-in `OpenRouterProvider` reusing OpenAI-compatible request/response shapes.
- Admin-configurable: enable flag, encrypted key, model chain (defaulting to
  `openrouter/auto` last), and a connection test.
- Zero behavior change when the fallback is off or unconfigured.
- Structured logging of which tier served each request.

**Non-Goals:**
- Routing **embeddings** through OpenRouter (unsupported + would break the vector
  space). Embeddings stay on OpenAI with the mock embedder as their only fallback.
- Re-embedding or migrating the existing corpus.
- Load-balancing / cost-optimized routing across providers (this is failover, not
  a router).
- Changing the response envelope consumed by the frontend.

## Decisions

### Decision 1: A `FallbackProvider` decorator, not manager branching
Introduce `app/Infrastructure/AI/FallbackProvider implements AIProviderInterface`
wrapping an **ordered list** of providers. Each capability method
(`chat`, `chatStream`, `extractText`) iterates the chain: try provider N, on
throwable log + continue to N+1; return the first success; if all throw, the last
(Mock) — which never throws — guarantees a result.

- `embed`/`embeddingModel`/`embeddingDimensions` do **not** iterate the chat
  chain. They delegate to a dedicated embedding provider (OpenAI, else Mock) so
  the vector space stays consistent. This is the crux of the "embeddings pinned"
  requirement.
- `name()` returns the active/last-used tier for logging; `isAvailable()` is true
  if any tier is available.

*Alternative considered:* put retry logic inside `AIProviderManager`. Rejected —
the manager is a factory; failover is cross-cutting behavior better expressed as a
composable decorator that any caller resolving the interface gets for free.

### Decision 2: `OpenRouterProvider` extends the OpenAI wire format
Implement `OpenRouterProvider` against `https://openrouter.ai/api/v1` with the
same message/response/SSE/tool-call parsing as `OpenAiProvider` (consider
extracting shared parsing into a small trait/base to avoid duplication, but keep
it minimal — no overengineering). Add required headers `HTTP-Referer` (app URL)
and `X-Title` (app name). `isAvailable()` = non-empty OpenRouter key. For the
model, try each configured model in order, ending at `openrouter/auto`.

*Alternative considered:* a generic "OpenAI-compatible" provider parameterized by
base URL/key/headers, with OpenAI and OpenRouter as instances. Reasonable and
DRY; acceptable if the shared surface is clean, but the two differ in headers,
model chain, and embeddings support, so a focused subclass/trait is clearer. Left
as an implementation choice in tasks.

### Decision 3: Config shape
`config/ai.php` gains:
```php
'providers' => [
  'openrouter' => [
    'api_key'  => env('OPENROUTER_API_KEY'),
    'models'   => array_filter([
        env('OPENROUTER_MODEL'),          // preferred free model
        env('OPENROUTER_MODEL_SECONDARY'),
        'openrouter/auto',                // always-last resort
    ]),
    'referer'  => env('OPENROUTER_REFERER', env('APP_URL')),
    'title'    => env('OPENROUTER_TITLE', env('APP_NAME', 'UniNexus')),
  ],
],
'fallback' => [
  'enabled' => env('AI_FALLBACK_ENABLED', false),
  'chain'   => ['openai', 'openrouter', 'mock'], // order of attempts for chat
],
```
Pick concrete default free model ids at implementation time from OpenRouter's
current free list (they change); `openrouter/auto` guarantees a resolution
regardless. Document that model ids are admin-overridable.

### Decision 4: Manager builds the chain
`AIProviderManager::provider()` returns:
- if `config('ai.fallback.enabled')` and an OpenRouter key exists → a
  `FallbackProvider` built from `config('ai.fallback.chain')` (skipping tiers with
  no key), with the embedding provider resolved separately (OpenAI→Mock);
- else → today's single-provider behavior (unchanged).
Keep the singleton binding so it resolves once per request.

### Decision 5: Admin settings & runtime overlay
- `applyStoredSettings()` maps new `settings.ai` fields → config:
  `openrouter_api_key` (decrypt → `ai.providers.openrouter.api_key`),
  `openrouter_models` → `ai.providers.openrouter.models`,
  `fallback_enabled` → `ai.fallback.enabled`.
- `SettingsController@update` validates and encrypts the OpenRouter key with the
  same write-only/keep-if-blank/remove-if-requested pattern as OpenAI.
- `SettingsController@index` adds `openrouterConfigured`/`openrouterKeyStored` to
  `providerStatus`; never emits the key.
- `SettingsController@test` gains an OpenRouter branch doing a minimal live chat
  request.
- `AISettings.vue` adds: fallback enable toggle, OpenRouter key input (+ remove
  checkbox), model-chain inputs, and an OpenRouter "Test connection" button.

## Risks / Trade-offs

- **Free models are rate-limited / inconsistent** → default the chain to end in
  `openrouter/auto`; treat any per-model error as a reason to advance the chain;
  Mock remains the terminal guarantee.
- **Quality/format drift between OpenAI and free models** (weaker tool-calling,
  looser JSON) → fallback is a resilience tier, not a quality guarantee; keep
  existing `tryJson` tolerant parsing; log tier so degraded answers are traceable.
- **Latency of retry** (a slow OpenAI failure then a second call) → use a bounded
  timeout on the primary so failover is prompt; log timings.
- **Streaming failover mid-stream** → if OpenAI fails *before* the first delta,
  fail over cleanly; if it fails *after* partial output, do **not** silently
  restart (would duplicate text) — surface the error for that request. Document
  this boundary; only pre-first-token failures fail over during streaming.
- **Secret handling** → the shared test key must never be committed; store only
  encrypted in `settings`, keep out of `.env.example`, and rotate before release.
- **Embedding-space corruption if someone wires OpenRouter into embeddings** →
  enforce in code (embed path bypasses the chat chain) and document in the admin
  UI copy.

## Migration Plan

1. Add `OpenRouterProvider` + `FallbackProvider` (no wiring yet) with unit tests.
2. Extend `config/ai.php`; default `AI_FALLBACK_ENABLED=false` → no runtime change.
3. Wire `AIProviderManager` to build the chain when enabled.
4. Extend admin settings (backend validation/store/status/test, then Vue UI).
5. Enable in a non-prod environment, set an OpenRouter key, force an OpenAI
   failure (bad key) and confirm answers come from OpenRouter with correct `model`
   and logs.
6. Roll out with fallback enabled once verified.

**Rollback:** set `fallback_enabled=false` (admin) or `AI_FALLBACK_ENABLED=false`
— instantly reverts to OpenAI → Mock. No schema/data migration to undo.

## Open Questions

- Which specific free OpenRouter model id(s) to seed as defaults (list churns) —
  resolve at implementation; `openrouter/auto` covers gaps regardless.
- Should faculty-assistant streaming get identical failover semantics as student
  chat? (Assumed yes; both share the SSE trait — confirm no faculty-only path is
  missed per Rule 0.)
- Should the "answered-by tier" be surfaced to admins in the UI (e.g. a small
  badge / last-used indicator), or logs-only? (Assumed logs-only for now.)
