<script setup>
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import DirectoryCard from '@/components/messenger/DirectoryCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { UsersIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    // Real data — students enrolled in this faculty's own current-term sections.
    // One entry per course/section a student is enrolled in with this faculty.
    students: { type: Array, default: () => [] },
});

const search = ref('');
const courseFilter = ref('');
const departmentFilter = ref('');
const semesterFilter = ref('');
const sectionFilter = ref('');

// Distinct filter options, derived from the faculty's own roster only.
const courses = computed(() => [...new Set(props.students.map((student) => student.course.code))].sort());
const departments = computed(() =>
    [...new Set(props.students.map((student) => student.department).filter(Boolean))].sort(),
);
const semesters = computed(() =>
    [...new Set(props.students.map((student) => student.semester).filter((value) => value != null))].sort(
        (a, b) => a - b,
    ),
);
const sections = computed(() => [...new Set(props.students.map((student) => student.section))].sort());

const resetFilters = () => {
    search.value = '';
    courseFilter.value = '';
    departmentFilter.value = '';
    semesterFilter.value = '';
    sectionFilter.value = '';
};

const filteredStudents = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.students.filter((student) => {
        const matchesName =
            !query ||
            student.name.toLowerCase().includes(query) ||
            (student.studentId || '').toLowerCase().includes(query);
        const matchesCourse = !courseFilter.value || student.course.code === courseFilter.value;
        const matchesDepartment = !departmentFilter.value || student.department === departmentFilter.value;
        const matchesSemester = !semesterFilter.value || student.semester === semesterFilter.value;
        const matchesSection = !sectionFilter.value || student.section === sectionFilter.value;

        return matchesName && matchesCourse && matchesDepartment && matchesSemester && matchesSection;
    });
});

// Client-side pagination over the filtered roster.
const PER_PAGE = 12;
const currentPage = ref(1);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredStudents.value.length / PER_PAGE)));

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredStudents.value.slice(start, start + PER_PAGE);
});

// Any filter/search change resets to the first page so results aren't hidden.
watch([search, courseFilter, departmentFilter, semesterFilter, sectionFilter], () => {
    currentPage.value = 1;
});

const goToPage = (page) => {
    currentPage.value = Math.min(Math.max(1, page), totalPages.value);
};

const chatHref = (id) => route('faculty.messages', { to: id });

const metaFor = (student) => [
    { label: 'Student ID', value: student.studentId || '—' },
    { label: 'Department', value: student.department || '—' },
    { label: 'Semester', value: student.semester ? `Semester ${student.semester}` : '—' },
    { label: 'Course', value: student.course.code },
];

const selectClass =
    'rounded-control border border-line bg-surface px-3 py-2.5 text-sm text-content focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
</script>

<template>
    <div>
        <Head title="My Students" />
        <AppLayout>
            <div class="page-container space-y-6 py-8">
                <PageHeader
                    title="My Students"
                    subtitle="Students enrolled in the courses and sections you teach."
                    :icon="UsersIcon"
                    :count="students.length"
                >
                    <template #actions>
                        <button type="button" class="ui-btn-ghost text-sm" @click="resetFilters">Reset filters</button>
                    </template>
                </PageHeader>

                <!-- Robust filter bar: Course / Department / Semester / Section -->
                <div class="space-y-3">
                    <div class="relative">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search students by name or ID…"
                            class="w-full rounded-pill border border-line bg-surface py-2.5 pl-11 pr-4 text-sm text-content placeholder:text-content-faint focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <select v-model="courseFilter" :class="selectClass">
                            <option value="">All Courses</option>
                            <option v-for="code in courses" :key="code" :value="code">{{ code }}</option>
                        </select>
                        <select v-model="departmentFilter" :class="selectClass">
                            <option value="">All Departments</option>
                            <option v-for="name in departments" :key="name" :value="name">{{ name }}</option>
                        </select>
                        <select v-model="semesterFilter" :class="selectClass">
                            <option value="">All Semesters</option>
                            <option v-for="value in semesters" :key="value" :value="value">Semester {{ value }}</option>
                        </select>
                        <select v-model="sectionFilter" :class="selectClass">
                            <option value="">All Sections</option>
                            <option v-for="value in sections" :key="value" :value="value">Section {{ value }}</option>
                        </select>
                    </div>
                </div>

                <!-- Student grid -->
                <div v-if="filteredStudents.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <DirectoryCard
                        v-for="student in paginatedStudents"
                        :key="student.rowId"
                        :name="student.name"
                        :avatar="student.avatar"
                        :subtitle="`${student.studentId || '—'} · ${student.department || '—'}`"
                        :tag="`Sec ${student.section}`"
                        :meta="metaFor(student)"
                        :chat-href="chatHref(student.id)"
                    />
                </div>
                <EmptyState
                    v-else
                    :icon="UsersIcon"
                    title="No students found"
                    description="No students match the current filters."
                />

                <!-- Pagination -->
                <div
                    v-if="totalPages > 1"
                    class="flex flex-col items-center justify-between gap-3 sm:flex-row"
                >
                    <span class="text-sm text-content-muted">
                        Page {{ currentPage }} of {{ totalPages }} · {{ filteredStudents.length }} students
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            class="ui-btn-secondary"
                            :disabled="currentPage === 1"
                            @click="goToPage(currentPage - 1)"
                        >
                            Previous
                        </button>
                        <button
                            class="ui-btn-secondary"
                            :disabled="currentPage === totalPages"
                            @click="goToPage(currentPage + 1)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
