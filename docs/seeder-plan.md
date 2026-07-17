# Seeder Refactor — Analysis & Plan

> Phase 1 deliverable for the seeder overhaul. Produced after reading every
> migration, model, relationship and the full Feature test suite.

## Key finding — the existing seeders are load-bearing fixtures, not dummy data

`RBACSeeder`, `AcademicSeeder` and `KnowledgeBaseSeeder` are **not** disposable.
The 25-test Feature suite (`tests/Feature/*`) runs with `DatabaseTransactions`
against the already-seeded dev database and asserts on a precise contract:

- The four demo accounts (`student@`, `prof.smith@`, `prof.jones@`,
  `admin@university.edu`, all password `demo123`).
- The demo student is enrolled in `CS301/CS305/CS310`, has `CS330` as a *pending*
  admin placement, and `CS340` as an unassigned open offering
  (`SelfRegistrationTest`).
- `CS301` has **exactly** sections `['A','B']`, taught by two *distinct* faculty
  (`SectionStructureTest`, `SectionIsolationTest`, `MultiSectionTest`).
- A completed prior term gives the transcript/CGPA real history (`TranscriptTest`).
- The knowledge base has indexed documents with chunks + embeddings
  (`AdminRoleTest`).

**Decision:** preserve these three seeders verbatim (they are the demo/test
baseline) and *layer* realistic, production-scale population on top with new
modular seeders. This satisfies the prompt's intent (a believable university
dataset, modular seeder architecture, scalable volume) while honouring the
prompt's own final constraint #4 (non-breaking) and CLAUDE.md's Legacy
Preservation rule.

## Hard invariants every new seeder must respect (proven by tests)

1. **Every course has ≥1 section.** (`SectionStructureTest::test_every_course_has_at_least_one_section`)
2. **Every academic row carries a `section_id`** — `course_user`,
   `course_materials`, `assignments`, `attendance_records`, `exams`. No row may
   have a `course_id` but a null `section_id`.
   (`SectionStructureTest::test_academic_records_are_attached_to_a_section`)
3. **Exactly one `terms.is_current = true`**, and at least one *other* term must
   exist (rollover/"set current" tests need a second + a "next" term).
4. **Do not add a third section to `CS301`** (label set must stay `['A','B']`).
   → New `SectionSeeder` only creates sections for courses that have none, so the
   demo courses are never touched.
5. `course_user` is unique on `(course_id, user_id)` — a student enrolls in any
   one course at most once.
6. `attendance_records` unique on `(course_id, user_id, date)`.
7. `users.email`, `student_id`, `employee_id` are unique. Bulk IDs are namespaced
   away from the demo IDs (`FAC001/002`, `ADM001`, `CS2024001`).

## Schema reality vs. the prompt (trust code over docs)

| Prompt term | Actual schema |
|---|---|
| `semesters` table | none — `semester` is an **integer curriculum level (1–9)** on `courses` and `users` |
| `enrollments`, `semester_registrations`, `section_assignments` | the single `course_user` pivot (with `section_id`, `term_id`, `status`) |
| Faculty "designation" / profile table | no column — folded into `users.bio` |
| `academic_tasks` | the `tasks` table (personal to-dos: `Lab Report`, `Presentation`, …) |

There is therefore **no `SemesterSeeder`**; "semester registration" = inserting a
`course_user` row bound to the current term + a section.

## Relevant tables & ownership

```
roles, permissions, permission_role, role_user        ← RBACSeeder (kept)
departments                                            ← RBACSeeder (kept)
users (4 demo)                                          ← RBACSeeder (kept)
terms                                                   ← TermSeeder (new, authoritative)
courses                                                 ← CourseSeeder (new) + AcademicSeeder (demo)
users (bulk faculty / admins / students)               ← FacultySeeder / AdminSeeder / StudentSeeder (new)
sections                                                ← SectionSeeder (new) + AcademicSeeder (demo)
course_user                                             ← EnrollmentSeeder (new) + AcademicSeeder (demo)
course_materials, exams, attendance_records            ← CourseMaterial/Exam/Attendance seeders (new) + demo
notes, tasks                                            ← Note/Task seeders (new)
class_test_attempts                                     ← ClassTestAttemptSeeder (feeds analytics + leaderboard)
flashcard_decks, flashcards                             ← FlashcardSeeder (demo student + sample cohort)
users.leaderboard_opt_in / leaderboard_alias           ← LeaderboardSeeder (opt-ins; XP derived at read time)
posts, post_comments, post_reactions, post_reports     ← DiscussionSeeder (section feed + moderation queue)
documents, document_chunks, embeddings                 ← KnowledgeBaseSeeder (kept)
```

User-driven data is intentionally **not** seeded: chat sessions/messages, saved
answers, assignment submissions (beyond the single demo one), document approvals.

**Engagement seeders (new-feature demo data).** These layer on top of the academic
population so the newer features (Learning Analytics, Leaderboard, Flashcards,
Discussions) are populated on the demo login rather than empty:

- `ClassTestAttemptSeeder` — submitted attempts (with plausible scores) for a share
  (`class_test_attempt_rate`) of each published test's enrolled students. Idempotent
  via the `(class_test_id, user_id)` unique index + `insertOrIgnore`.
