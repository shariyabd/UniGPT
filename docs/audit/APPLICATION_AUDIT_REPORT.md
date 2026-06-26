# UniGPT — Application Audit Report

> **Single source of truth** for what UniGPT actually implements today, and how its
> Landing page and Presentation deck compare against that reality.
> Method: static code-trace (Vue page → route → controller → domain service → migration),
> evidence-backed status labels, cross-checked against `.env` / `config/*`.
> Date: 2026-06-26 · Branch: `dev` · Stack: Laravel 11 · Inertia 2 · Vue 3 · MySQL 8.
> **No application code was modified — analysis only.**

---

## 0. How to read this report

Every feature is labelled:

| Label | Meaning |
|---|---|
| **COMPLETE** | User can use it end-to-end with real persistence/enforcement. |
| **PARTIAL** | Some layers exist; the flow is broken, mocked, or config-gated. |
| **NOT_STARTED** | Static stub / placeholder only. |
| **BLOCKED** | Depends on a missing/broken dependency. |

**Headline:** UniGPT is a **genuinely implemented system, not scaffolding.** All three
portals are prop-driven with real persistence and real RBAC enforcement. The RAG pipeline
(upload → approve → chunk → embed → retrieve → cite) is wired end-to-end. The defects that
exist are **few and minor** — the most material is a configuration/marketing gap, not a
broken feature: **the committed `.env` runs the deterministic *mock* AI provider, and the
Landing/Presentation overstate one model name and one chunk size.**

---

## 1. System Overview

### 1.1 Architecture (verified)

- **Frontend:** Vue 3 + Inertia 2, Tailwind, Vite, Ziggy routing. Root view
  `resources/views/app.blade.php`, pages in `resources/js/pages/{Student,Faculty,Admin,...}`.
- **Backend:** Laravel 11, Domain-Driven layout under `app/Domain/{Academic,Analytics,Chat,Notification,RAG,User}`.
  Controllers in `app/Http/Controllers/{Student,Faculty,Admin,Messenger,Auth}`.
- **Routing:** single active file `routes/web.php` (≈349 lines). `routes/{student,faculty,admin}.php` are **not registered** (editing them is a no-op).
- **Auth/RBAC:** custom. `RoleMiddleware` / `PermissionMiddleware` (aliases in `bootstrap/app.php`).
  User↔Role and Role↔Permission many-to-many; `role_user` pivot carries **`expires_at`** and the relation filters expired grants at query time (`User.php:303-312`).
- **AI:** provider behind an interface (`AIProviderManager` → `OpenAiProvider` | `MockProvider`).
  RAG is **MySQL-native** — embeddings stored as JSON float arrays in `longtext`, cosine similarity computed in PHP. No external vector DB, no LLM SDK.
- **Realtime:** `BROADCAST_CONNECTION=ably`, `QUEUE_CONNECTION=database`. Messaging persists first, then broadcasts, with a polling fallback.

### 1.2 Verified runtime configuration (committed `.env`)

| Key | Value | Implication |
|---|---|---|
| `AI_DEFAULT_PROVIDER` | **`mock`** | ⚠️ Out-of-the-box, **all AI runs on the deterministic MockProvider**, not GPT-4o. Admin → AI Settings can flip it at runtime. |
| `OPENAI_API_KEY` | present in **local `.env` only** | ✅ Corrected: `.env` is gitignored/untracked, `.env.example` ships an empty `OPENAI_API_KEY=`, and git history contains no `sk-` key. The key was **never committed**. (Earlier draft overstated this as a committed-secret leak.) |
| `OPENAI_MODEL` | `gpt-4o` | Chat model when provider = openai. ✅ matches deck/landing. |
| `EMBEDDING_MODEL` | `text-embedding-3-small` | ✅ Actual embedding model. ❌ Landing/FAQ/TechStack claim `text-embedding-3-large`. |
| `config/ai.php:39` default | `text-embedding-ada-002` | ⚠️ Config default drifts from the real model; only safe because `.env` overrides it. |
| `RAG_CHUNK_SIZE` / overlap | `150` / `40` tokens | ❌ Deck slide 07 claims **"512-token semantic units."** Config comment explicitly notes 512 "swallowed each short document whole." |
| `RAG_TOP_K` / threshold | `6` / `0.35` | ✅ matches engineering HTML. |
| `EMBEDDING_DIMENSIONS` | `1536` | ✅ consistent (3-small = 1536 dims). |

