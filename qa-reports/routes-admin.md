# Admin Routes

Sidebar groups: Overview, People, Academics, System. All require `role:admin`.

| URL | Page | How to reach | Status |
|---|---|---|---|
| /admin/dashboard | Admin Dashboard | Sidebar → Dashboard (post-login default) | PASS (BUG-4, BUG-7, BUG-8) |
| /admin/users | User Management | Sidebar → People → Users | PASS (create/validate/delete verified; BUG-1 label) |
| /admin/departments | Departments | Sidebar → People → Departments | PASS (create/delete verified) |
| /admin/roles | Roles & Permissions | Sidebar → People → Roles | PASS (42-perm matrix) |
| /admin/courses | Courses | Sidebar → Academics → Courses | PASS (250 courses) |
| /admin/terms | Academic Terms | Sidebar → Academics → Terms | PASS |
| /admin/documents | Document Library | Sidebar → Academics → Documents | PASS |
| /admin/documents/upload | Document Upload | Documents → Upload | PASS (form) |
| /admin/approvals | Approval Workflow | Sidebar → Academics → Approvals | PASS |
| /admin/exams | Exam Management | Sidebar → Academics → Exams | PASS (741 exams) |
| /admin/analytics | Analytics Dashboard | Sidebar → System → Analytics | PASS |
| /admin/ai-usage | AI Usage & Access | Sidebar → System → AI Usage | PASS |
| /admin/announcements | Announcements | Sidebar → System → Announcements | PASS (compose ok; send not triggered) |
| /admin/settings | AI Settings | Sidebar → System → Settings | PASS (Test Connection ok) |
| /admin/monitor | System Monitor | Sidebar → System → Monitor | PASS (BUG-8) |

Write endpoints exercised: user create (POST), user delete (DELETE), user deactivate→delete confirm, department create (POST), department delete (DELETE), AI provider test (POST settings/test).
