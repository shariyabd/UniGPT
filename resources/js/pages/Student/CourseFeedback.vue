<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { ChatBubbleLeftEllipsisIcon, StarIcon, LockClosedIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    sections: { type: Array, default: () => [] },
});

// Per-section local form state, seeded from any existing response.
const forms = reactive(Object.fromEntries(props.sections.map((section) => [
    section.sectionId,
    {
        rating: section.own?.rating ?? 0,
        comment: section.own?.comment ?? '',
        submitting: false,
    },
])));

const submit = (section) => {
    const form = forms[section.sectionId];
    if (!form.rating || form.submitting) return;

    form.submitting = true;
    router.post(route('course-feedback.store', section.sectionId), {
        rating: form.rating,
        comment: form.comment || null,
    }, {
        preserveScroll: true,
        onFinish: () => { form.submitting = false; },
    });
};
</script>

<template>
    <div>
        <Head title="Course Feedback" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Course Feedback"
                    subtitle="Anonymous mid-semester feedback — your instructor sees ratings and comments, never who wrote them."
                    :icon="ChatBubbleLeftEllipsisIcon"
                />

                <div v-if="sections.length" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card v-for="section in sections" :key="section.sectionId">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="font-semibold text-content truncate">
                                    {{ section.course?.code }} — {{ section.course?.name }}
                                </h2>
                                <p class="text-sm text-content-muted">
                                    {{ section.label }}<span v-if="section.faculty"> · {{ section.faculty }}</span>
                                </p>
                            </div>
                            <span
                                :class="[
                                    'ui-badge flex-shrink-0',
                                    section.open ? 'bg-success-bg text-success-fg' : 'bg-neutral-bg text-neutral-fg',
                                ]"
                            >
                                {{ section.open ? 'Open' : 'Closed' }}
                            </span>
                        </div>

                        <div v-if="section.open" class="mt-4 space-y-4">
                            <div v-if="section.submitted" class="flex items-center gap-2 text-sm text-success-fg">
                                <CheckCircleIcon class="w-4 h-4" />
                                Feedback submitted — you can revise it while the window is open.
                            </div>

                            <div>
                                <p class="ui-label">Overall rating</p>
                                <div class="flex items-center gap-1 mt-1">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="forms[section.sectionId].rating = star"
                                        class="p-0.5"
                                        :aria-label="`Rate ${star} of 5`"
                                    >
                                        <component
                                            :is="star <= forms[section.sectionId].rating ? StarSolidIcon : StarIcon"
                                            class="w-7 h-7"
                                            :class="star <= forms[section.sectionId].rating ? 'text-warning-fg' : 'text-content-faint'"
                                        />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label :for="`comment-${section.sectionId}`" class="ui-label">What's working, what isn't? (optional)</label>
                                <textarea
                                    :id="`comment-${section.sectionId}`"
                                    v-model="forms[section.sectionId].comment"
                                    rows="3"
                                    maxlength="2000"
                                    class="ui-input resize-none"
                                    placeholder="Be specific and constructive — this is fully anonymous."
                                ></textarea>
                            </div>

                            <button
                                @click="submit(section)"
                                :disabled="!forms[section.sectionId].rating || forms[section.sectionId].submitting"
                                class="ui-btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ forms[section.sectionId].submitting
                                    ? 'Sending…'
                                    : (section.submitted ? 'Update feedback' : 'Submit anonymously') }}
                            </button>
                        </div>

                        <div v-else class="mt-4 flex items-center gap-2 text-sm text-content-muted">
                            <LockClosedIcon class="w-4 h-4" />
                            {{ section.submitted
                                ? 'The window is closed — thanks for your feedback.'
                                : 'Your instructor has not opened feedback for this section yet.' }}
                        </div>
                    </Card>
                </div>

                <EmptyState
                    v-else
                    title="No sections found"
                    description="Once you're enrolled in sections, course feedback windows will appear here."
                    :icon="ChatBubbleLeftEllipsisIcon"
                />
            </div>
        </AppLayout>
    </div>
</template>
