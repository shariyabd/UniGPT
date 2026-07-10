<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    CheckCircleIcon,
    ClockIcon,
    PlusIcon,
    TrashIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    events: { type: Array, default: () => [] },
    today: { type: String, default: '' },
    courses: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
    ics: { type: Object, default: () => ({}) },
});

// Copy the signed feed URL so the student can paste it into Google/Outlook/
// Apple Calendar's "subscribe by URL" and stay auto-synced.
const copyFeedUrl = async () => {
    try {
        await navigator.clipboard.writeText(props.ics.feedUrl);
        toast.success('Subscribe URL copied — paste it into your calendar app.');
    } catch {
        toast.error('Could not copy the URL.');
    }
};

const toast = useToast();
const { confirm } = useConfirm();

// Keep the viewed month/day and scroll position across task mutations — task
// routes redirect back, so Inertia refreshes props while local state persists.
const visitOptions = { preserveScroll: true, preserveState: true };

const typeStyle = {
    assignment: { dot: 'bg-warning-fg', label: 'Deadline', badge: 'warning', icon: ClipboardDocumentCheckIcon, accent: 'border-warning-fg', tile: 'bg-warning-bg text-warning-fg' },
    exam: { dot: 'bg-danger-fg', label: 'Exam', badge: 'danger', icon: AcademicCapIcon, accent: 'border-danger-fg', tile: 'bg-danger-bg text-danger-fg' },
    task: { dot: 'bg-primary', label: 'Task', badge: 'primary', icon: CheckCircleIcon, accent: 'border-primary', tile: 'bg-primary-soft text-primary' },
    office_hours: { dot: 'bg-success-fg', label: 'Office Hours', badge: 'success', icon: ClockIcon, accent: 'border-success-fg', tile: 'bg-success-bg text-success-fg' },
};

const cursor = ref(new Date(props.today ? props.today + 'T00:00:00' : Date.now()));
cursor.value.setDate(1);

const monthLabel = computed(() =>
    cursor.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
);

const eventsByDate = computed(() => {
    const map = {};
    for (const event of props.events) {
        (map[event.date] ??= []).push(event);
    }
    return map;
});

const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const weeks = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const first = new Date(year, month, 1);
    const start = new Date(first);
    start.setDate(1 - first.getDay()); // back up to Sunday

    const result = [];
    let day = new Date(start);
    for (let w = 0; w < 6; w++) {
        const row = [];
        for (let d = 0; d < 7; d++) {
            const key = iso(day);
            row.push({
                key,
                day: day.getDate(),
                inMonth: day.getMonth() === month,
                isToday: key === props.today,
                events: eventsByDate.value[key] ?? [],
            });
            day.setDate(day.getDate() + 1);
        }
        result.push(row);
    }
    return result;
});

const selected = ref(props.today);
const selectedEvents = computed(() =>
    (eventsByDate.value[selected.value] ?? []).slice().sort((a, b) => (a.time ?? '').localeCompare(b.time ?? ''))
);

const upcoming = computed(() =>
    props.events
        .filter((e) => e.date >= props.today && !e.completed)
        .slice(0, 8)
);

const move = (delta) => {
    const next = new Date(cursor.value);
    next.setMonth(next.getMonth() + delta);
    cursor.value = next;
};

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const styleFor = (type) => typeStyle[type] ?? { dot: 'bg-neutral-fg', label: 'Event', badge: 'neutral', icon: ClockIcon, accent: 'border-line', tile: 'bg-neutral-bg text-neutral-fg' };

// ── Task management (the only events the student owns) ──────────────────────
const priorityVariant = (priority) => ({
    high: 'danger',
    medium: 'warning',
    low: 'success',
}[priority] ?? 'neutral');

const showTaskForm = ref(false);
const defaultPriority = computed(() =>
    props.priorities.some((p) => p.value === 'medium') ? 'medium' : (props.priorities[0]?.value ?? 'medium')
);

const taskForm = useForm({
    title: '',
    description: '',
    due_date: '',
    priority: 'medium',
    course_id: null,
    is_completed: false,
});

const openTaskForm = () => {
    taskForm.reset();
    taskForm.clearErrors();
    taskForm.priority = defaultPriority.value;
    taskForm.due_date = selected.value || props.today;
    showTaskForm.value = true;
};

