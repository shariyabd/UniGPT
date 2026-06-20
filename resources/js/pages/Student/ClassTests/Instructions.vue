<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import {
    ShieldExclamationIcon,
    ClockIcon,
    NoSymbolIcon,
    ArrowsPointingOutIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    test: { type: Object, required: true },
    inProgress: { type: Boolean, default: false },
});

const agreed = ref(false);
const starting = ref(false);

const start = () => {
    starting.value = true;
    router.post(route('class-tests.start', props.test.id), {}, {
        onFinish: () => { starting.value = false; },
    });
};
</script>

<template>
    <div>
        <Head :title="`${props.test.title} — Instructions`" />

        <AppLayout>
            <div class="page-container py-8">
                <Link :href="route('class-tests')" class="ui-btn-ghost mb-6 w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to class tests
                </Link>

                <div class="mx-auto max-w-2xl space-y-6">
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-content">{{ props.test.title }}</h1>
                        <p class="mt-1 text-content-muted">
                            {{ props.test.course.code }} · {{ props.test.course.name }} · Section {{ props.test.section }}
                        </p>
                    </div>

                    <Card>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.durationMinutes }}</div>
                                <div class="text-xs text-content-muted">minutes</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.questionCount }}</div>
                                <div class="text-xs text-content-muted">questions</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.totalMarks }}</div>
                                <div class="text-xs text-content-muted">marks</div>
                            </div>
                        </div>
                    </Card>

                    <Card title="Exam rules" :icon="ShieldExclamationIcon">
                        <ul class="space-y-3 text-sm text-content">
                            <li class="flex items-start gap-3">
                                <ArrowsPointingOutIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span>The test opens in <strong>fullscreen</strong>. Stay in fullscreen for the entire test.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <NoSymbolIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-danger-fg" />
                                <span><strong>Do not switch tabs or windows</strong>, minimise, or leave fullscreen. Copying is not allowed.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <ShieldExclamationIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-fg" />
                                <span>
                                    You will get <strong>{{ props.test.maxWarnings }} warning{{ props.test.maxWarnings === 1 ? '' : 's' }}</strong>.
                                    Leaving the exam screen again will <strong>disqualify you (score 0)</strong>.
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <ClockIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span>A countdown timer runs throughout. When it reaches zero the test <strong>auto-submits</strong> and grades whatever you've answered.</span>
                            </li>
                        </ul>

                        <p v-if="props.test.description" class="mt-4 whitespace-pre-line rounded-control bg-neutral-bg p-3 text-sm text-content-muted">
                            {{ props.test.description }}
                        </p>
                    </Card>

                    <Card v-if="!props.test.isOpen">
                        <p class="text-center text-sm text-danger-fg">This class test is not currently open.</p>
                    </Card>

                    <template v-else>
                        <label class="flex items-center gap-3 rounded-card border border-line p-4">
                            <input v-model="agreed" type="checkbox" class="h-5 w-5 rounded border-line" />
                            <span class="text-sm text-content">I have read and understood the rules above.</span>
                        </label>

                        <button
                            type="button"
                            class="ui-btn-primary w-full justify-center py-3 text-base disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!agreed || starting"
                            @click="start"
                        >
                            {{ inProgress ? 'Resume test' : 'Start test in fullscreen' }}
                        </button>
                    </template>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
