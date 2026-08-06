# Analytics & Reporting

This catalog consolidates every analytics, reporting, monitoring, and operational-logging feature across the six source products. The authoritative UniNexus experience (student/faculty/admin) contributes the deepest, most concrete surfaces — platform-wide analytics, user-activity tracking, AI-usage monitoring, live system-health probing, faculty academic reporting, and the early-warning at-risk engine. The two commercial LMS products (Mentor, SpaGreen) add instructor/results performance analytics and SaaS-style sales/progress reporting, while the wishlist adds a broad menu of generic reporting, log, and enterprise-reporting capabilities. Features that live elsewhere (the admin block/unblock action, the student My-Progress page, the monetization engine, AI capabilities, and enterprise structure) are cross-referenced with pointers rather than re-described.

---

## Platform Analytics

### Platform Analytics Overview (admin)
A read-only, platform-wide reporting dashboard with six overview stat cards — Total Users, Active Users, Total Queries, Documents, Chat Sessions, and Saved Answers — giving admins an instant snapshot of adoption and AI activity. A Refresh button reloads the live metrics.

### Queries by Department (admin)
A breakdown showing AI query volume per department, rendered as bars with percentages and counts so admins can see which departments lean most on the copilot.

### Most Asked Questions (admin)
A ranked list of the platform's top student queries with per-question ask counts, surfacing common information gaps and popular knowledge-base topics.

### Users by Role & At-a-Glance (admin)
A role-distribution view of the user base (students/faculty/admin) paired with an "At a Glance" summary block that rolls the headline platform metrics into a single quick-read panel.

### Admin & Instructor Dashboard Metrics (all products)
Overview dashboards across products surface headline analytics: UniNexus admin's system-health/student-insights/pending-approvals/recent-activity tiles; Mentor's revenue, enrollment stats, pending-payout queue and activity feed; SpaGreen's central management panel; and the wishlist's generic Admin, Tenant, and Manager dashboards. These are entry-point read-only snapshots; the deeper reports live in the sections below.

---

## Activity & Usage Monitoring

### User Activity Tracking Feed (admin)
Records meaningful page visits across the platform — who visited (or Guest), the external referrer they came from, the device used, and IP-derived geolocation — in a paginated feed with columns for user, page (friendly label + path), came-from referrer, device (type icon plus browser/platform), location (geo plus IP), and when (relative + exact timestamp).

### Activity Stat Cards & Breakdowns (admin)
The activity page surfaces four summary cards — Total Visits, Visits Today, Unique Visitors, and Guest Visits — plus three breakdown panels: Devices, Top Countries, and Top Pages.

### Activity Filters & Pagination (admin)
The visit feed narrows via a debounced user search and searchable Role, Device, and Country selects plus a From/To date range; filters persist in the URL across pagination.

### Visit Retention Pruning (admin)
Tracked visit records are automatically pruned on a retention schedule (default 90 days, configurable; 0 disables) so the activity log doesn't grow unbounded — a scheduled maintenance task, not a manual action.

### AI Usage Monitor (admin)
Tracks AI consumption per user and per request for cost control, with four summary cards (Total Tokens, Total Requests, Active Users, Blocked Users), a "Usage by User" table (name/email, role, request count, token total, last used, status badge), and a "Recent Requests" table (user, truncated query, tokens, model, timestamp). The Block/Unblock AI Access *action* triggered from this monitor is owned by the admin catalog.

### Usage Analytics & Real-time Progress (SaaS / commercial)
SpaGreen tracks real-time student progress and course-completion alongside its reporting suite; the wishlist adds SaaS Usage Analytics and Plan-Limit tracking for tenant-level consumption monitoring. These are consumption/progress dashboards distinct from the AI-token monitor above.

---

## System Health Monitoring

### System Monitor (admin)
A read-only live system-health dashboard showing CPU load (1/5/15-minute averages and core count), memory (current and peak MB), and storage (used percentage, free of total), with color-coded status badges.

### Services & Environment Probe (admin)
Within the System Monitor, a services panel actively probes Database, Queue (driver), Cache (via a write/read round-trip), and AI Provider (name/availability, checked without a billable request), plus an environment panel reporting PHP version, Laravel version, environment, and real system uptime. A manual Refresh and a 10-second auto-refresh toggle keep it current. (Maintenance Mode and health-check gating are governance features owned by the platform/admin catalog.)

