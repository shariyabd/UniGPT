# UniNexus — Student Portal Guide

UniNexus is an AI-powered university academic copilot with role-based dashboards. This document describes every feature available to users signed in with the **Student** role. Students get a personalised academic hub that combines their courses, deadlines, grades and attendance with a suite of AI study tools, community/collaboration spaces, and personal productivity aids. The left sidebar groups these features into sections — **Overview, Academic, AI Copilot, Connect, Community, Planner, and Documents** — and this guide follows a similar grouping. Everything below is scoped to what a student can see and do; access to individual items depends on the permissions granted to the student role.

---

## Overview

### Dashboard
The dashboard is the landing page after login and gives an at-a-glance snapshot of the student's academic life. It greets the student by name (with ID, department and semester) and shows headline stats: attendance rate, current study streak (consecutive active days), number of enrolled courses, cumulative GPA, saved-answer count and total chat sessions. Below that it surfaces recent AI chat conversations (each deep-links back into that chat), a course-materials summary (total / viewed / pending), a learning-path progress bar with current and next topic, and the nearest upcoming assignment deadlines (urgent ones highlighted). Quick-action cards jump straight to AI Chat, Roadmap, Documents and Saved Answers, and a rotating daily study tip plus a motivational quote round out the page.

---

## Academic

### Course Registration
Students self-register for offered sections in their current semester. The page lists available sections per course; the student registers with one click and can drop a section they previously joined. Registration enforces **prerequisites** — a course whose prerequisites are not yet completed shows the unmet requirements as badges and disables the Register button. If a section is full, admin-driven assignment places the student on a FIFO **waitlist**; when a seat opens (because someone drops), the head of the waitlist is automatically promoted and notified with a "seat opened up" message.

### Roadmap
A detailed semester-by-semester learning path. It lays out all enrolled courses with their instructors, assignment deadlines, current grades, per-course progress and completion status, alongside the student's cumulative GPA and overall program progress. It is the long-form companion to the dashboard's learning-path widget.

### Transcript
Displays the student's academic record organised by semester, showing courses taken, grades earned, and GPA per semester. It is a read-only view of official results.

### My Progress (Learning Analytics)
A personal analytics page that visualises study progress with charts (attendance trends, performance over time, activity, etc.). It also includes a **Concept Mastery Map**: a tier-coloured grid of topics derived from the student's practice-quiz results, flashcard review state and class-test scores, sorted weakest-first. Each weak topic offers one-click **"Practice this"** and **"Make flashcards"** buttons that generate targeted review material on the spot (difficulty eases automatically for the weakest topics). This is the adaptive-review loop that turns analytics into action.

### Course Materials
Browse and download the materials (lecture notes, slides, resources) uploaded by faculty for the student's courses. Materials can be filtered by course, category and file type. Each material can be downloaded, and the student can **mark it complete** (a toggle) to track what they have reviewed — the counts feed the dashboard's materials summary.

### Assignments
Lists every assignment across the student's courses with filter tabs (All, To-do, Submitted, Graded) and a pending count. Each row shows the course, title, status (not submitted / submitted / submitted late / graded / overdue), due date, point value and — once graded — the score. Overdue pending items are flagged.

### Assignment Detail & Submission
Opening an assignment shows the full prompt, any rubric (criteria with point values), and a submission form. Students submit a written text response and/or attach a file (up to 10 MB, with an upload progress bar). Submissions can be **resubmitted** any time until the assignment is graded; once graded, the submission locks and the instructor's grade and feedback become visible.

### Peer Review
When an instructor enables peer review on an assignment, the student is lazily assigned up to two anonymous classmate submissions to review. For each, the student gives a 1–5 star rating and constructive comments, and can revise the review afterward. Students also see the anonymous peer feedback they *received* (star ratings and comments only, no identities). Anonymity holds in both directions and peer reviews do not directly affect grades.

### Class Tests / Quizzes (Proctored)
Instructor-authored, timed, auto-graded quizzes with configurable anti-cheat surveillance. The list view shows each test's status (available, in-progress, not-open, disqualified, completed), duration, question count, marks and prior scores. Before starting, an **instructions page** explains the rules and enabled security layers and requires the student to agree. The attempt runs with a sticky countdown timer that auto-submits at zero, sequential or all-at-once navigation, MCQ/true-false questions, and instant results afterward (score, pass/fail status, and a question-by-question review with correct answers).

### Exam Proctoring Layers
During a proctored class test, several optional camera/browser security layers may be active (configured per test by admins/faculty):
- **Begin-gate consent** — a required gesture captures camera/screen consent and requests fullscreen before the timer starts.
- **Face-liveness blink gate** — the student must position their face and blink to prove liveness before questions appear; repeated failure can be bypassed (flagged for faculty).
- **Fullscreen enforcement** — leaving fullscreen blurs and disables the questions until the student returns; silently refused fullscreen is detected.
- **Tab-switch / focus tracking** — leaving the exam tab counts as a violation.
- **Face-loss detection** — a soft banner nudges the student back after a few free warnings, escalating to a blocking overlay and recorded violation if the face stays gone.
- **Phone detection** — the camera watches for a phone in view and photographs flagged moments.
- **Snapshot evidence** — periodic and event-triggered webcam photos are captured for faculty review.
- **Warnings vs. violations** — students get a small number of warnings (shown as "Warning X of N"); exceeding the limit leads to automatic **disqualification** (score 0). Clipboard/right-click restrictions, name/ID watermarking, activity logging and audible alerts may also apply.

