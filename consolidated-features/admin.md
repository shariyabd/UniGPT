# Admin Portal

The Admin Portal is the platform-owner control surface: a single administrator role holds full system access to own the course catalog and academic calendar, manage users and their permissions, curate the AI knowledge base and its approval workflow, moderate community and broadcast to audiences, oversee the marketplace, and govern platform-wide policies (AI backend, email, exam proctoring, demo mode, maintenance). The left sidebar organizes these into logical groups — Dashboard, People & Access, Academic, and System — with each item shown only when the admin's role holds the matching permission. This catalog covers the admin-only screens and actions; analytics dashboards, the AI capabilities themselves, the discussion/messaging systems, and the monetization/platform engines are owned by their respective scope files and are pointed to at the end.

## Dashboard

### Admin Dashboard
The landing page giving an at-a-glance picture of the whole platform. It shows four headline stat tiles (Total Users with active-user count, Active Now / online in the last 15 minutes, Pending documents, New registrations this week), a quick-action grid linking to User Management, Document Library, System Analytics and AI Configuration (each with a live count badge), and a full statistics block with trend indicators. It is read-only aside from inline approve/reject shortcuts on the Pending Approvals preview.

### System Health Card
An overall Healthy / Degraded / Down badge derived from database reachability and disk usage, plus database status, AI-provider status, and indexed-versus-pending document counts.

### Student Insights Card
A summary of the student body: total students, students enrolled this term, an overall attendance-rate percentage with progress bar, faculty count, and the current term name.

### Pending Approvals Preview
A preview of the most recent documents awaiting review, each with inline Approve/Reject buttons so the admin can clear the queue without leaving the dashboard.

### Recent Activities Feed
A feed of the latest system activity-log entries, giving a running picture of who did what across the platform.

## Knowledge Base

### Document Upload
A dedicated page for adding files to the AI knowledge base via a drag-and-drop zone plus file browser (PDF/DOC/DOCX/TXT/PPT/PPTX, multi-file, up to 50MB each with client-side validation). The document-information form captures a required Category, a Priority, a Departments multi-select (including "All Departments" for university-wide scope), a required Visibility multi-select (Students / Faculty / Admin Only, or All), a Description, and a removable-chip Tags input. Each file shows a live upload progress bar; new uploads are queued for the approval workflow rather than published immediately. A sidebar shows upload guidelines, recent uploads, and upload statistics (Today, This Week, Pending Review, Storage Used).

### Document Library
A browsable, searchable catalog of all knowledge-base documents shown as either a card grid or a table (view toggle). Each document exposes its file-type, title/description, category and status chips, tags, uploader, date, size, and view/download counts.

### Document Search & Filters
Within the library, free-text search by title/author/tags plus filters for Status, Category, Department, File Type, and Sort order, with a "Clear All Filters" action. The library is server-paginated (25 per page, showing the current range of the total).

### Document Preview
Renderable files (PDFs, text) open inline in the browser via a preview modal without downloading; Office files fall back to open-in-new-tab or download. Opening a preview records a view event.

### Document Download
Any document can be downloaded as a file attachment; each download is recorded and reflected in the document's download count.

### Document Bookmark
An optimistic toggle to bookmark or un-bookmark any document, keeping a personal shortlist of frequently used files.

### Document Share
A share action copies a direct link to the document to the clipboard for quick distribution.

### Document Delete
Permanently deletes a document from the library, behind a confirmation dialog.

## Approval Workflow

### Approvals Queue
A moderation queue listing documents by review status (defaults to Pending). Each entry shows title, description, department scope (or "All Departments"), category, upload date, file size/type, status, tags, version, and full uploader details (name, email, role, avatar). It supports a debounced server-side search and a Status filter that displays per-status counts (All / Pending / Processing / Approved / Rejected), and is paginated.

### Approve Document
Approves a document (with an optional comment) and triggers its indexing into the RAG knowledge base so it becomes retrievable by the AI; the confirmation notes the document is being indexed.

### Reject Document
Rejects a document with a required rejection reason (up to 1000 characters), recorded on the document's thread.

### Request Changes
Sends a document back to its uploader asking for revisions; a comment explaining the requested changes is required.

### Reviewer Comments
Admins add reviewer comments to a document. The expanded detail shows a color-coded Comments & Discussion thread (distinguishing rejection notes, change requests, and plain admin comments) alongside an Approval Timeline that merges the original submission with each subsequent review action.

## User & Access

### User Management
A full user-administration surface. The paginated list (25 per page) shows each user's name, email, avatar, primary role, department, semester, active/inactive status, whether the account is protected, last-login time, join date, and effective-permission count. Every mutating action is written to the admin activity log.

### User Search & Filters
A debounced search (name / email / department) plus Role and Status (active/inactive) filter dropdowns, with filters persisted in the URL and per-filter user statistics.

