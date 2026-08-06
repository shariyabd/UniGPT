# Mentor LMS — Complete Feature List

**Mentor LMS** is a self-hosted, Laravel-based Learning Management System for selling courses and running an online learning platform. It supports both a **single-instructor** model and a **multi-instructor marketplace**, with three primary user roles — **Administrator**, **Instructor**, and **Student**. This document lists every documented feature, grouped by the role that uses it, followed by shared platform capabilities.

Source: <https://mentor-lms.com/docs> (User Roles, Introduction, Feature pages, and AI Assistant plugin docs).

---

## Administrator Features

The administrator is the platform owner with full control over every aspect of the installation.

### Admin Dashboard
Central overview showing revenue tracking and transaction history, enrollment statistics across all courses, a pending payout request queue, new user registration monitoring, and a platform activity feed.

### User Management
View, edit, and manage all students and instructors. Approve instructor applications, suspend or remove accounts, and monitor user activity across the platform.

### Instructor Application Approval
In marketplace mode, review incoming instructor applications from the admin panel and approve or reject them with one click before granting dashboard access.

### Course Oversight & Approval
Review courses submitted by instructors before they go public, approve or reject them, feature selected courses, and monitor enrollment numbers and course quality.

### Revenue Management
Set platform commission rates, examine all transactions, view a complete payment history, and see revenue broken down by course or instructor.

### Payout Processing
Review instructor payout/withdrawal requests, approve them, and transfer funds through the configured payment method. Admins retain full control over platform cash flow.

### Content Moderation
Monitor and moderate course forums, student reviews, and blog posts — review, edit, or remove forum posts to maintain community standards.

### Certificate & Marksheet Designer
Create and customize certificate designs from the admin panel — add logo, colors, and branding. Templates apply platform-wide, including to all instructors' courses in marketplace mode.

### Drag & Drop Page Builder
Create and manage the platform's public-facing pages using a visual, no-code drag-and-drop builder (see Page Builder under Platform Features).

### Newsletters & Email Campaigns
Send newsletters or targeted email campaigns directly to all students or instructors.

### Platform Configuration / Branding
Update site name, logo, colors, and global settings; manage branding without coding.

### Payment Gateway Setup
Enter API credentials and activate one or more payment gateways (Stripe, PayPal, Razorpay, Paystack, SSLCommerz, Offline) from the admin panel.

### SMTP / Email Settings
Configure SMTP for transactional emails.

### Storage Configuration
Configure storage backends — local, AWS S3, or Cloudflare R2.

### Google Authentication Setup
Configure Google OAuth login for the platform.

### Zoom Integration Setup
Optionally connect a Zoom account to enable live classes across the platform.

### Homepage Template Selection
Choose and switch the active homepage template with a single click.

### Multi-Language Management
Add and manage languages; translate all static text across the admin, instructor, and student dashboards into any language.

### Color System Customization
Customize the platform's color system and theme.

### Security Controls
Role-based permissions and reCAPTCHA protection.

### System Maintenance
Update the application, manage backups, and reboot/maintain the system. Includes one-click updates and manual update options.

### AI Assistant Configuration (Plugin)
Only administrators can configure the AI Assistant plugin. Admins have unlimited AI token quota and access to all AI features, and set the token allowances enforced on instructors.

### Standalone Exam Management
Manage the standalone exam system — question types, grading rules, and pricing (in single-instructor/administrative mode, admins create courses and exams directly).

---

## Instructor Features

Instructors build and manage teaching content. Access is granted either by application-and-approval (marketplace mode) or by admin designation (administrative mode).

### Instructor Dashboard
Displays enrollment and revenue data by course, pending student work and forum activity, scheduled live class information, and payout status / account balance.

### Course Creation (Course Builder)
Build courses with sections and lessons using a drag-and-drop visual curriculum builder; reorder content instantly. No coding required.

### Content Types
Add video lessons (uploaded directly, no external plugin), text-based lessons, quizzes positioned between lessons, and downloadable assignments — all in one unified editor.

### Downloadable Lesson Resources
Attach resource files for students to download within course materials.

### Quizzes Between Lessons
Embed quizzes between lessons with multiple question types (single-choice, multiple-choice, true/false, short answer).

### Assignments
Create assignments, track submissions, and review student work.

### Manual Grading
Manually grade short-answer questions and assignments that require human review.

### Standalone Exam Creation
Create standalone exams with multiple question types that can be sold independently of courses.

### Course Preview
Test the student experience — preview the course before publishing.

### Live Classes (Zoom)
Schedule Zoom-powered live sessions directly from the course dashboard/editor; enrolled students are notified automatically. Set date, time, and duration.

### Student Management
Review enrolled learners and monitor their progress per course.

### Discussion Forum Participation
Answer student questions directly in course forums; receive notifications for new forum posts to respond promptly.

### Certificate Template Customization
Customize and design certificate templates for their courses.

### Revenue Tracking
Monitor course sales, earnings, and commission breakdowns.

### Payout Requests
Request financial disbursements/withdrawals once earnings meet the minimum requirement.

### Results & Performance Analytics
Analyze student performance and exam results, including average scores, pass rates, and which questions students find difficult.

