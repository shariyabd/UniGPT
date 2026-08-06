# Student Features

This catalog covers the student-only academic workflow and personal productivity tools — everything a learner uses to track their courses, manage deadlines, study, and organize their own materials. It consolidates student-facing features from the UniNexus university portal and the other source products, deduplicated so each capability appears once. Cross-cutting systems that students merely participate in (AI chat, discussions/messaging, payments, global search) live in their own scope files and are listed as pointers at the end. Availability of any item depends on the permissions granted to the student role.

## Overview

### Student Dashboard
The landing page after sign-in, giving an at-a-glance snapshot of the student's academic life. It greets the student by name (with ID, department and semester) and surfaces headline stats — attendance rate, current study streak (consecutive active days), enrolled-course count, cumulative GPA, saved-answer count and chat-session count. Below the stats it shows recent AI conversations (each deep-linking back into that chat), a course-materials summary (total / viewed / pending), a learning-path progress bar with current and next topic, and the nearest upcoming deadlines (urgent ones highlighted). Quick-action cards jump to key tools, and a rotating daily study tip plus motivational quote round out the page.

### Continue Learning
A resume shortcut that takes the student straight back to where they left off — the next incomplete topic, material or course activity — so returning learners can pick up without hunting through the catalog.

### Learning Goals
Personal learning goals the student sets and tracks over the term, giving a target to measure progress against and feeding the sense of momentum shown on the dashboard.

## Academic

### Course Registration
Students self-register for offered sections in their current semester. The page lists available sections per course; the student registers with one click and can drop a section they previously joined. Registration enforces prerequisites — a course whose prerequisites are not yet completed shows the unmet requirements as badges and disables the Register button. If a section is full, the student is placed on a FIFO waitlist and notified; when a seat opens (because someone drops), the head of the waitlist is promoted automatically and told "a seat opened up."

### Roadmap / Learning Path
A detailed semester-by-semester learning path. It lays out all enrolled courses with their instructors, assignment deadlines, current grades, per-course progress and completion status, alongside the student's cumulative GPA and overall program progress. It is the long-form companion to the dashboard's learning-path widget.

### Transcript
A read-only academic record organised by semester, showing courses taken, grades earned, and GPA per semester. It is the student's official results view.

### My Progress (Learning Analytics)
A personal analytics page that visualises study progress with charts (attendance trends, performance over time, activity). It includes a **Concept Mastery Map**: a tier-coloured grid of topics derived from the student's practice-quiz results, flashcard review state and class-test scores, sorted weakest-first. Each weak topic offers one-click **"Practice this"** and **"Make flashcards"** buttons that generate targeted review material on the spot (difficulty eases automatically for the weakest topics) — the adaptive-review loop that turns analytics into action.

### Course Materials
Browse and download the lecture notes, slides and resources faculty upload for the student's courses. Materials can be filtered by course, category and file type, downloaded, and toggled **mark complete** so the student can track what they have reviewed — those counts feed the dashboard's materials summary.

### Assignments
Lists every assignment across the student's courses with filter tabs (All, To-do, Submitted, Graded) and a pending count. Each row shows the course, title, status (not submitted / submitted / submitted late / graded / overdue), due date, point value and — once graded — the score. Overdue pending items are flagged.

### Assignment Detail & Submission
Opening an assignment shows the full prompt, any rubric (criteria with point values), and a submission form. Students submit a written text response and/or attach a file (up to 10 MB, with an upload progress bar). Submissions can be **resubmitted** any time until the assignment is graded; once graded, the submission locks and the instructor's grade and feedback become visible.

### Attendance
A read-only view of the student's attendance per course plus their overall attendance percentage across all enrolled courses.

### Exams
The student's exam timetable — scheduled exam dates for their courses and related exam information, read-only.

## Study Tools

### Notes
A personal note-taking area scoped to the student. Notes can be created, edited and deleted. They are also indexed into the student's private knowledge corpus so the AI copilot can answer questions grounded in their own notes; uploading a photo of handwritten notes to transcribe it is an AI capability (see the AI scope file).

