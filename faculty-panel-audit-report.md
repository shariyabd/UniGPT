# Faculty Panel — Audit & Functionalization Report

> **Status: completed audit — historical record.** All issues below were fixed and
> verified. For current system state see [PROJECT_STATUS.md](PROJECT_STATUS.md) and
> [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md).

Scope: every Faculty Panel route, page, component, button, link, and datum.
Method: route inventory → page-by-page deep audit → fix static/mock/dead items → verify.

## Per-page results

| ID | Route | Page | Issues found | Fix applied | Final status |
|----|-------|------|--------------|-------------|--------------|
| F1 | /faculty/dashboard | Dashboard | None — KPIs, courses, recent activity all from `FacultyDashboardController` (DB). Quick-action tiles are UI config pointing at real routes. | None needed | Verified |
| F2 | /faculty/courses | Dashboard (active courses) | None — `CourseService::facultyCourses` (DB). | None needed | Verified |
| F3 | /faculty/courses/create | CourseForm (create) | None — real `useForm` → `courses.store`; departments from DB. | None needed | Verified |
| F4 | /faculty/courses/{course} | CourseDetail | None — `CourseService::courseDetail`; tabs, material upload/delete, attendance & edit links, delete all wired to real routes. | None needed | Verified |
| F5 | /faculty/courses/{course}/edit | CourseForm (edit) | None — real `courses.update`. | None needed | Verified |
| F6 | /faculty/courses/{course}/attendance | Attendance | None — roster from `AttendanceService`; date reload + save post to real routes. | None needed | Verified |
| F7 | /faculty/ai-assistant | AIAssistant | **4 issues (see below)** | Topics, Export, Publish, Resources all fixed | Fixed |
| F8 | /faculty/exams | Exams | None — `ExamService::forFaculty` (DB). | None needed | Verified |
| F9 | /faculty/analytics | Analytics | None — `FacultyAnalyticsService`; course filter navigates to real route. | None needed | Verified |
| F10 | /faculty/courses/{course}/analytics | Analytics (scoped) | None | None needed | Verified |
| F11 | /faculty/grading | Grading | None — `GradingService::overview`; grade submit + AI feedback draft hit real routes. | None needed | Verified |
| F12 | /faculty/courses/{course}/grading | Grading (scoped) | None | None needed | Verified |

Sidebar/nav (`AppLayout.vue` faculty menu: Dashboard, Courses, Grading, Exams, Analytics,
AI Assistant) — every item maps to a real named route. No dead nav links.

## F7 — AI Assistant: issues and fixes

1. **Hardcoded topic catalog (mock data, broke functionality).**
   `courseTopics` was a static map keyed by three fake course names
   (`Database Systems`, `Machine Learning`, `Software Engineering`). The Quiz "Topic"
   dropdown and the Assignment "Topics to Cover" chips were driven by it, so for any
   real course the lists were **empty** — and because topic is required, quiz and
   assignment generation could not be used at all.
   - **Fix:** removed `courseTopics`/`availableTopics`. Quiz topic is now a free-text
     input; assignment topics use a free-text "type and add" field with removable chips.
     Works for every real course. → now **dynamic / functional**.

2. **`exportGenerated()` → `alert('Exporting…')` (dead button).**
   - **Fix:** real export — opens a formatted print document (quiz questions+answers, or
     assignment sections + rubric) and triggers the browser print dialog ("Save as PDF").
     No new dependency. → now **functional**.

3. **`publishContent()` → `alert('Publishing…')` (dead button).**
   - **Fix:** real DB write. New endpoint `POST /faculty/ai-assistant/publish`
     (`faculty.ai-assistant.publish`) persists the generated content as a published
     `Assignment` on the selected course (quiz → `type=quiz` with questions stored as text;
     assignment → mapped type, points, due date, rubric), authorized by the `manage` course
     gate, and notifies enrolled students (`NotificationType::ASSIGNMENT`). Published items
     then appear in CourseDetail → Assignments and the grading queue. → now **DB-integrated**.

4. **Fake "Recommended Resources" with dead `#` links + broken handler.**
   The generator injected four placeholder resources whose `url` was `'#'`, and the
   "View →" button called `window.open(...)` directly in the template (`window` is not
   exposed to Vue templates, so it errored).
   - **Fix:** stopped injecting placeholders — the section now renders only resources the
     generator actually returns (hidden otherwise). The View button uses a proper
     `openResource(url)` handler guarded against `#`. → no more dead links / broken handler.

## F7 + F11 — Runtime bugs found via live browser testing (round 2)

