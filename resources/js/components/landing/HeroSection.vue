<script setup>
import { Link } from '@inertiajs/vue3';
import { Motion, useReducedMotion } from 'motion-v';
import {
    SparklesIcon,
    ArrowRightIcon,
    PlayCircleIcon,
    DocumentTextIcon,
    CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const trustedBy = ['Students', 'Faculty', 'Administrators', 'Departments'];

// prefers-reduced-motion: skip the entrance transform when the user opts out.
const reduce = useReducedMotion();
const rise = (delay = 0) => ({
    initial: reduce.value ? false : { opacity: 0, y: 22 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.6, delay, ease: [0.22, 1, 0.36, 1] },
});
</script>

<template>
    <!-- Full brand-gradient hero (shares the login left-panel treatment: aurora
         glows + dotted texture + glass chips), enriched with a layered gradient
         mesh and a soft fade into the light page below. -->
    <section id="top" class="bg-hero-gradient relative overflow-hidden pb-44 pt-32 text-white sm:pt-40">
        <!-- Decorative layers (clipped, pointer-transparent) -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <!-- Gradient mesh for depth -->
            <div class="absolute inset-0" style="background: radial-gradient(60% 55% at 15% 20%, rgba(255,255,255,0.22), transparent 60%), radial-gradient(50% 50% at 85% 15%, rgba(34,211,238,0.30), transparent 60%), radial-gradient(55% 60% at 75% 90%, rgba(59,130,246,0.35), transparent 60%);"></div>
            <!-- Animated aurora blobs (larger than login for hero scale) -->
            <div class="aurora animate-aurora -left-32 top-0 h-[34rem] w-[34rem] bg-white/25"></div>
            <div class="aurora animate-aurora -right-24 top-16 h-[32rem] w-[32rem]" style="background:#22d3ee66; animation-delay:-6s"></div>
            <div class="aurora animate-aurora left-1/3 top-1/2 h-[28rem] w-[28rem] bg-white/15" style="animation-delay:-11s"></div>
            <!-- Fine dotted texture (matches login) -->
            <div
                class="absolute inset-0 opacity-[0.12]"
                style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"
            ></div>
            <!-- Soft fade so the vivid hero blends into the light section below -->
            <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-b from-transparent to-bg"></div>
        </div>

        <div class="page-container relative">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <!-- Copy -->
                <div>
                    <Motion as="span" v-bind="rise(0)" class="inline-flex items-center gap-2 rounded-pill border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold text-white backdrop-blur-md">
                        <SparklesIcon class="h-4 w-4" />
                        AI academic copilot for universities
                    </Motion>

                    <Motion as="h1" v-bind="rise(0.08)" class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-[4.25rem]">
                        Run your university on
                        <span class="bg-gradient-to-r from-white to-cyan-200 bg-clip-text text-transparent">intelligence</span>, not paperwork.
                    </Motion>

                    <Motion as="p" v-bind="rise(0.16)" class="mt-6 max-w-xl text-lg text-white/80">
                        UniNexus unifies students, faculty and administrators on one AI platform —
                        grounded answers from your own academic documents, automated teaching tools,
                        and live operational insight. One login, three tailored experiences.
                    </Motion>

                    <Motion as="div" v-bind="rise(0.24)" class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link href="/login" class="ui-btn gap-2 bg-white px-7 py-3.5 text-base text-primary shadow-card-hover hover:bg-white/90">
                            Launch UniNexus
                            <ArrowRightIcon class="h-5 w-5" />
                        </Link>
                        <a href="#solution" class="ui-btn gap-2 border border-white/30 bg-white/10 px-7 py-3.5 text-base text-white backdrop-blur-md hover:bg-white/20">
                            <PlayCircleIcon class="h-5 w-5" />
                            See how it works
                        </a>
                    </Motion>

                    <Motion as="div" v-bind="rise(0.32)" class="mt-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">Built for everyone on campus</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="role in trustedBy"
                                :key="role"
                                class="rounded-pill border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-white/85 backdrop-blur-md"
                            >
                                {{ role }}
                            </span>
                        </div>
                    </Motion>
                </div>

                <!-- Product mockup: a grounded AI answer (clean light card floats on the gradient) -->
                <Motion
                    as="div"
                    :initial="reduce ? false : { opacity: 0, y: 30, scale: 0.96 }"
                    :animate="{ opacity: 1, y: 0, scale: 1 }"
                    :transition="{ duration: 0.7, delay: 0.2, ease: [0.22, 1, 0.36, 1] }"
                >
                    <div class="relative mx-auto max-w-md">
                        <!-- glow behind card -->
                        <div class="absolute -inset-5 rounded-[2rem] bg-white/25 blur-2xl"></div>

                        <div class="relative rounded-card border border-white/40 bg-white/10 p-1 shadow-2xl backdrop-blur-md">
                            <div class="rounded-[14px] bg-surface p-5">
                                <!-- chat header -->
                                <div class="flex items-center gap-2 border-b border-line pb-3">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary">
                                        <SparklesIcon class="h-4 w-4 text-white" />
                                    </span>
                                    <span class="text-sm font-semibold text-content">UniNexus · Academic mode</span>
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

                        <!-- floating glass chips (login-style) — overhang the card
                             corners so they float without covering the content. -->
                        <div class="absolute -left-4 -top-4 hidden animate-float rounded-control border border-white/25 bg-white/15 px-3 py-2 text-xs font-semibold text-white shadow-lg backdrop-blur-md lg:block">
                            🔒 Cited from your docs
                        </div>
                        <div class="absolute -bottom-4 -right-4 hidden animate-float-slow rounded-control border border-white/25 bg-white/15 px-3 py-2 text-xs font-semibold text-white shadow-lg backdrop-blur-md lg:block">
                            ⚡ Grounded, cited answers
                        </div>
                    </div>
                </Motion>
            </div>
        </div>
    </section>
</template>
