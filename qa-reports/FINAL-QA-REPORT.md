# UniGPT — Final QA Report

**Tester:** SQA Agent (Playwright MCP, real browser)
**Build:** branch `dev`, served at `http://127.0.0.1:8001`
**Date:** 2026-06-23
**Method:** Live end-to-end exercise of every role-reachable page with positive, negative and edge tests. Forms were actually submitted; state-changing flows were verified and, where practical, reverted.

Demo accounts (password `demo123`): `student@university.edu`, `prof.smith@university.edu`, `admin@university.edu`.

---

## 1. Executive Summary

| Metric | Result |
|---|---|
| Roles tested | Student, Faculty, Admin (+ public/auth) |
| Pages / routes exercised | ~45 GET pages + ~20 write flows |
| Console JS errors observed | **0** across all roles |
| Core workflows verified end-to-end | Login, course register/drop, assignment submit, proctored class-test take→grade, AI chat (RAG), faculty grading, AI quiz generation, user CRUD, department CRUD |
| Bugs found | 2 Major, 6 Minor, plus several data-consistency observations |
| Overall verdict | **PASS (with defects)** — the product is feature-complete and stable end-to-end; defects are polish/correctness issues, none blocking. |

**Headline finding (positive):** The AI copilot is fully functional — student chat returns correct, RAG-cited answers; faculty AI assistant and AI quiz generation produce correct content. This contradicts the repo docs (`CLAUDE.md`) which describe AI/RAG as "mostly unimplemented scaffolding." OpenAI (`gpt-4o`) is configured and reachable (verified via Admin → Settings → Test Connection).

---

## 2. Bug List

### 🔴 Major

**BUG-1 — Destructive "Delete user" action is mislabeled "Deactivate user"**
- Role: Admin · Page: `/admin/users`
- The row trash-icon button has accessible name **"Deactivate user"**, but clicking it opens a dialog titled **"Delete user — This will permanently delete the user account. This action cannot be undone."**
- Impact: Misleading label on an irreversible destructive action; accessibility/safety risk. A real "deactivate" should toggle status (a separate `admin.users.toggle-active` route exists, but the UI does not expose it on the row).
- Expected: trash button labeled "Delete user"; provide a distinct "Deactivate" control for the toggle-active behavior.
- Repro: Admin → Users → hover any row's trash icon (aria-label "Deactivate user") → click → observe "Delete user" permanent-delete dialog.
- **Source (confirmed):** `resources/js/pages/Admin/UserManagement.vue:491-498` — `<button @click="deleteUser(user.id)" aria-label="Deactivate user">`. Dialog title is "Delete user" (line 246).
- **Secondary defect here:** the delete button is gated `v-if="can('update_user')"` (line 492), i.e. a permanent delete is exposed to anyone with **update** permission, not the dedicated `delete_user` permission. Authorization is too loose.

**BUG-2 — `/faculty/courses` renders the Faculty Dashboard instead of a courses page**
- Role: Faculty · Page: `/faculty/courses` (sidebar "Courses")
- The page title is "Faculty Dashboard", it re-renders the dashboard's "Active Courses / Quick Actions / Recent Activity" layout, and the greeting renders as **"Good evening,"** with an empty name (dashboard props not supplied).
- Impact: The "Courses" nav goes to a duplicate dashboard, not a dedicated course-management index; the empty greeting looks broken.
- Expected: a distinct courses index, or at least correct title/props.
- **Source (confirmed):** `app/Http/Controllers/Faculty/CourseController.php:25` — `index()` returns `Inertia::render('Faculty/Dashboard', …)` instead of a courses page component.

### 🟠 Minor

