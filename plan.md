# UniGPT — Follow-up Work Plan (historical work log)

> **Status (2026-06-18): largely complete — kept as a point-in-time record.**
> Task 2 (Vitest component tests) and Task 3 (faculty/student Form Requests + policies +
> eager-loading) are **done**. Task 1 (mock-data cleanup) is **done for the Faculty panel**
> (see [faculty-panel-audit-report.md](faculty-panel-audit-report.md)); a few residual admin
> display values remain — tracked under "Incomplete tasks" in
> [PROJECT_STATUS.md](PROJECT_STATUS.md). The live roadmap is PROJECT_STATUS.md, not this file.

Three remaining tasks, to be executed **one at a time, with approval between each**.
Each task is broken into phases. After every phase: run `./vendor/bin/pint`,
`php artisan test`, and `npm run build`, then stop for review.

Branch suggestion: work on `feature/platform-rbac-completion` (or a fresh branch
off it). Make small, feature-scoped commits — one per phase where practical.

---

## Task 1 — Remove remaining decorative / mock data

**Goal:** every screen renders only real database-backed data. Strip leftover
hardcoded arrays that survived the initial build, replacing them with real props
or removing the widget if there is no real data source yet.

**Known offenders (confirmed):**
- `resources/js/pages/Student/Roadmap.vue` → `skillsData` (line ~96), `careerPathways` (line ~131)
- `resources/js/pages/Faculty/Dashboard.vue` → `researchProjects` (line ~248), `researchProgress` (computed, ~309)

### Phase 1.1 — Audit
- `grep -rnE "ref\(\[|= \[\{" resources/js/pages` and list every hardcoded array.
- For each: classify as **(a) real data exists** → wire to a prop, or
  **(b) no data source** → remove the widget, or **(c) static UI labels** → keep
  (e.g. filter option lists are legitimately static).
- Produce the per-file decision list; stop for approval.

### Phase 1.2 — Student/Roadmap.vue
- If skills/career data should be real: add a backend source (e.g. derive skills
  from enrolled course tags; career pathways from a small seeded table or config)
  and pass via `StudentDashboardController::roadmap()`.
- Otherwise remove the `skillsData` / `careerPathways` sections and their template.
- Verify Roadmap still renders with real semester/credits/CGPA data.

### Phase 1.3 — Faculty/Dashboard.vue
- Replace `researchProjects` / `researchProgress` with real data, or remove the
  research widget. Faculty dashboard should show only real courses/students/grading.
- Wire any retained widget through `FacultyDashboardController`.

### Phase 1.4 — Sweep remaining pages
- Apply the Phase 1.1 decisions to any other flagged files.
- Final: no `ref([...])` of fake domain data remains; `npm run build` clean.

---

## Task 2 — Add Vue component tests

**Goal:** lock in the Phase 4 permission/UI behaviour with frontend tests so
regressions are caught automatically.

**Setup note:** this repo has no JS test runner yet. Phase 2.1 adds one.

### Phase 2.1 — Tooling
- Add **Vitest** + `@vue/test-utils` + `jsdom` as dev dependencies.
- Add `"test:js": "vitest run"` (and `"test:js:watch": "vitest"`) to `package.json`.
- Minimal `vitest.config.js` with the `@` alias mirroring `vite.config.js`, and a
  `usePage`/`route()` stub helper so components mount in isolation.
- Commit tooling separately; stop for approval before writing tests.

### Phase 2.2 — `usePermissions` composable
- Test `can` / `canAny` / `hasRole` / `primaryRole` against a stubbed `usePage`
  returning a fixed `auth.user`.

### Phase 2.3 — `AppLayout` navigation
- Mount with each role and assert the nav renders exactly the permitted links
  (student vs faculty vs admin), and that a permission absence hides its link.
- Assert the student-only user-menu items are hidden for faculty/admin.

### Phase 2.4 — Matrix editor (`RolePermissions.vue`)
- Assert checkboxes reflect `permissionIds`, the admin column is locked/disabled,
  toggling marks a role dirty, and Save posts the expected payload (mock `router.patch`).

### Phase 2.5 — Button gating
- `UserManagement.vue`: "Add User"/edit/deactivate hidden without `create_user`/`update_user`.
- `Admin/Dashboard.vue`: approve/reject hidden without `approve_document`.

---

## Task 3 — Harden faculty & student endpoints

**Goal:** bring faculty and student controllers up to the same standard already
applied to admin — Form Requests, policies, eager-loading, and verified DB
interaction. Preserve all behaviour.

### Phase 3.1 — Audit
- Review every method in `Student/StudentDashboardController`, `Student/ChatController`,
  `Student/SavedAnswerController`, `Faculty/FacultyDashboardController`,
  `Faculty/AIAssistantController`, `Faculty/CourseController`, `Faculty/GradingController`.
- Flag: inline `$request->validate()` calls, manual `abort_unless` ownership checks
  that should be policies, and queries with N+1 risk (missing `with()`).
- Produce the findings list; stop for approval.

### Phase 3.2 — Form Requests
- Extract inline validation into `app/Http/Requests/{Student,Faculty}/…` requests,
  each with a permission-aware `authorize()`. Wire them in, remove inline rules.

### Phase 3.3 — Policies
- Replace ad-hoc ownership checks (e.g. chat session / saved answer ownership) with
  policies (`ChatSessionPolicy`, `SavedAnswerPolicy`) via `Gate::authorize`.
- Confirm `CoursePolicy` covers all faculty course/grading actions.

### Phase 3.4 — Eager-loading & query correctness
- Add `with()` to flagged queries; verify no N+1 (e.g. via query count assertions
  in a test, or `DB::listen` during manual smoke).
- Confirm every column referenced exists; `migrate:fresh --seed` stays clean.

### Phase 3.5 — Tests
- Extend `StudentRoleTest` / `FacultyRoleTest` with the new validation/policy paths
  (e.g. a student cannot open another student's chat session → 403).

---

## Global verification (run after each task)

```bash
./vendor/bin/pint
php artisan test
npm run build
php artisan migrate:fresh --seed   # schema + demo data stay clean
```
