## ADDED Requirements

### Requirement: Automatic failover from primary provider to OpenRouter

The system SHALL, when the primary AI provider (OpenAI) fails to return a
successful chat/completion response, automatically retry the same request against
OpenRouter before falling back to the offline mock provider. A "failure" SHALL
include an HTTP error status, a network timeout, an exception, or an
unconfigured/unavailable primary provider. The failover SHALL be transparent to
the caller: the returned result envelope (content, `model`, token counts, tool
activity) keeps the same shape, with `model` reflecting the tier that answered.

#### Scenario: OpenAI returns an error and OpenRouter succeeds

- **WHEN** a student sends a chat message and the OpenAI request returns a non-2xx
  response (e.g. 401 expired key, 429 quota exceeded, 5xx)
- **THEN** the system SHALL immediately re-issue the request to OpenRouter using
  the configured OpenRouter chat model
- **AND** SHALL return OpenRouter's answer to the user with `model` set to the
  OpenRouter model that served it, without surfacing an error

#### Scenario: OpenAI times out

- **WHEN** the OpenAI request exceeds the configured request timeout
- **THEN** the system SHALL treat it as a failure and fail over to OpenRouter
  rather than blocking or returning the mock response

#### Scenario: Both real providers fail

- **WHEN** both OpenAI and OpenRouter fail for a request
- **THEN** the system SHALL fall back to the offline mock provider so the feature
  never hard-errors for the user

#### Scenario: Failover tier is logged

- **WHEN** any request is served by a non-primary tier
- **THEN** the system SHALL log a structured warning identifying the failed
  primary, the reason, and the tier that ultimately served the request

### Requirement: OpenRouter provider with OpenAI-compatible chat, streaming, and tools

The system SHALL provide an OpenRouter provider that implements the existing AI
provider contract for `chat`, `chatStream`, tool-calling, and `extractText`
(vision/OCR). It SHALL use OpenRouter's OpenAI-compatible endpoints and send the
`HTTP-Referer` and `X-Title` headers OpenRouter requires for free-tier routing.

#### Scenario: Non-streaming chat via OpenRouter

- **WHEN** a chat request is routed to OpenRouter
- **THEN** the provider SHALL POST OpenAI-format messages to OpenRouter's
  chat/completions endpoint and return a normalized result including content,
  model, and token usage

#### Scenario: Streaming chat via OpenRouter

- **WHEN** a streaming chat request is routed to OpenRouter
- **THEN** the provider SHALL consume OpenRouter's SSE stream, invoke the delta
  callback for each content chunk, and return the same aggregated result shape as
  the non-streaming path

#### Scenario: Tool calls preserved through OpenRouter

- **WHEN** a request offering agent tools is routed to OpenRouter and the model
  emits tool calls
- **THEN** the provider SHALL parse them into the same tool-call structure the
  agent loop already consumes, so agentic behavior works identically

### Requirement: Configurable OpenRouter model chain with auto last-resort

The system SHALL allow configuration of an ordered list of OpenRouter chat models
to attempt, and SHALL default the final entry to `openrouter/auto` so a request
still resolves when specific free models are momentarily unavailable. Model
selection SHALL be free-tier by default.

#### Scenario: Preferred free model used first

- **WHEN** the OpenRouter fallback handles a request and the preferred configured
  free model is available
- **THEN** that model SHALL be used

#### Scenario: Auto routing as final resort

- **WHEN** the configured specific OpenRouter models are unavailable or error
- **THEN** the system SHALL attempt `openrouter/auto` so OpenRouter selects any
  currently-available model

### Requirement: Configurable primary provider (either direction)

The system SHALL let an administrator choose which provider leads the chat chain —
OpenAI or OpenRouter — from the admin panel. The chosen provider is attempted
first and the other becomes the automatic fallback tier, with the offline mock
always terminal. Embeddings SHALL remain on OpenAI regardless of which provider
leads chat.

