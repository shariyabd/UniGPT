# UniGPT — Project Status, Backlog & Roadmap

> The live status tracker. **Source of truth = code.** For architecture and logic see
> [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md); for layout see [DIRECTORY_TREE.md](DIRECTORY_TREE.md).
>
> **Last updated:** 2026-06-18
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
| RBAC (40 perms, pivots, `expires_at`) | ✅ | `Enums/Permission`, `RBACSeeder`, `PermissionMiddleware` |
| AI Chat (sessions, messages, modes, pin/archive) | ✅ | `Domain/Chat/Services/ChatService` |
| RAG (chunk→embed→cosine retrieve→cite) | ✅ | `Domain/RAG/*`, MySQL vector store |
| Multi-LLM provider (OpenAI + mock fallback) | ✅ | `Infrastructure/AI/*`; mock = zero-key default |
| Document KB + approval + embed pipeline | ✅ | `DocumentService`, `ProcessDocumentJob` |
| Saved answers / bookmarks | ✅ | `Student/SavedAnswerController` |
| Streaming / Voice / TTS / STT / predictive | ⬜ | P3 — chat returns full response; voice is a UI mock |

### Student
| Feature | Status | Evidence |
|---|---|---|
| Dashboard (real stats/links/actions) | ✅ | `StudentDashboardController` |
| RAG tutor chat + saved answers | ✅ | `ChatController`, `SavedAnswerController` |
| Roadmap / GPA / progress | ✅ | `roadmap()` (real enrollment data) |
| **Registration (assign → confirm)** | ✅ | `RegistrationController`, `EnrollmentService::assignedFor/enroll` |
| Course materials (persisted completion, gated download) | ✅ | `CourseService::studentMaterials` |
| Assignments + submissions | ✅ | `Student/AssignmentController`, `SubmissionService` |
| Attendance · Transcript · Exams · Calendar | ✅ | Attendance/Transcript/Exam/Calendar services |
| Notes · Tasks (owner-scoped) | ✅ | `NoteController`, `TaskController` |
| Notifications (bell + index) | ✅ | `NotificationService`, `NotificationController` |

### Faculty
| Feature | Status | Evidence |
|---|---|---|
| Dashboard / taught sections / course detail | ✅ | `FacultyDashboardController`, `CourseService::courseDetail` |
| Material management (upload/download) | ✅ | `CourseMaterialController` |
| AI teaching assistant (chat + quiz/assignment gen + publish) | ✅ | `AIAssistantController`, `TeachingAssistantService` |
| Grading (rubric) + **AI-drafted feedback** | ✅ | `GradingController`, `GradingService` |
| Attendance management | ✅ | `Faculty/AttendanceController` |
| Learning analytics & at-risk flagging | ✅ | `FacultyAnalyticsService` |
| Exam timetable (read) | ✅ | `ExamService::forFaculty` |

### Admin
| Feature | Status | Evidence |
|---|---|---|
| User management + RBAC matrix | ✅ | `UserManagementController`, `RoleController` |
| Course catalog + **Sections** + faculty assignment | ✅ | `Admin/CourseController`, `Admin/SectionController` |
| **Terms** + registration toggle + rollover | ✅ | `Admin/TermController`, `TermService` |
| Department management (delete-guarded) | ✅ | `Admin/DepartmentController` |
| Knowledge base + approval workflow | ✅ | `Admin/DocumentController` |
| Exam/timetable CRUD | ✅ | `Admin/ExamController` |
| Announcements / broadcast | ✅ | `Admin/AnnouncementController` |
| Analytics · AI settings · System monitor | ✅ | `AnalyticsController`, `SettingsController`, `MonitorController` |
| Activity log / audit trail | ✅ | `ActivityLog`, `ActivityLogger` |
| Admin transcript editing | ⬜ | not started (low priority; grades flow via grading/enrolment) |

---

## Recently shipped

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

#### 1. 🔴 Real-time chat between students and faculty
A direct, **real-time messaging** channel so students can message faculty members inside
the platform (distinct from the AI tutor chat).
- **Scope:** 1:1 (and optionally course/section) threads, presence/typing, unread counts.
- **Likely build:** a `conversations` / `messages` schema; Laravel broadcasting
  (Reverb/Pusher) + Echo on the Vue side; reuse `NotificationService` for offline pings.
- **Note:** today cross-role communication is **asynchronous** via in-app notifications;
  this adds a synchronous channel.

#### 2. 🔴 Telegram / WhatsApp notifications
Deliver important academic events to students' phones via **Telegram and/or WhatsApp**,
since students aren't always on the website.
- **Triggers:** assignment published/updated, quiz scheduled, syllabus updates, course
  announcements, and other student-relevant events.
- **Likely build:** add external channels behind `NotificationService` (a `notify()`
  fan-out to in-app + Telegram Bot API / WhatsApp Cloud API); per-user opt-in + linked
  chat IDs in user `preferences`; queue the sends. Reuses the existing `NotificationType`
  events as the trigger surface.

#### 3. 🔴 Digital library with AI assistant
A **library module** of academic books/resources, plus an **AI assistant scoped to the
library** so students get answers grounded specifically in the available library content.
- **Scope:** browsable/searchable library catalog; a library-scoped RAG assistant.
- **Likely build:** extend the existing **document → chunk → embed → retrieve → cite**
  pipeline with a library corpus/namespace; a library-mode chat that retrieves only from
  library resources. Builds directly on the current RAG engine and `ChatMode`.

### B. P3 advanced band (deferred, infra-dependent)
- **Streaming chat** — token-by-token SSE/transport (chat currently returns the full response).
- **Voice I/O + TTS/STT** — browser mic capture + speech APIs (the voice UI is a mock).
- **Predictive analytics / recommendation engine** — ML over the now-rich academic data.
- **Alternate LLM providers** — Gemini / Local-LLM (config stubs today; only OpenAI + Mock built).
- **Document versioning** and **conversation memory/summarization** — empty domain dirs.
</content>
