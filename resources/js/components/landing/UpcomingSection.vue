<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    DevicePhoneMobileIcon,
    BookOpenIcon,
    ArrowRightIcon,
    BoltIcon,
    MicrophoneIcon,
    PresentationChartLineIcon,
    CpuChipIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

// Flagship roadmap items — features in active design, building on the platform that ships today.
// (The July 2026 wave — agentic chat actions, similarity screening, AI rubric grading,
// peer review, anonymous course feedback, concept mastery, prerequisites & waitlists,
// question bank and email digests — has shipped; see the Core platform section above.)
const upcoming = [
    {
        icon: DevicePhoneMobileIcon,
        tile: 'bg-accent-mint-soft text-accent-mint-fg',
        phase: 'Planned',
        title: 'Telegram & WhatsApp alerts',
        body: 'Push the events that matter — new assignments, scheduled quizzes, syllabus changes and announcements — straight to the phones students already use.',
        tags: ['Telegram', 'WhatsApp', 'Opt-in'],
    },
    {
        icon: BookOpenIcon,
        tile: 'bg-accent-lilac-soft text-accent-lilac-fg',
        phase: 'Exploring',
        title: 'Digital library + AI assistant',
        body: 'A library of academic books and resources with an AI assistant that answers strictly from the library’s own content — grounded, cited, and on-syllabus.',
        tags: ['Resource library', 'Grounded AI', 'Citations'],
    },
    {
        icon: BoltIcon,
        tile: 'bg-accent-sky-soft text-accent-sky-fg',
        phase: 'Shipped ✓',
        title: 'July 2026 wave — all live',
        body: 'Agentic chat actions, submission similarity screening, AI rubric grading, anonymous peer review & course feedback, concept mastery map, prerequisites & waitlists, a per-course question bank and email digests — shipped and fully tested.',
        tags: ['9 features', 'Live now', 'Fully tested'],
    },
];

// Deeper platform/engineering work — planned for later phases, mostly infra-dependent.
const engineering = [
    { icon: MicrophoneIcon, title: 'Voice I/O', body: 'Speak to the assistant with speech-to-text & text-to-speech.' },
    { icon: PresentationChartLineIcon, title: 'Predictive analytics', body: 'ML-driven recommendations, building on the shipped rule-based at-risk signals.' },
    { icon: CpuChipIcon, title: 'More AI providers', body: 'Gemini and self-hosted models alongside OpenAI.' },
    { icon: ClockIcon, title: 'Versioning & memory', body: 'Document version history and longer conversation memory.' },
];
</script>

<template>
    <section id="roadmap" class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="On the roadmap"
                title="What's coming next"
                subtitle="The platform keeps growing. These features are in active design — built on the RAG engine and notification system already shipping today."
            />

            <!-- Flagship upcoming features -->
            <div class="mt-14 grid gap-5 md:grid-cols-3">
                <div
                    v-for="(item, i) in upcoming"
                    :key="item.title"
                    v-reveal="i * 60"
                    class="reveal ui-card ui-card-hover relative flex flex-col p-6"
                >
                    <div class="flex items-start justify-between">
                        <span class="ui-icon-tile" :class="item.tile">
                            <component :is="item.icon" class="h-5 w-5" />
                        </span>
                        <span class="ui-status-neutral">{{ item.phase }}</span>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-content">{{ item.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-content-muted">{{ item.body }}</p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span
                            v-for="tag in item.tags"
                            :key="tag"
                            class="inline-flex items-center rounded-pill border border-line bg-bg px-2.5 py-1 text-xs font-medium text-content-muted"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Deeper engineering roadmap (later phases) -->
            <div v-reveal class="reveal mt-5 rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-bold uppercase tracking-[0.12em] text-content">Also on the engineering roadmap</h3>
                    <span class="ui-status-neutral">Later phases</span>
                </div>
                <p class="mt-1.5 text-sm text-content-muted">Deeper platform capabilities — most depend on additional infrastructure.</p>

                <div class="mt-6 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="item in engineering" :key="item.title" class="flex items-start gap-3">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-control border border-line bg-bg text-primary">
                            <component :is="item.icon" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-content">{{ item.title }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-content-muted">{{ item.body }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <p v-reveal class="reveal mt-8 flex items-center justify-center gap-2 text-sm text-content-muted">
                <ArrowRightIcon class="h-4 w-4 text-primary" />
                <span>Have a feature in mind? It probably fits the roadmap — these are shaped by how universities actually work.</span>
            </p>
        </div>
    </section>
</template>
