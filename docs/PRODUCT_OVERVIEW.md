# UniNexus — Product Overview & Landing Page Content

> A single, slide-ready source of the full landing-page narrative. Sections follow the
> **landing page order** (top → bottom) so each `##` heading maps to one section / slide.
> Content mirrors the live marketing copy in `resources/js/components/landing/`.

**One-liner:** UniNexus is an **AI academic copilot for universities** — grounded, cited
answers from your own documents, automated teaching tools, and live operational insight,
for **students, faculty and administrators** on one platform.

### Slide map
1. [Vision / Hero](#1-vision--hero)
2. [Problems](#2-problems)
3. [Solutions](#3-solutions)
4. [Features](#4-features)
5. [User Roles](#5-user-roles)
6. [AI Engine](#6-ai-engine)
7. [Intelligence Layer](#7-intelligence-layer)
8. [Workflow](#8-workflow)
9. [System Connections](#9-system-connections)
10. [Why UniNexus — the differentiator](#10-why-uninexus--the-differentiator)
11. [Impact / Proof](#11-impact--proof)
12. [Technology Stack](#12-technology-stack)
13. [Roadmap](#13-roadmap)
14. [Creator](#14-creator)
15. [FAQ](#15-faq)
16. [Call to Action](#16-call-to-action)

---

## 1. Vision / Hero

**Eyebrow:** AI academic copilot for universities

**Headline:** Run your university on **intelligence**, not paperwork.

**Sub-headline:** UniNexus unifies students, faculty and administrators on one AI platform —
grounded answers from your own academic documents, automated teaching tools, and live
operational insight. **One login, three tailored experiences.**

**Primary actions:** *Launch UniNexus* · *See how it works*

**Built for everyone on campus:** Students · Faculty · Administrators · Departments

**Product proof (sample interaction):**
- Q: *"What's the late submission policy for CS401?"*
- A: *"Submissions are accepted up to **72 hours late** with a 10% penalty per day. Beyond
  that, a grade of zero applies unless an extension was approved."*
- Tagged with **96% confidence**, cited from **CS401 Syllabus · p.4**.
- Floating proof points: 🔒 *Cited from your docs* · ⚡ *Grounded, cited answers*

---

## 2. Problems

> **Eyebrow:** The problem · **Title:** Campuses run on busywork the AI era should have ended
> **Subtitle:** Every role loses hours to tasks software should handle. UniNexus was built to remove them.

| # | Pain point | What goes wrong |
|---|---|---|
| 1 | **Answers buried in PDFs** | Policies, syllabi and handbooks are scattered across drives. Students ask staff what a document already answers. |
| 2 | **Attendance by hand** | Roll-call on paper and spreadsheets — error-prone, slow to total, and impossible to analyse at scale. |
| 3 | **Grading drains hours** | Faculty spend evenings writing feedback and building quizzes from scratch every single term. |
| 4 | **No personalised help** | Generic chatbots hallucinate. They have no idea what your courses, deadlines or rules actually say. |
| 5 | **Disconnected tools** | Separate apps for chat, records, documents and analytics that never talk to each other. |
| 6 | **Decisions run late** | Admins lack a live view of engagement and risk, so problems surface long after they could be fixed. |

---

## 3. Solutions

> **Eyebrow:** The solution · **Title:** From question to grounded answer in one flow
> **Subtitle:** UniNexus turns your institutional knowledge into an assistant that cites its sources.

| Step | Stage | What happens |
|---|---|---|
| 1 | **Ask** — Ask in plain language | A student, lecturer or admin asks a question — about a policy, a deadline, a grade, or campus data. |
| 2 | **Ground** — Retrieve & reason | UniNexus searches your approved documents, pulls the most relevant passages, and reasons over them — never guessing. |
| 3 | **Answer** — Answer with proof | You get a precise answer with citations, a confidence score, and suggested follow-ups you can trust and save. |

---

## 4. Features

> **Eyebrow:** Core platform · **Title:** One platform, every academic workflow
> **Subtitle:** Real, shipped capabilities — not roadmap promises. Here is what UniNexus does today.

**Flagship — Grounded RAG assistant**
Ask anything. UniNexus retrieves passages from your approved documents — **and from your own
notes and your courses' materials** — answers **streaming live, token by token**, with inline
citations and a confidence score, and suggests follow-ups. Multiple modes — academic, exam
prep, research — keep responses on-task. And it doesn't just answer — **it acts**: the
student chat takes real actions via tool calling (check upcoming deadlines, list and book
office-hour slots, generate practice quizzes and flashcard decks, add planner tasks — 8
tools), showing a live tool-activity trail in the conversation, with every action passing
the same permission checks as the rest of the platform. A segmented **⚡ Agent / 💬 Answers
only** switch above the composer puts the student in charge: Agent mode (the default) invites
action requests — hints and example prompts adapt, and replies that acted carry an ⚡ Agent
badge — while Answers only is a hard, server-side guarantee that tools are never even offered
to the model.
*Highlights:* Streaming answers · **Agentic in-chat actions (8 tools)** · **Agent / Answers-only switch** · Citations (library + personal sources) · Confidence scoring · Chat modes · Saved answers · Session history

**Supporting features**

| Feature | What it does |
|---|---|
| **Document knowledge base** | Upload, review and approve. Approved docs are chunked, embedded and made searchable automatically. |
| **Role-based dashboards** | Students, faculty and admins each get a tailored home with the tools and data that matter to them. |
| **Teaching automation** | Generate quizzes, assignments and rubrics, and draft grading feedback with an AI teaching assistant. |
| **Timed quizzes & class tests** | Faculty set up online quizzes/class tests with rules, a duration timer, questions and marks — writing questions by hand or **generating them with AI**, then editing. Students take them in-panel with a live countdown and get auto-graded results the moment they submit. |
| **Layered exam proctoring** | Faculty pick, per test, which security layers apply from an admin-approved set — fullscreen, tab-switch detection, clipboard block, one-at-a-time, randomisation, identity watermark, browser fingerprint, behaviour logging, risk scoring, **webcam / screen recording**, and the on-device **camera-AI layers**: a blink-verified **face-liveness gate** (questions stay hidden until a live face is confirmed; face loss blurs then locks the paper with escalating warnings), **phone & second-face detection**, and **snapshot evidence** (photo bursts at flagged moments + random samples — ~100× less storage than continuous video). Every attempt gets an evidence trail and a per-student review dossier (timeline, 0–100 risk score, recording playback, snapshot photo strip). Audible alerts call back a student who has left the frame. |
| **Attendance & analytics** | Mark attendance in seconds; track rates, grades, GPA and engagement across every course. |
| **My Progress analytics** | Every student gets a personal insight dashboard — GPA, attendance, test, assignment and activity trends visualised in one place. |
| **Concept mastery map** | A deterministic per-topic mastery score on My Progress, blended from class-test scores, practice-quiz accuracy and flashcard recall. Tier-coloured tiles sort weakest-first, and weak concepts offer one-click "Practice this" / "Make flashcards" straight into the AI generators. |
| **AI Study Planner** | Feed in your deadlines and UniNexus drafts a realistic study schedule, then saves each session straight to your tasks. |
| **Flashcards with spaced repetition** | Build decks by hand or generate them with AI, then review on an SM-2 spaced-repetition schedule that surfaces cards right before you'd forget. |
| **OCR handwritten notes** | Snap a photo of handwritten notes and gpt-4o vision transcribes them into a clean, searchable saved note — automatically indexed so the AI tutor can answer from it. |
| **Chat with my materials** | Personal-corpus RAG: the tutor answers from your own notes and your sections' course materials, not just the library — each strictly scoped to its owner/class. |
| **AI practice quizzes** | Students generate their own MCQ/true-false quizzes on any topic, get instant server-graded results with explanations, retake freely, and convert missed questions into flashcards. |
| **Question bank** | Faculty teaching a course share a bank of reusable MCQ/true-false questions — add them manually or import from existing class tests (duplicates skipped), spin a selection into a draft class test, and students self-quiz with deterministic practice quizzes sampled from the bank (no AI required). |
| **At-risk early warning** | Faculty see students flagged on four live signals (attendance, missed deadlines, test average, grade) with High/Watch levels and a one-click message to intervene. |
| **Submission similarity screening** | Every submission's text — typed answers plus extracted PDF/DOCX content — is chunked and embedded; high-similarity pairs within the same assignment are flagged with matching excerpts. Grading shows an amber badge and a side-by-side comparison panel. A review signal, not a verdict. |
| **AI-assisted rubric grading** | One "Draft grade with AI" click reads the actual submission and drafts per-criterion scores with one-line justifications, plus a suggested overall grade and feedback — all editable prefills that faculty review and save. Nothing auto-releases. |
| **Anonymous course feedback** | Faculty open a mid-semester feedback window per section; students submit one revisable 1–5 rating + comment. Results — average, star distribution, shuffled anonymized comments — unlock only once 3+ responses exist, and an AI button summarizes themes into Going well / Concerns / Suggestions. |
| **Anonymous peer review** | A per-assignment toggle: each submitting student receives up to two classmate submissions to rate and comment on — load-balanced, never their own, anonymous in both directions. Reviewees see the feedback; faculty see average peer ratings in grading. |
| **Prerequisites & waitlists** | Admins define course prerequisites (only a completed course counts); registration shows met/unmet badges and is enforced server-side. Full sections queue students on a FIFO waitlist that auto-promotes the head of the queue on any drop. |
| **Email digests & nudges** | A Monday-morning email digest — deadlines in 7 days, fresh grades, booked office hours, due flashcards — plus a daily "assignment due soon" email. Opt-out in Settings; delivered via the admin-configured SMTP. |
| **Semantic global search (⌘K)** | One search across documents, notes, materials, courses, assignments, discussions and past AI chats — matched by meaning, scoped to what you can access. |
| **Group study rooms** | Section-scoped live group chats for classmates — create a room per topic, join in one click, messages delivered in real time. |
| **Office-hours booking** | Faculty publish bookable slots; students book atomically (no double-booking), both sides are notified, meetings land on the calendar. |
| **Calendar .ics export & subscribe** | Download the unified calendar or subscribe by signed URL from Google/Outlook/Apple Calendar and stay auto-synced. |
| **Discussion feed** | Course and section discussion groups where students and faculty post, comment, like and report — faculty moderate their own sections and admins run a moderation queue. |
| **Leaderboard** | Opt-in, gamified XP rankings by department, semester or section — students choose an alias and compete on their own terms. |
| **User activity tracking** | An Admin → User Activity panel records who visited, from where (referrer), on what device, and an IP-derived location — meaningful page views on the public landing page and every authenticated page, logged after the response ships so it adds no page latency. Summary stats (total / today / unique visitors / guest visits), device + top-country + top-page breakdowns, filters (user, role, device, country, date range) and a paginated feed. Configurable retention (90 days default) with a scheduled prune. |
| **Demo-mode governance** | In demo mode every account is capped at a fixed number of AI requests across all AI surfaces — student chat/agent, faculty assistant and AI generators — so a public demo can't drain the shared provider budget (requests are refused with HTTP 429 once exhausted). Nothing is metered outside demo mode. |
| **Hidden maintenance switch** | A URL-driven operator control puts the whole app behind a maintenance page (`?live=false`) or flips it back live (`?live=true`) for everyone; the state persists globally until toggled, unlock is honoured first so a locked site can always be reopened, and the health check is never gated. |
| **Granular RBAC** | Roles map to fine-grained permissions with time-limited grants — every action is gated and logged. |
| **Announcements & alerts** | Broadcast to any audience and notify the right people on exams, grades and new materials. |
| **Real-time messaging** | Students and faculty chat live in-app — online presence, typing indicators, unread counts and instant delivery, separate from the AI tutor. |
| **Registration & rostering** | Admins assign students to course sections; students confirm in one click — seats, terms and rosters stay consistent. |
| **Exams, transcripts & calendar** | Schedule exams, auto-build transcripts with GPA & CGPA, and merge every deadline into one calendar. |

---

## 5. User Roles

> **Eyebrow:** Built for three · **Title:** One platform, three tailored experiences
> **Subtitle:** Switch roles to see exactly what each user gets the moment they sign in.

### 🎓 Student — *A copilot for every academic question*
From the first lecture to final transcript, students get instant, cited answers and a
planner that keeps the term on track.
- Streaming AI chat with citations, confidence & saved answers — grounded in the library **and your own notes & materials**
- **Agentic in-chat actions** — the tutor checks deadlines, books office hours, spins up quizzes, flashcards & planner tasks for you, with a live tool-activity trail
- Personal dashboard: courses, CGPA & deadlines
- One-click registration for assigned course sections — with **prerequisite met/unmet badges** and live **waitlist queue positions**
- Timed quizzes & class tests with instant auto-graded results
- Self-serve **AI practice quizzes** — instant grading, missed questions become flashcards — or self-quiz from the course **question bank**
- **Anonymous peer review** on assignments & **anonymous mid-semester course feedback**
- "My Progress" analytics: GPA, attendance, test & assignment trends — plus a **concept mastery map** with one-click adaptive review
- AI Study Planner: turn deadlines into a saved study schedule
- Flashcards — manual or AI-generated, with SM-2 spaced repetition
- OCR handwritten notes: photo → gpt-4o transcription → saved, AI-searchable note
- Course discussion feeds: post, comment, like & report
- **Group study rooms** — live section-scoped group chat with classmates
- **Book faculty office hours** in one click
- **⌘K semantic search** across everything you can access
- Opt-in gamified leaderboard with XP ranked by department, semester or section
- Attendance, transcript & GPA tracking
- Course roadmap, materials & document library
- Exam schedule, calendar (**.ics export/subscribe**), notes & tasks
- **Weekly email digest** & deadline-nudge emails — opt-out in Settings
- Real-time messaging with your faculty
- *Sample dashboard metrics:* 6 courses · 3.78 CGPA · 94% attendance

### 📊 Faculty — *Teach more, administrate less*
Faculty manage courses end to end while the AI teaching assistant drafts assessments and
feedback in seconds.
- Streaming AI teaching assistant: quizzes, assignments & rubrics
- Build timed quizzes & class tests (AI-generated, manual or **question-bank** questions) with auto-grading
- **Per-course question bank** — add or import reusable questions, spin a selection into a draft class test
- Per-test proctoring layers (incl. face liveness, phone detection, snapshot evidence) with a risk-scored, per-student review dossier
- Manage taught sections & publish course materials (auto-indexed for students' AI tutor)
- One-click attendance with live class stats
- Grading workspace with AI-drafted feedback — plus **"Draft grade with AI"** per-rubric-criterion prefills (reviewed & saved by you, never auto-released)
- **Submission similarity flags** — amber badge + side-by-side excerpt comparison per submission
- **Anonymous mid-semester feedback windows** — anonymized results unlock at 3+ responses, with an AI theme summary
- **Anonymous peer review** per assignment — average peer ratings surface in grading
- Per-course analytics & grade distributions
- **At-risk early warning** — four signals, High/Watch levels, message a flagged student in one click
- **Publish bookable office-hours slots** & manage bookings
- Join & moderate discussion feeds in your own sections
- Real-time messaging with your students
- *Sample dashboard metrics:* 4 courses · 128 students · 12 to grade

### ⚙️ Admin — *Total control, live oversight*
Admins govern users, knowledge and the AI itself — with a real-time view of system and
academic health.
- User, role & permission matrix management
- Course catalog, sections, terms & student assignment
- **Course prerequisites** & **section waitlists** — FIFO queue with auto-promotion on drops
- Document approval workflow & knowledge base
- Institution-wide analytics & top queries
- AI provider settings, prompts & retrieval tuning
- Exam-security gate: which proctoring layers faculty may use, with a built-in step-by-step layer guide
- AI usage monitor with per-user access control
- **User Activity panel** — who visited, referrer, device, IP-derived location, with summary stats, breakdowns, filters and a paginated feed (configurable retention, gated by view-all-analytics)
- **Demo-mode AI cap** — every account capped at a fixed number of AI requests across all AI surfaces so a public demo can't drain the provider budget
- **Hidden maintenance switch** — `?live=false` / `?live=true` puts the whole app into maintenance or back live for everyone, persisting until toggled
- System monitor, departments & announcements
- Discussion moderation queue for reported posts & comments
- *Sample dashboard metrics:* 2.4k users · 860 docs · 99.9% uptime

> *Dashboard metrics are illustrative.*

---

## 6. AI Engine

> **Eyebrow:** The intelligence · **Title:** Retrieval-augmented, not just a chatbot
> **Subtitle:** UniNexus grounds every answer in your institution's own documents — so responses are accurate, attributable and auditable.

**The RAG pipeline**

| Step | Stage | What happens |
|---|---|---|
| 1 | **Chunk** | Approved documents are split into overlapping passages on approval. |
| 2 | **Embed** | Each passage becomes a vector and is stored alongside its source. |
| 3 | **Retrieve** | Your question is embedded and matched by cosine similarity, top-K. |
| 4 | **Answer** | The model reasons over retrieved context and cites every claim. |

**Engine highlights**
- **Pluggable providers** — OpenAI gpt-4o + text-embedding-3-small in production, with a
  built-in mock provider for offline development.
- **Scoped to what you can see** — Retrieval only ever touches approved documents the
  current user is permitted to read — answers never leak.

---

## 7. Intelligence Layer

How UniNexus turns raw activity into decisions, automation and trustworthy answers.

- **Grounded reasoning, not guessing** — answers are constructed from retrieved,
  approved passages; if it isn't in your documents, it isn't asserted.
- **Confidence & citations on every answer** — each response carries a confidence score
  and links back to the exact source (e.g. *CS401 Syllabus · p.4*).
- **Suggested follow-ups** — the assistant proposes next questions to keep guidance moving.
- **Chat modes** — academic, exam-prep, research and more steer tone and focus per task.
- **Agentic actions** — the student assistant executes real tasks (checking deadlines,
  booking office hours, generating quizzes, flashcards and planner tasks) through the same
  domain services and permission checks as the UI, with a visible tool-activity trail.
- **Teaching automation** — auto-generates quizzes, assignments and rubrics, drafts
  grading feedback, and pre-fills rubric scores per criterion from the actual submission —
  always for faculty to review and edit.
- **Integrity signals** — submission text is embedded and screened for high-similarity
  pairs within an assignment, surfaced to faculty as a review signal, never a verdict.
- **Operational intelligence** — the system turns daily activity into institutional insight:
  top queries, engagement and grade trends, and early signals of at-risk students.
- **Permission-aware by design** — every AI action respects the same role-based permissions
  as the rest of the platform.

---

## 8. Workflow

> **Eyebrow:** How it connects · **Title:** A single loop, from setup to smarter decisions
> **Subtitle:** Each role feeds the next. The AI closes the loop by turning daily activity into institutional insight.

| Stage | Role | What they do |
|---|---|---|
| 1 | **Admin** — Sets the foundation | Builds the catalog, sections and terms, onboards users, approves documents and tunes the AI. |
| 2 | **Faculty** — Runs the classroom | Teaches assigned sections, publishes materials, marks attendance and grades with AI help. |
| 3 | **Student** — Engages & learns | Asks grounded questions, tracks progress and plans the term. |
| 4 | **AI** — Analyses everything | Surfaces top queries, engagement and grade trends across the institution. |
| 5 | **Leadership** — Decides faster | Acts on live insight instead of stale end-of-term reports. |

---

## 9. System Connections

How modules, services and roles connect — the platform is one loop, not isolated apps.

- **Admin → Faculty & Student:** account governance, document approval, AI configuration,
  and ownership of the catalog, sections and terms; admins assign students to sections.
- **Faculty → Student:** course materials, published quizzes/assignments and timed class tests, and grades + feedback (quizzes are auto-graded back to the student instantly).
- **Student → Faculty:** submissions and learning activity feed grading and analytics.
- **System events → Notifications:** grades posted, materials/assignments/exams published,
  enrollment assigned, and admin announcements reach the right people automatically.
- **Everyone → Shared core:** all roles depend on the **Chat/RAG engine** and the
  **admin-approved document knowledge base** at the center.
- **Activity → Analytics:** everything users do is logged and aggregated into institutional insight.

```
            ADMIN  ──owns catalog/terms, approves docs, assigns students──▶
              │                                                            │
              ▼                                                            ▼
           FACULTY ──materials, assignments, grades──▶ STUDENT ──submissions/activity──┐
              ▲                                                                          │
              └───────────────── analytics & insight ◀── AI ◀── (logged activity) ◀─────┘
                    Shared core: Chat/RAG engine + approved knowledge base
```

---

## 10. Why UniNexus — the differentiator

> **Eyebrow:** Why UniNexus · **Title:** The difference is grounding
> **Subtitle:** Traditional systems digitise paperwork. UniNexus removes it.

| Capability | Traditional systems | UniNexus |
|---|---|---|
| Answers to policy questions | Email staff and wait | Instant, cited from source docs |
| Attendance | Paper sheets & spreadsheets | One-click marking with live stats |
| Course registration | Clashing, self-picked sections | Admin-assigned, one-click confirm |
| Quizzes & feedback | Written from scratch each term | AI-drafted in seconds, then edited |
| Academic tracking | Manual GPA spreadsheets | Automatic transcript & analytics |
| AI accuracy | Generic bots that hallucinate | Grounded with confidence scores |
| Tooling | Many disconnected apps | One platform, three roles |

---

## 11. Impact / Proof

> *Illustrative figures based on the platform's automated workflows.*

| Metric | Meaning |
|---|---|
| **12h** | Saved per week, per faculty member |
| **46+** | Fine-grained access permissions |
| **3** | Tailored role experiences |
| **100%** | Answers traced to a source |

---

## 12. Technology Stack

> **Eyebrow:** Under the hood · **Title:** A modern, maintainable stack
> **Subtitle:** Production-grade foundations chosen for speed, clarity and long-term maintainability.

| Technology | Role |
|---|---|
| **Laravel 11** | Application core |
| **Vue 3** | Reactive UI |
| **Inertia 2** | SPA bridge |
| **Vite** | Build tooling |
| **Tailwind CSS** | Design system |
| **MySQL** | Data + vectors |
| **OpenAI** | gpt-4o · embeddings |
| **Ziggy** | Typed routing |

---

## 13. Roadmap

> **Eyebrow:** On the roadmap · **Title:** What's coming next
> **Subtitle:** The platform keeps growing. These features are in active design — built on the RAG engine and notification system already shipping today.

**Flagship upcoming features**

> ✅ **Real-time student–faculty chat has shipped** — see the Core platform & Roles sections above.
>
> ✅ **Recently shipped for students** — AI Study Planner, "My Progress" learning analytics,
> AI-generated Flashcards with SM-2 spaced repetition, an opt-in gamified Leaderboard,
> OCR handwritten-note transcription, and course Discussion feeds (with faculty section
> moderation and an admin moderation queue). All live — see §4 Features & §5 Roles.
>
> ✅ **Copilot depth & connection wave — all shipped:** token-by-token **streaming answers**,
> **"chat with my materials"** personal-corpus RAG, self-serve **AI practice quizzes**
> (missed → flashcards), the **at-risk early-warning** system for faculty, **⌘K semantic
> global search**, **group study rooms**, **office-hours booking**, and **.ics calendar
> export/subscribe**.
>
> ✅ **July 2026 wave — all shipped:** **agentic AI chat** (the tutor takes real actions —
> 8 tools with a live activity trail), **submission similarity screening**, the **concept
> mastery map + adaptive review**, **email digests & deadline nudges**, **AI-assisted
> rubric grading**, **anonymous mid-semester course feedback**, **anonymous peer review**
> on assignments, **course prerequisites & section waitlists**, and the per-course
> **question bank**. All live — see §4 Features & §5 Roles.
>
> ✅ **Operations & governance — shipped:** an Admin **User Activity panel** (visitor,
> referrer, device and IP-derived location tracking with breakdowns, filters and a
> paginated feed), a **demo-mode AI usage cap** across every AI surface, and a hidden
> URL-driven **maintenance switch**. All live — see §4 Features & §5 Roles.

| Feature | Stage | What it adds |
|---|---|---|
| **Telegram & WhatsApp alerts** | Planned | Push assignments, scheduled quizzes, syllabus changes and announcements straight to the phones students already use. |
| **Digital library + AI assistant** | Exploring | A library of academic books and resources with an AI assistant that answers strictly from the library's own content — grounded, cited, on-syllabus. |

**Also on the engineering roadmap** *(later phases — most depend on additional infrastructure)*
- **Voice I/O** — speak to the assistant with speech-to-text & text-to-speech *(intentionally on hold)*.
- **Lecture-audio transcription, completion certificates, expanded realtime presence** — designed, intentionally on hold.
- **Predictive analytics** — ML-driven recommendations building on the shipped rule-based at-risk signals.
- **More AI providers** — Gemini and self-hosted models alongside OpenAI.
- **Versioning & memory** — document version history and longer conversation memory.

---

## 14. Creator

**Mohammad Shariya** — Full-stack engineer · Builder of UniNexus

> "I built UniNexus because campuses are rich with knowledge but poor at making it reachable.
> Students wait on answers their handbook already holds; faculty lose evenings to busywork;
> admins fly blind until term's end. UniNexus is my attempt to put a single, trustworthy AI
> copilot behind every academic interaction — one that cites its sources, respects
> permissions, and gives each role exactly what they need."

Contact: shariya873@gmail.com

---

## 15. FAQ

> **Eyebrow:** FAQ · **Title:** Questions, answered
> **Subtitle:** Everything you need to know before you sign in.

**What exactly is UniNexus?**
UniNexus is an AI academic copilot for universities. It combines role-based dashboards for
students, faculty and admins with a retrieval-augmented assistant that answers questions
using your institution's own approved documents.

**How is it different from ChatGPT?**
Generic chatbots answer from general training data and can hallucinate. UniNexus only answers
from documents your institution has uploaded and approved, attaches citations and a
confidence score to every response, and respects each user's permissions.

**Is the AI mandatory to use?**
No. The AI assistant is one part of the platform. Attendance, grading, transcripts, course
management, analytics and admin tooling all work independently — the AI simply makes them faster.

**Who can use the platform?**
Three roles, each with a tailored experience: students get a learning copilot and planner,
faculty get teaching and grading tools, and admins govern users, knowledge and the AI itself.

**Is our data secure?**
Access is governed by granular role-based permissions with time-limited grants, every action
is logged, and document retrieval is scoped so answers never surface content a user isn't
allowed to see.

**Can we use our own AI provider?**
Yes. UniNexus ships with an OpenAI integration (gpt-4o and text-embedding-3-small) and a
pluggable provider layer, plus a built-in mock provider for offline development and testing.

---

## 16. Call to Action

**Headline:** Bring AI to every corner of your campus

**Body:** One platform for students, faculty and administrators — grounded, secure and ready
to use today. Sign in and see your role come to life.

**Actions:** *Get started* · *Try the assistant*

**Footer tagline:** The AI academic copilot for universities — grounded answers, automated
teaching tools and live operational insight, in one platform. *Reimagining university
management with AI.* — Built by Mohammad Shariya.
