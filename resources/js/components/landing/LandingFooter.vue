<script setup>
import { Link } from '@inertiajs/vue3';
import { AcademicCapIcon } from '@heroicons/vue/24/outline';

const sections = [
    {
        title: 'Product',
        links: [
            { label: 'Features', href: '#features' },
            { label: 'Roles', href: '#roles' },
            { label: 'AI Engine', href: '#ai-engine' },
            { label: 'Workflow', href: '#workflow' },
        ],
    },
    {
        title: 'Resources',
        links: [
            { label: 'FAQ', href: '#faq' },
            { label: 'Sign in', href: '/login', internal: true },
            { label: 'Terms', href: '/terms', internal: true },
            { label: 'Privacy', href: '/privacy', internal: true },
        ],
    },
];

function go(href) {
    document.querySelector(href)?.scrollIntoView({ behavior: 'smooth' });
}
</script>

<template>
    <footer class="border-t border-line bg-surface">
        <div class="page-container py-14">
            <div class="grid gap-10 md:grid-cols-[1.5fr,1fr,1fr]">
                <!-- Brand -->
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-control bg-primary">
                            <AcademicCapIcon class="h-5 w-5 text-white" />
                        </span>
                        <span class="text-lg font-bold tracking-tight text-content">UniGPT</span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm text-content-muted">
                        The AI academic copilot for universities — grounded answers, automated teaching
                        tools and live operational insight, in one platform.
                    </p>
                </div>

                <!-- Link columns -->
                <div v-for="col in sections" :key="col.title">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-content-faint">{{ col.title }}</h3>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in col.links" :key="link.label">
                            <Link
                                v-if="link.internal"
                                :href="link.href"
                                class="text-sm text-content-muted transition-colors hover:text-primary"
                            >
                                {{ link.label }}
                            </Link>
                            <a
                                v-else
                                :href="link.href"
                                class="text-sm text-content-muted transition-colors hover:text-primary"
                                @click.prevent="go(link.href)"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-line pt-6 sm:flex-row">
                <p class="text-sm text-content-faint">© {{ new Date().getFullYear() }} UniGPT. Built by Mohammad Shariya.</p>
                <p class="text-sm text-content-faint">Reimagining university management with AI.</p>
            </div>
        </div>
    </footer>
</template>