---

## Teaching & Course Analytics

### Faculty Analytics & Academic Reporting (faculty)
A department/course analytics report for a faculty member's teaching, presenting the performance and engagement picture for their students. It can be viewed across all their courses or scoped to a single course, and is gated by the department-analytics permission.

### Instructor Results & Performance Analytics (commercial)
Mentor's instructor Results & Performance analytics analyze student performance and exam results — average scores, pass rates, and which questions students find hardest — and its Revenue Tracking reports course sales, earnings, and commission breakdowns. SpaGreen adds per-instructor performance analytics and tracking for their courses. The wishlist's generic Instructor Analytics and Revenue Dashboard sit alongside these. (The revenue *engine* itself is owned by the commerce catalog; this covers the *reporting* of it only.)

### Course & Quiz Outcome Reports (commercial / wishlist)
Aggregate outcome reporting on courses and assessments: Mentor/SpaGreen's exam-and-quiz performance analytics (average scores, pass rates, difficult-question identification) and the wishlist's Course Analytics, Quiz Reports, Completion Reports, and Engagement Reports.

### Student-facing Analytics (pointer)
The student-facing "My Progress / Learning Analytics" page and the adaptive Concept Mastery Map are owned by the student catalog — see it there.

---

## Early-Warning / At-Risk

### Early-Warning At-Risk Report (faculty)
Within faculty Analytics, the at-risk section lists the specific students flagged by the early-warning engine, the signals that triggered each flag (attendance below 75% after enough sessions, repeatedly missed assignment deadlines, low class-test averages, or poor grades), and a risk level (high vs. watch). Each flagged student carries a message deep-link so faculty can reach out directly.

### At-Risk Dashboard Count (pointer)
The faculty dashboard's At-Risk Students count stat (unique flagged students across a faculty member's sections) is owned by the faculty/instructor catalog; this report is its detailed counterpart.

---

## Reports & Exports

### Learning & Student Analytics (wishlist)
Generic analytics surfaces from the wishlist: Learning Analytics and Student Analytics — aggregate views of learner activity and performance across the platform.

### Revenue & Instructor Analytics (wishlist)
Revenue Analytics and Instructor Analytics reporting surfaces (the underlying commerce/payout engine is owned by the commerce catalog; these are the reporting views only).

### Completion & Engagement Reports (wishlist)
Completion Reports and Engagement Reports summarizing how far learners get and how actively they participate.

### Quiz, Export & Custom Reports (wishlist)
Quiz Reports for assessment outcomes, plus Export Reports (download report data) and Custom Reports (admin-defined report configurations).

### Sales Analytics & Enrollment History (commercial)
SpaGreen's sales-analytics charts, enrollment history, real-time student progress, and course-completion tracking, together with its advanced system reporting — the SaaS reporting bundle for a course-selling platform.

---

## Logs & Records

### Audit & Activity Logs (admin / wishlist)
Append-only records of system actions: UniNexus writes every mutating admin action (user/course/term/announcement changes) to an admin activity log surfaced in the dashboard's Recent Activities feed; the wishlist formalizes this as Audit Logs and Activity Logs.

### Login History & Security Logs (commercial / wishlist)
SpaGreen's user-approval and Login History review, plus the wishlist's Security Logs — records of authentication events and account access for oversight.

### Search Logs (commercial)
SpaGreen's instant-search suggestions paired with search-log analysis, giving admins insight into what users are looking for.

---

## Enterprise Reporting

### Organization Reports & Manager Dashboard (wishlist)
Enterprise-tier reporting surfaces: Organization Reports (roll-ups across an org/tenant) and a Manager Dashboard for team leads to monitor their people. (The org/department/team *structure* itself is owned by the platform/enterprise catalog; this covers the *reports* over it.)

### Skill Matrix & Competency Tracking Reporting (wishlist)
Reporting views over enterprise learning frameworks — Skill Matrix reporting and Competency Tracking reporting — showing skill coverage and competency progress across employees. (The skill/competency structures themselves are owned elsewhere; this is the reporting layer.)