- `FlashcardSeeder` — three hand-crafted CS decks for the demo student, plus one
  generic deck for a sample of bulk students (`flashcard_students`).
- `LeaderboardSeeder` — opts the demo student + a share (`leaderboard_opt_in_rate`)
  of bulk students in; a third get a playful alias. XP itself is computed at read
  time from class-test / attendance data, so nothing is materialised.
- `DiscussionSeeder` — posts/comments/likes across the demo student's sections first,
  then other current-term sections (`discussion_sections` × `posts_per_section`),
  plus three open `post_reports` for the admin moderation queue.
- `DemoFeatureShowcaseSeeder` — backfills demo data so the three demo accounts
  (`student@`, faculty, `admin@`) can showcase every feature end-to-end.
  **Expanded (2026-07-16 wave)** to cover the recent feature waves (study planner,
  practice quizzes, peer review, course feedback, question bank, office hours,
  proctoring, agentic chat, etc.), keeping the demo logins populated rather than empty.
- `VisitSeeder` (new, 2026-07-17) — seeds ~380 demo page-visit rows for the new
  Admin → User Activity panel: authenticated visits for students/faculty/admins on
  their own pages, plus ~120 anonymous landing/login hits from external referrers
  (Google, Facebook, LinkedIn, …), spread over the last ~2 weeks with varied devices
  (desktop/mobile/tablet), browsers and locations (country/city). Writes to `visits`
  (`App\Models\Visit`). Idempotent: skips when the table already holds ≥50 rows, so it
  populates a fresh install but never piles onto real tracked traffic. Registered
  **last** in `DatabaseSeeder`, after `DemoFeatureShowcaseSeeder`.

## Dependency graph / insert order (`DatabaseSeeder`)

```
1.  RBACSeeder          roles → permissions → role-perm map → departments → demo users
2.  TermSeeder          spring-2026 (past), summer-2026 (CURRENT, reg open), fall-2026 (future)
3.  AcademicSeeder      demo student rich fixture (finds terms via firstOrCreate)
4.  CourseSeeder        CSE exact curriculum + curated catalogs per department
5.  FacultySeeder       ~80 faculty across departments (+ designations in bio)
6.  AdminSeeder         ~10 admins
7.  StudentSeeder       ~500 students, weighted across departments & semesters
8.  SectionSeeder       sections sized to student demand; guarantees ≥1 per course
9.  EnrollmentSeeder    enroll each student into their dept+semester current-term sections
10. CourseMaterialSeeder / ExamSeeder                    per section (all section_id-safe)
11. ClassTestSeeder / ClassTestAttemptSeeder             tests, then submitted attempts
12. AttendanceSeeder                                     per section
13. NoteSeeder / TaskSeeder                              per bulk student
14. FlashcardSeeder / LeaderboardSeeder / DiscussionSeeder  engagement / new-feature data
15. KnowledgeBaseSeeder RAG documents (kept)
16. DemoFeatureShowcaseSeeder  backfills every feature wave onto the 3 demo accounts
17. VisitSeeder          ~380 page-visit rows for Admin → User Activity (LAST; ≥50-row skip guard)
```

Child records never precede parents: terms → courses → faculty → sections →
students → enrollments → attendance/materials/exams → tests → attempts →
notes/tasks → flashcards/leaderboard/discussions → demo showcase → visits.

## Volume (config/seeder.php, env-overridable)

| Constant | Default |
|---|---|
| `students` | 500 |
| `faculty` | 80 |
| `admins` | 10 |
| `section_capacity` | 45 |
| `attendance_sessions` | 6 |
| `materials_per_section` | 3 |
| `exams_per_section` | 3 |
| `notes_per_student` | 2 |
| `tasks_per_student` | 3 |
| `max_courses_per_student` | 6 |
| `class_test_attempt_rate` | 70 (%) |
| `flashcard_students` | 60 |
| `leaderboard_opt_in_rate` | 60 (%) |
| `discussion_sections` | 40 |
| `posts_per_section` | 4 |

Set e.g. `SEED_STUDENTS=2000` to scale up without code changes.

## Risks & mitigations

- **Section_id invariant** → all bulk inserts set `section_id` explicitly; the
  `BelongsToSection` boot hook is a backstop, not relied on.
- **Capacity overflow** when a department+semester bucket is large → `SectionSeeder`
  creates `ceil(bucket / capacity)` sections per course; `EnrollmentSeeder`
  fills section by section.
- **Code collisions** → bulk catalog codes are department-prefixed and avoid the
  CSE curriculum's `MATH/PHY` prefixes (Mathematics dept uses `MAT`, Physics
  `PHYS`) and the demo's `CS3xx`.
- **Bcrypt cost** at 590 users → one shared hash is computed once and reused for
  all bulk accounts (mirrors `UserFactory`).
- **Performance** → high-volume tables (course_user, attendance, materials,
  exams) use chunked query-builder inserts; low-volume use Eloquent for casts.
- **Re-run safety** → each bulk seeder no-ops if its data already exists.
```
