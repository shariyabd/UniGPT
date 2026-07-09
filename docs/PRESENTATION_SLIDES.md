# UniGPT — Presentation Deck: Slide-by-Slide Breakdown

> A map of the product presentation (the `/presentation` page). It lists **every slide in
> order**, what each one is about, and the exact points shown on it — so you can reuse the
> content for a script, handout, abstract, or a rebuilt deck.
>
> Source: the live deck. **24 slides** total. Each slide has a *kicker* (the small label at
> the top) and a *title*, followed by its content.

**Deck identity:** UniGPT — "AI Academic Copilot for Universities" · Laravel 11 · Inertia 2 · Vue 3 · MySQL

**Narrative arc:** Hook → Problem → Stakes → Solution → How it's built → What each role gets →
The AI core → Proof (grounded answers, live demo) → Governance → Metrics → Roadmap → Close.

---

## Slide 1 — Cover / Hook
- **Kicker:** AI academic copilot for universities
- **Title:** "Run your university on intelligence, not paperwork."
- **Subtitle:** One platform. Three tailored experiences. Grounded AI that answers from your own academic documents — with citations, not hallucinations.
- **Audience tags:** Students · Faculty · Administrators
- **Three highlight chips:** RAG-grounded answers · Role-based governance · Proctored AI exams

## Slide 2 — The Problem
- **Kicker:** The problem
- **Title:** "Campus knowledge is everywhere — and answers are nowhere."
- **Three pain points:**
  1. **Fragmented systems** — syllabi, notes and policies scattered across portals, PDFs and inboxes.
  2. **Generic AI hallucinates** — ChatGPT invents policies it has never seen; unsafe for an institution.
  3. **Staff drown in repetition** — the same questions, grading and admin tasks answered manually, again and again.

## Slide 3 — Why It Matters (Stakes)
- **Kicker:** Why it matters
- **Title:** "In academia, a wrong answer is worse than no answer."
- **Three stakes:**
  1. **Trust is everything** — a policy answer must be traceable to a real, approved source.
  2. **Accountability** — institutions need to see, govern and audit how AI is used on campus.
  3. **Speed at scale** — thousands of students, hundreds of courses; manual workflows don't scale.

## Slide 4 — The Solution
- **Kicker:** The solution
- **Title:** "UniGPT — one AI platform for the whole campus"
- **Subtitle:** Grounded intelligence, role-aware dashboards, end-to-end academic workflows.
- **Three pillars:**
  1. **Grounded AI** — every answer cited from your approved documents, with a confidence score.
  2. **Three experiences** — Student, Faculty and Admin dashboards; one login, tailored per role.
  3. **Full academic loop** — teach, quiz, submit, proctor, grade and report, wired end-to-end.

## Slide 5 — System Architecture
- **Kicker:** System architecture
- **Title:** "Modern, modular, no vendor lock-in"
- **Subtitle:** Domain-Driven Design over a single Laravel + MySQL instance — no external vector DB, no LLM SDK.
- **Four layers (top to bottom):**
  1. **Presentation** — Vue 3 + Inertia 2 · Tailwind + Vite · Ziggy routes
  2. **Application** — Role/Permission middleware · Controllers + Form Requests · Domain services & actions
  3. **Domain (DDD)** — Chat · RAG · Academic · pluggable AI providers · contracts over implementations
  4. **Data** — MySQL 8 · native cosine vector store · audit log & analytics

## Slide 6 — Role-Based Access Control (RBAC)
- **Kicker:** Role-based access control
- **Title:** "Three roles, 46 fine-grained permissions"
- **Subtitle:** Every route guarded. Every action audited. Role assignments can even expire.
- **Student** ("Learn & get answers"): AI chat with citations/confidence/saved answers · dashboard with courses, CGPA & deadlines · one-click registration for assigned sections · timed quizzes with instant auto-grade · attendance, transcript & GPA · roadmap, materials & document library · exam schedule, calendar, notes & tasks · AI study planner, learning analytics & flashcards · opt-in leaderboard & course discussions · real-time messaging with faculty.
- **Faculty** ("Teach & assess"): AI teaching assistant (quizzes, assignments, rubrics) · build timed auto-graded quizzes · manage sections & publish materials · one-click attendance with live stats · grading workspace with AI-drafted feedback · per-course analytics & grade distributions · real-time messaging with students.
- **Admin** ("Govern & monitor"): user, role & permission matrix · course catalog, sections, terms & student assignment · document approval workflow & knowledge base · institution-wide analytics & top queries · AI provider settings, prompts & retrieval tuning · system monitor, departments & announcements.

## Slide 7 — The AI Core (RAG Pipeline)
- **Kicker:** The AI core
- **Title:** "Retrieval-Augmented Generation, MySQL-native"
- **Subtitle:** From an uploaded PDF to a cited answer — the whole pipeline runs in-house.
- **Five pipeline steps:**
  1. **Upload & approve** — admin curates the knowledge base
  2. **Chunk** — ~150-token overlapping passages
  3. **Embed** — vectors stored in MySQL
  4. **Retrieve** — cosine similarity, top-K
  5. **Cite** — confidence + source excerpts

