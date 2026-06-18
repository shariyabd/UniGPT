# Faculty Panel — Dynamic Verification Plan

> **Status: completed — historical record.** All 12 faculty routes were audited and
> verified; see [faculty-panel-audit-report.md](faculty-panel-audit-report.md) for results
> and [PROJECT_STATUS.md](PROJECT_STATUS.md) for current state.

> Note: `plan.md` already exists for an unrelated work plan, so this audit's
> inventory lives here to avoid overwriting it.

Goal: every Faculty Panel page, component, button, link and datum must be functional,
actionable and DB/API-driven. One route audited fully at a time.

## Route inventory (GET pages + their actions)

| ID | Method | URL | Page / Action | Access path | Status |
|----|--------|-----|---------------|-------------|--------|
| F1 | GET | /faculty/dashboard | Faculty/Dashboard | Sidebar → Dashboard | Completed |
| F2 | GET | /faculty/courses | Faculty/Dashboard (active courses) | Sidebar → Courses | Completed |
| F3 | GET | /faculty/courses/create | Faculty/CourseForm (create) | Dashboard → New Course | Completed |
| F4 | GET | /faculty/courses/{course} | Faculty/CourseDetail | Course card | Completed |
| F5 | GET | /faculty/courses/{course}/edit | Faculty/CourseForm (edit) | CourseDetail → Edit | Completed |
| F6 | GET | /faculty/courses/{course}/attendance | Faculty/Attendance | CourseDetail → Attendance | Completed |
| F7 | GET | /faculty/ai-assistant | Faculty/AIAssistant | Sidebar → AI Assistant | Completed |
| F8 | GET | /faculty/exams | Faculty/Exams | Sidebar → Exams | Completed |
| F9 | GET | /faculty/analytics | Faculty/Analytics | Sidebar → Analytics | Completed |
| F10 | GET | /faculty/courses/{course}/analytics | Faculty/Analytics (scoped) | Analytics course select | Completed |
| F11 | GET | /faculty/grading | Faculty/Grading | Sidebar → Grading | Completed |
| F12 | GET | /faculty/courses/{course}/grading | Faculty/Grading (scoped) | CourseDetail / link | Completed |

Action (non-GET) routes: courses.store/update/destroy, materials store/update/destroy/download,
attendance.store, ai-assistant.chat/quiz/assignment (+ publish — added), submissions.grade/feedback.

## Sidebar / nav (resources/js/Layouts/AppLayout.vue → `faculty` menu)
Dashboard, Courses, Grading, Exams, Analytics, AI Assistant — all map to real named routes. Verified OK.

## Findings summary
- F1–F6, F8–F12: fully dynamic, service/DB-backed. No mock data, no dead actions.
- **F7 (AI Assistant):** held the only static/mock data and dead buttons:
  1. `courseTopics` hardcoded map (3 fake course names) drove the Topic dropdowns →
     empty for every real course → quiz/assignment generation unusable. **Fixed** (free-text topics).
  2. `exportGenerated()` → `alert()`. **Fixed** (real browser print-to-PDF window).
  3. `publishContent()` → `alert()`. **Fixed** (persists a real Assignment to the course + notifies students).
  4. Hardcoded fake `resources[]` with dead `#` links + broken `window.open`. **Fixed**
     (placeholder removed; section renders only real backend data; View handler fixed).
  5. Static `submissionGuidelines` — generic boilerplate, no data source → **preserved** (reported).
