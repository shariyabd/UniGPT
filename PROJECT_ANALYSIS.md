# UniGPT — Comprehensive Project Analysis

> **Generated:** 2026-06-14
> **Scope:** Full architectural, business-logic, and feature analysis across all three roles (Student, Faculty, Administrator), with a completion-status report and a combined project maturity assessment.
> **Source of truth:** The codebase itself. Where the repo's own marketing docs (`README.md`, `PHASE_1_COMPLETE.md`, etc.) contradict the code, the code wins.

---

## 1. Project Abstraction / Executive Summary

### 1.1 What this project is

**UniGPT** (codebase folder `uni-chat`) is a **university AI academic copilot** — a web application intended to give students, faculty, and administrators a single AI-assisted platform for academic work. The vision is a **RAG-backed (Retrieval-Augmented Generation) chat assistant** grounded in a university's own documents (handbooks, syllabi, lecture notes, policies), surrounded by **role-based dashboards**:

- **Students** chat with an AI tutor, follow a learning roadmap, save useful answers, and browse course materials.
- **Faculty** manage courses, grade assignments, and use an AI teaching assistant to generate quizzes/assignments.
- **Administrators** manage users and roles, curate and approve the document knowledge base, configure the AI providers, and monitor the system.

### 1.2 Technology stack

| Layer | Technology |
|---|---|
| Backend framework | **Laravel 11** (PHP) |
| Frontend | **Inertia.js 2 + Vue 3** SPA (not Blade-rendered pages, not Livewire) |
| Build tooling | **Vite + Tailwind CSS** |
| Routing bridge | **Ziggy** (exposes named Laravel routes to Vue) |
| Database | **MySQL** (`uni_gpt`) — SQLite-in-memory test config is commented out |
| Notifications | `vue-toastification` |
| Architecture style | **Domain-Driven Design (DDD)** layout — `app/Domain/*`, `app/Infrastructure/*` |

> ⚠️ **Documentation caveat:** The repo's root markdown docs describe an *aspirational* Phase-1 plan and are partly inaccurate (e.g. they claim a Livewire UI — it is actually Inertia + Vue; Livewire is installed but unused). The AI/RAG/VectorDB layers they describe are **not implemented**.

### 1.3 Main goal & purpose

To act as an **AI academic copilot** for a university, where AI answers are **grounded in institution-approved documents** (RAG) rather than generic LLM output — reducing hallucination and giving cited, trustworthy academic guidance. It layers role-specific productivity tools on top of that chat core.

### 1.4 The problem it solves

- **Students** get instant, context-aware academic help (course material Q&A, exam prep, learning-path guidance) without waiting on faculty office hours, with answers traceable to real sources.
- **Faculty** offload repetitive work — drafting quizzes, assignments, and grading support — to an AI teaching assistant, and gain visibility into student progress.
- **Administrators** govern the knowledge base (which documents the AI is allowed to cite), manage who can access what, and tune/monitor the AI system centrally.
- **The institution** gets a controlled, auditable AI layer over its own academic content instead of staff/students using ungoverned external chatbots.

### 1.5 Primary users & stakeholders

| Stakeholder | Interest |
|---|---|
| **Students** | Day-to-day AI tutoring, materials, progress tracking |
| **Faculty / Instructors** | Course management, AI-assisted teaching, grading, student analytics |
| **Administrators / IT** | User & role governance, document approval, AI configuration, system monitoring |
| **University leadership** | A governed, branded AI platform with usage analytics |

### 1.6 Current reality (one-line verdict)

**A polished, design-complete front-end prototype on top of a fully functional authentication + RBAC backend — but with essentially none of the AI/RAG/chat/document business logic implemented.** The platform can authenticate and authorize three roles and render every screen; almost all screen *content* is hardcoded mock data.

---

## 2. System Architecture Overview

### 2.1 Request flow

