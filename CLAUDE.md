Yes — I merged your original `CLAUDE.md` with the improvements, while keeping it **compact and high-signal** (still roughly within your 200–300 line target).

I intentionally **did not bloat it** with the project audit workflow. This version focuses only on instructions that should apply to **every Claude Code request**.

# CLAUDE.md

## Project

**UniNexus** — AI-powered university academic copilot with role-based dashboards (**Student / Faculty / Admin**).

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
* **Provider fallback layer (live)** — chat resolves through a `FallbackProvider`
  chain (Admin → AI Settings picks OpenAI **or** OpenRouter as primary; the other
  backs it up; `openrouter/auto` is the last-resort model; Mock is the terminal
  never-fail link). Embeddings resolve **separately** — OpenRouter has no embeddings
  endpoint — via `FallbackEmbeddingProvider`: OpenAI or **Jina** (`jina-embeddings-v3`,
  free/multilingual), with optional dual-embed so RAG survives the primary embeddings
  API dying at midnight. Vectors are **model-tagged** and `RetrievalService` filters to
  the active model, so mixed-provider vectors never cross-compare; switching the
  embedding provider auto-dispatches `ReembedCorpusJob` (`rag:reembed`). Config seeds
  from `.env` via `AISettingsSeeder` so `migrate:fresh --seed` boots a working,
  resilient setup. Infra: `app/Infrastructure/AI/{OpenRouterProvider,JinaEmbeddingProvider,FallbackProvider,FallbackEmbeddingProvider,Concerns/ParsesOpenAiChatWire}`. Tests: `OpenRouterFallbackTest`.

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
* **Agentic chat tools (function calling)** — `Domain/Chat/Tools/` (`ChatToolRegistry` + 8 tools: deadlines, courses, office-hour list/book/cancel, practice-quiz gen, flashcard-deck gen, task create). Providers accept `options['tools']` and return `ChatResult::$toolCalls` (OpenAI parses `tool_calls` incl. streamed fragments; MockProvider keyword-routes so it works keylessly). Agent loop in `RagChatService::respond()` (max 3 rounds, then tools withdrawn; **student-only** — faculty assistant unchanged). Executions run through the real domain services (RBAC/atomic-claim rules bind AI actions; failures become error results, never exceptions). Trail persisted in `chat_messages.tool_activity`, streamed as `tool_start`/`tool_result` SSE events, rendered above the answer in `Student/Chat.vue`. **Agent/Answers-only mode switch**: segmented control ABOVE the composer in Chat.vue (not the old small pill); sends `agent` bool (default true) → `withTools` through ChatService/RagChatService; OFF = answers-only, tools never offered (server-enforced). Mode-aware UI: welcome-card example prompts swap per mode (`buildAgentFollowUps` vs `buildWelcomeFollowUps` via `setAgentMode`), composer placeholder + hint line change, violet ring on the input in Agent mode; acted replies carry an "⚡ Agent" badge. Tests: `ChatToolsTest`.
* **Submission similarity screening (plagiarism signal)** — `Domain/Academic/SubmissionSimilarityService` + `ScreenSubmissionSimilarityJob` (dispatched from `SubmissionService::submit`, queue-async). Submission text (content + `DocumentTextExtractor` file text) is chunked (`ChunkingService`) and embedded (`EmbeddingService`, works keylessly via MockProvider hash vectors); chunk vectors persist in `submission_embeddings`, flagged pairs (best chunk cosine ≥ `rag.submission_screening.flag_threshold`, default 0.82) in `submission_similarities` (**both directions**, resubmission wipes + recomputes). Faculty-only surface: `GradingService::attachSimilarity()` adds `similarity` to each grading-payload submission → warning badge in list + excerpt-pair panel in the grading modal (`Faculty/Grading.vue`). Assignments are section-scoped so comparisons stay within one roster. A signal for human review, not a verdict. Tests: `SubmissionSimilarityTest`.
* **Concept mastery map + adaptive review** — `Domain/Analytics/ConceptMasteryService` (deterministic, no AI calls): merges per-topic signal from practice-quiz attempts (`practice_quizzes.topic`), flashcard SM-2 state (deck title; "Review: X" folds into X; unreviewed decks = no signal; learned = repetitions ≥2 ∧ interval ≥6d) and submitted class-test scores (test title). Blend weights classTests 0.5 / practice 0.35 / flashcards 0.15, renormalized over available sources; weak = mastery <60, sorted weakest-first. Served as `conceptMastery` prop on the `progress` route; `Student/LearningAnalytics.vue` renders a tier-colored tile grid with one-click "Practice this" / "Make flashcards" buttons that POST the topic to the existing `practice.generate` / `flashcards.generate` routes (difficulty eases below 40%). Tests: `ConceptMasteryTest`.
* **Email digests & deadline nudges** — `Domain/Notification/EmailDigestService` (weekly payload: deadlines ≤7d via StudyPlannerService, grades posted ≤7d, booked office hours, due-flashcard count; returns null when empty) + queued mailables `WeeklyDigestMail`/`AssignmentDueMail` with Blade views under `resources/views/emails/` (shared `layout`). Commands: `digests:send-weekly` (chunked over active students, scheduled Mon 07:00 — ~35s over the 3.4k seeded students, fine for cron) and `assignments:remind` extended to email alongside its in-app nudge (existing per-student-per-assignment dedupe gates both). Opt-out: `preferences.email_digest` (default true; `wantsEmails()`), toggle in Student Settings (`UpdateSettingsRequest` + Settings.vue). SMTP comes from the admin Email Settings overlay (`MailConfigServiceProvider`). Tests: `EmailDigestTest`.
* **AI-assisted rubric grading (human-in-the-loop)** — `TeachingAssistantService::draftRubricGrade()` grades the submission text (content + file text via the shared `Domain/Academic/SubmissionTextService`, also now used by similarity screening) against the assignment rubric: per-criterion scores clamped to each max (names matched case-insensitively), suggested overall grade, feedback/strengths/improvements; keyless fallback = labelled heuristic (`source: ai|heuristic`). Endpoint `faculty.submissions.draft-grade` (POST, `grade_assignment`, course-manage Gate); Grading.vue "Draft grade with AI" button prefills rubric inputs (+ italic per-criterion justifications + heuristic warning), grade and feedback — faculty edit then save; nothing auto-releases. Also fixed: stored rubric rows use key `criterion` but the UI expects `name` — `GradingService::presentAssignment` now normalizes. Tests: `AiGradeDraftTest`.
* **Anonymous mid-semester course feedback** — `course_feedback` table (unique per section+student; ⚠️ user_id exists ONLY for dedupe/edit, never exposed) + `sections.feedback_open`. `Domain/Academic/CourseFeedbackService`: faculty toggle per-section window (opening notifies roster), students submit/revise one rating(1-5)+comment while open; faculty results (avg, distribution, comments) are withheld below `MIN_RESPONSES=3` and comments come back shuffled with no timestamps. AI theme summary via `TeachingAssistantService::summarizeFeedback()` (endpoint `faculty.course-feedback.summarize`, gated `use_ai_chat`+`ai.chat.access`; heuristic fallback). ⚠️ Format-spec lives in the SYSTEM message — a valid-JSON template in the user prompt gets echoed by MockProvider and mis-parsed by tryJson as a real answer. Routes `course-feedback*` (student) + `faculty.course-feedback*`; pages `Student|Faculty/CourseFeedback.vue`; nav links in AppLayout. Tests: `CourseFeedbackTest`.
* **Anonymous peer review on assignments** — `assignments.peer_review_enabled` (toggle in the faculty CourseDetail edit modal via `UpdateAssignmentRequest`) + `peer_reviews` table (unique submission+reviewer; NULL rating = pending task). `Domain/Academic/PeerReviewService`: tasks assigned lazily on the student assignment page (`tasksFor`, up to 2, least-reviewed-first, never own work), `submitReview` (route `assignments.peer-review`, anonymous notification to the reviewee), `receivedFor` (shuffled, no identifiers), `statsFor` → `peerReview` avg/count chip in the faculty Grading overview. Anonymity holds both ways. UI in `Student/AssignmentDetail.vue`. Tests: `PeerReviewTest`.
* **Prerequisites + section waitlists** — `course_prerequisites` pivot (admin-managed multi-select on the course form; self-reference filtered in `Admin/CourseController::prerequisiteIds`) and `section_waitlists` (FIFO). `EnrollmentService`: `unmetPrerequisites` (only pivot status **completed** satisfies), enforced in `eligibilityError`; `assignedFor` exposes per-course `prerequisites: [{code, met}]` (badges + disabled Register in `Student/Registration.vue`); admin assignment to a full section calls `waitlist()` (notified) instead of skipping; `drop()` runs `promoteFromWaitlist` (head of queue → pending placement + "A seat opened up" notification; skips students who got seats elsewhere). ⚠️ EnrollmentService now takes NotificationService in its constructor. Tests: `PrerequisiteWaitlistTest`.
* **Question bank** — `question_bank_items` (per-course, mirrors `class_test_questions` shape + topic/difficulty). `Domain/Academic/QuestionBankService`: faculty scope = courses they teach a section of (`teachableCourseIds`); manual add, `importFromTest` (dedupe by question text, topic = test title), `createDraftTest` (selected items → DRAFT class test via `ClassTestService::create`, redirects to the test editor), and `practiceQuizFromBank` for students (deterministic, **no AI gate**; maps mcq key→option-text answers for the practice grader). Routes `faculty.question-bank.*` (`manage_class_tests`) + `practice.from-bank`; pages `Faculty/QuestionBank.vue` + a "from bank" form on `Student/Practice/Index.vue`; nav link under Teaching. Tests: `QuestionBankTest`.
* **Camera-AI proctoring wave (2026-07-14)** — three new exam-security layers on the registry pattern, all client-side MediaPipe (self-hosted assets in `public/vendor/mediapipe/`, no CDN at exam time). **Face liveness** (`useFaceLiveness.js`): blink-verified gate before questions render (eye blendshapes + EAR fallback for masked faces, detection confidences 0.3), two-stage face-loss response (3s soft banner + question blur → 8s blocking overlay + counted incident; 2 free warnings then violations feed `max_warnings`), 30s gate bypass (logged `face_verification_bypassed` — never lock a student out), 90s no-blink photo-spoof flag, second-face detection (`numFaces: 2`). **Snapshot evidence** (`useExamSnapshots.js`): JPEG bursts at flagged moments + jittered periodic samples, `class_test_snapshots` table, per-attempt caps client+server, faculty photo-strip slider in `Attempt.vue` (⚠️ overlays inside AppLayout need **Teleport to body** — the layout's fade-in transform hijacks `position: fixed`). **Phone detection** (`usePhoneDetection.js`): ObjectDetector COCO "cell phone", consecutive-hit debounce + 30s cooldown, warning severity + photo evidence, never auto-violation. Supporting: **camera decoupled from recording** (`recording.camera` flag; camera layers do NOT require the `webcam` layer), recording bitrate cap 250kbps + `exam:prune-evidence` retention command (weekly), **fullscreen gate** fix (silently-refused fullscreen no longer lets students answer windowed), audible alerts (`useExamSounds.js`, must be unlocked from the Begin gesture), Admin → Exam Security "How each layer works" offcanvas (timings injected live via `guideConfig` prop). Tests: `FaceLivenessTest`, `SnapshotEvidenceTest`.
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