---

## 2. Student Portal — Feature Status

Source: `resources/js/pages/Student/*`, `app/Http/Controllers/{Student,Messenger}/*`, `app/Domain/{Chat,RAG}/*`.

| Feature | Status | Evidence | Notes |
|---|---|---|---|
| Dashboard | COMPLETE | `StudentDashboardController.php:37-81` | Real Eloquent stats; read-only nav. |
| AI Chat (RAG) | COMPLETE | `ChatController.php:61-90`, `ChatService.php:29-96` | Full RAG pipeline + persistence — see §5. |
| Archived Chats | COMPLETE | `ChatController.php:152-159` | Unarchive/delete wired. |
| Saved Answers | COMPLETE | `SavedAnswerController.php:38-103` | CRUD, folders, notes, star, view tracking. |
| Materials | COMPLETE | `StudentDashboardController.php:260-280` | Per-item completion via `material_completions` pivot. |
| Faculty directory | COMPLETE | `FacultyDirectoryController.php:32-83` | Real instructors from enrollment. |
| Messages (student↔faculty DM) | COMPLETE | `Messenger/MessageController.php:39-211`, `web.php:82-87` | **Live** persisted + broadcast DM. Stale "Upcoming/skeleton" comments — see §7. |
| Documents (library) | COMPLETE | `StudentDashboardController.php:83-106` | Server-filtered library, download/preview. |
| Documents — bookmark | **PARTIAL** | `Documents.vue:228-235` | Toggles **local state only**; no student persist route (only admin-scoped `web.php:274`). Bookmarks don't survive reload despite `document_bookmarks` table existing. |
| My Documents (submissions) | COMPLETE | `web.php:120-127` | Upload/edit/delete into admin approval queue. |
| Roadmap | COMPLETE | `StudentDashboardController.php:396-450` | Built from real enrollment graph. |
| Attendance | COMPLETE | `StudentDashboardController.php:118-134` | Real per-course rates. |
| Transcript | COMPLETE | `StudentDashboardController.php:136-139` | Real CGPA/grades. |
| Exams | COMPLETE | `StudentDashboardController.php:141-144` | Section-scoped, display-only. |
| Calendar | COMPLETE | `StudentDashboardController.php:146-158` | Merges assignments+exams+tasks; inline task CRUD. |
| Registration | COMPLETE | `RegistrationController.php`, `web.php:138-140` | Register/drop offered sections. |
| Assignments | COMPLETE | `AssignmentController.php:30-96` | Submit/resubmit, file upload, faculty notified, lock on grade. |
| Class Tests (proctored quiz) | COMPLETE | `ClassTestController.php:26-167` | Timer, fullscreen + violation tracking, auto-grade, instant result. |
| Notes | COMPLETE | `NoteController.php:15-56` | Owner-scoped CRUD + pin. |
| Tasks | COMPLETE | `TaskController.php:16-77` | Owner-scoped CRUD + toggle. |
| Profile / Settings | COMPLETE | `StudentDashboardController.php:170-200` | Persists; settings feed chat default language. |

**No NOT_STARTED / BLOCKED student pages.** Only defect: bookmark persistence (PARTIAL).

---

## 3. Faculty Portal — Feature Status

Source: `resources/js/pages/Faculty/*`, `app/Http/Controllers/Faculty/*`, `app/Domain/*`.
*(No faculty Vue page contains mock/hardcoded data — verified by grep.)*