1. A single root Blade view `resources/views/app.blade.php` boots `resources/js/app.js`.
2. Controllers return `Inertia::render('SomePage', [...props])`; pages auto-resolve from `resources/js/pages/**/*.vue` by name.
3. Named routes reach Vue via **Ziggy** (`route()` helper); flash messages via shared props + `vue-toastification`.

### 2.2 Routing model (important gotcha)

- **All live web routes are in [routes/web.php](routes/web.php).**
- `routes/student.php`, `routes/faculty.php`, `routes/admin.php` exist but are **empty skeletons NOT registered** in [bootstrap/app.php](bootstrap/app.php) — editing them does nothing. They are dead scaffolding.
- `routes/api.php` is effectively empty — **there are no API endpoints**, no chat-message POST endpoint, no WebSocket/broadcast routes.

### 2.3 Authentication & RBAC (custom, not Laravel scaffolding)

- `role` / `permission` middleware aliases registered in [bootstrap/app.php](bootstrap/app.php) → [RoleMiddleware](app/Http/Middleware/RoleMiddleware.php) / [PermissionMiddleware](app/Http/Middleware/PermissionMiddleware.php).
- Guests redirect to `/login`; **deactivated users (`is_active = false`) are force-logged-out** by middleware.
- Login **requires the user to pick a role** (`student|faculty|admin`) and pass `hasRole()` for it; rate-limited per email+IP (5 attempts).
- Many-to-many roles/permissions with **temporal role assignment** (`role_user.expires_at`) — the default `roles()` relation filters out expired roles; `allRoles()` does not.
- The **User model lives at [app/Domain/User/Models/User.php](app/Domain/User/Models/User.php)** (not `app/Models`). It owns all RBAC helpers and goes through [UserManagementService](app/Domain/User/Services/UserManagementService.php) backed by `EloquentUserRepository`.

### 2.4 DDD layer status at a glance

| Layer | Component | Status |
|---|---|---|
| Domain | **User** | ✅ Fully implemented (RBAC, service, repository, provider) |
| Domain | Chat, RAG, Academic, Analytics | ❌ Empty directory scaffolding |
| Infrastructure | **Repositories/EloquentUserRepository** | ✅ Implemented |
| Infrastructure | AI, VectorDB, FileStorage, Speech | ❌ Empty |
| Services (`app/Services/`) | — | ❌ Empty directory |
| Config | `ai.php`, `rag.php`, `vector.php` | ⚠️ Exist & well-formed but **consumed by nothing** |
| Controllers | Auth, Dashboards | ✅ Exist; dashboards mostly serve mock data |
| Controllers | `Api/` | ❌ Empty |
| Composer deps | OpenAI / Anthropic / vector / embedding libs | ❌ **None installed** |

---

## 3. Role-by-Role Analysis

Each role is analyzed end-to-end: routes → controller → page → backing logic, then summarized as a feature list with permissions and dependencies.

---

## 3.A STUDENT

### 3.A.1 Workflows & responsibilities

The Student is the primary end-user. Intended workflows:

1. **Log in** (selecting "student" role) → land on the Student Dashboard.
2. **Chat** with the AI tutor in academic/exam/assignment modes; receive cited, confidence-scored answers; save useful ones.
3. **Follow a learning roadmap** across multiple semesters, tracking module progress and CGPA.
4. **Browse course materials & documents** (lecture notes, syllabi, handbooks).
5. **Review saved answers**, organize them, and manage personal profile/settings.

### 3.A.2 Accessible routes & pages

Middleware `['auth', 'role:student']`, unprefixed:

| Route | Controller method | Page | Real backend? |
|---|---|---|---|
| `GET /dashboard` | `StudentDashboardController::index` | `Student/Dashboard` | Partial — loads real user roles/permissions; stats are mock |
| `GET /chat` | `::chat` | `Student/Chat` | ❌ Stub — renders page, no data, no AI |
| `GET /saved` | `::savedAnswers` | `Student/SavedAnswers` | ❌ Stub — count hardcoded (12) |
| `GET /roadmap` | `::roadmap` | `Student/Roadmap` | ❌ Stub — progress hardcoded |
| `GET /documents` | `::documents` | `Student/Documents` | ❌ Stub — returns `[]` |
| `GET /materials` | `::materials` | `Student/Materials` | ❌ Stub — returns `[]` |
| `GET /profile` | `::profile` | `Student/Profile` | ✅ Loads real authenticated user + roles |
| `GET /settings` | `::settings` | `Student/Settings` | ❌ Stub — hardcoded default preferences |

