<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    events: { type: Array, default: () => [] },
    today: { type: String, default: '' },
});

const typeStyle = {
    assignment: { dot: 'bg-warning-fg', label: 'Deadline', badge: 'warning', icon: ClipboardDocumentCheckIcon, accent: 'border-warning-fg', tile: 'bg-warning-bg text-warning-fg' },
    exam: { dot: 'bg-danger-fg', label: 'Exam', badge: 'danger', icon: AcademicCapIcon, accent: 'border-danger-fg', tile: 'bg-danger-bg text-danger-fg' },
    task: { dot: 'bg-primary', label: 'Task', badge: 'primary', icon: CheckCircleIcon, accent: 'border-primary', tile: 'bg-primary-soft text-primary' },
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
        .filter((e) => e.date >= props.today)
        .slice(0, 8)
);

const move = (delta) => {
    const next = new Date(cursor.value);
    next.setMonth(next.getMonth() + delta);
    cursor.value = next;
};

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const styleFor = (type) => typeStyle[type] ?? { dot: 'bg-neutral-fg', label: 'Event', badge: 'neutral', icon: ClockIcon, accent: 'border-line', tile: 'bg-neutral-bg text-neutral-fg' };
</script>

<template>
    <div>
        <Head title="Calendar" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Calendar"
                    subtitle="Deadlines, exams and your tasks in one place."
                    :icon="CalendarIcon"
                />

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
                        <Card title="Selected day" :subtitle="selected || undefined" :icon="CalendarIcon">
                            <EmptyState
                                v-if="selectedEvents.length === 0"
                                title="No events"
                                description="Nothing scheduled for this day."
                                :icon="CalendarIcon"
                            />
                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="ev in selectedEvents"
                                    :key="ev.id"
                                    class="flex items-start gap-3 rounded-control border-l-4 bg-bg p-3 transition-colors"
                                    :class="styleFor(ev.type).accent"
                                >
                                    <span
                                        class="ui-icon-tile flex-shrink-0"
                                        :class="styleFor(ev.type).tile"
                                    >
                                        <component
                                            :is="styleFor(ev.type).icon"
                                            class="w-5 h-5"
                                        />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-content">{{ ev.title }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-content-muted">
                                            <Badge :variant="styleFor(ev.type).badge">{{ styleFor(ev.type).label }}</Badge>
                                            <span v-if="ev.course" class="truncate">{{ ev.course }}</span>
                                            <span v-if="ev.time" class="inline-flex items-center gap-1">
                                                <ClockIcon class="w-3.5 h-3.5" /> {{ ev.time }}
                                            </span>
                                        </div>
                                    </div>
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
