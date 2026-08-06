# UniNexus — Admin Portal Guide

UniNexus is an AI-powered university academic copilot with three role-based experiences (Student, Faculty, Admin). This document is the exhaustive reference for the **Admin Portal** — every capability available to an admin-role user. Admins hold full system access: they own the course catalog and academic calendar, manage users and their permissions, curate the AI knowledge base and its approval workflow, moderate community discussions, monitor platform-wide analytics and system health, configure the AI and email backends, and govern platform-wide policies such as exam-proctoring, demo mode, and maintenance. The admin left sidebar organizes these features into logical groups — an unlabeled top group (Dashboard), **People & Access**, **Academic**, and a **System** group — with each item shown only when the admin's role holds the matching permission.

---

## Dashboard & Overview

### Admin Dashboard
The landing page for admins, giving an at-a-glance picture of the whole platform. It shows four headline stat tiles (Total Users with active-user count, Active Now / online in the last 15 minutes, Pending documents, and New registrations this week), a quick-action grid linking to User Management, Document Library, System Analytics and AI Configuration (each with a live count badge), a full statistics block with trend indicators, a **System Health** card (overall Healthy/Degraded/Down badge derived from database reachability and disk usage, plus database status, AI provider and indexed-vs-pending document counts), a **Student Insights** card (total students, students enrolled this term, an overall attendance-rate percentage with progress bar, faculty count, and the current term name), a **Pending Approvals** preview of the most recent documents awaiting review with inline Approve/Reject buttons, and a **Recent Activities** feed of the latest system activity-log entries. It is read-only aside from the quick approve/reject shortcuts.

---

## Knowledge Base (Documents)

### Document Upload
A dedicated upload page where admins add files to the AI knowledge base. It offers a drag-and-drop zone plus a file browser (PDF/DOC/DOCX/TXT/PPT/PPTX, multi-file, up to 50MB each with client-side validation), a document-information form with a required Category, a Priority, a **Departments** multi-select (including "All Departments" for university-wide scope), a required **Visibility** multi-select (Students / Faculty / Admin Only, or All), a Description, and a removable-chip Tags input. Each selected file shows a live upload progress bar and status. New uploads are queued for the approval workflow rather than published immediately. The page sidebar shows upload guidelines, a Recent Uploads list, and upload statistics (Today, This Week, Pending Review, Storage Used).

### Document Library
A browsable, searchable catalog of all documents in the knowledge base, shown as either a grid of cards or a table (view toggle). Each document exposes its file-type, title/description, category and status chips, tags, uploader, date, size, and view/download counts.

### Document Search & Filters
Within the library, admins can free-text search by title/author/tags and apply filters for Status, Category, Department, File Type, and a Sort order, with a "Clear All Filters" action. The library is server-paginated (25 per page, showing the current range of the total).

### Document Preview
Renderable files (PDFs, text) open inline in the browser via a preview modal/iframe without downloading; Office files fall back to open-in-new-tab or download. Opening a preview records a view event on the document.

### Document Download
Any document can be downloaded as a file attachment; each download is recorded as a download event and reflected in the document's download count.

### Document Bookmark
Admins can bookmark or un-bookmark any document with an optimistic toggle, keeping a personal shortlist of frequently used files.

### Document Share
A share action copies a direct link to the document to the clipboard for quick distribution.

### Document Delete
Admins can permanently delete a document from the library (behind a confirmation dialog).

---

## Document Approval Workflow

### Approvals Queue
A moderation queue listing documents by review status (defaults to Pending). Each entry shows the title, description, department scope (or "All Departments"), category, upload date, file size and type, status, tags, version, and full uploader details (name, email, role, avatar). It supports a debounced server-side search and a Status filter that displays per-status counts (All / Pending / Processing / Approved / Rejected), and is paginated.

### Approve Document
Approves a document (with an optional comment) and triggers its indexing into the RAG knowledge base so it becomes retrievable by the AI. The confirmation notes the document is being indexed.