### Attendance
A read-only view of the student's attendance per course and their overall attendance percentage across all enrolled courses.

### Exams
Shows the student's exam timetable — scheduled exam dates for their courses — and related exam information.

---

## AI Copilot

### AI Chat (RAG assistant)
A full-screen ChatGPT-style workspace where students ask academic questions and receive answers **grounded in cited sources** drawn from the university's document library, the student's own notes, and their enrolled-section materials. Each answer shows an "Academic Sources" panel (document title, page, section, relevance tier, download link) and a confidence badge, so answers are transparent and traceable. Responses render Markdown, code blocks and LaTeX math, and can be copied or saved.

### Streaming Responses
AI answers stream in token-by-token in real time with a live typing indicator, rather than appearing all at once, so long answers feel responsive.

### Agent vs. Answers-only Mode
A segmented control above the composer switches the chat between two modes. In **Agent mode** the assistant can take real actions on the student's behalf (see Agentic Tools); the input gets a violet accent, the placeholder invites commands, and acted-on replies carry an "⚡ Agent" badge. In **Answers-only mode**, tools are never offered (enforced server-side) and the assistant only explains and answers. Welcome-card prompts, placeholder text and hints all adapt to the active mode.

### Agentic Tools (function calling)
When Agent mode is on, the assistant can perform real tasks through the app's own services (with the same permission and safety rules a manual action would follow): listing upcoming deadlines and courses, listing/booking/cancelling office-hour slots, generating a practice quiz, generating a flashcard deck, and creating a task. Each action appears as a status pill above the answer (running → success/warning) with a summary and, where relevant, a link to view the result (e.g. "View Quiz").

### Response Modes
Independent of Agent/Answers, students can pick a response style: **Simple** (quick concise answers), **Detailed** (in-depth with examples), **Exam Mode** (exam-focused key points), and **Assignment** (guides without doing the work for them). Welcome banners and starter suggestions adjust to the chosen style.

### Welcome-card Starter Prompts
A fresh chat shows a personalised welcome card listing the student's enrolled courses and four suggested starter prompts. The suggestions are sampled/shuffled for variety and swap depending on whether Agent or Answers-only mode is active, and some prompts auto-fill with the student's real course names.

### Voice & Language Input
The composer includes a voice-input button that captures speech via the browser and appends the transcript, plus a language selector so the student can converse in an enabled language (e.g. English or Bengali).

### Chat Session Management
The chat has a searchable history sidebar of all past sessions (pinned ones float to the top). Per session the student can **rename** (inline edit), **pin/unpin**, **archive**, and **delete**. A "New Chat" button starts a clean session. The whole conversation can also be **exported** to a plain-text file, and deep-links can open a specific session and highlight a specific message.

### Archived Chats
A dedicated view listing conversations the student has archived, keeping the main history clean while preserving the ability to unarchive and reopen them.

### Saved Answers
A bookmark button on any AI response saves it to a personal **Saved Answers** collection for later reference. Saved answers can be viewed, edited, and deleted, and the saved-answer count appears on the dashboard.

### Flashcards
Personal study decks reviewed with **SM-2 spaced repetition**. Students create decks manually (title, description, optional course), add front/back cards, or **generate a deck with AI** (topic, card count, difficulty, optional course). Studying presents due cards first; the student flips each card to reveal the answer, then self-rates recall as **Again / Hard / Good / Easy**, which reschedules that card's next review date. Decks show a "N due" badge, and AI-generated decks are marked as such. (Missed practice-quiz questions and weak concept-mastery topics can also spawn decks automatically.)

### Practice Quizzes
Self-paced, unproctored quizzes for self-testing with instant grading. Students **generate a quiz with AI** (topic, optional course, 3–15 questions, difficulty) or build one **from the Question Bank** (real instructor-curated questions for a selected course). Taking a quiz shows all questions on one page with no timer; submitting reveals a colour-coded score, per-question review with correct answers and explanations, and attempt history. Missed questions can be turned into a **flashcard deck** with one click, and quizzes can be retaken or deleted.

---

## Connect

### My Faculty
A directory of the student's own instructors (the faculty teaching their enrolled sections), providing a quick way to see who teaches them and to start a conversation.

### Messages
A dedicated messenger view for real-time direct chat between the student and their faculty. Access is relationship-based (a student can only message faculty who teach them). Messages send instantly and fall back to polling if the realtime connection is unavailable, with the database as the source of truth.

### Study Rooms
Section-scoped **group study chats** where classmates in the same section collaborate. Students can browse rooms, create a room, join or leave, and view members; the chat itself runs on the shared messenger plumbing. Group rooms are kept separate from 1:1 messages and never appear as direct threads.

