/**
 * UniGPT — product presentation deck (config-driven).
 *
 * The presentation is generated from this data model, NOT hardcoded markup.
 * Each slide declares a `layout` (rendered by Presentation.vue) plus
 * layout-specific fields. Add / reorder / remove slides here and the deck
 * updates automatically — counter, progress bar and dot-nav are all derived
 * from `slides.length`.
 *
 * Slide shape:
 *   { id, layout, kicker?, title?, subtitle?, ...layoutFields }
 *
 * Icons are referenced by name (string) and resolved against the heroicons
 * map in Presentation.vue — keeps this file pure data.
 */

export const meta = {
    name: 'UniGPT',
    tagline: 'AI Academic Copilot for Universities',
    repo: 'Laravel 11 · Inertia 2 · Vue 3 · MySQL',
};

export const slides = [
    /* ---------------------------------------------------------------- 01 HOOK */
    {
        id: 'cover',
        layout: 'cover',
        kicker: 'AI academic copilot for universities',
        title: 'Run your university on\nintelligence, not paperwork.',
        subtitle:
            'One platform. Three tailored experiences. Grounded AI that answers from your own academic documents — with citations, not hallucinations.',
        tags: ['Students', 'Faculty', 'Administrators'],
        chips: [
            { icon: 'SparklesIcon', label: 'RAG-grounded answers' },
            { icon: 'ShieldCheckIcon', label: 'Role-based governance' },
            { icon: 'ClipboardDocumentCheckIcon', label: 'Proctored AI exams' },
        ],
    },

    /* ------------------------------------------------------------- 02 PROBLEM */
    {
        id: 'problem',
        layout: 'statement',
        kicker: 'The problem',
        title: 'Campus knowledge is everywhere —\nand answers are nowhere.',
        accent: 'danger',
        points: [
            { icon: 'FolderOpenIcon', title: 'Fragmented systems', desc: 'Syllabi, notes and policies scattered across portals, PDFs and inboxes.' },
            { icon: 'ExclamationTriangleIcon', title: 'Generic AI hallucinates', desc: 'ChatGPT invents policies it has never seen. Unsafe for an institution.' },
            { icon: 'ClockIcon', title: 'Staff drown in repetition', desc: 'The same questions, grading and admin tasks, answered manually, again and again.' },
        ],
    },

    /* --------------------------------------------------------- 03 WHY MATTERS */
    {
        id: 'stakes',
        layout: 'statement',
        kicker: 'Why it matters',
        title: 'In academia, a wrong answer\nis worse than no answer.',
        accent: 'warning',
        points: [
            { icon: 'LockClosedIcon', title: 'Trust is everything', desc: 'A policy answer must be traceable to a real, approved source document.' },
            { icon: 'EyeIcon', title: 'Accountability', desc: 'Institutions need to see, govern and audit how AI is used on campus.' },
            { icon: 'BoltIcon', title: 'Speed at scale', desc: 'Thousands of students, hundreds of courses — manual workflows do not scale.' },
        ],
    },

    /* ------------------------------------------------------------ 04 SOLUTION */
    {
        id: 'solution',
        layout: 'pillars',
        kicker: 'The solution',
        title: 'UniGPT — one AI platform for the whole campus',
        subtitle: 'Grounded intelligence, role-aware dashboards, end-to-end academic workflows.',
        items: [
            { icon: 'SparklesIcon', title: 'Grounded AI', desc: 'Every answer cited from your approved documents, with a confidence score.' },
            { icon: 'UsersIcon', title: 'Three experiences', desc: 'Student, Faculty and Admin dashboards — one login, tailored to each role.' },
            { icon: 'AcademicCapIcon', title: 'Full academic loop', desc: 'Teach, quiz, submit, proctor, grade and report — wired end-to-end.' },
        ],
    },

    /* -------------------------------------------------------- 05 ARCHITECTURE */
    {
        id: 'architecture',
        layout: 'architecture',
        kicker: 'System architecture',
        title: 'Modern, modular, no vendor lock-in',
        subtitle: 'Domain-Driven Design over a single Laravel + MySQL instance — no external vector DB, no LLM SDK.',
        layers: [
            { name: 'Presentation', accent: 'cyan', items: ['Vue 3 + Inertia 2', 'Tailwind + Vite', 'Ziggy routes'] },
            { name: 'Application', accent: 'primary', items: ['Role / Permission middleware', 'Controllers + Form Requests', 'Domain services & actions'] },
            { name: 'Domain (DDD)', accent: 'violet', items: ['Chat · RAG · Academic', 'Pluggable AI providers', 'Contracts over implementations'] },
            { name: 'Data', accent: 'emerald', items: ['MySQL 8 (uni_gpt)', 'Native cosine vector store', 'Audit log & analytics'] },
        ],
    },

    /* ----------------------------------------------------------------- 06 RBAC */
    {
        id: 'rbac',
        layout: 'roles',
        kicker: 'Role-based access control',
        title: 'Three roles, 40+ fine-grained permissions',
        subtitle: 'Every route guarded. Every action audited. Role assignments can even expire.',
        roles: [
            {
                icon: 'AcademicCapIcon', name: 'Student', accent: 'cyan',
                tagline: 'Learn & get answers',
                features: [
                    'Streaming AI chat with citations — grounded in the library + your own notes & materials',
                    'Personal dashboard: courses, CGPA & deadlines',
                    'One-click registration for assigned course sections',
                    'Timed quizzes & class tests with instant auto-graded results',
                    'AI practice quizzes — missed questions become flashcards',
                    'AI Study Planner, SM-2 Flashcards & "My Progress" analytics',
                    'OCR handwritten notes; discussions & opt-in leaderboard',
                    'Course roadmap, materials, ⌘K semantic search & document library',
                    'Exam schedule, calendar (.ics sync), notes & tasks',
                    'Real-time messaging, group study rooms & office-hours booking',
                ],
            },
            {
                icon: 'BookOpenIcon', name: 'Faculty', accent: 'primary',
                tagline: 'Teach & assess',
                features: [
                    'Streaming AI teaching assistant: quizzes, assignments & rubrics',
                    'Build timed quizzes & class tests with auto-grading',
                    'Per-test proctoring layers + risk-scored review dossier',
                    'Manage taught sections & publish course materials',
                    'One-click attendance with live class stats',
                    'Grading workspace with AI-drafted feedback',
                    'At-risk early warning + bookable office hours',
                    'Per-course analytics & grade distributions',
                    'Moderate section discussions; message your students live',
                ],
            },
            {
                icon: 'ShieldCheckIcon', name: 'Admin', accent: 'emerald',
                tagline: 'Govern & monitor',
                features: [
                    'User, role & permission matrix management',
                    'Course catalog, sections, terms & student assignment',
                    'Document approval workflow & knowledge base',
                    'Institution-wide analytics & top queries',
                    'AI provider settings, prompts & retrieval tuning',
                    'Exam-security gate + discussion moderation queue',
                    'AI usage monitor with per-user access control',
                    'System monitor, departments & announcements',
                ],
            },
        ],
    },

    /* ------------------------------------------------------------- 07 AI CORE */
    {
        id: 'rag-pipeline',
        layout: 'pipeline',
        kicker: 'The AI core',
        title: 'Retrieval-Augmented Generation, MySQL-native',
        subtitle: 'From an uploaded PDF to a cited answer — the whole pipeline runs in-house.',
        steps: [
            { icon: 'DocumentTextIcon', label: 'Upload & approve', sub: 'Admin curates the knowledge base' },
            { icon: 'PuzzlePieceIcon', label: 'Chunk', sub: '~150-token overlapping passages' },
            { icon: 'CpuChipIcon', label: 'Embed', sub: 'Vectors stored in MySQL' },
            { icon: 'MagnifyingGlassIcon', label: 'Retrieve', sub: 'Cosine similarity, top-K' },
            { icon: 'CheckBadgeIcon', label: 'Cite', sub: 'Confidence + source excerpts' },
        ],
    },

    /* --------------------------------------------------- 08 GROUNDED ANSWERS */
    {
        id: 'grounded',
        layout: 'chat',
        kicker: 'The differentiator',
        title: 'Answers you can trust — and verify',
        subtitle: 'Inline citations, a live confidence score and downloadable sources. No black box.',
        pairs: [
            {
                q: "What's the late submission policy for CS401?",
                a: 'Submissions are accepted up to 72 hours late with a 10% daily penalty. Beyond that a grade of zero applies unless an extension was approved.',
                confidence: '96% confidence',
                source: 'CS401 Syllabus · p.4',
            },
            {
                q: 'When is the database systems midterm?',
                a: 'The CS302 midterm is on March 14th, 9:00 AM in Hall B. It covers indexing, normalization and transaction control.',
                confidence: '92% confidence',
                source: 'Exam Timetable · Spring',
            },
        ],
        keywords: [
            { icon: 'CheckBadgeIcon', label: 'Confidence score', desc: 'High / Medium / Low, computed from retrieval similarity.' },
            { icon: 'DocumentTextIcon', label: 'Cited sources', desc: 'Every claim traces back to an approved document & page.' },
            { icon: 'GlobeAltIcon', label: 'Multi-language', desc: 'English & Bangla out of the box, configurable per institution.' },
        ],
    },

    /* --------------------------------------------------- 08b CORE PLATFORM */
    {
        id: 'core-platform',
        layout: 'grid',
        kicker: 'Core platform',
        title: 'One platform, every academic workflow',
        columns: 3,
        items: [
            { icon: 'SparklesIcon', title: 'Agentic AI tutor', desc: 'Streaming, cited answers — and real actions: it books office hours, builds quizzes and plans tasks for you.' },
            { icon: 'DocumentTextIcon', title: 'Document knowledge base', desc: 'Upload → review → approve; approved docs auto-chunk, embed and become searchable.' },
            { icon: 'Squares2X2Icon', title: 'Role-based dashboards', desc: 'Student, faculty and admin each get a tailored home with the tools that matter.' },
            { icon: 'AcademicCapIcon', title: 'AI teaching automation', desc: 'Generate quizzes, assignments and rubrics, and draft grading feedback.' },
            { icon: 'ClipboardDocumentCheckIcon', title: 'Timed & proctored tests', desc: 'In-panel quizzes with a live countdown, anti-cheat lockdown and instant auto-grading — plus self-serve practice quizzes.' },
            { icon: 'ChatBubbleLeftRightIcon', title: 'Messaging & community', desc: 'Live 1:1 faculty chat, group study rooms, section discussion feeds and an opt-in leaderboard.' },
            { icon: 'ChartBarIcon', title: 'Analytics & early warning', desc: 'One-tap attendance; GPA and engagement per course; at-risk students flagged on four signals.' },
            { icon: 'LightBulbIcon', title: 'Study suite', desc: 'AI study planner, SM-2 flashcards, "My Progress" analytics and OCR of handwritten notes.' },
            { icon: 'ShieldCheckIcon', title: 'Granular RBAC + audit', desc: 'Fine-grained, time-limited permissions — every action gated and logged.' },
            { icon: 'BookOpenIcon', title: 'Registration, exams & terms', desc: 'Assign → confirm rostering, exam schedules, GPA transcripts and a unified calendar.' },
        ],
    },

    /* ------------------------------------------------- 08b JULY 2026 WAVE */
    {
        id: 'new-wave',
        layout: 'grid',
        kicker: 'New · July 2026 wave',
        title: 'The copilot now acts, assesses and listens',
        columns: 3,
        items: [
            { icon: 'BoltIcon', title: 'Agentic chat actions', desc: 'The AI tutor books office hours, checks deadlines, generates quizzes & flashcards, adds tasks — visible action trail, one-tap Agent / Answers-only switch.' },
            { icon: 'MagnifyingGlassIcon', title: 'Similarity screening', desc: 'Every submission is embedded and compared; suspicious pairs surface at grading with matching excerpts.' },
            { icon: 'Squares2X2Icon', title: 'Concept mastery map', desc: 'Per-topic mastery from tests, practice and flashcards — weak spots get one-click targeted review.' },
            { icon: 'CheckBadgeIcon', title: 'AI rubric grading', desc: 'AI drafts per-criterion scores + feedback from the real submission; faculty review before anything releases.' },
            { icon: 'ChatBubbleLeftRightIcon', title: 'Anonymous course feedback', desc: 'Mid-semester pulse per section with an anonymity floor and AI-summarized themes.' },
            { icon: 'UsersIcon', title: 'Peer review', desc: 'Students review two anonymous classmate submissions each — anonymous in both directions.' },
            { icon: 'BookOpenIcon', title: 'Prerequisites & waitlists', desc: 'Registration enforces completed prerequisites; full sections queue students and auto-promote on drops.' },
            { icon: 'ClipboardDocumentCheckIcon', title: 'Question bank', desc: 'Per-course reusable questions — import from tests, spin up draft exams, let students practice from it.' },
            { icon: 'BellAlertIcon', title: 'Email digests & nudges', desc: 'Weekly week-ahead email and due-soon reminders, opt-out per student, on your own SMTP.' },
        ],
    },

    /* ------------------------------------------------------- 09 STUDENT GRID */
    {
        id: 'student-features',
        layout: 'grid',
        kicker: 'Student experience',
        title: 'Everything a student needs, in one place',
        columns: 3,
        items: [
            { icon: 'SparklesIcon', title: 'AI Tutor that acts', desc: 'Grounded, cited chat that also books office hours, builds quizzes and plans your tasks — with a one-tap Agent / Answers-only switch.' },
            { icon: 'ClipboardDocumentCheckIcon', title: 'Class Tests', desc: 'Timed, proctored, auto-graded with instant review.' },
            { icon: 'BoltIcon', title: 'Practice Quizzes', desc: 'AI quizzes on any topic or straight from the faculty question bank; misses become flashcards.' },
            { icon: 'PencilSquareIcon', title: 'Assignments + Peer Review', desc: 'Submit, track, read rubrics — and review two anonymous classmates.' },
            { icon: 'BookOpenIcon', title: 'Materials & Roadmap', desc: 'Course content with per-item completion tracking.' },
            { icon: 'ChartBarIcon', title: 'Progress & Mastery', desc: 'Transcript, attendance, trend analytics and a per-concept mastery map with one-click review.' },
            { icon: 'LightBulbIcon', title: 'Planner, Flashcards & OCR', desc: 'AI study schedules, SM-2 spaced repetition, handwriting → searchable notes.' },
            { icon: 'UsersIcon', title: 'Community', desc: 'Section discussions, opt-in leaderboard, study rooms & office-hours booking.' },
            { icon: 'MagnifyingGlassIcon', title: '⌘K Semantic Search', desc: 'One palette over docs, notes, courses, discussions & chats — plus .ics calendar sync.' },
        ],
    },

    /* --------------------------------------------------- 10 STUDENT WORKFLOW */
    {
        id: 'student-journey',
        layout: 'workflow',
        kicker: 'Workflow · Student asks the AI',
        title: 'Question to grounded answer in seconds',
        steps: [
            { title: 'Ask', desc: 'Student types a question in the AI tutor chat.' },
            { title: 'Retrieve', desc: 'System embeds the query and finds the most relevant approved chunks.' },
            { title: 'Ground', desc: 'The model answers strictly from retrieved context.' },
            { title: 'Cite & save', desc: 'Sources, confidence and follow-ups attached — bookmark for later.' },
        ],
    },

    /* ------------------------------------------------------- 11 FACULTY GRID */
    {
        id: 'faculty-features',
        layout: 'grid',
        kicker: 'Faculty experience',
        title: 'An AI co-teacher for every instructor',
        columns: 3,
        items: [
            { icon: 'SparklesIcon', title: 'AI Assistant', desc: 'ChatGPT-style workspace, course-aware context.' },
            { icon: 'BeakerIcon', title: 'AI Quiz Generator', desc: 'Topic → MCQ / true-false with explanations, editable.' },
            { icon: 'PencilSquareIcon', title: 'Assignment Generator', desc: 'Auto-draft description, tasks and rubric.' },
            { icon: 'CheckBadgeIcon', title: 'AI-Assisted Grading', desc: 'Draft per-criterion rubric scores + feedback from the submission — review, edit, release.' },
            { icon: 'ChartBarIcon', title: 'Learning Analytics', desc: 'Class performance & at-risk student flags.' },
            { icon: 'UserGroupIcon', title: 'Courses & Attendance', desc: 'Materials, rosters and attendance per section.' },
            { icon: 'ClipboardDocumentCheckIcon', title: 'Question Bank', desc: 'Reusable per-course questions; import from tests, build draft exams.' },
            { icon: 'MagnifyingGlassIcon', title: 'Similarity Screening', desc: 'Flagged submission pairs with side-by-side excerpts at grading.' },
            { icon: 'ChatBubbleLeftRightIcon', title: 'Course Feedback', desc: 'Anonymous section pulse with AI-summarized themes.' },
        ],
    },

    /* --------------------------------------------------- 12 FACULTY WORKFLOW */
    {
        id: 'faculty-journey',
        layout: 'workflow',
        kicker: 'Workflow · Faculty creates an exam',
        title: 'From topic to live exam in one flow',
        accent: 'emerald',
        steps: [
            { title: 'Generate', desc: 'AI drafts a quiz from a topic, difficulty & Bloom level.' },
            { title: 'Refine', desc: 'Faculty edits questions, options and answer keys.' },
            { title: 'Publish', desc: 'One click → assignment OR interactive proctored class test.' },
            { title: 'Auto-grade', desc: 'Students notified; objective answers graded instantly.' },
        ],
    },

    /* --------------------------------------------------- 13 PROCTORED TESTS */
    {
        id: 'proctoring',
        layout: 'grid',
        kicker: 'Proctored class tests',
        title: 'Real exam integrity, in the browser',
        columns: 2,
        items: [
            { icon: 'EyeIcon', title: 'Fullscreen enforcement', desc: 'Tab-switch & fullscreen-exit detection with warnings.' },
            { icon: 'ExclamationTriangleIcon', title: 'Auto-disqualify', desc: 'Exceed the warning limit → attempt auto-submitted.' },
            { icon: 'ClockIcon', title: 'Per-student timer', desc: 'Countdown begins on start, not globally — fair to all.' },
            { icon: 'ShieldCheckIcon', title: 'Server-authoritative grading', desc: 'Answers never trusted from the client; graded on the server.' },
        ],
    },

    /* ------------------------------------------------ 13b REAL-TIME MESSAGING */
    {
        id: 'messaging',
        layout: 'grid',
        kicker: 'Real-time messaging',
        title: 'Students and faculty, talking live',
        columns: 2,
        items: [
            { icon: 'ChatBubbleLeftRightIcon', title: 'Instant 1:1 chat', desc: 'Direct student↔faculty messaging — separate from the AI tutor. Messages land in real time, gated to people who share a section.' },
            { icon: 'SignalIcon', title: 'Presence & typing', desc: 'Green “active now” when the other person is in the app, a live typing indicator, and last-seen — Messenger-style.' },
            { icon: 'BellAlertIcon', title: 'Smart conversation list', desc: 'Newest thread jumps to the top with a last-message preview and an unread badge that clears as you read.' },
            { icon: 'ShieldCheckIcon', title: 'Database-backed & reliable', desc: 'Every message is persisted first, then broadcast; a polling fallback guarantees delivery even if the socket drops.' },
        ],
    },

    /* ------------------------------------------- 13c REAL-TIME ARCHITECTURE */
    {
        id: 'realtime-scale',
        layout: 'pillars',
        kicker: 'Realtime, honestly',
        title: 'Built to run anywhere — even shared hosting',
        subtitle: 'Live chat ships on a hosted broadcaster (Ably). Here is the trade-off and the path to production scale.',
        accent: 'warning',
        items: [
            { icon: 'ServerStackIcon', title: 'The constraint', desc: 'cPanel shared hosting can’t run a persistent WebSocket server or queue worker — so the free Ably tier (≈200 concurrent connections) is the cap, and chat is text-only.' },
            { icon: 'BoltIcon', title: 'How we stay within it', desc: 'Synchronous broadcast (no queue), DB as source of truth, heartbeat presence instead of always-on sockets, and a poll fallback — no connection wasted.' },
            { icon: 'ArrowTrendingUpIcon', title: 'Path to production', desc: 'Lift the cap by upgrading the Ably plan, or self-host Laravel Reverb on a VPS with a queue worker. Same Echo client — only the broadcaster swaps.' },
        ],
    },

    /* --------------------------------------------------------- 14 ADMIN GRID */
    {
        id: 'admin-features',
        layout: 'grid',
        kicker: 'Admin experience',
        title: 'Govern the platform end-to-end',
        columns: 3,
        items: [
            { icon: 'UsersIcon', title: 'User & Role Matrix', desc: 'Create users, edit the role→permission matrix live.' },
            { icon: 'FolderOpenIcon', title: 'Document Approval', desc: 'Review, comment, approve → auto-embed into RAG.' },
            { icon: 'CpuChipIcon', title: 'AI Usage Monitor', desc: 'Per-user tokens & requests; block / unblock access.' },
            { icon: 'BookOpenIcon', title: 'Catalog & Terms', desc: 'Courses, sections, departments, term rollover.' },
            { icon: 'BellAlertIcon', title: 'Announcements', desc: 'Broadcast notifications fan out to every user.' },
            { icon: 'Cog6ToothIcon', title: 'AI Settings', desc: 'Temperature, top-K, similarity threshold, prompts.' },
        ],
    },

    /* ----------------------------------------------- 15 GOVERNANCE / CONTROL */
    {
        id: 'governance',
        layout: 'pillars',
        kicker: 'Governed AI',
        title: 'Institutions stay in control',
        subtitle: 'AI on campus is curated, measured and accountable — never a free-for-all.',
        items: [
            { icon: 'CheckBadgeIcon', title: 'Approve before index', desc: 'Only admin-approved documents ever reach the AI.' },
            { icon: 'CpuChipIcon', title: 'Measure every token', desc: 'Per-user usage tracking with block / unblock controls.' },
            { icon: 'EyeIcon', title: 'Audit everything', desc: 'Every major action logged with user and timestamp.' },
        ],
    },

    /* ------------------------------------------------------------ 16 METRICS */
    {
        id: 'metrics',
        layout: 'metrics',
        kicker: 'By the numbers',
        title: 'A mature, demonstrable MVP',
        stats: [
            { value: '3', label: 'Role dashboards', sub: 'Student · Faculty · Admin' },
            { value: '40+', label: 'Permissions', sub: 'Fine-grained RBAC matrix' },
            { value: '100%', label: 'Core workflows', sub: 'Wired end-to-end' },
            { value: '0', label: 'External AI deps', sub: 'No vector DB, no LLM SDK' },
        ],
    },

    /* ------------------------------------------------------- 17 PLUGGABLE AI */
    {
        id: 'pluggable',
        layout: 'pillars',
        kicker: 'No lock-in',
        title: 'Swap the brain, keep the platform',
        subtitle: 'Provider logic lives behind an interface — demo today, production tomorrow.',
        items: [
            { icon: 'BeakerIcon', title: 'Mock provider (default)', desc: 'Ships in deterministic demo mode — full walkthrough with zero API keys.' },
            { icon: 'SparklesIcon', title: 'OpenAI provider', desc: 'Drop in a key for production-grade generation.' },
            { icon: 'PuzzlePieceIcon', title: 'Future providers', desc: 'Gemini or local LLMs plug into the same contract.' },
        ],
    },

    /* ------------------------------------------------------ 18 SEE IT LIVE */
    {
        id: 'demo',
        layout: 'demo',
        kicker: 'See it in action',
        title: 'A guided tour of the platform',
        // Real screenshots captured from the running app (light theme) — see
        // scripts/capture-demo.mjs. Six core screens per role.
        frames: [
            /* -------- Student -------- */
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-chat.png', path: '/chat',
                title: 'AI Tutor — Grounded Chat',
                caption: 'Ask anything about your courses; every answer is drawn from approved university documents.',
                points: ['Cited sources + confidence score', 'Academic & exam-prep modes', 'Saved answers and chat history'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-roadmap.png', path: '/roadmap',
                title: 'Learning Roadmap',
                caption: 'A personalised, semester-by-semester journey with live progress.',
                points: ['Per-module completion %', 'AI study recommendations', 'Timeline & grid views'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-dashboard.png', path: '/dashboard',
                title: 'Student Dashboard',
                caption: 'A personalised academic hub — progress, deadlines and one-tap actions.',
                points: ['Live CGPA & attendance', 'Upcoming assignment deadlines', 'Quick access to AI chat & roadmap'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-class-tests.png', path: '/class-tests',
                title: 'Proctored Class Tests',
                caption: 'Timed, auto-graded quizzes set by instructors — with anti-cheat enforcement.',
                points: ['Duration, questions & marks upfront', 'Fullscreen lockdown on start', 'Instant results & answer review'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-transcript.png', path: '/transcript',
                title: 'Transcript & Grades',
                caption: 'A complete academic record, grouped by semester.',
                points: ['Cumulative GPA & credits', 'Per-course grades & points', 'Print-ready transcript'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-assignments.png', path: '/assignments',
                title: 'Assignments & Submissions',
                caption: 'Submit work and track grades across every course.',
                points: ['To-do · submitted · graded filters', 'Due dates & point values', 'One-click file submission'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-exams.png', path: '/exams',
                title: 'Exam Schedule',
                caption: 'Upcoming and past exams for every enrolled course.',
                points: ['Date, time, hall & marks', 'Midterm & final countdown', 'Exam-day instructions'],
            },
            {
                role: 'Student', tint: '#0891b2', image: '/demo/student-materials.png', path: '/materials',
                title: 'Course Materials',
                caption: 'All your course content, organised by week.',
                points: ['Per-course completion tracking', 'Weekly progress overview', 'Search & download materials'],
            },

            /* -------- Faculty -------- */
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-ai.png', path: '/faculty/ai-assistant',
                title: 'AI Teaching Assistant',
                caption: 'Generate ready-to-publish quizzes and assignments from a single topic.',
                points: ['MCQ · True/False · Essay types', 'Difficulty & Bloom-level control', 'Edit, then publish in one click'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-courses.png', path: '/faculty/courses',
                title: 'My Courses',
                caption: 'Every section you teach, with class progress and AI shortcuts.',
                points: ['Live class-progress bars', 'Generate quiz / assignment', 'Manage materials per section'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-analytics.png', path: '/faculty/analytics',
                title: 'Learning Analytics',
                caption: 'Performance and attendance across every course you teach.',
                points: ['At-risk student flags', 'Grade distribution charts', 'Attendance & pending-grading totals'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-class-tests.png', path: '/faculty/class-tests',
                title: 'Class Test Authoring',
                caption: 'Create, publish and track timed quizzes for each of your sections.',
                points: ['Per-section publishing', 'Live results & analytics', 'Edit or close at any time'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-students.png', path: '/faculty/students',
                title: 'My Students',
                caption: 'The full roster across the courses and sections you teach.',
                points: ['Filter by course & section', 'Student IDs & enrolment', 'Direct message a student'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-dashboard.png', path: '/faculty/dashboard',
                title: 'Faculty Dashboard',
                caption: 'Teaching at a glance — courses, students and pending work.',
                points: ['Active courses & total students', 'Pending grading count', 'AI quick-actions panel'],
            },
            {
                role: 'Faculty', tint: '#7c3aed', image: '/demo/faculty-exams.png', path: '/faculty/exams',
                title: 'Exam Timetable',
                caption: 'Scheduled exams across the courses you teach.',
                points: ['Per-course exam cards', 'Date, time & venue', 'Managed by administration'],
            },

            /* -------- Admin -------- */
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-documents.png', path: '/admin/documents',
                title: 'RAG Knowledge Base',
                caption: 'Curate exactly which documents the AI is allowed to answer from.',
                points: ['Approve → auto-embed into RAG', 'Department-scoped visibility', 'Preview, version & download'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-roles.png', path: '/admin/roles',
                title: 'Roles & Permissions',
                caption: 'A live matrix of every permission each role holds.',
                points: ['40+ granular permissions', 'Toggle per role, applied instantly', 'Protected system administrator'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-users.png', path: '/admin/users',
                title: 'User Management',
                caption: 'Manage every account with full role control.',
                points: ['Create · edit · deactivate', 'Assign roles (with expiry)', 'Search by role & status'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-courses.png', path: '/admin/courses',
                title: 'Course Catalog',
                caption: 'The institution-wide catalog and section offerings.',
                points: ['Hundreds of courses & sections', 'Assign faculty to sections', 'Filter by department & semester'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-analytics.png', path: '/admin/analytics',
                title: 'System Analytics',
                caption: 'Platform-wide visibility for administrators.',
                points: ['Users by role breakdown', 'Query & token trends', 'Document library stats'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-departments.png', path: '/admin/departments',
                title: 'Departments',
                caption: 'Academic departments with their codes and rollups.',
                points: ['User & course counts', 'Active / inactive status', 'Create, edit & manage'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-exams.png', path: '/admin/exams',
                title: 'Exam Management',
                caption: 'Schedule exams across courses — students are notified automatically.',
                points: ['Hundreds of scheduled exams', 'Filter by course, type & date', 'Auto-notify enrolled students'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-terms.png', path: '/admin/terms',
                title: 'Academic Terms',
                caption: 'Manage terms and run the end-of-term rollover.',
                points: ['Set the current term', 'Open / close registration', 'Section & enrolment counts'],
            },
            {
                role: 'Admin', tint: '#059669', image: '/demo/admin-announcements.png', path: '/admin/announcements',
                title: 'Announcements',
                caption: 'Broadcast a notification to any group of users.',
                points: ['Target everyone or a role', 'Fan-out to all recipients', 'History of recent sends'],
            },
        ],
    },

    /* ------------------------------------------------------------ 19 ROADMAP */
    {
        id: 'roadmap',
        layout: 'roadmap',
        kicker: 'Where it goes next',
        title: 'Shipped, and what is coming',
        shipped: ['Streaming RAG tutor + citations', 'Agentic chat actions (8 tools)', 'Chat with my materials (personal RAG)', 'AI quiz → proctored exam', 'Layered exam-security proctoring', 'AI practice quizzes + question bank', 'AI rubric grading (human-in-the-loop)', 'Submission similarity screening', 'Anonymous peer review', 'Anonymous course feedback + AI themes', 'Concept mastery map', 'Prerequisites + section waitlists', 'Email digests & deadline nudges', 'At-risk early warning', 'Study planner + SM-2 flashcards', 'OCR handwritten notes', 'Discussions, leaderboard, study rooms', 'Office-hours booking', '⌘K semantic search', '.ics calendar sync', 'RBAC + audit log', 'Document approval pipeline', 'AI usage governance'],
        upcoming: ['Telegram / WhatsApp alerts', 'Voice input / output', 'Managed vector DB at scale'],
    },

    /* ------------------------------------------------------------ 19 IMPACT */
    {
        id: 'impact',
        layout: 'statement',
        kicker: 'The impact',
        title: 'Less paperwork. More learning.',
        accent: 'success',
        points: [
            { icon: 'BoltIcon', title: 'Instant, trusted answers', desc: 'Students self-serve from grounded, cited knowledge 24/7.' },
            { icon: 'ArrowTrendingUpIcon', title: 'Faculty multiplied', desc: 'AI drafts quizzes, exams and feedback — humans review, not author.' },
            { icon: 'ShieldCheckIcon', title: 'Administration in control', desc: 'Full visibility and governance over AI on campus.' },
        ],
    },

    /* ------------------------------------------------------------ 20 CLOSING */
    {
        id: 'closing',
        layout: 'closing',
        title: 'UniGPT',
        subtitle: 'The AI academic copilot your campus can actually trust.',
        points: ['100% core workflows · end-to-end', '0 external AI dependencies', 'Grounded · Governed · Proctored'],
        cta: 'Ready to pilot on your campus this term →',
    },
];