## Slide 8 — Grounded Answers (The Differentiator)
- **Kicker:** The differentiator
- **Title:** "Answers you can trust — and verify"
- **Subtitle:** Inline citations, a live confidence score and downloadable sources. No black box.
- **Two sample Q&A:**
  - *"What's the late submission policy for CS401?"* → 72h late with 10%/day penalty… → **96% confidence · CS401 Syllabus p.4**
  - *"When is the database systems midterm?"* → March 14, 9:00 AM, Hall B… → **92% confidence · Exam Timetable, Spring**
- **Three trust markers:** Confidence score (High/Med/Low) · Cited sources (doc + page) · Multi-language (English & Bangla, configurable).

## Slide 9 — Core Platform (Everything at a Glance)
- **Kicker:** Core platform
- **Title:** "One platform, every academic workflow"
- **Nine capability cards:** Grounded AI tutor · Document knowledge base · Role-based dashboards · AI teaching automation · Timed & proctored tests · Real-time messaging · Attendance & analytics · Granular RBAC + audit · Registration, exams & terms.

## Slide 10 — Student Experience (Feature Grid)
- **Kicker:** Student experience
- **Title:** "Everything a student needs, in one place"
- **Six core features:** AI Tutor (grounded chat, history, pin/archive) · Class Tests (timed, proctored, auto-graded) · Assignments (submit, track, rubrics) · Materials & Roadmap (completion tracking) · Transcript & Attendance · Notes & Tasks (+ saved AI answers).
- **Six newer features:** AI Study Planner (deadlines → AI schedule → saved as tasks) · My Progress (personal GPA/attendance/test/assignment/activity charts) · Flashcards (manual or AI-generated, SM-2 spaced repetition) · Leaderboard (opt-in, aliasable, gamified XP by department/semester/section) · Discussions (course/section groups: post/comment/like/report) · OCR Notes (photo → gpt-4o vision transcription → saved note).

## Slide 11 — Student Workflow (Asking the AI)
- **Kicker:** Workflow · Student asks the AI
- **Title:** "Question to grounded answer in seconds"
- **Four steps:** Ask → Retrieve (embed query, find relevant approved chunks) → Ground (answer strictly from context) → Cite & save (sources, confidence, follow-ups, bookmark).

## Slide 12 — Faculty Experience (Feature Grid)
- **Kicker:** Faculty experience
- **Title:** "An AI co-teacher for every instructor"
- **Six features:** AI Assistant (course-aware) · AI Quiz Generator (MCQ/TF + explanations) · Assignment Generator (description, tasks, rubric) · AI-Assisted Grading (draft feedback) · Learning Analytics (at-risk flags) · Courses & Attendance (per section).
- **Plus:** moderate their own sections' Discussion Feed (review reported posts/comments, keep the conversation on-topic).

## Slide 13 — Faculty Workflow (Creating an Exam)
- **Kicker:** Workflow · Faculty creates an exam
- **Title:** "From topic to live exam in one flow"
- **Four steps:** Generate (AI drafts from topic/difficulty/Bloom) → Refine (edit questions & keys) → Publish (assignment OR proctored class test) → Auto-grade (students notified, objective answers graded instantly).

## Slide 14 — Proctored Class Tests
- **Kicker:** Proctored class tests
- **Title:** "Real exam integrity, in the browser"
- **Layered, per-test proctoring:** faculty pick from 14 admin-approved layers — fullscreen, tab-switch/exit detection, clipboard block, question/option randomisation, identity watermark, browser fingerprint, behaviour logging, **webcam + screen recording**.
- **Server-authoritative:** per-student timer, client answers never trusted, auto-disqualify over the warning limit.
- **Evidence + review:** every attempt yields a behaviour timeline, a 0–100 risk score, and a per-student review dossier with recording playback.

## Slide 15 — Real-Time Messaging
- **Kicker:** Real-time messaging
- **Title:** "Students and faculty, talking live"
- **Four points:** Instant 1:1 chat (gated to people who share a section) · Presence & typing (active-now, typing, last-seen) · Smart conversation list (newest first, unread badge) · Database-backed & reliable (persist then broadcast, polling fallback).

## Slide 16 — Real-Time Architecture (Honest Trade-offs)
- **Kicker:** Realtime, honestly
- **Title:** "Built to run anywhere — even shared hosting"
- **Subtitle:** Live chat ships on a hosted broadcaster (Ably). Here's the trade-off and the path to scale.
- **Three pillars:**
  1. **The constraint** — cPanel shared hosting can't run a persistent socket server or queue worker; free Ably tier (~200 concurrent) is the cap; chat is text-only.
  2. **How we stay within it** — synchronous broadcast, DB as source of truth, heartbeat presence, poll fallback.
  3. **Path to production** — upgrade Ably or self-host Laravel Reverb on a VPS; same client, swap the broadcaster.

