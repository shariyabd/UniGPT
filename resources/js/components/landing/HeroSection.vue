<script setup>
import { Link } from '@inertiajs/vue3';
import {
    SparklesIcon,
    ArrowRightIcon,
    PlayCircleIcon,
    DocumentTextIcon,
    CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const trustedBy = ['Students', 'Faculty', 'Administrators', 'Departments'];
</script>

<template>
    <section id="top" class="relative overflow-hidden pb-20 pt-32 sm:pt-40">
        <!-- Ambient aurora + grid -->
        <div class="absolute inset-0 grid-backdrop"></div>
        <div class="aurora animate-aurora -left-32 top-0 h-[28rem] w-[28rem] bg-primary/40"></div>
        <div class="aurora animate-aurora -right-24 top-24 h-[26rem] w-[26rem]" style="background:#22d3ee66; animation-delay:-6s"></div>
        <div class="aurora animate-aurora left-1/3 top-40 h-[22rem] w-[22rem]" style="background:#8b5cf655; animation-delay:-10s"></div>

        <div class="page-container relative">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <!-- Copy -->
                <div class="reveal" v-reveal>
                    <span class="inline-flex items-center gap-2 rounded-pill border border-line bg-surface/70 px-4 py-1.5 text-sm font-semibold text-primary backdrop-blur">
                        <SparklesIcon class="h-4 w-4" />
                        AI academic copilot for universities
                    </span>

                    <h1 class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight text-content sm:text-6xl lg:text-[4.25rem]">
                        Run your university on
                        <span class="text-gradient">intelligence</span>, not paperwork.
                    </h1>

                    <p class="mt-6 max-w-xl text-lg text-content-muted">
                        UniGPT unifies students, faculty and administrators on one AI platform —
                        grounded answers from your own academic documents, automated teaching tools,
                        and live operational insight. One login, three tailored experiences.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link href="/login" class="ui-btn-primary gap-2 px-7 py-3.5 text-base">
                            Launch UniGPT
                            <ArrowRightIcon class="h-5 w-5" />
                        </Link>
                        <a href="#solution" class="ui-btn-secondary gap-2 px-7 py-3.5 text-base">
                            <PlayCircleIcon class="h-5 w-5" />
                            See how it works
                        </a>
                    </div>

                    <div class="mt-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-content-faint">Built for everyone on campus</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="role in trustedBy"
                                :key="role"
                                class="rounded-pill border border-line bg-surface/60 px-3 py-1 text-xs font-medium text-content-muted backdrop-blur"
                            >
                                {{ role }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Product mockup: a grounded AI answer -->
                <div class="reveal" v-reveal="120">
                    <div class="relative mx-auto max-w-md">
                        <!-- glow behind card -->
                        <div class="absolute -inset-4 rounded-[2rem] bg-primary/20 blur-2xl"></div>

                        <div class="ring-gradient relative rounded-card p-1 shadow-card-hover">
                            <div class="rounded-[14px] bg-surface p-5">
                                <!-- chat header -->
                                <div class="flex items-center gap-2 border-b border-line pb-3">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary">
                                        <SparklesIcon class="h-4 w-4 text-white" />
                                    </span>
                                    <span class="text-sm font-semibold text-content">UniGPT · Academic mode</span>
                                    <span class="ml-auto ui-status-success">online</span>
                                </div>

                                <!-- user question -->
                                <div class="mt-4 flex justify-end">
                                    <p class="max-w-[80%] rounded-2xl rounded-br-sm bg-primary px-3.5 py-2 text-sm text-white">
                                        What's the late submission policy for CS401?
                                    </p>
                                </div>

                                <!-- AI answer -->
                                <div class="mt-3 flex gap-2">
                                    <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-soft">
                                        <SparklesIcon class="h-4 w-4 text-primary" />
                                    </span>
                                    <div class="max-w-[85%] rounded-2xl rounded-bl-sm border border-line bg-bg px-3.5 py-2.5">
                                        <p class="text-sm text-content">
                                            Submissions are accepted up to <span class="font-semibold">72 hours late</span>
                                            with a 10% penalty per day. Beyond that, a grade of zero applies unless
                                            an extension was approved.
                                        </p>
                                        <div class="mt-2.5 flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 rounded-pill bg-success-bg px-2 py-0.5 text-[11px] font-semibold text-success-fg">
                                                <CheckBadgeIcon class="h-3.5 w-3.5" /> 96% confidence
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-pill border border-line bg-surface px-2 py-0.5 text-[11px] font-medium text-content-muted">
                                                <DocumentTextIcon class="h-3.5 w-3.5" /> CS401 Syllabus · p.4
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- typing -->
                                <div class="mt-3 flex items-center gap-1.5 pl-9">
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-content-faint" style="animation-delay:0ms"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-content-faint" style="animation-delay:150ms"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-content-faint" style="animation-delay:300ms"></span>
                                </div>
                            </div>
                        </div>

                        <!-- floating chips -->
                        <div class="absolute -left-6 top-16 hidden animate-float rounded-control border border-line bg-surface px-3 py-2 text-xs font-semibold text-content shadow-card-hover sm:block">
                            🔒 Cited from your docs
                        </div>
                        <div class="absolute -right-5 bottom-10 hidden animate-float-slow rounded-control border border-line bg-surface px-3 py-2 text-xs font-semibold text-content shadow-card-hover sm:block">
                            ⚡ Grounded, cited answers
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
