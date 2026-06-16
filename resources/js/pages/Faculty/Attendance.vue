<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    ClipboardDocumentCheckIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    date: {
        type: String,
        required: true,
    },
    roster: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => ['present', 'absent', 'late', 'excused'],
    },
    summary: {
        type: Object,
        default: () => ({ sessions: 0, records: 0, rate: null }),
    },
});

// Local, editable copy of the roster (status per student).
const entries = ref(props.roster.map((student) => ({ ...student })));
const selectedDate = ref(props.date);

// Reloading on a new date re-renders the page with fresh roster props.
watch(entries, () => {}, { deep: true });

const reloadForDate = () => {
    router.get(
        route('faculty.courses.attendance', props.course.id),
        { date: selectedDate.value },
        { preserveState: false, preserveScroll: true },
    );
};

const setStatus = (studentId, status) => {
    const entry = entries.value.find((e) => e.id === studentId);
    if (entry) entry.status = status;
};

const setAll = (status) => {
    entries.value.forEach((entry) => { entry.status = status; });
};

const presentCount = computed(
    () => entries.value.filter((e) => e.status !== 'absent').length,
);

const absentCount = computed(
    () => entries.value.filter((e) => e.status === 'absent').length,
);

const statusClasses = (status, active) => {
    const map = {
        present: active
            ? 'bg-success-fg text-white border-success-fg shadow-sm'
            : 'text-success-fg border-line hover:bg-success-bg',
        absent: active
            ? 'bg-danger-fg text-white border-danger-fg shadow-sm'
            : 'text-danger-fg border-line hover:bg-danger-bg',
        late: active
            ? 'bg-warning-fg text-white border-warning-fg shadow-sm'
            : 'text-warning-fg border-line hover:bg-warning-bg',
        excused: active
            ? 'bg-primary text-white border-primary shadow-sm'
            : 'text-primary border-line hover:bg-primary-soft',
    };
    return map[status] ?? '';
};

const form = useForm({ date: props.date, entries: [] });

const submit = () => {
    form.date = selectedDate.value;
    form.entries = entries.value.map((e) => ({
        user_id: e.id,
        status: e.status,
        notes: e.notes ?? null,
    }));
    form.post(route('faculty.courses.attendance.store', props.course.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div>
        <Head :title="`Attendance — ${course.code}`" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Attendance"
                    :subtitle="`${course.code} — ${course.name}`"
                    :icon="ClipboardDocumentCheckIcon"
                    eyebrow="Faculty"
                >
                    <template #actions>
                        <Link
                            :href="route('faculty.courses.show', course.id)"
                            class="ui-btn-secondary"
                        >
                            <ArrowLeftIcon class="w-4 h-4 mr-1.5" />
                            Back to course
                        </Link>
                    </template>
                </PageHeader>

                <!-- Summary stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Enrolled students"
                        :value="entries.length"
                        :icon="UserGroupIcon"
                        color="emerald"
                    />
                    <StatCard
                        label="Present today"
                        :value="presentCount"
                        :icon="CheckCircleIcon"
                        color="emerald"
                    />
                    <StatCard
                        label="Absent today"
                        :value="absentCount"
                        :icon="XCircleIcon"
                        color="rose"
                    />
                    <StatCard
                        label="Course rate"
                        :value="summary.rate !== null ? `${summary.rate}%` : '—'"
                        :icon="ChartBarIcon"
                        color="blue"
                    />
                </div>

                <!-- Session controls -->
                <Card title="Session" :icon="CalendarDaysIcon">
                    <div class="flex flex-wrap items-end gap-4 justify-between">
                        <div>
                            <label for="session-date" class="ui-label">
                                Session date
                            </label>
                            <input
                                id="session-date"
                                v-model="selectedDate"
                                type="date"
                                class="ui-input max-w-xs"
                                @change="reloadForDate"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-content-muted self-center mr-1">Mark all:</span>
                            <button
                                v-for="status in statuses"
                                :key="status"
                                type="button"
                                class="px-3 py-1.5 rounded-control text-sm font-medium border capitalize transition-colors"
                                :class="statusClasses(status, false)"
                                @click="setAll(status)"
                            >
                                {{ status }}
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Roster -->
                <Card v-if="entries.length" padding="p-0" title="Student roster" :icon="UserGroupIcon">
                    <div class="divide-y divide-line">
                        <div
                            v-for="student in entries"
                            :key="student.id"
                            class="flex items-center justify-between gap-4 px-4 sm:px-6 py-4 hover:bg-bg transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    :src="student.avatar"
                                    :alt="student.name"
                                    class="w-9 h-9 rounded-full ring-1 ring-line"
                                />
                                <div class="min-w-0">
                                    <p class="font-medium text-content truncate">{{ student.name }}</p>
                                    <p class="text-xs text-content-muted">{{ student.studentId }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5 justify-end">
                                <button
                                    v-for="status in statuses"
                                    :key="status"
                                    type="button"
                                    class="px-3 py-1.5 rounded-control text-sm font-medium border capitalize transition-colors"
                                    :class="statusClasses(status, student.status === status)"
                                    @click="setStatus(student.id, status)"
                                >
                                    {{ status }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 sm:px-6 py-4 bg-bg border-t border-line flex justify-end">
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="ui-btn-primary disabled:opacity-50"
                            @click="submit"
                        >
                            <CheckCircleIcon class="w-5 h-5 mr-1.5" />
                            {{ form.processing ? 'Saving…' : 'Save attendance' }}
                        </button>
                    </div>
                </Card>

                <EmptyState
                    v-else
                    title="No students enrolled"
                    description="No students are enrolled in this course."
                    :icon="UserGroupIcon"
                />
            </div>
        </AppLayout>
    </div>
</template>