### 3.A.3 What the student can actually do today

- ✅ Register / log in / log out as a student; reach a working role-gated area.
- ✅ View their real profile (name, IDs, roles).
- ✅ Navigate a **complete, polished UI** for chat, roadmap, saved answers, materials, documents.
- ❌ **Cannot** actually chat with an AI (no backend, no AI provider, no message persistence).
- ❌ **Cannot** see real materials/documents (queries return empty; UI shows hardcoded items).
- ❌ **Cannot** save a real answer or track real roadmap progress (all mock).

### 3.A.4 Interaction with other roles

- Consumes documents that **Admins upload/approve** and **Faculty upload** (knowledge base) — *intended, not wired*.
- Faculty view **student progress/analytics** generated from student activity — *intended, not wired*.
- Admin governs the student's account status, role, and permissions (real).

### 3.A.5 Student feature list

**Core features (intended):** AI tutor chat (modes: general/academic/research/exam-prep/assignment-help/career-guidance), saved answers with folders/tags, multi-semester learning roadmap, course materials & document library.
**Supporting features:** Dashboard stats (attendance, GPA, streaks), recent conversations, upcoming deadlines, profile, preferences/settings, study tips.
**Permissions (seeded):** `VIEW_DOCUMENTS`, `DOWNLOAD_DOCUMENT`, `VIEW_COURSES`, `ENROLL_COURSE`, `VIEW_ASSIGNMENTS`, `SUBMIT_ASSIGNMENT`, `USE_AI_CHAT`, `VIEW_CHAT_HISTORY`, `DELETE_CHAT`, `VIEW_OWN_ANALYTICS`.
**Restrictions:** No upload, no user management, no analytics beyond own, no AI configuration.
**Dependencies:** Chat/RAG domain (missing), Documents/Academic domain (missing), Analytics domain (missing).

---

## 3.B FACULTY

### 3.B.1 Workflows & responsibilities

Faculty are instructors who manage teaching and leverage AI to reduce workload:

1. **Log in** (role "faculty") → Faculty Dashboard with course/student overview.
2. **Use the AI teaching assistant** to generate quizzes, assignments, and teaching resources.
3. **Grade** assignment submissions, applying rubrics and feedback.
4. **View student progress/analytics** for their courses.

### 3.B.2 Accessible routes & pages

Middleware `['auth', 'role:faculty']`, prefix `faculty.`:

| Route | Controller method | Page | Real backend? |
|---|---|---|---|
| `GET /faculty/dashboard` | `FacultyDashboardController::index` | `Faculty/Dashboard` | Partial — real roles/permissions; stats/courses/tasks mock |
| `GET /faculty/ai-assistant` | `::aiAssistant` | `Faculty/AIAssistant` | ❌ Stub |
| `GET /faculty/grading` | `::grading` | `Faculty/Grading` | ❌ Stub — pending assignments `[]` |
| `GET /faculty/courses/{course}/grading` | `::grading($courseId)` | `Faculty/Grading` | ❌ Stub — accepts course id, returns empty |

> Note: `Faculty/CourseDetail.vue` exists as a built page but has **no route** wired to it.

### 3.B.3 What faculty can actually do today

- ✅ Log in, reach a role-gated faculty area, view real profile/roles.
- ✅ Navigate complete UIs for dashboard, AI assistant (quiz/assignment generators), grading, course detail.
- ❌ **Cannot** generate real quizzes/assignments (no AI backend).
- ❌ **Cannot** grade real submissions (no Academic/Assignment models or tables exist).
- ❌ **Cannot** see real student analytics (all hardcoded; Analytics domain empty).

