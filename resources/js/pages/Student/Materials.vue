<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    DocumentTextIcon,
    VideoCameraIcon,
    BookOpenIcon,
    AcademicCapIcon,
    EyeIcon,
    PlayIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    ArrowDownTrayIcon,
    PresentationChartLineIcon,
    BeakerIcon,
    ChartBarIcon,
    Squares2X2Icon,
    ListBulletIcon,
    SparklesIcon,
    FolderIcon,
    InboxIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';

// Server props
const props = defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
    enrolledCourses: {
        type: Array,
        default: () => [],
    },
});

const pageData = usePage();
const toast = useToast();

// Component state
const searchQuery = ref('');
const selectedMaterialType = ref('all');
const selectedWeek = ref('all');
const viewMode = ref('grid'); // grid or list

// Per-student completion, seeded from the DB and kept in sync via the API.
const completedMaterialIds = ref(new Set(
    (props.courses || [])
        .flatMap((course) => course.materials || [])
        .filter((material) => material.completed)
        .map((material) => material.id),
));

// Student context (derived from authenticated user)
const studentContext = computed(() => {
    const authUser = pageData.props?.auth?.user || {};
    return {
        name: authUser.name || 'Student',
        department: authUser.department?.name || authUser.department || '',
        semester: authUser.semester ? `${authUser.semester} Semester` : '',
        year: '',
    };
});

// Enrolled courses grouped into the single "Current Semester" group the sidebar expects.
const enrolledCourses = computed(() => {
    const mappedCourses = (props.enrolledCourses || []).map((course) => {
        const courseEntry = (props.courses || []).find((c) => c.id === course.id);
        const courseMaterialList = courseEntry?.materials || [];
        const completedMaterials = courseMaterialList.filter((m) => completedMaterialIds.value.has(m.id)).length;

        return {
            id: course.id,
            code: course.code,
            name: course.name,
            instructor: course.instructor,
            totalMaterials: courseMaterialList.length,
            completedMaterials,
            credits: course.credits,
            semester: course.semester,
        };
    });

    if (mappedCourses.length === 0) {
        return [];
    }

    return [
        {
            semester: 'current',
            title: 'Current Semester',
            courses: mappedCourses,
        },
    ];
});

// Materials keyed by course id, mapped into the {courseInfo, weeks} shape the
// template iterates. Materials grouped into weeks; week status derived from completion.
const courseMaterials = computed(() => {
    const result = {};

    (props.courses || []).forEach((course) => {
        const enrolled = (props.enrolledCourses || []).find((c) => c.id === course.id);

        const weeksMap = {};
        (course.materials || []).forEach((material) => {
            const weekNumber = material.week || 1;
            if (!weeksMap[weekNumber]) {
                weeksMap[weekNumber] = {
                    weekNumber,
                    title: `Week ${weekNumber}`,
                    addedDate: material.uploadedAt || null,
                    materials: [],
                };
            }
            // Earliest upload date in the week becomes its "added" date.
            if (material.uploadedAt && (!weeksMap[weekNumber].addedDate || material.uploadedAt < weeksMap[weekNumber].addedDate)) {
                weeksMap[weekNumber].addedDate = material.uploadedAt;
            }
            weeksMap[weekNumber].materials.push({
                id: material.id,
                title: material.title,
                description: material.description,
                type: material.type,
                format: material.type,
                fileSize: material.fileSize,
                uploadedAt: material.uploadedAt,
                downloadCount: material.downloads,
                completed: completedMaterialIds.value.has(material.id),
                hasFile: material.hasFile,
                downloadUrl: material.downloadUrl,
                documentId: material.documentId,
            });
        });

        const weeks = Object.values(weeksMap)
            .map((week) => {
                const completed = week.materials.filter((m) => m.completed).length;
                let status = 'not-started';
                if (completed === week.materials.length && week.materials.length > 0) {
                    status = 'completed';
                } else if (completed > 0) {
                    status = 'in-progress';
                }
                return { ...week, status };
            })
            .sort((a, b) => a.weekNumber - b.weekNumber);

        result[course.id] = {
            courseInfo: {
                code: course.code,
                name: course.name,
                instructor: enrolled?.instructor || '',
                semester: enrolled?.semester || '',
                credits: enrolled?.credits || 0,
            },
            weeks,
        };
    });

    return result;
});

