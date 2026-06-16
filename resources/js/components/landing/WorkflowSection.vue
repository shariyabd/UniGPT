<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    Cog6ToothIcon,
    PresentationChartLineIcon,
    AcademicCapIcon,
    SparklesIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

const flow = [
    { icon: Cog6ToothIcon, role: 'Admin', title: 'Sets the foundation', body: 'Creates departments, onboards users, approves documents and tunes the AI.' },
    { icon: PresentationChartLineIcon, role: 'Faculty', title: 'Runs the classroom', body: 'Builds courses, publishes materials, marks attendance and grades with AI help.' },
    { icon: AcademicCapIcon, role: 'Student', title: 'Engages & learns', body: 'Asks grounded questions, tracks progress and plans the term.' },
    { icon: SparklesIcon, role: 'AI', title: 'Analyses everything', body: 'Surfaces top queries, engagement and grade trends across the institution.' },
    { icon: ArrowTrendingUpIcon, role: 'Leadership', title: 'Decides faster', body: 'Acts on live insight instead of stale end-of-term reports.' },
];
</script>

<template>
    <section id="workflow" class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="How it connects"
                title="A single loop, from setup to smarter decisions"
                subtitle="Each role feeds the next. The AI closes the loop by turning daily activity into institutional insight."
            />

            <div class="relative mt-16">
                <!-- connecting line (desktop) -->
                <div class="absolute left-0 right-0 top-7 hidden h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent lg:block"></div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4">
                    <div
                        v-for="(node, i) in flow"
                        :key="node.role"
                        v-reveal="i * 100"
                        class="reveal relative text-center"
                    >
                        <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-line bg-surface shadow-card">
                            <span class="absolute inset-0 rounded-full bg-primary/20 animate-pulse-ring" :style="{ animationDelay: i * 0.4 + 's' }"></span>
                            <component :is="node.icon" class="relative h-6 w-6 text-primary" />
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-[0.14em] text-primary">{{ node.role }}</p>
                        <h3 class="mt-1 font-semibold text-content">{{ node.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-content-muted">{{ node.body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
