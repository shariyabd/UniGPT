# UniNexus — Faculty Portal Guide

UniNexus is an AI-powered university academic copilot with role-based dashboards for students, faculty, and administrators. This guide documents every feature available to **faculty-role** users: teaching and course management, assessment and grading, exam authoring with camera-based proctoring, AI teaching tools, analytics and early-warning insights, and the shared communication surfaces (messenger, discussions, notifications, global search). Faculty features are grouped in the left sidebar under headings such as Teaching, People, Community, Content, and Insights. Everything below reflects the actual behavior in the application.

---

## Getting Started & Overview

### Faculty Dashboard

The landing page after logging in as faculty. It greets you with your profile summary (name, email, employee ID, department) and a row of headline stat cards: **Active Courses**, **Total Students** (summed across every section you teach), **Pending Grading** (ungraded submissions across your sections), **At-Risk Students** (from the early-warning engine), and **AI Queries** (your assistant session count). Below the stats you see your list of active courses and a feed of your recent activity. It is a read-only snapshot designed to orient you quickly and point you toward whatever needs attention.

### At-Risk Students Indicator

Surfaced both as a dashboard stat and inside Analytics. The early-warning engine flags students who show academic-risk signals — attendance below 75% (after enough sessions), repeatedly missed assignment deadlines, low class-test averages, or poor grades. The dashboard shows the count of unique at-risk students across your sections so you can intervene early. Deeper detail (which students, which signals, and a message deep-link) lives in the Analytics report.

---

## Teaching & Courses

### My Courses

A list of every course you teach. The catalog itself (creating courses, defining sections, and assigning faculty) is admin-owned — as faculty you get a read-focused view of your own teaching load. From here you open any course to manage its detail. Courses you don't teach are never shown.

### Course Detail

The hub for a single course, opened from My Courses. It has tabbed sections for **Overview**, **Students** (the enrolled roster, paginated), **Materials**, and **Assignments**. If you teach multiple sections of the same course, a section switcher at the top scopes the whole page (roster, materials, assignments) to the chosen section. A quick link jumps to attendance for the active section.

### Course Materials

Upload and manage the learning materials students see for a course. You can add a material with a title, type, week number, and an optional file, and mark it published (visible to students) or keep it as a draft. Existing materials can be edited or deleted, and files can be downloaded. Uploaded file-backed materials are also indexed into the AI retrieval corpus so they can inform student and faculty AI answers.

### Attendance

Mark and view attendance per course/section. Opened from the course detail page, it lets you record who was present for a session and review attendance history. Attendance data also feeds the early-warning engine (low attendance is one of the at-risk signals).

### Exams Timetable

A read-only view of the scheduled exams for the courses you teach. Exam scheduling and timetable management are admin-owned; faculty simply consult this page to see upcoming exam dates and details relevant to their courses.

---

## Assessment & Grading

### Grading Queue

The central grading workspace, reachable globally or scoped to a specific course/section (via a `section` filter). It lists student submissions for your assignments, highlighting what still needs grading. Only the course's own faculty (or an admin) can grade its submissions. This is where all the assessment tools below live.

### Grade a Submission

Enter a numeric grade, optional written feedback, and per-criterion rubric scores for a submission, then save. Saving notifies the student that their work was graded (with the score out of the assignment's total points) and surfaces the grade on their transcript. Nothing reaches the student until you save.

### Download Submission File

Download the file a student attached to their submission. Access is strictly limited to the owning faculty (or an admin), and the original filename and content type are preserved.

### AI Feedback Suggestion

A one-click button that drafts written feedback for a submission using AI, based on the assignment title, the current grade, the total points, an excerpt of the student's work, and the rubric criteria. The suggestion is returned to the grading panel for you to review and edit — it is never saved automatically.

### AI Draft Rubric Grade

A more advanced AI assist that reads the actual submission text (typed content plus extracted file text) and grades it against the assignment's rubric. It returns per-criterion scores (each clamped to that criterion's maximum), a suggested overall grade, and feedback with strengths and improvements. The draft prefills the rubric inputs — including italic per-criterion justifications — so you can review, adjust, and save. When no AI provider is configured it falls back to a clearly-labelled heuristic. Human-in-the-loop by design: nothing auto-releases to the student.

### Submission Similarity (Plagiarism Signal)