### Reject Document
Rejects a document; a rejection reason is required (up to 1000 characters) and is recorded on the document's thread.

### Request Changes
Sends a document back to its uploader asking for revisions; a comment explaining the requested changes is required.

### Reviewer Comments
Admins can add reviewer comments to a document. The document's expanded detail shows a color-coded **Comments & Discussion** thread (distinguishing rejection notes, change requests, and plain admin comments) alongside an **Approval Timeline** history that merges the original submission with each subsequent review action.

---

## Analytics & Monitoring

### Platform Analytics
A read-only, platform-wide reporting dashboard. It presents six overview stat cards (Total Users, Active Users, Total Queries, Documents, Chat Sessions, Saved Answers), a "Queries by Department" breakdown (per-department bars with percentages and counts), a "Most Asked Questions" ranked list of top queries with ask counts, and a "Users by Role" distribution, plus an "At a Glance" summary. A Refresh button reloads the live metrics.

### User Activity Tracking
Admin → User Activity records meaningful page visits across the platform — who visited (or Guest), from where (external referrer), on what device, and IP-derived location. The page shows a paginated feed with columns for user, page (friendly label + path), came-from referrer, device (type icon plus browser and platform), location (geo plus IP), and when (relative and exact timestamp).

### User Activity Stat Cards & Breakdowns
The User Activity page surfaces four summary cards — Total Visits, Visits Today, Unique Visitors, and Guest Visits — plus three breakdown panels: Devices, Top Countries, and Top Pages.

### User Activity Filters & Pagination
Admins can narrow the visit feed with a debounced user search and searchable Role, Device, and Country selects, plus a From/To date range. Filters persist in the URL across pagination, and the feed is paginated.

### Visit Retention Pruning
Tracked visit records are automatically pruned on a retention schedule (default 90 days, configurable; set to 0 to disable), so the activity log does not grow unbounded. This runs as a scheduled maintenance task rather than a manual action.

### AI Usage Monitor
Admin → AI Usage tracks AI consumption per user and per request for cost control. It shows four summary cards (Total Tokens, Total Requests, Active Users, Blocked Users), a "Usage by User" table (name/email, role, request count, token total, last used, and status badge), and a "Recent Requests" table (user, truncated query, tokens, model, timestamp).

### Block / Unblock AI Access
From the AI Usage monitor, admins can block a specific user from all AI features. A block modal requires a reason (shown to the user) and a duration (Permanent, 1, 7, or 30 days) — a positive duration sets an expiry, otherwise the block is permanent. Unblocking restores access. The protected primary administrator account cannot be blocked.

### System Monitor
A read-only live system-health dashboard (System group). It displays CPU load (1/5/15-minute averages and core count), memory (current and peak MB), storage (used percentage, free of total), a services panel actively probing Database, Queue (driver), Cache (via a write/read round-trip), and AI Provider (name / availability, checked without a billable request), and an environment panel (PHP version, Laravel version, environment, real system uptime). It offers a manual Refresh and an auto-refresh toggle (10-second interval), with color-coded status badges.

---

## User & Access Management

### User Management
A full user-administration surface. The paginated list (25 per page) shows each user's name, email, avatar, primary role, department, semester, active/inactive status, whether the account is protected, last-login time, join date, and effective-permission count. Every mutating action is written to the admin activity log.

### User Search & Filters
The user list supports a debounced search (name / email / department) and Role and Status (active/inactive) filter dropdowns, with filters persisted in the URL and per-filter user statistics.

### Create User
Admins can create a new user with a chosen role and a hashed password; new accounts are active by default. The action is logged.

### Edit User
Admins can update a user's details (name, email, status, department, semester). The System Administrator account can never be deactivated (its active flag is protected). Logged.

### Toggle User Active
A one-click activate/deactivate on any user, blocked for the protected admin account and logged with the resulting state. Inactive users are logged out and cannot sign in.