| Feature | Status | Evidence | Notes |
|---|---|---|---|
| Dashboard | COMPLETE | `FacultyDashboardController.php:27-76` | Real stats + activity feed. |
| Courses / CourseDetail | COMPLETE | `CourseController.php:23-39` | Read-only (course CRUD is admin-owned by design). |
| Course Materials (CRUD) | COMPLETE | `CourseManagementService.php:119-160` | Real file persistence, scoped to own section. |
| Students directory | COMPLETE | `StudentDirectoryController.php:59-85` | Real roster, term-scoped. |
| Messages (faculty↔student) | COMPLETE | `Messenger/MessageController.php` | Live persisted + Ably broadcast + poll fallback. |
| Grading | COMPLETE | `GradingController.php:28-75` | Grade persistence + student notification + file download. |
| Grading — AI feedback | COMPLETE *(provider-gated)* | `GradingController.php:97-115`, `TeachingAssistantService.php:149-185` | Real provider call + deterministic fallback; faculty edits before save. |
| Attendance | COMPLETE | `AttendanceController.php:23-70` | Section-scoped roster + persistence. |
| Exams | COMPLETE *(read-only)* | `FacultyDashboardController::exams:22-25` | View only; create/update is admin — by design. |
| Class Tests (author/publish/grade) | COMPLETE | `Faculty/ClassTestController.php`, `ClassTestService.php` | Server-authoritative auto-grading + proctoring. |
| Class Tests — AI generation | COMPLETE *(provider-gated)* | `ClassTestController::generate:49-54` | MCQ + True/False only (auto-gradable). |
| Analytics | COMPLETE | `AnalyticsController.php:19-29` | Real aggregates, no random values. |
| AI Assistant (chat) | COMPLETE *(provider-gated)* | `AIAssistantController.php:59-80` | Same RAG engine as student chat + full session CRUD. |
| AI Assistant — quiz/assignment gen | COMPLETE *(provider-gated)* | `AIAssistantController.php:143-151` | Real `TeachingAssistantService` generation. |
| AI Assistant — publish as assignment / class test | COMPLETE | `AIAssistantController.php:157-249` | One-click publish creates real `Assignment`/`ClassTest` + notifications. |
| Archived Chats | COMPLETE | `AIAssistantController::archived:134-141` | Real. |

**Provider caveat:** every "provider-gated" feature is **code-COMPLETE** but, with committed
`.env` (`mock`), runs through `MockProvider` — which self-labels output as *"Demo response
generated by the built-in mock AI provider."* Live GPT-4o is implemented and reachable; it is
simply not the active provider until admin flips it.

---

## 4. Admin Portal — Feature Status

Source: `resources/js/pages/Admin/*`, `app/Http/Controllers/Admin/*`.

| Feature | Status | Evidence | Notes |
|---|---|---|---|
| Dashboard | COMPLETE | `AdminDashboardController.php:33-127` | Real counts. Minor dead UI: `doc.urgent=false`, `doc.preview=''` (`Dashboard.vue:87-88`); "Online Now" label says 5 min but query uses 15. |
| Analytics | COMPLETE | `AnalyticsService.php:21-94` | Real aggregates (uses `DB::raw`/manual joins — repo-rule nit, functionally correct). |
| UserManagement | COMPLETE | `UserManagementController.php:29-150` | Server filter/paginate, real `syncRoles`, protected-admin guard, FK-safe delete. |
| RolePermissions (RBAC) | COMPLETE | `RoleController.php:48-63` | Real `permissions()->sync()`. See §6. |
| Departments | COMPLETE | `DepartmentController.php:22-74` | Referential-guard on delete. |
| Courses + Sections | COMPLETE | `SectionController.php:72-159` | Capacity-enforced, per-student notifications, dept/faculty invariant. |
| Terms | COMPLETE | `TermService.php:62-80` | Real transactional end-of-term rollover (not a stub). |
| Announcements | COMPLETE | `AnnouncementController.php:53-84` | One notification row per recipient. |
| DocumentUpload | COMPLETE | `DocumentService.php:33-66` | Real multipart upload, hashed file, PENDING status. |
| DocumentLibrary | COMPLETE | `DocumentController.php` | Server-driven list, real file stream. |
| Approvals | COMPLETE | `DocumentService.php:124-138` | `approve()` dispatches `ProcessDocumentJob` → feeds RAG. |
| Exams | COMPLETE | `ExamService.php:66-100` | Per-section scheduling + notifications. |
| AISettings | **PARTIAL** | `SettingsController.php:103-114`, `OpenAiProvider.php:76-79` | Persist/load real & **encrypted**; **`test()` only checks key presence — never calls OpenAI.** "Provider reachable" is misleading. |
| AiUsage | COMPLETE | `AiUsageService.php:27-140` | Real token aggregation from `chat_messages.tokens`; real block/unblock. |
| SystemMonitor | COMPLETE | `MonitorController.php:28-183` | Genuine OS probes (load, memory, disk, uptime, live DB/queue/cache). |