### 3.B.4 Interaction with other roles

- Provides materials/documents consumed by **Students** — *intended*.
- Their uploaded content may require **Admin approval** before entering the knowledge base — *intended*.
- **Admin** manages faculty accounts, roles, and department assignment (real).

### 3.B.5 Faculty feature list

**Core features (intended):** AI teaching assistant (quiz generator, assignment creator, teaching resources, chat), assignment grading with rubrics, course management, student progress/analytics.
**Supporting features:** Dashboard (active courses, total students, pending assignments, AI query counts), recently-graded list, announcements, course detail (roster, attendance, grade distribution).
**Permissions (seeded):** All student permissions **plus** `UPLOAD_DOCUMENT`, `CREATE_COURSE`, `UPDATE_COURSE`, `CREATE_ASSIGNMENT`, `GRADE_ASSIGNMENT`, `VIEW_DEPARTMENT_ANALYTICS` (16 total).
**Restrictions:** No user management, no AI provider configuration, no system administration, department-scoped analytics only.
**Dependencies:** Chat/RAG, Academic (courses/assignments/enrollment), Analytics domains — **all missing**.

---

## 3.C ADMINISTRATOR

### 3.C.1 Workflows & responsibilities

Admins govern the platform:

1. **Log in** (role "admin") → Admin Dashboard (system overview).
2. **Manage users & roles/permissions** (the one area with real backend logic).
3. **Curate the knowledge base** — upload documents, browse the library, **approve/reject** pending documents.
4. **Configure the AI** (providers, models, parameters, RAG settings).
5. **View analytics** and **monitor system health**.

### 3.C.2 Accessible routes & pages

Middleware `['auth', 'role:admin']`. **7 of 8 routes are inline closures** rendering Inertia pages directly (no controller):

| Route | Handler | Page | Real backend? |
|---|---|---|---|
| `GET /admin/dashboard` | `AdminDashboardController::index` | `Admin/Dashboard` | ✅ Partial — real user statistics via `UserManagementService`; activities mock |
| `GET /admin/users` | inline closure | `Admin/UserManagement` | ⚠️ Partial — closure passes some real data; page also shows mock |
| `GET /admin/documents/upload` | inline closure | `Admin/DocumentUpload` | ❌ UI only |
| `GET /admin/documents` | inline closure | `Admin/DocumentLibrary` | ❌ UI only |
| `GET /admin/approvals` | inline closure | `Admin/Approvals` | ❌ UI only |
| `GET /admin/analytics` | inline closure | `Admin/Analytics` | ❌ UI only |
| `GET /admin/monitor` | inline closure | `Admin/SystemMonitor` | ❌ UI only |
| `GET /admin/settings` | inline closure | `Admin/AISettings` | ❌ UI only |

> **Dead code:** `AdminDashboardController::userManagement()` is fully implemented (paginated users + roles via the service) but **no route points to it** — `/admin/users` uses its own inline closure instead.

### 3.C.3 What admins can actually do today

- ✅ Log in, reach the admin area, view a dashboard with **real aggregate user statistics** (counts by role, active users, new registrations, online users).
- ✅ **User management is genuinely functional at the service/repository layer**: create users (with role assignment), update, deactivate, search, paginate, filter by department — though the routed page mixes in mock data and the dedicated controller method is unrouted.
- ❌ Document upload/library/approvals: **UI only** — no document tables, no storage handler, no approval persistence.
- ❌ Analytics & system monitor: **all hardcoded** (no Analytics domain, no real metrics source).
- ❌ AI settings: **UI only** — `config/ai.php`/`rag.php`/`vector.php` exist but nothing reads them; no provider integration to configure.

### 3.C.4 Interaction with other roles

- **Owns the lifecycle** of Student and Faculty accounts: creation, role assignment (with expiry), activation/deactivation, department assignment — **real** via `UserManagementService`.
- Approves the documents that feed **Student** and **Faculty** knowledge base — *intended*.
- Configures the AI that all roles consume — *intended*.