const selectedCourseId = ref(null);

// Material type options (counts derived from the selected course's materials)
const materialTypes = computed(() => {
    const allMaterials = (currentMaterials.value.weeks || []).flatMap((week) => week.materials || []);
    const countByType = (type) => allMaterials.filter((material) => material.type === type).length;

    return [
        { value: 'all', label: 'All Materials', icon: DocumentTextIcon, count: allMaterials.length },
        { value: 'lecture', label: 'Lectures', icon: PresentationChartLineIcon, count: countByType('lecture') },
        { value: 'assignment', label: 'Assignments', icon: AcademicCapIcon, count: countByType('assignment') },
        { value: 'reading', label: 'Readings', icon: BookOpenIcon, count: countByType('reading') },
        { value: 'lab', label: 'Lab Work', icon: BeakerIcon, count: countByType('lab') },
    ];
});

const currentCourse = computed(() =>
    enrolledCourses.value
        .flatMap((semester) => semester.courses)
        .find((course) => course.id === selectedCourseId.value),
);

const currentMaterials = computed(() => courseMaterials.value[selectedCourseId.value] || { weeks: [] });

const filteredWeeks = computed(() => {
    if (!currentMaterials.value.weeks) return [];

    return currentMaterials.value.weeks.filter((week) => {
        const matchesWeek = selectedWeek.value === 'all' || week.weekNumber === parseInt(selectedWeek.value);
        const matchesSearch = !searchQuery.value ||
            week.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            week.materials.some((material) =>
                material.title.toLowerCase().includes(searchQuery.value.toLowerCase()),
            );

        if (selectedMaterialType.value !== 'all') {
            return matchesWeek && matchesSearch && week.materials.some((m) => m.type === selectedMaterialType.value);
        }

        return matchesWeek && matchesSearch;
    });
});

const weekOptions = computed(() => {
    if (!currentMaterials.value.weeks) return [{ value: 'all', label: 'All Weeks' }];

    const weeks = currentMaterials.value.weeks.map((week) => ({
        value: week.weekNumber.toString(),
        label: `Week ${week.weekNumber}`,
    }));

    return [{ value: 'all', label: 'All Weeks' }, ...weeks];
});

const overallProgress = computed(() => {
    if (!currentCourse.value || !currentCourse.value.totalMaterials) return 0;
    return Math.round((currentCourse.value.completedMaterials / currentCourse.value.totalMaterials) * 100);
});

// Whether the current course has at least one downloadable file.
const hasDownloadableMaterials = computed(() =>
    (currentMaterials.value.weeks || []).some((week) => week.materials.some((m) => m.downloadUrl)),
);

// Utility functions
const getMaterialIcon = (type, format) => {
    if (format === 'video') return VideoCameraIcon;
    if (type === 'assignment') return AcademicCapIcon;
    if (type === 'reading') return BookOpenIcon;
    if (type === 'lab') return BeakerIcon;
    if (type === 'lecture') return PresentationChartLineIcon;
    return DocumentTextIcon;
};

const getMaterialBadgeVariant = (type) => ({
    lecture: 'info',
    assignment: 'success',
    reading: 'violet',
    lab: 'warning',
    exam: 'danger',
}[type] || 'slate');

const getWeekBadgeVariant = (status) => ({
    completed: 'success',
    'in-progress': 'info',
    'not-started': 'slate',
}[status] || 'slate');

const weekStatusLabel = (status) => ({
    completed: 'Completed',
    'in-progress': 'In progress',
    'not-started': 'Not started',
}[status] || status);

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }
    return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
};

const weekCompletionPercent = (week) => {
    if (!week.materials.length) return 0;
    return Math.round((week.materials.filter((m) => m.completed).length / week.materials.length) * 100);
};

// ── Completion persistence ──────────────────────────────────────────────────
const setCompletion = async (material, completed) => {
    try {
        await axios.patch(route('materials.completion', material.id), { completed });
        const next = new Set(completedMaterialIds.value);
        completed ? next.add(material.id) : next.delete(material.id);
        completedMaterialIds.value = next;
        return true;
    } catch (e) {
        toast.error('Could not update progress.');
        return false;
    }
};

