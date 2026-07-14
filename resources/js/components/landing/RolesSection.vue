<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import SectionHeading from './SectionHeading.vue';
import {
    AcademicCapIcon,
    PresentationChartLineIcon,
    Cog6ToothIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const tabs = [
    {
        key: 'student',
        label: 'Student',
        icon: AcademicCapIcon,
        headline: 'A copilot for every academic question',
        blurb: 'From the first lecture to final transcript, students get instant, cited answers and a planner that keeps the term on track.',
        features: [
            'Streaming AI chat with citations — grounded in the library and your own notes & materials',
            'Personal dashboard: courses, CGPA & deadlines',
            'One-click registration for assigned course sections',
            'Timed quizzes & class tests with instant auto-graded results',
            'Self-serve AI practice quizzes — missed questions become flashcards',
            'AI Study Planner, SM-2 Flashcards & "My Progress" analytics',
            'OCR handwritten notes — photo → searchable, AI-readable note',
            'Course discussions & opt-in gamified leaderboard',
            'Attendance, transcript & GPA tracking',
            'Course roadmap, materials & document library',
            '⌘K semantic search across everything you can access',
            'Exam schedule, calendar (.ics sync), notes & tasks',
            'Real-time messaging, group study rooms & office-hours booking',
        ],
        metrics: [
            { label: 'Courses', value: '6' },
            { label: 'CGPA', value: '3.78' },
            { label: 'Attendance', value: '94%' },
        ],
        chart: [38, 56, 48, 70, 62, 84, 92],
    },
    {
        key: 'faculty',
        label: 'Faculty',
        icon: PresentationChartLineIcon,
        headline: 'Teach more, administrate less',
        blurb: 'Faculty manage courses end to end while the AI teaching assistant drafts assessments and feedback in seconds.',
        features: [
            'Streaming AI teaching assistant: quizzes, assignments & rubrics',
            'Build timed quizzes & class tests with auto-grading',
            'Per-test proctoring layers — lockdown, watermark, face-liveness gate, phone detection, snapshot evidence, risk-scored review dossier',
            'Manage taught sections & publish course materials',
            'One-click attendance with live class stats',
            'Grading workspace with AI-drafted feedback',
            'At-risk early warning: four signals, one-click intervention',
            'Publish bookable office-hours slots',
            'Per-course analytics & grade distributions',
            'Join & moderate your sections\' discussion feeds',
            'Real-time messaging with your students',
        ],
        metrics: [
            { label: 'Courses', value: '4' },
            { label: 'Students', value: '128' },
            { label: 'To grade', value: '12' },
        ],
        chart: [45, 40, 62, 58, 76, 70, 88],
    },
    {
        key: 'admin',
        label: 'Admin',
        icon: Cog6ToothIcon,
        headline: 'Total control, live oversight',
        blurb: 'Admins govern users, knowledge and the AI itself — with a real-time view of system and academic health.',
        features: [
            'User, role & permission matrix management',
            'Course catalog, sections, terms & student assignment',
            'Document approval workflow & knowledge base',
            'Institution-wide analytics & top queries',
            'AI provider settings, prompts & retrieval tuning',
            'Exam-security gate: which proctoring layers faculty may use, with a built-in layer guide',
            'Discussion moderation queue for reported posts & comments',
            'AI usage monitor with per-user access control',
            'System monitor, departments & announcements',
        ],
        metrics: [
            { label: 'Users', value: '2.4k' },
            { label: 'Docs', value: '860' },
            { label: 'Uptime', value: '99.9%' },
        ],
        chart: [50, 60, 55, 72, 68, 82, 90],
    },
];

const active = ref('student');
const current = computed(() => tabs.find((t) => t.key === active.value));

// Animate the dashboard chart from flat → full the first time the section
// scrolls into view (and again on every tab switch, since the panel remounts).
const chartVisible = ref(false);
const sectionRef = ref(null);
let observer = null;

onMounted(() => {
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduced || typeof IntersectionObserver === 'undefined') {
        chartVisible.value = true;
        return;
    }
    observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => e.isIntersecting && (chartVisible.value = true)),
        { threshold: 0.3 },
    );
    if (sectionRef.value) observer.observe(sectionRef.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <section id="roles" ref="sectionRef" class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="Built for three"
                title="One platform, three tailored experiences"
                subtitle="Switch roles to see exactly what each user gets the moment they sign in."
            />

            <!-- Tab switcher -->
            <div v-reveal class="reveal mt-12 flex justify-center">
                <div class="inline-flex flex-wrap justify-center gap-1 rounded-pill border border-line bg-surface p-1.5 shadow-card">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-pill px-5 py-2.5 text-sm font-semibold transition-all duration-200"
                        :class="active === tab.key ? 'bg-primary text-white shadow-card' : 'text-content-muted hover:text-primary'"
                        @click="active = tab.key"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Panel -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-3"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div :key="current.key" class="mt-10 grid items-center gap-8 lg:grid-cols-2">
                    <!-- Copy + checklist -->
                    <div>
                        <span class="ui-icon-tile bg-primary text-white"><component :is="current.icon" class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-2xl font-bold tracking-tight text-content">{{ current.headline }}</h3>
                        <p class="mt-3 text-content-muted">{{ current.blurb }}</p>

                        <ul class="mt-6 space-y-3">
                            <li v-for="f in current.features" :key="f" class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-success-bg text-success-fg">
                                    <CheckIcon class="h-3.5 w-3.5" />
                                </span>
                                <span class="text-sm text-content">{{ f }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Mock dashboard -->
                    <div class="relative">
                        <div class="absolute -inset-3 rounded-card bg-primary/15 blur-2xl"></div>
                        <div class="ui-card relative overflow-hidden p-6">
                            <div class="flex items-center justify-between border-b border-line pb-4">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-control bg-primary text-white">
                                        <component :is="current.icon" class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-content">{{ current.label }} dashboard</p>
                                        <p class="text-xs text-content-faint">UniNexus workspace</p>
                                    </div>
                                </div>
                                <span class="ui-status-success">live</span>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-3">
                                <div
                                    v-for="m in current.metrics"
                                    :key="m.label"
                                    class="rounded-control border border-line bg-bg p-3 text-center"
                                >
                                    <p class="text-xl font-bold text-content">{{ m.value }}</p>
                                    <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-content-faint">{{ m.label }}</p>
                                </div>
                            </div>

                            <!-- live bar chart: grows low → high on scroll-in and tab switch -->
                            <div class="mt-4 rounded-control border border-line bg-bg p-4">
                                <div class="flex h-24 items-end justify-between gap-1.5">
                                    <div
                                        v-for="(h, i) in current.chart"
                                        :key="i"
                                        class="chart-bar w-full rounded-t bg-primary/70"
                                        :class="{ 'is-live': chartVisible }"
                                        :style="{ height: h + '%', animationDelay: (i * 0.09) + 's' }"
                                    ></div>
                                </div>
                                <p class="mt-3 text-xs text-content-muted">Weekly activity</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </section>
</template>

<style scoped>
/* Bars sit flat until the chart is "live", then grow from the baseline up.
   Re-runs on every tab switch because the panel remounts. */
.chart-bar {
    transform: scaleY(0);
    transform-origin: bottom;
}
.chart-bar.is-live {
    animation: chart-grow 0.85s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
@keyframes chart-grow {
    from {
        transform: scaleY(0);
    }
    to {
        transform: scaleY(1);
    }
}
@media (prefers-reduced-motion: reduce) {
    .chart-bar {
        transform: scaleY(1);
        animation: none;
    }
}
</style>
