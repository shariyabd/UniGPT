<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Motion, useScroll } from 'motion-v';
import { AcademicCapIcon, SunIcon, MoonIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '@/composables/useTheme';

const { isDark, initTheme, toggleTheme } = useTheme();
const scrolled = ref(false);
const mobileOpen = ref(false);

// Reading-progress bar: tracks whole-page scroll (0 → 1).
const { scrollYProgress } = useScroll();

const links = [
    { label: 'Features', href: '#features' },
    { label: 'Journey', href: '#journey' },
    { label: 'Roles', href: '#roles' },
    { label: 'AI Engine', href: '#ai-engine' },
    { label: 'Workflow', href: '#workflow' },
    { label: 'Roadmap', href: '#roadmap' },
    { label: 'FAQ', href: '#faq' },
];

function onScroll() {
    scrolled.value = window.scrollY > 12;
}

function go(href) {
    mobileOpen.value = false;
    document.querySelector(href)?.scrollIntoView({ behavior: 'smooth' });
}

onMounted(() => {
    initTheme();
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
        :class="scrolled ? 'border-b border-line bg-surface/80 backdrop-blur-xl' : 'border-b border-transparent'"
    >
        <nav class="page-container flex h-16 items-center justify-between gap-4">
            <!-- Brand -->
            <a href="#top" class="flex items-center gap-2.5" @click.prevent="go('#top')">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-control shadow-card transition-colors"
                    :class="scrolled ? 'bg-primary' : 'bg-white/15 ring-1 ring-white/25 backdrop-blur'"
                >
                    <AcademicCapIcon class="h-5 w-5 text-white" />
                </span>
                <span
                    class="text-lg font-bold tracking-tight transition-colors"
                    :class="scrolled ? 'text-content' : 'text-white'"
                >UniNexus</span>
            </a>

            <!-- Desktop links -->
            <div class="hidden items-center gap-1 md:flex">
                <a
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-control px-3 py-2 text-sm font-medium transition-colors"
                    :class="scrolled ? 'text-content-muted hover:bg-primary-soft hover:text-primary' : 'text-white/85 hover:bg-white/10 hover:text-white'"
                    @click.prevent="go(link.href)"
                >
                    {{ link.label }}
                </a>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="ui-btn-ghost h-9 w-9 p-0"
                    :class="scrolled ? '' : 'text-white hover:bg-white/10 hover:text-white'"
                    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggleTheme"
                >
                    <SunIcon v-if="isDark" class="h-5 w-5" />
                    <MoonIcon v-else class="h-5 w-5" />
                </button>

                <Link
                    href="/login"
                    class="ui-btn-ghost hidden px-3.5 sm:inline-flex"
                    :class="scrolled ? '' : 'text-white hover:bg-white/10 hover:text-white'"
                >Sign in</Link>
                <Link
                    href="/login"
                    class="px-4"
                    :class="scrolled ? 'ui-btn-primary' : 'ui-btn bg-white text-primary hover:bg-white/90'"
                >Get started</Link>

                <button
                    type="button"
                    class="ui-btn-ghost h-9 w-9 p-0 md:hidden"
                    :class="scrolled ? '' : 'text-white hover:bg-white/10 hover:text-white'"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <XMarkIcon v-if="mobileOpen" class="h-5 w-5" />
                    <Bars3Icon v-else class="h-5 w-5" />
                </button>
            </div>
        </nav>

        <!-- Mobile menu -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="mobileOpen" class="border-t border-line bg-surface/95 backdrop-blur-xl md:hidden">
                <div class="page-container flex flex-col gap-1 py-3">
                    <a
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-control px-3 py-2.5 text-sm font-medium text-content-muted hover:bg-primary-soft hover:text-primary"
                        @click.prevent="go(link.href)"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>
        </Transition>

        <!-- Scroll-progress indicator -->
        <Motion
            as="div"
            class="absolute inset-x-0 bottom-0 h-0.5 origin-left bg-gradient-to-r from-primary via-[#3b82f6] to-[#18f5ea]"
            :style="{ scaleX: scrollYProgress }"
        />
    </header>
</template>
