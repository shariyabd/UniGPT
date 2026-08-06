# Instructor / Faculty Features

Teaching, authoring, and grading workflows for instructor/faculty-role users. This catalog covers what an instructor does day to day: orienting on a dashboard, managing the courses and sections they teach, publishing materials, marking attendance, grading submissions and rubrics, authoring proctored class tests, curating a reusable question bank, submitting their own documents for the knowledge base, and consulting the instructor-side view of their earnings. AI teaching tools, the assessment/proctoring engines, community surfaces, analytics, and commerce mechanics live in the shared scope files listed at the end; only the instructor-facing entry points are described here.

## Overview

### Faculty Dashboard
The landing page after signing in as faculty. It greets the instructor with a profile summary (name, email, employee ID, department) and a row of headline stat cards: Active Courses, Total Students (summed across every section taught), Pending Grading (ungraded submissions), At-Risk Students, and AI Queries (assistant session count). Below the stats sit the list of active courses and a feed of recent activity. It is a read-only snapshot meant to orient the instructor and point them toward what needs attention.

### At-Risk Students Indicator
A dashboard stat card showing the count of unique students flagged by the early-warning engine across the instructor's sections (signals such as low attendance, missed deadlines, low class-test averages, and poor grades). It is an at-a-glance prompt to intervene early; the detailed report naming specific students, signals, and risk levels lives in the analytics scope.

## Teaching & Courses

### My Courses
A read-focused list of every course the instructor teaches. Creating courses, defining sections, and assigning faculty are admin-owned; here the instructor sees only their own teaching load and opens any course to manage its detail. Courses they don't teach never appear.

### Course Detail Hub
The hub for a single course, with tabbed sections for Overview, Students (the enrolled roster, paginated), Materials, and Assignments. When the instructor teaches multiple sections of the same course, a section switcher at the top scopes the whole page — roster, materials, and assignments — to the chosen section, with a quick link to attendance for the active section.

### Course Materials Management
Upload and manage the learning materials students see for a course. The instructor can add a material with a title, type, week number, and optional file, and mark it published (visible to students) or keep it a draft. Existing materials can be edited, deleted, and downloaded. File-backed materials also feed the AI retrieval corpus (see the AI scope).

### Course Preview / Free-Preview Lessons
Preview the student experience before publishing — the instructor can test how a course and its lessons appear to students and mark selected lessons as free previews for prospective learners, verifying the experience before it goes live.

### Attendance Marking
Record and review attendance per session for a course/section, opened from the course detail page. The instructor marks who was present and browses attendance history; this data also feeds the early-warning engine (low attendance is an at-risk signal).

### Exams Timetable (read-only)
A read-only view of scheduled exams for the courses the instructor teaches. Scheduling and timetable management are admin-owned (mirrors admin exam scheduling); faculty simply consult this page for upcoming exam dates relevant to their courses.

## Assessment & Grading

### Grading Queue
The central grading workspace, reachable globally or scoped to a specific course/section via a section filter. It lists student submissions for the instructor's assignments and highlights what still needs grading. Only the course's own faculty (or an admin) can grade its submissions.

### Grade a Submission
Enter a numeric grade, optional written feedback, and per-criterion rubric scores for a submission, then save. Saving notifies the student that their work was graded (with the score out of the assignment's total points) and surfaces the grade on their transcript. Nothing reaches the student until the instructor saves.

### Download Submission File
Download the file a student attached to a submission. Access is strictly limited to the owning faculty (or an admin), and the original filename and content type are preserved.

### Assignment Management
From the course detail Assignments tab, edit a published assignment — including toggling its anonymous peer-review option — change its published/unpublished status, or delete it. Deleting an assignment also removes its submissions and is irreversible, so it prompts for confirmation.

### Publish Assignment
Turn a drafted (or AI-generated) assignment into a real, published assignment attached to one of the instructor's sections. Publishing makes it live for students and logs the action.

## Class Tests & Question Bank

### Create / Edit a Class Test
The authoring form builds a proctored, auto-graded class test against one of the instructor's sections: define questions, set the maximum warnings allowed during proctoring, and choose which exam-security (proctoring) layers apply — limited to the layers the admin has globally enabled. The same form handles both creating a new test and editing an existing one.

### Class Test Status & Deletion
Manage the lifecycle of authored class tests. The searchable list is scoped to the instructor's own sections; each test's status can be toggled between published (visible/available to students) and closed, and tests can be deleted with confirmation.

### Publish Class Test
Turn a drafted or generated quiz into a live, published class test on one of the instructor's sections. Publishing sets the test to published status, which notifies the enrolled students that it is available.

### Class Test Results
An aggregate results view for a class test showing how students performed across attempts, so the instructor can gauge overall outcomes for the section.

### Per-Attempt Proctoring Review
Open a single student's attempt to see the full proctoring dossier: the event timeline, computed risk, device fingerprint, warnings/violations, submitted answers, and links to captured evidence. This is where the instructor audits what happened during a proctored test for one student. (The mechanics of each proctoring layer live in the learning/assessment scope.)

### Recording & Snapshot Evidence Review
For attempts that captured evidence, stream the webcam recording chunks and view the snapshot photo-strip taken at flagged moments and periodic samples. Evidence is served from private storage and strictly scoped to the specific test/attempt — only the owning faculty (or an admin) can view it.

### Question Bank Management
A per-course library of reusable questions the instructor builds up over time (mirroring the class-test question shape, plus topic and difficulty), scoped to courses they teach a section of. The instructor can add questions manually, import from an existing class test (deduplicated by question text, tagged with the test's title as topic), and create a draft test from a selection of banked questions — which spins up a draft class test and drops them into its editor.

## Documents

### My Documents
Faculty-authored documents that flow into the admin approval queue for inclusion in the shared knowledge base. The instructor can upload a document, edit or delete their own submissions, and download or preview them. Submissions are owner-scoped (they see and manage only their own) and require the upload permission; once submitted they enter the admin review/approval workflow before becoming part of the library.

## Earnings

### Payout Requests & Revenue View
The instructor-side view of earnings: monitor course sales, earnings, and commission breakdowns, view current account balance and payout status, and request a financial disbursement/withdrawal once earnings meet the minimum requirement. This is only the instructor dashboard surface — the commission engine, payout processing, and payment gateways live in the commerce scope.

## Shared systems available to instructors

- AI teaching tools — AI Teaching Assistant (streaming, chat-session management), AI Quiz/Assignment/Class-test-question generators, AI feedback suggestion, AI draft rubric grade, AI feedback theme summary, demo AI request limits → ai-and-automation.md
- Assessment & content engines — submission similarity/plagiarism screening, peer-review system, proctoring-layer mechanics, certificate/marksheet engine, content types and live classes → learning-content-and-assessment.md
- Community & communication — discussions (post/pin/moderate own sections), messages/messenger, group study rooms, office-hours publishing/cancellation and online meeting booking, course-feedback window management, notifications, presence/heartbeat, support tickets → communication-community-and-engagement.md
- Analytics — department/course analytics reporting and the detailed early-warning at-risk report → analytics-and-reporting.md
- Commerce — commission rates, payout processing, payment gateways, invoices → commerce-and-monetization.md
- Platform — global search (⌘K), authentication, multi-language, storage, mobile → platform-infrastructure-and-integrations.md
