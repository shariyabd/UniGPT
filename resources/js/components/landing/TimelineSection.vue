<script setup>
import { ref } from 'vue';
import { Motion, useScroll, useReducedMotion } from 'motion-v';
import SectionHeading from './SectionHeading.vue';
import {
    AcademicCapIcon,
    SparklesIcon,
    ChartBarIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';

// Scroll-linked progress line: fills as the timeline scrolls through the viewport.
const track = ref(null);
const { scrollYProgress } = useScroll({
    target: track,
    offset: ['start 75%', 'end 55%'],
});

// prefers-reduced-motion: when true we render items in their final state (no
// entrance transform). Motion doesn't auto-disable JS animations, so we gate the
// `initial` prop manually — `false` tells Motion to skip the enter animation.
const reduce = useReducedMotion();

const steps = [
    {
        icon: AcademicCapIcon,
        phase: 'Weeks 1–2',
        title: 'Enroll & onboard',
        body: 'Register for courses with prerequisite checks, land in your role-tailored dashboard, and meet an AI copilot that already knows your syllabus.',
    },
    {
        icon: SparklesIcon,
        phase: 'Throughout the term',
        title: 'Study smarter',
        body: 'Ask grounded, cited questions about your own materials, turn notes into flashcards and practice quizzes, and book office hours in a click.',
    },
    {
        icon: ChartBarIcon,
        phase: 'Every week',
        title: 'Track your progress',
        body: 'Learning analytics, a concept-mastery map and streaks show exactly where you stand — and early-warning signals flag risks before they bite.',
    },
    {
        icon: TrophyIcon,
        phase: 'Midterms & finals',
        title: 'Ace your assessments',
        body: 'Self-quiz against the question bank, review adaptive weak-spot decks, and walk into exams prepared — with the leaderboard for a little friendly push.',
    },
];
</script>

<template>
    <section id="journey" class="relative overflow-hidden py-24">
        <div class="aurora animate-aurora -right-16 top-24 h-72 w-72 bg-primary/25"></div>

        <div class="page-container relative">
            <SectionHeading
                eyebrow="How it works"
                title="One platform for the whole semester"
                subtitle="From the first day of registration to final exams, UniNexus stays a step ahead — here's the journey a student takes."
            />

            <div ref="track" class="relative mx-auto mt-16 max-w-3xl">
                <!-- Rail (static track) -->
                <div class="absolute bottom-2 left-[21px] top-2 w-0.5 rounded-full bg-line md:left-1/2 md:-translate-x-1/2"></div>
                <!-- Rail fill (scroll-linked) -->
                <Motion
                    as="div"
                    class="absolute bottom-2 left-[21px] top-2 w-0.5 origin-top rounded-full bg-gradient-to-b from-primary to-[#18f5ea] md:left-1/2 md:-translate-x-1/2"
                    :style="{ scaleY: scrollYProgress }"
                />

                <ul class="space-y-10 md:space-y-16">
                    <Motion
                        as="li"
                        v-for="(step, i) in steps"
                        :key="step.title"
                        class="relative"
                        :initial="reduce ? false : { opacity: 0, y: 28 }"
                        :while-in-view="{ opacity: 1, y: 0 }"
                        :in-view-options="{ once: true, margin: '0px 0px -12% 0px' }"
                        :transition="{ duration: 0.55, ease: [0.22, 1, 0.36, 1] }"
                    >
                        <div
                            class="flex items-start gap-5"
                            :class="i % 2 === 1 ? 'md:flex-row-reverse md:text-right' : ''"
                        >
                            <!-- Node -->
                            <span
                                class="relative z-10 flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-card ring-4 ring-bg md:absolute md:left-1/2 md:-translate-x-1/2"
                            >
                                <component :is="step.icon" class="h-5 w-5" />
                            </span>

                            <!-- Card -->
                            <div class="ui-card flex-1 p-6 md:w-[calc(50%-2.5rem)] md:flex-none" :class="i % 2 === 1 ? 'md:mr-auto' : 'md:ml-auto'">
                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-primary">{{ step.phase }}</span>
                                <h3 class="mt-2 text-lg font-semibold text-content">{{ step.title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-content-muted">{{ step.body }}</p>
                            </div>
                        </div>
                    </Motion>
                </ul>
            </div>
        </div>
    </section>
</template>