### Assign Role
Changes a user's role (synced to a single role). Blocked for the protected admin. Logged.

### Delete User
Permanently deletes a user, behind a confirmation. Blocked for the protected admin and for deleting your own account; if the user has related records, deletion fails gracefully with a suggestion to deactivate instead. Logged.

### Bulk User Actions
When users are selected via row checkboxes (or select-all), a bulk-actions bar allows activating, deactivating, or changing the role of multiple users at once.

### Role–Permission Matrix Editor
Admin → Roles presents a single matrix table with permission rows (grouped by category) against role columns; each cell is a grant/revoke checkbox. Column headers show each role's granted-permission count ("12 / 40" or "All"). Per-role Save buttons enable only when that role has unsaved changes and apply immediately on save (the full selected permission set replaces the role's prior set). The **admin** role is locked — its cells are shown all-checked and disabled and cannot be edited. This editor changes which permissions each existing role holds; it does not create or delete roles.

---

## Academic Structure

### Course Catalog
Admin → Courses is the admin-owned course catalog. The paginated list shows each course with its enrolled-student roster (loaded per page). Supporting data includes departments, semester options, all faculty and students, all terms (with the current one marked), and the full course list for choosing prerequisites.

### Course Search & Filters
Courses can be searched by code/name (server-side) and filtered by Department and Semester, with a clear action and a results-count line.

### Create / Edit / Delete Course
Admins can create a course (code, name, description, department, semester, credits) and edit it via a modal. A course delete is blocked while any students remain enrolled. Every create/update/delete is logged.

### Course Prerequisites
When creating or editing a course, admins pick prerequisite courses from a multi-select. A course can never be its own prerequisite (self-references are filtered out). Prerequisites are later enforced during student registration.

### Sections / Offerings
Under each course, admins manage sections (offerings). Creating a section captures a label, term, assigned faculty, max enrollment, active state, and schedule/classroom/office-hours details. Sections can be edited or deleted, but a section delete is blocked while it still has enrolled students. Assignments and drops are logged.

### Assign Faculty to Section
Faculty are assigned to a section through the section form when creating or editing it, so each offering has a responsible instructor (or shows as "Unassigned").

### Enroll / Drop Students (Roster Management)
A roster-management modal lets admins bulk-assign one or more students to a section (capped at seats remaining) and remove existing students. When capacity remains, an assignment creates a **pending placement** that reserves a seat and notifies the student to confirm via Registration; non-students or already-enrolled students are skipped with a reason. Dropping a student removes them and notifies them, and triggers waitlist promotion. The result summarizes assigned / waitlisted / skipped counts.

### Section Waitlists
When an admin assigns a student to a **full** section, the student is placed on a FIFO waitlist and notified they'll be auto-assigned when a seat opens, rather than being dropped. When a seat later frees up, the head of the waitlist is promoted automatically and notified.

### Academic Terms
Admin → Terms manages the academic calendar. The page lists all terms as cards (name, Current badge, start/end dates, section count, active-enrollment count, and a registration open/closed badge) and shows term-level stats. The "New term" button appears only while fewer than the three standard terms (Fall/Spring/Summer) exist.

### Create / Delete Term
Admins create a term by picking one of the standard unused names and setting start/end dates. A term delete is blocked if it is the current term or if it still has any sections.

### Set Current Term
Marks a term as the active/current term for the platform.

### Toggle Registration Window
A single toggle opens or closes student registration for a term, with a matching confirmation message.

### End-of-Term Rollover / Close
A close/rollover action closes a term and optionally rolls enrollments forward into a chosen next term (with an option to promote that next term to current). It reports how many enrollments were completed and how many sections were archived. Logged.

### Department Management
Admin → Departments manages academic departments. The paginated list shows each department as a card with name, optional code, active/inactive status, description, and footer stats for its user count and course count. Admins can create and edit departments (slug auto-generated) and delete them — but a delete is blocked while any users or courses still reference the department, to avoid orphaning them. Create and update are logged.

