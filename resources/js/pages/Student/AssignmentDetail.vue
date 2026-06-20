<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    ArrowLeftIcon,
    CalendarIcon,
    PaperClipIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    assignment: { type: Object, required: true },
    submission: { type: Object, default: null },
});

const toast = useToast();

// A graded submission is locked; otherwise the student may submit or resubmit.
const isLocked = computed(() => props.submission?.isGraded === true);

const form = useForm({
    content: props.submission?.content ?? '',
    file: null,
});

const fileName = ref(props.submission?.fileName ?? null);

const onFile = (event) => {
    form.file = event.target.files[0] ?? null;
    fileName.value = form.file ? form.file.name : props.submission?.fileName ?? null;
};

const submit = () => {
    form.post(route('assignments.submit', props.assignment.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.file = null;
            // Success toast comes from the server flash (centralized pipeline).
        },
        onError: (errors) => toast.error(Object.values(errors)[0] || 'Could not submit.'),
    });
};

const statusBadge = computed(() => {
    if (!props.submission) return null;
    if (props.submission.isGraded) return { label: 'Graded', variant: 'success' };
    if (props.submission.status === 'late') return { label: 'Submitted late', variant: 'warning' };
    return { label: 'Submitted', variant: 'primary' };
});
</script>

<template>
    <div>
        <Head :title="assignment.title" />

        <AppLayout>
            <div class="page-container max-w-3xl py-8 space-y-6">
                <Link :href="route('assignments')" class="inline-flex items-center gap-1.5 text-sm font-medium text-content-muted hover:text-content">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to assignments
                </Link>

                <!-- Assignment header -->
                <Card>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-content-muted">{{ assignment.course.code }} · {{ assignment.course.name }}</span>
                        <Badge variant="slate" class="capitalize">{{ assignment.type }}</Badge>
                        <Badge v-if="statusBadge" :variant="statusBadge.variant">{{ statusBadge.label }}</Badge>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-content">{{ assignment.title }}</h1>

                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-content-muted">
                        <span class="flex items-center gap-1.5">
                            <CalendarIcon class="h-4 w-4 text-content-faint" />
                            {{ assignment.dueLabel ? `Due ${assignment.dueLabel}` : 'No due date' }}
                        </span>
                        <span>{{ assignment.totalPoints }} points</span>
                        <span v-if="assignment.isPastDue && !submission" class="flex items-center gap-1.5 font-medium text-danger-fg">
                            <ExclamationTriangleIcon class="h-4 w-4" /> Past due
                        </span>
                    </div>

                    <p v-if="assignment.description" class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-content">{{ assignment.description }}</p>

                    <!-- Rubric -->
                    <div v-if="assignment.rubric && assignment.rubric.length" class="mt-5">
                        <h2 class="text-sm font-semibold text-content">Rubric</h2>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="(criterion, i) in assignment.rubric" :key="i" class="flex justify-between gap-4 rounded-control bg-bg px-3 py-2 text-sm">
                                <span class="text-content">{{ criterion.criterion || criterion.title || criterion }}</span>
                                <span v-if="criterion.points" class="font-medium text-content-muted">{{ criterion.points }} pts</span>
                            </li>
                        </ul>
                    </div>
                </Card>

                <!-- Grade & feedback (once graded) -->
                <Card v-if="submission && submission.isGraded">
                    <div class="flex items-center gap-3">
                        <span class="ui-icon-tile bg-success-bg text-success-fg"><CheckBadgeIcon class="h-5 w-5" /></span>
                        <div>
                            <div class="text-sm text-content-muted">Your grade</div>
                            <div class="text-2xl font-bold text-content">{{ submission.grade }}<span class="text-base text-content-faint">/{{ assignment.totalPoints }}</span></div>
                        </div>
                    </div>
                    <div v-if="submission.feedback" class="mt-4">
                        <h2 class="text-sm font-semibold text-content">Instructor feedback</h2>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-content">{{ submission.feedback }}</p>
                    </div>
                </Card>

                <!-- Submission form -->
                <Card :title="submission ? 'Your submission' : 'Submit your work'">
                    <div v-if="isLocked" class="rounded-card bg-bg p-4 text-sm text-content-muted">
                        This submission has been graded and can no longer be edited.
                        <p v-if="submission.content" class="mt-3 whitespace-pre-wrap text-content">{{ submission.content }}</p>
                        <p v-if="submission.fileName" class="mt-2 flex items-center gap-1.5 text-content">
                            <PaperClipIcon class="h-4 w-4" /> {{ submission.fileName }}
                        </p>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="submit">
                        <p v-if="submission" class="text-sm text-content-muted">
                            You submitted on {{ submission.submittedLabel }}. You can resubmit until this is graded.
                        </p>

                        <div>
                            <label for="content" class="ui-label">Written response</label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                rows="8"
                                class="ui-input"
                                placeholder="Type your answer, or attach a file below."
                            />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-danger-fg">{{ form.errors.content }}</p>
                        </div>

                        <div>
                            <label for="file" class="ui-label">Attachment <span class="font-normal text-content-faint">(optional, max 10 MB)</span></label>
                            <input
                                id="file"
                                type="file"
                                class="ui-input"
                                @change="onFile"
                            />
                            <p v-if="fileName" class="mt-1 flex items-center gap-1.5 text-sm text-content-muted">
                                <PaperClipIcon class="h-4 w-4" /> {{ fileName }}
                            </p>
                            <p v-if="form.errors.file" class="mt-1 text-sm text-danger-fg">{{ form.errors.file }}</p>
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">
                                {{ form.progress.percentage }}%
                            </progress>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="ui-btn-primary" :disabled="form.processing">
                                {{ submission ? 'Resubmit' : 'Submit' }}
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
