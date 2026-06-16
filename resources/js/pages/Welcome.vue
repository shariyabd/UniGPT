<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    AcademicCapIcon,
    ChatBubbleLeftRightIcon,
    ArrowRightIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
</script>

<template>
    <div>
        <Head title="Welcome" />
        <div class="relative flex min-h-dvh items-center justify-center overflow-hidden bg-bg px-4 py-16">
            <!-- Soft primary accent blobs -->
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary-soft blur-3xl opacity-60"></div>
                <div class="absolute -bottom-24 -right-20 h-80 w-80 rounded-full bg-primary-soft blur-3xl opacity-60"></div>
                <div class="absolute left-1/2 top-1/2 h-[26rem] w-[26rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-soft blur-3xl opacity-40"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-2 rounded-pill bg-primary-soft px-4 py-1.5 text-sm font-semibold text-primary">
                    <SparklesIcon class="h-4 w-4" />
                    AI-powered academic copilot
                </span>

                <div class="mx-auto mt-8 flex h-20 w-20 items-center justify-center rounded-card bg-primary shadow-card">
                    <AcademicCapIcon class="h-11 w-11 text-white" />
                </div>

                <h1 class="mt-8 text-5xl font-bold tracking-tight text-content sm:text-7xl">UniGPT</h1>
                <p class="mt-4 text-lg text-content-muted sm:text-2xl">
                    Your university academic copilot — for students, faculty and administrators.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        href="/dashboard"
                        class="ui-btn-primary w-full justify-center gap-2 px-7 py-3.5 text-base sm:w-auto"
                    >
                        Go to Dashboard
                        <ArrowRightIcon class="h-5 w-5" />
                    </Link>
                    <Link
                        href="/chat"
                        class="ui-btn-secondary w-full justify-center gap-2 px-7 py-3.5 text-base sm:w-auto"
                    >
                        <ChatBubbleLeftRightIcon class="h-5 w-5" />
                        Start a Chat
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
