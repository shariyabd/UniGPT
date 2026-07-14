## 1. Config

- [x] 1.1 Add an `openrouter` provider block to `config/ai.php` (`api_key`,
      `models` array ending in `openrouter/auto`, `referer`, `title`) reading new
      `OPENROUTER_*` env keys.
- [x] 1.2 Add a `fallback` block to `config/ai.php` (`enabled` from
      `AI_FALLBACK_ENABLED` default false; `chain` = `['openai','openrouter','mock']`).
- [x] 1.3 Document the new env keys (do NOT put the real key in `.env.example`;
      leave placeholders only).

## 2. OpenRouter provider

- [x] 2.1 Create `app/Infrastructure/AI/OpenRouterProvider.php` implementing
      `AIProviderInterface` against `https://openrouter.ai/api/v1`, sending
      `HTTP-Referer` and `X-Title` headers.
- [x] 2.2 Implement `chat()` (OpenAI-compatible messages/response) with model-chain
      iteration ending at `openrouter/auto`; normalize to `ChatResult`.
- [x] 2.3 Implement `chatStream()` consuming OpenRouter SSE, invoking `$onDelta`
      per content chunk, returning the same aggregated `ChatResult` shape.
- [x] 2.4 Implement tool-call parsing (incl. streamed fragments) into the existing
      `ChatResult::$toolCalls` structure so the agent loop is unchanged.
- [x] 2.5 Implement `extractText()` (vision) via an OpenRouter vision-capable model.
- [x] 2.6 Implement `name()`, `isAvailable()` (non-empty key); `embed*()` should
      throw/no-op so embeddings are never taken from this provider.
- [x] 2.7 Factor shared OpenAI-wire parsing with `OpenAiProvider` into a small
      trait/base if it avoids meaningful duplication (keep minimal).

## 3. Fallback decorator

- [x] 3.1 Create `app/Infrastructure/AI/FallbackProvider.php` implementing
      `AIProviderInterface`, wrapping an ordered list of chat providers.
- [x] 3.2 Implement chain iteration for `chat`/`extractText`: try each provider,
      catch throwable, log structured warning (failed tier + reason + serving
      tier), continue; return first success; Mock terminal guarantees a result.
- [x] 3.3 Implement `chatStream` failover with the pre-first-token boundary: fail
      over only if the primary fails before emitting any delta; if it fails after
      partial output, surface the error (no silent restart / duplicate text).
- [x] 3.4 Delegate `embed`/`embeddingModel`/`embeddingDimensions` to a dedicated
      embedding provider (OpenAI→Mock), bypassing the chat chain.
- [x] 3.5 Implement `name()`/`isAvailable()` reflecting the active/last-used tier.

## 4. Manager wiring

- [x] 4.1 Update `AIProviderManager` to build a `FallbackProvider` from
      `config('ai.fallback.chain')` when `fallback.enabled` and an OpenRouter key
      exist (skipping keyless tiers); otherwise keep current single-provider path.
- [x] 4.2 Add `'openrouter' => new OpenRouterProvider()` to the `make()` match.
- [x] 4.3 Preserve the singleton binding; confirm the interface still resolves once
      per request.

## 5. Admin settings (backend)

- [x] 5.1 Extend `AIServiceProvider::applyStoredSettings()` to overlay
      `settings.ai` → config for `openrouter_api_key` (decrypt), `openrouter_models`,
      and `fallback_enabled`.
- [x] 5.2 Update `Admin/SettingsController@update` validation to accept the
      OpenRouter key (encrypt, write-only, keep-if-blank, remove-if-requested),
      `openrouter_models`, and `fallback_enabled`; persist via `Setting::put`.
- [x] 5.3 Update `@index` to add `openrouterConfigured`/`openrouterKeyStored` to
      `providerStatus`; never emit the key value.
- [x] 5.4 Extend `@test` with an OpenRouter branch performing a minimal live chat
      request and returning success/human-readable failure.

## 6. Admin settings (frontend)

- [x] 6.1 Add a fallback enable/disable toggle to `Admin/AISettings.vue`.
- [x] 6.2 Add OpenRouter API key input (+ remove checkbox), write-only like OpenAI.
- [x] 6.3 Add model-chain inputs (with `openrouter/auto` as documented last resort)
      and helper copy noting embeddings stay on OpenAI.
- [x] 6.4 Add an OpenRouter "Test connection" button wired to `@test`.

## 8. Configurable primary provider (either direction)

- [x] 8.1 Replace the hardcoded `fallback.chain` with `fallback.primary`
      (`openai`|`openrouter`, env `AI_FALLBACK_PRIMARY`); `AIProviderManager`
      derives the order [primary, other, mock].
- [x] 8.2 Pin the embedder to OpenAI (else mock) independent of the chat primary.
- [x] 8.3 Overlay `fallback_primary` in `applyStoredSettings`; validate + surface
      it in `SettingsController` index/update.
- [x] 8.4 Add a "Primary provider" radio selector (OpenAI / OpenRouter) to the
      fallback card in `AISettings.vue`.
- [x] 8.5 Tests: OpenRouter-primary serves first (OpenAI untouched); OpenRouter-
      primary fails over to OpenAI.

## 9. Free embedding provider (Jina) for RAG without OpenAI

- [x] 9.1 Add a `jina` provider block to `config/ai.php`; document `JINA_*` env keys.
- [x] 9.2 Create `JinaEmbeddingProvider` (embed-only; chat/vision throw) using
      Jina's OpenAI-shaped `/v1/embeddings` with `task: text-matching`.
- [x] 9.3 Resolve the embedder independently via `config('ai.embedding.provider')`
      in `AIProviderManager` (`resolveEmbedder`); compose so embeddings use it even
      when chat is a different provider; degrade to mock without a key.
- [x] 9.4 Admin settings: embedding-provider selector + encrypted Jina key +
      "Test Embeddings"; overlay `embedding_provider`/`jina_api_key` at runtime.
- [x] 9.5 Add `rag:reembed` command to re-index the whole corpus (library +
      personal shadow docs) with the current embedding model.
- [x] 9.6 Tests: Jina embed round-trip, Jina cannot chat, manager routes
      embeddings→Jina while chat→OpenRouter with OpenAI never contacted.

## 7. Tests & verification

- [x] 7.1 Unit test `OpenRouterProvider` chat/stream/tool parsing (HTTP faked).
- [x] 7.2 Feature/unit test `FallbackProvider`: OpenAI-fails→OpenRouter-serves,
      both-fail→Mock, embeddings never route to OpenRouter, tier logging.
- [x] 7.3 Test admin settings: save/encrypt key, key never returned to client,
      test-connection branch, disabled-fallback preserves OpenAI→Mock.
- [x] 7.4 Manual verify: force an OpenAI failure (bad key) with fallback enabled
      and confirm answers come from OpenRouter with correct `model` + logs; then
      toggle off and confirm current behavior restored.
- [x] 7.5 Run `./vendor/bin/pint`, `php artisan test --filter=...`, and rebuild
      frontend (`npm run build`); re-grep for any missed provider wiring (Rule 0).