### 3.C.5 Administrator feature list

**Core features:** User management (✅ real at service layer), role/permission management, document upload & library, document approval workflow, AI provider/RAG configuration, analytics, system monitoring.
**Supporting features:** Dashboard system overview (✅ real user stats), recent activity log, system health, pending-approval queue.
**Permissions (seeded):** **All 24 permissions** including `MANAGE_USER_ROLES`, `APPROVE_DOCUMENT`, `CONFIGURE_AI`, `MANAGE_SYSTEM`, `MANAGE_PERMISSIONS`, `VIEW_ALL_ANALYTICS`, `CONFIGURE_SETTINGS`.
**Restrictions:** None within the app (top of role hierarchy).
**Dependencies:** User domain (✅ present); Documents/RAG, Analytics, AI Infrastructure (❌ missing) for everything except user management.

---

## 4. Role Relationships

```
                 ┌─────────────────────────────────────────────┐
                 │              ADMINISTRATOR                   │
                 │  • Creates/manages Student & Faculty accounts│
                 │  • Assigns roles/permissions (with expiry)   │
                 │  • Approves documents → knowledge base       │
                 │  • Configures AI for everyone                │
                 └───────────────┬──────────────┬──────────────┘
                                 │ governs       │ governs
                ┌────────────────▼───┐      ┌────▼─────────────────┐
                │      FACULTY        │      │      STUDENT         │
                │ • Uploads materials │─────▶│ • Consumes materials │
                │ • AI teaching tools │ docs │ • AI tutor chat      │
                │ • Grades work       │      │ • Roadmap / saved    │
                │ • Views student     │◀─────│ • Generates activity │
                │   progress          │ data │   & analytics        │
                └─────────────────────┘      └──────────────────────┘
```

- **Admin → Faculty/Student:** account governance, document approval, AI config (account governance is *real*; the rest *intended*).
- **Faculty → Student:** materials/document provision, progress monitoring (*intended*).
- **Student → Faculty:** activity generates analytics faculty consume (*intended*).
- **Shared dependency:** all three depend on the **Chat/RAG core** and the **document knowledge base**, neither of which is implemented.

---

## 5. Completion Status Report

### 5.1 ✅ Fully completed / functional

| Area | Detail |
|---|---|
| **Authentication** | Login/register/logout, role-selection at login, `.edu` email validation, password-reset flow, per-email+IP rate limiting, demo-login with auto-seeding |
| **RBAC core** | Roles, Permissions, Departments, `role_user` & `permission_role` pivots; many-to-many with **temporal (`expires_at`) assignments**; full helper API (`hasRole`, `hasPermission`, `assignRole`, `syncRoles`, `getPrimaryRole`, `getDashboardRoute`, `isStudent/Faculty/Admin`) |
| **Role middleware** | Route gating by role, force-logout of deactivated users, unauthorized-attempt logging |
| **User management (service layer)** | `UserManagementService` + `EloquentUserRepository`: statistics, pagination with roles, create/update/deactivate, search, department filter |
| **Admin user statistics** | Real aggregate counts surfaced on the Admin Dashboard |
| **Enums** | `UserRole`, `Permission` (24), `ChatMode`, `DocumentStatus`, `Language`, `ConfidenceLevel` — defined and partly used |
| **Seeders** | `RBACSeeder` seeds 3 roles, 24 permissions, 10 departments, 3 demo users |
| **Frontend UI** | **Every screen for all three roles is built and polished** (responsive, dark mode, animations, modals, forms, tables) |

### 5.2 ⚠️ Partially completed

| Area | What works | What's missing |
|---|---|---|
| **Student Dashboard** | Real user/roles/permissions loaded | Stats (attendance, GPA, streak), recent chats, deadlines all mock |
| **Faculty Dashboard** | Real user/roles loaded | Courses, students, tasks, AI-query counts mock |
| **Admin Dashboard** | Real user statistics | Recent activities, system health mock |
| **Admin User Management** | Service/repository fully real; closure passes some live data | Page mixes in mock rows; the real `userManagement()` controller method is **unrouted** |
| **Profile** | Real authenticated user shown | Editing/persistence not wired |
| **Password reset** | `store`/`update` logic real | Forgot/reset pages are thin stubs |