Loaded the app in a real (headless Chrome) faculty session and drove each feature. The page
*rendered* fine (chat input, tabs, buttons all present — "nothing renders" was a stale build),
but several actions failed silently at runtime. Fixed and re-verified live:

6. **Quiz Generator silently failed.** The UI sent `difficulty: beginner|intermediate|advanced`,
   but `GenerateQuizRequest` only accepts `easy|medium|hard` → **HTTP 422**, and `generateQuiz`
   had no `catch`, so the button just reset with no feedback.
   - **Fix:** difficulty option values changed to `easy|medium|hard`; form defaults updated;
     added error toasts to quiz **and** assignment generation. Verified: quiz now generates and
     previews. (Also corrected an invalid default `bloomLevel: 'application'` → `'apply'`.)

7. **Grade submission broken (HTTP 405).** `Grading.vue` sent `router.patch(...)` but the route
   `faculty.submissions.grade` is **POST** → 405 Method Not Allowed.
   - **Fix:** changed to `router.post`, added success/error toasts.

8. **Graded value didn't refresh in the UI.** `Grading.vue` copied props into local refs once at
   setup; after the post-grade Inertia reload the component isn't remounted, so the grade stayed
   stale until a hard refresh.
   - **Fix:** added `watch`ers to re-sync `assignments`/`submissions` from props. Verified live:
     re-grading to 91 updates the list immediately.

9. **Assignment preview body never rendered (duplicate object key).** The generated assignment
   object set `type` twice — `type: 'assignment'` then `type: assignmentForm.value.type` ('Project')
   — so the discriminator became `'Project'` and every `generatedContent.type === 'assignment'`
   block (Overview, Tasks, Rubric, Guidelines) was hidden.
   - **Fix:** renamed the category field to `category`, keeping `type: 'assignment'`. Verified live:
     full preview now shows Overview + tasks + rubric + guidelines; Publish + Export work.

Live end-to-end verification (authenticated faculty, headless Chrome): AI chat send/receive ✓,
Quiz generate+preview ✓, Assignment generate+preview ✓, Publish → "Published to CS301" toast +
DB row ✓, Grade submit → live update to 91 ✓. Zero console/page errors.

### Supporting changes (Rule 0 propagation)
- `app/Enums/NotificationType.php` — added `ASSIGNMENT` case + its `getIcon()` arm (string column, safe).
- `resources/js/pages/Notifications/Index.vue` — registered `DocumentTextIcon` for the new type.
- `app/Domain/Academic/Services/CourseManagementService.php` — added `publishAssignment()`.
- `app/Http/Requests/Faculty/PublishContentRequest.php` — new validated request.
- `app/Http/Controllers/Faculty/AIAssistantController.php` — added `publish()`.
- `routes/web.php` — registered the publish route.
- `tests/Feature/FacultyRoleTest.php` — added publish + authorization tests.

## Final summary

### Total pages checked
12 faculty routes (8 distinct Inertia pages + scoped variants).

### Total issues found
4 — all on the AI Assistant page:
- 1 mock-data section that broke generation (hardcoded topics)
- 2 dead action buttons (Export, Publish)
- 1 fake data section with dead links + a broken handler (Resources)

### Total fixes applied
- 1 DB integration (Publish → real `Assignment` + student notifications)
- 1 functional export (browser print-to-PDF)
- 2 mock-data sections made dynamic / removed (topics, resources)
- 1 broken handler repaired (resource View)

### Static data remaining
**Yes — one item, intentionally preserved:**

| Page | Section | Reason not converted |
|------|---------|----------------------|
| AI Assistant | Generated assignment "Submission Guidelines" (Format / Naming / Platform / Late Policy) | Generic, instructional boilerplate appended to AI output. No backend table, model, or business source exists to make it course-specific, so per the Critical Preservation Rule it was left untouched. It contains no data, links, or actions — purely static guidance text. |

Static UI configuration that is **not** data and was correctly left as-is: dashboard quick-action
tiles, the "Teaching Tip" card, and form option lists (difficulty, Bloom levels, assignment types,
question types) — all point to real routes/behaviour and are standard UI constants, not mock data.

## Verification
- `./vendor/bin/pint` — PASS (all touched files)
- `php artisan route:list --name=faculty.ai-assistant` — publish route registered
- `npm run build` — PASS (AIAssistant compiles)
- `php artisan test --filter=FacultyRoleTest|CourseManagementTest` — PASS (19 assertions across
  faculty pages, quiz/assignment generation, grading, publish; 1 graceful skip where the demo
  seed has a single faculty).
