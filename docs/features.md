# UniNexus — Feature Documentation

> Comprehensive, code-verified documentation of **every** feature, grouped by user role.
> For a bare index of feature names, see [feature-list.md](feature-list.md).
>
> **Source of truth = the code** (verified against the codebase on 2026-07-17). Where behaviour
> differs from older marketing docs, this file follows the code and flags the difference.
>
> **Reading the "Working Criteria":** these are the exact rules, thresholds, guards, and edge
> cases the code enforces — the conditions under which a feature does and does not operate.
> Numbers, enum values, permission slugs, and route names are taken verbatim from source.

**Conventions used throughout**

- **Permission slugs** (e.g. `use_ai_chat`) come from `app/Enums/Permission.php` (46 total). A
  route gated by `permission:x` requires that slug; role groups (`role:student|faculty|admin`)
  gate the whole section.
- **`ai.chat.access`** is a *middleware alias* (`EnsureAiChatAccess`), not a permission — it
  returns HTTP 403 JSON if an admin has blocked the user's AI access.
- **`demo.ai.limit`** is the demo-mode AI cap middleware (see [Engine / System](#demo-mode-ai-usage-cap)).
- **Enrollment access key:** `User::enrolledSectionIds()` returns sections whose pivot status is
  **not** `dropped` or `pending` — so `enrolled` **and** `completed` grant read access; `pending`
  placements grant **zero** access until confirmed.

---

## Table of Contents

- [Student Features](#student-features)
- [Faculty Features](#faculty-features)
- [Admin Features](#admin-features)
- [Shared / Cross-Role Features](#shared--cross-role-features)
- [Engine / System Features](#engine--system-features)
- [Appendix: Known Code/Doc Discrepancies](#appendix-known-codedoc-discrepancies)

---

# Student Features

> Students see only their own enrolled classes and their own data. Everything is scoped to the
> section a student is actually confirmed into.

## Student Dashboard

**Short description.** The student's study hub — current courses, key numbers, deadlines, and study streak.

**How it works.** Loads current-term courses and computes stat tiles, an attendance rate, a study streak, a materials-progress summary, recent chats, and upcoming deadlines.

**Working criteria.**
- Courses shown are **current-term only** (`isCurrent === true`); past terms live in Transcript/Roadmap.
- Four stat tiles: Courses (current count), **CGPA** (unweighted mean of grade points — see the discrepancy note), Saved Answers count, Chat Sessions count.
- **Study streak:** consecutive days with ≥1 activity-log entry, window capped at 366 days, with a one-day grace (if today has no activity but yesterday does, the streak survives).
- **Upcoming deadlines:** assignments in enrolled sections with a non-null future `due_at`, limit 5, ordered by `due_at`; priority = `high` (≤3 days), `medium` (≤7), else `low`.
- Route `dashboard`, `role:student`, no permission gate.
- ⚠️ Dashboard deadlines and material stats use queries that don't filter `status='published'` and exclude `completed` sections respectively — see the [appendix](#appendix-known-codedoc-discrepancies).

---

## AI Tutor Chat (RAG, streaming, cited)

**Short description.** The flagship feature: ask questions in natural language, get answers grounded in the university's approved documents **plus the student's own notes and their sections' materials**, with inline citations and a confidence score, streamed live.

**How it works.**
1. The message is classified — greetings/small-talk short-circuit to an instant rule-based reply with no retrieval and no LLM cost.
2. For real questions, the query is embedded and the most similar chunks are retrieved (cosine similarity over MySQL vectors), scoped to what the student may see.
3. Retrieved chunks become numbered citations and a grounded context block.
4. The AI answers using that context, in the chosen language and chat mode, streaming token-by-token over SSE.
5. The session, messages, citations, confidence, and follow-ups are persisted.

**Working criteria.**
- **Chat modes** (6, `ChatMode` enum): `general`, `academic` (default), `research`, `exam_prep`, `assignment_help` (prompt forbids writing the final submission), `career_guidance`. Mode is per-message; an invalid value silently falls back to `academic`.
- **Languages:** admin-configured via AI Settings; **default is English (`en`) + Bangla (`bn`)**. A request language not in the supported set falls back to the admin default. (The `Language` enum's 7 codes are dead code — not the live set.)
- **Small-talk short-circuit:** classified as `smalltalk` (≤5 words matching greeting/thanks/farewell/ack lists) or `meta` (≤8 words matching "what can you do" style phrases) → canned reply with `confidence=null`, `sources=[]`, `model='rule-based'`, `tokens=0`.
- **Confidence** = best retrieval similarity × 100, banded: `≥0.9` Very High, `≥0.7` High, `≥0.5` Medium, `≥0.3` Low, else Very Low. (With the default 0.35 threshold and the shipped embedding model, most real citations land in Low/Medium.)
- **Follow-ups:** exactly 3 per mode, hardcoded.
- **Message length:** max 4000 chars.
- **Gates:** page needs `use_ai_chat`; sending needs `use_ai_chat` + `ai.chat.access` + `demo.ai.limit`. History reads need `view_chat_history`; delete needs `delete_chat`.
- If an admin has blocked the student's AI access, the chat box is replaced with a message stating the reason and (if set) the until-date.
- Sources are always **approved, visible** library documents ∪ the student's own notes ∪ their sections' materials.

**Capabilities — session management.** Sessions auto-create on first message; title comes from the first user message (≤50 chars); history feeds the last 6 messages to the model. Sessions can be pinned, renamed (≤120 chars), archived (also clears pin), unarchived, or hard-deleted. Listing orders pinned first, then most-recent.

---

## Chat — Agent Mode (8 in-chat tools)

**Short description.** In Agent mode (the default), the tutor doesn't just answer — it takes real actions on the student's behalf via LLM function calling, showing a live tool-activity trail.

**How it works.** When tools are enabled, the provider may return tool calls instead of (or alongside) an answer. `RagChatService` runs an agent loop: execute every requested tool, feed results back to the model, repeat. Each tool delegates to the same domain service as the equivalent UI action, so RBAC and business rules bind AI actions identically. The trail is persisted and rendered above the answer; over SSE it emits `tool_start` / `tool_result` events.

**Working criteria.**
- **Student-only, enforced twice:** tool definitions are empty for non-students, and execution refuses non-students even if a name resolves. The faculty assistant has no tools.
- **`agent=false` is server-enforced:** when the request's `agent` flag is false, tool definitions are never attached to the provider call and the tool-instruction prompt block is omitted. Default is agent **on**.
- **Agent loop: max 3 rounds.** After the 3rd round, tools are withdrawn before the final completion so the model must produce text. Worst case = 4 provider calls.
- **Failure isolation:** an aborted action (permission/validation) becomes an error tool-result with the guard's message; any other exception becomes a generic "tool failed unexpectedly" result. Nothing bubbles out of the loop as an exception.
- Tokens accumulate across every round; the tool trail is persisted in `chat_messages.tool_activity`.
- ⚠️ Only the final answer text is persisted; prose emitted live alongside a round-1 tool call is shown but not saved.

**Capabilities — the 8 tools (total: 8).**

| # | Tool | What it does | Key parameters | Guards / failure modes |
|---|------|--------------|----------------|------------------------|
| 1 | `get_upcoming_deadlines` | Lists upcoming assignments, exams, class tests across enrolled sections | `limit` (default 15, clamped 1–30) | Read-only; scoped to the student's sections |
| 2 | `list_my_courses` | Lists the student's enrolled courses | none | Excludes `pending` enrollments |
| 3 | `list_office_hour_slots` | Lists bookable office-hour slots of the student's faculty | none | Only the student's faculty's open slots + their own bookings |
| 4 | `book_office_hour_slot` | Books an office-hour slot | `slot_id` (required) | 404 if missing; 403 if faculty doesn't teach the student; 422 if past; 409 if just claimed (atomic) |
| 5 | `cancel_office_hour_booking` | Cancels an office-hour booking | `slot_id` (required) | 404 if slot missing/not booked; 403 unless holder or the slot's faculty |
| 6 | `generate_practice_quiz` | Generates an AI practice quiz | `topic` (required), `question_count` (1–15, default 5), `difficulty` (easy/medium/hard), `course_id` | 422 on empty topic; `course_id` verified against active enrollment (bogus → null) |
| 7 | `generate_flashcard_deck` | Generates an AI flashcard deck | `topic` (required), `count` (1–30, default 10), `difficulty`, `course_id` | 422 on empty topic; enrollment-checked `course_id` |
| 8 | `create_study_task` | Creates a personal task | `title` (required), `due_date` (required, `YYYY-MM-DD`), `description`, `priority` (low/medium/high), `course_id` | 422 on empty title or bad date format; enrollment-checked `course_id`; no past-date guard |

Every acting reply carries an "⚡ Agent" badge; mode-aware hints, placeholder, and example prompts switch between Agent and Answers-only.

---

## Chat — Answers-Only Mode

**Short description.** A hard, server-side guarantee that the tutor only explains and never acts.

**How it works.** The segmented switch above the composer sets `agent=false`; the server never attaches tool definitions to the model call, so tools cannot be invoked even by a crafted request.

**Working criteria.**
- Enforced server-side, not merely hidden in the UI.
- Welcome example prompts, composer placeholder, and hint line switch to explanatory phrasing; the violet Agent-mode ring is removed.

---

## Saved Answers

**Short description.** Save a useful AI answer into folders with personal notes, star it, and jump back to the original conversation.

**How it works.** Saving a chat message derives its question from the nearest preceding user message and copies the answer's sources/confidence.

**Working criteria.**
- Idempotent per message (re-saving overwrites, never duplicates).
- Ownership checked (403 if the message isn't in the student's own session).
- Fields: `title` (≤60 chars, derived), `notes` (≤2000), `folder` (default "General"), `starred` (via update), `category` (the session's mode label).
- **Folders are derived at read time** by grouping on the `folder` string — not a table.
- **View tracking:** opening a saved answer increments `view_count` and stamps `last_viewed_at` (no dedupe/throttle).
- Gates: view needs `view_chat_history`; store/update need `use_ai_chat`; delete needs `delete_chat`.
- ⚠️ `tags` is displayed but has no write path — always empty.

---

## Course Registration

**Short description.** Students confirm or drop the sections an admin has placed them into, while the term's registration window is open.

**How it works.** Admin assigns a student to a section → the pivot is `pending` (seat reserved, no access). The student's Registration page shows only assigned sections; clicking Register confirms (`pending → enrolled`). Dropping sets `dropped` and auto-promotes any waitlist head.

**Working criteria.**
- **Enrollment statuses:** `pending` (reserved, no access) · `enrolled` (active) · `completed` (past term, read access retained) · `dropped` (history kept). One enrollment per (course, student).
- **Eligibility (checked in order, first failure wins):** (1) registration must be open (a current term with `is_registration_open`); (2) the section must belong to the current term and be active; (3) the student must have a `pending` placement in it ("not assigned… contact the registrar"); (4) all prerequisites must be **completed**.
- **No capacity, credit-limit, schedule-conflict, or semester-match check at confirm** — the seat was already reserved at assign time.
- **Capacity** = `enrolled + pending` (a reserved seat counts); checked at *assign* time, not at student confirm.
- **Drop:** blocked if registration is closed, or if the row isn't `enrolled` (a `pending` placement cannot be dropped). Drop triggers waitlist promotion.
- **Only notification in this flow** is waitlist promotion ("A seat opened up"); assign/enroll/drop themselves don't notify the student here (admin actions do).
- Routes `register`, `register.store`, `register.drop` — all `enroll_course`.

---

## Prerequisites Enforcement

**Short description.** A course can require other courses first; the student can't confirm a section until they've completed every prerequisite.

**Working criteria.**
- **Only a `completed` enrollment satisfies a prerequisite** — being `enrolled`/in-progress does not.
- The Registration page shows each prerequisite as a met/unmet badge; the Register button is disabled and the confirm is blocked server-side until all are met, with the missing codes named.
- Admins define prerequisites via a multi-select on the course form (direct self-reference is filtered; A→B→A cycles are not detected).

---

## Section Waitlists

**Short description.** When a full section is assigned, the student joins a FIFO waitlist and is auto-promoted when a seat frees up.

**Working criteria.**
- Assigning to a full section queues the student in `section_waitlists` (FIFO by row id) and notifies them, instead of failing.
- **Position** = count of queue entries with id ≤ the student's own (1-indexed); the Registration page shows a waitlist card with the position.
- **Promotion** (on any drop): while the section has capacity, the lowest-id entry is taken, deleted, skipped if the student already holds a seat/pending placement in that course, else placed as `pending` with a "seat opened up" notification.
- ⚠️ There's no student-facing self-waitlist route; entries are created by admin assignment. Also, a skipped student's entry is deleted before the skip check, so they lose their spot — see the [appendix](#appendix-known-codedoc-discrepancies).

---

## Course Materials

**Short description.** The published learning resources for the student's own classes, grouped by course, each downloadable and markable complete.

**Working criteria.**
- Visibility: materials in sections the student is enrolled/completed in (excludes `dropped`/`pending`), filtered to `is_published = true`, ordered by week.
- **Download:** 404 unless the material has a file and the student is in its section; increments the download counter, then streams from the private disk.
- **Completion:** toggling stores/removes a `material_completions` pivot row with a timestamp; 403 if not in the section.
- Gates: `view_courses`.

---

## Document Library

**Short description.** Browse the approved documents the student is allowed to see, with search, filters, preview, download, and bookmark.

**Working criteria.**
- Scope: `approved` documents whose `visibility` JSON contains `students`.
- Filters (server-side): category, search, department, file type. Paginated 25.
- **Download:** authorized via policy (approved + visible), 404 if the file is missing, increments `downloads`. Preview increments `views`, served inline.
- Bookmark toggles a pivot; the target must be an approved, visible document (404 otherwise).
- Gates: view `view_documents`; download `download_document`.

---

## My Documents (submissions to approval queue)

**Short description.** Students submit their own documents into the same admin approval queue; once approved they become AI-tutor knowledge.

**Working criteria.**
- All routes gated `upload_document`; every action checks ownership (403 otherwise).
- **Upload:** `title` (≤255), `category` (≤100, required), optional departments/tags, **file required ≤50 MB, mimes pdf/doc/docx/txt/md/ppt/pptx**. Byte-identical duplicates (SHA-256) are rejected.
- Visibility is auto-set (students → `['students']`), not user-chosen. New submissions start `PENDING`.
- **Edit → re-review:** any edit forces status back to `PENDING`, clears the rejection reason/approver, and **deletes all indexed chunks** (dropping it from RAG until re-approved).
- Reviewer comments (commented / changes-requested / rejected) are shown to the uploader.

---

## Assignments & Submission

**Short description.** Submit text and/or a file to a published assignment before the deadline; graded submissions lock.

**Working criteria.**
- Access: 404 unless the assignment is `published` and the student is in its section.
- **Late marking:** `late` if `due_at` is non-null and past at submit time; re-evaluated on every resubmission; `due_at = null` is never late (no grace period).
- **Lock on grade:** a `graded` submission returns 403 on further submits.
- **Resubmission:** unlimited while not graded — one row per (assignment, student), overwritten in place, `submitted_at` reset each time.
- **File rules:** `content` ≤20000 chars; `file` ≤10 MB, mimes pdf/doc/docx/txt/zip/png/jpg/jpeg/ppt/pptx/xls/xlsx. At least one of content/file is required.
- Every save queues a similarity-screening job and notifies the assignment's faculty (`SUBMISSION`).
- Gates: view `view_assignments`; submit `submit_assignment`.

---

## Anonymous Peer Review

**Short description.** When a teacher enables peer review, students who have submitted are given up to two classmates' submissions to review anonymously.

**Working criteria.**
- **Up to 2 review tasks per student**, assigned lazily on the assignment page (only if peer review is enabled and the student has their own submission).
- **Assignment order:** least-reviewed submissions first, never the student's own; no reciprocal-pair exclusion.
- **Anonymity both ways:** the task exposes only submission content (no author); received feedback returns only rating + comment, shuffled.
- **Rating** 1–5 (validated and clamped); comment ≤2000 chars. A completed review can be edited; the reviewee is notified only on first completion (anonymous, typed `ASSIGNMENT`).
- Ratings never affect the grade — they surface to faculty only as an average.

---

## Class Tests (take, proctored, auto-graded)

**Short description.** Students take their teachers' timed, proctored quizzes and get an instant auto-graded result.

**How it works.** Start → one timed attempt → questions render (answers stripped, optionally shuffled) under the selected proctoring layers → violation/event/media pings → submit (or timeout) → server-authoritative grading → result.

**Working criteria.**
- **Availability:** starting requires the test to be `published` and within its optional `available_from`/`available_until` window.
- **One attempt per student per test, ever — no retakes.** Created with a UUID session id and `total_marks` = sum of question marks.
- **Timer anchored to the student's `started_at`** (not publish time), enforced server-side. A 10-second submit grace exists server-side; the client clock hits zero 10s earlier.
- **Answer stripping:** questions render without `correct_answer`. `shuffle_questions` shuffles order; `shuffle_options` shuffles MCQ options only (True/False keeps order, keys travel with text).
- **Violation → disqualify:** the `max_warnings` threshold (default 3) tolerates that many; the **4th** violation (strictly greater) triggers disqualification. Disqualification is client-driven off the returned flag; a disqualified attempt scores 0.
- **Grading (server-side, idempotent):** exact string match of selected vs correct answer; unanswered = null (not wrong); late (past deadline+grace) → status `expired` but still graded on answers; disqualified → score 0. Risk score computed on submit if that layer is on.
- **Result:** visible only once the attempt is finalised; reveals every correct answer, the student's selections, correctness, and marks. `passed` = score ≥ `pass_marks` (or null if no pass mark).
- ⚠️ An in-progress attempt whose deadline already passed at re-start is finalised `expired` with score 0 and **no answers graded** — contrast the normal late path.
- Gate: `take_class_test`.

---

## Practice Quizzes (AI-generated)

**Short description.** Students quiz themselves without a teacher: pick a topic, count, and difficulty; the AI generates an MCQ/true-false quiz, graded instantly on the server.

**Working criteria.**
- **Generation:** `topic` (≤120, required), optional `course_id`, `question_count` (3–15, default 5), `difficulty` (easy/medium/hard, default medium). Only `multiple-choice` and `true-false` types.
- **Answer stripping:** questions render without answer/explanation.
- **Server-side grading:** correctness is case-insensitive, whitespace-trimmed, matched on option **text**; 1 point per question. Reveals correct answers + explanations after submit.
- **Retakes unlimited** — each submit creates a new attempt; list shows best/last score.
- **Missed → flashcards:** converts missed questions into a "Review: {topic}" deck (returns null if nothing missed).
- Ownership enforced on every action. Generation gated `use_ai_chat` + `ai.chat.access` + `demo.ai.limit`.

---

## Practice Quizzes from Question Bank

**Short description.** Build a practice quiz from the course's faculty-curated question bank — no AI, so it works even if AI access is blocked.

**Working criteria.**
- **No AI gate, no extra permission.**
- Guards: the student must be enrolled in the course (`pending` excluded; `dropped` still passes); 422 if the bank is empty.
- Question count clamped 1–20 (default 10), random order, difficulty fixed `medium`.
- Deterministic shape translation from the bank's class-test format to the practice grader (grades on option text); no explanations.

---

## Flashcards (SM-2 spaced repetition)

**Short description.** Personal study decks reviewed on the SM-2 spaced-repetition schedule.

**Working criteria.**
- **SM-2 review:** grade clamped 0–5. Fail (`<3`) → repetitions reset to 0, interval 1. Pass (`≥3`) → repetitions++, interval = 1 (rep 1) / 6 (rep 2) / `round(interval × ease)` after. Ease updated every review by the standard SM-2 formula, floored at **1.3**. `due_at = now + max(1, interval)` days.
- **Due selection:** never-reviewed cards (null `due_at`) are always due.
- Decks are owner-scoped; `source` defaults to `manual`.

---

## AI Flashcard Generation

**Short description.** Generate a deck from a topic instead of writing cards by hand.

**Working criteria.**
- Generation happens first, then the deck is created (`source: 'ai'`), then cards replace any existing ones.
- `topic` (≤200, required), optional `title`, `count` (1–30, required), `difficulty` (required). ⚠️ A successful generation returning zero cards yields an empty deck.
- Only `flashcards.generate` is AI-gated (`use_ai_chat` + `ai.chat.access` + `demo.ai.limit`); manual deck/card CRUD is not.

---

## Notes

**Short description.** Personal, owner-scoped notes, optionally tagged to a course and pinnable; indexed into the student's RAG corpus.

**Working criteria.**
- `title` (≤150, required), `content` (≤10000), optional `course_id`, `is_pinned`.
- Listing orders pinned first, then most-recently-updated; paginated 25.
- Every create/update/delete dispatches a RAG-sync job (delete tombstones the shadow document).
- ⚠️ Notes have **no tagging** field despite some doc phrasing; `course_id` is not scoped to enrolled courses.

---

## OCR Handwritten Notes

**Short description.** Snap a photo of a handwritten page and the AI (gpt-4o vision) transcribes it into editable text you then save as a note.

**Working criteria.**
- Endpoint `notes.ocr`, gated `use_ai_chat` + `ai.chat.access` + `demo.ai.limit`.
- Image required, mimes jpg/jpeg/png/webp, **max 8 MB**.
- The image is never persisted — read from the temp path, only text returned. The student reviews/edits before saving; OCR does not create the note itself.
- ⚠️ No provider fallback — if the provider throws, the request errors (unlike the Study Planner).

---

## Tasks

**Short description.** Personal to-dos with due date, priority, and optional course; appear on the calendar.

**Working criteria.**
- Priorities: `low`, `medium`, `high`. Ordering: incomplete first, then by due date (nulls last). Paginated 25.
- `toggle` sets `completed_at = now()` / null; `update` preserves the original completion timestamp.
- Owner-scoped (403 otherwise). No permission middleware beyond the student role.

---

## AI Study Planner

**Short description.** Turns the student's upcoming deadlines into a suggested study schedule, saved as tasks.

**Working criteria.**
- **Deadlines source:** published assignments (future `due_at`), exams (future `exam_date`), published class tests (future `available_until`), scoped to enrolled sections.
- **Generation:** `hours_per_day` clamped 1–12 (default 3). AI first, deterministic fallback if the provider is unavailable (works with no API key).
- **Deterministic fallback:** with deadlines → one session per deadline, targeted 2 days before, duration `min(180, hours×60)` min; no deadlines → one session per day, `min(120, …)`.
- The plan is ephemeral until the student saves chosen sessions as Tasks.
- Generate gated `use_ai_chat` + `ai.chat.access` + `demo.ai.limit`; saving tasks is ungated.

---

## Transcript (GPA / CGPA)

**Short description.** All courses grouped by semester with grades and credits, per-semester GPA, and an overall credit-weighted CGPA.

**Working criteria.**
- **4.0 scale:** A 4.0 / A- 3.7 / B+ 3.3 / B 3.0 / B- 2.7 / C+ 2.3 / C 2.0 / C- 1.7 / D+ 1.3 / D 1.0 / F 0.0. **No A+, no D-** — unmapped letters are excluded from GPA.
- **CGPA** = credit-weighted mean over graded courses; `null` when nothing is graded.
- Any row with a mappable grade counts toward GPA regardless of status; `completedCredits` counts only `completed` rows.
- Gate `view_courses`.
- ⚠️ The Dashboard/Roadmap CGPA is an **unweighted** mean and disagrees with this credit-weighted one — see the [appendix](#appendix-known-codedoc-discrepancies).

---

## Roadmap (degree progress)

**Short description.** Semester-by-semester degree progress: each course's instructor, status, grade, assignment completion, and per-semester GPA.

**Working criteria.**
- Source: enrolled courses (excluding `pending`), grouped by semester.
- Module status: `completed` if the pivot is completed, else `in-progress` (so `dropped` renders as in-progress).
- Assignment status: `completed` if any submission row exists, else `pending`.
- Per-semester GPA is credit-weighted; overall progress = mean of pivot progress.
- Gate `view_courses`.

---

## Attendance View

**Short description.** A read-only per-course attendance summary (data entered by faculty).

**Working criteria.**
- **Counts as present:** everything except `absent` — `late` and `excused` both count.
- Rate = `round(attended / total × 100)`, null when there are no records; `total` counts records, not scheduled sessions.
- Scope excludes `pending` (includes `dropped`/`completed`). Recent = last 5 records per course.
- Gate `view_attendance`.

---

## Exams View

**Short description.** The student's upcoming and past exams, with countdown labels.

**Working criteria.**
- Scope: exams in enrolled sections. Split into upcoming (`date ≥ today`) and past (desc).
- Countdown: `Today` / `Tomorrow` / `In N days` (<7) / `In 1 week` (<14) / `In N weeks`.
- Gate `view_exams`.

---

## Calendar

**Short description.** One combined calendar of assignment deadlines, exam dates, personal tasks, and booked office hours.

**Working criteria.**
- Four merged sources: assignments (enrolled sections, non-null `due_at`), exams (by section), own tasks (non-null due date, all-day), booked office-hour slots.
- Tasks can be created from the calendar. No permission gate beyond `role:student`.
- ⚠️ The assignment source doesn't filter `status='published'` — draft assignments can appear (see [appendix](#appendix-known-codedoc-discrepancies)).

---

## Calendar .ics Export & Subscribe Feed

**Short description.** Download the calendar as `.ics` or subscribe by URL so it stays in sync in Google/Outlook/Apple Calendar.

**Working criteria.**
- **Export** (`calendar.export`): authenticated download, `attachment`.
- **Feed** (`calendar.feed`): a **signed, sessionless** URL (`signed` + `throttle:30,1`) served `inline` — the signature is the only credential.
- RFC 5545 compliant: proper escaping, 75-octet line folding, all-day (`VALUE=DATE`) vs timed events (fixed 1-hour duration; exam duration is ignored). Summary prefixes: "Due:" / "Exam:" / "Task:".
- ⚠️ The feed URL is **non-expiring** — anyone holding it can read that student's calendar until `APP_KEY` rotates.

---

## Learning Analytics ("My Progress")

**Short description.** A private analytics dashboard charting the student's own trends.

**Working criteria.**
- Reuses Transcript + Attendance services so GPA/attendance stay canonical.
- Six chart series: GPA trend (skips null semesters), attendance by course, test-score trend (submitted attempts with marks, mean of per-attempt %), assignment-score trend, weekly activity (last 8 weeks), plus a summary (CGPA, credits, attendance, avg test/assignment score, streak, AI sessions).
- Private — built only from the student's own data.
- Gate `view_own_analytics`.

---

## Concept Mastery Map & Adaptive Review

**Short description.** A tile per studied topic, colored by mastery, with one-click adaptive review — deterministic, no AI.

**Working criteria.**
- **Blend weights:** class tests 0.5 / practice 0.35 / flashcards 0.15, renormalized over whichever sources are present.
- Accuracies: class tests `score/total`, practice `score/total`, flashcards `learned/total` where **"learned" = repetitions ≥2 AND interval ≥6 days**.
- **Weak = mastery <60**; tiles sorted weakest-first.
- Topic names merge case-insensitively; "Review: X" flashcard decks fold into topic X; never-studied decks carry no signal.
- Weak tiles offer one-click "Practice this" / "Make flashcards" posting the topic to the AI generators (difficulty eases below 40%).

---

## Leaderboard (opt-in XP)

**Short description.** An opt-in, gamified XP ranking deliberately decoupled from official GPA.

**Working criteria.**
- **XP** = `submitted-test marks + graded-assignment marks + (present+late attendance count × 5)`, rounded. (Raw marks are summed, so higher-scale courses contribute more.)
- **Opt-in required** (`leaderboard_opt_in`); optional `leaderboard_alias` (≤30 chars) replaces the real name.
- Scopes: `department` (default), `semester`, `section`; invalid scope falls back to department.
- Rankings truncated to top 100, but the viewer's own rank is resolved from the full list; XP is computed at read time, never stored.
- Routes `leaderboard`, `leaderboard.settings` — no permission gate.

---

## Discussion Feed

**Short description.** Each enrolled section has its own discussion group; students post, comment, like, and report.

**Working criteria.**
- Membership derived from enrollment (excludes `dropped`/`pending`, retains `completed`). Access is 403 outside accessible sections.
- Post ≤5000 chars; comment ≤2000; report reason ≤300. Feed orders pinned first, 10 per page.
- A student can delete their **own** posts/comments; likes toggle idempotently; reports feed the admin moderation queue. Posts/comments are soft-deleted.
- Gates: view `view_discussions`; post/comment `post_discussion`.

---

## Study Rooms (group chat)

**Short description.** Section-scoped group chats between classmates, built on the messenger plumbing.

**Working criteria.**
- A room is a `group` conversation scoped to a section; membership = participants pivot.
- **Create/join** require enrollment in the room's section (403 otherwise); join is idempotent.
- **Leave** detaches; **the last member out deletes the room** (messages cascade).
- Strictly separate from the 1:1 messenger — group rooms never appear as direct threads.
- Routes `study-rooms.*` — no permission gate; the chat itself uses the shared messenger endpoints (participant auth).

---

## Office Hours Booking

**Short description.** Book a bookable slot published by a teacher who teaches you; first click wins.

**Working criteria.**
- **Visible slots:** open slots of the student's faculty + the student's own bookings (classmates' bookings are invisible).
- **Book (in order):** 403 unless the faculty teaches the student; 422 if the slot is past; **atomic conditional UPDATE** claims it; **409** if someone just claimed it. Booking notifies the faculty.
- **Cancel:** allowed for the booking holder or the slot's faculty; re-opens the slot; notifies the other party. No past-slot guard.
- Routes `office-hours*` — no permission gate.

---

## Anonymous Course Feedback

**Short description.** When a teacher opens feedback for a section, the student leaves one anonymous 1–5 rating + comment, revisable while open.

**Working criteria.**
- Submit requires enrollment in the section (403) and an open window (422 otherwise).
- **One response per (section, student)**, editable while the window is open (overwrites). Rating clamped 1–5; comment trimmed.
- Anonymity: the student's identity is never exposed to faculty; results only unlock at ≥3 responses (see the faculty side).
- Routes `course-feedback*` — no permission gate.

---

## Messaging with Faculty

**Short description.** Real-time 1:1 chat with a teacher you share a class with. (Documented fully under [Shared → Real-Time Messaging](#real-time-messaging--presence).)

## My Faculty Directory

**Short description.** A directory of the student's actual instructors, drawn from enrollments, with a messaging entry point. Because teachers stay within their department, a student only sees faculty from their own programme.

## Profile & Settings

**Short description.** Edit name/bio/avatar and set preferences.

**Working criteria.**
- Profile: `name` (≤255), `bio` (≤1000), `avatar` (URL, ≤2048).
- **Exactly 4 preferences**, all required: `theme` (light/dark/system), `notifications` (bool), `email_digest` (bool), `language` (from the admin-supported set). Saving replaces the whole preferences object.
- **Leaderboard opt-in is not a preference** — it's a separate route/columns.
- No permission gate beyond the student role.

---

# Faculty Features

> Everything a teacher sees is limited to the sections they personally teach — their own
> students, materials, and grades. Faculty are *assigned* to sections; the catalog, terms, and
> sections themselves are admin-owned.

## Faculty Dashboard

**Short description.** A teaching overview: course/student counts, grading backlog, at-risk count, AI usage, and recent activity.

**Working criteria.**
- Five stat tiles: Active Courses (distinct courses across taught sections), Total Students (per-course enrolled sums — a cross-enrolled student is counted per course), Pending Grading (ungraded submissions in taught sections), At-Risk Students (deduplicated by student id), AI Queries (chat-session count).
- Recent activity = the faculty's last 6 activity-log entries.
- Route `faculty.dashboard`, no permission gate beyond `role:faculty`.

## My Students Directory

**Short description.** A roster of the teacher's own students for the current term, plus a messaging entry point.

**Working criteria.**
- Scope: sections the faculty teaches, term-scoped to the current term (⚠️ if no current term is configured, all terms are returned), enrolled students only.
- One row per (section, student) — a student in two of the faculty's courses appears twice. Department shown is the student's own major (cross-major electives are intentional).

## Messaging with Students

**Short description.** Real-time 1:1 chat with the faculty's own students. (Full behaviour under [Shared → Real-Time Messaging](#real-time-messaging--presence).)

## My Courses & Course Detail

**Short description.** A read-only view of the sections the faculty teaches, with rosters, materials, and assignments.

**Working criteria.**
- Access via the `manage` course policy: admin **or** teaches (own section, or the legacy `course.faculty_id` owner path).
- Sections of one course collapse to a single card; counts sum across the faculty's own sections.
- On Course Detail, a foreign `?section=` id silently falls back to the faculty's first section rather than 403.
- Gate `view_courses`.

## Course Materials Management

**Short description.** Upload and manage learning resources for a taught section; publishing notifies the class.

**Working criteria.**
- Upload: `title` (≤255), `type` (lecture/slides/reading/assignment/video), `week` (1–52), `is_published` (defaults **true** — omitting it publishes immediately), file ≤50 MB, mimes pdf/doc/docx/txt/md/ppt/pptx/zip.
- Section scoping: a `section_id` is resolved only among the faculty's own sections; a foreign id silently falls back to their default section.
- **Publishing notifies** only that section's enrolled students (`MATERIAL`). Update re-notifies on every save of a published material; a published→draft transition doesn't un-notify.
- RAG sync fires on add/update/delete **regardless of publish state** (drafts are indexed too).
- Download increments the counter before streaming.
- Gates: manage `manage_materials`; download `view_courses`.

## AI Teaching Assistant (chat + streaming)

**Short description.** A ChatGPT-style assistant for teachers on the same cited-answer engine as the student tutor, with streaming — plus quiz/assignment/feedback generators.

**Working criteria.**
- Chat mode is hard-coded `research`; message ≤4000 chars; streaming via SSE (`delta` → `done`, or `error`).
- **No agent tools** (student-only); this is answer-only.
- **Preview vs publish:** every *generate* endpoint returns JSON and persists nothing; only the two *publish* endpoints write. Generation is demo-capped; publishing is not.
- Gates: chat/generate `use_ai_chat`; assignment gen `create_assignment`; publish-assignment `create_assignment`; publish-class-test `manage_class_tests`. Chat, quiz-gen, and assignment-gen also carry `demo.ai.limit`.

## AI Quiz Generation

**Short description.** Generate a draft quiz the teacher edits before publishing.

**Working criteria.**
- `topic` (≤255, required), `difficulty` (easy/medium/hard), `questionCount` (1–20), `bloomLevel`, `includeExplanations`. Question types drawn from 6 (`multiple-choice`, `true-false`, `short-answer`, `essay`, `fill-in-blank`, `matching`); empty selection defaults to multiple-choice.
- Keyless deterministic fallback produces one canned question per requested type.

## AI Assignment Generation

**Short description.** Generate a draft assignment (tasks + rubric) the teacher edits before publishing.

**Working criteria.**
- `title` (≤255), `topics.*` (≤100 each), `points` (1–1000). Fallback rubric is a fixed 50/30/20 split (Correctness/Depth/Presentation).

## Publish AI Draft as Assignment

**Short description.** Turn a draft into a real published assignment for the class.

**Working criteria.**
- Authorized by the `manage` course gate. Section = the faculty's section of the course (**can be null** → assignment is still created/published but notifies nobody). Type defaults `homework`, points default 100, status hard-coded `published`.
- Notifies the section's enrolled students (`ASSIGNMENT`).

## Publish AI Draft as Class Test

**Short description.** Turn draft questions into a real, auto-graded, proctored class test.

**Working criteria.**
- Questions narrowed to the 2 auto-gradable types (`mcq`, `true_false`); true-false answer ∈ {true,false}; mcq needs ≥2 options with the correct answer among the keys. Duration 1–600 min.
- Requires a section (**422 if the faculty teaches no section** — stricter than the assignment path). Created `published` and notifies the roster.
- ⚠️ Created with no `security_config`, so **all proctoring layers are off** (the explicit `shuffle_questions:true` is discarded) — see the [appendix](#appendix-known-codedoc-discrepancies).

## Attendance Management

**Short description.** Mark a section's roster present/absent/late/excused for a date.

**Working criteria.**
- **Statuses:** `present`/`absent`/`late`/`excused`; everything except `absent` counts as present.
- **Upsert keyed on (course, student, date)** — no duplicates; re-saving a day updates it. ⚠️ `section_id` is not in the key, so marking in one section overwrites another section's same-day record for a cross-enrolled student.
- Non-roster user ids are silently skipped. **Unmarked students default to `present`** (a blind save marks everyone present).
- Any date is accepted (no past/future bound).
- Gate `mark_attendance`.
- ⚠️ The marking roster is **not** filtered by pivot status — dropped students appear (unlike every other faculty surface).

## Class Tests Authoring

**Short description.** Author timed MCQ/true-false quizzes on a taught section.

**Working criteria.**
- Ownership: admin or the faculty teaches the test's section.
- **Statuses:** the form allows `draft`/`published`; toggling introduces a third value `closed` (`published → closed → published`).
- Question types: `mcq`, `true_false` only. `marks` 1–100; `duration_minutes` 1–600; `pass_marks` ≥0 (no upper bound — can exceed total); `max_warnings` 0–20 (default 3).
- Editing **deletes and recreates all questions** (question ids change; `total_marks` recomputed).
- **Publishing notifies** the section's enrolled students; draft→published and closed→published both notify, but re-saving a published test does not.
- Availability window (`available_from`/`available_until`) gates the student list, not the attempt clock.

## AI Class-Test Question Generation

**Short description.** Generate class-test questions with AI, edited before saving.

**Working criteria.**
- `topic` (≤255), `questionCount` (1–20), `difficulty`, `types` (`mcq`/`true_false`, empty → both), `marks` (1–100). Explanations forced off.
- MCQ keys A–F (max 6 options); a correct answer that can't be matched to an option defaults to the first key. Blank questions are dropped (fewer than requested can return).

## Class Test Results & Attempt Review Dossier

**Short description.** A results screen per test plus a per-student review dossier.

**Working criteria.**
- Results columns per attempt: student, status, score, total, violation count, **risk score**, event/recording counts, submitted-at, review link. Stats: attempts, disqualified count, average score (over `submitted`/`expired`, excluding disqualified/in-progress), flagged count (`risk ≥ 50` **or** any violation — the 50 is hard-coded).
- The dossier shows identity + fingerprint, risk score with contributing factors, a behaviour-event timeline, grouped webcam/screen recordings (in-browser playback), and a trigger-labelled snapshot photo strip. Media stream through faculty-only routes with existence + ownership checks.
- Gate `manage_class_tests`.

## Risk Scoring

**Short description.** A computed 0–100 suspicion score per attempt, from logged behaviour.

**Working criteria.** Only runs when the `risk_analysis` layer is on, at submit. Weighted factors (each skipped if weight ≤0), clamped 0–100:

| Factor | Weight | Fires when |
|--------|--------|-----------|
| Per violation | 15 × min(violations, 3) → max 45 | any violations |
| Fast answers | 20 | ≥ half of timed answers under 3s |
| Uniform timing | 20 | ≥3 answers, coefficient of variation < 0.1 |
| Long idle | 15 | any idle span ≥ 60s |
| Frequent focus loss | 20 | focus-loss events > `max_warnings` |
| Face loss | 15 | face-loss events > liveness free-warnings (2) |
| Phone activity | 20 | any phone/multi-face detection |
| No mouse movement | 10 | no mouse/click events while others exist |

## Exam Security / Proctoring Layer Selection

**Short description.** Per test, the faculty picks which of the admin-approved proctoring layers apply.

**How it works.** Effective layers resolve as **config default → admin global gate → per-test faculty selection**. Config declares which layers exist; the admin toggles `available`/`default` per layer; the faculty tick the available ones per test (stored in `class_tests.security_config`).

**Working criteria — the 17 layers.**

*Lockdown:* `fullscreen` (each exit = a warning), `tab_switch`, `clipboard` (blocks copy/cut/paste/drag/right-click), `sequential` (one question per screen), `lock_back` (no revisiting; requires `sequential`, auto-off otherwise).

*Integrity:* `shuffle_questions` (per-student order), `shuffle_options` (MCQ only, ≥2 options; T/F keeps order), `watermark` (name/id/session/clock overlay), `integrity_notice` (asks LLMs not to answer).

*Monitoring:* `fingerprint` (SHA-256 device hash at start), `behavior_log` (focus/click/timing/idle events), `risk_analysis` (the 0–100 score).

*Media (all default off, need consent):* `webcam` (continuous recording, 250 kbps cap, 15s chunks), `screen_record` (screen/tab recording), `face_liveness` (blink gate + monitoring, camera without recording), `snapshot_evidence` (JPEG bursts + random samples), `phone_detection` (on-device phone-in-frame, review signal only).

**Client composable timings (from `config/exam_security.php`).**
- **Face liveness:** blink-verified gate before questions; face loss → 3s soft banner (blurs paper) → 8s blocking overlay (counts an incident). First **2** incidents free, then violations. **30s gate bypass** (logged, so a covered face is never locked out). 90s no-blink photo-spoof flag. Second-face detection with a 30s cooldown. Detects at ~8 fps.
- **Snapshots:** bursts of 3 frames at ~700ms; periodic samples jittered 60–90s; downscaled to 640px, JPEG 0.7; server-authoritative cap of 60 per attempt.
- **Phone detection:** COCO "cell phone" at ~2 fps; needs 3 consecutive hits; 30s cooldown; warning severity + photo evidence, never an auto-violation.
- **Audible alerts:** two soft beeps for warnings, three descending harsh beeps for blocks/violations; must be unlocked from the "Begin" gesture; silent no-op when disabled.
- **Recording:** camera decoupled from recording (one shared `getUserMedia` stream); audio never captured; 250 kbps bitrate cap; MIME negotiated vp9→vp8→webm.
- Evidence pruned by `exam:prune-evidence` (weekly, default 30-day retention, 0 = keep forever).

## Grading

**Short description.** A per-assignment queue where the teacher enters a grade and feedback; the student is notified and the grade flows to the transcript.

**Working criteria.**
- Queue: "All Courses" mode (every taught section) or a scoped course/section; a foreign `?section=` id silently falls back. No pagination.
- **`grade` has no upper bound** (a grade above total is accepted); `rubric_scores` is unvalidated. Grading sets status `graded`; re-grading is unrestricted (and loses the `late` marker).
- Notifies the student (`GRADE`) on every save. Every method is authorized by the `manage` course gate.
- Rubric normalization: stored rows use `criterion`, the UI expects `name` — normalized to accept either (⚠️ inconsistent across four code paths).

## AI Suggested Feedback

**Short description.** One click drafts strengths, improvements, and a feedback comment the teacher edits before saving.

**Working criteria.**
- Reads the submission's written content only (≤600 chars), not file text. Returns feedback + strengths + improvements, persists nothing.
- Fallback tiers by grade ratio (≥0.85 "excellent", ≥0.6 "good", else "needs attention").
- ⚠️ Calls the LLM but is **not** demo-capped (unlike draft-grade).

## AI-Assisted Rubric Grading ("Draft grade with AI")

**Short description.** One click reads the actual submission (text + file) and drafts per-criterion scores with justifications, plus a suggested overall grade — all editable prefills.

**Working criteria.**
- Uses full submission text (≤6000 chars incl. extracted file text); empty text → pure heuristic path (provider not called).
- Per-criterion scores are **clamped to each criterion's max**; criterion names matched case-insensitively; unmatched criteria fall back to a labelled heuristic. Suggested grade = sum of criterion scores (can exceed total if the rubric sums higher).
- Heuristic fallback scores 40–100% of max based on length + keyword coverage, rounded to 0.5. Labelled `source: ai` or `heuristic`.
- Nothing is persisted — the teacher reviews and saves. Gated `grade_assignment` + `demo.ai.limit`.

## Submission Similarity Screening

**Short description.** Every submission is compared against classmates' submissions for the same assignment; suspicious pairs are flagged for the teacher.

**How it works.** A queued job extracts the submission text (content + PDF/DOCX), chunks and embeds it, and compares chunk vectors against peers' in the same assignment.

**Working criteria.**
- **Flag threshold: cosine ≥ 0.82** (the score is the best single chunk-pair — one matching paragraph flags the whole submission).
- **Scoping:** same assignment only, not itself, same embedding model only. Assignments are section-scoped, so comparisons never leave the roster.
- Flagged **in both directions** (two rows per pair; identical score, orientation-swapped excerpts, kept to the top 3 pairs).
- **Resubmission** wipes and recomputes the submission's flags (both directions).
- Chunk count bounded to the first 40; minimum chunk length 40 chars. Advisory only — never auto-penalized.
- Surfaced in Grading as an amber badge + side-by-side excerpt panel.

## Peer-Review Averages in Grading

**Short description.** When peer review is enabled, the grading view shows the average peer rating per submission as context.

**Working criteria.** Aggregated in SQL (`AVG(rating)`, count) over completed reviews only; null when none exist. Never part of the grade.

## Question Bank

**Short description.** A per-course library of reusable MCQ/true-false questions shared by faculty teaching the course.

**Working criteria.**
- **Scope:** courses the faculty teaches a section of (section-based; does **not** honour the legacy course-owner path); not term-scoped.
- Manual add validates like class-test questions (mcq needs ≥2 keyed options + correct-key match; true_false answer case-insensitive but stored raw — ⚠️ a `"TRUE"` bank item becomes unanswerable in a test).
- **Import from a test:** dedupes by case-insensitive trimmed question text against the pre-loop bank snapshot; topic = the test title, difficulty `medium`.
- No pagination.

## Draft Class Test from Question Bank

**Short description.** Assemble selected bank questions into a draft class test in one click.

**Working criteria.**
- Guard is section-level (the faculty must hold that section, stricter than course-level). Cross-course item ids are silently dropped.
- Created `draft` (no notification), duration `max(5, items × 2)` minutes.
- ⚠️ Created with no `security_config` → all proctoring layers off (same defect as AI publish).

## Office Hours Publishing & Management

**Short description.** Publish bookable single-capacity slots and manage bookings.

**Working criteria.**
- Create: `starts_at` (after now), `ends_at` (after start), optional location (≤120)/note (≤200). No overlap detection, no max duration, no recurring creation.
- Only upcoming slots are shown to faculty (no past-slot history). Deleting a booked slot notifies the student first.
- `cancelBooking` re-opens the slot and notifies the other party; no past-slot guard.
- No permission gate beyond `role:faculty`.

## Anonymous Course Feedback Windows

**Short description.** Open/close a per-section feedback window; view anonymized results once enough responses exist.

**Working criteria.**
- Toggle is a pure `feedback_open` flip (no window dates/duration); opening notifies the roster (`ANNOUNCEMENT`), re-opening re-notifies.
- **Results unlock only at ≥3 responses** (`MIN_RESPONSES = 3`). Below that only the response count shows (average, distribution, comments withheld).
- At ≥3: average (1 dp), 1–5 distribution (zero-filled), and comments **shuffled with no ids or timestamps**, re-shuffled each load.
- ⚠️ Opening notifies unfiltered `$section->students()` (includes dropped/pending). The response count is always visible, which is mildly de-anonymizing in tiny sections.

## AI Course-Feedback Summary

**Short description.** One click summarizes the anonymous comments into themes.

**Working criteria.**
- Re-enforces the ≥3-response gate server-side (422 otherwise). Empty comments → heuristic (provider not called). Takes ≤60 comments, each ≤400 chars.
- The output-format spec lives in the system message on purpose (so the mock provider doesn't echo a template as an answer). Fallback groups into Going-well / Concerns / Suggestions from the rating distribution.
- Gated `use_ai_chat` + `ai.chat.access` + `demo.ai.limit`.

## Faculty Analytics

**Short description.** Per-course (or all-courses) insights: enrollment, attendance, grade distribution, average score, submission completion, and the at-risk list.

**Working criteria.**
- Overview figures: course count, total students (once per course), average attendance (**unweighted mean of per-course rates**), pending grading.
- Report per course/section: enrolled count, attendance summary, grade histogram (⚠️ order array omits A+ and D-, silently dropping them), average score (mean of per-submission %), submission stats (total/graded/pending/completion rate), at-risk list.
- Gate `view_department_analytics`.

## At-Risk Early Warning

**Short description.** Flags students on four live signals, with High/Watch levels and a one-click message.

**Working criteria — the exact 4 signals.**

| Signal | Condition |
|--------|-----------|
| Attendance | ≥4 sessions **and** rate < 75% |
| Missed deadlines | ≥2 missed published, past-due assignments |
| Test average | non-null **and** < 50% |
| Grade | one of `D+`, `D`, `D-`, `F` (strict, case-sensitive) |

- **Level:** ≥2 signals → `high`; exactly 1 → `watch`; 0 signals → excluded. Sorted most-signals-first, each with a "why" string.
- The test average includes disqualified/expired attempts (score 0), so proctoring disqualifications drag it down.
- Dashboard count deduplicates a student across the faculty's courses (⚠️ N+1, uncached, recomputed each dashboard load).

## Assignment Management (edit / status / delete)

**Short description.** Edit, publish/unpublish, or delete published assignments, and toggle peer review.

**Working criteria.**
- Update: `title` (≤255), `type` (≤50, free string — `quiz` is a magic value), `total_points` (1–1000), `due_at` (nullable, past dates accepted), `status` (draft/published), `peer_review_enabled` (bool), rubric rows (`criterion` + `points`).
- Update **notifies** the section's enrolled students only when the post-update status is `published`, with a "due date changed" branch. Notification links to the assignment page (publish path links to transcript — inconsistent).
- **`toggleStatus`** is a pure draft↔published flip that **notifies nobody** and writes no activity log (⚠️ a silent publish, unlike update).
- **Delete** is unconditional, sends no notification, and depends on the DB FK for cascade.
- There is **no create route** — assignments are created only via the AI-assistant publish path.
- Peer-review toggle has no dedicated route/notification; it's a `nullable` field on the edit form (omitting it preserves the current value).
- All gated `create_assignment`.

## Discussion Feed Participation & Moderation

**Short description.** Faculty take part in their sections' discussions and moderate them (pin/delete).

**Working criteria.**
- Faculty see and moderate only sections they personally teach.
- **Moderate** = pin a post or delete any post/comment in their sections (needs `moderate_discussions` **and** teaching that section). They can also delete their own content anywhere they participate.
- Reports they don't handle escalate to the admin moderation queue.

---

# Admin Features

> Admins run the institution. Every admin route is gated by `role:admin` **plus** a specific
> permission. Most admin mutations write an activity-log entry.

## Admin Dashboard

**Short description.** A single overview of users, documents, activity, system health, and student insights.

**Working criteria.**
- Four stat cards: Total Users (+ active count), **Online Now** (users who *logged in* in the last 15 minutes — see the caveat below), Documents (approved count + pending), New This Week.
- Pending documents (limit 5), recent activity (limit 8).
- **System health:** `down` if the DB is unreachable, `degraded` if disk ≥85% used, else `healthy`; also reports the active AI provider name and indexed/pending doc counts.
- **Student insights:** students enrolled this term, overall attendance rate, current term name.
- ⚠️ "Online Now" is a login-recency metric, **not** presence — a user active for hours on one login reads as offline after 15 min. Real presence uses a 120s `last_seen_at` heartbeat elsewhere.
- No extra permission beyond `role:admin`.

## User Management

**Short description.** Create, edit, activate/deactivate, delete users, and assign roles.

**Working criteria.**
- **Protected admin account** (`admin@university.edu`, by email): cannot be deactivated, deleted, or role-changed.
- Create: `name` (≤255), unique email, password (min 8), role (student/faculty/admin), optional department/ids, `semester` (1–12). `is_active` forced true.
- Update: fields are `sometimes`; email unique ignoring self. The protected admin's `is_active` is silently dropped.
- **Delete guards (in order):** protected admin → error; self-delete → error; then a QueryException catch → "has related records, deactivate instead" (FK-safe hard delete).
- Assign role **replaces** all roles with exactly one.
- **Temporal roles (`expires_at`):** the model supports expiring role grants (filtered out when expired), but ⚠️ **no admin UI writes an expiry** — it's a model-layer capability only.
- Index paginated 25, filter-scoped stats.
- Gates: view `view_users`; create `create_user`; update/toggle `update_user`; role `manage_user_roles`; delete `delete_user`.

## Role & Permission Matrix

**Short description.** A visual grid to turn permissions on/off per role.

**Working criteria.**
- Sync is a **full replace** — the `permissions` key must be sent; an empty array revokes everything on that role.
- **The admin role is locked server-side** (403 if you try to edit it) — the admin always holds every permission, so it can't be edited and lock everyone out.
- Permissions grouped by 10 categories (Documents, User Management, Courses, Assignments, Attendance, Exams, AI & Chat, Community, Analytics, System).
- Gate `manage_permissions`.

## Course Catalog Management

**Short description.** The official course catalog — create, edit, remove courses.

**Working criteria.**
- `code` (≤20, unique), `name` (≤255), `department_id`, `semester` (1–12), `credits` (required 1–12), prerequisites (multi-select).
- **Delete blocked** while the course has any enrolled students.
- Gates: view `view_courses`; create `create_course`; update `update_course`; delete `delete_course`.

## Course Prerequisites

**Short description.** Define which courses must be completed first (enforced at registration).

**Working criteria.**
- Multi-select on the course form; direct self-reference is filtered (⚠️ A→B→A cycles are not detected).
- Only a `completed` enrollment satisfies a prerequisite; registration is blocked server-side until met.

## Sections Management & Student Assignment

**Short description.** Create section offerings, assign faculty and students, and drop students.

**Working criteria.**
- **Label constraint: exactly A–J** (10 values), uppercased/trimmed, unique per (course, term).
- **Faculty–department invariant:** an assigned faculty must belong to the course's department (skipped when the course has no department).
- `max_enrollment` required (1–1000).
- **Assign (per student, in order):** skip non-students; skip if already in the section; if full → **waitlist** + notify; else → **`pending`** placement (reserves a seat) + "register to confirm" notification. Capacity is re-checked per student.
- **Drop** triggers waitlist promotion and notifies the dropped student.
- **Delete blocked** while the section has enrolled students.
- ⚠️ An all-waitlisted assign batch flashes an *error* despite succeeding.
- Gate `manage_sections`.

## Terms & End-of-Term Rollover

**Short description.** Manage the academic calendar (the 3 standard terms) and close a term at its end.

**Working criteria.**
- Only Fall/Spring/Summer exist (at most 3 terms).
- **Exactly one `is_current`** (set transactionally). Nothing forces at least one current — closing the only current term leaves zero.
- **Close (transactional):** archive the term's sections (`is_active=false`), mark `enrolled` rows `completed` (grades frozen; `pending`/waitlisted untouched), clear `is_current`, and optionally promote a next term. The next term must not be the current one.
- **Delete guards:** can't delete the current term; can't delete a term with sections.
- Gate `manage_terms`.

## Registration Window Control

**Short description.** Open or close registration for a term.

**Working criteria.** A pure `is_registration_open` flip (no body/validation); it drives the student registration window. Gate `manage_terms`.

## Departments Management

**Short description.** Create, edit, remove departments.

**Working criteria.**
- `name` (≤150, unique), optional `code` (≤20, unique), description, `is_active`.
- **Delete blocked** while the department has any users **or** courses.
- Gate `manage_departments`.

## Document Library (all statuses)

**Short description.** The admin view of every uploaded document, in every state.

**Working criteria.**
- Shows all statuses and does **not** apply visibility scoping — admins see all uploads. Paginated 25; filters by category/status/department/file-type/search.
- Categories are a hard-coded list, but the field validates only as a free string ≤100.
- **Statuses:** `pending`, `processing`, `approved`, `rejected`, `failed`. (⚠️ `processed` is a dead enum case never assigned; `failed` documents appear in no stat card.)
- Soft-deleted; download/preview increment counters (no per-user audit row).
- Gates: view `view_documents`; download `download_document`; delete `delete_document`.

## Document Approval Workflow

**Short description.** Review uploads: approve, reject, request changes, or comment — approval feeds the RAG index.

**Working criteria.**
- **Upload:** `title` (≤255), `category` (required), `visibility` (required, ≥1 of students/faculty/admins), file ≤50 MB, mimes pdf/doc/docx/txt/md/ppt/pptx. Byte-identical duplicates rejected by SHA-256 (soft-deleted rows excluded, so a deleted file can be re-uploaded).
- **Reason/comment requirements:** approve comment optional; reject reason required (≤1000); request-changes comment required; comment required.
- **Approve** sets `processing`, dispatches `ProcessDocumentJob` → extract → chunk → embed → on success `approved` (the terminal indexed state), on failure `failed`.
- **Reject** sets `rejected` and **deletes the chunks** (de-indexes from RAG). **Edit** forces `pending` and deletes chunks. Request-changes sets `pending` only; comment changes no status.
- ⚠️ `visibility = 'admins'` is selectable but no role maps to it; approve-time comments are recorded but never rendered; delete removes the file but not the chunks.
- Gate `approve_document` (queue actions), `upload_document` (upload).

## Exams & Timetable Management

**Short description.** Schedule exams for a course; notifies affected students.

**Working criteria.**
- Types: `midterm`, `final`, `quiz`, `practical`. Fields: `exam_date` (required), optional `start_time` (H:i), `duration_minutes` (1–600), location, `total_marks` (1–1000), instructions.
- **Section-less = all sections:** with a `section_id` → one exam; without → one exam row per section of the course (a course with zero sections gets one section-less row).
- Each created exam notifies that section's enrolled students (`EXAM`).
- ⚠️ Fan-out is create-only (editing back to "all sections" keeps the current section); delete sends no notification.
- Gate `manage_exams`.

## Exam Security Global Gate

**Short description.** The global control over which proctoring layers faculty may use and which are on by default.

**Working criteria.**
- Per layer, two switches: `available` (global gate — off hides it everywhere and strips it from faculty input) and `default` (pre-ticked on new tests). Only these two are overridable; labels/categories are config-only.
- Turning a layer off removes it from existing tests at read time (not migrated).
- A "How each layer works" guide surfaces the live configured timings so it can't drift.
- Gate `manage_exams` (not a dedicated permission).

## AI Settings

**Short description.** The control panel for the AI provider, models, retrieval tuning, prompt, and languages.

**Working criteria.**
- Settings: provider (openai/mock), model, temperature (0–2), max_tokens (1–32000), embedding model/provider (openai/jina/mock), embedding fallback toggle + secondary, chat fallback toggle + primary (openai/openrouter), OpenRouter model list, `rag_top_k` (1–20), `rag_similarity_threshold` (0–1), system prompt (≤4000), supported languages (≥1, default English + Bangla), and write-only API keys.
- **API keys are write-only and encrypted** — never sent to the client; blank keeps the existing key; a `remove_*` flag clears it. Settings merge over existing (partial saves are safe).
- **Provider switch → reembed:** changing embedding provider/fallback/secondary/jina-key dispatches a **synchronous** corpus reembed. ⚠️ Changing only the OpenAI embedding *model* does not trigger it; re-saving the same Jina key triggers a spurious reembed (ciphertext comparison).
- OpenRouter model chain always appends `openrouter/auto` as the last resort.
- Persisted to the `ai` settings row, overlaid on config at boot.
- Gate `configure_ai`.

## AI Provider Connection Test

**Short description.** A "Test connection" button that actually contacts the provider.

**Working criteria.**
- **It makes a real, billable round-trip** (a "ping" chat with 5 tokens, or a 1-vector embedding), reporting reachability and the answering model/dimensions.
- `mock` short-circuits with no network call. Failures return 422 with the error.
- ⚠️ With the shipped `.env` (provider = mock), the default test always short-circuits to the mock message regardless of the OpenAI key.

## Email (SMTP) Settings

**Short description.** Configure SMTP without touching `.env`, and send a test email.

**Working criteria.**
- Fields: `mailer` (smtp/log), host, port (1–65535), encryption (tls/ssl/none), username, password (write-only, encrypted), from address/name.
- Overlaid onto mail config at boot; a decrypt failure (e.g. rotated APP_KEY) falls back to env.
- Test sends via the **currently-booted** config — settings saved the same request apply after the next boot; with `mailer=log` the test "succeeds" without delivering.
- Gate `configure_email`.

## Platform Analytics

**Short description.** An institution-wide usage overview.

**Working criteria.**
- Aggregates: total/active users, total AI queries (user messages), approved documents, chat sessions, saved answers; department AI-usage breakdown; top queries (byte-identical grouping, top 8); users by role.
- Read-only, no filters/date-range, uncached.
- ⚠️ `usersByRole` ignores `expires_at`, so it can disagree with User Management.
- Gate `view_all_analytics`.

## AI Usage Monitor & Access Control

**Short description.** Per-user AI usage tracking plus block/unblock of a user's AI-chat access.

**Working criteria.**
- Token usage aggregated from assistant messages (per user: request count, total tokens, last used). Recent requests paginated 20.
- **Block:** `reason` (5–500, required), `expires_in_days` (1–365; **null/0 = permanent**). The protected admin can't be blocked. Blocks auto-expire (no cleanup job).
- **What a blocked user sees:** HTTP 403 JSON showing the admin's reason verbatim (⚠️ the private reason is exposed to the user) and the until-date.
- ⚠️ Block/unblock write no activity-log entry.
- Gates: view `view_all_analytics`; block/unblock `update_user`.

## System Monitor

**Short description.** A live, read-only look at server health.

**Working criteria.**
- Probes CPU load, PHP request memory (⚠️ not system memory), disk usage, DB (PDO), queue (sync always operational, else a size probe), cache (real write/read round-trip), AI provider (`isAvailable()` only), and uptime (`/proc/uptime`, Linux-only → "n/a").
- ✅ **No billable AI call** — the AI probe is a config/key check only.
- Gate `manage_system`.

## Announcements / Broadcast Notifications

**Short description.** Broadcast a notification to an audience, with edit-after-send.

**Working criteria.**
- Audiences: `all`, `student`, `faculty`, `admin`. Recipients are **active** users of the audience; inactive users never receive.
- One notification row per recipient (no announcements table). Grouped for display by (created-at, title), last 50.
- **Edit** updates every matching row (content only; the audience is fixed once sent); no delete/recall; editing doesn't re-notify or reset read state.
- ⚠️ The 50-row cap is applied before grouping, so one large broadcast fills the list.
- Gate `send_notifications`.

## Discussion Moderation Queue

**Short description.** The university-wide queue of reported posts and comments.

**Working criteria.**
- `open` reports, paginated 20.
- **`resolve`** = dismiss (keep content, mark resolved). **`removeContent`** = soft-delete the target, then mark resolved.
- ⚠️ Both store status `resolved` — the upheld/dismissed outcome isn't recorded; neither notifies anyone or penalizes the author.
- Gate `moderate_discussions`.

## User Activity Tracking

**Short description.** A panel showing who visited, from where, on what device, and an IP-derived location.

**How it works.** `TrackVisit` records a page view in `terminate()` (after the response flushes, so zero page latency); the admin panel filters and aggregates the log.

**Working criteria.**
- **Tracked:** GET, resolved route, HTTP 200, not a partial Inertia reload, and either an Inertia visit or a full `text/html` load. Guests are tracked (null user).
- **Blocklist (7 routes):** notifications poll/index, heartbeat, global search, messenger overview, messenger messages index, live-toggle.
- **Geo:** free ip-api.com over plain HTTP (⚠️ PII to a third party), cached per-IP 30 days; private/reserved IPs → "Local" with no call; failures → null.
- **Device:** dependency-free UA parser (device type / platform / browser).
- Stats (filter-scoped): total, today, unique visitors, guest visits, device/country/page breakdowns. Filters: user search (⚠️ excludes guests when set), role, device, country, date range. Feed paginated 30.
- **Retention:** `visits:prune`, default 90 days (0 disables), scheduled weekly Sunday 03:30.
- Gate `view_all_analytics`.

---

# Shared / Cross-Role Features

## Authentication (login with role selection)

**Short description.** Session auth with a mandatory role selection at login.

**Working criteria.**
- Login validates email (email format), password, and `role` (student/faculty/admin) — role is mandatory, no default.
- **Rate limit: 5 attempts** per email+IP (Laravel's default 60s decay); every failure branch increments it, cleared only on success.
- Failure messages: missing user / bad password → generic (anti-enumeration); **inactive account → distinct "pending admin approval"** message; role mismatch → "no access to the selected role."
- Redirects by role: admin → admin dashboard, faculty → faculty dashboard, student → student dashboard.
- ⚠️ The `guest` middleware group is empty, so an authenticated user can re-POST login.

## Self-Service Signup

**Short description.** Create an account (student or faculty only; admin is login-only).

**Working criteria.**
- Validates name, unique email (must contain `university.edu`, `.edu`, or `gmail.com`), confirmed password, role (student/faculty), department, and accepted terms; `student_id`/`employee_id` required per role.
- **New accounts are created inactive** (`is_active=false`, pending admin approval) and are **not** logged in.

## Demo Login

**Short description.** One-click login to seeded demo accounts (all password `demo123`).

**Working criteria.**
- Fixed accounts per role (`student@`, `prof.smith@`, `admin@university.edu`). If missing, it auto-seeds RBAC and re-queries.
- ⚠️ Bypasses rate limiting, the active check, and password verification.

## Password Reset

**Short description.** Standard email-based password reset.

**Working criteria.** Token TTL 60 minutes, 60s throttle; update sets a new hash and a fresh remember token.

## RBAC Enforcement

**Short description.** Role and permission gating on every protected route, with temporal grants.

**Working criteria.**
- Unauthenticated → redirect to login. **Deactivated users are force-logged-out** on their next gated request.
- Role failure → redirect to the user's dashboard (⚠️ never 403, even for JSON). Permission failure → 403 JSON for XHR, else redirect. Permission checks use OR semantics (`permission:a,b` passes with either).
- **46 permissions** across 10 categories; roles↔permissions many-to-many; the `role_user` pivot carries `expires_at` (expired grants are filtered out at query time).
- **Seeded grants:** students 16 permissions, faculty student-set + 7 more (23 distinct), admin all 46.

## In-App Notifications

**Short description.** A per-recipient notification system with a live bell.

**Working criteria.**
- **10 types:** `grade`, `material`, `assignment`, `submission`, `enrollment`, `exam`, `class_test`, `announcement`, `office_hours`, `system`.
- Poll every 75s; index paginated 25; mark-read / read-all / delete are owner-scoped (403 otherwise).
- **Mute:** `preferences.notifications = false` silences the poll only (⚠️ not the index/dropdown). Default is on.
- Each event creates one row per recipient (auto-fired on graded submission, published material/assignment/exam/class-test, enrollment assignment, announcement).

## Email Digests & Deadline Nudges

**Short description.** A weekly digest and daily "assignment due soon" emails.

**Working criteria.**
- **Weekly digest** (`digests:send-weekly`, Mondays 07:00): deadlines ≤7 days, grades posted ≤7 days, booked office hours ≤7 days, due flashcard count. **Returns null (skipped) when there's nothing to report.**
- **Due nudge** (`assignments:remind`, daily 08:00): published assignments due within 24h, skipped if a submission exists; **de-duped at most once per student per assignment** (keyed on the notification's title + assignment id).
- **Opt-out:** `preferences.email_digest = false`. Both mails are queued; sent via the admin-configured SMTP.
- ⚠️ Opting out of email still writes the in-app dedupe row first; the digest's deadline window has no lower bound (overdue items can appear).

## Real-Time Messaging & Presence

**Short description.** Direct 1:1 chat between a student and a faculty member they share a class with, with presence, typing, and unread counts.

**Working criteria.**
- **Who can message whom:** exactly faculty↔student, and only if they share a section (`canMessage`). Student↔student, faculty↔faculty, and any admin messaging are always false.
- Conversations are `direct` (1:1) or `group` (study rooms); the direct lookup is scoped so a group room never satisfies a 1:1. Message body ≤5000 chars.
- **Delivery is DB-first, then broadcast** (`MessageSent`, `ShouldBroadcastNow`) over a private per-conversation channel; a broadcast failure is logged and swallowed (persistence is the commit point).
- **Presence:** 120s active window via a `last_seen_at` heartbeat (pinged ~60s, never while the tab is hidden). Typing is a client-side whisper (needs the realtime socket).
- **Polling fallback:** when the socket is unavailable, messages poll (~4s), the contact list polls ~10s. ⚠️ Realtime is off by default (`BROADCAST_CONNECTION` defaults to null), so out of the box it runs poll-only and the typing indicator doesn't work.
- Routes under `messenger.*`, `auth` only (relationship-based auth, no role/permission gate).

## Discussion Feed (shared)

**Short description.** The section discussion feed is a single shared surface for students and faculty (documented per-role above); membership is derived from enrollment/teaching, and admins can access every section.

## Global Search (⌘K)

**Short description.** One palette searches pages plus content, scoped to what the user can access.

**Working criteria.**
- Server requires ≥2 chars; the client fires at ≥3 chars with a 350ms debounce. Up to 5 results per group; empty groups dropped.
- **Groups:** Knowledge (semantic, via retrieval), Courses, Assignments, Discussions, Chat History (deep-linked to the exact message), and Users (admin-only).
- Everything is permission-scoped. ⚠️ For admins, the Assignments and Discussions groups return empty (their `sectionIds` is empty).
- Route `search.global`, `auth` only.

## Public Document Library (`/docs`)

**Short description.** A public, browsable file library served from `public/document-library`.

**Working criteria.** Unauthenticated; path-traversal guarded; directories render a listing, files stream inline (or `?download=1` to force download). Still gated by maintenance mode.

## Landing Page / Product Presentation / Legal Pages

**Short description.** Public marketing surfaces: the landing page (`/`), a full-screen pitch deck (`/presentation`, no app chrome), and hardcoded Terms/Privacy pages. All are public but still behind the maintenance switch.

---

# Engine / System Features

## RAG Pipeline (chunk → embed → retrieve → cite)

**Short description.** The retrieval-augmented generation engine behind all chat, grounded in approved documents.

**Working criteria.**
- **Chunking:** word-window with overlap — effective size 150 tokens (~113 words), overlap 40 (~30 words), step ~83 words; floor 50 tokens. Page-aware for PDFs.
- **Extraction:** PDF (true page awareness, Smalot), DOCX (single page), and any other extension read as plain text.
- **Retrieval:** embed the query, filter candidates to the active embedding model + the user's retrievable scope, score by cosine in PHP (batched 300), keep those ≥ threshold, take top-K. **Default top-K 6, threshold 0.35** (clamped to 0.08 for the mock model). Single-best fallback returns the top match if nothing passes.
- **Retrievable scope** = approved+visible library docs ∪ the user's own notes ∪ their sections' materials (students: published only; faculty: their own incl. unpublished).
- **Cache:** retrieval results cached (1h TTL), keyed by corpus version + model + user + material scope + top-K + threshold + query hash; invalidated by a corpus-version bump on any document change.
- Ingestion is idempotent (re-chunks on reprocess); processing exceptions are swallowed to `failed` status.

## Personal-Corpus RAG ("chat with my materials")

**Short description.** Student notes and course-material files are indexed as hidden shadow documents through the same pipeline.

**Working criteria.**
- Shadow documents carry `source_type = note|material`, `owner_id`/`source_id`, and are hidden everywhere by a global scope except retrieval.
- Extractable material types: pdf/docx/txt/md/markdown/text; anything else (or an unpublished/missing material) removes the shadow.
- Sync jobs fire on note/material create/update/delete (delete = the job finds no row → forget the shadow); unchanged content is skipped via a stored hash.
- Backfill: `rag:sync-personal [--notes] [--materials] [--user=]`.
- Citations label sources "Personal Note" / "Course Material" automatically.

## Multi-Provider AI with Fallback Chain

**Short description.** Chat resolves through an ordered provider chain that always ends in the never-fail mock.

**Working criteria.**
- **Chat chain** (only built when fallback is enabled **and** an OpenRouter key exists): primary + secondary (OpenAI/OpenRouter, order by the admin's `fallback_primary`), each kept only if its key is present, with **Mock always appended as the terminal tier** — so a chat result is always produced.
- Failover is silent across tiers; a streaming failure after the first token rethrows (to avoid duplicated text). An exception surfaces only when the whole chain fails.
- Providers: OpenAI (gpt-4o, native HTTP), OpenRouter (free-model chain ending in `openrouter/auto`), Mock (keyless, deterministic, always available). `gemini`/`local` config blocks exist but have no implementation (silently resolve to Mock).

## Multi-Backend Embeddings with Fallback

**Short description.** Embeddings resolve separately from chat (OpenRouter has no embeddings endpoint).

**Working criteria.**
- Backends: OpenAI (`text-embedding-3-small`, 1536-dim), Jina (`jina-embeddings-v3`, free/multilingual), Mock (256-dim, lexical hash). If none are available, Mock is used — embeddings never hard-fail.
- **Dual-embed** (when embedding fallback is on): both backends' vectors are stored, so retrieval survives one embeddings API dying.
- Batch size 50 chunks per call.

## Confidence Scoring & Citations

**Short description.** Every grounded answer carries numbered citations and a confidence score.

**Working criteria.**
- Confidence = the best retrieval similarity (0–1), banded into Very High/High/Medium/Low/Very Low (0.9/0.7/0.5/0.3 thresholds).
- Context block format: `[n] Title (p.N): chunk`, joined by blank lines, capped at 6000 chars. Excerpts collapsed to ≤240 chars.
- ⚠️ The `document_chunks.section` field is never populated (always null in citations).

## Streaming Responses (SSE)

**Short description.** Token-by-token streaming for student chat and the faculty assistant.

**Working criteria.**
- SSE events: `delta` (`{text}`), `tool_start` / `tool_result` (agent mode), `done` (same payload as the JSON endpoint), `error`.
- Headers defeat proxy buffering; the client parser handles CSRF and frame reassembly. Mid-stream errors become an `error` event (HTTP 200 already sent).
- Token accounting on the streaming path relies on `stream_options.include_usage`.

## Model-Tagged Vectors & Corpus Re-embedding

**Short description.** Vectors are tagged with the embedding model; retrieval only scores the active model's vectors, so a provider switch requires re-embedding.

**Working criteria.**
- Switching the embedding backend auto-dispatches a reembed (synchronously, in-request). Manual: `rag:reembed`.
- Reembed covers library docs **and** personal shadow docs, skipping zero-chunk documents.
- ⚠️ Changing only the OpenAI embedding *model* doesn't auto-trigger a reembed (orphans vectors until `rag:reembed`).

## Demo-Mode AI Usage Cap

**Short description.** In demo mode, every account is capped at a fixed number of AI requests across all AI surfaces.

**Working criteria.**
- **Cap: 10 requests per account** (`DEMO_AI_REQUEST_LIMIT`), active only when `APP_MODE=demo`; unmetered otherwise.
- **Charged up-front** — a request is counted the moment it passes, regardless of provider success (so a failing call can't be farmed; ⚠️ even a zero-cost small-talk reply consumes one).
- Applied to 12 request-consuming AI POST endpoints only (never page loads/history). Exhaustion → HTTP 429 with a `demo_limit_reached` marker.

## Hidden Maintenance Switch

**Short description.** A URL-driven, globally persisted switch that puts the whole app behind a maintenance page.

**Working criteria.**
- `?live=false` → Maintenance page (HTTP 503) for everyone; `?live=true` → back to live, evaluated first so an operator can always unlock.
- State persisted globally via cache (`Cache::forever`); default from `APP_LIVE_DEFAULT` (default live). The testing env and `/up` are never gated; JSON callers get a JSON 503, page loads get the Maintenance page.
- ⚠️ The toggle has no authentication — any anonymous request with `?live=false` takes the site down (security through obscurity only).

## Activity Logging / Audit Trail

**Short description.** Key admin and user actions are written to `activity_logs`.

**Working criteria.** Most mutations log an entry (action, description, subject, causer). ⚠️ Some do not: section/department/term/exam delete, document download/preview, AI-usage block/unblock, discussion moderation, and settings saves.

---

# Appendix: Known Code/Doc Discrepancies

These are behaviours where the code diverges from an intuitive reading, from older docs, or from
itself. They are documented so the feature descriptions above can stay accurate; none are fixed
here — this is a reference file only.

**Routing / config**
- `routes/{student,faculty,admin}.php` are **empty stubs** — every route lives in `routes/web.php`.
- `config/permissions.php` and `UserRole::permissions()` return **dot-notation slugs used nowhere**; the live permission model is `app/Enums/Permission.php` + the DB.
- Dead config keys: `rag.chunking.strategy`, `rag.retrieval.rerank`, all `rag.citation.*`, `rag.context.window_size`, the entire `config/vector.php`, `ai.providers.gemini.*`, `ai.providers.local.*`, `ai.speech.*`.
- Several hardcoded config fallbacks contradict the shipped defaults (chunk size 512 vs 150, context 3000 vs 6000, top-K 5 vs 6, threshold 0.7 vs 0.35) — only reachable if the config file is missing.
- **The shipped `.env` runs the mock AI provider** (`AI_DEFAULT_PROVIDER=mock`) despite a real OpenAI key being present; realtime broadcasting defaults to off.

**Grades / GPA**
- Two incompatible CGPAs: Dashboard/Roadmap use an **unweighted** mean; Transcript uses a **credit-weighted** one — they also differ on which statuses count and the empty sentinel.
- The 4.0 scale omits A+ and D-; the faculty grade histogram silently drops both even though D- is an at-risk grade.
- `grade` has no upper bound; `rubric_scores` is unvalidated.

**Assignments / draft leakage**
- Calendar, dashboard deadlines, and roadmap query assignments **without `status='published'`** — draft assignments can leak to students.
- `AssignmentController::toggleStatus` publishes without notifying, unlike the update path.

**Class tests / proctoring**
- AI-published class tests and question-bank draft tests are created with **all proctoring layers off** (no `security_config`).
- An expired in-progress attempt is finalised with score 0 and no answers graded, contradicting the normal late path.
- Editing a class test deletes and recreates all questions (question ids change).
- The results "flagged" threshold (risk ≥ 50) is hard-coded; the risk face-loss factor uses liveness config even when face-liveness is off.

**Attendance**
- The marking roster ignores pivot status (dropped students appear); unmarked students default to present; the upsert key omits `section_id` (cross-section overwrite for a co-enrolled student).

**Enrollment**
- A skipped waitlist entry is deleted before the skip check, so the student loses their queue position.

**Anonymity / privacy**
- The calendar feed URL is non-expiring (no revocation without APP_KEY rotation).
- The AI-block reason is shown verbatim to the blocked user.
- Geo lookup sends the visitor IP to a third party over plain HTTP.
- Course-feedback "open" notifies dropped/pending students; the always-visible response count is mildly de-anonymizing in tiny sections.

**Misc**
- Notes/saved-answers `tags` are displayed but have no write path.
- `course_id` is enrollment-checked by the chat tools but only existence-checked by the UI form requests.
- `DocumentStatus::PROCESSED` is a dead enum case; `failed` documents appear in no stat card.
- Announcements are grouped by (created-at, title) with the 50-row cap applied before grouping.
- Discussion moderation stores only "resolved" — the upheld/dismissed outcome isn't recorded.

