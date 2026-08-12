<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <!-- Dark anchor band (see AiEngineSection): black canvas with the brand-gradient
         CTA card popping on top. -->
    <section class="dark relative overflow-hidden border-y border-line bg-bg py-24 text-content">
        <div class="page-container">
            <div v-reveal class="reveal bg-brand-gradient relative overflow-hidden rounded-card px-6 py-16 text-center shadow-card-hover sm:px-12">
                <!-- glow accents -->
                <div class="aurora -left-10 -top-10 h-72 w-72 bg-white/20" style="opacity:.35"></div>
                <div class="aurora -bottom-16 -right-10 h-72 w-72" style="background:#18f5ea; opacity:.3"></div>

                <div class="relative mx-auto max-w-2xl">
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                        Bring AI to every corner of your campus
                    </h2>
                    <p class="mt-5 text-lg text-white/80">
                        One platform for students, faculty and administrators — grounded, secure and
                        ready to use today. Sign in and see your role come to life.
                    </p>

                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Link
                            href="/login"
                            class="ui-btn inline-flex w-full justify-center gap-2 bg-white px-7 py-3.5 text-base text-primary hover:bg-white/90 sm:w-auto"
                        >
                            Get started
                            <ArrowRightIcon class="h-5 w-5" />
                        </Link>
                        <Link
                            href="/login"
                            class="ui-btn inline-flex w-full justify-center gap-2 border border-white/40 bg-white/10 px-7 py-3.5 text-base text-white backdrop-blur hover:bg-white/20 sm:w-auto"
                        >
                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                            Try the assistant
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
