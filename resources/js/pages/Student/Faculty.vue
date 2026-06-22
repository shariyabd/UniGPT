<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import DirectoryCard from '@/components/messenger/DirectoryCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { UserGroupIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    // Real data — faculty teaching this student's current-term sections.
    // One entry per course/section (a faculty teaching two of your courses
    // appears once per course).
    faculty: { type: Array, default: () => [] },
});

const search = ref('');
const courseFilter = ref('');

// A student belongs to one department and every course in their program is
// owned by it, so there is no department dimension to filter here — only the
// student's own courses. Filtering is by name + course.
const courses = computed(() =>
    [...new Set(props.faculty.map((member) => member.course.code))].sort(),
);

const filteredFaculty = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.faculty.filter((member) => {
        const matchesName = !query || member.name.toLowerCase().includes(query);
        const matchesCourse = !courseFilter.value || member.course.code === courseFilter.value;

        return matchesName && matchesCourse;
    });
});

const chatHref = (id) => route('messages', { to: id });

const metaFor = (member) => [
    { label: 'Course', value: `${member.course.code} — ${member.course.name}` },
    { label: 'Section', value: member.section },
    { label: 'Semester', value: member.semester ? `Semester ${member.semester}` : '—' },
    { label: 'Email', value: member.email },
];
</script>

<template>
    <div>
        <Head title="My Faculty" />
        <AppLayout>
            <div class="page-container space-y-6 py-8">
                <PageHeader
                    title="My Faculty"
                    subtitle="Faculty teaching your current courses. Open a chat to reach out."
                    :icon="UserGroupIcon"
                    :count="faculty.length"
                />

                <!-- Filters -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search faculty by name…"
                            class="w-full rounded-pill border border-line bg-surface py-2.5 pl-11 pr-4 text-sm text-content placeholder:text-content-faint focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                    <select
                        v-model="courseFilter"
                        class="rounded-control border border-line bg-surface px-3 py-2.5 text-sm text-content focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="">All Courses</option>
                        <option v-for="code in courses" :key="code" :value="code">{{ code }}</option>
                    </select>
                </div>

                <!-- Faculty grid -->
                <div v-if="filteredFaculty.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <DirectoryCard
                        v-for="member in filteredFaculty"
                        :key="member.rowId"
                        :name="member.name"
                        :avatar="member.avatar"
                        :subtitle="member.department || 'Faculty'"
                        :tag="member.course.code"
                        :meta="metaFor(member)"
                        :chat-href="chatHref(member.id)"
                    />
                </div>
                <EmptyState
                    v-else
                    :icon="UserGroupIcon"
                    title="No faculty found"
                    description="No faculty match your current search and filters."
                />
            </div>
        </AppLayout>
    </div>
</template>
