# Learning, Content & Assessment Engine

This is the shared, role-agnostic engine that powers **what learners study and how they are assessed** — the course/catalog machinery, every content type and its player, live and virtual class delivery, the quiz/exam/assignment assessment stack, the proctoring and originality signals that guard integrity, peer review, and the certificate/credential pipeline. It consolidates behavior across the UniNexus university copilot (Student/Faculty/Admin roles) and two commercial LMS products (Mentor LMS, Faculty LMS by SpaGreen), plus the feature wishlist. The role-specific *actions* that sit on top of this engine — a student submitting an assignment, a faculty member grading a queue or authoring a class test, an admin running the catalog CRUD or governing proctoring — live in their respective role files; where products or roles diverge, notes call it out. AI *generation* of courses, quizzes, and questions is a pointer to the AI file; this document describes only the engine those actions and generators drive.

---

## Course & Catalog Engine

### Course Builder / Visual Curriculum Builder
A drag-and-drop builder for authoring a course as an ordered tree of sections and lessons (and, in some products, chapters), with instant reorder and no coding. Mentor LMS and the wishlist expose a full visual curriculum builder where sections, lessons, quizzes-between-lessons, and downloadable assignments all live in one unified editor. In UniNexus the equivalent authoring surface is admin-owned course/section setup plus faculty course-material and class-test authoring rather than a marketing-style drag-drop builder.

### Course Sections & Chapters
The structural units of a course: sections group lessons, and some products (SpaGreen) add an intermediate chapter level. Content is organized, resourced, and reordered within this hierarchy.

### Course Categories, Tags & Attributes
Taxonomy for organizing and filtering the catalog: categories and subcategories, free-form tags, and product-style options/attributes/brands (SpaGreen treats courses as products with options and attributes). Used to drive advanced filtering and search across the catalog.

### Advanced Filtering & Resource Management
Catalog-wide filtering by category, department, tag, type, price, and status, backed by managed resource files attached to courses and lessons. Instant search suggestions and search-log analysis (SpaGreen) sharpen discovery.

### Learning Paths & Career Paths
Ordered multi-course journeys toward a goal. The wishlist defines Learning Paths and Career Paths as first-class objects; UniNexus surfaces a semester-by-semester learning path/roadmap generated from a student's enrolled courses (the student-facing roadmap view is a pointer to student.md).

### Course Bundles & Templates
Bundles group several courses into one sellable/enrollable package; templates are reusable course skeletons that speed authoring of new courses. (Wishlist; commerce aspects of bundles are a pointer to the commerce file.)

### Draft Courses & Course Versioning
Courses can exist in an unpublished **draft** state before going live, and versioning tracks successive revisions of a course so changes are auditable and reversible. In the LMS products a draft course is reviewed/approved before publication; UniNexus class tests likewise carry a draft state before publish.

### Prerequisites (Concept & Enforcement)
Prerequisite relationships gate access to a course until required prior courses are satisfied. The engine models the prerequisite graph (a course can never be its own prerequisite); only a **completed** prior course satisfies the requirement, and enforcement happens at registration/enrollment time — unmet requirements surface as badges and block the Register action. (Admin manages the prerequisite set; the student-facing enforcement UX is a pointer to student.md/admin.md.)

### Drip Content & Course Scheduling
Drip content releases lessons on a schedule or on progression milestones rather than all at once; course scheduling controls when a course opens/closes and how its content unlocks over time. (Wishlist.)

### Private / Public / Featured Courses
Visibility tiers: public courses appear in the open catalog, private courses are restricted to invited/enrolled learners, and featured courses are promoted (admin can feature selected courses; in marketplace mode featuring is part of course oversight). UniNexus additionally scopes course visibility by department and by the Students/Faculty/Admin audience.

### Course Preview
Lets an author (or prospective learner) experience a course as a student before publishing/purchasing, including designated free-preview lessons. Instructors use it to test the student experience; prospective buyers use free previews to sample content.

### Course Cloning & Archive
Cloning duplicates an existing course as a starting point for a new one; archiving retires a course from the active catalog without deleting it, preserving records. End-of-term rollover in UniNexus archives sections as part of closing a term.

---

## Content Types & Delivery

### Video Lessons (Direct Upload)
Core video content uploaded directly into the platform with no external plugin required (Mentor/SpaGreen), playable in the integrated course player. Optional external video hosting (YouTube, Vimeo) is supported by SpaGreen to reduce hosting cost; audio-only playback options accompany video.

### Audio / Podcast Lessons
Audio-first lessons and podcast-style episodes as a standalone content type (wishlist "Audio Lessons" / "Podcast Lessons").

### PDF / Document Lessons
PDF and document lessons rendered or downloaded within the course. UniNexus's document/material handling additionally indexes file-backed documents into the RAG corpus so they can inform AI answers.

### Text / HTML Lessons
Rich text and raw-HTML lesson bodies authored in-editor, mixed freely with other content types in the same course.

