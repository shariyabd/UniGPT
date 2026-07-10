<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ChatBubbleLeftEllipsisIcon,
    SparklesIcon,
    StarIcon,
    LockClosedIcon,
    LockOpenIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sections: { type: Array, default: () => [] },
});

const toast = useToast();

const toggle = (section) => {
    router.patch(route('faculty.course-feedback.toggle', section.sectionId), {}, { preserveScroll: true });
};

// Per-section AI summaries, fetched on demand.
const summaries = reactive({});
const summarizing = reactive({});

const summarize = async (section) => {
    if (summarizing[section.sectionId]) return;
    summarizing[section.sectionId] = true;

    try {
        const { data } = await axios.post(route('faculty.course-feedback.summarize', section.sectionId));
        summaries[section.sectionId] = data;
    } catch (e) {
        toast.error(e.response?.data?.message || 'Could not summarize the feedback. Please try again.');
    } finally {
        summarizing[section.sectionId] = false;
    }
};

const distributionMax = (section) =>
    Math.max(1, ...Object.values(section.ratingDistribution || {}));
</script>

<template>
    <div>
        <Head title="Course Feedback" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Course Feedback"
                    subtitle="Open anonymous mid-semester feedback per section, then review themes — responses are never attributable."
                    :icon="ChatBubbleLeftEllipsisIcon"
                />

                <div v-if="sections.length" class="space-y-6">
                    <Card v-for="section in sections" :key="section.sectionId">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="font-semibold text-content truncate">
                                    {{ section.course?.code }} — {{ section.course?.name }}
                                    <span class="text-content-muted font-normal">· {{ section.label }}</span>
                                </h2>
                                <p class="text-sm text-content-muted mt-0.5">
                                    {{ section.responseCount }} response{{ section.responseCount === 1 ? '' : 's' }}
                                    <template v-if="section.revealed && section.averageRating !== null">
                                        · average {{ section.averageRating }}/5
                                    </template>
                                </p>
                            </div>
                            <button
                                @click="toggle(section)"
                                :class="section.open ? 'ui-btn-secondary' : 'ui-btn-primary'"
                            >
                                <component :is="section.open ? LockClosedIcon : LockOpenIcon" class="w-4 h-4" />
                                {{ section.open ? 'Close window' : 'Open feedback' }}
                            </button>
                        </div>

                        <!-- Anonymity floor -->
                        <div
                            v-if="!section.revealed"
                            class="mt-4 flex items-center gap-2 rounded-card border border-line bg-bg p-3 text-sm text-content-muted"
                        >
                            <EyeSlashIcon class="w-4 h-4 flex-shrink-0" />
                            Results unlock at {{ section.minResponses }} responses to protect anonymity
                            ({{ section.responseCount }}/{{ section.minResponses }} so far).
                        </div>

                        <template v-else>
                            <!-- Rating distribution -->
                            <div class="mt-4 space-y-1.5">
                                <div
                                    v-for="rating in [5, 4, 3, 2, 1]"
                                    :key="rating"
                                    class="flex items-center gap-2 text-xs"
                                >
                                    <span class="w-8 flex items-center gap-0.5 text-content-muted">
                                        {{ rating }} <StarIcon class="w-3 h-3" />
                                    </span>
                                    <div class="flex-1 h-2 rounded-full bg-neutral-bg overflow-hidden">
                                        <div
                                            class="h-full bg-primary rounded-full"
                                            :style="{ width: `${(section.ratingDistribution?.[rating] || 0) / distributionMax(section) * 100}%` }"
                                        ></div>
                                    </div>
                                    <span class="w-6 text-right text-content-muted">{{ section.ratingDistribution?.[rating] || 0 }}</span>
                                </div>
                            </div>

                            <!-- AI theme summary -->
                            <div class="mt-5">
                                <button
                                    @click="summarize(section)"
                                    :disabled="summarizing[section.sectionId]"
                                    class="ui-btn-secondary text-sm disabled:opacity-50"
                                >
                                    <SparklesIcon class="w-4 h-4 text-primary" />
                                    {{ summarizing[section.sectionId] ? 'Summarizing…' : 'Summarize themes with AI' }}
                                </button>

                                <div v-if="summaries[section.sectionId]" class="mt-3 rounded-card border border-line bg-bg p-4 space-y-3 text-sm">
                                    <p class="text-content">{{ summaries[section.sectionId].summary }}</p>
                                    <div v-if="summaries[section.sectionId].positives?.length">
                                        <p class="font-semibold text-success-fg mb-1">Going well</p>
                                        <ul class="list-disc pl-5 text-content-muted space-y-0.5">
                                            <li v-for="(item, i) in summaries[section.sectionId].positives" :key="i">{{ item }}</li>
                                        </ul>
                                    </div>
                                    <div v-if="summaries[section.sectionId].concerns?.length">
                                        <p class="font-semibold text-warning-fg mb-1">Concerns</p>
                                        <ul class="list-disc pl-5 text-content-muted space-y-0.5">
                                            <li v-for="(item, i) in summaries[section.sectionId].concerns" :key="i">{{ item }}</li>
                                        </ul>
                                    </div>
                                    <div v-if="summaries[section.sectionId].suggestions?.length">
                                        <p class="font-semibold text-primary mb-1">Suggestions</p>
                                        <ul class="list-disc pl-5 text-content-muted space-y-0.5">
                                            <li v-for="(item, i) in summaries[section.sectionId].suggestions" :key="i">{{ item }}</li>
                                        </ul>
                                    </div>
                                    <p v-if="summaries[section.sectionId].source === 'heuristic'" class="text-xs text-content-faint">
                                        Basic summary — configure an AI provider for thematic analysis.
                                    </p>
                                </div>
                            </div>

                            <!-- Anonymized comments -->
                            <div v-if="section.comments.length" class="mt-5">
                                <p class="text-sm font-semibold text-content mb-2">Comments (anonymous, shuffled)</p>
                                <div class="space-y-2">
                                    <p
                                        v-for="(comment, index) in section.comments"
                                        :key="index"
                                        class="rounded-control border border-line bg-surface p-3 text-sm text-content-muted whitespace-pre-wrap"
                                    >{{ comment }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <EmptyState
                    v-else
                    title="No teaching sections"
                    description="Sections you teach will appear here with their feedback windows."
                    :icon="ChatBubbleLeftEllipsisIcon"
                />
            </div>
        </AppLayout>
    </div>
</template>
