# UniNexus — University Academic Copilot

UniNexus is an **AI-powered academic platform** for a university. It pairs a
**RAG-grounded AI assistant** (answers cited from the institution's own approved
documents) with **role-based dashboards** for **Students, Faculty, and
Administrators**. Built with **Laravel 11 + Inertia 2 + Vue 3 + Tailwind**, backed by
**MySQL** — and runnable end-to-end with **no API keys** thanks to a deterministic
mock AI provider.

> **Trust code over docs.** Where any document disagrees with the source, the code
> wins. These docs are kept current as of **2026-07-10**.

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

UniNexus gives a university a single, governed AI layer over its own academic content:

- **Students** chat with an AI tutor that **streams answers token-by-token**,
  answers from *approved* university documents **plus their own notes and course
  materials** ("chat with my materials" — the personal corpus is indexed into the
  same RAG pipeline) with **citations + a confidence score**, and now **takes real
  actions in-chat** (agentic tool calling: check upcoming deadlines,
  list/book/cancel office-hour slots, generate practice quizzes and flashcard
  decks, add planner tasks — **8 tools** with a live tool-activity trail, every
  action passing the same permission checks as the UI; a segmented
  **⚡ Agent / 💬 Answers-only** switch above the composer picks the mode —
  example prompts and hints follow it, replies that acted get an ⚡ Agent badge,
  and Answers-only is enforced server-side: tools are never even offered to the
  model), follow a course
  roadmap, track attendance, grades, exams, and a calendar (**exportable /
  subscribable as .ics** for Google/Outlook/Apple Calendar), register for
  admin-assigned course sections (with **prerequisite met/unmet badges** and
  **waitlist queue positions**), submit assignments (and give/receive **anonymous
  peer reviews** on classmates' submissions), take **timed quizzes/class
  tests** with instant auto-graded results, generate their own **AI practice
  quizzes** (instant server-graded feedback, missed questions convert to
  flashcards) or **self-quiz from each course's question bank** (no AI needed),
  give **anonymous mid-semester course feedback**, get a **weekly email digest**
  and deadline-nudge emails (opt-out in Settings),
  **message their faculty in real time**, join **group study rooms**
  (section-scoped live group chat with classmates), **book faculty office hours**,
  search everything from one **⌘K semantic global search** (documents, notes,
  materials, courses, assignments, discussions, chat history), keep personal
  notes/tasks/saved answers, and use the study suite: an **AI Study Planner**
  (turns deadlines into a saveable study schedule), **Flashcards** (manual or
  AI-generated, with SM-2 spaced repetition), a **Learning Analytics / "My
  Progress"** dashboard (GPA, attendance, test/assignment trends **plus a
  concept mastery map** — weakest topics first, one-click adaptive review), an
  opt-in **Leaderboard** (gamified XP by department / semester / section),
  **course/section Discussions** (post/comment/like), and **OCR of handwritten
  notes** (photo → transcribed text → saved note, via gpt-4o vision — indexed
  into the personal RAG corpus).