### Saved Answers
A bookmark collection of AI responses the student has chosen to keep for later reference. Saved answers can be viewed, edited and deleted, and the saved-answer count appears on the dashboard. (Answers are produced by the AI copilot — see the AI scope file.)

### Flashcards
Personal study decks reviewed with **SM-2 spaced repetition**. Students create decks manually (title, description, optional course) and add front/back cards, or generate a deck with AI (topic, card count, difficulty, optional course). Studying presents due cards first; the student flips each card, then self-rates recall as **Again / Hard / Good / Easy**, which reschedules that card's next review date. Decks show a "N due" badge and AI-generated decks are marked as such. Missed practice-quiz questions and weak concept-mastery topics can also spawn decks automatically.

### Practice Quizzes
Self-paced, unproctored quizzes for self-testing with instant grading. Students generate a quiz with AI (topic, optional course, 3–15 questions, difficulty) or build one **from the Question Bank** (real instructor-curated questions for a selected course). Taking a quiz shows all questions on one page with no timer; submitting reveals a colour-coded score, per-question review with correct answers and explanations, and attempt history. Missed questions can be turned into a **flashcard deck** with one click, and quizzes can be retaken or deleted.

## Planner & Productivity

### AI Study Planner
Turns the student's upcoming deadlines into a personalised study schedule. The tool proposes study sessions and the student saves the chosen ones as personal **Tasks**. (The generation engine is an AI capability — see the AI scope file.)

### Calendar
A unified calendar showing assignment deadlines, exams, class schedules and booked office hours in one place, alongside the student's personal tasks with priorities.

### ICS Calendar Export & Subscribe Feed
From the Calendar, students can **download** a one-time iCalendar (.ics) file of their schedule, or subscribe an external calendar (Google, Outlook, Apple) to a **signed feed URL** minted for them. The feed is a sessionless, signed link those calendar apps fetch server-to-server and keep in sync, so deadlines and booked meetings show up in the student's everyday calendar app automatically.

### Tasks
A personal to-do list. Students create tasks with a priority, edit them, toggle them complete/incomplete, and delete them. Tasks created by the AI Study Planner and by the chat agent land here too.

### Reminders
Deadline and study reminders that nudge the student about upcoming due dates and scheduled work, complementing the calendar and task list.

## Documents

### Documents Library
Browse the university's document library (handbooks, syllabi, policies, guidelines, reference material). Students can **download** a document, **preview** it inline in the browser, and **bookmark** it for quick access later. Bookmarks are a per-student toggle.

### My Documents
An owner-scoped area where students submit their own documents into the admin approval queue. Students upload a document, edit or delete their own submissions, and download/preview them while they await review. Approved submissions can then flow into the wider system.

## Account

### Profile
Students view and edit their own profile information (such as name and email) from a self-service profile page.

### Settings
A preferences page where students manage their experience: notification and appearance preferences, **email-digest opt-out** (weekly summaries are on by default and toggled off here), **leaderboard opt-in** with an optional display alias, and the preferred language for the AI chat. When the app runs in demo mode, students also experience a fixed cap on AI requests across all AI features (a deployment governance policy — see the admin scope file).

### Email Digests & Deadline Nudges
Unless opted out in Settings, students receive a **weekly email digest** summarising upcoming deadlines, recently posted grades, booked office hours and how many flashcards are due, plus **assignment-due reminder** emails as deadlines approach. Empty weeks send nothing, and reminders are de-duplicated so a student isn't nagged repeatedly for the same assignment.

## Shared systems available to students

Students use several cross-cutting systems documented in other scope files:

- **AI chat, agent mode, agentic tools, streaming, response modes, welcome prompts, voice input, chat session management, archived chats, handwriting OCR, and all AI study generation** → ai-and-automation.md
- **Discussions/forums, direct messaging, study rooms, office-hours booking, leaderboard, anonymous course-feedback submission, notifications center, presence, and course reviews** → communication-community-and-engagement.md
- **Wishlist/cart, payments, wallet, invoices, purchase history, and certificate purchase/download** → commerce-and-monetization.md
- **Course player and content types, certificate/marksheet issuance, and the proctoring engine for proctored exams** → learning-content-and-assessment.md
- **Global ⌘K search and mobile/offline access** → platform-infrastructure-and-integrations.md