## Slide 17 — Admin Experience (Feature Grid)
- **Kicker:** Admin experience
- **Title:** "Govern the platform end-to-end"
- **Seven features:** User & Role Matrix · Document Approval (→ auto-embed into RAG) · AI Usage Monitor (per-user tokens, block/unblock) · Catalog & Terms (courses, sections, rollover) · Announcements (broadcast) · Discussion Moderation (queue of reported posts/comments) · AI Settings (temperature, top-K, threshold, prompts).

## Slide 18 — Governed AI (Control)
- **Kicker:** Governed AI
- **Title:** "Institutions stay in control"
- **Subtitle:** AI on campus is curated, measured and accountable — never a free-for-all.
- **Three pillars:** Approve before index (only approved docs reach the AI) · Measure every token (per-user usage + block controls) · Audit everything (every major action logged with user & timestamp).

## Slide 19 — Metrics (By the Numbers)
- **Kicker:** By the numbers
- **Title:** "A mature, demonstrable MVP"
- **Four stats:** **3** role dashboards · **46** permissions (fine-grained RBAC) · **100%** core workflows (wired end-to-end) · **0** external AI dependencies (no vector DB, no LLM SDK).

## Slide 20 — Pluggable AI (No Lock-In)
- **Kicker:** No lock-in
- **Title:** "Swap the brain, keep the platform"
- **Subtitle:** Provider logic lives behind an interface — demo today, production tomorrow.
- **Three pillars:** Mock provider (default, zero API keys) · OpenAI provider (drop in a key for production) · Future providers (Gemini or local LLMs on the same contract).

## Slide 21 — See It Live (Guided Demo Carousel)
- **Kicker:** See it in action
- **Title:** "A guided tour of the platform"
- **Format:** a carousel of real app screenshots, grouped by role. Each frame has a screen title, a caption, and 3 highlight points.
- **Student screens (8):** AI Tutor (grounded chat) · Learning Roadmap · Student Dashboard · Proctored Class Tests · Transcript & Grades · Assignments & Submissions · Exam Schedule · Course Materials.
- **Faculty screens (6):** AI Teaching Assistant · My Courses · Learning Analytics · Class Test Authoring · My Students · Faculty Dashboard · Exam Timetable.
- **Admin screens (8):** RAG Knowledge Base · Roles & Permissions · User Management · Course Catalog · System Analytics · Departments · Exam Management · Academic Terms · Announcements.

## Slide 22 — Roadmap (Shipped vs. Coming)
- **Kicker:** Where it goes next
- **Title:** "Shipped, and what is coming"
- **Shipped:** RAG tutor + citations · AI quiz → proctored exam · AI-assisted grading · real-time messaging · RBAC + audit log · document approval pipeline · AI usage governance.
- **Upcoming:** Telegram/WhatsApp alerts · token-by-token streaming · voice input/output · managed vector DB at scale.

## Slide 23 — Impact
- **Kicker:** The impact
- **Title:** "Less paperwork. More learning."
- **Three outcomes:** Instant, trusted answers (24/7 self-serve from cited knowledge) · Faculty multiplied (AI drafts; humans review, not author) · Administration in control (full visibility & governance).

## Slide 24 — Closing
- **Title:** UniGPT
- **Subtitle:** "The AI academic copilot your campus can actually trust."
- **Three closing pills:** 100% core workflows · end-to-end · 0 external AI dependencies · Grounded · Governed · Proctored
- **Call to action:** "Ready to pilot on your campus this term →"

---

## Quick Index (one line per slide)

| # | Slide | What it covers |
|---|---|---|
| 1 | Cover / Hook | Tagline, three roles, three highlight chips |
| 2 | Problem | Fragmented knowledge, AI hallucination, staff repetition |
| 3 | Stakes | Trust, accountability, speed at scale |
| 4 | Solution | Grounded AI · three experiences · full academic loop |
| 5 | Architecture | 4 layers: Presentation → Application → Domain → Data |
| 6 | RBAC | Three roles + 46 permissions, with per-role feature lists |
| 7 | AI Core | RAG pipeline: upload → chunk → embed → retrieve → cite |
| 8 | Grounded Answers | Sample cited Q&A + confidence/sources/multi-language |
| 9 | Core Platform | 9 capability cards across the whole product |
| 10 | Student Features | 6 core + 6 newer student feature cards |
| 11 | Student Workflow | Ask → Retrieve → Ground → Cite & save |
| 12 | Faculty Features | 6 faculty feature cards |
| 13 | Faculty Workflow | Generate → Refine → Publish → Auto-grade |
| 14 | Proctored Tests | 4 exam-integrity controls |
| 15 | Messaging | 4 real-time messaging points |
| 16 | Realtime Architecture | Constraint, mitigation, path to scale (honest) |
| 17 | Admin Features | 7 admin feature cards |
| 18 | Governed AI | Approve before index · measure tokens · audit |
| 19 | Metrics | 3 · 46 · 100% · 0 |
| 20 | Pluggable AI | Mock · OpenAI · future providers |
| 21 | Live Demo | Screenshot carousel: 8 student · 6 faculty · 8 admin screens |
| 22 | Roadmap | Shipped vs. upcoming |
| 23 | Impact | Trusted answers · faculty multiplied · admin in control |
| 24 | Closing | Tagline + 3 pills + pilot CTA |