A plagiarism/originality signal shown to faculty in the grading view. When students submit, their work is chunked and embedded and compared against other submissions for the same section-scoped assignment; pairs whose best matching chunk exceeds the similarity threshold are flagged. Flagged submissions carry a warning badge in the list, and the grading modal shows an excerpt-pair panel so you can see the matching passages. Resubmissions recompute the comparison. This is a signal to prompt human review, not a verdict.

### Peer Review Stats

If anonymous peer review is enabled on an assignment (toggle in the course detail edit modal), students review each other's work anonymously. In the grading overview you see a peer-review chip summarizing the average rating and the number of reviews collected for that assignment. Reviewer and reviewee identities are never revealed to faculty.

### Assignment Management

From the course detail Assignments tab you can edit a published assignment (including toggling its **peer review** option), change its published/unpublished status, or delete it. Deleting an assignment also removes its submissions and is irreversible, so it prompts for confirmation.

---

## Exams, Class Tests & Question Bank

### Class Tests / Quizzes

Author and manage proctored, auto-graded class tests for the sections you teach. The list is searchable, and you can only manage tests for your own sections. Each test carries a status you can toggle between **published** (visible/available to students) and **closed**. Tests can be created, edited, and deleted (deletion is confirmed).

### Create / Edit a Class Test

The authoring form lets you build a test against one of your sections, define questions, set the maximum warnings allowed during proctoring, and choose which exam-security (proctoring) layers apply — limited to the layers the admin has globally enabled. The same form handles both creating a new test and editing an existing one.

### AI-Generate Class Test Questions

Inside the authoring form, an AI action drafts test questions for you based on your inputs. The generated questions populate the form in place; nothing is persisted until you save the test, so you can freely edit or discard the suggestions.

### Class Test Results

An aggregate results view for a class test showing how students performed across attempts, so you can gauge overall outcomes for the section.

### Per-Attempt Proctoring Review

Open a single student's attempt to see the full proctoring dossier: the event timeline, computed risk, device fingerprint, warnings/violations, their submitted answers, and links to captured evidence. This is where you audit what happened during a proctored test for one student.

### Recording & Snapshot Evidence

For attempts that captured evidence, you can stream the webcam **recording** chunks and view the **snapshot** image frames (a photo-strip) taken at flagged moments and periodic samples during the exam. Evidence is served from private storage and strictly scoped to the specific test/attempt — only the owning faculty (or an admin) can view it.

### Proctoring Layers

