<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const stats = [
    { key: 'hours', target: 12, suffix: 'h', label: 'Saved per week, per faculty member', decimals: 0 },
    { key: 'time', target: 1.2, suffix: 's', label: 'Average grounded answer time', decimals: 1 },
    { key: 'roles', target: 3, suffix: '', label: 'Tailored role experiences', decimals: 0 },
    { key: 'cited', target: 100, suffix: '%', label: 'Answers traced to a source', decimals: 0 },
];

const display = reactive(Object.fromEntries(stats.map((s) => [s.key, '0'])));
const root = ref(null);
let observer = null;
let started = false;

function format(value, decimals) {
    return decimals ? value.toFixed(decimals) : Math.round(value).toString();
}

function run() {
    if (started) return;
    started = true;
    const duration = 1500;
    const start = performance.now();

    const tick = (now) => {
        const t = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
        stats.forEach((s) => {
            display[s.key] = format(s.target * eased, s.decimals);
        });
        if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

onMounted(() => {
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduced || typeof IntersectionObserver === 'undefined') {
        stats.forEach((s) => (display[s.key] = format(s.target, s.decimals)));
        return;
    }
    observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => e.isIntersecting && run()),
        { threshold: 0.4 },
    );
    if (root.value) observer.observe(root.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <section ref="root" class="relative overflow-hidden py-20">
        <div class="aurora animate-aurora left-1/4 top-0 h-72 w-72 bg-primary/30"></div>
        <div class="page-container relative">
            <div class="grid gap-6 rounded-card border border-line bg-surface p-8 shadow-card sm:grid-cols-2 lg:grid-cols-4 lg:p-10">
                <div v-for="(s, i) in stats" :key="s.key" v-reveal="i * 80" class="reveal text-center">
                    <p class="text-4xl font-bold tracking-tight text-content sm:text-5xl">
                        <span class="text-gradient">{{ display[s.key] }}{{ s.suffix }}</span>
                    </p>
                    <p class="mx-auto mt-3 max-w-[14rem] text-sm text-content-muted">{{ s.label }}</p>
                </div>
            </div>
            <p class="mt-4 text-center text-xs text-content-faint">
                Illustrative figures based on the platform's automated workflows.
            </p>
        </div>
    </section>
</template>
