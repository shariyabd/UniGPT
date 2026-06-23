# Faculty Routes

Sidebar groups: Overview, Teaching, Connect, Insights, AI Copilot. All require `role:faculty`.

| URL | Page | How to reach | Status |
|---|---|---|---|
| /faculty/dashboard | Faculty Dashboard | Sidebar → Dashboard (post-login default) | PASS |
| /faculty/courses | Courses (index) | Sidebar → Teaching → Courses | **FAIL — renders dashboard (BUG-2)** |
| /faculty/courses/{id} | Course detail (tabs) | Click a course card | PASS |
| /faculty/courses/{id}/grading | Per-course grading | Course → grade flow | PASS (grade verified) |
| /faculty/courses/{id}/attendance | Attendance marking | Course → Attendance | PASS (loads) |
| /faculty/grading | Grading (index) | Sidebar → Teaching → Grading | PARTIAL — no course selector (BUG-6) |
| /faculty/class-tests | Class Tests list | Sidebar → Teaching → Class Tests | PASS |
| /faculty/class-tests/create | New class test (+AI gen) | "New class test" | PASS (AI gen verified) |
| /faculty/exams | Exams | Sidebar → Teaching → Exams | PASS |
| /faculty/students | My Students | Sidebar → Connect → My Students | PASS |
| /faculty/messages | Messages | Sidebar → Connect → Messages | PASS (send verified) |
| /faculty/analytics | Learning Analytics | Sidebar → Insights → Analytics | PASS |
| /faculty/ai-assistant | AI Teaching Assistant | Sidebar → AI Copilot | PASS (AI verified) |

Write endpoints exercised: submission grade + feedback (POST grade), AI question generation (POST generate), message send (POST messages).