#### Scenario: OpenRouter selected as primary

- **WHEN** the primary provider is set to OpenRouter and a chat request is made
- **THEN** the system SHALL attempt OpenRouter first, and only contact OpenAI if
  OpenRouter fails

#### Scenario: OpenAI selected as primary

- **WHEN** the primary provider is set to OpenAI and a chat request is made
- **THEN** the system SHALL attempt OpenAI first and fail over to OpenRouter on
  failure

#### Scenario: Embeddings unaffected by primary selection

- **WHEN** OpenRouter is the primary chat provider and an embedding is requested
- **THEN** the system SHALL still use the OpenAI embedding model (or the mock
  embedder when no OpenAI key exists), never OpenRouter

### Requirement: Configurable embedding provider independent of chat

The system SHALL allow the embeddings backend to be selected independently of the
chat provider (config `ai.embedding.provider`, admin-configurable), so RAG keeps
working when the chat provider is unavailable. It SHALL support a free embeddings
provider (Jina AI) alongside OpenAI and the offline mock. Because stored vectors
are tagged by model and retrieval only scores same-model vectors, the system SHALL
provide a re-embed command to re-index the corpus after switching providers.

#### Scenario: Embeddings via Jina while chat uses OpenRouter

- **WHEN** the embedding provider is Jina and chat is served by OpenRouter (OpenAI
  unavailable)
- **THEN** query and document embeddings SHALL be produced by Jina and chat
  answers by OpenRouter, and OpenAI SHALL be contacted for neither

#### Scenario: Re-embed after switching provider

- **WHEN** an administrator switches the embedding provider and runs the re-embed
  command
- **THEN** all corpus documents (library and personal shadow docs) SHALL be
  re-embedded and tagged with the new model so retrieval returns results

#### Scenario: Embedding provider degrades to mock without a key

- **WHEN** the configured embedding provider has no API key
- **THEN** the system SHALL fall back to the offline mock embedder so RAG never
  hard-fails

### Requirement: Embeddings remain pinned to the corpus-consistent provider

The system SHALL NOT route embedding generation to OpenRouter, because OpenRouter
exposes no embeddings endpoint and because embedding the corpus with a different
model than the stored vectors would break similarity search. Embeddings SHALL
continue to use the configured OpenAI embedding model, with the existing offline
mock hash-embedder as their only fallback.

#### Scenario: Embedding request never routes to OpenRouter

- **WHEN** a document or query embedding is requested
- **THEN** the system SHALL use the configured OpenAI embedding model (or the mock
  embedder when unavailable) and SHALL NOT attempt OpenRouter for embeddings

### Requirement: Admin configuration of the OpenRouter fallback

The system SHALL let an administrator, from Admin → AI Settings, enable or disable
the OpenRouter fallback, store an encrypted OpenRouter API key (write-only, never
echoed to the client), configure the OpenRouter chat model chain, and test the
OpenRouter connection. Settings SHALL apply at runtime without a redeploy,
consistent with existing AI settings behavior.

#### Scenario: Admin enables fallback and saves a key

- **WHEN** an admin enables the fallback, enters an OpenRouter API key, and saves
- **THEN** the system SHALL store the key encrypted and begin using OpenRouter as
  the fallback tier on subsequent AI requests without requiring a restart

#### Scenario: API key never returned to the browser

- **WHEN** the AI Settings page loads
- **THEN** the stored OpenRouter API key SHALL NOT be sent to the client; only a
  "configured" indicator SHALL be shown

#### Scenario: Test connection

- **WHEN** an admin clicks "Test connection" for OpenRouter
- **THEN** the system SHALL perform a minimal live request to OpenRouter and
  report success or a human-readable failure reason

#### Scenario: Fallback disabled preserves current behavior

- **WHEN** the fallback is disabled or no OpenRouter key is configured
- **THEN** the provider chain SHALL behave exactly as today (OpenAI → mock) with
  no OpenRouter attempts
