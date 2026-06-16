<script setup>
import { EnvelopeIcon, SparklesIcon } from '@heroicons/vue/24/outline';

const email = 'shariya873@gmail.com';
</script>

<template>
    <section class="relative py-24">
        <div class="page-container">
            <div v-reveal class="reveal relative mx-auto max-w-4xl overflow-hidden rounded-card border border-line bg-surface p-8 shadow-card sm:p-12">
                <div class="aurora -right-10 -top-10 h-56 w-56 bg-primary/25"></div>

                <div class="relative grid items-center gap-8 sm:grid-cols-[auto,1fr]">
                    <!-- Avatar -->
                    <div class="mx-auto sm:mx-0">
                        <div class="ring-gradient flex h-28 w-28 items-center justify-center rounded-card">
                            <div class="flex h-full w-full items-center justify-center rounded-[14px] bg-primary text-4xl font-bold text-white">
                                MS
                            </div>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="text-center sm:text-left">
                        <span class="inline-flex items-center gap-2 rounded-pill bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">
                            <SparklesIcon class="h-3.5 w-3.5" /> Creator
                        </span>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-content">Mohammad Shariya</h2>
                        <p class="mt-1 text-sm font-medium text-content-muted">Full-stack engineer · Builder of UniGPT</p>

                        <p class="mt-5 text-content-muted">
                            I built UniGPT because campuses are rich with knowledge but poor at making it
                            reachable. Students wait on answers their handbook already holds; faculty lose
                            evenings to busywork; admins fly blind until term's end. UniGPT is my attempt to
                            put a single, trustworthy AI copilot behind every academic interaction — one that
                            cites its sources, respects permissions, and gives each role exactly what they need.
                        </p>

                        <div class="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                            <a :href="`mailto:${email}`" class="ui-btn-primary gap-2 px-5">
                                <EnvelopeIcon class="h-4 w-4" />
                                {{ email }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
