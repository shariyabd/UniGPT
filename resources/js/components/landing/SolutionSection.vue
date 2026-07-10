<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    ChatBubbleBottomCenterTextIcon,
    CpuChipIcon,
    SparklesIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const steps = [
    {
        icon: ChatBubbleBottomCenterTextIcon,
        tone: 'primary',
        step: 'Ask',
        title: 'Ask in plain language',
        body: 'A student, lecturer or admin asks a question — about a policy, a deadline, a grade, or campus data.',
    },
    {
        icon: CpuChipIcon,
        tone: 'sky',
        step: 'Ground',
        title: 'Retrieve & reason',
        body: 'UniNexus searches your approved documents, pulls the most relevant passages, and reasons over them — never guessing.',
    },
    {
        icon: SparklesIcon,
        tone: 'mint',
        step: 'Answer',
        title: 'Answer with proof',
        body: 'You get a precise answer with citations, a confidence score, and suggested follow-ups you can trust and save.',
    },
];
</script>

<template>
    <section id="solution" class="relative py-24">
        <div class="aurora -left-20 top-10 h-72 w-72 bg-primary/30"></div>
        <div class="page-container relative">
            <SectionHeading
                eyebrow="The solution"
                title="From question to grounded answer in one flow"
                subtitle="UniNexus turns your institutional knowledge into an assistant that cites its sources."
            />

            <div class="mt-14 grid items-stretch gap-5 lg:grid-cols-3">
                <template v-for="(s, i) in steps" :key="s.step">
                    <div v-reveal="i * 110" class="reveal relative">
                        <div class="ui-card h-full p-7">
                            <div class="flex items-center gap-3">
                                <span
                                    class="ui-icon-tile"
                                    :class="{
                                        'bg-primary-soft text-primary': s.tone === 'primary',
                                        'bg-accent-sky-soft text-accent-sky-fg': s.tone === 'sky',
                                        'bg-accent-mint-soft text-accent-mint-fg': s.tone === 'mint',
                                    }"
                                >
                                    <component :is="s.icon" class="h-5 w-5" />
                                </span>
                                <span class="text-xs font-bold uppercase tracking-[0.16em] text-content-faint">
                                    Step {{ i + 1 }} · {{ s.step }}
                                </span>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold text-content">{{ s.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-content-muted">{{ s.body }}</p>
                        </div>

                        <!-- connector arrow -->
                        <div
                            v-if="i < steps.length - 1"
                            class="absolute -right-3.5 top-1/2 z-10 hidden -translate-y-1/2 lg:flex"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-surface text-primary shadow-card">
                                <ArrowRightIcon class="h-4 w-4" />
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>
</template>
