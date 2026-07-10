Yes — I merged your original `CLAUDE.md` with the improvements, while keeping it **compact and high-signal** (still roughly within your 200–300 line target).

I intentionally **did not bloat it** with the project audit workflow. This version focuses only on instructions that should apply to **every Claude Code request**.

# CLAUDE.md

## Project

**UniGPT** — AI-powered university academic copilot with role-based dashboards (**Student / Faculty / Admin**).

Stack:

* Laravel 11
* Inertia 2
* Vue 3
* Vite
* Tailwind
* MySQL

⚠️ Root docs (`README.md`, `PROJECT_STRUCTURE.md`, etc.) are partially outdated.

* Docs mention **Livewire** → incorrect (installed but unused)
* Actual frontend → **Inertia + Vue**
* **AI/RAG is implemented and live** — OpenAI (`gpt-4o`, embeddings `text-embedding-3-small`)
  configured via Admin → Settings (provider reachable). Student chat returns
  RAG-cited answers; faculty AI assistant + AI quiz/assignment generation work.
  (The earlier "mostly unimplemented scaffolding" note is outdated — verify in
  `app/Domain/Chat/*` before assuming a feature is stubbed.)

**Trust code over docs.**

### Recently added feature suite (all live)

Student study/community features layered on the same domain/RBAC/Inertia patterns:

* **AI Study Planner** — `Domain/Academic/StudyPlannerService`, `Student/StudyPlannerController` (`study-planner*` routes). Generates a schedule from deadlines, saves as `Task`s.
* **Learning Analytics** — `Domain/Analytics/LearningAnalyticsService`, `Student/LearningAnalyticsController` (`progress` route). Charts via `components/charts/StatChart.vue` (Chart.js/vue-chartjs).
* **Flashcards** — `Domain/Academic/FlashcardService`, `Student/FlashcardController`; models `FlashcardDeck`/`Flashcard`; SM-2 review; `TeachingAssistantService::generateFlashcards()`.
* **Leaderboard** — `Domain/Analytics/LeaderboardService`, `Student/LeaderboardController`; opt-in via `users.leaderboard_opt_in`/`leaderboard_alias`; XP computed at read time.
* **Discussions** — `Domain/Community/DiscussionService`, `Community/DiscussionController` (shared student+faculty, `discussions.*`), `Admin/DiscussionModerationController`; models `Post`/`PostComment`/`PostReaction`/`PostReport`; a **Section is the group**.
* **OCR notes** — `AIProviderInterface::extractText()` (gpt-4o vision + mock fallback), `Domain/Chat/OcrService`, `NoteController::ocr` (`notes.ocr`).
* **Office-hours booking** — `office_hour_slots` (single-capacity, `booked_by` null = open), `Domain/Academic/OfficeHoursService` (atomic claim via conditional UPDATE → 409 on race; relationship-gated: students book only faculty teaching their sections), `Faculty|Student OfficeHoursController` (`faculty.office-hours.*`, `office-hours.*`), pages `Faculty/OfficeHours.vue` + `Student/OfficeHours.vue`, `NotificationType::OFFICE_HOURS` both ways, booked meetings feed `CalendarService`.
* **ICS calendar export** — `Domain/Academic/IcsExportService` (RFC 5545: escaping, 75-octet folding, all-day vs timed), `Student/CalendarExportController`: authed download `calendar.export` + **signed** sessionless feed `calendar.feed` (`signed` + throttle middleware; URL minted per-user on the Calendar page for Google/Outlook/Apple subscribe).
* **Group study rooms** — section-scoped group chats on the messenger plumbing: `conversations.type` (`direct|group`) + `title`/`section_id`/`created_by`; `Domain/Community/StudyRoomService`, `Student/StudyRoomController` (`study-rooms.*`, student-only), page `Student/StudyRooms.vue` (uses `useConversation().openById`). Chat itself runs on the shared messenger endpoints (participant-based auth + `conversation.{id}` channel work unchanged for groups). ⚠️ Direct-surface guards: `Conversation::betweenOrCreate` is scoped `->direct()` and messenger `overview()` filters `type=direct` — group rooms must never appear as 1:1 threads.
* **Semantic global search (⌘K)** — `Domain/Search/GlobalSearchService` + `SearchController` (`GET /search`, `search.global`, shared auth group). "Knowledge" group = semantic hits via `RetrievalService` over the user's RAG corpus (library docs, own notes, section materials); lexical groups = courses, assignments, discussions, own chat history (deep-linked `?session=&message=`), users (admin only). AppLayout ⌘K palette merges page matches + grouped remote results (350ms debounce, ≥3 chars) with unified keyboard nav.
* **At-risk early warning** — `Domain/Analytics/EarlyWarningService` flags students on 4 signals (attendance <75% after ≥4 sessions, ≥2 missed published-assignment deadlines, class-test average <50%, grade D+/D/D-/F) with `riskLevel` high (≥2 signals) / watch. Surfaced in `FacultyAnalyticsService` report (`atRisk` key, upgraded card in `Faculty/Analytics.vue` with message deep-link) and as a faculty dashboard stat (`countForFaculty`, unique students).
* **Practice quizzes (student self-quizzing)** — `Domain/Academic/PracticeQuizService`, `Student/PracticeQuizController` (`practice.*` routes), models `PracticeQuiz`/`PracticeAttempt`, pages `Student/Practice/Index|Take`. AI generation via `TeachingAssistantService::generateQuiz` restricted to MCQ/true-false; correct answers stay server-side (stripped before render, revealed in grading results); missed questions convert to a flashcard deck (`source: practice`). Generation reuses the `use_ai_chat` + `ai.chat.access` gate.
* **Streaming AI responses (SSE)** — `AIProviderInterface::chatStream()` (OpenAI `stream:true` + mock word-streaming), `RagChatService::answerStream()`, `ChatService::sendMessage(onDelta:)`; endpoints `chat.stream` (student) and `faculty.ai-assistant.stream` share the `StreamsServerSentEvents` controller trait; frontend consumes via `resources/js/lib/sse.js` (`postEventStream`, fetch + CSRF). Events: `delta` → `done` (same payload as the JSON endpoints) or `error`. Non-streaming endpoints kept intact.
* **Personal-corpus RAG ("chat with my materials")** — student notes and file-backed course materials are indexed as hidden **shadow documents** (`documents.source_type` = `note|material`, `source_id`, `owner_id`; global scope `Document::LIBRARY_SCOPE` hides them everywhere except retrieval — RAG code opts out via `Document::allSources()`). `Domain/RAG/Ingestion/PersonalCorpusService` + `SyncNoteToRagJob`/`SyncCourseMaterialToRagJob`; retrieval scope = library-visible ∪ own notes ∪ enrolled/teaching-section materials (`RetrievalService::scopeRetrievable()`). Backfill: `php artisan rag:sync-personal [--user=]` (intentionally not run for all seeded notes — API cost).

