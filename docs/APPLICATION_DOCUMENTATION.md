# UniNexus — Complete Application Guide

> A plain-language guide to everything UniNexus does. It explains every feature for each
> type of user (Student, Faculty, Admin), how each feature works from start to finish,
> what it connects to, and how information flows through the system.
>
> No technical background needed — if you can use a website, you can follow this guide.

**What UniNexus is:** an AI-powered academic assistant for a university. It gives students,
teachers, and administrators their own dashboards, and adds an AI helper that answers
questions using the university's *own* documents — and always shows where each answer came from.

---

## Table of Contents

1. [The Big Picture](#1-the-big-picture)
2. [How the University is Organised](#2-how-the-university-is-organised) — the foundation everything sits on
3. [What Admins Can Do](#3-what-admins-can-do)
4. [What Faculty Can Do](#4-what-faculty-can-do)
5. [What Students Can Do](#5-what-students-can-do)
6. [The Shared Engines](#6-the-shared-engines) — AI, notifications, messaging
7. [Start-to-Finish Workflows](#7-start-to-finish-workflows)
8. [Quick Reference](#8-quick-reference)

---

## 1. The Big Picture

UniNexus is built around **three types of users**, each with their own dashboard:

| User | Who they are | What they mainly do |
|---|---|---|
| **Admin** | Registrar, IT, or university leadership | Run the institution: manage people, departments, terms, the course catalogue, class sections, the document library, exams, and the AI settings |
| **Faculty** | Teachers / instructors | Teach their assigned classes: share materials, set assignments and quizzes, take attendance, grade work, and use an AI teaching assistant |
| **Student** | Enrolled learners | Study: chat with the AI tutor, register for courses, read materials, submit assignments, take quizzes, and track their attendance and grades |

**One idea ties everything together.** The university is organised top-down — departments
contain courses, courses are offered as class sections each term, students enrol into those
sections, and everything else (materials, assignments, quizzes, attendance, exams, grades)
hangs off the section a student is actually in. Separately, the university's documents flow
through an approval process and become the knowledge the AI tutor draws on.

```
Departments → Courses → Class sections (each term) → Enrolled students
                              ↓
        Materials · Assignments · Quizzes · Attendance · Exams · Grades

Documents → Reviewed & approved → Become the AI tutor's knowledge (with citations)
```

Every page is protected. The system always checks **who you are** (student, faculty, or
admin) and **what you're allowed to do** before showing you anything.

---

## 2. How the University is Organised

> This section explains the structure that every feature depends on. It's worth reading
> first — the rest of the guide refers back to it.

### 2.1 People and roles

Every person has an account with a name, email, and a **role**: Student, Faculty, or Admin.
A person also belongs to a **department** (e.g. Computer Science) and, for students, a
**semester level** (their year of study).

- An account can be **deactivated** — a deactivated person is signed out and can't log back in.
- One special administrator account is **protected**: it can never be deleted, deactivated, or
  have its role changed, so the university can never accidentally lock itself out.
- Roles can be **temporary** — an admin can grant someone a role that automatically expires on
  a set date.

### 2.2 Permissions — who's allowed to do what

Each role comes with a set of **permissions** — 46 fine-grained "can do this" switches (for
example, "can approve documents", "can grade assignments", "can configure the AI"). Admins can
adjust which permissions each role has from a visual editor, so the university can tailor
access to its own policies.

When you try to open a page or take an action, the system checks two things: that your **role**
is allowed there, and that you hold the specific **permission** required. If not, you're sent
back to your own dashboard with a short explanation.

### 2.3 The academic structure

| Building block | What it means |
|---|---|
| **Department** | An academic unit such as Computer Science or Business. People and courses belong to a department. |
| **Term** | A semester — Fall, Spring, or Summer. Exactly one term is the "current" term at any time, and registration can be opened or closed for it. |
| **Course** | A catalogue entry — the official definition of a subject (its code, credit value, and which year it belongs to). Courses are managed by admins. |
| **Class section** | The actual class a student attends — for example, "Section A of CS301 in Summer 2026". A section has one teacher and its own group of students. **This is where the real activity happens.** |
| **Enrolment** | The link that puts a specific student into a specific section, carrying their status, grade, and progress. |

**A few important rules that keep everything consistent:**

- **The class section is the unit that matters.** Materials, assignments, quizzes, attendance,
  exams, and grades all belong to a *section*. A teacher or student only ever sees the content
  for *their own* section — never another section's.
- **Joining a course is a two-step, admin-controlled process:**
  1. An admin **assigns** a student to a section. This reserves a seat for them.
  2. The student then **confirms** the registration while the term's registration window is
     open. Only then are they officially enrolled.
  - A student can only register for sections an admin has placed them in — there's no
    free-for-all sign-up. Reserved and confirmed seats both count toward a section's capacity.
- **Teachers stay within their own department.** A section's teacher must belong to the same
  department as the course. As a result, students only ever see and message faculty from their
  own programme.
- **End-of-term rollover:** when an admin closes a term, that term's sections wind down,
  still-active enrolments are marked "completed" (grades are locked in), and the next term can
  be promoted to current — rolling the whole university into the new semester.

---

## 3. What Admins Can Do

> Admins run the institution. Most admin actions are recorded in an activity log, which feeds
> the "recent activity" feed on the dashboard.

### 3.1 Dashboard
A single overview screen: how many users and how many are online now, how many documents are
approved vs. waiting for review, new sign-ups this week, the latest documents awaiting
approval, a recent-activity feed, a **system health** indicator (database, storage, and AI
status shown as healthy / degraded / down), and **student insights** (how many students are
enrolled this term and the overall attendance rate).

### 3.2 Document Library & Approval ⭐
*This is the most important admin area — it's where the AI tutor's knowledge comes from.*

- **Uploading:** anyone with permission can upload a document (handbook, syllabus, policy,
  lecture notes, and so on), choose who should be able to see it (students, faculty, admins),
  tag it, and assign it to departments. The system rejects an exact duplicate of a file that's
  already there. A new document starts as **"pending review."**
- **The library view:** admins see documents in every state (including their own pending and
  rejected uploads) and can search and filter by category, department, file type, or status.
- **The approval queue:** documents waiting for review, with each item's full history and any
  reviewer comments.
- **Reviewing a document**, an admin can:

  | Decision | What happens |
  |---|---|
  | **Approve** | The document is read, indexed, and becomes part of the AI tutor's knowledge (see below). |
  | **Reject** (reason required) | The document is set aside and removed from the AI's knowledge. |
  | **Request changes** (comment required) | Sent back to the uploader to fix and resubmit. |
  | **Comment** | Leaves a note without changing the decision. |

- **How approval turns a document into AI knowledge:** the moment an admin approves a document,
  the system automatically reads its text, breaks it into small passages, and indexes each
  passage so the AI can find and quote it later. Only **approved** documents that a person is
  allowed to see can ever appear in that person's AI answers. If a document is later rejected or
  edited, its indexed passages are removed, so it instantly disappears from AI answers.
- Admins can also download, preview, bookmark, or delete documents.
- **What it connects to:** approved documents appear in the student and faculty **document
  libraries** *and* become the sources the **AI tutor cites**.

### 3.3 Analytics
A university-wide usage overview: total users and active users, how many AI questions have been
asked, how many documents and chat sessions exist, a breakdown of AI usage by department, the
most common questions students ask, and a count of users by role.

### 3.4 AI Usage Monitor & Access Control ⭐
Tracks how much each person is using the AI, including a running total of usage per person and
per request, who's most active, and recent activity. Crucially, it lets an admin **revoke a
person's AI-chat access** (with a reason, either permanently or until a set date) to control
costs or address misuse — and reinstate it later. A blocked person sees a clear message
explaining why and until when. (The protected admin account can't be blocked.)

### 3.5 User Management
Create, edit, activate/deactivate, and delete user accounts; assign roles; and search or filter
the user list. The protected admin account is shielded from deletion, deactivation, and
role changes. If an account can't be deleted because it has linked records, the system suggests
deactivating it instead.

### 3.6 Role & Permission Editor
A visual grid for turning permissions on or off for each role, so the university can match the
system to its own access policies.

### 3.7 Course Catalogue & Sections
- **Courses:** the official catalogue — create, edit, and remove courses (a course can't be
  deleted while it still has enrolled students).
- **Sections:** create the actual class offerings for a term, assign a teacher (who must be in
  the course's department), and place students into them. Placing a student **reserves a seat**
  and notifies the student to confirm; removing one drops them and notifies them too.
- **Prerequisites:** a course can require other courses first (picked from a simple
  multi-select on the course form). A student can only confirm a section once they've
  **completed** every prerequisite — being merely enrolled in one isn't enough.
- **Waitlists:** if an admin places a student into a section that's already **full**, the
  student joins that section's **waitlist** (first come, first served) instead. Whenever
  someone drops the section, the first student in the queue automatically receives a
  reserved seat and a notification to come and confirm — no admin action needed.
- This area is the **bridge** between admin setup and student self-registration.

### 3.8 Terms & Rollover
Manage the academic calendar: create the three standard terms (Fall, Spring, Summer), set which
one is current, **open or close registration**, and **close a term** at its end (which winds
down its classes, locks in grades, and can promote the next term). The current term drives the
dashboard insights and the student registration window.

### 3.9 Departments
Create, edit, and remove departments. A department can't be deleted while it still has people or
courses, to avoid leaving anything orphaned.

### 3.10 System Monitor
A live look at the server's health — processor load, memory, storage space, and whether the
database, background jobs, cache, and AI service are responding. Read-only; it never makes a
billable AI call just to check status.

### 3.11 Announcements
Send a broadcast notification to an audience (everyone, or just students/faculty/admins).
Admins can see past announcements with how many people received each, and edit an announcement
after sending.

### 3.11a Discussion Moderation
A moderation queue for the course discussion feeds (see 4.13 / 5.19). When a member reports a
post or comment, it lands here for an admin to work: the item is shown with its reported content
and the reason, and the admin can **dismiss** the report (leaving the content in place) or
**remove** the content. Faculty already moderate their own sections; this queue is the
university-wide backstop for anything escalated to admins.

### 3.12 Exams & Timetable
Schedule exams for a course — with a date, time, duration, location, total marks, and
instructions. If no specific section is chosen, the exam is scheduled for every section of the
course at once. Scheduling an exam notifies the affected students, and the exam appears on both
faculty and student timetables.

### 3.12a Exam Security ⭐
The global gate over the class-test proctoring layers. For each layer (fullscreen, tab-switch,
clipboard block, one-at-a-time, disable-back, shuffle questions, shuffle options, watermark,
integrity notice, fingerprint, behaviour logging, risk scoring, webcam recording, screen
recording, face liveness, snapshot evidence, phone detection) the admin controls two switches: whether it is **available** to faculty at all, and
whether it is **on by default** on a new test. Turning a layer off removes it from the faculty
authoring form and stops any existing test from applying it. Faculty still choose, per test, which
of the available layers actually run. The heavier "consent" layers (webcam / screen) are clearly
marked because they prompt the student for camera / screen access before the exam begins. A
"How each layer works" offcanvas explains every layer's step-by-step flow with the live
configured timings (grace periods, free warnings, retention), so the guide can never drift from
actual behaviour.

### 3.13 AI Settings ⭐
The control panel for the AI itself. Admins choose the AI provider (the real OpenAI service or a
built-in offline "demo" mode), the models used, how creative vs. precise answers should be, how
many source passages to pull per question, the minimum relevance for a source to count, an
optional custom instruction for the AI's tone, and the list of supported answer languages. The
API key is **write-only and stored securely** — it's never shown back on screen. A **"Test
connection"** button actually contacts the provider and reports whether it's reachable. These
settings instantly affect student chat, the faculty assistant, AI generation, and how documents
are searched.

---

## 4. What Faculty Can Do

> Everything a teacher sees is limited to **the sections they personally teach** — their own
> students, their own materials, their own grades. They never see another teacher's classes.

### 4.1 Dashboard
A teaching overview: number of courses and students, work waiting to be graded, AI usage,
tiles for each active class (with student/material/assignment counts and average progress), and
recent activity.

### 4.2 My Students & Messages
A roster of the teacher's own students for the current term, plus a messaging view to start a
chat with any of them. (Messaging is fully working — see [The Shared Engines](#6-the-shared-engines).)

### 4.3 My Courses
A read-only view of the classes they teach (the catalogue is admin-run). Opening a class shows
that section's roster with each student's grade, progress, and status, along with its materials
and assignments.

### 4.4 Course Materials
Teachers upload and manage learning resources for their class — lecture notes, slides, readings,
and so on — and mark them published or not. **Publishing a material notifies the class's
students**, who then see it in their own materials list.

### 4.5 My Documents
Teachers can submit documents into the **same approval queue** admins review. Their submissions
default to being visible to students and faculty. The view shows any reviewer feedback, and once
approved the document joins the AI tutor's knowledge.

### 4.6 AI Teaching Assistant ⭐
A ChatGPT-style assistant for teachers, running on the same cited-answer AI engine as the
student tutor — with answers **streaming in live, word by word**. Beyond chatting, it can
**generate** draft quizzes, draft assignments, and suggested grading feedback. These drafts are *previews only* — nothing reaches students until
the teacher explicitly publishes:

- **Publish as an assignment:** turns a draft into a real graded assignment for the teacher's
  class, and notifies the students.
- **Publish as a class test:** turns draft questions into a real, auto-graded, proctored quiz
  for the class.

Because the AI also runs in an offline demo mode, all of this works even without an API key.

### 4.7 Attendance
For a chosen class and date, the teacher marks each student present, absent, and so on. Re-saving
the same day simply updates it (no duplicates), and only actual class members can be marked. This
feeds the student's attendance view and the "at-risk students" flag in analytics.

### 4.8 Exams
A read-only timetable of the teacher's upcoming and past exams (exams are scheduled by admins).

### 4.9 Class Tests (Quizzes)
Teachers create interactive multiple-choice / true-false quizzes — by hand or by accepting an
AI-generated draft — for a class they teach. Publishing notifies students. A results screen shows
every attempt (student, score, status, warning count, **risk score**, and whether it was flagged)
plus summary statistics. The quiz is **fully server-controlled**: correct answers never reach the
student's browser during a live attempt, the timer can't be tampered with, and proctoring
violations can disqualify an attempt.

**Per-test security layers.** When authoring a test the teacher picks which proctoring layers
apply, from a set of independent, configurable options: fullscreen enforcement, tab-switch
detection, clipboard/right-click blocking, one-question-at-a-time, disable-going-back, randomise
question order, randomise answer options, identity watermark, browser fingerprint, behaviour
logging, risk scoring, an AI assessment-integrity notice, **webcam** / **screen recording**, and
three on-device camera-AI layers: **face liveness** (a blink-verified gate keeps questions hidden
until a live face is confirmed — a photo can't blink — then monitors continuously: 3s without a
face shows a warning and blurs the paper, 8s locks it; the first two incidents are free, further
ones count as violations; a logged 30s bypass ensures a student whose face can't be detected is
never locked out), **snapshot evidence** (webcam photo bursts at flagged moments plus random
samples — moment-based evidence instead of hours of video), and **phone detection** (an
on-device model flags a phone raised into frame, with photo evidence — a review signal, never an
auto-violation). Detection runs entirely in the browser; no frames are uploaded for it.
An administrator decides globally which layers are available and which are pre-ticked (see 3.x
Exam Security). The exam's runtime and the server both apply only the layers that test enabled.

**Attempt review.** From the results screen a teacher opens a per-student **Review** dossier:
the student's identity and device fingerprint, a computed 0–100 risk score with its contributing
factors, a full behaviour timeline (focus loss, clipboard attempts, answer timing, idle spans),
and — when recording layers were on — in-browser playback of the webcam and screen recordings,
plus a trigger-labelled **snapshot photo strip** (slider viewer with keyboard navigation) when
snapshot evidence was on. Recordings and snapshots are stored privately, only ever visible to
faculty and admins, and pruned automatically after the retention window.

### 4.10 Analytics & At-Risk Early Warning ⭐
Insights for the teacher's classes: students, attendance, grading backlog, grade distribution,
and submission rates — plus an **early-warning list of at-risk students**. A student is flagged
on any of four signals: attendance below 75%, two or more missed assignment deadlines, a
class-test average below 50%, or a failing grade. Two or more signals mark them **High risk**
(one signal = **Watch**), each flag shows exactly *why* ("Attendance 62%", "3 missed
deadlines"…), and a message button opens a direct chat with that student so flagging turns into
intervention in one click. The teacher's dashboard shows a running count of flagged students.

### 4.11 Assignment Management
Edit, publish/unpublish, or delete the assignments they've set. Editing notifies students and
highlights a changed due date. An assignment can also have **peer review** switched on from
the course's edit screen — see 5.13 for what students then do.

### 4.12 Grading ⭐
A queue of submissions per assignment (graded, pending, and late counts at a glance). The teacher
enters a grade and feedback (optionally using **AI-suggested feedback** — strengths, areas to
improve, and a draft comment they can edit first), and the student is notified. Grades flow
straight into the student's transcript and progress views.

Three extra helpers sit inside the grading view:

- **AI rubric drafting:** when an assignment has a rubric, one click asks the AI to score the
  submission against each rubric criterion, with a short justification per criterion. The
  scores simply pre-fill the grading form — the teacher reviews and edits everything before
  saving, and nothing reaches the student until they do. (Without an AI key, a clearly
  labelled built-in estimate is used instead.)
- **Similarity screening:** in the background, every submission is automatically compared
  against the other submissions for the same assignment (including the text inside uploaded
  PDF and Word files). If two are suspiciously similar, both show a warning badge in the
  grading queue, and the teacher can open the matching excerpts side by side to judge for
  themselves. It only ever *flags* — it never penalises anyone automatically.
- **Peer review at a glance:** if the assignment has peer review enabled (see 5.13), the
  grading view shows the average rating a submission received from classmates — as extra
  context for the teacher, never as the grade.

### 4.13 Discussion Feed
Each section the teacher runs has its own **discussion group**, with the teacher and the section's
enrolled students as members. Teachers **take part** like everyone else — posting, commenting,
liking — and also **moderate their own sections:** they can **pin** an important post to the top or
**delete** an off-topic or inappropriate post or comment. Members can **report** anything they
think breaks the rules; reports the teacher doesn't handle can be escalated to the admin
moderation queue (see 3.11a). Teachers only ever see and moderate the feeds for the sections they
personally teach.

### 4.14 Office Hours
Teachers publish **bookable office-hours slots** — a time window plus an optional room or
meeting link and note. Students of their sections book them one-per-slot (no double-booking);
the teacher sees who booked, can cancel a booking (re-opening the slot) or remove a slot
entirely, and everyone affected is notified automatically.

### 4.15 Anonymous Course Feedback
Teachers can **open or close feedback** for each section they teach. While it's open, each of
the section's students can leave one anonymous rating and comment. To protect anonymity, the
teacher sees **nothing until at least three students have responded**; after that they see the
average rating and the comments — shuffled, with no names and no timestamps, so nothing can be
traced back to an individual student. One click asks the AI to **summarise the main themes**
in the comments (what's working, what to improve).

### 4.16 Question Bank
A per-course library of reusable quiz questions. Teachers build it by writing questions
directly or by **importing the questions from an existing class test** (duplicates are
skipped), tagging each question with a topic and difficulty. From the bank they can assemble
a **draft class test in one click** — it stays a draft until published, like any other test.
The bank quietly helps students too: enrolled students can generate practice quizzes straight
from it (see 5.24).

---

## 5. What Students Can Do

> Students see only their own enrolled classes and their own data.

### 5.1 Dashboard
A study hub: current courses, key numbers (course count, overall GPA, saved answers, chat
sessions), recent AI chats, upcoming deadlines, attendance rate, a study streak, and how far
through their course materials they are.

### 5.2 AI Tutor (Chat) ⭐ — the flagship feature
The student asks questions in natural language and gets answers **grounded in the university's
own approved documents — plus the student's own notes and their courses' materials** — with
citations showing exactly which source each fact came from. Answers **stream in live,
word by word**, like a modern chat assistant, instead of arriving all at once. How a
question becomes an answer:

1. **Quick replies for small talk.** Greetings or "what can you do?" get an instant, friendly
   reply without any heavy processing.
2. **Finding the right sources.** For real questions, the system searches everything the
   student may draw on — approved library documents, **their own saved notes** (including
   scanned handwriting), and **files their teachers shared with their class** — and picks
   the handful most relevant to the question. Sources are labelled so a "Personal Note" or
   "Course Material" citation is clearly distinguished from a library document.
3. **Showing its work.** Those sources become numbered citations attached to the answer, each
   with a short excerpt and a confidence indicator.
4. **Writing the answer.** The AI is given the student's context (their department, year, and
   courses, so it understands course nicknames), told which language to answer in, and asked to
   answer using the sources first — citing them — and to be honest when the documents don't
   cover something.
5. **Saving the conversation** with its confidence level and sources, so it can be revisited.

**The tutor can also *do* things, not just answer.** When a student asks it to — "book me a
slot with Prof. Smith", "make me a practice quiz on recursion", "add a study task for Friday"
— the AI can check upcoming deadlines, list the student's courses, list / book / cancel
office-hours slots, generate a practice quiz or a flashcard deck, or create a study task, and
then reports back what it did. Each step is shown in the chat as it happens (and kept in the
conversation history), so nothing happens invisibly. Crucially, these actions go through
exactly the same rules as clicking the buttons yourself — the AI can't book a slot that isn't
open to that student, and first-click-wins on office hours still applies.

The student decides when the tutor may act: a prominent switch above the message box toggles
between **"⚡ Agent"** (the default — actions allowed, with hints and example prompts that
invite requests like "Book office hours with my professor") and **"💬 Answers only"** (the
tutor just explains — the ability to act is switched off on the server, not merely hidden).
Replies where the tutor actually did something carry a small "⚡ Agent" badge next to the AI
badge, alongside the usual step-by-step action trail.

Students can pick a **chat mode** (general, academic, exam prep, assignment help, and so on),
choose an answer language, and organise conversations (pin, rename, archive, delete). If an admin
has blocked their AI access, they see a clear message instead of the chat box. Only approved,
visible documents are ever used as sources.

### 5.3 Saved Answers
Students can save a useful AI answer into folders with their own notes, star it, and jump back to
the original conversation any time.

### 5.4 Roadmap
A semester-by-semester view of degree progress: each course's teacher, status, and grade,
assignments marked done or pending, and the GPA per semester and overall.

### 5.5 Document Library
Browse the approved documents the student is allowed to see, with search and filters, plus
bookmark, download, and in-browser preview. (This is the same library the AI tutor draws on.)

### 5.6 My Documents
Students can submit their own documents into the admin approval queue (visible to students by
default), see reviewer comments, and edit a submission (which sends it back for re-review). Once
approved, it becomes part of the AI tutor's knowledge.

### 5.7 Course Materials
The published materials for the student's own class, grouped by course, each downloadable and
markable as "completed" to track progress.

### 5.8 Attendance
A read-only view of attendance per course — totals, attended, absent, and rate — plus recent
records. (The data is entered by teachers.)

### 5.9 Transcript
All courses grouped by semester with grades and credits, a GPA per semester, and an overall GPA.

### 5.10 Exams
The student's upcoming and past exams, with countdowns. Only their own classes' exams appear.

### 5.11 Calendar
One combined calendar of assignment deadlines, exam dates, the student's personal tasks, and
their **booked office-hours meetings**. Tasks can be created right from the calendar. The
calendar can be **downloaded as an .ics file** or **subscribed to by URL** so it stays in sync
inside Google Calendar, Outlook, or Apple Calendar automatically.

### 5.12 Course Registration ⭐
Where a student **confirms or drops** the sections an admin has placed them in, while the term's
registration window is open. The page shows the seats reserved for them (with seats remaining)
and the ones they've already confirmed. Registration is only possible for sections they've been
placed in — placement is controlled by the administration.

Two more rules apply at confirm time:

- **Prerequisites:** each reserved seat shows the course's prerequisites as badges — met or
  not met. A student can't confirm a section until they've **completed** every prerequisite
  course, and the page explains exactly what's missing.
- **Waitlists:** if the section filled up, the student sees their **place in the queue**
  instead of a Register button. When a seat frees up, the first student in line automatically
  gets a reserved seat and a notification to come back and confirm.

### 5.13 Assignments
A list of the student's assignments, each tagged pending, submitted, late, or graded. The student
submits text and/or a file before the deadline (submitted after the deadline is marked "late"),
which notifies the teacher. Once an assignment is graded it locks. Submitting feeds the teacher's
grading queue.

**Peer review.** When a teacher switches peer review on for an assignment, students who have
submitted are each given up to two classmates' submissions to review — anonymously, never their
own, and spread out so the least-reviewed work gets looked at first. A reviewer leaves a rating
and a comment; the author is notified and sees the feedback they received without ever learning
who wrote it. Peer ratings are advice for the author (and context for the teacher) — they are
never part of the grade.

### 5.14 Class Tests (Quizzes) ⭐
Students take their teachers' interactive quizzes under **fullscreen proctoring**:

- Starting a quiz begins a timed attempt that can't be restarted to gain extra time.
- Questions appear **without** the correct answers, optionally shuffled, with a tamper-proof
  countdown.
- Leaving fullscreen or switching tabs is recorded as a warning; too many warnings disqualify
  the attempt.
- On submit (or when time runs out), the quiz is **graded instantly and automatically**, the
  teacher is notified, and the student sees their score with a per-question breakdown.

### 5.15 Notes & 5.16 Tasks
Personal productivity tools. **Notes** can be tagged to a course and pinned. **Tasks** have a due
date, priority, and optional course, can be ticked off, and show up on the calendar.

On the Notes page, a student can also **snap handwritten notes into text**: they upload a photo of
a handwritten page and the AI (gpt-4o vision) transcribes it into editable text, which is saved as
an ordinary note they can then tidy up, tag, and pin like any other.

### 5.17 My Faculty & Messages
A directory of the student's actual teachers (drawn automatically from their enrolments), with a
messaging view to chat with any of them. Because teachers stay within their own department, a
student only ever sees faculty from their own programme.

### 5.18 Profile & Settings
Edit name, bio, and avatar; and set preferences — light/dark theme, notifications on/off, the
preferred answer language for the AI tutor, and whether to receive **email digests and
reminders** (see 6.2).

### 5.19 AI Study Planner
Looks at the student's **upcoming deadlines** — assignments, exams, and class tests — and turns
them into a suggested **study schedule**, spreading the work across the days before each deadline.
The student reviews the plan and saves the sessions they want as their own personal **Tasks**, so
they show up on the calendar and in the to-do list alongside everything else.

### 5.20 My Progress (Learning Analytics)
A personal analytics dashboard that charts how the student is doing over time: their **GPA trend**,
**attendance**, **class-test and assignment score trends**, and **weekly activity** (rendered as
graphs with Chart.js). It's a private, self-reflection view built only from the student's own data
— nothing here is shared with anyone else.

The page also includes a **concept mastery map**: a tile for every topic the student has actually
studied, coloured by how well they know it. Mastery is blended from three real signals — their
practice-quiz results per topic, how well their flashcard decks are sticking (the spaced-repetition
state), and their class-test scores — with no AI involved. Topics scoring below 60 are marked
**weak**, and every weak tile has one-click buttons to generate a practice quiz or a flashcard deck
on exactly that topic — closing the loop from "I'm weak here" to "I'm practising it".

### 5.21 Flashcards
The student builds study **decks** — either by hand, card by card, or by asking the AI to
**generate** a deck from a topic. Reviewing a deck uses **spaced repetition (the SM-2 method)**:
after each card the student rates how well they knew it, and the system schedules that card to come
back sooner or later so the material sticks with less cramming.

### 5.22 Leaderboard
An **opt-in**, gamified ranking. A student who joins earns **XP** for study activity — completing
class tests, submitting assignments, and keeping up attendance. This XP is a separate motivation
score, **deliberately decoupled from official grades**, so competing never affects academic
records. Players can use an **alias** instead of their real name, and the board can be ranked by
**department, semester, or section**. Students who don't opt in simply don't appear.

### 5.23 Discussion Feed
Every course/section the student is enrolled in has its own **discussion group** (membership comes
straight from enrolment). Members **post, comment, and like**, and can **report** anything
inappropriate. The section's teacher takes part and moderates (pinning or removing posts), and
serious reports can reach the admin moderation queue. A student only sees the feeds for the classes
they're actually in.

### 5.24 Practice Quizzes ⭐
Students can quiz **themselves** without waiting for a teacher: pick any topic (optionally tied
to a course), a question count, and a difficulty, and the AI generates a multiple-choice /
true-false quiz. There's no proctoring — this is for learning, not assessment. On submit the
system grades **on the server** (the correct answers never reach the browser beforehand) and
shows a score with per-question explanations. Quizzes can be retaken any number of times, and
one click turns the **missed questions into a flashcard deck** so the weak spots go straight
into spaced-repetition review.

Besides AI generation, a student can build a practice quiz **from their course's question
bank** (see 4.16) — real questions their teacher curated, filtered by topic or difficulty.
Bank quizzes don't use the AI at all, so they work even for a student whose AI access is
blocked.

### 5.25 Study Rooms
Classmates in the same section can create and join **group chat rooms** — one per topic (say,
"Midterm prep squad"). Rooms are strictly section-scoped: only students actually enrolled in
that class can see or join them. Messages are delivered live, every message shows who sent it,
and the last member to leave closes the room. Study rooms are completely separate from the
private student↔teacher messenger.

### 5.26 Office Hours
Students see the **bookable office-hours slots** published by the teachers who actually teach
them, and book one in a click. First click wins — a slot can never be double-booked — and both
sides are notified on booking or cancellation. Booked meetings appear on the student's calendar
(and in its .ics export).

### 5.27 Anonymous Course Feedback
When a teacher opens feedback for one of the student's sections, the student can leave a
**rating and comment — completely anonymously**, once per course. The responses are shown to
the teacher without names or timestamps, shuffled, and not at all until at least three
students have responded — so nothing can be traced back to an individual. The student can
update their response while the feedback window is open.

---

## 6. The Shared Engines

These systems work quietly behind every portal.

### 6.1 The AI tutor and how it stays grounded
UniNexus's AI can run in two modes, switchable by an admin:

- **Live mode** uses the real OpenAI service for the smartest answers (requires an API key).
- **Demo mode** is a built-in, offline stand-in that needs no API key at all — so the entire
  product, including AI chat and AI generation, can be demonstrated with zero external setup or
  cost. If live mode is selected but no key is configured, the system automatically falls back
  to demo mode rather than failing.

What makes the answers trustworthy is that the AI doesn't answer from memory alone — it answers
from the university's **approved documents**, and shows the citations. When a document is
approved it's automatically broken into small passages and indexed; when a student asks a
question, the system finds the most relevant passages they're allowed to see and gives them to
the AI as the basis for its answer. Because the relevance search respects who can see what, two
people asking the same question may get answers built from different sources. Any change to the
document library is reflected immediately. All of this is done with the university's existing
database — there's no separate specialised search system to run.

### 6.2 Notifications
A single notification system delivers updates to the right people: grades posted, new materials,
new or changed assignments, submissions received, enrolment changes, scheduled exams, published
quizzes, due-date reminders, and admin announcements. Each person gets their own copy, shown via
a bell that updates automatically and can be muted in settings. Notifications about class
activity always go to exactly the right class's students (or the class's teacher) — never the
whole university by accident.

Beyond the in-app bell, UniNexus also reaches people by **email**: a **weekly digest** every
Monday morning summarising what's ahead, and **due-soon reminder emails** for assignments,
sent alongside the in-app nudge (with the same de-duplication, so nobody is reminded twice
about the same deadline). Both are optional — a single "email digest" switch in Settings turns
them off — and emails are sent in the background, so they never slow the app down.

### 6.3 Messaging & presence
Students and their teachers can chat directly. A conversation can only be started between a
student and a teacher who **share a class**, which keeps messaging relevant and within a
student's own programme. Messages are delivered live when real-time messaging is enabled, and
fall back to refreshing every few seconds otherwise — either way nothing is lost. A small
"online now" indicator shows who's currently active. The same live-messaging engine also powers
the students' **group study rooms** (§5.25).

### 6.4 Global search (⌘K)
The search box in the header finds more than pages: type a few characters and it also searches
**content** — the documents, notes and course materials the person can access (matched by
*meaning*, not just keywords, using the same engine that grounds the AI tutor), plus their
courses, assignments, class discussions, and their own past AI conversations (each result jumps
to the exact message). Admins can also look up users. Everyone's results are limited to what
they're allowed to see.

---

## 7. Start-to-Finish Workflows

These tie the features together into the journeys people actually take.

### 7.1 Bringing a student on board and into classes
1. An **admin** sets up the department, opens a term, and turns on registration.
2. The **admin** creates the student's account and the course catalogue, sets up class sections,
   and places the student into their sections (reserving seats); the student is notified.
3. The **student** opens Registration, sees their reserved seats, and confirms.
4. The student now sees that class's materials, assignments, exams, quizzes, attendance, and
   teacher everywhere in their portal.

### 7.2 Assigning a teacher to a class
1. An **admin** creates a course and a section and assigns a teacher from the same department.
2. The **teacher's** portal immediately fills in with that class — its roster, materials,
   grading, analytics, and messaging contacts.

### 7.3 The life of an assignment
1. A **teacher** creates an assignment (by hand or by publishing an AI draft), optionally with
   peer review switched on; students are notified.
2. A **student** submits before the deadline (after it, it's marked late); the teacher is
   notified, and the submission is quietly screened for similarity against classmates' work.
3. If peer review is on, submitting also brings the student up to two classmates' submissions
   to review anonymously — and their own work receives anonymous feedback the same way.
4. The **teacher** grades it (optionally with AI-suggested feedback or an AI-drafted rubric
   score they edit first); the student is notified and the grade flows into their transcript
   and roadmap.

### 7.4 The life of a quiz
1. A **teacher** creates and publishes a quiz to their class; students are notified.
2. A **student** starts it and takes it fullscreen under proctoring; warnings are tracked.
3. On submit or time-out, the quiz is **graded instantly**, the teacher is notified, and the
   student sees their result.

### 7.5 Turning a document into AI knowledge
1. **Anyone with permission** uploads a document; it's marked "pending review."
2. An **admin** reviews it — approve, reject, request changes, or comment.
3. On **approval**, the system automatically reads, splits, and indexes it.
4. The document now appears in the right people's libraries and can be **cited by the AI tutor**.
   Rejecting or editing it removes it from the AI's knowledge again.

### 7.6 Asking the AI a question
1. A **student or teacher** asks a question.
2. Small talk gets an instant reply; real questions trigger a search of the approved documents
   they're allowed to see.
3. The most relevant passages are gathered and given to the AI as numbered sources.
4. The AI answers using those sources, citing them, and is honest when the documents don't cover
   something.
5. The conversation is saved, and the usage is recorded for the admin's AI monitor.

### 7.7 Taking attendance
1. A **teacher** picks a class and date and marks each student.
2. The **student** sees their attendance rate; persistently low attendance flags them as
   at-risk in the teacher's analytics.

### 7.8 How notifications reach people
A meaningful event (a grade, a submission, a new material, an enrolment change, an exam, an
announcement) generates a notification for exactly the right people — the relevant class's
students, or its teacher — shown via the notification bell.

### 7.9 Closing out a term
An **admin** closes the current term. Its classes wind down, active enrolments are marked
completed with grades locked in, and the next term can be promoted — moving the whole university
into the new semester.

---

## 8. Quick Reference

### The three roles
- **Student** — learns: AI tutor, registration, materials, assignments, quizzes, attendance,
  transcript, productivity tools.
- **Faculty** — teaches: materials, assignments, quizzes, attendance, grading, analytics, AI
  teaching assistant.
- **Admin** — runs the institution: people, departments, terms, courses, sections, the document
  library and approvals, exams, announcements, analytics, and AI settings.

### Things worth knowing
- **Everything is class-section-based.** Teachers and students only ever see their own class's
  content.
- **The AI only quotes approved documents** the reader is allowed to see, and always shows its
  sources.
- **The AI works with or without an API key** — a built-in demo mode means it can be shown with
  zero external setup.
- **Joining a class is admin-controlled** — students confirm seats they've been placed in; they
  don't sign up freely.
- **Teachers and students can only message** when they share a class.
- **One protected admin account** can never be locked out, so the university always retains
  access.

---

*This guide describes how UniNexus behaves for the people who use it. For developer-level detail
(code structure, data design, conventions), see the technical documents in the project root.*