const submitTask = () => {
    taskForm.due_date = selected.value || props.today;
    taskForm.post(route('tasks.store'), {
        ...visitOptions,
        onSuccess: () => {
            taskForm.reset();
            taskForm.priority = defaultPriority.value;
            showTaskForm.value = false;
            // Success toast comes from the server flash (centralized pipeline).
        },
        onError: () => toast.error('Could not add the task.'),
    });
};

const toggleTask = (event) => {
    router.patch(route('tasks.toggle', event.taskId), {}, {
        ...visitOptions,
        onSuccess: () => toast.success(event.completed ? 'Task reopened.' : 'Task completed.'),
        onError: () => toast.error('Could not update the task.'),
    });
};

const removeTask = async (event) => {
    const ok = await confirm({
        title: 'Delete task',
        message: `“${event.title}” will be permanently deleted.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;

    router.delete(route('tasks.destroy', event.taskId), {
        ...visitOptions,
        // Success toast comes from the server flash (centralized pipeline).
        onError: () => toast.error('Could not delete the task.'),
    });
};
</script>

<template>
    <div>
        <Head title="Calendar" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Calendar"
                    subtitle="Deadlines, exams, office hours and your tasks in one place."
                    :icon="CalendarIcon"
                >
                    <template #actions>
                        <a v-if="ics.exportUrl" :href="ics.exportUrl" class="ui-btn-secondary">
                            Export .ics
                        </a>
                        <button v-if="ics.feedUrl" type="button" @click="copyFeedUrl" class="ui-btn-secondary">
                            Copy subscribe URL
                        </button>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Month grid -->
                    <Card class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <button
                                type="button"
                                @click="move(-1)"
                                aria-label="Previous month"
                                class="p-2 rounded-control text-content-muted hover:bg-primary-soft hover:text-primary transition-colors"
                            >
                                <ChevronLeftIcon class="w-5 h-5" />
                            </button>
                            <h2 class="text-card-title font-bold tracking-tight text-content">{{ monthLabel }}</h2>
                            <button
                                type="button"
                                @click="move(1)"
                                aria-label="Next month"
                                class="p-2 rounded-control text-content-muted hover:bg-primary-soft hover:text-primary transition-colors"
                            >
                                <ChevronRightIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="grid grid-cols-7 text-center text-xs font-semibold uppercase tracking-wide text-content-faint mb-1">
                            <div v-for="d in weekdays" :key="d" class="py-1">{{ d }}</div>
                        </div>

                        <div v-for="(week, wi) in weeks" :key="wi" class="grid grid-cols-7 gap-1">
                            <button
                                v-for="cell in week"
                                :key="cell.key"
                                type="button"
                                @click="selected = cell.key"
                                class="aspect-square p-1.5 rounded-control text-left relative transition-colors"
                                :class="[
                                    cell.inMonth ? 'text-content' : 'text-content-faint',
                                    cell.key === selected
                                        ? 'bg-primary-soft'
                                        : 'hover:bg-bg',
                                ]"
                            >
                                <span
                                    :class="[
                                        'text-xs font-medium',
                                        cell.isToday
                                            ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary text-white'
                                            : '',
                                    ]"
                                >{{ cell.day }}</span>
                                <div class="flex gap-0.5 mt-0.5 flex-wrap">
                                    <span
                                        v-for="(ev, i) in cell.events.slice(0, 3)"
                                        :key="i"
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="styleFor(ev.type).dot"
                                    ></span>
                                </div>
                            </button>
                        </div>

                        <!-- Legend -->
                        <div class="flex flex-wrap items-center gap-4 mt-4 pt-3 border-t border-line text-xs">
                            <span
                                v-for="(style, key) in typeStyle"
                                :key="key"
                                class="flex items-center gap-1.5 text-content-muted"
                            >
                                <span class="w-2 h-2 rounded-full" :class="style.dot"></span> {{ style.label }}
                            </span>
                        </div>
                    </Card>

                    <!-- Agenda -->
                    <div class="space-y-6">
                        <Card :icon="CalendarIcon">
                            <template #header>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <h2 class="ui-card-title">Selected day</h2>
                                        <p v-if="selected" class="text-xs text-content-faint">{{ selected }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openTaskForm"
                                        class="ui-btn-ghost text-xs flex-shrink-0"
                                    >
                                        <PlusIcon class="w-4 h-4" />
                                        Add task
                                    </button>
                                </div>
                            </template>

                            <!-- Inline task form, scoped to the selected day -->
                            <form
                                v-if="showTaskForm"
                                @submit.prevent="submitTask"
                                class="mb-4 space-y-3 rounded-control border border-line bg-bg p-3"
                            >
                                <div>
                                    <input
                                        v-model="taskForm.title"
                                        type="text"
                                        placeholder="What needs doing?"
                                        class="ui-input"
                                        autofocus
                                    />
                                    <p v-if="taskForm.errors.title" class="text-xs text-danger-fg mt-1">{{ taskForm.errors.title }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <select v-model="taskForm.priority" class="ui-input" aria-label="Priority">
                                        <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                                    </select>
                                    <select v-model="taskForm.course_id" class="ui-input" aria-label="Course">
                                        <option :value="null">— No course —</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" :disabled="taskForm.processing" class="ui-btn-primary flex-1">
                                        {{ taskForm.processing ? 'Saving…' : 'Add for ' + (selected || today) }}
                                    </button>
                                    <button type="button" @click="showTaskForm = false" class="ui-btn-ghost">Cancel</button>
                                </div>
                            </form>

                            <EmptyState
                                v-if="selectedEvents.length === 0 && !showTaskForm"
                                title="No events"
                                description="Nothing scheduled for this day."
                                :icon="CalendarIcon"
                            />
                            <div v-else-if="selectedEvents.length" class="space-y-2.5">
                                <div
                                    v-for="ev in selectedEvents"
                                    :key="ev.id"
                                    class="flex items-start gap-3 rounded-control border-l-4 bg-bg p-3 transition-colors"
                                    :class="styleFor(ev.type).accent"
                                >
                                    <!-- Tasks: clickable completion toggle. Other events: type icon. -->
                                    <button
                                        v-if="ev.type === 'task'"
                                        type="button"
                                        @click="toggleTask(ev)"
                                        :aria-label="ev.completed ? 'Mark task incomplete' : 'Mark task complete'"
                                        class="mt-0.5 flex-shrink-0 transition-colors"
                                        :class="ev.completed ? 'text-success-fg hover:text-success-fg/80' : 'text-content-faint hover:text-success-fg'"
                                    >
                                        <CheckCircleSolid v-if="ev.completed" class="w-6 h-6" />
                                        <CheckCircleIcon v-else class="w-6 h-6" />
                                    </button>
                                    <span
                                        v-else
                                        class="ui-icon-tile flex-shrink-0"
                                        :class="styleFor(ev.type).tile"
                                    >
                                        <component
                                            :is="styleFor(ev.type).icon"
                                            class="w-5 h-5"
                                        />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-semibold text-content"
                                            :class="{ 'line-through text-content-faint': ev.completed }"
                                        >{{ ev.title }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-content-muted">
                                            <Badge :variant="styleFor(ev.type).badge">{{ styleFor(ev.type).label }}</Badge>
                                            <Badge v-if="ev.type === 'task' && ev.priority" :variant="priorityVariant(ev.priority)">{{ ev.priority }}</Badge>
                                            <span v-if="ev.course" class="truncate">{{ ev.course }}</span>
                                            <span v-if="ev.time" class="inline-flex items-center gap-1">
                                                <ClockIcon class="w-3.5 h-3.5" /> {{ ev.time }}
                                            </span>
                                        </div>
                                    </div>

                                    <button
                                        v-if="ev.type === 'task'"
                                        type="button"
                                        @click="removeTask(ev)"
                                        aria-label="Delete task"
                                        class="ui-btn-ghost p-1.5 flex-shrink-0 text-content-faint hover:text-danger-fg"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                    <Link
                                        v-else-if="ev.type === 'assignment' && ev.assignmentId"
                                        :href="route('assignments.show', ev.assignmentId)"
                                        aria-label="Open assignment"
                                        title="Open assignment"
                                        class="ui-btn-ghost p-1.5 flex-shrink-0 text-content-faint hover:text-primary"
                                    >
                                        <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>
                        </Card>

                        <Card title="Upcoming" :icon="ClockIcon">
                            <EmptyState
                                v-if="upcoming.length === 0"
                                title="Nothing on the horizon"
                                description="You're all caught up."
                                :icon="CheckCircleIcon"
                            />
                            <div v-else class="space-y-1">
                                <div
                                    v-for="ev in upcoming"
                                    :key="ev.id"
                                    class="flex items-center justify-between gap-3 rounded-control px-2 py-2 hover:bg-bg transition-colors"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0" :class="styleFor(ev.type).dot"></span>
                                        <span class="text-sm text-content truncate">{{ ev.title }}</span>
                                    </div>
                                    <span class="text-xs text-content-faint flex-shrink-0">{{ ev.date.slice(5) }}</span>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