New permissions (Community category): `view_discussions`, `post_discussion`, `moderate_discussions`. AI-backed endpoints reuse the `use_ai_chat` + `ai.chat.access` gate. Demo data for all of these is seeded — see `docs/seeder-plan.md`.

---

## Response Style

* Be concise and high-signal
* Avoid repeating obvious context
* Prefer precise analysis over long explanations
* Do not overengineer
* Prioritize maintainability and correctness

---

## Commands

```bash
composer dev
php artisan serve
npm run dev
npm run build

php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=RBACSeeder

php artisan test
./vendor/bin/phpunit tests/Feature/

./vendor/bin/pint

php artisan route:list
php artisan optimize:clear
```

---

## Database

Uses **MySQL** database (`uni_gpt`), not SQLite.

`phpunit.xml` SQLite config is commented out.

Always ensure:

* migrations valid
* foreign keys correct
* indexes added where needed
* schema matches code

---

## Architecture

### Frontend

Root view:

```php
resources/views/app.blade.php
```

Boot file:

```js
resources/js/app.js
```

Controllers return:

```php
Inertia::render('PageName', [...props]);
```

Frontend structure:

* Pages → `resources/js/pages`
* Components → `resources/js/components`
* Layouts → `resources/js/Layouts`

Uses:

* Ziggy for route helper
* vue-toastification for toasts

⚠️ Inertia page names and route names are string-based and fail silently.

---

## Routing

Only active route file:

```php
routes/web.php
```

These are NOT registered:

* `routes/student.php`
* `routes/faculty.php`
* `routes/admin.php`

Editing them does nothing unless registered in `bootstrap/app.php`.

Route groups:

* Public
* Authenticated:

  * `role:student`
  * `role:faculty`
  * `role:admin`

Routes must be:

* RESTful
* grouped
* named
* consistent

Avoid:

```php
/getUsers
/deleteCourse
```

Prefer:

```php
/admin/users
/admin/courses
```

---

## Authentication / RBAC

Custom auth system.

Middleware aliases in:

```php
bootstrap/app.php
```

Uses:

* `RoleMiddleware`
* `PermissionMiddleware`

Rules:

* guests → `/login`
* inactive users → logout

Login requires:

* credentials
* selected role (`student|faculty|admin`)
* role validation
* rate limiting

RBAC:

* User ↔ Role (many-to-many)
* Role ↔ Permission (many-to-many)

Pivot supports `expires_at`.

---

## User Model

NOT in `app/Models`.

Actual location:

```php
app/Domain/User/Models/User.php
```

Namespace:

```php
App\Domain\User\Models\User
```

Important methods:

* `hasRole`
* `hasPermission`
* `assignRole`
* `syncRoles`
* `isStudent`
* `isFaculty`
* `isAdmin`
* `getPrimaryRole`
* `getDashboardRoute`