### Interactive Lessons, SCORM, xAPI/Tin Can & H5P
Standards-based and interactive content: SCORM packages, xAPI (Tin Can) statements, H5P interactive content, and generic interactive lessons. SpaGreen delivers SCORM and text courses via plugins; the wishlist lists xAPI/Tin Can/H5P/interactive lessons as first-class types.

### Embedded Content & Downloadable Resources/Attachments
Embed third-party content inline, and attach downloadable resource files/attachments to lessons for students to download within the course materials.

### Course Player
The unified playback surface presenting video (with audio options), text/HTML, quizzes positioned between lessons, and downloadable resources in one integrated player, with per-lesson completion feeding progress tracking.

### Free Preview Lessons
Individual lessons marked as free previews so prospective students can sample them before enrolling or purchasing.

### Recorded Sessions
Recordings of past live classes, made available alongside on-demand lessons so live and recorded content blend in the same course.

---

## Live & Virtual Classes

### Live Classes / Webinars (Zoom-powered)
Schedule live sessions (date, time, duration) directly from the course editor; enrolled students are notified automatically and join with one click from their dashboard. Zoom integration is native (admin connects a Zoom account platform-wide); sessions blend with recorded lessons in the same course, and session data (access, student lists, recordings) is stored on the owner's own server. SpaGreen also supports live classes/webinars as a course type.

### Virtual Classroom & Interactive Whiteboard
A richer synchronous space beyond a plain video call: a virtual classroom with an interactive whiteboard for live instruction (wishlist).

### Screen Sharing & In-Class Live Chat
Within a live session, instructors share their screen and participants use an in-class live chat channel (SpaGreen live-class chat + screen sharing).

### Online Meeting Booking
Book one-on-one, in-person, or group meeting sessions with an instructor (SpaGreen). This is the content/delivery-layer meeting primitive; UniNexus's relationship-gated office-hours booking is a pointer to student.md/instructor.md, and general messaging is a pointer to the communication file.

---

## Assessment Engine

### Quiz Builder
Author quizzes as standalone assessments or embedded between lessons, mixing question types in a single quiz. In UniNexus the equivalent is faculty-authored class tests; instructor-facing authoring UI is a pointer to instructor.md.

