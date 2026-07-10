# UniNexus — Project Status, Backlog & Roadmap

> The live status tracker. **Source of truth = code.** For architecture and logic see
> [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md); for layout see [DIRECTORY_TREE.md](DIRECTORY_TREE.md).
>
> **Last updated:** 2026-07-10
> **Contents:** [Summary](#progress-summary) · [Feature matrix](#feature-matrix-codebase-vs-spec)
> · [Recently shipped](#recently-shipped) · [Incomplete tasks](#incomplete-tasks)
> · [Future plans / upcoming features](#future-plans--upcoming-features)

Legend: ✅ COMPLETE · 🟡 PARTIAL · ⬜ NOT_STARTED · 🚫 BLOCKED.
COMPLETE = backend + DB + frontend + a working end-to-end user flow.

---

## Progress Summary

| Band | State |
|---|---|
| **P0 — Foundation** | ✅ Auth, RBAC, AI Chat, RAG, DB schema |
| **P1 — Core functional** | ✅ Attendance, admin frontends wired, Faculty Course/Material CRUD |
| **P2 — Extended** | ✅ Notifications, Transcript, Exams/Timetable, Faculty analytics |
| **P2.5 — Low-priority** | ✅ Calendar, Notes, Tasks, Department management |
| **P2.6 — Academic structure** | ✅ Terms, Sections, **admin-assign → student-confirm registration**, section-label constraints |
| **P3 — Advanced/Future** | ⬜ NOT_STARTED — see [Future plans](#future-plans--upcoming-features) |

**Maturity: a working RAG MVP across all three roles, end-to-end.** The remaining work
is the P3 advanced band plus the new product features below — none of which block the
current MVP.

---

## Feature Matrix (codebase vs spec)

### Platform / AI
| Feature | Status | Evidence |
|---|---|---|
| Auth + login/role-selection + rate limit | ✅ | `Auth/AuthenticationController`, `RoleMiddleware` |
| RBAC (46 perms, pivots, `expires_at`) | ✅ | `Enums/Permission`, `RBACSeeder`, `PermissionMiddleware` |
| AI Chat (sessions, messages, modes, pin/archive) | ✅ | `Domain/Chat/Services/ChatService` |
| RAG (chunk→embed→cosine retrieve→cite) | ✅ | `Domain/RAG/*`, MySQL vector store |
| Multi-LLM provider (OpenAI + mock fallback) | ✅ | `Infrastructure/AI/*`; mock = zero-key default |
| Document KB + approval + embed pipeline | ✅ | `DocumentService`, `ProcessDocumentJob` |
| Saved answers / bookmarks | ✅ | `Student/SavedAnswerController` |
| **Streaming AI responses (SSE)** — student chat + faculty assistant | ✅ | `AIProviderInterface::chatStream`, `RagChatService::answerStream`, `chat.stream` / `faculty.ai-assistant.stream`, `resources/js/lib/sse.js` |
| **Personal-corpus RAG** ("chat with my materials": own notes + section materials as shadow documents) | ✅ | `Domain/RAG/Ingestion/PersonalCorpusService`, `Document::LIBRARY_SCOPE`, `Sync*ToRagJob`, `rag:sync-personal` |
| **Semantic global search (⌘K)** — knowledge + courses/assignments/discussions/chat history | ✅ | `Domain/Search/GlobalSearchService`, `SearchController`, AppLayout palette |
| **Agentic AI chat (tool calling)** — 8 permission-checked in-chat actions + live tool-activity trail, **Agent / Answers-only mode switch** (answers-only enforced server-side) (student chat only; mock-provider keyless) | ✅ | `Domain/Chat/Tools/ChatToolRegistry` + 8 `*Tool` classes (deadlines, courses, office hours, quiz/flashcard gen, planner tasks) |
| **Email digests & deadline nudges** — weekly Monday digest + daily due-soon email, opt-out, admin SMTP | ✅ | `Domain/Notification/Services/EmailDigestService`, `digests:send-weekly` + `assignments:remind`, `routes/console.php` schedule |
| Voice / TTS / STT / predictive | ⬜ | P3 — voice is a UI mock (intentionally on hold) |

### Student
| Feature | Status | Evidence |
|---|---|---|
| Dashboard (real stats/links/actions) | ✅ | `StudentDashboardController` |
| RAG tutor chat + saved answers | ✅ | `ChatController`, `SavedAnswerController` |
| Roadmap / GPA / progress | ✅ | `roadmap()` (real enrollment data) |
| **Registration (assign → confirm)** | ✅ | `RegistrationController`, `EnrollmentService::assignedFor/enroll` |
| **Prerequisite badges + waitlist queue positions** on registration (server-side block until prereqs met) | ✅ | `RegistrationController`, `EnrollmentService::waitlistFor`, `SectionWaitlist` |
| Course materials (persisted completion, gated download) | ✅ | `CourseService::studentMaterials` |
| Assignments + submissions | ✅ | `Student/AssignmentController`, `SubmissionService` |
| **Anonymous peer review** (rate up to 2 anonymized classmate submissions; see feedback received) | ✅ | `Student/AssignmentController::storePeerReview` (`assignments.peer-review`), `PeerReviewService`, `PeerReview` |
| **Anonymous course feedback** (one revisable 1–5 rating + comment per open section window) | ✅ | `Student/CourseFeedbackController` (`course-feedback*`), `CourseFeedbackService`, `CourseFeedback` |
| Timed quizzes / class tests (take + auto-grade + instant result) | ✅ | `Student/ClassTestController`, `ClassTestService` |
| Layered proctoring (fullscreen/tab/clipboard/watermark/fingerprint/behaviour/webcam+screen recording) | ✅ | `ExamSecurityService`, `config/exam_security.php`, `class_test_events`/`class_test_recordings` |
| Attendance · Transcript · Exams · Calendar | ✅ | Attendance/Transcript/Exam/Calendar services |
| Notes · Tasks (owner-scoped) | ✅ | `NoteController`, `TaskController` |
| Notifications (bell + index) | ✅ | `NotificationService`, `NotificationController` |
| **Real-time chat with faculty** (presence, typing, unread) | ✅ | `Messenger/MessageController`, `Conversation`/`Message`, `Events/MessageSent` (Ably) |
| **AI Study Planner** (deadlines → schedule → save as tasks) | ✅ | `StudyPlannerController`, `Domain/Academic/StudyPlannerService` |
| **Learning Analytics / My Progress** (GPA/attendance/test/assignment/activity charts) | ✅ | `LearningAnalyticsController`, `Domain/Analytics/LearningAnalyticsService`, Chart.js |
| **Flashcards** (manual + AI-generated, SM-2 spaced repetition) | ✅ | `FlashcardController`, `Domain/Academic/FlashcardService`, `FlashcardDeck`/`Flashcard` |
| **Leaderboard** (opt-in XP; dept/semester/section) | ✅ | `LeaderboardController`, `Domain/Analytics/LeaderboardService` |
| **Discussions** (section feed: post/comment/like/report) | ✅ | `Community/DiscussionController`, `Domain/Community/DiscussionService` |
| **OCR handwritten notes** (photo → transcribe → save note → RAG-indexed) | ✅ | `NoteController::ocr`, `Domain/Chat/OcrService`, gpt-4o vision |
| **AI practice quizzes** (self-serve, server-graded, retakes, missed → flashcards) | ✅ | `PracticeQuizController`, `Domain/Academic/PracticeQuizService`, `PracticeQuiz`/`PracticeAttempt` |
| **Question-bank self-quizzing** (deterministic practice quizzes sampled from the course bank — no AI) | ✅ | `PracticeQuizController` + `QuestionBankService`, `QuestionBankItem` |
| **Concept mastery map + adaptive review** (class tests .5 / practice .35 / SM-2 recall .15; weakest-first tiles, <60% → one-click practice/flashcards) | ✅ | `Domain/Analytics/ConceptMasteryService`, surfaced on the `progress` page |
| **Group study rooms** (section-scoped live group chat) | ✅ | `StudyRoomController`, `Domain/Community/StudyRoomService`, `conversations.type=group` |
| **Office-hours booking** (browse faculty slots, atomic book/cancel, notifications) | ✅ | `Student/OfficeHoursController`, `Domain/Academic/OfficeHoursService`, `office_hour_slots` |
| **Calendar .ics export + subscribe feed** (Google/Outlook/Apple) | ✅ | `CalendarExportController`, `IcsExportService`, signed `calendar.feed` route |

### Faculty
| Feature | Status | Evidence |
|---|---|---|
| Dashboard / taught sections / course detail | ✅ | `FacultyDashboardController`, `CourseService::courseDetail` |
| Material management (upload/download) | ✅ | `CourseMaterialController` |
| AI teaching assistant (chat + quiz/assignment gen + publish) | ✅ | `AIAssistantController`, `TeachingAssistantService` |
| Timed quizzes / class tests (author manually or AI-generated, timer, marks, auto-grade) | ✅ | `Faculty/ClassTestController`, `ClassTestService` |
| Per-test proctoring layer selection + attempt review dossier (timeline, risk score, recordings) | ✅ | `Faculty/ClassTestController::attempt`, `ClassTestService::attemptReview` |
| Grading (rubric) + **AI-drafted feedback** | ✅ | `GradingController`, `GradingService` |
| **AI-assisted rubric grading** ("Draft grade with AI": per-criterion prefills clamped to maxima + justifications + suggested grade/feedback; editable, never auto-released; heuristic fallback) | ✅ | `GradingController::draftGrade` (`submissions.draft-grade`), `GradingService` |
| **Submission similarity screening** (chunk+embed submission text incl. PDF/DOCX extraction; cosine ≥ 0.82 pairs flagged, amber badge + side-by-side excerpts, section-scoped, recomputed on resubmit) | ✅ | `SubmissionSimilarityService`, `SubmissionTextService`, `ScreenSubmissionSimilarityJob`, `SubmissionEmbedding`/`SubmissionSimilarity` |
| **Anonymous course-feedback windows** (open/close per section, roster notified, results unlock at ≥3 responses, AI "Summarize themes") | ✅ | `Faculty/CourseFeedbackController` (`course-feedback.toggle/summarize`), `CourseFeedbackService` |
| **Peer review per assignment** (toggle; up to 2 load-balanced anonymous reviews per submitter; avg peer ratings in grading) | ✅ | `PeerReviewService`, `PeerReview`, assignment toggle + grading panel |
| **Question bank** (per-course shared bank: manual add, import from class tests w/ duplicate skip, spin selection into a draft test) | ✅ | `Faculty/QuestionBankController` (`question-bank.*`), `QuestionBankService`, `QuestionBankItem` |
| Attendance management | ✅ | `Faculty/AttendanceController` |
| Learning analytics & **at-risk early warning** (4 signals: attendance / missed deadlines / test average / grade, high–watch levels, dashboard stat + message deep-link) | ✅ | `Domain/Analytics/EarlyWarningService`, `FacultyAnalyticsService` |
| **Office hours** (publish slots, manage bookings, notified both ways) | ✅ | `Faculty/OfficeHoursController`, `OfficeHoursService` |
| **Streaming AI assistant** | ✅ | `faculty.ai-assistant.stream`, `AIAssistant.vue` streaming drafts |
| Exam timetable (read) | ✅ | `ExamService::forFaculty` |
| **Real-time chat with students** (presence, typing, unread) | ✅ | `Messenger/MessageController`, `Conversation`/`Message`, `Events/MessageSent` (Ably) |
| **Discussions** (participate + moderate own sections: pin/delete) | ✅ | `Community/DiscussionController`, `DiscussionService::canModerate` |

### Admin
| Feature | Status | Evidence |
|---|---|---|
| User management + RBAC matrix | ✅ | `UserManagementController`, `RoleController` |
| Course catalog + **Sections** + faculty assignment | ✅ | `Admin/CourseController`, `Admin/SectionController` |
| **Course prerequisites** (per-course; only a COMPLETED course satisfies; registration blocked server-side) | ✅ | `Admin/CourseController`, `EnrollmentService`, `create_prerequisites_and_waitlists` migration |
| **Section waitlists** (assign to full section → FIFO queue; drop auto-promotes head to pending placement + notification) | ✅ | `SectionWaitlist`, `EnrollmentService::waitlist/promoteFromWaitlist` |
| **Terms** + registration toggle + rollover | ✅ | `Admin/TermController`, `TermService` |
| Department management (delete-guarded) | ✅ | `Admin/DepartmentController` |
| Knowledge base + approval workflow | ✅ | `Admin/DocumentController` |
| Exam/timetable CRUD | ✅ | `Admin/ExamController` |
| Announcements / broadcast | ✅ | `Admin/AnnouncementController` |
| Analytics · AI settings · System monitor | ✅ | `AnalyticsController`, `SettingsController`, `MonitorController` |
| Exam Security settings (global layer availability + defaults) | ✅ | `Admin/ExamSecurityController`, `exam_security` setting |
| **Discussion moderation queue** (reported posts/comments → dismiss/remove) | ✅ | `Admin/DiscussionModerationController`, `PostReport` |
| Activity log / audit trail | ✅ | `ActivityLog`, `ActivityLogger` |
| Admin transcript editing | ⬜ | not started (low priority; grades flow via grading/enrolment) |

---

## Recently shipped

### Latest wave — July 2026: act, assess & connect (9 features)
Shipped 2026-07 on the same domain-service → thin-controller → Inertia patterns,
all with feature tests:

1. **Agentic AI chat (tool calling).** The student AI chat now takes real actions:
   **8 tools** via LLM function calling — check upcoming deadlines, list my courses,
   list/book/cancel office-hour slots, generate practice quizzes and flashcard
   decks, add planner tasks (`Domain/Chat/Tools/*`, orchestrated by
   `ChatToolRegistry`). A live **"tool activity" trail** renders in the chat
   (spinner → ✓ with deep links to the created quiz/deck/booking). Every action
   executes through the *same* domain services and permission checks as the normal
   UI; works keylessly on the mock provider; student chat only. A segmented
   **⚡ Agent / 💬 Answers only** switcher above the composer sets the mode —
   placeholder, hint line and welcome example prompts follow it, and replies that
   actually acted carry an ⚡ Agent badge. Answers-only is enforced server-side:
   the request's `agent` flag gates `withTools`, so tool definitions are never
   offered to the model.
2. **Submission similarity screening.** Every assignment submission — written text
   plus extracted PDF/DOCX text (`SubmissionTextService`) — is chunked and embedded
   (`ScreenSubmissionSimilarityJob`); pairs within the same assignment at
   **cosine ≥ 0.82** are flagged with matching excerpt pairs. Faculty grading shows
   an amber similarity badge per submission and a side-by-side excerpt comparison
   panel. Resubmissions recompute; comparisons never leave the section. A **review
   signal, not a verdict**.
3. **Concept mastery map + adaptive review.** Deterministic per-topic mastery on
   the student "My Progress" page (`ConceptMasteryService`), blended from
   class-test scores (weight .5), practice-quiz accuracy (.35) and flashcard SM-2
   recall (.15). Tier-colored tiles sorted weakest-first; weak concepts (<60%)
   offer one-click **"Practice this" / "Make flashcards"** that feed the topic
   straight into the AI generators.
4. **Email digests & deadline nudges.** Weekly Monday-morning digest
   (`digests:send-weekly`: deadlines in 7 days, freshly posted grades, booked
   office hours, due flashcards — students with nothing to report are skipped)
   plus a daily **"assignment due soon"** email twin of the in-app reminder
   (`assignments:remind`, max once per student per assignment). Opt-out toggle in
   student Settings; delivered via the admin-configured SMTP
   (`EmailDigestService`).
5. **AI-assisted rubric grading.** A **"Draft grade with AI"** button in the
   faculty grading panel: the AI reads the actual submission (text + file) and
   drafts per-rubric-criterion scores (clamped to each criterion's max) with
   one-line justifications, a suggested overall grade and feedback. Everything is
   an **editable prefill** — faculty review and save; nothing auto-releases.
   Labelled heuristic fallback without an AI key
   (`GradingController::draftGrade`).
6. **Anonymous mid-semester course feedback.** Faculty open/close a feedback
   window per section (roster notified); students submit one revisable 1–5 rating
   + comment. Results (average, star distribution, shuffled anonymized comments)
   unlock only once **≥3 responses** exist, protecting anonymity; an AI
   **"Summarize themes"** button groups comments into Going well / Concerns /
   Suggestions (`CourseFeedbackService`).
7. **Anonymous peer review on assignments.** Per-assignment toggle; each student
   who submits receives up to **2 classmate submissions** to rate and comment on
   (load-balanced, never their own). Anonymous in both directions; reviewees get
   notified and see the feedback on the assignment page; faculty see average peer
   ratings in grading (`PeerReviewService`).
8. **Course prerequisites & section waitlists.** Admins define prerequisites per
   course (only a **COMPLETED** course satisfies one); the registration page shows
   met/unmet badges and registration is blocked server-side until met. Assigning
   students to a full section queues them on a **FIFO waitlist**; any drop
   auto-promotes the head of the queue to a pending placement with a notification;
   students see their queue positions
   (`EnrollmentService::waitlist/promoteFromWaitlist`, `SectionWaitlist`).
9. **Question bank.** Per-course bank of reusable MCQ/true-false questions shared
   by faculty teaching the course (`QuestionBankService`, `QuestionBankItem`): add
   manually, import from existing class tests (duplicates skipped), select
   questions to spin up a **draft class test**, and students can self-quiz with
   **deterministic practice quizzes** sampled from the bank — no AI required.

### Copilot depth & connection wave (8 features)
Shipped on the existing domain-service → thin-controller → Inertia patterns,
all with feature tests (suite then at 209 passing):

1. **Personal-corpus RAG — "chat with my materials."** Student notes (incl. OCR'd)
   and file-backed course materials are indexed as hidden **shadow documents**
   (`documents.source_type = note|material`, owner/section-scoped) and flow through
   the *same* chunk → embed → retrieve → cite pipeline as the library. A global
   scope (`Document::LIBRARY_SCOPE`) keeps shadows out of every admin/library
   surface; retrieval scope = library-visible ∪ own notes ∪ enrolled/teaching-section
   materials. Sync jobs fire on note/material mutations; backfill via
   `php artisan rag:sync-personal [--user=]`. Citations label sources
   "Personal Note" / "Course Material" automatically.
2. **Streaming AI responses (SSE).** `chatStream()` on both providers (OpenAI
   `stream:true` + word-streaming mock), `RagChatService::answerStream`, SSE
   endpoints for student chat and the faculty assistant (`delta` → `done` events,
   same payload as the JSON endpoints), fetch-based client parser
   (`resources/js/lib/sse.js`) with live-typing drafts in both chat UIs. Token
   usage accounting preserved via `stream_options.include_usage`.
3. **AI practice quizzes.** Students generate MCQ/true-false quizzes on any topic
   (same generator faculty use), take them unproctored, get instant **server-side**
   grading (answers never reach the client pre-submit), retake freely, and convert
   missed questions into a flashcard deck (`source: practice`) for SM-2 review.
4. **At-risk early warning.** `EarlyWarningService` flags students on 4 signals —
   attendance <75% (≥4 sessions), ≥2 missed published-assignment deadlines,
   class-test average <50%, grade D+/D/D−/F — with **high** (≥2 signals) / **watch**
   levels. Surfaced on the faculty Analytics report (message deep-link per student)
   and as a faculty dashboard stat (unique flagged students).
5. **Semantic global search (⌘K).** One palette searches pages + content:
   a semantic "Knowledge" group over the user's RAG corpus plus lexical groups
   (courses, assignments, discussions, own chat history deep-linked to the exact
   message, users for admins). Debounced, permission-scoped, keyboard-navigable.
6. **Group study rooms.** Section-scoped group chats built *on* the messenger:
   `conversations.type = group` + title/section/creator; membership = participants
   pivot; the same message endpoints, Ably channel auth and polling fallback work
   unchanged. Guards keep group rooms out of the 1:1 surface (`betweenOrCreate`
   scoped `->direct()`; overview filters direct-only). Self-serve join for
   classmates; last member out deletes the room.
7. **Office-hours booking.** Faculty publish single-capacity slots; students of
   their sections book/cancel with an **atomic** claim (conditional UPDATE → 409 on
   race). In-app notifications both ways (`NotificationType::OFFICE_HOURS`); booked
   meetings appear on the student calendar.
8. **ICS calendar export + subscribe feed.** RFC 5545 export of the unified
   calendar (deadlines, exams, tasks, office hours): authenticated `.ics` download
   plus a **signed, sessionless feed URL** (throttled) that Google/Outlook/Apple
   Calendar can poll — minted per-user on the Calendar page.

### Study & community suite (Planner · Analytics · Flashcards · Leaderboard · Discussions · OCR)
Six student-facing features layered on the existing domain/RBAC/Inertia patterns:
- **AI Study Planner** (`StudyPlannerService`) turns the student's real deadlines
  (assignments/exams/class tests) into a schedule and saves chosen sessions as `Task`s.
- **Learning Analytics / "My Progress"** (`LearningAnalyticsService` + Chart.js
  `StatChart.vue`) charts GPA, attendance, class-test & assignment trends, activity.
- **Flashcards** (`flashcard_decks`/`flashcards`, `FlashcardService`, `generateFlashcards()`
  on `TeachingAssistantService`) — manual or AI-generated decks with **SM-2** review.
- **Leaderboard** (`LeaderboardService`, `users.leaderboard_opt_in/alias`) — **opt-in**,
  aliasable, gamified XP (from tests/assignments/attendance) scoped by department /
  semester / section.
- **Discussions** (`Domain/Community`, `posts`/`post_comments`/`post_reactions`/`post_reports`)
  — a section = a group (membership from enrolment/teaching); faculty moderate their
  sections, admins work a global report queue.
- **OCR handwritten notes** — extends `AIProviderInterface` with `extractText()`
  (gpt-4o vision, mock fallback); the Notes page scans a photo → editable text → saved note.

New permissions: `view_discussions`, `post_discussion`, `moderate_discussions` (Community
category). Demo data for all of the above is seeded (see `docs/seeder-plan.md`).

### Real-time student↔faculty messaging
A direct, **real-time chat** between a student and a faculty member they share a section
with — distinct from the AI tutor. 1:1 conversations are persisted (`conversations` /
`conversation_user` / `messages`), eligibility is enforced by `User::canMessage()` (must
share a section) and a `ConversationPolicy`. Delivery is **DB-first, then broadcast**:
`MessageSent` (`ShouldBroadcastNow`) publishes to a private per-conversation channel over
**Ably**, with a low-frequency polling fallback so messages still arrive if the socket
drops. Messenger-style UX: **online presence** via a lightweight heartbeat
(`PresenceController`, `last_seen_at`, 2-min active window), a **typing indicator** over
Ably whispers, and a conversation list that **reorders to newest** with last-message
preview and **unread counts** (cleared on read). Text-only, and built to fit **cPanel
shared hosting** — synchronous broadcast (no queue worker) on the free Ably tier
(≈200 concurrent connections). Backed by `MessengerTest`. To lift the connection cap,
upgrade the Ably plan or self-host Laravel Reverb on a VPS — the Echo client is unchanged.

### Timed quizzes / class tests (online exam system)
Faculty author timed quizzes/class tests on a section — title, instructions/rules,
duration (countdown timer), MCQ / True-False questions + answer options, marks, and an
optional availability window. Questions can be **written by hand or generated with AI**
(`ClassTestService::generateQuestions`, reusing `TeachingAssistantService`) and then edited
before publishing. Students see the rules and duration on a pre-start screen, click
**Start** to begin the timer — the countdown is anchored to each student's start, not to
publish time — enter fullscreen with anti-cheat (tab-switch / fullscreen-exit detection;
warn once then disqualify), and answer within the allotted time. On submit (or when time
expires) the system **auto-grades** the objective questions and returns an instant score and
answer review; a disqualified attempt scores 0. Publishing a quiz notifies the section
roster via the existing `NotificationService`.

### Layered exam-security / proctoring (per-test, admin-gated)
Proctoring is a **config-driven layer system**, not a fixed set of hard-coded checks.
`config/exam_security.php` declares 14 independent layers; `ExamSecurityService` resolves the
*effective* set for a test as **config default → admin global gate → per-test faculty
selection**. Layers: fullscreen enforcement, tab-switch / focus-loss detection, clipboard &
context-menu block, one-question-at-a-time, disable-going-back, randomise question order,
randomise answer options, identity watermark, browser fingerprint, behaviour logging, risk
scoring, AI assessment-integrity notice, **webcam recording** and **screen recording**.

- **Faculty** tick the layers per test on the authoring form (stored in
  `class_tests.security_config` JSON); an **admin** decides which layers are available at all
  and which are on by default (Admin → **Exam Security**, persisted to the `exam_security`
  setting).
- **Evidence trail:** typed events land in `class_test_events` (replacing the old bare counter);
  the attempt carries the fingerprint, session, IP, user-agent and a computed 0–100 **risk
  score** with contributing factors. Webcam/screen are captured with `MediaRecorder`, uploaded
  in chunks to the private disk, and indexed in `class_test_recordings`.
- **Review:** the faculty results screen adds risk / flagged columns and a per-student **Review**
  dossier (`faculty.class-tests.attempt`) — identity + fingerprint, risk factors, a behaviour
  timeline, and in-browser recording playback (chunks are stitched client-side because
  `MediaRecorder` timeslices are only valid webm when concatenated in order).
- Media layers require explicit student consent before the exam starts; the integrity notice is
  pinned in the exam's sticky header for the whole attempt.

### Admin-assigned → student-confirmed registration (P2.6)
The enrollment model was redesigned: **students no longer self-pick** courses. The admin
**assigns** a student to a specific section (pivot `pending`, seat reserved), and the
student's Registration page shows **only** assigned sections to **confirm** with one click
(`pending` → `enrolled`). Section labels are constrained to a **A–J dropdown**, normalized
uppercase, **unique per course+term**. Pending placements grant **zero** access until
confirmed (centrally gated via `User::enrolledSectionIds()`). Backed by
`SectionAssignmentTest`, `SelfRegistrationTest`, `EnrollmentTest`. See
[PROJECT_ANALYSIS.md §2.3](PROJECT_ANALYSIS.md#23-enrollment-admin-assigns--student-confirms-two-step).

### Terms & Sections academic structure (P2.6)
Introduced `terms` and `sections`: a course is the catalog entry, a **section** is the
offering (instructor/schedule/capacity/roster) within a **term**. Materials, assignments,
exams and attendance are section-scoped (`BelongsToSection`).

### Faculty panel functionalization
Full audit removed the last mock/dead items on the AI Assistant page (free-text topics,
real export, **publish → real `Assignment` + student notifications**, real resources).

### P1 / P2 / P2.5 bands (historical)
Attendance · admin frontends wired to real props · Faculty Course/Material CRUD ·
Notifications · Transcript · Exams · Faculty analytics · Calendar/Notes/Tasks · Department
management — all shipped end-to-end with tests.

---

## Incomplete Tasks

Tracked, not-yet-done work (excluding the larger future features below):

| # | Task | Priority | Notes |
|---|---|---|---|
| 1 | **Strip residual frontend mock data** | Low | A few admin screens (`Admin/Analytics.vue`, `Admin/Dashboard.vue` health/insight figures, `Admin/AISettings.vue` defaults) and the `Student/Chat` initial greeting still carry hardcoded display values on top of real backends. Wire to props or remove. |
| 2 | **Delete dead route stubs** | Low | `routes/{student,faculty,admin}.php` are unregistered; remove to avoid confusion. |
| 3 | **Admin transcript editing** | Low | Grades already flow through grading + enrolment; a direct admin edit screen is unbuilt. |
| 4 | **Semester promotion on term rollover** | Low | `TermService::close()` could auto-advance student `semester` on rollover — designed, not greenlit. |
| 5 | **Deeper RAG/chat/document feature tests** | Medium | Coverage is strongest on RBAC/academic; add pipeline tests for embed→retrieve→cite and the approval→process job. |
| 6 | **Scale the vector store** | Medium (scale) | MySQL JSON + PHP cosine is fine for demos; swap a managed vector DB behind `RetrievalService`/`EmbeddingService` beyond ~100k vectors. |

---

## Future Plans / Upcoming Features

### A. New product features (requested)

> ✅ **Real-time student↔faculty chat has shipped** — see [Recently shipped](#recently-shipped).

#### 1. 🔴 Telegram / WhatsApp notifications
Deliver important academic events to students' phones via **Telegram and/or WhatsApp**,
since students aren't always on the website.
- **Triggers:** assignment published/updated, quiz scheduled, syllabus updates, course
  announcements, and other student-relevant events.
- **Likely build:** add external channels behind `NotificationService` (a `notify()`
  fan-out to in-app + Telegram Bot API / WhatsApp Cloud API); per-user opt-in + linked
  chat IDs in user `preferences`; queue the sends. Reuses the existing `NotificationType`
  events as the trigger surface.

#### 2. 🔴 Digital library with AI assistant
A **library module** of academic books/resources, plus an **AI assistant scoped to the
library** so students get answers grounded specifically in the available library content.
- **Scope:** browsable/searchable library catalog; a library-scoped RAG assistant.
- **Likely build:** extend the existing **document → chunk → embed → retrieve → cite**
  pipeline with a library corpus/namespace; a library-mode chat that retrieves only from
  library resources. Builds directly on the current RAG engine and `ChatMode`.

### B. P3 advanced band (deferred, infra-dependent)
- ✅ **Streaming chat** — **shipped** (copilot depth & connection wave): token-by-token
  SSE on the student chat and faculty assistant.
- **Voice I/O + TTS/STT** — browser mic capture + speech APIs (the voice UI is a mock)
  — **intentionally on hold**.
- **Lecture-audio transcription · realtime presence / websocket expansion ·
  completion certificates** — designed, **intentionally on hold** (do not treat as
  shipped).
- **Predictive analytics / recommendation engine** — ML over the now-rich academic data
  (the at-risk early-warning signals are a first, rule-based step).
- **Alternate LLM providers** — Gemini / Local-LLM (config stubs today; only OpenAI + Mock built).
- **Document versioning** and **conversation memory/summarization** — empty domain dirs.
- **Admin at-risk rollup** — per-department early-warning counts on the admin
  analytics page (small follow-up on `EarlyWarningService`).