**BUG-3 — AI chat responses don't render Markdown/LaTeX**
- Role: Student · Page: `/chat`
- AI answers show raw `### Headers`, ` ``` ` code fences, and `\(O(\log n)\)` LaTeX as literal text. Bold (`**`) and bullets render, but headings, code blocks and math do not.
- Impact: Degraded readability for a chat-first product, especially for code/math answers.

**BUG-4 — Admin dashboard prints `[object Object]`**
- Role: Admin · Page: `/admin/dashboard`
- Subheader reads: `Good Evening, System Administrator • [object Object]` — an un-stringified JS object (likely the active term) leaked to the UI.

**BUG-5 — Date off-by-one across views (timezone rounding)**
- Role: Student · Pages: `/dashboard`, `/assignments`, `/assignments/{id}`
- Same assignment shows **2026-06-29** on the dashboard, **Due Jun 30, 2026** on the list, and **Due Jul 13, 2026 9:37 PM** on the detail (vs **Jul 14** on the list). Display/timezone normalization is inconsistent between components.

**BUG-6 — Main faculty Grading page is stuck on one course with no course selector**
- Role: Faculty · Page: `/faculty/grading`
- The sidebar "Grading" link opens a page hard-bound to the first course (CS201, 0 assignments → "0 pending"), while 6–7 submissions are pending in other courses. There is no course/section switcher, so gradeable work is unreachable from the main nav. (Per-course grading at `/faculty/courses/{id}/grading` works correctly and was used to grade successfully.)

**BUG-7 — Admin "New This Week" equals Total Users**
- Role: Admin · Page: `/admin/dashboard`, `/admin/analytics`
- "New This Week" = **594** = Total Users. The weekly-new metric appears to count all users (seed `created_at` recent) rather than a real 7-day window.

**BUG-8 — System health status inconsistent between pages**
- Admin dashboard shows **"All Systems Operational / healthy"**; `/admin/monitor` shows **"Degraded"** (disk at 88%). The two health summaries disagree.

### 🔵 Data-consistency observations (not user-facing defects, likely seed data)

- **Dropped course still on transcript:** after dropping CS330 on `/register`, it still appears on `/transcript` (Sem 5, grade "IP"). Transcript and registration read different sources.
- **Cross-department teaching:** Prof. Smith (Computer Science Engineering) is assigned MATH 1101 / MATH 2204 on the faculty dashboard — appears to violate the documented "faculty teach only own-dept courses" invariant.
- **Student count mismatch:** Faculty dashboard "Total Students = 80" vs Faculty analytics "Students = 84" (different aggregation).
- **Dashboard progress mismatch:** Student dashboard "Current Progress 65%" sits directly above "0 of 3 topics completed"; roadmap reports 71% overall / 49% semester. The dashboard 65% looks hardcoded/disconnected from the "0 of 3" count.
- **Class-test seed content** uses placeholder text ("Interpretation A/B/C/D of …"); not a bug, but unrealistic.

---

## 3. Coverage by Role

### Public / Auth — PASS
- `/login`: role selector + demo buttons + email/password. **Negative:** wrong password → toast "These credentials do not match our records." **Positive:** demo login routes each role to its dashboard. Light/dark toggle present.
- Access control verified: a logged-in Student hitting `/admin/dashboard` and `/faculty/dashboard` is redirected to `/dashboard` (no data leak, no error).

### Student — PASS
| Page | Result | Notes |
|---|---|---|
| /dashboard | PASS | stats, deadlines, learning path (see BUG-5, progress mismatch) |
| /register | PASS | Register + Drop (with confirm dialog) both verified, state restored |
| /roadmap | PASS | semesters, timeline/grid, AI recommendations, PDF export |
| /transcript | PASS | per-semester tables (see dropped-course observation) |
| /materials | PASS | week/type filters; Open/Download disabled when no file attached |
| /assignments + /assignments/{id} | PASS | **empty-submit validation** + **valid submission** verified → "Submitted/Resubmit" |
| /class-tests + take + result | PASS | full proctored flow: rules gate → fullscreen take → timer → auto-grade (1/5, 20%) → answer review |
| /attendance | PASS | overall 90%, per-course tables |
| /exams | PASS | course filter, upcoming/past |
| /chat | PASS | **AI works** with RAG citation (see BUG-3 markdown) |
| /saved | PASS | empty state |
| /my-faculty | PASS | search + course filter, Message links |
| /messages | PASS | **message send verified**, thread updates live |
| /calendar | PASS | month nav, today highlighted, legend |
| /tasks | PASS | **create + delete (confirm)** verified, state restored |
| /notes | PASS | create form + list |
| /documents | PASS | categories, search, Preview/Download/Ask-AI |
| /profile | PASS | profile update submitted |
| /settings | PASS | appearance, notifications, language |
| /notifications | PASS | list + delete buttons |

### Faculty — PASS (with BUG-2, BUG-6)
| Page | Result | Notes |
|---|---|---|
| /faculty/dashboard | PASS | 15 courses, 80 students, stats |
| /faculty/courses | **FAIL (BUG-2)** | renders dashboard, empty greeting |
| /faculty/courses/{id} | PASS | Overview/Students/Materials/Assignments tabs |
| /faculty/courses/{id}/grading | PASS | **grade submitted** via quick-grade A- + feedback → 100% graded, avg 90% |
| /faculty/grading | PARTIAL (BUG-6) | no course selector, stuck on CS201 |
| /faculty/ai-assistant | PASS | **AI teaching answer verified** |
| /faculty/class-tests/create | PASS | **AI question generation verified** (5 correct MCQs with right answers) |
| /faculty/students | PASS | 80 students, search + 4 filters, Message |
| /faculty/messages | PASS | student list + conversations, unread badge |
| /faculty/analytics | PASS | course filter, attendance/grading stats |
| /faculty/exams | PASS | exam table |

### Admin — PASS (with BUG-1, BUG-4, BUG-7, BUG-8)
| Page | Result | Notes |
|---|---|---|
| /admin/dashboard | PASS | (BUG-4 `[object Object]`, BUG-7 metric, BUG-8 health) |
| /admin/users | PASS | 594 users, pagination, filters; **create + validation + delete verified** (BUG-1 label) |
| /admin/departments | PASS | **create + delete (confirm) verified**, restored |
| /admin/roles | PASS | 42-permission × 3-role matrix; Administrator locked |
| /admin/courses | PASS | 250 courses, New course, filters |
| /admin/terms | PASS | term cards + rollover |
| /admin/documents + /upload | PASS | library + upload form |
| /admin/approvals | PASS | status filters (10 approved, 0 pending) |
| /admin/exams | PASS | 741 exams, pagination |
| /admin/analytics | PASS | stats + charts (BUG-7) |
| /admin/ai-usage | PASS | per-user usage, block/unblock |
| /admin/announcements | PASS | compose form verified; **send not executed** (mass-broadcast, intentionally not triggered) |
| /admin/settings | PASS | provider/RAG config; **Test Connection → "Provider reachable"** |
| /admin/monitor | PASS | CPU/mem/disk/services (BUG-8) |

---

## 4. Test Data Created (for cleanup awareness)

Most mutations were reverted in-test. The following demo-data artifacts remain and can be cleared with `php artisan migrate:fresh --seed`:
- Student: 1 assignment submission (Midterm Project #2), 1 messenger message to Prof. Smith, 1 class-test result (Quiz 1 CS301), profile bio = "QA test bio update".
- Faculty: 1 grade recorded (Demo Student, Assignment 1, 90/100), AI chat sessions (student + faculty).
- Reverted: registered/dropped CS330, created/deleted task, created/deleted user "QA Test User", created/deleted "QA Test Department".

---

## 5. Recommendations (priority order)

1. **BUG-1** — relabel the user delete button; expose a real deactivate toggle. (safety/accessibility)
2. **BUG-2** — fix the `/faculty/courses` route to render a real courses page with correct props.
3. **BUG-6** — add a course/section selector to the main `/faculty/grading` page.
4. **BUG-3** — render Markdown + LaTeX in AI chat responses.
5. **BUG-4** — stringify the term/date on the admin dashboard.
6. **BUG-5 / BUG-7 / BUG-8** — normalize date/timezone formatting and reconcile metric/health calculations across pages.
7. Update `CLAUDE.md`: AI/RAG is implemented and live (OpenAI gpt-4o), not scaffolding.

---

## 6. Fixes Applied (2026-06-23)

All recommendations actioned, verified live in the browser, `pint` clean, `npm run build` green.

| Bug | Fix | Files | Verified |
|---|---|---|---|
| BUG-1 | Trash button relabeled "Delete user" + gated on `delete_user`; added a separate Activate/Deactivate toggle (`toggle-active`) | `resources/js/pages/Admin/UserManagement.vue` | Rows now show Edit / Deactivate / Delete |
| BUG-2 | `index()` now renders a dedicated `Faculty/Courses` page | `app/Http/Controllers/Faculty/CourseController.php`, new `resources/js/pages/Faculty/Courses.vue` | Title "My Courses", course grid, no empty greeting |
| BUG-3 | New shared `renderMarkdown()` (headings, fenced/inline code, lists, bold/italic, `\( \)`/`\[ \]`/`$…$` LaTeX, HTML-escaped) used by both chat surfaces | new `resources/js/lib/markdown.js`, `Student/Chat.vue`, `Faculty/AIAssistant.vue` | `###`→`<h*>`, ```` ``` ````→`<pre>`, no raw delimiters |
| BUG-4 | Render `department?.name` instead of the object | `resources/js/pages/Admin/Dashboard.vue` | "… • Computer Science Engineering", no `[object Object]` |
| BUG-5 | Assignment list uses the server `dueLabel` (app tz) instead of re-parsing ISO in the browser | `resources/js/pages/Student/Assignments.vue` | List "Jul 13" now matches dashboard/detail |
| BUG-6 | Added a Course selector (15 courses) that deep-links to per-course grading | `resources/js/pages/Faculty/Grading.vue` | Selecting CS301 → `/faculty/courses/1/grading` |
| BUG-8 | Dashboard health now derived from DB + disk (same ≥85% threshold as the monitor) | `app/Http/Controllers/Admin/AdminDashboardController.php`, `Admin/Dashboard.vue` | Dashboard now shows "Degraded", matching the monitor |
| BUG-7 | **No code change** — `countNewRegistrations(7)` is correct; 594 is a seed artifact (all users seeded today). Documented only. | — | query verified correct |

Doc: `CLAUDE.md` updated — AI/RAG marked implemented/live (OpenAI gpt-4o), not scaffolding.