### Question Bank (Concept)
A reusable, per-course library of questions carrying topic and difficulty metadata, so questions can be curated once and drawn into multiple assessments. Questions can be imported from an existing test (deduplicated by text, tagged with the test's title as topic) and selected to spin up a draft test; the same bank can seed student practice quizzes. (Faculty management UI and student "practice from bank" action are pointers to instructor.md/student.md.)

### Question Types & Randomized Questions
Supported item types include single-choice and multiple-choice, true/false, and short-answer, mixable within one assessment; questions (and their options) can be randomized/shuffled per attempt to deter sharing.

### Timed Quizzes / Exams
Countdown-timed assessments that auto-submit at zero, with configurable time limits, attempt rules, and sequential vs. all-at-once navigation. A sticky timer runs during the attempt; fullscreen mode may be required.

### Practice Tests / Practice Quizzes
Self-paced, unproctored, instantly-graded assessments for self-testing, with no timer and immediate per-question review. (The student self-quizzing tool and its "missed questions → flashcards" loop are a pointer to student.md; AI generation of practice quizzes is a pointer to the AI file.)

### Assignments (incl. Coding & File-Upload)
Assignments accept a written response and/or a file upload; specialized variants include coding assignments and file-upload assignments (wishlist). Instructors track and review submissions. (Student submission UX and faculty grading queue are pointers to student.md/instructor.md.)

### Rubrics
Structured grading criteria (each with a point maximum) attached to an assignment, used for consistent per-criterion scoring during manual or AI-assisted grading.

### Manual Grading & Auto Grading
Objective question types (MCQ/true-false) are auto-graded instantly with immediate feedback; short-answer questions and assignments requiring judgment are manually graded by the instructor. Both paths coexist in one assessment.

### Exams (Standalone / Sellable)
Standalone exams that can be sold and taken independently of any course — for certification, practice, or competitive purposes — delivered through a timed, fullscreen exam interface with attempt rules and issued marksheets/certificates on completion. (Mentor LMS standalone exams; commerce/selling is a pointer to the commerce file, exam scheduling is a pointer to admin.md.)

### Survey Builder & Polls
Non-graded instruments for gathering input: multi-question surveys and quick single-question polls (wishlist). Distinct from anonymous course feedback, which is a pointer to the communication file.

### Skills Assessment
Assessments that measure competencies/skills (rather than course completion) and feed a skill matrix or competency tracking (wishlist).

---

## Assessment Integrity & Proctoring

These are client-side, camera-AI and browser proctoring layers that produce **signals for human review, not automatic verdicts**. The engine defines the mechanics of each layer once here; the admin **governance** screen that decides which layers are available/on-by-default and the faculty **authoring/review** of proctored tests are pointers to admin.md/instructor.md, and the student-facing exam experience is a pointer to student.md.

### Begin-Gate Consent
A required opening gesture that captures camera/screen consent and requests fullscreen before the timer starts; camera/media layers carry a consent badge.

### Face-Liveness Blink Gate
Before questions render, the candidate must position their face and blink to prove liveness (guarding against a static photo). Repeated failure can be bypassed after a grace period (the bypass is logged/flagged) so a real student is never permanently locked out.

### Fullscreen Enforcement
Leaving fullscreen blurs and disables the questions until the candidate returns; a silently-refused fullscreen is detected rather than letting the candidate answer windowed.

### Tab-Switch / Focus Tracking
Leaving the exam tab or losing window focus is recorded as a violation.

### Face-Loss Detection
When the candidate's face leaves the frame, a soft banner nudges them back for a few free warnings, escalating to a blocking overlay and a counted violation if the face stays gone.

### Phone Detection
The camera watches for a phone appearing in view (object detection) and photographs flagged moments; treated as a warning signal, never an automatic violation.

### Snapshot Evidence
Webcam still frames captured at flagged moments and on a jittered periodic schedule, stored (with per-attempt caps) as a photo-strip for faculty review. Served from private storage, strictly scoped to the specific test/attempt.

### Webcam Recording
Optional continuous webcam recording of the attempt (bitrate-capped), streamable by the owning faculty for audit; subject to retention pruning. Camera capture is decoupled from recording so snapshot/liveness layers work without full recording.

### Warnings vs. Violations & Auto-Disqualification
Candidates receive a small, configurable number of warnings ("Warning X of N"); exceeding the max-warnings threshold triggers automatic **disqualification** (score 0). The default threshold is admin-governed and settable per test.

### Clipboard / Right-Click Restriction, Watermarking & Question Shuffling
Additional deterrents: disabling clipboard copy/paste and right-click, overlaying the candidate's name/ID as a watermark, and shuffling question/option order per attempt.

### Device Fingerprinting, Behavior Logging & Risk Analysis
Each attempt records a device fingerprint and a timeline of behavior events, from which a computed **risk** score is derived — surfaced in the per-attempt proctoring dossier for human audit. Audible alerts may accompany violations.

---

## Peer Review & Originality

### Peer Review System
Anonymous student-to-student assessment of assignment work. When enabled on an assignment, each reviewer is **lazily** assigned up to two classmate submissions (least-reviewed-first, never their own); reviewers give a 1–5 rating plus constructive comments and may revise. Anonymity holds in **both** directions — reviewers never see whose work they grade, reviewees never see who reviewed them — and peer reviews do not directly affect grades. Faculty see only **aggregate** stats (average rating, review count). The system lives here; the student review action and the faculty stats chip are pointers to student.md/instructor.md.

### Submission Similarity / Plagiarism Signal
An originality signal computed by chunking and embedding a submission's text (typed content plus extracted file text) and comparing chunks against other submissions for the **same section-scoped assignment**. Pairs whose best matching chunk exceeds the flag threshold are recorded in both directions and surfaced as a warning badge plus an excerpt-pair evidence panel; resubmission wipes and recomputes the comparison. It is a signal to prompt human review, **not a verdict**. (Faculty-facing grading surface is a pointer to instructor.md.)

---

## Certificates & Credentials

### Auto-Issued Certificates
A certificate of completion is issued automatically when a learner finishes a course or passes an exam, downloadable as PDF.

### Certificate Builder / Customizable Templates
A design tool for certificate templates — logo, colors, branding, and layout. Admin-created templates can apply platform-wide (including across all instructors' courses in marketplace mode), and instructors may customize templates for their own courses.

### Dynamic Certificates
Templates populated with per-recipient dynamic fields (name, course, date, score) at issue time (wishlist).

### Certificate Verification
Each issued credential carries a unique verification identifier so its authenticity can be checked independently.

### Certificate Sharing & Expiration
Credentials can be shared to professional networks (e.g., LinkedIn) or downloaded as PDF, and templates may define an expiration date after which the credential lapses (wishlist).

### Marksheets
A detailed score breakdown issued after an exam/course, showing performance **section by section** rather than a single aggregate score. Auto-generated on completion, downloadable, and customizable alongside certificates.

### Digital Credentials / Open Badges
Standards-based portable credentials — digital credentials and Open Badges — earned for achievements and shareable/verifiable across platforms (wishlist).

---

## Adaptive & Cohort Learning

### Microlearning
Delivery of content in small, bite-sized units for quick, focused study sessions (wishlist).

### Adaptive Learning & Personalized Learning Paths
Learning that adjusts to the individual — content difficulty and sequencing tuned to performance, and personalized paths generated per learner. UniNexus realizes a concrete slice of this as the concept-mastery-driven adaptive-review loop (weak topics auto-lower difficulty for generated practice/flashcards); the analytics-to-action UX is a pointer to student.md, and AI path generation is a pointer to the AI file.

### Competency-Based Learning
Progression gated by demonstrated competencies rather than time spent, feeding competency tracking and a skill matrix (wishlist / enterprise).

### Cohort Management
Group learners into cohorts that move through a course together on a shared schedule, enabling cohort-scoped pacing, discussion, and reporting (wishlist).

### Mentorship
Structured mentor-to-learner relationships as a content/delivery concept — pairing learners with mentors for guided progression (wishlist). Distinct from office-hours and messaging, which are pointers to their respective role/communication files.