### Exam / Timetable Management
Admin → Exams schedules and maintains the exam timetable. The paginated list shows each exam (course code, section, type, title, date/time/location). Supporting data includes courses with their sections, departments, section options, and exam types.

### Exam Search & Filters
Exams can be searched by text and filtered by Department, Course, Section, Type, and a From/To date range, with filters preserved in the URL and a clear action.

### Create / Edit / Delete Exam
Admins schedule an exam (course, one or many sections including "All Sections", title, type, date, start time, duration, location, total marks, instructions) via a modal; enrolled students are auto-notified. Exams can be edited or deleted (with confirmation). Create/update are logged.

---

## Moderation & Communication

### Discussion Moderation Queue
Admin → Discussion Reports is the moderation queue for reported discussion posts and comments across all sections. It lists open reports (paginated), each showing whether it targets a post or a comment, the report reason, when it was reported and by whom, the section (course code + section), the content author, the content body, and a flag if the content was already removed elsewhere.

### Resolve / Dismiss Report
An admin can dismiss a report as reviewed with no action taken, recording who resolved it and when.

### Remove Reported Content
An admin can remove the offending post or comment (soft-delete, behind a confirmation), which also marks the report resolved. The remove action is hidden once the content has already been removed.

### Announcements / Broadcast Notifications
Admin → Announcements broadcasts an in-app notification to a chosen audience (Everyone, Students, Faculty, or Admins). The compose form captures a title (up to 150 chars) and message (up to 2000 chars); only active users receive it, and the result reports the recipient count. A "Recent announcements" panel lists recent broadcasts (title, message, audience, recipient count, relative time) with client-side pagination. Sends are logged.

### Edit Sent Announcement
An already-sent announcement's title and message can be edited, updating every recipient's copy at once. The audience is fixed once sent — only the content changes.

### Discussions Participation
Admins participate in the shared student/faculty discussion feed (a Section acts as the group). Access to each section is authorization-checked. Within a section admins can create posts (up to 5000 chars), add comments (up to 2000 chars), toggle likes, pin/unpin posts (moderator capability), delete posts and comments (as a moderator or the author), and report posts or comments (which feed the moderation queue).

### Notifications
Every authenticated admin has an in-app notifications center listing notifications with type-specific icons, title/message, and relative time, with unread emphasis. The header shows the unread count. Admins can mark an individual notification read, mark all read, delete a notification, and click a notification to open its deep link (which also marks it read). The list is paginated and polls for new notifications in the background.

### Messenger
Admins have access to the shared, relationship-based direct-messaging plumbing (conversation resolve, overview, message history, send, and read-state), which powers 1:1 messaging and presence across the platform.

### Presence Heartbeat
A background heartbeat keeps the admin's online/presence status current, feeding "Active Now / Online" indicators shown across dashboards.

---

## Global Tools