---

## 5. RAG / Chat — Deep Dive (the core differentiator)

**Verdict: real and end-to-end.** Per message (`ChatService::sendMessage` → `RagChatService::answer`):

1. **Intent gate** — `QueryClassifier` peels greetings/thanks → canned reply at zero API cost.
2. **Retrieval** — `RetrievalService::retrieve`: embed query (cached) → load `Embedding` rows scoped `approved()->visibleTo($user)` → **cosine-rank in PHP** (threshold `0.35`, top-K `6`, single-best fallback). Cached by corpus version + visibility scope.
3. **Citations** — `CitationService::buildContext` builds `[1] Title (p.N): chunk`; confidence = best similarity score.
4. **Grounded prompt** — `RagChatService::buildMessages`: mode prompt + user profile (role/dept/semester/courses for acronym resolution) + "cite by bracket / don't invent citations when no context" branch.
5. **LLM call** — `OpenAiProvider` POST `/chat/completions` (gpt-4o) **or** `MockProvider`.
6. **Persistence** — assistant message saved with confidence/level/sources/follow-ups/model/tokens; citations written to `message_citations`.

**Ingestion (real):** `approve()` → `ProcessDocumentJob` → extract (PDF/DOCX) → chunk (150 tok / 40 overlap) → `EmbeddingService::embedDocument` (batched, `text-embedding-3-small`, 1536-dim) → store `Embedding` → bump `CorpusVersion` (invalidates retrieval cache). Idempotent, SHA-256 dedup.

**Modes & languages:** academic / exam-prep / assignment / research modes; English + Bangla.
**Access control:** gated by `permission:use_ai_chat` + `EnsureAiChatAccess` (admin can block a user).

**Engineering reference:** `unigpt-rag-pipeline.html` (root) is a standalone technical
walkthrough mapping every pipeline box to a real file/table/column — accurate to the code
(8 tables, 1536 dims, top-K 6, threshold 0.35, temp 0.3, gpt-4o).

**Scalability flag (not a bug):** brute-force O(n) PHP cosine over JSON-in-`longtext`. Fine
for demo scale; needs a vector index (pgvector / managed) for large corpora — the deck's
13c slide and the HTML are both candid about this.

---

## 6. RBAC — Deep Dive

**Real, not stubbed.** Permission writes via `$role->permissions()->sync()` to `permission_role`;
user grants via `syncRoles`. Enforcement via `RoleMiddleware`/`PermissionMiddleware` (force-logout
inactive users, log unauthorized attempts, 403/redirect) applied across `web.php:265-344`.
`role_user` pivot has `assigned_at`, `assigned_by`, **`expires_at`** (expired grants filtered at
query time). Admin granted every `PermissionEnum` case (`RBACSeeder.php:128-129`) — matching the
locked admin column in the UI.

**One dormant bug:** `app/Models/Role.php:31-36` `users()` references `App\Models\User` (nonexistent —
real User is `App\Domain\User\Models\User`). Would fatal if called; **zero callers today.**

---

## 7. Cross-Cutting Findings & Defects (consolidated)

| # | Severity | Finding | Location |
|---|---|---|---|
| 1 | ~~High~~ → **Resolved/Non-issue** | API key is **not** committed — `.env` gitignored, `.env.example` empty, no key in git history. Verified during Batch 3. | `.env.example:82`, `.gitignore:9` |
| 2 | **High (perception)** | AI runs on **mock** provider out-of-the-box | `.env:77` `AI_DEFAULT_PROVIDER=mock` |
| 3 | Medium | AI Settings "Test connection" never calls OpenAI | `SettingsController.php:103-114` |
| 4 | Medium | Student document bookmark not persisted (local state only) | `Documents.vue:228-235` |
| 5 | Low | Embedding-model config default drift (`ada-002`) | `config/ai.php:39` |
| 6 | Low | `Role::users()` wrong namespace (dormant) | `app/Models/Role.php:31-36` |
| 7 | Low | Stale "Upcoming/skeleton" comments for messaging (feature is live) | `web.php:171,186-189`, `StudentDirectoryController.php:24-26`, `MEMORY.md` |
| 8 | Low | Dashboard dead UI fields; "Online Now" label/query mismatch | `Admin/Dashboard.vue:87-88`, `AdminDashboardController.php:33` |
| 9 | Info | No native vector index (brute-force PHP cosine) | `RetrievalService.php:71-111` |
| 10 | Info | Async embedding needs a running queue worker (`QUEUE_CONNECTION=database`) | `DocumentService.php:136` |