### 5.3 ❌ Not implemented / incomplete

| Area | Status |
|---|---|
| **AI chat (the core product)** | UI only. `chat()`/`aiAssistant()` return empty Inertia views. No AI provider client, no message POST endpoint, no persistence. |
| **RAG pipeline** | Absent. `app/Domain/RAG/*` (Embeddings, Retrieval, Citations, Prompts, Contracts) all empty. |
| **Vector database** | Absent. `app/Infrastructure/VectorDB/` empty; `config/vector.php` unused. |
| **AI provider integration** | Absent. `app/Infrastructure/AI/` empty; `config/ai.php` unused; **no OpenAI/Anthropic/embedding libs in composer.json**. |
| **Document management** | UI only. No `documents`/`document_chunks` tables, no `FileStorage`, no upload handler, no approval persistence. |
| **Academic domain** | Absent. No Course/Assignment/Enrollment models or tables → grading, materials, roadmap have no data source. |
| **Analytics domain** | Absent. All analytics/system-monitor figures hardcoded. |
| **Speech (STT/TTS)** | Absent. `app/Infrastructure/Speech/` empty. |
| **Chat memory/history** | Absent. `app/Domain/Chat/Memory/` empty; no tables. |
| **API endpoints** | Absent. `routes/api.php` empty; `app/Http/Controllers/Api/` empty. |
| **Saved answers / Roadmap / Materials data** | UI only — backing methods return empty arrays or hardcoded counts. |
| **Real-time chat** | Absent. No WebSocket/broadcasting. |

### 5.4 🐞 Known bugs & inconsistencies

| # | Issue | Impact |
|---|---|---|
| 1 | **Role slug casing mismatch.** `UserRole::getSlug()` returns UPPERCASE (`'ADMIN'`); `RBACSeeder` and `role:` middleware use lowercase. Enum-based comparisons in `getPrimaryRoleEnum()`, `getDashboardRoute()`, `assignRole/removeRole(UserRole::…)` **silently fail to match** lowercase DB slugs. Login/middleware work (they compare lowercase). | Latent — enum-driven logic can silently no-op. Treat lowercase as source of truth. |
| 2 | **`auth.user` is hardcoded to `null`** in [HandleInertiaRequests](app/Http/Middleware/HandleInertiaRequests.php). Vue pages **cannot read the current user from shared props** until wired to `$request->user()`. | Pages can't show the logged-in user globally; drives reliance on per-page mock data. |
| 3 | **`Permission` model has `category` in `$fillable`, but the migration never creates a `category` column.** | Potential mass-assignment / missing-data errors when category is used. |
| 4 | **Dead routes:** `routes/student.php`, `routes/faculty.php`, `routes/admin.php` are unregistered empty skeletons. | Confusing; edits there have no effect. |
| 5 | **Unrouted controller method:** `AdminDashboardController::userManagement()` is implemented but never routed (route uses inline closure). | Real logic bypassed. |
| 6 | **Unrouted page:** `Faculty/CourseDetail.vue` is built but has no route. | Inaccessible feature. |
| 7 | **Mock data inside prop-fed pages** (User Management, dashboards) mixes real and fake data. | Misleading; needs cleanup when wiring backend. |

---

## 6. Combined Project Report

### 6.1 System depth & complexity

- **Breadth:** 3 roles, ~20 distinct screens, ~24 fine-grained permissions across 7 categories, temporal role assignment, department modeling, demo-seeding, rate-limited multi-role auth.
- **Depth:** Genuinely deep only in **Auth + RBAC + User management** (clean DDD: model → service → repository → provider, with an interface contract). Everything else is **wide but shallow** — a complete UI shell with no backing logic.
- **DDD scaffolding** anticipates Chat, RAG, Academic, Analytics, AI, VectorDB, FileStorage, and Speech subsystems, but only the **User** slice is real.