### Office Hours
Students browse the bookable office-hour slots published by their faculty and **book** an open slot or **cancel** their booking. Booking is relationship-gated (a student can only book faculty who teach their sections) and each slot has a single capacity — booking uses an atomic claim so two students can't grab the same slot (the loser gets a clear conflict message). Booked meetings automatically appear on the student's calendar, and both parties are notified.

### Course Feedback (anonymous)
While a faculty member has opened the feedback window for a section, the student can submit **one anonymous** mid-semester response — a 1–5 rating plus an optional comment — and revise it while the window stays open. The student's identity is never exposed to faculty; responses are only shown to faculty in aggregate (and comments are shuffled with no timestamps).

---

## Community

### Discussions
A shared course/section discussion feed (used by both students and faculty), where the section acts as the group. Students can view discussion posts, create posts and comments, like posts, and report inappropriate posts or comments for moderation. Access is relationship-based (limited to sections the student is enrolled in).

### Leaderboard
An **opt-in**, gamified XP ranking that lets students see how they rank (by department, semester or section). Participation is off by default; a student opts in and can set a display alias so their real name isn't shown. XP is computed from study activity at read time. Opting out removes them from the board.

---

## Planner

### AI Study Planner
Generates a personalised study schedule from the student's upcoming deadlines. The AI proposes study sessions, and the student can save chosen sessions as personal **Tasks**. Generation runs behind the AI-access gate, so it respects any admin AI restrictions.

### Calendar
A unified calendar showing assignment deadlines, exams, class schedules and booked office hours. Students can view everything in one place and manage personal tasks with priorities.

### ICS Calendar Export & Subscribe Feed
From the Calendar, students can **download** a one-time iCalendar (.ics) file of their schedule, or subscribe an external calendar (Google, Outlook, Apple) to a **signed feed URL** minted for them. The feed is a sessionless, signed link that those calendar apps fetch server-to-server and keep in sync, so deadlines and booked meetings show up in the student's everyday calendar app.

### Tasks
A personal to-do list. Students create tasks (with priority), edit them, toggle them complete/incomplete, and delete them. Tasks created by the AI Study Planner and by the chat agent land here too.

### Notes
A personal note-taking area, scoped to the student. Notes can be created, edited and deleted, and are indexed into the student's private RAG corpus so the AI Chat can answer questions grounded in their own notes ("chat with my materials").

### OCR Handwriting Transcription
On the Notes page, students can upload a photo of handwritten notes and have the AI **transcribe** it to text (using vision OCR). This runs behind the AI-access gate and lets handwritten study material become searchable, editable notes.

---

## Documents

### Documents Library
Browse the university's document library (handbooks, syllabi, policies, guidelines, reference material). Students can **download** a document, **preview** it inline in the browser, and **bookmark** it for quick access later. Bookmarks are a per-student toggle.

### My Documents
Students can **submit their own documents** into the admin approval queue. This owner-scoped area lets the student upload a document, edit or delete their own submissions, and download/preview them while they await review. Approved submissions can then flow into the wider system.

---

## Communication & Notifications (all-role, available to students)

### Global ⌘K Search
A command-palette search (triggered with ⌘K) available on every page. It blends on-page matches with grouped remote results after a short debounce: a semantic **"Knowledge"** group (AI-powered hits across the student's accessible RAG corpus — library docs, their notes, section materials) plus lexical groups for courses, assignments, discussions, and the student's own chat history (deep-linked to the exact message). Results are keyboard-navigable and jump straight to the item.

### Notifications
An in-app notification centre covering deadlines, grades, office-hour bookings, waitlist promotions, discussion activity and more. New notifications arrive via background polling; students can mark a single notification read, mark all read, and delete notifications.

### Presence / Heartbeat
Every authenticated page quietly sends a presence "heartbeat," which powers online/last-seen indicators (e.g. in the messenger) so students and faculty can see who is currently active.

---

## Account

### Profile
Students view and edit their own profile information (such as name and email) from a self-service profile page.

### Settings
A preferences page where students manage their experience, including notification and appearance preferences, **email digest opt-out** (weekly email summaries of deadlines, posted grades, booked office hours and due flashcards — on by default, toggle off here), and **leaderboard opt-in** with an optional display alias. Preferred language for the AI chat is also managed here.

### Email Digests & Deadline Nudges
Unless opted out in Settings, students receive a **weekly email digest** summarising upcoming deadlines, recently posted grades, booked office hours and how many flashcards are due, plus **assignment-due reminder** emails as deadlines approach. Empty weeks send nothing, and reminders are de-duplicated so a student isn't nagged repeatedly for the same assignment.

### Demo AI Request Limits
When the app is running in demo mode, every account (including student demo accounts) is capped at a fixed number of AI requests across **all** AI features — chat/agent, practice-quiz generation, flashcard generation, study-planner generation and note OCR. The cap is charged up-front per request; once exhausted, further AI actions return a "demo limit reached" message. Outside demo mode nothing is metered and normal page use is never counted.