---

## Known Gotchas

### Role Slug Mismatch

Enum may return uppercase (`ADMIN`) but DB stores lowercase (`admin`).

Source of truth = lowercase DB slug.

Never rely on uppercase role values.

---

### Shared Auth User (resolved)

`HandleInertiaRequests` now shares the **real authenticated user** (id, name, roles,
permissions, department, dashboard route) — not `null`. Vue can read `auth.user`
via `usePage()`/`usePermissions()`. (Historical note: an earlier build shared `null`.)

---

## Task Mode

Before making code changes, determine task type:

* Bug Fix
* Refactor
* New Feature
* Architecture Audit
* Database Change

Workflow:

1. Understand existing implementation
2. Identify dependencies
3. Detect blast radius
4. Propose plan
5. Execute safely

Never code before understanding context.

---

## Search First Policy

Before modifying logic, search for all related code.

Search:

* routes
* controllers
* services
* actions
* models
* relationships
* Vue pages
* migrations
* tests
* configs

Never assume a file is isolated.

Large systems often hide indirect dependencies.

---

# Rule 0 — Propagate Every Change (MOST IMPORTANT)

A refactor is incomplete until **all usage sites** are updated.

Changing any:

* variable
* method
* class
* route
* enum
* DB column
* prop
* page name
* config key
* relationship

You MUST:

### 1. Search

```bash
grep -rn "oldName" app/ resources/ routes/ database/ config/ tests/
```

### 2. Update ALL usages

Backend:

* controllers
* services
* repositories
* actions
* DTOs
* requests
* policies
* models
* middleware

Frontend:

* Vue props
* route()
* composables
* stores
* forms
* emits
* watchers
* Inertia page names

Database:

* migrations
* factories
* seeders
* validation rules

Tests:

* unit
* feature
* mocks

### 3. Re-search

Old symbol must be gone.

### 4. Verify

Use verification proportionally.

Small change:

```bash
./vendor/bin/pint
php artisan test --filter=RelevantTest
```

Large change:

```bash
php artisan route:list
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
npm run build
```

Never leave partial refactors.

---

## Legacy Preservation Rule

This project is partially migrated from legacy code.

Before deleting or rewriting logic, verify whether code exists for:

* backward compatibility
* unfinished migration
* hidden business rules

Prefer refactor over rewrite.

Do not remove old code unless confirmed unused.

---

## Implementation Awareness

Many modules are scaffolding only.

Presence of:

* class
* interface
* migration
* Vue page
* service

DOES NOT mean feature is complete.

Always verify end-to-end:

Database
→ Backend logic
→ API / Controller
→ Frontend integration
→ User workflow

---

## Feature Status Labels

Use during audits:

* COMPLETE
* PARTIAL
* NOT_STARTED
* BLOCKED

A feature is COMPLETE only if user can use it end-to-end.

---

## Naming

Use meaningful names only.

Variables:

```php
camelCase
```

Methods:

```php
verbFirstCamelCase
```

Classes:

```php
PascalCase
```

Bad:

```php
$a
$x
data()
handle()
```

Good:

```php
$totalPrice
calculateTotalAmount()
```

---

## Typing

Always type:

* params
* returns
* nullable values
* collections/models when possible

Prefer:

```php
declare(strict_types=1);
```

Example:

```php
public function assignRole(User $user, string $role): bool
```

---

## Named Arguments

Use named args for multi-parameter calls.

Good:

```php
calculateTotal(
    price: $price,
    quantity: $quantity
);
```

---

## Separation of Concerns

Controllers:

* validation
* orchestration
* response only

Business logic belongs in:

* Services
* Domain layer
* Actions

Vue:

* UI only
* local state only

Avoid business logic inside Vue.

---

## Database Access

Prefer:

* Eloquent
* relationships
* scopes
* query builder

Avoid:

* raw SQL
* excessive `DB::raw`
* manual joins if relationships suffice

Always prevent N+1.

Example:

```php
User::with(['roles', 'permissions'])->get();
```

---

## Models

Models should define:

* `$fillable` / `$guarded`
* `$casts`
* relationships
* scopes

Example:

```php
protected $casts = [
    'is_active' => 'boolean',
];
```

---

## Refactor Priority

1. Database design
2. Raw SQL removal
3. Naming
4. Type safety
5. Fat controllers
6. Vue logic leakage
7. Routes
8. Relationships
9. Migrations
10. Docs

---

## Principles

Always follow:

* SOLID
* DRY
* KISS
* Clean Architecture
* Separation of Concerns

Avoid unnecessary abstraction.

Use DTOs / repositories only when complexity justifies them.

---

## Workflow (Every Task)

1. Analyze domain + dependencies
2. Explain problem and risks
3. Refactor safely
4. Propagate all changes (Rule 0)
5. Verify changes
6. Confirm behavior unchanged