### 6.2 Core business features (intended vs. real)

| Business capability | Intended | Real today |
|---|---|---|
| RAG-grounded AI chat | ✅ Centerpiece | ❌ Not implemented |
| Role-based dashboards | ✅ | ⚠️ UI complete, data mostly mock |
| User & role governance | ✅ | ✅ Implemented |
| Document knowledge base + approval | ✅ | ❌ UI only |
| Faculty AI teaching tools & grading | ✅ | ❌ UI only |
| Student roadmap / saved answers / materials | ✅ | ❌ UI only |
| Analytics & system monitoring | ✅ | ❌ Hardcoded |
| AI provider configuration | ✅ | ❌ Config files unused |

### 6.3 Major workflows (end-to-end status)

1. **Auth & role routing** — ✅ Works end to end (register → role-checked login → role dashboard → logout).
2. **Admin user lifecycle** — ✅ Largely works at the service layer (create/assign/deactivate/search).
3. **Student asks AI a question** — ❌ Breaks immediately (no chat backend).
4. **Faculty generates a quiz / grades** — ❌ No backend or data.
5. **Admin uploads & approves a document → student reads it** — ❌ No document persistence anywhere in the chain.

### 6.4 Overall project maturity

**Phase: Early — "vertical slice + UI prototype."**

- **Maturity score (qualitative): ~25–30% of the envisioned product.**
- **Strong foundation:** Authentication, authorization, RBAC, and user management are production-quality and well-architected (clean DDD, typed, service/repository separation).
- **Impressive but hollow front-end:** Every screen exists and looks finished; ~80–95% of screen content is hardcoded mock data with no backend wiring.
- **The actual product (AI/RAG/chat/documents) is unbuilt** — no AI SDKs installed, no vector store, no document tables, no API endpoints.

### 6.5 Priority roadmap to a working MVP

1. **Fix foundational bugs first:** role-slug casing (#1) and `auth.user` sharing (#2) — they undermine everything built on top.
2. **Install an AI SDK** (e.g. official Anthropic/OpenAI client) and implement `app/Infrastructure/AI/*` + a chat-message API endpoint + chat/message tables → make Student Chat real.
3. **Implement document management:** `documents`/`document_chunks` tables, `FileStorage`, upload + approval persistence (admin) → feeds RAG.
4. **Build the RAG pipeline:** embeddings → vector store (`config/vector.php`) → retrieval → citations, grounding chat answers in approved docs.
5. **Implement the Academic domain** (courses/assignments/enrollment) → unlock real materials, roadmap, grading, and faculty tools.
6. **Wire Analytics** → replace hardcoded dashboard/analytics/monitor figures.
7. **Clean up:** route the implemented `userManagement()` method, add a route for `Faculty/CourseDetail`, remove dead route files, strip mock data from prop-fed pages.

---

## 7. Appendix — Quick Reference

### 7.1 Database tables (6 application + 7 Laravel infra)

`departments`, `users`, `roles`, `permissions`, `role_user`, `permission_role` — plus Laravel's `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`.
**No tables exist for** chat, messages, documents, chunks, embeddings, courses, assignments, enrollments, or analytics.

### 7.2 Models

- `app/Domain/User/Models/User.php` (RBAC-rich)
- `app/Models/`: `Role`, `Permission`, `Department`, `RoleUser`, `PermissionRole`

### 7.3 Enums (`app/Enums/`)

`UserRole` (admin/faculty/student), `Permission` (24), `ChatMode` (6), `DocumentStatus` (6), `Language` (7), `ConfidenceLevel` (5).

### 7.4 Demo credentials (seeded)

- Student — `student@university.edu`
- Faculty — `prof.smith@university.edu`
- Admin — `admin@university.edu`

(`demoLogin` auto-runs `RBACSeeder` if these are missing.)
