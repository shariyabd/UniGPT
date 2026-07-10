# UniNexus — Architecture & Developer Reference

> **Updated:** 2026-07-10. The deep reference for the whole system — read this after
> [README.md](README.md) to build a complete mental model.
> **Source of truth = the code.** Where older marketing docs disagree, the code wins.
>
> **Contents:** [1. Overview](#1-overview--abstract) · [2. Application Logic](#2-application-logic)
> · [3. Architecture](#3-architecture) · [4. Directory Structure](#4-directory-structure)
> · [5. Workflows](#5-workflows-end-to-end) · [6. Roles & Responsibilities](#6-roles--responsibilities)
> · [7. Communication Flow](#7-communication-flow) · [8. Data Model](#8-data-model-reference)
> · [9. Appendix](#9-appendix)

---

## 1. Overview / Abstract

**UniNexus** (repo folder `uni-chat`) is a **university AI academic copilot**: a web app
that gives **Students, Faculty, and Administrators** a single AI-assisted platform for
academic work. Its core is a **RAG-backed (Retrieval-Augmented Generation) chat
assistant** grounded in the university's own approved documents (handbooks, syllabi,
policies, lecture notes), wrapped in **role-based dashboards** and a real **academic
domain** (terms, courses, sections, enrollment, grading, attendance, exams).

**Why it exists:** generic LLMs hallucinate. UniNexus answers from *institution-approved*
content and returns **citations + a confidence score**, so academic guidance is
traceable and auditable. Around that core it layers role-specific productivity tools.

**Tech stack:** Laravel 11 (PHP 8.2+) · Inertia 2 + Vue 3 SPA · Vite + Tailwind · MySQL
(`uni_gpt`, also the vector store) · Ziggy · `vue-toastification` · OpenAI (native HTTP)
with a deterministic **Mock** fallback · `smalot/pdfparser` for documents.

**One-line verdict:** a working, end-to-end RAG academic platform — real auth + RBAC, a
real document→chunk→embed→retrieve→cite→answer pipeline, real chat/saved-answers, a real
academic domain, and real admin governance — **runnable with zero API keys** via the mock
provider. Remaining gaps are scale/polish (external vector DB, speech, alternate
LLMs) tracked in [PROJECT_STATUS.md](PROJECT_STATUS.md).

---

## 2. Application Logic

The business rules that define how the system behaves. These are the things a new
developer most often gets wrong.

### 2.1 Identity & access (custom RBAC)

- The **`User` model lives at `app/Domain/User/Models/User.php`** — *not* `app/Models`.
  It owns all RBAC helpers: `hasRole`, `hasPermission`, `assignRole`, `syncRoles`,
  `isStudent`/`isFaculty`/`isAdmin`, `getPrimaryRole`, `getDashboardRoute`.
- **Roles ↔ Permissions** are many-to-many. **46 fine-grained permission slugs**
  (`app/Enums/Permission.php`) are the source of truth — `config/permissions.php` is a
  legacy soft map. (The three newest slugs are the **Community** set: `view_discussions`,
  `post_discussion`, `moderate_discussions`.)
- **Temporal roles:** `role_user.expires_at` lets a role assignment expire.
- **Role slug casing:** the canonical slug is the **lowercase** DB value (`admin`,
  `faculty`, `student`). Never compare against an uppercase enum name.
- **Login requires a role selection** (`student|faculty|admin`) that is validated
  against the user's assigned roles, `.edu`-style email, and rate-limited per email+IP.
- **Deactivated users** (`is_active = false`) are **force-logged-out** by the role and
  permission middleware on their next request.

### 2.2 The academic spine: Term → Course → Section → Enrollment

This is the most important domain model and was recently redesigned.

- A **Course** is the catalog entry (code, name, department, **semester** 1–8, credits).
  Admin-owned.
- A **Section** is a concrete **offering** of a course in a **Term** — it carries the
  instructor (`faculty_id`), schedule, capacity (`max_enrollment`), and is the unit that
  owns **materials, assignments, exams, attendance, and the roster**.
- **Section labels are constrained to a fixed A–J set**, normalized to uppercase and
  **unique per (course, term)** — enforced in `SectionRequest` + `CourseManagementService`.
  This prevents inconsistent values like `A` vs `a`. The admin picks the label from a
  dropdown, never free text.
- A **Term** has `is_current` (exactly one) and `is_registration_open`. Most student
  reads are scoped to the current term.

### 2.3 Enrollment: admin-assigns → student-confirms (two-step)

Enrollment lives on the **`course_user` pivot** (unique per `(course, user)`), stamped
with `section_id`, `term_id`, `status`, `grade`, `progress`, `enrolled_at`.

The flow (in `EnrollmentService`):

1. **Admin assigns** a student to a specific section → pivot `status = 'pending'`,
   `enrolled_at = null`. This **reserves a seat** but grants the student **no access**.
2. The student sees *only* their **assigned (pending)** sections on the **Registration**
   page and clicks **Register** to confirm → `status = 'enrolled'`, `enrolled_at = now()`.
3. Dropping sets `status = 'dropped'` (the row is kept for history).

Statuses: **`pending`** (reserved, awaiting confirm) · **`enrolled`** (active) ·
**`completed`** (past term) · **`dropped`** (withdrawn).

Critical rules:
- **A student may only register for sections the admin assigned to them** —
  `eligibilityError()` rejects anything else ("not assigned… contact the registrar").
- **Capacity** = enrolled **+** pending (a reserved seat counts). Checked at *assign*
  time, not at student confirm (the seat is already held).
- **Pending placements grant zero access.** `User::enrolledSectionIds()` excludes both
  `dropped` and `pending`, which centrally gates materials, exams, calendar,
  submissions, attendance, dashboards, and the chat/notes/tasks course pickers. A
  pending course must never leak content before the student confirms.
- **Prerequisites:** `course_prerequisites` links a course to required courses. Only a
  **completed** enrollment satisfies a prerequisite — `EnrollmentService::unmetPrerequisites()`
  feeds `eligibilityError()` (confirm is blocked with an explanation), and `assignedFor()`
  exposes per-prerequisite `{code, met}` badges to the Registration page (button disabled
  until all are met). Admins pick prerequisites via a multi-select on the course form.
- **Waitlists:** assigning a student to a **full** section queues them in `section_waitlists`
  (FIFO) and notifies them instead of failing. `drop()` auto-promotes the queue head into a
  `pending` placement + a "register to confirm" notification; the Registration page shows a
  waitlist card with the student's queue position.

### 2.4 RAG chat: grounded, cited, confidence-scored, streamed

When a user sends a chat message (`ChatService` → `RagChatService`):

1. **Embed the query** (`EmbeddingService`) and **retrieve** the top-K most similar
   chunks by **cosine similarity computed in PHP** over vectors stored in the
   MySQL `embeddings` table (`RetrievalService`). The retrieval scope is the union of
   **approved library documents the user may see**, the user's **own notes**, and the
   **materials of their enrolled/teaching sections** (see §2.4a).
2. **Build a grounded context block** from those chunks and call the **AI provider** —
   either as a single completion (`answer()`) or **streamed token-by-token**
   (`answerStream()` → `AIProviderInterface::chatStream`, delivered over SSE via the
   `chat.stream` / `faculty.ai-assistant.stream` endpoints and parsed client-side by
   `resources/js/lib/sse.js`).
3. **Persist** the session + messages + **citations** (`message_citations`) + a
   **confidence score/level** + suggested **follow-ups** — identically on both paths.

- **Chat modes** (`ChatMode`): general / academic / research / exam_prep /
  assignment_help / career_guidance — each injects a different system prompt.
- **Provider resolution** (`AIProviderManager`): use the configured provider
  (OpenAI) if a key is present; otherwise **fall back to the deterministic
  `MockProvider`** so chat works with no credentials. The mock composes an answer from
  the injected context, streams it word-by-word, and produces stable lexical-hash embeddings.

### 2.4a Personal corpus — "chat with my materials" (shadow documents)

Student notes (including OCR'd handwriting) and file-backed course materials are
RAG-searchable via **shadow documents**: rows in `documents` with
`source_type = note|material`, `source_id`, and `owner_id`. They flow through the
*same* chunk → embed → retrieve → cite pipeline as the library.

- A **global scope** (`Document::LIBRARY_SCOPE`) restricts every ordinary query to
  `source_type = library`, so shadows can never leak into the admin library,
  approvals, dashboards, analytics or route binding. RAG code opts out explicitly via
  `Document::allSources()`.
- **Sync:** `SyncNoteToRagJob` / `SyncCourseMaterialToRagJob` fire on every mutation
  (one job handles create/update/delete — a missing model means "forget the shadow");
  `PersonalCorpusService` skips re-embedding unchanged content via a stored hash.
  Backfill: `php artisan rag:sync-personal [--user=]`.
- **Access:** retrieval scope = library-visible ∪ own notes ∪ enrolled/teaching-section
  materials; the retrieval cache key is per-user. Citations carry the shadow's
  category ("Personal Note" / "Course Material") so the UI labels sources for free.

### 2.4b Agentic chat — tool calling (student tutor)

The student tutor doesn't just answer — it can **act**. Providers accept
`options['tools']` (OpenAI function calling; the mock provider handles tool-shaped
requests deterministically) and surface requests via `ChatResult::$toolCalls`.
`RagChatService` runs an **agent loop — max 3 rounds, student chat only**: execute the
requested tools, feed the results back, repeat until the model produces a plain answer.

- **Registry:** `ChatToolRegistry` (`app/Domain/Chat/Tools/`) exposes **8 tools** —
  `get_upcoming_deadlines`, `list_my_courses`, `list_office_hour_slots`,
  `book_office_hour_slot`, `cancel_office_hour_booking`, `generate_practice_quiz`,
  `generate_flashcard_deck`, `create_study_task` — each a `ChatToolInterface`
  implementation returning a `ToolExecution` DTO.
- **Safety by construction:** every tool delegates to the real domain service
  (`OfficeHoursService`, `PracticeQuizService`, `FlashcardService`, `TaskService`
  equivalents…), so RBAC, section scoping and the **atomic office-hours claim** bind AI
  actions exactly as they bind UI clicks.
- **Persistence & UX:** the tool trail is persisted in `chat_messages.tool_activity`
  (json) and rendered as a step-by-step trail in `Student/Chat.vue`; the SSE path emits
  `tool_start` / `tool_result` events between `delta`s.
- **Mode switch (Agent vs Answers only):** chat requests carry an `agent` boolean
  (default `true`) that flows `ChatController` → `ChatService::sendMessage(withTools:)`
  → `RagChatService`; when `false`, tool definitions are **never attached** to the
  provider call — a server-side guarantee, not just UI. The segmented switcher above
  the composer drives mode-aware hint/placeholder/example prompts, and replies that
  ran tools render an "⚡ Agent" badge next to the AI badge.

### 2.5 Document knowledge base lifecycle

`Document` → **upload** (admin) → stored on local disk → **approval workflow**
(approve / reject / request-changes / comment, audited in `document_approvals`) →
on approval a queued **`ProcessDocumentJob`** runs **extract text → chunk
(`ChunkingService`) → embed (`EmbeddingService`) → store**. Only **approved** chunks are
retrievable in chat. Visibility is scoped; downloads are logged. Documents soft-delete.

### 2.6 Other domain rules worth knowing

- **Attendance:** faculty mark a section roster by date; `AttendanceStatus` LATE and
  EXCUSED both `countsAsPresent()`. Students see a per-course rate.
- **Grades & transcript:** grades live on `course_user.grade`; `TranscriptService`
  computes per-term GPA and credit-weighted CGPA on a 4.0 scale (no extra schema).
- **Quizzes / class tests:** faculty author a timed quiz on a section (instructions,
  duration, MCQ / True-False questions + options, marks, optional availability window).
  Questions can be **written manually or generated with AI** (`ClassTestService::generateQuestions`,
  built on `TeachingAssistantService`) and edited before publishing. A student starts an
  **attempt** (the countdown is anchored to their start, enforced server-side), takes it in
  fullscreen with anti-cheat (warn once, then disqualify), and on submit/timeout the attempt
  is **auto-graded** — the score and answer review return instantly; a disqualified attempt
  scores 0. Publishing a quiz notifies the roster.
- **Exam security (layered proctoring):** a config-driven registry of 14 independent layers
  (`config/exam_security.php`) resolved per test by `ExamSecurityService` as *config default →
  admin global gate → per-test faculty selection* (`class_tests.security_config`). Beyond the
  original fullscreen/tab/clipboard guards it adds question/option randomisation, an identity
  watermark, browser fingerprinting, a typed behaviour/violation event log (`class_test_events`),
  a computed 0–100 risk score on the attempt, an AI assessment-integrity notice, and **webcam /
  screen recording** captured with `MediaRecorder` and stored in chunks on a private disk
  (`class_test_recordings`). Faculty get a per-attempt review dossier (`attemptReview`); admins
  gate the layers globally via the `exam_security` setting (`Admin/ExamSecurityController`).
- **Notifications** are per-recipient rows; auto-fired on graded submission, published
  material, scheduled exam, published assignment, published quiz, and enrollment
  assignment; admins can **broadcast** announcements.
- **Calendar** (`CalendarService`) merges assignment deadlines + exams + personal tasks.
- **Notes/Tasks** are personal and **owner-scoped** (a user only ever sees their own).
- **AI Study Planner:** `StudyPlannerService` (`app/Domain/Academic/`) builds a study schedule
  from the student's upcoming deadlines and **persists chosen sessions as `Task` records**;
  surfaced via `Student/StudyPlannerController`.
- **Flashcards:** `FlashcardService` (`app/Domain/Academic/`) with `FlashcardDeck` / `Flashcard`
  models. Each card carries its own **SM-2 spaced-repetition state**; decks can be **AI-generated**
  via `TeachingAssistantService::generateFlashcards()`.
- **Learning Analytics (student):** `LearningAnalyticsService` (`app/Domain/Analytics/`) drives
  the student **progress** page (`Student/LearningAnalyticsController`, route name `progress`).
  It **reuses `TranscriptService` + `AttendanceService`**; charts render through
  `components/charts/StatChart.vue` (Chart.js / vue-chartjs, newly added deps).
- **Leaderboard:** `LeaderboardService` (`app/Domain/Analytics/`). **Opt-in** via
  `users.leaderboard_opt_in` / `leaderboard_alias`. **XP** (derived from class-test, assignment,
  and attendance data) is **computed at read time — never materialised** — and scoped by
  department / semester / section.
- **Discussions (Community):** section-scoped forum where **a `Section` *is* the discussion
  group** (membership derived from enrolment / teaching). Logic lives in the **new
  `DiscussionService` (`app/Domain/Community/`)** over `Post` / `PostComment` / `PostReaction` /
  `PostReport` models (posts + comments soft-deleted). Served by a shared
  `Community/DiscussionController` (student + faculty, **relationship-based access like the
  messenger routes**) with `Admin/DiscussionModerationController` handling the report queue.
- **OCR notes:** `AIProviderInterface` gained **`extractText(imagePath, mimeType)`**, implemented
  in `OpenAiProvider` (gpt-4o vision) with a `MockProvider` fallback. `OcrService`
  (`app/Domain/Chat/`) backs the `NoteController::ocr` endpoint (route `notes.ocr`), gated by
  `use_ai_chat` + `ai.chat.access`.
- **Submission similarity screening:** every submit queues a `ScreenSubmissionSimilarityJob`
  that extracts text via the shared `SubmissionTextService` (content + PDF/DOCX extraction),
  chunk-embeds it into `submission_embeddings`, and compares it against classmates' chunks
  (`SubmissionSimilarityService`). Pairs above `rag.submission_screening.flag_threshold`
  (**0.82**) are flagged **in both directions** in `submission_similarities`; faculty Grading
  shows a badge + a matching-excerpt panel. Advisory only — nothing is auto-penalised.
- **AI rubric grading:** `TeachingAssistantService::draftRubricGrade()` drafts per-criterion
  scores (clamped to each criterion's max, case-insensitive criterion-name matching, heuristic
  fallback labelled `source: heuristic`). POST `faculty/submissions/{id}/draft-grade` prefills
  the `Grading.vue` rubric inputs + justifications; the teacher still edits and saves. (This
  wave also fixed the stored-rubric `criterion` vs UI `name` key mismatch in `GradingService`.)
- **Peer review:** opt-in per assignment (`assignments.peer_review_enabled`, toggled from the
  faculty CourseDetail edit modal). `PeerReviewService::tasksFor()` lazily assigns **≤2**
  review tasks per submitted reviewer — least-reviewed-first, never their own; `peer_reviews`
  is unique per (submission, reviewer) with `NULL` rating = pending. `submitReview()`
  (POST `assignments/{assignment}/peer-reviews/{review}`) notifies the reviewee anonymously;
  `receivedFor()` returns shuffled feedback; `statsFor()` feeds an average-rating chip in
  Grading. Ratings never touch the grade.
- **Anonymous course feedback:** `course_feedback` (unique per (section, student); the
  respondent's identity is never exposed) gated by `sections.feedback_open`.
  `CourseFeedbackService` enforces a **MIN_RESPONSES = 3 anonymity floor** and returns
  shuffled, timestamp-free comments; `TeachingAssistantService::summarizeFeedback()` produces
  an AI theme summary. Routes `course-feedback*` (student) + `faculty.course-feedback*`;
  pages `Student|Faculty/CourseFeedback.vue`.
- **Question bank:** `question_bank_items` per course (mirrors the class-test question shape
  + topic/difficulty). `QuestionBankService` scopes faculty to courses they teach;
  `importFromTest()` dedupes; `createDraftTest()` assembles a draft `ClassTest` via
  `ClassTestService`; `practiceQuizFromBank()` lets **enrolled students** self-quiz from the
  bank — deterministic, **no AI gate**, mapping MCQ answer keys → text answers. Routes
  `faculty.question-bank.*` + `practice.from-bank`.
- **Concept mastery (student):** `ConceptMasteryService` (no AI calls) merges practice-quiz
  topics, flashcard-deck SM-2 state ("Review: X" decks fold into topic X; a card counts as
  *learned* at ≥2 reps ∧ ≥6-day interval) and submitted class-test scores, blended
  **.5 / .35 / .15** (renormalised when a signal is missing); **<60 = weak**. Served as the
  `conceptMastery` prop on `/progress`; weak tiles get one-click quiz/deck generate buttons
  in `Student/LearningAnalytics.vue`.
- **Email digests & nudges:** `EmailDigestService` (`app/Domain/Notification/`) + queued
  `WeeklyDigestMail` / `AssignmentDueMail` mailables (views under `resources/views/emails/`).
  `digests:send-weekly` is scheduled **Mon 07:00** in `routes/console.php`;
  `assignments:remind` (daily 08:00) now emails alongside the in-app nudge with the same
  dedupe. Opt-out via `preferences.email_digest` (Settings toggle).
- **Auditing:** `ActivityLogger` writes to `activity_logs` for key actions.

---

## 3. Architecture

### 3.1 Request flow (Inertia monolith)

1. `resources/views/app.blade.php` (single root view) boots `resources/js/app.js`.
2. A controller returns `Inertia::render('Page', [...props])`; Vue pages auto-resolve
   from `resources/js/pages/**/*.vue` by name.
3. Vue calls named routes via **Ziggy** (`route()`); flash messages flow through shared
   props into `vue-toastification`.
4. `HandleInertiaRequests` shares the **real authenticated user** (id, name, roles,
   permissions, department, dashboard route) plus the notifications badge.

> **There is no separate REST API.** "API-style" actions (POST `/chat`, grade, approve,
> register, …) are **web routes returning Inertia/redirects**, guarded by session auth +
> role + permission middleware. `routes/api.php` is effectively empty. This is deliberate.

### 3.2 Layered design (DDD-flavored)

```
Controller (thin: orchestrate + respond)
  → Form Request (validation + permission-aware authorize())
  → Policy (per-record ownership, e.g. "this faculty owns this course")
  → Domain Service (all business logic)
      → Eloquent models (MySQL)
      → Infrastructure adapters (AI provider, file storage, repositories)
```

- **Controllers stay thin.** Business logic belongs in `app/Domain/{Context}/Services/`.
- **Authorization is layered:** route middleware (role + permission) → Form Request
  `authorize()` → Policy for record ownership.
- **Bounded contexts:** User, Academic, Chat, RAG, Notification, Analytics, Community.

### 3.3 Authentication & middleware

- Aliases registered in `bootstrap/app.php`: `role` → `RoleMiddleware`,
  `permission` → `PermissionMiddleware`. Only **`routes/web.php`** is registered.
- `RoleMiddleware` / `PermissionMiddleware`: gate access, log unauthorized attempts,
  and force-logout deactivated users. Guests redirect to `/login`.

### 3.4 AI / RAG infrastructure

- **Contract:** `AIProviderInterface` (`chat`, `chatStream`, `extractText`, `embed`,
  `embeddingDimensions`, `embeddingModel`, `name`, `isAvailable`). `chat`/`chatStream`
  accept `options['tools']` (function-calling schemas) and report requested calls via
  `ChatResult::$toolCalls` — the hook the agentic tutor (§2.4b) is built on.
- **Implementations:** `OpenAiProvider` (native HTTP, no SDK; SSE streaming with
  `stream_options.include_usage` so token accounting survives streaming) and
  `MockProvider` (deterministic, always available, word-streams its answers);
  resolved by `AIProviderManager` with auto-fallback.
- **Vector store = MySQL.** Embeddings are JSON in the `embeddings` table; retrieval is
  PHP cosine similarity. `app/Infrastructure/VectorDB/` is empty and `config/vector.php`
  is unused — both are swap points for a managed vector DB at scale.
- **File storage:** `DocumentStorageService` (local disk) + `DocumentTextExtractor`
  (PDF/DOCX/txt/md, page-aware).

### 3.5 Domain-layer status

| Context | State |
|---|---|
| User (RBAC, service, repository) | ✅ Implemented |
| Academic (Course/Section/Term/Enrollment — incl. **prerequisites & waitlists**/Grading/Attendance/Exam/ExamSecurity/ClassTest/Transcript/Calendar/Submission/**StudyPlanner**/**Flashcard**/**PracticeQuiz**/**OfficeHours**/**IcsExport**/**SubmissionSimilarity + SubmissionText**/**PeerReview**/**CourseFeedback**/**QuestionBank**) | ✅ Implemented |
| Chat (`ChatService`, `RagChatService` — answer + **answerStream** + **agent loop**, `TeachingAssistantService` — incl. **`draftRubricGrade` / `summarizeFeedback`**, **`OcrService`**, **Tools/`ChatToolRegistry` + 8 chat tools**) | ✅ Implemented |
| RAG (Embedding/Retrieval/Citation + **Ingestion/`PersonalCorpusService`** — MySQL-native, per-user scope) | ✅ Implemented |
| Search (**`GlobalSearchService`** — ⌘K semantic + lexical) | ✅ Implemented |
| Document (Service, Processing, Chunking) | ✅ Implemented |
| Notification (+ **`EmailDigestService`** — weekly digest + due-soon emails), Analytics (platform + faculty + **`EarlyWarningService`** at-risk + **student `LearningAnalyticsService`** + **`ConceptMasteryService`** + **`LeaderboardService`** + `AiUsageService`) | ✅ Implemented |
| Community (`DiscussionService` — section-scoped forum + moderation; **`StudyRoomService`** — group chats) | ✅ Implemented |
| AI Infrastructure (OpenAI + Mock + Manager, both streaming-capable) | ✅ Implemented |
| Chat/Memory, RAG/Prompts, Academic/Rules+ValueObjects | ⬜ Empty (inlined or unneeded) |
| VectorDB, Speech infrastructure | ⬜ Empty (MySQL store; no speech) |

---

## 4. Directory Structure

A high-level map lives here; the **fully annotated tree** is in
[DIRECTORY_TREE.md](DIRECTORY_TREE.md).

```
app/
  Domain/{User,Academic,Chat,RAG,Notification,Analytics,Community}/  business logic
  Infrastructure/{AI,FileStorage,Repositories}/             external adapters
  Http/{Controllers,Requests,Middleware}/                   thin HTTP layer
  Models/                                                    Eloquent entities
  Enums/  Policies/  Jobs/  Services/  Console/Commands/
resources/js/{pages,components,Layouts,composables}/         Inertia + Vue SPA
routes/web.php                                               the only live route file
database/{migrations,seeders,factories}/
config/{ai,rag,permissions,vector}.php
```

---

## 5. Workflows (end-to-end)

### 5.1 Auth & role routing
Login (pick role) → validate credentials + role + rate limit → `HandleInertiaRequests`
shares the user → redirect to `getDashboardRoute()` → role/permission middleware gates
every subsequent page. Deactivated mid-session → force logout.

### 5.2 Admin sets up an offering and assigns a student
1. Admin creates a **Term** (sets current, opens registration).
2. Admin creates a **Course** (catalog).
3. Admin creates a **Section** under it — picks an **A–J label** (dropdown), term,
   faculty, capacity.
4. Admin **assigns** a student to that section → `pending` placement (seat reserved,
   notification sent: "register to confirm").
5. Student opens **Registration**, sees only the assigned course, clicks **Register** →
   `enrolled`; the section's materials/exams/etc. become accessible.

### 5.3 Student asks the AI a question
Chat page → POST `/chat` → embed query → retrieve approved-doc chunks → build cited
context → provider answers (OpenAI or Mock) → response shows answer + **citations** +
**confidence** + follow-ups; session/messages/citations persisted. Useful answers can be
**saved** (folders/tags/starred). If the request calls for an *action* ("book me a slot",
"quiz me on X"), the agent loop (§2.4b) runs the matching tool(s) and the chat shows the
tool trail alongside the answer.

### 5.4 Admin curates a document → student queries it
Upload → stored → approval workflow (approve/reject/request-changes/comment, audited) →
on approve, `ProcessDocumentJob` chunks + embeds → the doc becomes **retrievable in chat**
and **downloadable** by permitted roles.

### 5.5 Faculty teaches, generates, grades
View taught sections → upload materials → **AI teaching assistant** generates a
**quiz/assignment** (LLM when keyed, deterministic template otherwise) → **publish** it
as a real `Assignment` (notifies enrolled students) → students submit (each submission is
**similarity-screened** in the background; peer-review tasks unlock if enabled) → faculty
**grade** with rubric + **AI-drafted feedback** or a per-criterion **AI rubric draft** (both
editable, nothing auto-saved), with a **similarity badge** and the **peer-review average**
as context → grade notification fires → **learning analytics** (grade distribution,
attendance, at-risk). At term's end the teacher can open **anonymous course feedback** and
read the AI theme summary once the 3-response floor is met.

### 5.6 Student term lifecycle
Register → attend (faculty mark attendance) → submit assignments → receive grades →
view **transcript** (term GPA + CGPA) and **exams/calendar**; manage personal
**notes/tasks**.

---

## 6. Roles & Responsibilities

### 6.A Student
**Can:** chat with the **streaming, agentic** RAG tutor (6 modes, cited + confidence,
grounded in the library **plus their own notes and section materials**; it can also **act**
— book/cancel office hours, generate quizzes/decks, create study tasks — via the tool loop);
save/organize answers; follow a roadmap from real enrollments; register for
**admin-assigned** sections (with **prerequisite badges** and **waitlist** status); view
materials & approved documents (download logged); submit assignments (similarity-screened);
**peer-review classmates' submissions** anonymously when enabled; **take timed
quizzes/class tests** (countdown timer, auto-graded with an instant result); generate
**AI practice quizzes** (server-graded, retakes, missed → flashcards) or build them **from
the course question bank** (no AI needed); track a **concept mastery map** on My Progress;
leave **anonymous course feedback**; join **group study rooms**; **book faculty office
hours**; use the **⌘K global search**; view grades, transcript, attendance, exams, calendar
(**.ics export/subscribe**); receive an opt-out **weekly email digest** + due-soon reminder
emails; keep personal notes/tasks; edit profile/settings.
**Cannot:** upload/approve documents, manage users, configure AI, or see analytics beyond
their own.
**Key permissions:** `use_ai_chat`, `view_chat_history`, `view_courses`, `enroll_course`,
`view_assignments`, `submit_assignment`, `take_class_test`, `view_attendance`, `view_exams`,
`view_documents`, `download_document`, `view_own_analytics`.

### 6.B Faculty
**Can:** view/manage the **sections they teach**; upload/manage course materials; use the
**AI teaching assistant** (RAG chat + quiz/assignment generators + publish); **author and
run timed quizzes/class tests** (rules, duration, MCQ / True-False questions written by hand
or **AI-generated**, marks, optional availability window — fullscreen proctoring + auto-graded);
curate a per-course **question bank** (author, import from tests, assemble draft tests);
grade submissions with rubrics + AI-drafted feedback or a per-criterion **AI rubric draft**,
informed by **similarity-screening badges** (with excerpt panel) and **peer-review averages**;
toggle **peer review** per assignment; open/close **anonymous course feedback** and read the
AI theme summary; mark attendance; read exam timetable; publish **bookable office-hours
slots** and manage bookings; view per-course **learning analytics** (grade distribution,
attendance rate, submission completion) with the **at-risk early-warning list** (4 signals,
high/watch levels, one-click message to the flagged student).
**Cannot:** manage users, configure the AI provider, own the catalog/terms/sections
(admin-owned), or administer the system. Department-scoped.
**Key permissions:** all student perms **+** `manage_materials`, `create_assignment`,
`grade_assignment`, `manage_class_tests`, `mark_attendance`, `view_department_analytics`.
> The catalog, sections, and term/registration are **admin-owned**; faculty are *assigned*
> to sections and manage teaching artifacts within them.

### 6.C Administrator
**Can:** full user lifecycle + RBAC matrix; own the **course catalog** (incl. **course
prerequisites**), **sections, terms, departments**; **assign students** to sections (full
sections queue a **FIFO waitlist** that auto-promotes on drop); curate the **document knowledge base**
(upload + approve/reject/request-changes/comment → embed pipeline); configure & **test**
the AI provider; broadcast **announcements**; manage **exams**; view **platform analytics**;
**monitor** system health; review the **audit log**.
**Cannot:** nothing within the app (top of hierarchy).
**Key permissions:** all 40, incl. `manage_user_roles`, `manage_permissions`,
`manage_departments`, `manage_terms`, `manage_sections`, `approve_document`, `configure_ai`,
`manage_exams`, `send_notifications`, `manage_system`, `view_all_analytics`.

---

## 7. Communication Flow

How roles and components interact (all relationships below are **wired and real**):

```
                       ┌──────────────────────────────────────────────┐
                       │                ADMINISTRATOR                  │
                       │ • Owns users + RBAC, catalog, sections, terms │
                       │ • Assigns students → sections (pending)       │
                       │ • Approves documents → knowledge base (RAG)   │
                       │ • Configures the AI provider; broadcasts       │
                       └───────┬───────────────────────────┬──────────┘
                  governs +    │ assigns students /          │ governs accounts,
                  approves docs │ owns offerings              │ approves docs
                  ┌────────────▼──────────────┐   ┌──────────▼───────────────┐
                  │          FACULTY           │   │          STUDENT          │
                  │ • Teach assigned sections  │   │ • Register for assigned   │
                  │ • Upload materials ────────┼──▶│   sections; read materials│
                  │ • Publish quizzes/assigns ─┼──▶│ • Submit assignments ─────┼─┐
                  │ • Grade + AI feedback ─────┼──▶│ • View grades/attendance  │ │
                  │ • View learning analytics ◀┼───┤ • RAG AI tutor chat       │ │
                  └────────────────────────────┘   └───────────────────────────┘ │
                          ▲                                  │ activity + grades   │
                          │ submissions feed grading ◀───────┘                     │
                          └───────────────── Notifications fire on grade / material /
                                              exam / assignment / enrollment ───────┘
                  Shared core: every role depends on the Chat/RAG engine and the
                  admin-approved Document knowledge base. Activity → Analytics.
```

- **Admin → Faculty/Student:** account governance, document approval, AI config,
  section/term ownership, student↔section assignment.
- **Faculty → Student:** materials, published assignments/quizzes, grades + feedback.
- **Faculty ↔ Student (real-time):** direct 1:1 chat between a student and a faculty
  member they share a section with — presence, typing and unread counts, over Ably
  (`Messenger/MessageController`, `MessageSent`). A synchronous channel alongside the
  asynchronous notifications below.
- **Student → Faculty:** submissions and activity feed grading and analytics.
- **System events → Notifications:** grade posted, material/assignment/exam published,
  enrollment assigned, admin announcement.
- **Everyone → Chat/RAG + Knowledge base:** the shared dependency at the center.

> **Not yet present** (see [PROJECT_STATUS.md](PROJECT_STATUS.md) → Future Plans):
> external (Telegram/WhatsApp) push. Direct real-time student↔faculty messaging has
> **shipped**; broadcast announcements and event notifications remain asynchronous.

---

## 8. Data Model Reference

**RBAC / User:** `users`, `roles`, `permissions`, `role_user` (`expires_at`),
`permission_role`, `departments`.
**Academic:** `terms`, `courses`, `sections` (incl. `feedback_open`), `course_user`
(pivot: status, grade, progress, `term_id`, `section_id`, `enrolled_at`),
`course_prerequisites`, `section_waitlists` (FIFO), `course_materials`,
`material_completions`, `assignments` (incl. `peer_review_enabled`),
`assignment_submissions`, `submission_embeddings`, `submission_similarities`,
`peer_reviews` (unique (submission, reviewer); `NULL` rating = pending),
`course_feedback` (unique (section, student)), `question_bank_items`,
`attendance_records`, `exams`, `notes`, `tasks`.
**Chat / RAG / Documents:** `chat_sessions`, `chat_messages` (incl. `tool_activity`
json — the agentic tool trail), `message_citations`, `saved_answers`, `documents`
(soft-deletes), `document_chunks`, `embeddings`, `document_approvals`.
**System:** `notifications`, `activity_logs`, `settings` + Laravel infra
(`cache`, `jobs`, `sessions`, `password_reset_tokens`).

Key relationships: `User ↔ Course` via `course_user`; `Course → Section → Term`;
section-scoped models (`CourseMaterial`, `Assignment`, `Exam`, `AttendanceRecord`) use
the `BelongsToSection` trait; `Document → DocumentChunk → Embedding`;
`ChatMessage → MessageCitation → Document`.

---

## 9. Appendix

### 9.1 Key services
- **Academic:** `Course`, `CourseManagement`, `Enrollment` (incl. prerequisites + waitlists),
  `Grading`, `Submission`, `SubmissionSimilarity`, `SubmissionText`, `PeerReview`,
  `CourseFeedback`, `QuestionBank`, `Attendance`, `Exam`, `ExamSecurity`, `ClassTest`,
  `Transcript`, `Calendar`, `Term`, `StudyPlanner`, `Flashcard`, `PracticeQuiz`,
  `OfficeHours`, `IcsExport` services (`app/Domain/Academic/Services/`).
- **Chat/RAG:** `ChatService`, `RagChatService` (answer + answerStream + agent loop),
  `TeachingAssistantService` (incl. `draftRubricGrade`, `summarizeFeedback`), `OcrService`;
  `ChatToolRegistry` + 8 tools (`app/Domain/Chat/Tools/`); `EmbeddingService`,
  `RetrievalService`, `CitationService`, `PersonalCorpusService`; `ChunkingService`.
- **Search:** `GlobalSearchService` (⌘K — semantic knowledge group + lexical entity groups).
- **Documents/Jobs:** `DocumentService`, `DocumentProcessingService` (+ `ProcessDocumentJob`,
  `SyncNoteToRagJob`, `SyncCourseMaterialToRagJob`, `ScreenSubmissionSimilarityJob`).
- **Community:** `DiscussionService`, `StudyRoomService`.
- **User/Analytics:** `UserManagementService`, `EloquentUserRepository`,
  `AnalyticsService`, `FacultyAnalyticsService`, `EarlyWarningService`,
  `LearningAnalyticsService`, `ConceptMasteryService`, `LeaderboardService`,
  `NotificationService`, `EmailDigestService` (+ queued `WeeklyDigestMail` /
  `AssignmentDueMail`; commands `digests:send-weekly`, `assignments:remind`),
  `ActivityLogger`.

### 9.2 Enums (`app/Enums/`)
`Permission` (46, categorized), `UserRole` (admin/faculty/student, lowercase),
`ChatMode` (6), `NotificationType` (10, incl. `class_test` + `office_hours`),
`AttendanceStatus` (4), `ExamType` (4), `TaskPriority` (3), `DocumentStatus` (6),
`ConfidenceLevel` (5), `Language` (7).

### 9.3 Authorization artifacts
~30 permission-aware Form Requests (`app/Http/Requests/{Admin,Faculty,Student}/`);
Policies — `ChatSessionPolicy`, `ConversationPolicy`, `CoursePolicy`, `DocumentPolicy`,
`SavedAnswerPolicy`. The signed `calendar.feed` route uses a URL signature (no session).

### 9.4 Tests
**PHP** (`tests/Feature/`, 45 suites / 235 tests): role gating (Admin/Faculty/Student),
permission matrix, attendance, course/department/exam/section management, enrollment,
**section assignment + isolation**, self-registration, multi-section, notifications,
transcript, faculty analytics, feedback generation, term rollover/separation, productivity,
class tests, messenger, corpus versioning, **personal corpus (RAG scoping)**,
**chat streaming (SSE)**, **practice quizzes**, **early warning**, **global search**,
**study rooms**, **office hours**, **calendar export** — plus the July 2026 wave:
**chat tools (agentic)**, **submission similarity**, **concept mastery**, **email digests**,
**AI grade drafts**, **course feedback**, **peer review**, **prerequisites & waitlists**,
**question bank**.
**JS** (`resources/js/tests/`, Vitest): `AppLayout` nav gating, `usePermissions`,
`RolePermissions` matrix, button gating.

### 9.5 Demo credentials (seeded, password `demo123`)
Student `student@university.edu` · Faculty `prof.smith@university.edu`,
`prof.jones@university.edu` · Admin `admin@university.edu`.
`demoLogin` auto-seeds RBAC if these are missing.