Class tests support several client-side, camera-AI proctoring layers (all subject to the admin's global on/off gate): **face liveness** (a blink-verified check before questions appear, with warnings escalating to violations), **snapshot evidence** capture, and **phone detection** (flags a phone appearing on camera). Additional layers include tab/focus tracking and optional webcam recording. You choose which enabled layers to apply per test and set the warning tolerance; the review tools above let you inspect the resulting evidence.

### Question Bank

A per-course library of reusable questions you can build up over time (mirroring the class-test question shape, plus topic and difficulty). Your bank scope covers courses you teach a section of. You can add questions manually, **import from an existing class test** (deduplicated by question text, tagged with the test's title as topic), and **create a draft test** from a selection of banked questions — which spins up a draft class test and drops you into its editor. Students can also generate practice quizzes from the bank (a separate, AI-free student feature).

---

## AI Tools

### AI Teaching Assistant

A full ChatGPT-style chat workspace for faculty, opened from the sidebar (and the role's promo CTA). You converse with the AI assistant in a persistent session-based interface, ask teaching questions, and use it to draft materials. It is gated by the AI-chat permission and, in demo mode, by the demo request cap.

### Streaming AI Responses

The assistant streams its replies token-by-token for a live, responsive feel, using server-sent events. A non-streaming request path also exists as a fallback. Both return the same final answer.

### Chat Session Management

The assistant organizes conversations into sessions you can manage: **rename** a session, **pin** it to the top, **archive** it to declutter, **unarchive** it back, and **delete** it permanently. A dedicated **Archived Chats** page lists your archived sessions so you can revisit or restore them. History access and deletion are governed by the relevant chat permissions.

### AI Quiz Generator

Generate a quiz with AI from your prompt/parameters. The generated quiz is returned to the interface for review; it is not saved until you explicitly publish it.

### AI Assignment Generator

Generate an assignment with AI. Like the quiz generator, the result is drafted for your review and is only persisted when you choose to publish.

### Publish Assignment

Turn a generated (or drafted) assignment into a real, published assignment attached to one of your sections. Publishing makes it live for students and logs the action.

### Publish Class Test

Turn a generated quiz into a live, published class test on one of your sections. Publishing sets the test to published status, which notifies the enrolled students that it's available.

---

## Communication

### My Students Directory

A roster view of all the students across the courses and sections you teach, giving you a single directory of the people you're responsible for. It pairs with the Messages view for reaching out directly.

### Messages (Messenger)

A live, real-time direct-messaging workspace for chatting one-on-one with your students (and other permitted users). Access is relationship-based — you can message students in your sections. The messenger loads conversations and messages dynamically for an instant feel, supports read receipts/unread tracking, and runs on real-time channels. Faculty also participate in section-scoped group study rooms through the same messenger plumbing where applicable.

### Discussions

Shared discussion boards where a course section acts as the group. Faculty can view and post to discussions for their sections (subject to the discussions permissions). Because faculty hold the moderation permission, you can **pin** important posts and moderate content within your own sections. A separate campus-wide moderation queue for reported content is admin-only.

### Office Hours

Publish bookable office-hours slots and manage who books them. You create single-capacity slots with a start/end time, an optional location, and an optional note; students who are eligible (enrolled in a section you teach) can then claim an open slot. From this page you can **remove** slots and **cancel a booking** (which reopens the slot and frees it for others). Booked meetings flow into the calendar, and both booking and cancellation trigger notifications.

### Course Feedback

Run anonymous, mid-semester feedback per section. For each section you teach you can **open or close** the feedback window (opening notifies the whole roster). While open, students submit or revise a single 1–5 rating with an optional comment. Results are withheld until a minimum number of responses is reached, after which you see the average, the rating distribution, and the comments — shuffled and stripped of timestamps and identifiers to protect anonymity. Student identity is never exposed.

### AI Feedback Theme Summary

Once a section's feedback has enough responses, a one-click AI action summarizes the qualitative comments into themes for you, drawing on the comments and rating distribution. It's gated behind the AI-chat access controls (and demo limits), with a heuristic fallback when no AI provider is available. It only runs when results have been revealed.

---

## Documents

### My Documents

Faculty-authored documents that flow into the admin approval queue for inclusion in the shared knowledge base. You can upload a document, edit or delete your own submissions, and download or preview them. Submissions are owner-scoped (you only see and manage your own) and require the upload permission. Once submitted they enter the admin review/approval workflow before becoming part of the library.

---

## Analytics & Insights

### Analytics & Academic Reporting

A department/course analytics report for your teaching. It can be viewed across all your courses or scoped to a single course, and presents the performance and engagement picture for your students. It's gated by the department-analytics permission.

### Early-Warning At-Risk Report

Within Analytics, the at-risk section lists the specific students flagged by the early-warning engine, the signals that triggered each flag (low attendance, missed deadlines, low class-test averages, poor grades), and a risk level (high vs. watch). It includes a message deep-link so you can reach out to a flagged student directly. This is the detailed counterpart to the at-risk count shown on the dashboard.

---

## Platform-Wide Features

### Global Search (⌘K)

A command-palette search available on every authenticated page. Pressing ⌘K (or Ctrl+K) opens a search that blends on-page matches with grouped remote results, including a semantic "Knowledge" group (over your accessible materials and documents) plus lexical groups such as courses, assignments, discussions, and your own chat history (deep-linked back to the exact message). It uses a short debounce and unified keyboard navigation.

### Notifications

An in-app notification center available to every role. It lists your notifications (grades, office-hour bookings/cancellations, feedback windows opening, discussion activity, and more), supports live polling for new items, and lets you mark a single notification read, mark all read, or delete notifications.

### Presence / Heartbeat

A background presence heartbeat is pinged from every authenticated page. It keeps your online/active status current across the app (for example, so the messenger and directories can reflect who's around) with no action required on your part.

### Demo AI Request Limits

When the app runs in demo mode, every account — faculty included — is capped at a fixed number of AI requests across all AI surfaces (the teaching assistant chat/stream, the quiz and assignment generators, AI grade/feedback drafts, and the feedback summarizer). The cap is enforced only on request-consuming AI actions (not page loads or history reads); hitting it returns a "demo limit reached" response. Outside demo mode nothing is metered.
