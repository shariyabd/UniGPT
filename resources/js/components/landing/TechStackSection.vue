<script setup>
import SectionHeading from './SectionHeading.vue';

const stack = [
    { name: 'Laravel 11', note: 'Application core' },
    { name: 'Vue 3', note: 'Reactive UI' },
    { name: 'Inertia 2', note: 'SPA bridge' },
    { name: 'Vite', note: 'Build tooling' },
    { name: 'Tailwind CSS', note: 'Design system' },
    { name: 'MySQL', note: 'Data + vectors' },
    { name: 'OpenAI', note: 'gpt-4o · embeddings' },
    { name: 'Ziggy', note: 'Typed routing' },
];
</script>

<template>
    <section class="relative py-24">
        <div class="page-container">
            <SectionHeading
                eyebrow="Under the hood"
                title="A modern, maintainable stack"
                subtitle="Production-grade foundations chosen for speed, clarity and long-term maintainability."
            />

            <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div
                    v-for="(tech, i) in stack"
                    :key="tech.name"
                    v-reveal="i * 50"
                    class="reveal ui-card ui-card-hover group flex items-center gap-3 p-4"
                >
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-control bg-primary-soft text-sm font-bold text-primary transition-transform duration-300 group-hover:scale-110">
                        {{ tech.name.charAt(0) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-content">{{ tech.name }}</p>
                        <p class="truncate text-xs text-content-muted">{{ tech.note }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
