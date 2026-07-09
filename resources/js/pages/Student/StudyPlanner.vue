<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { SparklesIcon, CalendarDaysIcon, ClockIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    deadlines: { type: Array, default: () => [] },
});

const toast = useToast();

const today = new Date().toISOString().slice(0, 10);
const inTwoWeeks = new Date(Date.now() + 14 * 864e5).toISOString().slice(0, 10);

const form = reactive({
    start_date: today,
    end_date: inTwoWeeks,
    hours_per_day: 3,
    focus: '',
});

const generating = ref(false);
const saving = ref(false);
const plan = ref(null);
// Track which generated sessions the student wants to keep (all on by default).
const selected = ref([]);

const deadlineVariant = (type) => ({
    exam: 'danger',
    'class-test': 'warning',
    assignment: 'primary',
}[type] ?? 'neutral');

const deadlineLabel = (type) => ({
    exam: 'Exam',
    'class-test': 'Class Test',
    assignment: 'Assignment',
}[type] ?? type);

const generate = async () => {
    generating.value = true;
    try {
        const { data } = await axios.post(route('study-planner.generate'), { ...form });
        plan.value = data.plan;
        selected.value = data.plan.sessions.map((_, index) => index);
    } catch (error) {
        toast.error(error.response?.data?.message ?? 'Could not generate a study plan. Please try again.');
    } finally {
        generating.value = false;
    }
};

const sessions = computed(() => plan.value?.sessions ?? []);

const toggle = (index) => {
    const at = selected.value.indexOf(index);
    if (at === -1) {
        selected.value.push(index);
    } else {
        selected.value.splice(at, 1);
    }
};

const chosenSessions = computed(() => sessions.value.filter((_, index) => selected.value.includes(index)));

const saveToTasks = () => {
    if (chosenSessions.value.length === 0) {
        toast.info('Select at least one session to add.');
        return;
    }

    saving.value = true;
    router.post(
        route('study-planner.tasks'),
        {
            sessions: chosenSessions.value.map((session) => ({
                title: session.title,
                date: session.date,
                focus: session.focus,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                plan.value = null;
                selected.value = [];
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
};
</script>

<template>
    <div>
        <Head title="Study Planner" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="AI Study Planner"
                    subtitle="Turn your upcoming deadlines into a realistic study schedule, then save the sessions you want as tasks."
                    :icon="SparklesIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Generator + deadlines -->
                    <div class="space-y-6">
                        <Card class="h-fit">
                            <template #header>
                                <h2 class="ui-card-title">Plan settings</h2>
                            </template>

                            <form @submit.prevent="generate" class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label" for="start-date">From</label>
                                        <input id="start-date" v-model="form.start_date" type="date" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label" for="end-date">To</label>
                                        <input id="end-date" v-model="form.end_date" type="date" class="ui-input" />
                                    </div>
                                </div>

                                <div>
                                    <label class="ui-label" for="hours">Study hours per day</label>
                                    <input id="hours" v-model.number="form.hours_per_day" type="number" min="1" max="12" class="ui-input" />
                                </div>

                                <div>
                                    <label class="ui-label" for="focus">Focus (optional)</label>
                                    <textarea
                                        id="focus"
                                        v-model="form.focus"
                                        rows="2"
                                        placeholder="e.g. prioritise data structures, weak on recursion"
                                        class="ui-input resize-none"
                                    ></textarea>
                                </div>

                                <button type="submit" :disabled="generating" class="ui-btn-primary w-full">
                                    <SparklesIcon class="w-4 h-4" />
                                    {{ generating ? 'Generating…' : 'Generate plan' }}
                                </button>
                            </form>
                        </Card>

                        <Card>
                            <template #header>
                                <h2 class="ui-card-title">Upcoming deadlines</h2>
                            </template>

                            <EmptyState
                                v-if="deadlines.length === 0"
                                title="Nothing due"
                                description="You have no upcoming assignments, exams or class tests."
                                :icon="CalendarDaysIcon"
                            />

                            <ul v-else class="space-y-3">
                                <li v-for="(deadline, index) in deadlines" :key="index" class="flex items-start gap-3">
                                    <Badge :variant="deadlineVariant(deadline.type)">{{ deadlineLabel(deadline.type) }}</Badge>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-content truncate">{{ deadline.title }}</p>
                                        <p class="text-xs text-content-muted">
                                            <span v-if="deadline.course">{{ deadline.course }} · </span>Due {{ deadline.date }}
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </Card>
                    </div>

                    <!-- Generated plan -->
                    <div class="lg:col-span-2 space-y-4">
                        <EmptyState
                            v-if="!plan"
                            title="No plan yet"
                            description="Set your date range and study hours, then generate a schedule tailored to your deadlines."
                            :icon="SparklesIcon"
                        />

                        <template v-else>
                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <p class="text-sm text-content-muted">
                                    {{ sessions.length }} session{{ sessions.length === 1 ? '' : 's' }} ·
                                    {{ plan.range.start }} → {{ plan.range.end }}
                                </p>
                                <button type="button" :disabled="saving" class="ui-btn-primary" @click="saveToTasks">
                                    <CheckCircleIcon class="w-4 h-4" />
                                    {{ saving ? 'Saving…' : `Add ${chosenSessions.length} to tasks` }}
                                </button>
                            </div>

                            <Card
                                v-for="(session, index) in sessions"
                                :key="index"
                                padding="p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        :id="`session-${index}`"
                                        type="checkbox"
                                        :checked="selected.includes(index)"
                                        class="mt-1"
                                        @change="toggle(index)"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <label :for="`session-${index}`" class="font-medium text-content cursor-pointer">
                                            {{ session.title }}
                                        </label>
                                        <p v-if="session.focus" class="text-sm text-content-muted mt-0.5">{{ session.focus }}</p>
                                        <div class="flex flex-wrap items-center gap-2 mt-2 text-xs text-content-muted">
                                            <span class="inline-flex items-center gap-1">
                                                <CalendarDaysIcon class="w-3.5 h-3.5" />{{ session.date }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <ClockIcon class="w-3.5 h-3.5" />{{ session.durationMinutes }} min
                                            </span>
                                            <Badge v-if="session.course" variant="primary">{{ session.course }}</Badge>
                                            <Badge v-if="session.relatedDeadline" variant="neutral">{{ session.relatedDeadline }}</Badge>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        </template>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