const toggleComplete = async (material) => {
    const next = !completedMaterialIds.value.has(material.id);
    const ok = await setCompletion(material, next);
    if (ok) {
        toast.success(next ? 'Marked as complete.' : 'Marked as incomplete.');
    }
};

const markComplete = (material) => {
    if (!material || material.id == null || completedMaterialIds.value.has(material.id)) return;
    setCompletion(material, true);
};

// ── Download / open actions ─────────────────────────────────────────────────
const triggerDownload = (url) => {
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.rel = 'noopener';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
};

const viewMaterial = (material) => {
    if (!material.downloadUrl) {
        toast.info('No file is attached to this material yet.');
        return;
    }
    markComplete(material);
    window.open(material.downloadUrl, '_blank');
};

const downloadMaterial = (material) => {
    if (!material.downloadUrl) {
        toast.info('No file is attached to this material yet.');
        return;
    }
    markComplete(material);
    triggerDownload(material.downloadUrl);
};

const downloadWeekMaterials = (week) => {
    const downloadable = (week.materials || []).filter((m) => m.downloadUrl);
    if (downloadable.length === 0) {
        toast.info('No downloadable files in this week.');
        return;
    }
    downloadable.forEach((material) => {
        triggerDownload(material.downloadUrl);
        markComplete(material);
    });
};

const downloadAllMaterials = () => {
    const downloadable = (currentMaterials.value.weeks || [])
        .flatMap((week) => week.materials)
        .filter((m) => m.downloadUrl);

    if (downloadable.length === 0) {
        toast.info('No downloadable files for this course yet.');
        return;
    }
    downloadable.forEach((material) => {
        triggerDownload(material.downloadUrl);
        markComplete(material);
    });
    toast.success(`Downloading ${downloadable.length} file${downloadable.length > 1 ? 's' : ''}.`);
};

const askAIAboutMaterial = (material) => {
    const query = `Explain the concepts in ${material.title}`;
    window.open(`/chat?q=${encodeURIComponent(query)}`, '_blank');
};

const selectCourse = (courseId) => {
    selectedCourseId.value = courseId;
    selectedWeek.value = 'all';
    selectedMaterialType.value = 'all';
    searchQuery.value = '';
};

onMounted(() => {
    const firstCourse = enrolledCourses.value[0]?.courses?.[0];
    if (firstCourse) {
        selectedCourseId.value = firstCourse.id;
    }
});
</script>

