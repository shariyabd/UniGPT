# UniGPT — Product Overview & Landing Page Content

> A single, slide-ready source of the full landing-page narrative. Sections follow the
> **landing page order** (top → bottom) so each `##` heading maps to one section / slide.
> Content mirrors the live marketing copy in `resources/js/components/landing/`.

**One-liner:** UniGPT is an **AI academic copilot for universities** — grounded, cited
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
10. [Why UniGPT — the differentiator](#10-why-unigpt--the-differentiator)
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

**Sub-headline:** UniGPT unifies students, faculty and administrators on one AI platform —
grounded answers from your own academic documents, automated teaching tools, and live
operational insight. **One login, three tailored experiences.**

**Primary actions:** *Launch UniGPT* · *See how it works*

**Built for everyone on campus:** Students · Faculty · Administrators · Departments

**Product proof (sample interaction):**
- Q: *"What's the late submission policy for CS401?"*
- A: *"Submissions are accepted up to **72 hours late** with a 10% penalty per day. Beyond
  that, a grade of zero applies unless an extension was approved."*
- Tagged with **96% confidence**, cited from **CS401 Syllabus · p.4**, answered in **~1.2s**.
- Floating proof points: 🔒 *Cited from your docs* · ⚡ *Answer in ~1.2s*

---

## 2. Problems

> **Eyebrow:** The problem · **Title:** Campuses run on busywork the AI era should have ended
> **Subtitle:** Every role loses hours to tasks software should handle. UniGPT was built to remove them.

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
> **Subtitle:** UniGPT turns your institutional knowledge into an assistant that cites its sources.

| Step | Stage | What happens |
|---|---|---|
| 1 | **Ask** — Ask in plain language | A student, lecturer or admin asks a question — about a policy, a deadline, a grade, or campus data. |
| 2 | **Ground** — Retrieve & reason | UniGPT searches your approved documents, pulls the most relevant passages, and reasons over them — never guessing. |
| 3 | **Answer** — Answer with proof | You get a precise answer with citations, a confidence score, and suggested follow-ups you can trust and save. |

---

## 4. Features

> **Eyebrow:** Core platform · **Title:** One platform, every academic workflow
> **Subtitle:** Real, shipped capabilities — not roadmap promises. Here is what UniGPT does today.

**Flagship — Grounded RAG assistant**
Ask anything. UniGPT retrieves passages from your approved documents, answers with inline
citations and a confidence score, and suggests follow-ups. Multiple modes — academic, exam
prep, research — keep responses on-task.
*Highlights:* Citations · Confidence scoring · Chat modes · Saved answers · Session history

**Supporting features**

| Feature | What it does |
|---|---|
| **Document knowledge base** | Upload, review and approve. Approved docs are chunked, embedded and made searchable automatically. |
| **Role-based dashboards** | Students, faculty and admins each get a tailored home with the tools and data that matter to them. |
| **Teaching automation** | Generate quizzes, assignments and rubrics, and draft grading feedback with an AI teaching assistant. |
| **Attendance & analytics** | Mark attendance in seconds; track rates, grades, GPA and engagement across every course. |
| **Granular RBAC** | Roles map to fine-grained permissions with time-limited grants — every action is gated and logged. |
| **Announcements & alerts** | Broadcast to any audience and notify the right people on exams, grades and new materials. |
| **Registration & rostering** | Admins assign students to course sections; students confirm in one click — seats, terms and rosters stay consistent. |
| **Exams, transcripts & calendar** | Schedule exams, auto-build transcripts with GPA & CGPA, and merge every deadline into one calendar. |

---

## 5. User Roles

> **Eyebrow:** Built for three · **Title:** One platform, three tailored experiences
> **Subtitle:** Switch roles to see exactly what each user gets the moment they sign in.

### 🎓 Student — *A copilot for every academic question*
From the first lecture to final transcript, students get instant, cited answers and a
planner that keeps the term on track.
- AI chat with citations, confidence & saved answers
- Personal dashboard: courses, CGPA & deadlines
- One-click registration for assigned course sections
- Attendance, transcript & GPA tracking
- Course roadmap, materials & document library
- Exam schedule, calendar, notes & tasks
- *Sample dashboard metrics:* 6 courses · 3.78 CGPA · 94% attendance

### 📊 Faculty — *Teach more, administrate less*
Faculty manage courses end to end while the AI teaching assistant drafts assessments and
feedback in seconds.
- AI teaching assistant: quizzes, assignments & rubrics
- Manage taught sections & publish course materials
- One-click attendance with live class stats
- Grading workspace with AI-drafted feedback
- Per-course analytics & grade distributions
- *Sample dashboard metrics:* 4 courses · 128 students · 12 to grade

### ⚙️ Admin — *Total control, live oversight*
Admins govern users, knowledge and the AI itself — with a real-time view of system and
academic health.
- User, role & permission matrix management
- Course catalog, sections, terms & student assignment
- Document approval workflow & knowledge base
- Institution-wide analytics & top queries
- AI provider settings, prompts & retrieval tuning
- System monitor, departments & announcements
- *Sample dashboard metrics:* 2.4k users · 860 docs · 99.9% uptime

> *Dashboard metrics are illustrative.*

---

## 6. AI Engine

> **Eyebrow:** The intelligence · **Title:** Retrieval-augmented, not just a chatbot
> **Subtitle:** UniGPT grounds every answer in your institution's own documents — so responses are accurate, attributable and auditable.

**The RAG pipeline**

| Step | Stage | What happens |
|---|---|---|
| 1 | **Chunk** | Approved documents are split into overlapping passages on approval. |
| 2 | **Embed** | Each passage becomes a vector and is stored alongside its source. |
| 3 | **Retrieve** | Your question is embedded and matched by cosine similarity, top-K. |
| 4 | **Answer** | The model reasons over retrieved context and cites every claim. |

**Engine highlights**
- **Pluggable providers** — OpenAI gpt-4o + text-embedding-3-large in production, with a
  built-in mock provider for offline development.
- **Scoped to what you can see** — Retrieval only ever touches approved documents the
  current user is permitted to read — answers never leak.

---

## 7. Intelligence Layer

How UniGPT turns raw activity into decisions, automation and trustworthy answers.

- **Grounded reasoning, not guessing** — answers are constructed from retrieved,
  approved passages; if it isn't in your documents, it isn't asserted.
- **Confidence & citations on every answer** — each response carries a confidence score
  and links back to the exact source (e.g. *CS401 Syllabus · p.4*).
- **Suggested follow-ups** — the assistant proposes next questions to keep guidance moving.
- **Chat modes** — academic, exam-prep, research and more steer tone and focus per task.
- **Teaching automation** — auto-generates quizzes, assignments and rubrics, and drafts
  grading feedback for faculty to review and edit.
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
- **Faculty → Student:** course materials, published quizzes/assignments, and grades + feedback.
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

## 10. Why UniGPT — the differentiator

> **Eyebrow:** Why UniGPT · **Title:** The difference is grounding
> **Subtitle:** Traditional systems digitise paperwork. UniGPT removes it.

| Capability | Traditional systems | UniGPT |
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
| **1.2s** | Average grounded answer time |
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

| Feature | Stage | What it adds |
|---|---|---|
| **Real-time student–faculty chat** | In design | Direct, live messaging between students and faculty — separate from the AI tutor — with presence, typing and unread counts. |
| **Telegram & WhatsApp alerts** | Planned | Push assignments, scheduled quizzes, syllabus changes and announcements straight to the phones students already use. |
| **Digital library + AI assistant** | Exploring | A library of academic books and resources with an AI assistant that answers strictly from the library's own content — grounded, cited, on-syllabus. |

**Also on the engineering roadmap** *(later phases — most depend on additional infrastructure)*
- **Streaming chat** — token-by-token responses as they generate.
- **Voice I/O** — speak to the assistant with speech-to-text & text-to-speech.
- **Predictive analytics** — early-risk signals & recommendations from academic data.
- **More AI providers** — Gemini and self-hosted models alongside OpenAI.
- **Versioning & memory** — document version history and longer conversation memory.

---

## 14. Creator

**Mohammad Shariya** — Full-stack engineer · Builder of UniGPT

> "I built UniGPT because campuses are rich with knowledge but poor at making it reachable.
> Students wait on answers their handbook already holds; faculty lose evenings to busywork;
> admins fly blind until term's end. UniGPT is my attempt to put a single, trustworthy AI
> copilot behind every academic interaction — one that cites its sources, respects
> permissions, and gives each role exactly what they need."

Contact: shariya873@gmail.com

---

## 15. FAQ

> **Eyebrow:** FAQ · **Title:** Questions, answered
> **Subtitle:** Everything you need to know before you sign in.

**What exactly is UniGPT?**
UniGPT is an AI academic copilot for universities. It combines role-based dashboards for
students, faculty and admins with a retrieval-augmented assistant that answers questions
using your institution's own approved documents.

**How is it different from ChatGPT?**
Generic chatbots answer from general training data and can hallucinate. UniGPT only answers
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
Yes. UniGPT ships with an OpenAI integration (gpt-4o and text-embedding-3-large) and a
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
</content>