- **Faculty** manage the sections they teach, upload materials (**auto-indexed
  into their students' RAG corpus**), grade submissions with **AI-drafted rubric
  grades and feedback** ("Draft grade with AI" — per-criterion prefills read from
  the actual submission, always reviewed and saved by faculty, never
  auto-released) and **submission similarity flags** (embedded-text screening
  within an assignment; amber badge + side-by-side excerpt comparison — a review
  signal, not a verdict), generate quizzes/assignments with a **streaming** AI teaching
  assistant, author and run **timed online quizzes/class tests** — writing questions
  manually, **generating them with AI**, or pulling from a shared per-course
  **question bank** (import from existing tests, spin selected questions into a
  draft test), with auto-grading — apply **per-test
  proctoring layers** (fullscreen, tab/clipboard guards, watermark, fingerprint,
  behaviour logging, risk scoring, **webcam + screen recording**, and the
  camera-AI layers: **face-liveness verification** — a blink-checked gate before
  questions render plus continuous monitoring with question-blur and escalating
  warnings, **snapshot evidence** — photo bursts at flagged moments instead of
  storage-heavy continuous video, and **phone / second-face detection** — all
  on-device MediaPipe, no frames leave the browser) and review a
  per-student evidence dossier (timeline, recordings, photo strip, risk score),
  get **at-risk early warnings** (students flagged
  on attendance, missed deadlines, test average and grade — with one-click
  messaging), open **anonymous mid-semester feedback windows** per section
  (results unlock at ≥3 responses; AI theme summary), enable **anonymous peer
  review** per assignment (average peer ratings shown in grading), publish
  **bookable office-hours slots**, **message their students
  in real time**, moderate their sections' **discussion feed**, and view
  learning analytics.
- **Administrators** govern users and the RBAC matrix, own the course catalog,
  sections, terms and departments (including **course prerequisites** — only a
  completed course satisfies one — and **section waitlists**: full sections queue
  FIFO, drops auto-promote), curate the document knowledge base (upload →
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
| AI provider | Pluggable **OpenAI** (native HTTP, no SDK) with an admin-configurable **OpenRouter** fallback chain (free models, either provider primary) and always-available **deterministic Mock** fallback |
| Embeddings | **OpenAI** or **Jina AI** (free, multilingual), selectable in admin; optional dual-embed fallback keeps RAG alive if the primary embeddings API dies. Vectors are model-tagged so providers never cross-compare |
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

### Exam face-liveness assets (committed)
The face-liveness proctoring layer runs MediaPipe Face Landmarker fully client-side.
Its assets live in `public/vendor/mediapipe/` (wasm bundle + `face_landmarker.task`
model, ~37 MB total) and are committed so exams never depend on a CDN. To refresh
them after upgrading `@mediapipe/tasks-vision`:
```bash
cp node_modules/@mediapipe/tasks-vision/wasm/* public/vendor/mediapipe/wasm/
curl -L -o public/vendor/mediapipe/face_landmarker.task \
  "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/latest/face_landmarker.task"
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
| AI chat | **Streaming, agentic** RAG tutor (6 modes, **8 in-chat actions** with a live tool trail, one-tap **Agent / Answers-only** switch — answers-only enforced server-side) grounded in library **+ own notes & materials**, saved answers | **Streaming** AI teaching assistant (quiz/assignment gen, feedback) | Configures the AI provider |
| Courses | Register for **admin-assigned** sections (**prereq badges, waitlist positions**); view materials | Manage taught sections; upload materials (auto-indexed for RAG) | Owns catalog, sections, terms, departments, **prerequisites & waitlists** |
| Assessment | Submit assignments, take timed quizzes/tests, **self-serve AI practice quizzes** (missed → flashcards) or **question-bank quizzes**, view grades/transcript | Grade submissions (**AI-drafted rubric grades**, **similarity flags**), author timed quizzes/tests (manual, AI-generated or **question-bank** questions), mark attendance | — |
| Visibility | Own roadmap, attendance, exams, calendar (**.ics export/subscribe**) | Per-course learning analytics + **at-risk early warning** (4 signals, high/watch levels) | Platform analytics, system monitor, audit log |
| Knowledge base | Read/download approved docs | Upload course materials | Upload + approve/reject documents (→ RAG) |
| Search | **⌘K semantic global search** (knowledge, courses, assignments, discussions, chat history) | Same, scoped to taught sections | Same + user lookup |
| Messaging | Real-time chat with their faculty + **group study rooms** with classmates | Real-time chat with their students | — |
| Office hours | Browse & **book** their faculty's slots (atomic, notified) | **Publish slots**, manage bookings | — |
| Community | Discussions, opt-in leaderboard | Discussions (moderate own sections) | Discussion moderation queue |
| Feedback | **Anonymous mid-semester course feedback**, give/receive **anonymous peer reviews** | Open/close feedback windows (≥3-response unlock, AI theme summary); peer-review toggle + avg ratings in grading | — |
| Email | **Weekly digest + deadline nudges** (opt-out in Settings) | — | SMTP settings power the digests |
| Study suite | Study Planner, Flashcards (SM-2), Practice Quizzes, My Progress analytics + **concept mastery map**, OCR notes | — | — |
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
                              (MySQL)         (chunk→embed→retrieve   (OpenAI→OpenRouter
                                               →cite, MySQL vectors)    | Jina embeds | Mock)
```

- **One route file** — `routes/web.php` (the `routes/{student,faculty,admin}.php`
  files are unregistered dead stubs). API-style actions are web routes returning
  Inertia/redirects — a deliberate Inertia-monolith design.
- **Custom auth + RBAC** — `User` model lives at `app/Domain/User/Models/User.php`.
- Full detail in **[PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md)**.

---

## 7. What's next

The **July 2026 wave** has **shipped** (see Roles at a Glance above):
**agentic AI chat** (8 in-chat actions via tool calling, live tool-activity
trail), **submission similarity screening**, the **concept mastery map +
adaptive review**, **email digests & deadline nudges**, **AI-assisted rubric
grading**, **anonymous mid-semester course feedback**, **anonymous peer review
on assignments**, **course prerequisites & section waitlists**, and a per-course
**question bank** — on top of the earlier waves: **personal-corpus RAG** ("chat
with my materials"), **streaming AI responses (SSE)**, **AI practice quizzes**
(missed → flashcards), the **at-risk early-warning system**, **semantic ⌘K
global search**, **group study rooms**, **office-hours booking**, **ICS calendar
export/subscribe**, the study suite (Study Planner, Flashcards, Learning
Analytics, Leaderboard, Discussions, OCR notes) and real-time messaging. The
completion tracker, the list of
**incomplete tasks**, and the remaining **Future Plans / Upcoming Features**
(Telegram/WhatsApp notifications and an AI-assisted digital library) live in
**[PROJECT_STATUS.md](PROJECT_STATUS.md)**.

---

**Built with Laravel 11, Inertia 2, Vue 3, Vite, and Tailwind CSS.**