### AI Assistant (Plugin)
Instructors can use all AI features (they cannot configure the plugin), subject to a token quota that resets daily/weekly/monthly:
- **AI Course Generation** — generate a full draft course (structure, descriptions, sections, FAQs, outcomes, requirements, optional lessons, and thumbnail) from a single prompt.
- **AI Course Updates** — modify course title, short description, and full description via instruction.
- **AI Section Management** — rename existing sections with AI (creation remains manual).
- **AI Text Lessons** — generate new lessons with HTML body content or revise existing lesson titles/bodies.
- **AI Section Quizzes** — create or update quizzes from section lesson content; configure question types.
- **AI Course Info Content** — generate/edit FAQs, learning outcomes, and requirements individually or in bulk.
- **AI Thumbnail Generation** — create course thumbnail images during setup or later via the Media tab.
- **"Write with AI" Rich-Text Editing** — generate or refine HTML content inside any editor via a toolbar button.

---

## Student Features

Students browse, purchase, and consume learning content.

### Student Dashboard
Displays enrolled courses with progress indicators, upcoming live class sessions, assignment submission status, certificate/marksheet downloads, purchase and payment history, and account/profile settings.

### Course Catalog & Enrollment
Browse the course catalog, add courses to a cart, and enroll via supported payment gateways.

### Wishlist
Save courses to a wishlist for future purchase.

### Course Player
Access video lessons, text content, quizzes, and downloadable resources within an integrated course player.

### Progress Tracking
Visual indicators of completion progress per course.

### Live Class Attendance
Join scheduled Zoom sessions with one click directly from the course dashboard; email notifications when a new live class is scheduled.

### Assignment Submission
Submit assignment work and view instructor feedback.

### Exam Taking
Purchase and take standalone exams through a timed exam interface with a countdown timer and fullscreen mode; multiple-attempt rules apply.

### Automatic Grading & Instant Feedback
Objective question types are graded instantly upon submission, with immediate feedback.

### Marksheets
Receive detailed marksheets after completing an exam, showing scores per section.

### Certificates
Automatically receive a certificate of completion upon finishing a course or passing an exam; download in PDF format to share on professional networks (e.g., LinkedIn). Each credential has a unique verification identifier.

### Discussion Forums
Ask questions, share insights, and interact with instructors and peers in per-course forums.

### Course Reviews
Leave star ratings and written feedback for completed courses.

### Notifications
Receive real-time alerts (e.g., live class scheduling, forum activity).

### Purchase & Payment History
View a record of purchases and payments made.

### Account & Profile Management
Manage profile details and account security controls.

---

## Shared / Platform-Wide Features

Capabilities that span roles or belong to the platform infrastructure.

### Multi-Instructor Marketplace
Run the platform as a solo site or a multi-instructor marketplace, and switch between models without reinstalling. Supports unlimited instructors, per-sale commission calculation, per-instructor earnings analytics, and a full apply → approve → payout workflow.

### Payment Gateways
Built-in support for **Stripe**, **PayPal**, **Razorpay**, **Paystack**, **SSLCommerz**, and **Offline/manual** payments. Multiple gateways can run simultaneously; customers choose their method at checkout. Payments are processed through each gateway's official API — raw card data is never stored.

### Transaction Management
Complete payment history logging, filtering by gateway or date, and export for accounting.

### Certificates & Marksheets System
Auto-generated on course completion / exam pass, admin- or instructor-customizable, downloadable as PDF, shareable, and verifiable via unique identifiers. Marksheets show section-by-section score breakdowns.

### Exams & Quizzes Engine
Multiple question types (multiple choice, true/false, short answer), mixed within one assessment; automatic grading; customizable time limits; configurable attempt rules; detailed marksheets; and performance analytics (average scores, pass rates, difficult questions). Exams can be sold as standalone products (certification, practice, competitive).

### Discussion Forums
Every course automatically gets its own dedicated forum for student ↔ instructor ↔ peer interaction, with instructor notifications and admin moderation.

### Live Classes (Zoom)
Native Zoom integration for scheduling live sessions, blended with recorded lessons in the same course. Session data (access, student lists, recordings) is stored on the owner's own server.

### Drag & Drop Page Builder
Prebuilt homepage templates (switchable in one click), a drag-and-drop section editor, design customization (colors, fonts, images, headlines, copy), unlimited custom pages (About, FAQ, Terms, Privacy), navigation integration, per-page SEO metadata (title, description, keywords), and automatic mobile responsiveness — all no-code.

### Blog Platform
Built-in blog for content marketing and engagement.

### Student Review System
Star ratings and written feedback on courses (moderated by admins).

### Multi-Language / Localization
Multi-language dashboard support; translate all static text across every dashboard into any language.

### Storage Options
Local, AWS S3, or Cloudflare R2 storage backends.

### Google Authentication
Optional Google OAuth sign-in.

### Self-Hosted Deployment
Fully self-hosted Laravel application with a React frontend via Inertia.js; one-click updates and backups; web-based installer; plugin system for extensibility.

### AI Assistant Plugin (role-gated)
Optional plugin providing AI course/lesson/quiz/content/thumbnail generation and "Write with AI" editing. **Admins** configure it (unlimited quota); **Instructors** use all features under a token quota that resets daily/weekly/monthly; **Students** have no access.

### Employee / Corporate Training
Supports employee training program use cases in addition to public course selling.
