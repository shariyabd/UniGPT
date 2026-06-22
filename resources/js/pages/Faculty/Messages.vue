<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MessengerShell from '@/components/messenger/MessengerShell.vue';
import { ChatBubbleLeftRightIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    // Real data — students this faculty can message (one row per course/section).
    students: { type: Array, default: () => [] },
    // Contact id deep-linked from the "My Students" list ("?to=" → server prop).
    initialContactId: { type: [Number, null], default: null },
});

const search = ref('');

// A conversation is per-person, so dedupe by student id (a student in two of the
// faculty's courses is a single chat). Then normalise to the MessengerShell shape.
const contacts = computed(() => {
    const query = search.value.trim().toLowerCase();
    const byPerson = new Map();

    for (const student of props.students) {
        const matches =
            !query ||
            student.name.toLowerCase().includes(query) ||
            (student.studentId || '').toLowerCase().includes(query);
        if (!matches || byPerson.has(student.id)) {
            continue;
        }
        byPerson.set(student.id, {
            id: student.id,
            name: student.name,
            avatar: student.avatar,
            subtitle: `${student.studentId || '—'} · ${student.department || '—'}`,
            tag: `Sec ${student.section}`,
            tagVariant: 'primary',
            meta: [
                { label: 'Student ID', value: student.studentId || '—' },
                { label: 'Department', value: student.department || '—' },
                { label: 'Semester', value: student.semester ? `Semester ${student.semester}` : '—' },
                { label: 'Section', value: student.section },
                { label: 'Course', value: `${student.course.code} — ${student.course.name}` },
                { label: 'Email', value: student.email },
            ],
        });
    }

    return [...byPerson.values()];
});
</script>

<template>
    <div>
        <Head title="Messages" />
        <AppLayout>
            <div class="page-container space-y-6 py-8">
                <PageHeader
                    title="Messages"
                    subtitle="Search for a student to start a conversation."
                    :icon="ChatBubbleLeftRightIcon"
                />

                <MessengerShell
                    :contacts="contacts"
                    :initial-selected-id="initialContactId"
                    select-prompt="Search for a student on the left to start a chat."
                    empty-list-text="No students match your search."
                >
                    <template #toolbar>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-content-faint" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search students by name or ID…"
                                class="w-full rounded-pill border border-line bg-surface py-2 pl-9 pr-3 text-sm text-content placeholder:text-content-faint focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </template>
                </MessengerShell>
            </div>
        </AppLayout>
    </div>
</template>
