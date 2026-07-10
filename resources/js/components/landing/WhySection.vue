<script setup>
import SectionHeading from './SectionHeading.vue';
import { XMarkIcon, CheckIcon } from '@heroicons/vue/24/outline';

const rows = [
    { label: 'Answers to policy questions', old: 'Email staff and wait', uni: 'Instant, cited from source docs' },
    { label: 'Attendance', old: 'Paper sheets & spreadsheets', uni: 'One-click marking with live stats' },
    { label: 'Course registration', old: 'Clashing, self-picked sections', uni: 'Admin-assigned, one-click confirm' },
    { label: 'Quizzes & feedback', old: 'Written from scratch each term', uni: 'AI-drafted in seconds, then edited' },
    { label: 'Academic tracking', old: 'Manual GPA spreadsheets', uni: 'Automatic transcript & analytics' },
    { label: 'AI accuracy', old: 'Generic bots that hallucinate', uni: 'Grounded with confidence scores' },
    { label: 'Tooling', old: 'Many disconnected apps', uni: 'One platform, three roles' },
];
</script>

<template>
    <section class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="Why UniNexus"
                title="The difference is grounding"
                subtitle="Traditional systems digitise paperwork. UniNexus removes it."
            />

            <div v-reveal class="reveal mx-auto mt-14 max-w-4xl overflow-hidden rounded-card border border-line bg-surface shadow-card">
                <!-- header -->
                <div class="grid grid-cols-3 border-b border-line bg-bg">
                    <div class="p-4 text-sm font-semibold text-content-muted">Capability</div>
                    <div class="p-4 text-center text-sm font-semibold text-content-muted">Traditional systems</div>
                    <div class="p-4 text-center text-sm font-bold text-primary">UniNexus</div>
                </div>

                <div
                    v-for="(row, i) in rows"
                    :key="row.label"
                    class="grid grid-cols-3 items-center border-b border-line last:border-0"
                    :class="i % 2 === 1 ? 'bg-bg/40' : ''"
                >
                    <div class="p-4 text-sm font-medium text-content">{{ row.label }}</div>
                    <div class="flex items-center justify-center gap-2 p-4 text-center text-sm text-content-muted">
                        <XMarkIcon class="h-4 w-4 flex-shrink-0 text-danger-fg" />
                        <span>{{ row.old }}</span>
                    </div>
                    <div class="flex items-center justify-center gap-2 p-4 text-center text-sm font-medium text-content">
                        <CheckIcon class="h-4 w-4 flex-shrink-0 text-success-fg" />
                        <span>{{ row.uni }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
