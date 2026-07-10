<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
    UserGroupIcon,
    StarIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    assignment: { type: Object, required: true },
    submission: { type: Object, default: null },
    peerTasks: { type: Array, default: () => [] },
    peerReceived: { type: Array, default: () => [] },
});

// --- Anonymous peer review -------------------------------------------------
const peerForms = reactive(Object.fromEntries(props.peerTasks.map((task) => [
    task.id,
    { rating: task.rating ?? 0, comments: task.comments ?? '', submitting: false },
])));

const submitPeerReview = (task) => {
    const peerForm = peerForms[task.id];
    if (!peerForm.rating || peerForm.submitting) return;

    peerForm.submitting = true;
    router.post(route('assignments.peer-review', [props.assignment.id, task.id]), {
        rating: peerForm.rating,
        comments: peerForm.comments || null,
    }, {
        preserveScroll: true,
        onFinish: () => { peerForm.submitting = false; },
    });
};

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

                <!-- Peer reviews to complete (anonymous) -->
                <Card v-if="peerTasks.length" title="Peer review">
                    <p class="text-sm text-content-muted -mt-1 mb-4">
                        Review these anonymous classmate submissions — they'll never see who reviewed them, and you don't see whose work this is.
                    </p>
                    <div class="space-y-5">
                        <div v-for="(task, index) in peerTasks" :key="task.id" class="rounded-card border border-line p-4">
                            <p class="text-xs font-semibold text-content-muted mb-2">Submission {{ index + 1 }}</p>
                            <div class="max-h-56 overflow-y-auto whitespace-pre-wrap rounded-control bg-bg p-3 text-sm text-content">
                                {{ task.content || 'This classmate submitted an attachment without written text.' }}
                            </div>

                            <div v-if="task.completed && !peerForms[task.id].submitting" class="mt-3 flex items-center gap-2 text-sm text-success-fg">
                                <CheckBadgeIcon class="h-4 w-4" /> Review submitted — you can revise it below.
                            </div>

                            <div class="mt-3 flex items-center gap-1">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    @click="peerForms[task.id].rating = star"
                                    class="p-0.5"
                                    :aria-label="`Rate ${star} of 5`"
                                >
                                    <component
                                        :is="star <= peerForms[task.id].rating ? StarSolidIcon : StarIcon"
                                        class="h-6 w-6"
                                        :class="star <= peerForms[task.id].rating ? 'text-warning-fg' : 'text-content-faint'"
                                    />
                                </button>
                            </div>
                            <textarea
                                v-model="peerForms[task.id].comments"
                                rows="3"
                                maxlength="2000"
                                class="ui-input resize-none mt-2"
                                placeholder="What works well? What could be improved? Be constructive."
                            ></textarea>
                            <button
                                @click="submitPeerReview(task)"
                                :disabled="!peerForms[task.id].rating || peerForms[task.id].submitting"
                                class="ui-btn-primary mt-3 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ peerForms[task.id].submitting ? 'Sending…' : (task.completed ? 'Update review' : 'Submit review') }}
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Feedback received from classmates (anonymous) -->
                <Card v-if="peerReceived.length" title="Feedback from classmates">
                    <div class="space-y-3">
                        <div v-for="(review, index) in peerReceived" :key="index" class="rounded-card border border-line bg-bg p-3">
                            <div class="flex items-center gap-1">
                                <component
                                    :is="star <= review.rating ? StarSolidIcon : StarIcon"
                                    v-for="star in 5"
                                    :key="star"
                                    class="h-4 w-4"
                                    :class="star <= review.rating ? 'text-warning-fg' : 'text-content-faint'"
                                />
                            </div>
                            <p v-if="review.comments" class="mt-2 text-sm text-content whitespace-pre-wrap">{{ review.comments }}</p>
                        </div>
                        <p class="flex items-center gap-1.5 text-xs text-content-faint">
                            <UserGroupIcon class="h-3.5 w-3.5" /> Peer reviews are anonymous and don't affect your grade directly.
                        </p>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
