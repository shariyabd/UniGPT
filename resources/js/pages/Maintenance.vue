<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    appName: {
        type: String,
        default: 'UniNexus',
    },
});
</script>

<template>
    <Head title="Under Maintenance" />

    <div class="grid min-h-dvh place-items-center bg-bg px-6 text-content antialiased">
        <div class="w-full max-w-md text-center">
            <div
                class="mx-auto mb-8 grid h-16 w-16 place-items-center rounded-2xl bg-primary-soft text-primary"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-8 w-8"
                >
                    <path
                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"
                    />
                </svg>
            </div>

            <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-primary">
                Maintenance Mode
            </p>

            <h1 class="mb-3 text-2xl font-bold sm:text-3xl">
                We'll be right back
            </h1>

            <p class="text-muted">
                {{ appName }} is temporarily offline for scheduled maintenance.
                Thanks for your patience — please check back in a little while.
            </p>

            <div class="mt-10 border-t border-line pt-6 text-xs text-muted/70">
                If you believe this is an error, contact your administrator.
            </div>
        </div>
    </div>
</template>
