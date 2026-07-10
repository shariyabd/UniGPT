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
                        <h3 class="mt-5 text-xl font-bold text-content">Grounded RAG assistant</h3>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-content-muted">
                            Ask anything. UniGPT retrieves passages from your approved documents — and from your own
                            notes and your courses' materials — then answers live, streaming word by word, with inline
                            citations and a confidence score. Multiple modes — academic, exam prep, research — keep
                            responses on-task.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="ui-status-primary">Streaming answers</span>
                            <span class="ui-status-primary">Citations</span>
                            <span class="ui-status-primary">Chat with my materials</span>
                            <span class="ui-status-primary">Confidence scoring</span>
                            <span class="ui-status-primary">Chat modes</span>
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
            </div>
        </div>
    </section>
</template>
