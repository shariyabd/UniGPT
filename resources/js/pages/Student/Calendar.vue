<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeftIcon, ChevronRightIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    events: { type: Array, default: () => [] },
    today: { type: String, default: '' },
});

const typeStyle = {
    assignment: { dot: 'bg-blue-500', label: 'Assignment', text: 'text-blue-600 dark:text-blue-400' },
    exam: { dot: 'bg-rose-500', label: 'Exam', text: 'text-rose-600 dark:text-rose-400' },
    task: { dot: 'bg-emerald-500', label: 'Task', text: 'text-emerald-600 dark:text-emerald-400' },
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
</script>

<template>
    <Head title="Calendar" />

    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center">
                    <CalendarDaysIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Calendar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Deadlines, exams and your tasks in one place.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Month grid -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <button type="button" @click="move(-1)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <ChevronLeftIcon class="w-5 h-5 text-gray-500" />
                        </button>
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ monthLabel }}</h2>
                        <button type="button" @click="move(1)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <ChevronRightIcon class="w-5 h-5 text-gray-500" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 text-center text-xs font-medium text-gray-400 mb-1">
                        <div v-for="d in weekdays" :key="d" class="py-1">{{ d }}</div>
                    </div>

                    <div v-for="(week, wi) in weeks" :key="wi" class="grid grid-cols-7">
                        <button
                            v-for="cell in week"
                            :key="cell.key"
                            type="button"
                            @click="selected = cell.key"
                            class="aspect-square p-1 rounded-lg text-left relative transition-colors"
                            :class="[
                                cell.inMonth ? 'text-gray-900 dark:text-white' : 'text-gray-300 dark:text-gray-600',
                                cell.key === selected ? 'ring-2 ring-indigo-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                            ]"
                        >
                            <span :class="['text-xs', cell.isToday ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white' : '']">{{ cell.day }}</span>
                            <div class="flex gap-0.5 mt-0.5 flex-wrap">
                                <span
                                    v-for="(ev, i) in cell.events.slice(0, 3)"
                                    :key="i"
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="typeStyle[ev.type]?.dot"
                                ></span>
                            </div>
                        </button>
                    </div>

                    <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs">
                        <span v-for="(style, key) in typeStyle" :key="key" class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full" :class="style.dot"></span> {{ style.label }}
                        </span>
                    </div>
                </div>

                <!-- Agenda -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">{{ selected || 'Selected day' }}</h3>
                        <div v-if="selectedEvents.length === 0" class="text-sm text-gray-400">No events this day.</div>
                        <div v-else class="space-y-2">
                            <div v-for="ev in selectedEvents" :key="ev.id" class="flex items-start gap-2">
                                <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0" :class="typeStyle[ev.type]?.dot"></span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ev.title }}</p>
                                    <p class="text-xs text-gray-400">
                                        <span :class="typeStyle[ev.type]?.text">{{ typeStyle[ev.type]?.label }}</span>
                                        <span v-if="ev.course"> · {{ ev.course }}</span>
                                        <span v-if="ev.time"> · {{ ev.time }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Upcoming</h3>
                        <div v-if="upcoming.length === 0" class="text-sm text-gray-400">Nothing on the horizon.</div>
                        <div v-else class="space-y-2">
                            <div v-for="ev in upcoming" :key="ev.id" class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="typeStyle[ev.type]?.dot"></span>
                                    <span class="text-sm text-gray-700 dark:text-gray-200 truncate">{{ ev.title }}</span>
                                </div>
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-2">{{ ev.date.slice(5) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