<template>
    <div>
        <Head title="Course Materials" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Course Materials"
                    subtitle="Access all your course materials organized by week"
                    :icon="BookOpenIcon"
                    :eyebrow="[studentContext.department, studentContext.year, studentContext.semester].filter(Boolean).join(' • ') || undefined"
                >
                    <template #actions>
                        <Link href="/roadmap" class="ui-btn-secondary">
                            <ChartBarIcon class="w-4 h-4" />
                            View Roadmap
                        </Link>
                        <Link href="/dashboard" class="ui-btn-primary">
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                    <Card>
                        <div class="flex items-center gap-4">
                            <div class="ui-icon-tile h-11 w-11 bg-primary-soft text-primary">
                                <DocumentTextIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-stat text-content">{{ currentCourse?.totalMaterials || 0 }}</div>
                                <div class="text-xs text-content-muted">Total Materials</div>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <div class="flex items-center gap-4">
                            <div class="ui-icon-tile h-11 w-11 bg-success-bg text-success-fg">
                                <CheckCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-stat text-content">{{ currentCourse?.completedMaterials || 0 }}</div>
                                <div class="text-xs text-content-muted">Completed</div>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <div class="flex items-center gap-4">
                            <div class="ui-icon-tile h-11 w-11 bg-primary-soft text-primary">
                                <ChartBarIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-stat text-content">{{ overallProgress }}%</div>
                                <div class="text-xs text-content-muted">Progress</div>
                            </div>
                        </div>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Sidebar - Course Selection & Filters -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Current Semester Courses -->
                        <Card title="Current Semester" :icon="FolderIcon">
                            <div class="space-y-3">
                                <button
                                    v-for="course in enrolledCourses[0]?.courses || []"
                                    :key="course.id"
                                    @click="selectCourse(course.id)"
                                    :class="`w-full text-left p-4 rounded-control border transition-all duration-200 hover:-translate-y-0.5 ${
                                        selectedCourseId === course.id
                                            ? 'bg-primary-soft border-primary shadow-card'
                                            : 'bg-surface border-line hover:shadow-card'
                                    }`"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <div :class="`w-2.5 h-2.5 rounded-full ${selectedCourseId === course.id ? 'bg-primary' : 'bg-line'}`"></div>
                                        <div class="font-semibold text-content text-sm">
                                            {{ course.code }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-content-muted mb-3 line-clamp-2">
                                        {{ course.name }}
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-xs text-content-muted">
                                            {{ course.completedMaterials }}/{{ course.totalMaterials }}
                                        </div>
                                        <div class="flex-1 bg-neutral-bg rounded-full h-1.5">
                                            <div
                                                class="bg-primary h-1.5 rounded-full transition-all duration-500"
                                                :style="{ width: (course.totalMaterials ? course.completedMaterials / course.totalMaterials * 100 : 0) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-medium text-content-muted">
                                            {{ course.totalMaterials ? Math.round(course.completedMaterials / course.totalMaterials * 100) : 0 }}%
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </Card>

                        <!-- Material Type Filter -->
                        <Card title="Material Types">
                            <div class="space-y-2">
                                <button
                                    v-for="type in materialTypes"
                                    :key="type.value"
                                    @click="selectedMaterialType = type.value"
                                    :class="`w-full flex items-center justify-between p-3 rounded-control text-sm transition-all duration-200 ${
                                        selectedMaterialType === type.value
                                            ? 'bg-primary text-white'
                                            : 'text-content-muted hover:bg-primary-soft hover:text-primary'
                                    }`"
                                >
                                    <div class="flex items-center gap-3">
                                        <component :is="type.icon" class="w-5 h-5" />
                                        <span class="font-medium">{{ type.label }}</span>
                                    </div>
                                    <span :class="`px-2 py-0.5 rounded-pill text-xs font-medium ${selectedMaterialType === type.value ? 'bg-white/20 text-white' : 'bg-neutral-bg text-neutral-fg'}`">
                                        {{ type.count }}
                                    </span>
                                </button>
                            </div>
                        </Card>

                        <!-- Week Filter -->
                        <Card title="Week Filter">
                            <label for="week-filter" class="ui-label">Week</label>
                            <select
                                id="week-filter"
                                v-model="selectedWeek"
                                class="ui-input"
                            >
                                <option
                                    v-for="week in weekOptions"
                                    :key="week.value"
                                    :value="week.value"
                                >
                                    {{ week.label }}
                                </option>
                            </select>
                        </Card>

                        <!-- Quick Actions -->
                        <Card title="Quick Actions">
                            <div class="space-y-2">
                                <button
                                    @click="downloadAllMaterials"
                                    :disabled="!hasDownloadableMaterials"
                                    class="w-full flex items-center gap-3 p-3 rounded-control text-sm font-medium bg-success-bg text-success-fg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                    Download All Materials
                                </button>
                                <button
                                    @click="askAIAboutMaterial({ title: 'course concepts' })"
                                    class="w-full flex items-center gap-3 p-3 rounded-control text-sm font-medium bg-primary-soft text-primary hover:opacity-90 transition-opacity"
                                >
                                    <SparklesIcon class="w-4 h-4" />
                                    AI Study Assistant
                                </button>
                                <Link
                                    href="/roadmap"
                                    class="w-full flex items-center gap-3 p-3 rounded-control text-sm font-medium bg-primary-soft text-primary hover:opacity-90 transition-opacity"
                                >
                                    <ChartBarIcon class="w-4 h-4" />
                                    View Roadmap
                                </Link>
                            </div>
                        </Card>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Search and Controls -->
                        <Card>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Search -->
                                <div class="flex-1 relative">
                                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-content-faint" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search materials by title or description..."
                                        class="ui-input pl-10"
                                    />
                                </div>

                                <!-- View Mode Toggle -->
                                <div class="flex gap-2">
                                    <button
                                        @click="viewMode = 'grid'"
                                        aria-label="Grid view"
                                        :class="`px-4 py-2.5 rounded-control transition-colors ${
                                            viewMode === 'grid'
                                                ? 'bg-primary text-white'
                                                : 'bg-neutral-bg text-neutral-fg hover:bg-primary-soft hover:text-primary'
                                        }`"
                                    >
                                        <Squares2X2Icon class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        aria-label="List view"
                                        :class="`px-4 py-2.5 rounded-control transition-colors ${
                                            viewMode === 'list'
                                                ? 'bg-primary text-white'
                                                : 'bg-neutral-bg text-neutral-fg hover:bg-primary-soft hover:text-primary'
                                        }`"
                                    >
                                        <ListBulletIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </Card>

                        <!-- Course Info -->
                        <Card v-if="currentMaterials.courseInfo">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-bold text-content mb-1">
                                        {{ currentMaterials.courseInfo.code }} - {{ currentMaterials.courseInfo.name }}
                                    </h2>
                                    <p v-if="currentMaterials.courseInfo.instructor || currentMaterials.courseInfo.semester" class="text-content-muted">
                                        {{ [currentMaterials.courseInfo.instructor, currentMaterials.courseInfo.semester].filter(Boolean).join(' • ') }}
                                    </p>
                                </div>
                                <div v-if="currentMaterials.courseInfo.credits" class="flex items-center gap-4 flex-shrink-0">
                                    <Badge variant="slate">{{ currentMaterials.courseInfo.credits }} Credits</Badge>
                                </div>
                            </div>
                        </Card>

                        <!-- Weekly Progress Overview -->
                        <Card v-if="currentMaterials.weeks?.length" title="Weekly Progress Overview" :icon="ChartBarIcon">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                                <div
                                    v-for="week in currentMaterials.weeks.slice(0, 8)"
                                    :key="week.weekNumber"
                                    class="text-center p-3 rounded-control border border-line bg-surface hover:shadow-card transition-all"
                                >
                                    <div class="text-sm font-medium text-content mb-2">
                                        Week {{ week.weekNumber }}
                                    </div>

                                    <!-- Progress Circle -->
                                    <div class="relative w-12 h-12 mx-auto mb-2">
                                        <svg class="w-12 h-12 transform -rotate-90">
                                            <circle
                                                cx="24" cy="24" r="20"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                class="text-line"
                                            />
                                            <circle
                                                cx="24" cy="24" r="20"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                stroke-linecap="round"
                                                :stroke-dasharray="`${2 * Math.PI * 20}`"
                                                :stroke-dashoffset="`${2 * Math.PI * 20 * (1 - weekCompletionPercent(week) / 100)}`"
                                                :class="week.status === 'completed' ? 'text-success-fg' : week.status === 'in-progress' ? 'text-primary' : 'text-content-faint'"
                                                class="transition-all duration-500"
                                            />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-bold text-content">
                                                {{ weekCompletionPercent(week) }}%
                                            </span>
                                        </div>
                                    </div>

                                    <Badge :variant="getWeekBadgeVariant(week.status)">{{ weekStatusLabel(week.status) }}</Badge>
                                </div>
                            </div>
                        </Card>

                        <!-- Materials by Week -->
                        <div class="space-y-6">
                            <Card
                                v-for="week in filteredWeeks"
                                :key="week.weekNumber"
                                padding="p-0"
                            >
                                <!-- Week Header -->
                                <div class="px-5 sm:px-6 py-4 border-b border-line">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-base font-bold text-content">
                                                Week {{ week.weekNumber }}
                                            </h3>
                                            <p v-if="week.addedDate" class="text-sm text-content-muted mt-0.5">
                                                Added {{ formatDate(week.addedDate) }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Badge :variant="getWeekBadgeVariant(week.status)">
                                                {{ weekStatusLabel(week.status) }}
                                            </Badge>
                                            <span class="text-sm text-content-muted">
                                                {{ week.materials.length }} materials
                                            </span>
                                            <button
                                                v-if="week.materials.some((m) => m.downloadUrl)"
                                                @click="downloadWeekMaterials(week)"
                                                aria-label="Download all week materials"
                                                class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                                title="Download all week materials"
                                            >
                                                <ArrowDownTrayIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Materials Grid/List -->
                                <div class="p-5 sm:p-6">
                                    <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-3'">
                                        <div
                                            v-for="material in week.materials.filter((m) => selectedMaterialType === 'all' || m.type === selectedMaterialType)"
                                            :key="material.id"
                                            class="rounded-control border border-line bg-surface p-4 transition-all duration-200 hover:shadow-card"
                                        >
                                            <div class="flex items-start gap-4">
                                                <!-- Material Icon -->
                                                <div class="ui-icon-tile h-12 w-12 flex-shrink-0 bg-primary-soft text-primary">
                                                    <component
                                                        :is="getMaterialIcon(material.type, material.format)"
                                                        class="w-6 h-6"
                                                    />
                                                </div>

                                                <!-- Material Info -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h4 class="font-semibold text-content truncate">
                                                            {{ material.title }}
                                                        </h4>
                                                        <button
                                                            @click.stop="toggleComplete(material)"
                                                            :aria-label="material.completed ? 'Mark as incomplete' : 'Mark as complete'"
                                                            :title="material.completed ? 'Completed — click to undo' : 'Mark as complete'"
                                                            class="flex-shrink-0 transition-colors"
                                                            :class="material.completed ? 'text-success-fg hover:text-success-fg/80' : 'text-content-faint hover:text-success-fg'"
                                                        >
                                                            <CheckCircleIconSolid v-if="material.completed" class="w-5 h-5" />
                                                            <CheckCircleIcon v-else class="w-5 h-5" />
                                                        </button>
                                                    </div>

                                                    <p v-if="material.description" class="text-sm text-content-muted mt-1 line-clamp-2">
                                                        {{ material.description }}
                                                    </p>

                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-3 text-xs text-content-muted">
                                                        <Badge :variant="getMaterialBadgeVariant(material.type)">{{ material.type }}</Badge>
                                                        <span v-if="material.fileSize">{{ formatFileSize(material.fileSize) }}</span>
                                                        <span v-if="material.uploadedAt">{{ formatDate(material.uploadedAt) }}</span>
                                                        <span v-if="material.downloadCount">{{ material.downloadCount }} downloads</span>
                                                        <span v-if="!material.hasFile" class="text-content-faint italic">No file attached</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-line">
                                                <button
                                                    @click.stop="viewMaterial(material)"
                                                    :disabled="!material.downloadUrl"
                                                    class="flex-1 ui-btn-primary text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <component :is="material.format === 'video' ? PlayIcon : EyeIcon" class="w-4 h-4" />
                                                    {{ material.format === 'video' ? 'Play Video' : 'Open' }}
                                                </button>

                                                <button
                                                    @click.stop="downloadMaterial(material)"
                                                    :disabled="!material.downloadUrl"
                                                    aria-label="Download"
                                                    class="px-3 py-2 bg-neutral-bg text-neutral-fg rounded-control hover:bg-primary-soft hover:text-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Download"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>

                                                <button
                                                    @click.stop="askAIAboutMaterial(material)"
                                                    aria-label="Ask AI about this material"
                                                    class="px-3 py-2 bg-primary-soft text-primary rounded-control hover:opacity-90 transition-opacity"
                                                    title="Ask AI about this material"
                                                >
                                                    <SparklesIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Card>

                            <!-- Empty State for Week -->
                            <Card v-if="filteredWeeks.length === 0">
                                <EmptyState
                                    :icon="InboxIcon"
                                    title="No materials found"
                                    description="Try adjusting your search criteria or filters to find the materials you're looking for."
                                >
                                    <button
                                        @click="searchQuery = ''; selectedMaterialType = 'all'; selectedWeek = 'all'"
                                        class="ui-btn-primary"
                                    >
                                        Clear Filters
                                    </button>
                                </EmptyState>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}
</style>
