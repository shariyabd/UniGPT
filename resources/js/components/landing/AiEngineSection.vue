<script setup>
import SectionHeading from './SectionHeading.vue';
import {
    DocumentTextIcon,
    CircleStackIcon,
    MagnifyingGlassCircleIcon,
    ChatBubbleLeftRightIcon,
    BoltIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const pipeline = [
    { icon: DocumentTextIcon, title: 'Chunk', body: 'Approved documents — and your own notes & course materials — are split into overlapping passages on ingest.' },
    { icon: CircleStackIcon, title: 'Embed', body: 'Each passage becomes a vector and is stored alongside its source.' },
    { icon: MagnifyingGlassCircleIcon, title: 'Retrieve', body: 'Your question is embedded and matched by cosine similarity, top-K.' },
    { icon: ChatBubbleLeftRightIcon, title: 'Answer', body: 'The model reasons over retrieved context, streams the answer live, and cites every claim.' },
];

const highlights = [
    { icon: BoltIcon, title: 'Pluggable providers', body: 'OpenAI gpt-4o + text-embedding-3-small in production, with a built-in mock provider for offline development — both streaming-capable.' },
    { icon: LockClosedIcon, title: 'Scoped to what you can see', body: 'Retrieval only ever touches approved documents you may read, your own notes, and your own sections’ materials — answers never leak.' },
];
</script>

<template>
    <section id="ai-engine" class="relative overflow-hidden py-24">
        <div class="absolute inset-0 grid-backdrop"></div>
        <div class="aurora animate-aurora right-0 top-10 h-80 w-80" style="background:#18f5ea55"></div>

        <div class="page-container relative">
            <SectionHeading
                eyebrow="The intelligence"
                title="Retrieval-augmented, not just a chatbot"
                subtitle="UniNexus grounds every answer in your institution's own documents — so responses are accurate, attributable and auditable."
            />

            <!-- Pipeline -->
            <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(p, i) in pipeline"
                    :key="p.title"
                    v-reveal="i * 90"
                    class="reveal glass relative rounded-card p-6"
                >
                    <span class="absolute right-5 top-5 text-3xl font-bold text-primary/15">{{ i + 1 }}</span>
                    <span class="ui-icon-tile bg-primary-soft text-primary"><component :is="p.icon" class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-semibold text-content">{{ p.title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-content-muted">{{ p.body }}</p>
                </div>
            </div>

            <!-- Highlights -->
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div
                    v-for="(h, i) in highlights"
                    :key="h.title"
                    v-reveal="i * 90"
                    class="reveal ui-card flex items-start gap-4 p-6"
                >
                    <span class="ui-icon-tile flex-shrink-0 bg-primary text-white"><component :is="h.icon" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold text-content">{{ h.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-content-muted">{{ h.body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
