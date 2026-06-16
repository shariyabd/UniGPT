<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    DocumentMagnifyingGlassIcon,
    ClockIcon,
    PencilSquareIcon,
    PuzzlePieceIcon,
    QuestionMarkCircleIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const problems = [
    { icon: DocumentMagnifyingGlassIcon, title: 'Answers buried in PDFs', body: 'Policies, syllabi and handbooks are scattered across drives. Students ask staff what a document already answers.' },
    { icon: ClockIcon, title: 'Attendance by hand', body: 'Roll-call on paper and spreadsheets — error-prone, slow to total, and impossible to analyse at scale.' },
    { icon: PencilSquareIcon, title: 'Grading drains hours', body: 'Faculty spend evenings writing feedback and building quizzes from scratch every single term.' },
    { icon: QuestionMarkCircleIcon, title: 'No personalised help', body: 'Generic chatbots hallucinate. They have no idea what your courses, deadlines or rules actually say.' },
    { icon: PuzzlePieceIcon, title: 'Disconnected tools', body: 'Separate apps for chat, records, documents and analytics that never talk to each other.' },
    { icon: ChartBarIcon, title: 'Decisions run late', body: 'Admins lack a live view of engagement and risk, so problems surface long after they could be fixed.' },
];
</script>

<template>
    <section class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="The problem"
                title="Campuses run on busywork the AI era should have ended"
                subtitle="Every role loses hours to tasks software should handle. UniGPT was built to remove them."
            />

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(item, i) in problems"
                    :key="item.title"
                    v-reveal="i * 70"
                    class="reveal ui-card ui-card-hover group p-6"
                >
                    <span class="ui-icon-tile bg-danger-bg text-danger-fg transition-transform duration-300 group-hover:-translate-y-0.5">
                        <component :is="item.icon" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-content">{{ item.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-content-muted">{{ item.body }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
