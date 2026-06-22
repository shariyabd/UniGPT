<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MessengerShell from '@/components/messenger/MessengerShell.vue';
import { ChatBubbleLeftRightIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    // Real data — faculty the student can message (one row per course/section).
    faculty: { type: Array, default: () => [] },
    // Contact id deep-linked from the "My Faculty" list ("?to=" → server prop).
    initialContactId: { type: [Number, null], default: null },
});

const search = ref('');

// A conversation is per-person, so dedupe by faculty id (a faculty teaching two
// of the student's courses is a single chat). Then normalise to the
// MessengerShell contact shape.
const contacts = computed(() => {
    const query = search.value.trim().toLowerCase();
    const byPerson = new Map();

    for (const member of props.faculty) {
        if (query && !member.name.toLowerCase().includes(query)) {
            continue;
        }
        if (byPerson.has(member.id)) {
            continue;
        }
        byPerson.set(member.id, {
            id: member.id,
            name: member.name,
            avatar: member.avatar,
            subtitle: member.department || 'Faculty',
            tag: member.course.code,
            tagVariant: 'primary',
            meta: [
                { label: 'Department', value: member.department || '—' },
                { label: 'Course', value: `${member.course.code} — ${member.course.name}` },
                { label: 'Section', value: member.section },
                { label: 'Email', value: member.email },
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
                    subtitle="Search for a faculty member to start a conversation."
                    :icon="ChatBubbleLeftRightIcon"
                />

                <MessengerShell
                    :contacts="contacts"
                    :initial-selected-id="initialContactId"
                    select-prompt="Search for a faculty member on the left to start a chat."
                    empty-list-text="No faculty match your search."
                >
                    <template #toolbar>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-content-faint" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search faculty…"
                                class="w-full rounded-pill border border-line bg-surface py-2 pl-9 pr-3 text-sm text-content placeholder:text-content-faint focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </template>
                </MessengerShell>
            </div>
        </AppLayout>
    </div>
</template>
