<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    AcademicCapIcon,
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

// Real data from the backend (Faculty\CourseController@index → CourseService::facultyCourses).
const props = defineProps({
    courses: { type: Array, default: () => [] },
});

const searchQuery = ref('');

const filteredCourses = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.courses;

    return props.courses.filter((course) =>
        `${course.code} ${course.name}`.toLowerCase().includes(query),
    );
});
</script>

<template>
    <div>
        <Head title="My Courses" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="My Courses"
                    :subtitle="`${courses.length} course(s) you teach this term`"
                    :icon="BookOpenIcon"
                    eyebrow="Teaching"
                >
                    <template #actions>
                        <div class="relative w-full sm:w-72">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-content-faint" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by code or name…"
                                class="ui-input pl-10"
                            />
                        </div>
                    </template>
                </PageHeader>

                <Card padding="p-5 sm:p-6">
                    <EmptyState
                        v-if="filteredCourses.length === 0"
                        :title="courses.length === 0 ? 'No active courses' : 'No courses match your search'"
                        :description="courses.length === 0
                            ? 'You are not teaching any courses yet.'
                            : 'Try a different course code or name.'"
                        :icon="BookOpenIcon"
                    />

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
                        <Link
                            v-for="course in filteredCourses"
                            :key="course.id"
                            :href="route('faculty.courses.show', course.id)"
                            class="group block rounded-card border border-line bg-surface p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="text-sm font-semibold text-content-muted">{{ course.code }}</div>
                                    <h3 class="font-bold text-content">{{ course.name }}</h3>
                                </div>
                                <span class="ui-icon-tile bg-primary-soft text-primary">
                                    <AcademicCapIcon class="w-5 h-5" />
                                </span>
                            </div>
                            <p
                                v-if="course.sections && course.sections.length"
                                class="mb-2 flex flex-wrap items-center gap-1.5"
                            >
                                <span
                                    v-for="label in course.sections"
                                    :key="label"
                                    class="inline-flex items-center rounded-full bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary"
                                >
                                    Section {{ label }}
                                </span>
                            </p>
                            <p class="text-xs text-content-muted mb-4">
                                {{ course.semester }} • {{ course.credits }} credits
                            </p>
                            <div class="flex items-center gap-4 text-sm text-content-muted">
                                <span class="flex items-center gap-1"><UserGroupIcon class="w-4 h-4" /> {{ course.students }}</span>
                                <span class="flex items-center gap-1"><BookOpenIcon class="w-4 h-4" /> {{ course.materials }}</span>
                                <span class="flex items-center gap-1"><DocumentTextIcon class="w-4 h-4" /> {{ course.assignments }}</span>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs text-content-muted mb-1">
                                    <span>Class progress</span>
                                    <span>{{ course.progress }}%</span>
                                </div>
                                <div class="w-full bg-neutral-bg rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: course.progress + '%' }"></div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