---

# PHASE 2 — Landing Page Verification

Landing page = `resources/js/pages/Landing.vue` + 17 section components. Claims diffed against §2–§6.

### ✅ Implemented & Correct (landing is accurate)

- **Grounded RAG assistant** — citations, confidence score, suggested follow-ups, chat modes, saved answers, session history. ✅ All real (§5).
- **Document knowledge base** — upload → review → approve → auto chunk/embed/searchable. ✅ (§4, §5).
- **Role-based dashboards** — one login, three tailored experiences. ✅ (§2–§4).
- **Granular RBAC** — fine-grained permissions, **time-limited grants**, gated + logged. ✅ (§6).
- **Teaching automation** — AI quizzes, assignments, rubrics, drafted grading feedback. ✅ (§3).
- **Timed quizzes & class tests** — AI-or-hand-written, live countdown, instant auto-grade. ✅ (§3).
- **Attendance & analytics**, **transcripts/GPA/CGPA**, **registration & rostering**, **exams/calendar/notes/tasks**. ✅ (§2–§4).
- **Real-time messaging** — student↔faculty, presence, unread counts, separate from AI tutor. ✅ live (§2–§3).
- **Permission-scoped retrieval** — "answers never leak." ✅ (`visibleTo` scope, §5).
- **Pluggable providers + built-in mock provider.** ✅ (§5).
- **Tech stack** (Laravel 11, Vue 3, Inertia 2, Vite, Tailwind, MySQL, Ziggy). ✅.
- **Upcoming section** correctly labels streaming / Telegram-WhatsApp / digital library / voice / predictive analytics as roadmap, and correctly notes messaging **has shipped**. ✅.

### ❌ Incorrect / Misleading

| Claim | Reality | Fix |
|---|---|---|
| **"OpenAI gpt-4o + `text-embedding-3-large`"** (AiEngine, FAQ, TechStack) | Actual embedding model is **`text-embedding-3-small`** (`.env:84`) | Change to `text-embedding-3-small` (or set env to 3-large if intended). |
| **"⚡ Answer in ~1.2s"** / Stats "1.2s average grounded answer time" | No measured latency anywhere; mock provider is instant, real gpt-4o is several seconds | Soften to "in seconds" or mark illustrative (Stats already disclaims; Hero chip does not). |
| Hero implies a **live polished GPT answer** | Out-of-the-box provider is **mock** | Not a page bug, but demo environments will show mock answers unless provider is flipped. |
| **"typing indicators"** (Features, Roles) | Presence + unread confirmed; live typing indicator not verified in trace | Verify the typing-indicator wire exists before claiming it. |
| Stats: **12h/week saved, 3, 100%** | Disclaimed as "illustrative" ✅ — acceptable, but Hero's standalone numbers are not disclaimed | Keep the disclaimer pattern consistent across Hero + Stats. |

### ➕ Missing (implemented but under-sold on the landing page)

- **Proctored exam integrity** (fullscreen enforcement, tab-switch detection, auto-disqualify, per-student timer, server-authoritative grading) — a strong differentiator, only lightly implied. The **deck** showcases it (slide 13); the landing barely does.
- **MySQL-native RAG / "0 external AI dependencies"** — a genuine architectural selling point (no vector DB, no LLM SDK), present in the deck (slide 16) but **absent from the landing**.
- **Multi-language (English + Bangla)** chat — real, not mentioned on landing.
- **End-of-term rollover automation** (archive sections, complete enrollments, promote term) — real admin power-feature, unmentioned.
- **AI usage governance** (per-user token metering, block/unblock) — real, unmentioned on landing.
- **System monitor** (live OS/DB/queue health) — real, unmentioned.

### 💡 Improvement Suggestions (landing)

1. Fix the `3-large` → `3-small` model claim (accuracy/credibility).
2. Add a **proctoring** feature card and a **"MySQL-native, zero external AI deps"** trust badge.
3. Make latency claims honest (remove fixed "1.2s" or label illustrative everywhere).
4. Mention **Bangla** support — strong differentiator for the target market.
5. Consider a "Governed AI" trust block (approve-before-index, token metering, audit log).

