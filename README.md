# UniGPT — University Academic Copilot

UniGPT is an **AI-powered academic platform** for a university. It pairs a
**RAG-grounded AI assistant** (answers cited from the institution's own approved
documents) with **role-based dashboards** for **Students, Faculty, and
Administrators**. Built with **Laravel 11 + Inertia 2 + Vue 3 + Tailwind**, backed by
**MySQL** — and runnable end-to-end with **no API keys** thanks to a deterministic
mock AI provider.

> **Trust code over docs.** Where any document disagrees with the source, the code
> wins. These docs are kept current as of **2026-07-09**.

---

## 📖 Documentation Map

This README is the front door. The deeper references:

| Doc | What it covers |
|---|---|
| **[README.md](README.md)** (this file) | Overview, quick start, tech stack, roles at a glance |
| **[PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md)** | **Deep reference** — application logic, architecture, end-to-end workflows, roles & responsibilities, communication flow |
| **[DIRECTORY_TREE.md](DIRECTORY_TREE.md)** | Annotated directory structure (what lives where) |
| **[PROJECT_STATUS.md](PROJECT_STATUS.md)** | Feature completion tracker, **incomplete tasks**, and **Future Plans / Upcoming Features** |
| **[CLAUDE.md](CLAUDE.md)** | Conventions & rules for contributors (and AI assistants) |

New here? Read this page, then **PROJECT_ANALYSIS.md** for the full mental model.

---

## 1. What it does (Abstract)

UniGPT gives a university a single, governed AI layer over its own academic content:

- **Students** chat with an AI tutor that answers from *approved* university documents
  with **citations + a confidence score**, follow a course roadmap, track attendance,
  grades, exams, and a calendar, register for admin-assigned course sections, submit
  assignments, take **timed quizzes/class tests** with instant auto-graded results,
  **message their faculty in real time**, keep personal notes/tasks/saved answers,
  and use the newer study suite: an **AI Study Planner** (turns deadlines into a
  saveable study schedule), **Flashcards** (manual or AI-generated, with SM-2 spaced
  repetition), a **Learning Analytics / "My Progress"** dashboard (GPA, attendance,
  test/assignment trends), an opt-in **Leaderboard** (gamified XP by department /
  semester / section), **course/section Discussions** (post/comment/like), and
  **OCR of handwritten notes** (photo → transcribed text → saved note, via gpt-4o vision).
- **Faculty** manage the sections they teach, upload materials, grade submissions
  (with AI-drafted feedback), generate quizzes/assignments with an AI teaching
  assistant, author and run **timed online quizzes/class tests** — writing questions
  manually or **generating them with AI**, with auto-grading — apply **per-test
  proctoring layers** (fullscreen, tab/clipboard guards, watermark, fingerprint,
  behaviour logging, risk scoring, **webcam + screen recording**) and review a
  per-student evidence dossier, **message their students in real time**, moderate
  their sections' **discussion feed**, and view learning analytics.
- **Administrators** govern users and the RBAC matrix, own the course catalog,
  sections, terms and departments, curate the document knowledge base (upload →
  approve → embed), configure the AI provider, **gate the exam-security layers**,
  broadcast announcements, **moderate reported discussion posts/comments**, and monitor
  the system.

The differentiator: AI answers are **grounded in institution-approved documents
(RAG)** rather than generic LLM output — reducing hallucination and making guidance
**cited and auditable**.

---

## 2. Technology Stack

| Layer | Technology |
|---|---|
| Backend framework | **Laravel 11** (PHP 8.2+) |
| Frontend | **Inertia.js 2 + Vue 3** SPA (`<script setup>`) — *not* Blade pages, *not* Livewire |
| Build tooling | **Vite + Tailwind CSS** |
| Route bridge | **Ziggy** (`route()` helper in Vue) |
| Database | **MySQL** (`uni_gpt`) — application data **and** the vector store |
| Toasts | `vue-toastification` |
| AI provider | Pluggable **OpenAI** (native HTTP, no SDK) + always-available **deterministic Mock** fallback |
| Document parsing | `smalot/pdfparser` (PDF) + native ZipArchive/XML (DOCX) |
| JS testing | **Vitest** + `@vue/test-utils` + `jsdom` |
| Architecture | **Domain-Driven Design** layout (`app/Domain/*`, `app/Infrastructure/*`) |