### Global Search (⌘K)
Every authenticated admin has a ⌘K command-palette search available from the app header. It merges local page matches with grouped remote results (a semantic "Knowledge" group over the admin's RAG corpus plus lexical groups such as courses, assignments, discussions, chat history, and — admin only — users), with debounced queries, unified keyboard navigation, and deep-linked results.

---

## System & AI Configuration

### AI Settings
Admin → Settings configures the AI backend. It shows the effective configuration — chat provider and model, temperature and max tokens, embedding model/provider and the model actually in effect, embedding fallback toggle and secondary provider, RAG top-K and similarity threshold, system-prompt override, supported languages, the chat-provider fallback toggle and primary choice, and the OpenRouter model chain. Provider options are OpenAI and Mock (offline). API keys are **write-only** and stored encrypted — the browser only ever sees whether each key (OpenAI / OpenRouter / Jina) is configured, never the key itself; leaving a key field blank keeps the current key and an explicit "remove" clears it.

### Provider Fallback Configuration
Within AI Settings, admins configure a resilient provider chain: an "Enable fallback" toggle, a primary-provider choice between OpenAI and OpenRouter (the other backs it up), a masked OpenRouter API key, and a model-chain textarea (with an `openrouter/auto` safety net always appended). This keeps chat working even if the primary AI provider fails.

### Embeddings Provider & Re-embed
Embeddings resolve separately from chat (OpenRouter has no embeddings endpoint). Admins pick a primary embeddings provider (OpenAI, Jina, or Mock), can supply a masked Jina key, and can enable a dual-index fallback with a secondary provider so RAG survives an embeddings-API outage. Because vectors are tagged by embedding model, changing the embeddings backend on save **automatically re-indexes the corpus** so old and new vectors don't cross-compare; if that synchronous re-index fails, a warning advises running the re-embed command manually.

### AI Test Connection
AI Settings provides test buttons that perform real round-trips: **Test OpenRouter** does a minimal live chat call and reports the answering model; **Test Embeddings** does a minimal live embed and reports the model and vector dimensions; the default/active-provider test does an actual embed call (Mock reports "available" without a network call) so "reachable" means a verified round-trip, not just a stored key. Failures surface the error message.

### Email / SMTP Settings
Admin → Email Configuration configures outbound mail. It shows the effective config — mailer (SMTP or Log), host, port, encryption (TLS/SSL/None), username, from-address, and from-name — with the SMTP password write-only and stored encrypted (the browser only sees whether one is stored). Blank keeps the current password; an explicit "remove" clears it.

### Email Test Send
The Email Settings page includes a "send a test email" input and button that dispatches a plain test message to a chosen address to verify delivery; success reports the recipient and failure surfaces the error.

### Exam Security Governance
Admin → Exam Security is the global gate deciding which exam-proctoring layers faculty may use and which are pre-enabled. It lists every proctoring layer from the registry, grouped by category (Lockdown, Question integrity, Monitoring & evidence, Media recording), each with two per-layer toggles: **Available** (whether faculty can offer it at all) and **On by default** (pre-ticked on new tests, only settable when Available). Camera/media layers carry a consent badge. Turning off availability hides a layer from faculty and stops it on existing tests. The page also shows and saves the default max-warnings-before-disqualification value.

### Exam Security "How Each Layer Works" Guide
The Exam Security page has a "How each layer works" slide-in guide documenting the step-by-step behavior of every layer (fullscreen enforcement, tab-switch and clipboard detection, sequential/lock-back navigation, question shuffling, watermarking, integrity notices, fingerprinting, behavior logging, risk analysis, webcam, snapshot evidence, phone detection, screen recording, and face-liveness blink-gate). All timings and thresholds shown in the guide are injected live from the server configuration, so the documentation always matches shipped behavior. It closes by noting these signals are for human review, not automatic verdicts.

### Maintenance Mode Switch
A hidden, URL-driven maintenance switch lets an operator take the whole app offline. Adding `?live=false` to any URL drops every visitor into a Maintenance page (HTTP 503, JSON 503 for API callers); `?live=true` flips it back to live. The state is persisted globally in cache so it sticks across all requests and visitors, and `?live=true` is always evaluated first so an operator can never lock themselves out. The default state before any toggle comes from configuration (live by default); the health-check endpoint and the test environment are never gated.

### Demo Mode Governance
When the deployment runs in demo mode (`APP_MODE=demo`), every account is capped at a fixed number of AI requests across all AI surfaces (student chat/agent, faculty assistant, and AI generators). The cap is enforced only on request-consuming AI POST endpoints (never on page loads or history reads), charged up-front regardless of provider success, and returns an HTTP 429 with a "demo limit reached" payload once exhausted. Outside demo mode nothing is metered. This is a deployment-level policy governing AI cost during demonstrations.