---

# PHASE 3 — Presentation Content Verification

Deck = `resources/js/presentation/deck.js` (23 slides, data-driven) rendered by `Presentation.vue`.
Claims diffed against §2–§6.

### ✅ Correctly Presented

- Problem → stakes → solution framing matches the real product.
- **Architecture (05)** — DDD, Laravel+MySQL, "no external vector DB, no LLM SDK." ✅.
- **RBAC (06)** — "3 roles, 40+ permissions, expiring assignments." ✅ (§6).
- **RAG pipeline (07)** — upload→chunk→embed→retrieve→cite, MySQL-native. ✅ *(except chunk size, below)*.
- **Grounded answers (08)** — inline citations, confidence High/Med/Low from similarity, multi-language EN/BN. ✅ (§5).
- **Per-role feature grids (06, 08b, 09, 11, 14)** — match implemented features. ✅.
- **Proctored tests (13)** — fullscreen, auto-disqualify, per-student timer, server-authoritative grading. ✅ (§2–§3).
- **Messaging (13b)** + **honest realtime/Ably trade-off (13c)** — accurate and unusually candid. ✅.
- **Governance (15)** — approve-before-index, token metering, audit log. ✅ (§4).
- **Pluggable AI (17)** — mock / OpenAI / future providers. ✅.
- **Demo (18)** — 24 real captured screenshots across all three portals. ✅.
- **Metrics (16)** — 3 dashboards / 40+ permissions / 100% core workflows / 0 external AI deps. ✅.

### ❌ Incorrect / Inaccurate

| Slide | Claim | Reality | Fix |
|---|---|---|---|
| **07 RAG pipeline** | **"512-token semantic units"** | Actual `RAG_CHUNK_SIZE=150`; config comment says 512 "swallowed each short document whole" | Change to **"~150-token overlapping chunks."** |
| 18 Demo (code comment) | "Six core screens per role" | Actual 8 student / 7 faculty / 9 admin = 24 | Cosmetic comment only; harmless. |
| Deck vs HTML | Deck says generic "OpenAI"; HTML pins gpt-4o @ temp 0.3 | Consistent with code | No action; deck is just less specific. |

### ➕ Missing from the Presentation

- **The honest "AI runs on mock by default" framing** — the deck (slide 17) sells "demo today, production tomorrow" well, but never states the committed default is mock. For a technical/investor audience this is a credibility *strength* if owned explicitly.
- **Security/permissions depth beyond retrieval scoping** — force-logout of inactive users, per-action audit logging is mentioned (15) but the *enforcement* story (every route guarded) could be a slide.
- **End-of-term rollover & registration lifecycle** — real and impressive, only a bullet in the admin grid.

### Content Gaps

- No slide quantifies **scale tested** (seed data shows 40–50 students/section, dense enrollment) — a "demonstrable at realistic scale" proof point is available but unused.
- No explicit **"what's NOT done yet"** beyond roadmap — the deck is honest about Ably limits (13c) but could preempt the "is the AI real?" question head-on.

---

## Appendix — Stat/claim consistency table (Landing vs Deck vs Code)

| Item | Landing | Deck | Code/Config | Verdict |
|---|---|---|---|---|
| Chat model | gpt-4o | OpenAI (gpt-4o in HTML) | `gpt-4o` | ✅ |
| Embedding model | **3-large** | (not stated) | **3-small** | ❌ landing wrong |
| Chunk size | (not stated) | **512 tokens** | **150 tokens** | ❌ deck wrong |
| Top-K / threshold | (not stated) | top-K (07) | 6 / 0.35 | ✅ |
| Vector store | "MySQL · Data + vectors" | MySQL-native | JSON in `longtext` | ✅ |
| Roles / permissions | 3 roles | 3 roles, 40+ perms | RBACSeeder grants all | ✅ |
| Answer latency | **~1.2s** | (none) | unmeasured | ⚠️ unsubstantiated |
| Active AI provider | implies live | "demo today" (17) | **mock** in `.env` | ⚠️ needs flip for live |

---

*End of Application Audit Report. Companion: `PRESENTATION_IMPROVEMENT_PLAN.md`.*