> `livewire/livewire` is installed but **unused** — the UI is entirely Inertia + Vue.
> No external vector-DB client or LLM SDK is installed: embeddings are JSON in MySQL
> and AI calls use Laravel's HTTP client. Both are swappable behind interfaces.

---

## 3. Quick Start

### Prerequisites
- PHP 8.2+, Composer
- Node.js 18+, npm
- MySQL (database `uni_gpt`)

### Setup
```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
# set DB_DATABASE=uni_gpt and DB credentials in .env

php artisan migrate:fresh --seed   # schema + demo data (RBAC, academic, knowledge base)
npm run build
```

### Run (two terminals)
```bash
php artisan serve     # http://localhost:8000
npm run dev           # Vite HMR
```
`composer dev` runs the common dev processes together if configured.

### Demo accounts (seeded, password `demo123`)
| Role | Email |
|---|---|
| Student | `student@university.edu` |
| Faculty | `prof.smith@university.edu`, `prof.jones@university.edu` |
| Admin | `admin@university.edu` |

The login page also offers **demo-login** buttons (auto-seeds RBAC if missing). Login
requires picking a role (`student | faculty | admin`); it is validated against the
user's assigned roles and rate-limited per email+IP.

---

## 4. Common Commands

```bash
# Dev
php artisan serve
npm run dev
npm run build

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=RBACSeeder

# Tests
php artisan test                       # PHP feature/unit suite
npm run test:js                        # Vitest (Vue components/composables)

# Quality / inspection
./vendor/bin/pint                      # PHP code style
php artisan route:list
php artisan optimize:clear
```

---

## 5. Roles at a Glance

| | Student | Faculty | Administrator |
|---|---|---|---|
| AI chat | RAG tutor (6 modes), saved answers | AI teaching assistant (quiz/assignment gen, feedback) | Configures the AI provider |
| Courses | Register for **admin-assigned** sections; view materials | Manage taught sections; upload materials | Owns catalog, sections, terms, departments |
| Assessment | Submit assignments, take timed quizzes/tests, view grades/transcript | Grade submissions, author timed quizzes/tests (manual or AI-generated questions), mark attendance | — |
| Visibility | Own roadmap, attendance, exams, calendar | Per-course learning analytics | Platform analytics, system monitor, audit log |
| Knowledge base | Read/download approved docs | Upload course materials | Upload + approve/reject documents (→ RAG) |
| Messaging | Real-time chat with their faculty | Real-time chat with their students | — |
| Community | Discussions, opt-in leaderboard | Discussions (moderate own sections) | Discussion moderation queue |
| Study suite | Study Planner, Flashcards (SM-2), My Progress analytics, OCR notes | — | — |
| Governance | — | Department-scoped | User & RBAC management, announcements |

Access is enforced by **role middleware + 46 fine-grained permissions** (with
temporal role assignment via `role_user.expires_at`). See **PROJECT_ANALYSIS.md §3**.

---

## 6. Architecture in one breath

```
Browser (Vue 3 page) ──Inertia──▶ routes/web.php ──▶ Controller (thin)
        ▲                                              │ validates via Form Request
        │ Inertia::render(props)                        │ authorizes via Policy
        │                                              ▼
   Ziggy route()                              Domain Service (business logic)
                                                       │
                                    ┌──────────────────┼───────────────────┐
                                    ▼                  ▼                   ▼
                              Eloquent models     RAG pipeline       AI provider
                              (MySQL)         (chunk→embed→retrieve   (OpenAI | Mock)
                                               →cite, MySQL vectors)
```

- **One route file** — `routes/web.php` (the `routes/{student,faculty,admin}.php`
  files are unregistered dead stubs). API-style actions are web routes returning
  Inertia/redirects — a deliberate Inertia-monolith design.
- **Custom auth + RBAC** — `User` model lives at `app/Domain/User/Models/User.php`.
- Full detail in **[PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md)**.

---

## 7. What's next

Real-time student↔faculty chat, the **AI Study Planner**, **Flashcards**, **Learning
Analytics**, the **Leaderboard**, **Discussions**, and **OCR handwritten notes** have all
**shipped** (see Roles at a Glance above). The completion tracker, the list of
**incomplete tasks**, and the remaining **Future Plans / Upcoming Features**
(Telegram/WhatsApp notifications and an AI-assisted digital library) live in
**[PROJECT_STATUS.md](PROJECT_STATUS.md)**.

---

**Built with Laravel 11, Inertia 2, Vue 3, Vite, and Tailwind CSS.**
</content>
</invoke>
