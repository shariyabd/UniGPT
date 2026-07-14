## Why

The AI chat and RAG features depend on a single provider (OpenAI). If the OpenAI
key is exhausted, the subscription lapses, or the API returns an error/timeout,
every AI surface (student chat, faculty assistant, quiz/flashcard generation,
OCR) degrades to the keyless `MockProvider` — which returns demo-quality answers,
not real ones. We want a real second-tier: when OpenAI fails, transparently retry
the same request against **OpenRouter** (which exposes free, OpenAI-compatible
chat models) before ever dropping to mock. This keeps the product usable during
outages or billing gaps at effectively zero marginal cost.

## What Changes

- Introduce a **resilient provider chain** for chat/completions: primary
  (OpenAI) → fallback (OpenRouter) → last-resort (Mock). On a failed OpenAI
  response (HTTP error, timeout, unavailable key), the request is instantly
  re-attempted on OpenRouter with no visible interruption to the user.
- Add a new **`OpenRouterProvider`** implementing the existing
  `AIProviderInterface`. It reuses OpenAI-compatible request/response shapes for
  `chat`, `chatStream` (SSE), and tool-calling, and sets OpenRouter's required
  `HTTP-Referer`/`X-Title` headers.
- Add a **`FallbackProvider`** decorator that wraps an ordered list of providers
  and implements the try-next-on-failure logic (per-call, per-capability), with
  structured logging of which tier served each request.
- **Configurable OpenRouter models** with sensible defaults:
  - preferred free chat model (e.g. a free instruct model),
  - a secondary free chat model,
  - final fallback `openrouter/auto` (OpenRouter auto-routes to whatever is
    available at that moment).
- **Admin panel controls** (Admin → AI Settings): enable/disable the fallback,
  store the OpenRouter API key (encrypted, write-only, like the OpenAI key), set
  the OpenRouter chat model chain, and a "Test connection" for OpenRouter.
- **Embeddings stay on OpenAI** by design (OpenRouter has no embeddings
  endpoint, and mixing embedding models corrupts the existing vector space). The
  fallback for embeddings remains the current Mock hash-embedder, and the admin
  UI documents this constraint. This is called out explicitly so "best embedding
  model" is understood as "keep the corpus-consistent OpenAI embedding model".
- `config/ai.php` gains an `openrouter` provider block and a `fallback` chain
  definition; `AIProviderManager` builds the chain instead of a single provider.

No breaking changes: when the fallback is disabled or no OpenRouter key is set,
behavior is identical to today (OpenAI → Mock).

## Capabilities

### New Capabilities
- `ai-provider-fallback`: A resilient, admin-configurable provider chain that
  automatically fails over from the primary AI provider (OpenAI) to OpenRouter
  (free models, incl. `openrouter/auto`) and finally to the offline mock, for
  chat, streaming, tool-calling, and OCR — while keeping embeddings pinned to the
  corpus-consistent provider.

### Modified Capabilities
<!-- No existing capability specs in openspec/specs/; all behavior is introduced fresh under the new capability. -->

## Impact

- **New code**: `app/Infrastructure/AI/OpenRouterProvider.php`,
  `app/Infrastructure/AI/FallbackProvider.php`.
- **Modified code**: `app/Providers/AIServiceProvider.php` (settings overlay for
  OpenRouter + fallback flag), `app/Domain/Chat/Services/AIProviderManager.php`
  (build chain vs. single provider), `config/ai.php` (new `openrouter` block +
  `fallback` config), `app/Http/Controllers/Admin/SettingsController.php`
  (validation, encrypt/store key, provider status, test), and
  `resources/js/pages/Admin/AISettings.vue` (new fields + test button).
- **APIs/behavior**: All AI endpoints (`chat`, `chat.stream`,
  `faculty.ai-assistant*`, quiz/flashcard generation, `notes.ocr`) transparently
  gain the fallback path; response envelopes (`model`, `tokens`, tool activity)
  are unchanged — `model` now reflects which tier answered.
- **Config/secrets**: new `OPENROUTER_API_KEY`, `OPENROUTER_MODEL*` env keys and
  a `settings.ai.openrouter_api_key` (encrypted) admin-stored value. The key the
  user shared is for testing only and must be replaced/rotated before release; it
  is never committed to the repo.
- **Dependencies**: none new — reuses the Laravel `Http` client already used by
  `OpenAiProvider`.
