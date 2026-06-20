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
                            Ask anything. UniGPT retrieves passages from your approved documents, answers with inline
                            citations and a confidence score, and suggests follow-ups. Multiple modes — academic,
                            exam prep, research — keep responses on-task.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="ui-status-primary">Citations</span>
                            <span class="ui-status-primary">Confidence scoring</span>
                            <span class="ui-status-primary">Chat modes</span>
                            <span class="ui-status-primary">Saved answers</span>
                            <span class="ui-status-primary">Session history</span>
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
                    <span class="ui-icon-tile bg-accent-rose-soft text-accent-rose-fg"><ClipboardDocumentCheckIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Registration & rostering</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Admins assign students to course sections; students confirm in one click — seats, terms and rosters stay consistent.</p>
                </div>

                <div v-reveal="120" class="reveal ui-card ui-card-hover p-6 md:col-span-2">
                    <span class="ui-icon-tile bg-accent-yellow-soft text-accent-yellow-fg"><CalendarDaysIcon class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">Exams, transcripts & calendar</h3>
                    <p class="mt-1.5 text-sm text-content-muted">Schedule exams, auto-build transcripts with GPA & CGPA, and merge every deadline into one calendar.</p>
                </div>
            </div>
        </div>
    </section>
</template>
