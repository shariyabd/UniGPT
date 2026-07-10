<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    SparklesIcon,
    DocumentCheckIcon,
    Squares2X2Icon,
    AcademicCapIcon,
    ShieldCheckIcon,
    ChartPieIcon,
    BellAlertIcon,
    ClipboardDocumentCheckIcon,
    CalendarDaysIcon,
    PencilSquareIcon,
    ChatBubbleLeftRightIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    UserGroupIcon,
    ClockIcon,
    BoltIcon,
    RectangleStackIcon,
    CameraIcon,
    TrophyIcon,
    DocumentMagnifyingGlassIcon,
    EnvelopeIcon,
    ScaleIcon,
    ChatBubbleLeftEllipsisIcon,
    UsersIcon,
    QueueListIcon,
    ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';
</script>

<template>
    <section id="features" class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="Core platform"
                title="One platform, every academic workflow"
                subtitle="Real, shipped capabilities — not roadmap promises. Here is what UniGPT does today."
            />

            <div class="mt-14 grid auto-rows-[minmax(0,1fr)] gap-5 md:grid-cols-3">
                <!-- Hero feature: RAG chat -->
                <div v-reveal class="reveal ui-card md:col-span-2 md:row-span-2 relative overflow-hidden p-8">
                    <div class="aurora -right-10 -top-10 h-56 w-56 bg-primary/30"></div>
                    <div class="relative">
                        <span class="ui-icon-tile bg-primary text-white"><SparklesIcon class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-xl font-bold text-content">Grounded RAG assistant — that acts</h3>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-content-muted">
                            Ask anything. UniGPT retrieves passages from your approved documents — and from your own
                            notes and your courses' materials — then answers live, streaming word by word, with inline
                            citations and a confidence score. And it doesn't just answer: ask it to book office hours,
                            check your deadlines, build a practice quiz or plan your week, and it does it — showing a
                            live trail of every action it takes. A one-tap Agent / Answers-only switch decides when
                            it may act — and answers-only is enforced server-side.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="ui-status-primary">Streaming answers</span>
                            <span class="ui-status-primary">Agentic actions</span>
                            <span class="ui-status-primary">Agent / Answers switch</span>
                            <span class="ui-status-primary">Citations</span>
                            <span class="ui-status-primary">Chat with my materials</span>
                            <span class="ui-status-primary">Confidence scoring</span>
                            <span class="ui-status-primary">Saved answers</span>
                        </div>
                    </div>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><DocumentCheckIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Document knowledge base</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Upload, review and approve. Approved docs are chunked, embedded and made searchable automatically.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-mint-soft text-accent-mint-fg"><Squares2X2Icon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Role-based dashboards</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Students, faculty and admins each get a tailored home with the tools and data that matter to them.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-peach-soft text-accent-peach-fg"><AcademicCapIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Teaching automation</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Generate quizzes, assignments and rubrics, and draft grading feedback with an AI teaching assistant.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6 md:col-span-2">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><PencilSquareIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Timed quizzes & class tests</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Faculty build timed online quizzes and class tests in minutes — write questions by hand or generate them with AI, then edit. Students take them in-panel with a live countdown and get auto-graded results the moment they submit.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-danger-bg text-danger-fg"><ShieldCheckIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Layered exam proctoring</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Faculty pick per-test security layers from an admin-approved set — fullscreen, tab-switch & clipboard guards, watermark, fingerprint, webcam/screen recording — with a risk-scored, per-student review dossier.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-lilac-soft text-accent-lilac-fg"><ChartPieIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Attendance & analytics</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Mark attendance in seconds; track rates, grades, GPA and engagement across every course.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-success-bg text-success-fg"><ShieldCheckIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Granular RBAC</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Roles map to fine-grained permissions with time-limited grants — every action is gated and logged.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-warning-bg text-warning-fg"><BellAlertIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Announcements & alerts</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Broadcast to any audience and notify the right people on exams, grades and new materials.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><ChatBubbleLeftRightIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Real-time messaging</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Students and their faculty chat live in-app — with online presence, typing indicators, unread counts and instant delivery, separate from the AI tutor.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-rose-soft text-accent-rose-fg"><ClipboardDocumentCheckIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Registration & rostering</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Admins assign students to course sections; students confirm in one click — seats, terms and rosters stay consistent.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-yellow-soft text-accent-yellow-fg"><CalendarDaysIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Exams, transcripts & calendar</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Schedule exams, auto-build transcripts with GPA & CGPA, and merge every deadline into one calendar — exportable and subscribable as .ics from Google, Outlook or Apple Calendar.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-mint-soft text-accent-mint-fg"><BoltIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">AI practice quizzes</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Students generate their own quizzes on any topic, get instant server-graded results with explanations, retake freely — and turn missed questions into flashcards.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-warning-bg text-warning-fg"><ExclamationTriangleIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">At-risk early warning</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Faculty see struggling students flagged on four live signals — attendance, missed deadlines, test average, grade — with a one-click message to intervene.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-lilac-soft text-accent-lilac-fg"><MagnifyingGlassIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Semantic global search</h3>
                    <p class="mt-1.5 text-sm text-content-muted">One ⌘K palette searches documents, notes, materials, courses, assignments, discussions and past AI chats — by meaning, scoped to what you can access.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><UserGroupIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Group study rooms</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Section-scoped live group chats — classmates create a room per topic, join in one click, and message in real time.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-peach-soft text-accent-peach-fg"><ClockIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Office-hours booking</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Faculty publish bookable slots; students book atomically — no double-booking, both sides notified, meetings land on the calendar.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-yellow-soft text-accent-yellow-fg"><SparklesIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">AI Study Planner</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Feed in your deadlines and UniGPT drafts a realistic study schedule, then saves each session straight to your tasks.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-rose-soft text-accent-rose-fg"><RectangleStackIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Flashcards with spaced repetition</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Build decks by hand or generate them with AI, then review on an SM-2 schedule that surfaces cards right before you'd forget.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-mint-soft text-accent-mint-fg"><ChartPieIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">"My Progress" analytics</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Every student gets a personal insight dashboard — GPA, attendance, test, assignment and activity trends visualised in one place.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><CameraIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">OCR handwritten notes</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Snap a photo of handwritten notes and gpt-4o vision transcribes it into a clean saved note — automatically indexed so the AI tutor can answer from it.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-lilac-soft text-accent-lilac-fg"><ChatBubbleLeftRightIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Discussion feeds</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Every course section gets its own group — post, comment, like and report; faculty moderate their sections and admins run a moderation queue.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-warning-bg text-warning-fg"><TrophyIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Gamified leaderboard</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Opt-in XP rankings by department, semester or section — students choose an alias and compete on their own terms.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-primary-soft text-primary"><BoltIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Agentic chat actions</h3>
                    <p class="mt-1.5 text-sm text-content-muted">The AI tutor takes real actions — book office hours, check deadlines, generate quizzes & flashcards, add planner tasks — through the same permissions as the UI, with a visible action trail.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-warning-bg text-warning-fg"><DocumentMagnifyingGlassIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Submission similarity screening</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Every submission is embedded and compared within its assignment — suspicious pairs surface at grading with side-by-side matching excerpts. A signal for humans, not a verdict.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-mint-soft text-accent-mint-fg"><Squares2X2Icon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Concept mastery map</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Tests, practice quizzes and flashcard recall blend into a per-topic mastery grid — weak concepts get one-click "Practice this" and "Make flashcards".</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-lilac-soft text-accent-lilac-fg"><ScaleIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">AI rubric grading</h3>
                    <p class="mt-1.5 text-sm text-content-muted">One click drafts per-criterion scores with justifications, a suggested grade and feedback from the actual submission — faculty review and edit before anything reaches the student.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-rose-soft text-accent-rose-fg"><ChatBubbleLeftEllipsisIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Anonymous course feedback</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Mid-semester pulse per section: results unlock only past an anonymity floor, comments arrive shuffled, and AI summarizes the themes for the instructor.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-sky-soft text-accent-sky-fg"><UsersIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Anonymous peer review</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Each student who submits reviews two anonymous classmate submissions — anonymous both ways, with average peer ratings feeding the faculty grading view.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-peach-soft text-accent-peach-fg"><QueueListIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Prerequisites & waitlists</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Registration enforces completed prerequisites with met/unmet badges; full sections queue students FIFO and auto-promote the moment a seat frees up.</p>
                </div>

                <div v-reveal class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-accent-yellow-soft text-accent-yellow-fg"><ArchiveBoxIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Question bank</h3>
                    <p class="mt-1.5 text-sm text-content-muted">A reusable per-course bank: add questions, import from past tests, spin up draft exams from a selection — and students practice on it for free, no AI required.</p>
                </div>

                <div v-reveal="60" class="reveal ui-card ui-card-hover p-6">
                    <span class="ui-icon-tile bg-success-bg text-success-fg"><EnvelopeIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Email digests & nudges</h3>
                    <p class="mt-1.5 text-sm text-content-muted">A Monday week-ahead email — deadlines, fresh grades, booked office hours, due flashcards — plus due-soon assignment reminders. Opt-out per student, on your own SMTP.</p>
                </div>
            </div>
        </div>
    </section>
</template>