### Create User
Creates a new user with a chosen role and a hashed password; new accounts are active by default. Logged.

### Edit User
Updates a user's details (name, email, status, department, semester). The protected System Administrator account can never be deactivated. Logged.

### Toggle User Active
One-click activate/deactivate on any user, blocked for the protected admin account and logged with the resulting state. Inactive users are logged out and cannot sign in.

### Assign Role
Changes a user's role (synced to a single role). Blocked for the protected admin. Logged.

### Delete User
Permanently deletes a user behind a confirmation. Blocked for the protected admin and for deleting your own account; if the user has related records, deletion fails gracefully with a suggestion to deactivate instead. Logged.

### Bulk User Actions
When users are selected via row checkboxes (or select-all), a bulk-actions bar activates, deactivates, or changes the role of multiple users at once.

### Role–Permission Matrix Editor
A single matrix table with permission rows (grouped by category) against role columns; each cell is a grant/revoke checkbox. Column headers show each role's granted-permission count ("12 / 40" or "All"). Per-role Save buttons enable only on unsaved changes and replace the role's prior permission set on save. The admin role is locked — its cells are all-checked and disabled. This editor changes which permissions existing roles hold; it does not create or delete roles.

### Staff Roles, Permissions & Departments
Beyond the core three roles, the platform supports distinct staff roles with role-based permissions and specific access levels, and multiple departments of staff, so operational duties can be delegated with least-privilege access. (Sourced from SpaGreen's staff-role model, layered on the same matrix editor above.)

### User Approval & Login History
Admin controls to approve new user/registration accounts before they gain access and to review per-user login history for oversight. (Sourced from SpaGreen.)

### Block / Unblock AI Access
Admins can block a specific user from all AI features. The block modal requires a reason (shown to the user) and a duration (Permanent, 1, 7, or 30 days) — a positive duration sets an expiry, otherwise the block is permanent. Unblocking restores access; the protected primary administrator cannot be blocked. (The AI-usage monitoring dashboard this is launched from lives in analytics.)

## Academic Structure

### Course Catalog
The admin-owned course catalog. The paginated list shows each course with its enrolled-student roster (loaded per page). Supporting data includes departments, semester options, all faculty and students, all terms (current one marked), and the full course list for choosing prerequisites.

### Course Search & Filters
Courses searched by code/name (server-side) and filtered by Department and Semester, with a clear action and a results-count line.

### Create / Edit / Delete Course
Create a course (code, name, description, department, semester, credits) and edit it via a modal. A course delete is blocked while any students remain enrolled. Every create/update/delete is logged.

### Course Prerequisites
When creating or editing a course, admins pick prerequisite courses from a multi-select. A course can never be its own prerequisite (self-references filtered out). Prerequisites are later enforced during student registration.

### Sections / Offerings
Under each course, admins manage sections (offerings): creating one captures a label, term, assigned faculty, max enrollment, active state, and schedule/classroom/office-hours details. Sections can be edited or deleted, but a delete is blocked while enrolled students remain. Assignments and drops are logged.

### Assign Faculty to Section
Faculty are assigned through the section form when creating or editing it, so each offering has a responsible instructor (or shows as "Unassigned").

### Enroll / Drop Students (Roster Management)
A roster-management modal bulk-assigns one or more students to a section (capped at seats remaining) and removes existing ones. With capacity remaining, an assignment creates a pending placement that reserves a seat and notifies the student to confirm via Registration; non-students or already-enrolled students are skipped with a reason. Dropping a student removes and notifies them and triggers waitlist promotion. The result summarizes assigned / waitlisted / skipped counts.

### Section Waitlists
Assigning a student to a full section places them on a FIFO waitlist (notified they'll be auto-assigned when a seat opens) rather than dropping them. When a seat later frees up, the head of the waitlist is promoted automatically and notified.

### Academic Terms
Admin → Terms manages the academic calendar. It lists all terms as cards (name, Current badge, start/end dates, section count, active-enrollment count, registration open/closed badge) and shows term-level stats. The "New term" button appears only while fewer than the three standard terms (Fall/Spring/Summer) exist.

### Create / Delete Term
Create a term by picking one of the standard unused names and setting start/end dates. A term delete is blocked if it is the current term or still has sections.

### Set Current Term
Marks a term as the active/current term for the platform.

### Toggle Registration Window
A single toggle opens or closes student registration for a term, with a matching confirmation message.

### End-of-Term Rollover / Close
Closes a term and optionally rolls enrollments forward into a chosen next term (with an option to promote that term to current). It reports how many enrollments completed and how many sections were archived. Logged.

### Department Management
Admin → Departments manages academic departments. The paginated list shows each as a card with name, optional code, active/inactive status, description, and footer stats for user count and course count. Admins create and edit departments (slug auto-generated) and delete them — but a delete is blocked while any users or courses still reference the department. Create and update are logged.

### Exam / Timetable Management
Admin → Exams schedules and maintains the exam timetable. The paginated list shows each exam (course code, section, type, title, date/time/location). Supporting data includes courses with their sections, departments, section options, and exam types.

### Exam Search & Filters
Exams searched by text and filtered by Department, Course, Section, Type, and a From/To date range, with filters preserved in the URL and a clear action.

### Create / Edit / Delete Exam
Schedule an exam (course, one or many sections including "All Sections", title, type, date, start time, duration, location, total marks, instructions) via a modal; enrolled students are auto-notified. Exams can be edited or deleted with confirmation. Create/update are logged.

## Marketplace Administration

These are admin approval and processing actions for a multi-instructor marketplace model (sourced from Mentor/SpaGreen). The underlying monetization engine — commission math, payout transfers, gateways — lives in the commerce scope; the admin decision points live here.

### Instructor Application Approval
In marketplace mode, review incoming instructor applications from the admin panel and approve or reject them before granting dashboard access.

### Course Submission Review & Approval
Review courses submitted by instructors before they go public, approve or reject them, and monitor course quality and enrollment numbers.

### Feature Courses
Promote selected courses to featured status so they surface prominently in the storefront.

### Commission-Rate Configuration
Admins set the platform commission rate that governs revenue splits on each sale. (The revenue-split calculation and reporting itself is a commerce capability.)

### Instructor Payout Processing
Review instructor payout/withdrawal requests, approve them, and transfer funds through the configured payment method, retaining full control over platform cash flow. (Gateway execution and transaction ledger are commerce.)

### Content Moderation (Marketplace)
Admin oversight of course reviews, blog posts, and course-forum posts — review, edit, or remove content to maintain community standards. (The review/blog/forum systems themselves live in communication.)

### Newsletters & Email Campaigns
Send newsletters or targeted email campaigns directly to all students or instructors from the admin panel.

## Moderation & Broadcast

### Discussion Moderation Queue
Admin → Discussion Reports is the moderation queue for reported discussion posts and comments across all sections. It lists open reports (paginated), each showing whether it targets a post or a comment, the report reason, when and by whom it was reported, the section (course code + section), the content author, the content body, and a flag if the content was already removed elsewhere. (The discussion system itself is communication.)

### Resolve / Dismiss Report
Dismiss a report as reviewed with no action taken, recording who resolved it and when.

### Remove Reported Content
Remove the offending post or comment (soft-delete behind a confirmation), which also marks the report resolved. The remove action is hidden once the content has already been removed.

### Announcements / Broadcast
Admin → Announcements broadcasts an in-app notification to a chosen audience (Everyone, Students, Faculty, or Admins). The compose form captures a title (up to 150 chars) and message (up to 2000 chars); only active users receive it, and the result reports the recipient count. A "Recent announcements" panel lists recent broadcasts (title, message, audience, recipient count, relative time) with client-side pagination. Sends are logged.

### Edit Sent Announcement
An already-sent announcement's title and message can be edited, updating every recipient's copy at once. The audience is fixed once sent — only the content changes.

## System & AI Configuration

### AI Settings
Admin → Settings configures the AI backend. It shows the effective configuration — chat provider and model, temperature and max tokens, embedding model/provider and the model actually in effect, embedding fallback toggle and secondary provider, RAG top-K and similarity threshold, system-prompt override, supported languages, the chat-provider fallback toggle and primary choice, and the OpenRouter model chain. Provider options are OpenAI and Mock (offline). API keys are write-only and stored encrypted — the browser only ever sees whether each key (OpenAI / OpenRouter / Jina) is configured, never the key itself; a blank field keeps the current key and an explicit "remove" clears it.

### Provider Fallback Configuration
Within AI Settings, configure a resilient provider chain: an "Enable fallback" toggle, a primary-provider choice between OpenAI and OpenRouter (the other backs it up), a masked OpenRouter API key, and a model-chain textarea (with an `openrouter/auto` safety net always appended), keeping chat working if the primary provider fails.

### Embeddings Provider & Re-embed
Embeddings resolve separately from chat. Admins pick a primary embeddings provider (OpenAI, Jina, or Mock), supply a masked Jina key, and can enable a dual-index fallback with a secondary provider so RAG survives an embeddings-API outage. Because vectors are tagged by embedding model, changing the backend on save automatically re-indexes the corpus so old and new vectors don't cross-compare; if that synchronous re-index fails, a warning advises running the re-embed command manually.

### AI Test Connection
Test buttons perform real round-trips: Test OpenRouter does a minimal live chat call and reports the answering model; Test Embeddings does a minimal live embed and reports model plus vector dimensions; the default/active-provider test does an actual embed call (Mock reports "available" without a network call) so "reachable" means a verified round-trip, not just a stored key. Failures surface the error message.

### Email / SMTP Settings
Admin → Email Configuration configures outbound mail. It shows the effective config — mailer (SMTP or Log), host, port, encryption (TLS/SSL/None), username, from-address, from-name — with the SMTP password write-only and encrypted (browser sees only whether one is stored). Blank keeps the current password; "remove" clears it.

### Email Test Send
A "send a test email" input and button dispatches a plain test message to a chosen address to verify delivery; success reports the recipient and failure surfaces the error.

### Integration & Storage Setup (admin configures)
Admin-side configuration surfaces for platform capabilities owned elsewhere: storage backends (local / S3 / Wasabi / R2), Google OAuth login setup, and Zoom integration for live classes. Here the admin configures credentials and toggles; the underlying auth, storage, and live-class capabilities live in platform/learning-content scopes.

### Homepage, Theme & Branding Management (admin configures)
Admin-side configuration for the public storefront: homepage/page-builder layout and template selection, theme colors and fonts, branding (site name, logo, color system), localization/language management, custom CSS/JS injection, SEO settings, and the media gallery. These are admin config actions; the marketing/page-builder and localization capabilities themselves live in the platform scope.

### Exam Security Governance
Admin → Exam Security is the global gate deciding which exam-proctoring layers faculty may use and which are pre-enabled. It lists every proctoring layer from the registry, grouped by category (Lockdown, Question integrity, Monitoring & evidence, Media recording), each with two per-layer toggles: Available (whether faculty can offer it at all) and On by default (pre-ticked on new tests, only settable when Available). Camera/media layers carry a consent badge. Turning off availability hides a layer from faculty and stops it on existing tests. The page also shows and saves the default max-warnings-before-disqualification value.

### Exam Security "How Each Layer Works" Guide
A slide-in guide on the Exam Security page documenting the step-by-step behavior of every layer (fullscreen enforcement, tab-switch and clipboard detection, sequential/lock-back navigation, question shuffling, watermarking, integrity notices, fingerprinting, behavior logging, risk analysis, webcam, snapshot evidence, phone detection, screen recording, face-liveness blink-gate). All timings and thresholds are injected live from server configuration, so the docs always match shipped behavior; it closes by noting these signals are for human review, not automatic verdicts.

### Maintenance Mode Switch
A hidden, URL-driven switch to take the whole app offline. Adding `?live=false` to any URL drops every visitor into a Maintenance page (HTTP 503; JSON 503 for API callers); `?live=true` flips it back. State is persisted globally in cache so it sticks across all requests and visitors, and `?live=true` is evaluated first so an operator can never lock themselves out. The default before any toggle comes from configuration (live by default); the health-check endpoint and the test environment are never gated.

### Demo Mode Governance
When the deployment runs in demo mode, every account is capped at a fixed number of AI requests across all AI surfaces (student chat/agent, faculty assistant, AI generators). The cap is enforced only on request-consuming AI POST endpoints (never page loads or history reads), charged up-front regardless of provider success, and returns an HTTP 429 "demo limit reached" payload once exhausted. Outside demo mode nothing is metered — a deployment-level policy governing AI cost during demonstrations.

### System Settings & Maintenance
General system-settings screens plus operational maintenance actions (application updates, backups, and system upkeep) available to the platform owner. (Sourced from Mentor/suggested; deeper backup/audit-log infrastructure lives in the platform scope.)

## Shared systems referenced by admins
- **AI capabilities themselves** (RAG chat, agentic tools, AI generation/grading, embeddings, streaming) → see `ai-and-automation.md`. Admin here only configures and gates them.
- **Analytics & monitoring dashboards** — Platform Analytics, User Activity Tracking (stat cards, filters, retention pruning), AI Usage Monitor, System Monitor → see `analytics-and-reporting.md`. (Only the admin *action* Block/Unblock AI Access is documented here.)
- **Discussion, messaging, notifications & presence systems** — the discussion feed, messenger plumbing, notification center, heartbeat, reviews/blog → see `communication-community-and-engagement.md`. Admin here only moderates and broadcasts.
- **Monetization engine** — payment gateways, marketplace revenue split, subscriptions, coupons, wallet, invoices, transaction ledger → see `commerce-and-monetization.md`. Admin here only approves applications/submissions and processes payouts.
- **Platform infrastructure** — multi-tenancy/organizations, auth & SSO, storage capability, security/GDPR, page-builder/theme/localization engines, integrations, developer API, SaaS management, global search → see `platform-infrastructure-and-integrations.md`. Admin here only configures these.
- **Course/content & assessment engine** — content types, live classes, proctoring mechanics, certificates engine → see `learning-content-and-assessment.md`.
- **Role workflows** — day-to-day student and instructor experiences → see `student.md` and `instructor.md`.
