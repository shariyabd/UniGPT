<script setup>
import { ref } from 'vue';
import SectionHeading from './SectionHeading.vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';

const faqs = [
    { q: 'What exactly is UniNexus?', a: 'UniNexus is an AI academic copilot for universities. It combines role-based dashboards for students, faculty and admins with a retrieval-augmented assistant that answers questions using your institution\'s own approved documents.' },
    { q: 'How is it different from ChatGPT?', a: 'Generic chatbots answer from general training data and can hallucinate. UniNexus only answers from documents your institution has uploaded and approved, attaches citations and a confidence score to every response, and respects each user\'s permissions.' },
    { q: 'Is the AI mandatory to use?', a: 'No. The AI assistant is one part of the platform. Attendance, grading, transcripts, course management, analytics and admin tooling all work independently — the AI simply makes them faster.' },
    { q: 'Who can use the platform?', a: 'Three roles, each with a tailored experience: students get a learning copilot and planner, faculty get teaching and grading tools, and admins govern users, knowledge and the AI itself.' },
    { q: 'Is our data secure?', a: 'Access is governed by granular role-based permissions with time-limited grants, every action is logged, and document retrieval is scoped so answers never surface content a user isn\'t allowed to see.' },
    { q: 'Can we use our own AI provider?', a: 'Yes. UniNexus ships with an OpenAI integration (gpt-4o and text-embedding-3-small) and a pluggable provider layer, plus a built-in mock provider for offline development and testing.' },
];

const open = ref(0);
const toggle = (i) => (open.value = open.value === i ? -1 : i);
</script>

<template>
    <section id="faq" class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="FAQ"
                title="Questions, answered"
                subtitle="Everything you need to know before you sign in."
            />

            <div class="mx-auto mt-12 max-w-3xl space-y-3">
                <div
                    v-for="(faq, i) in faqs"
                    :key="i"
                    v-reveal="i * 50"
                    class="reveal overflow-hidden rounded-card border border-line bg-surface shadow-card"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-primary-soft/50"
                        @click="toggle(i)"
                    >
                        <span class="text-sm font-semibold text-content sm:text-base">{{ faq.q }}</span>
                        <ChevronDownIcon
                            class="h-5 w-5 flex-shrink-0 text-content-muted transition-transform duration-300"
                            :class="open === i ? 'rotate-180 text-primary' : ''"
                        />
                    </button>
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-60 opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="max-h-60 opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-show="open === i" class="overflow-hidden">
                            <p class="px-5 pb-5 text-sm leading-relaxed text-content-muted">{{ faq.a }}</p>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </section>
</template>
