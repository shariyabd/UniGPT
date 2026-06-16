<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
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
    CalendarIcon,
    ClockIcon,
    EyeIcon,
    PlayIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    DocumentArrowDownIcon,
    PresentationChartLineIcon,
    BeakerIcon,
    StarIcon,
    UserIcon,
    BoltIcon,
    ChartBarIcon,
    Squares2X2Icon,
    ListBulletIcon,
    ChatBubbleLeftRightIcon,
    SparklesIcon,
    FolderIcon,
    LockClosedIcon,
    InboxIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';

// Server props
const props = defineProps({
    courses: {
        type: Array,
        default: () => []
    },
    enrolledCourses: {
        type: Array,
        default: () => []
    }
});

const pageData = usePage();

// Component state
const selectedSemester = ref(5);
const searchQuery = ref('');
const selectedMaterialType = ref('all');
const selectedWeek = ref('all');
const viewMode = ref('grid'); // grid or list
const showFilters = ref(false);

// Tracks which materials have been opened/downloaded this session
const viewedMaterialIds = ref(new Set());

// Student context (derived from authenticated user)
const studentContext = computed(() => {
    const authUser = pageData.props?.auth?.user || {};
    return {
        name: authUser.name || 'Student',
        department: authUser.department?.name || authUser.department || '',
        semester: authUser.semester ? `${authUser.semester} Semester` : '',
        year: ''
    };
});

// Enrolled courses grouped into the semester structure the template expects.
// The sidebar iterates enrolledCourses[0]?.courses, so all enrolled courses
// are placed under a single "Current Semester" group.
const enrolledCourses = computed(() => {
    const mappedCourses = (props.enrolledCourses || []).map(course => {
        const courseEntry = (props.courses || []).find(c => c.id === course.id);
        const courseMaterialList = courseEntry?.materials || [];
        const completedMaterials = courseMaterialList.filter(m => viewedMaterialIds.value.has(m.id)).length;

        return {
            id: course.id,
            code: course.code,
            name: course.name,
            instructor: course.instructor,
            totalMaterials: course.totalMaterials || courseMaterialList.length,
            completedMaterials,
            lastAccessed: null,
            credits: course.credits,
            semester: course.semester,
            progress: course.progress,
            grade: course.grade,
            status: course.status
        };
    });

    if (mappedCourses.length === 0) {
        return [];
    }

    return [
        {
            semester: 'current',
            title: 'Current Semester',
            courses: mappedCourses
        }
    ];
});

// Materials keyed by course id, mapped into the {courseInfo, weeks} shape the
// template iterates. Materials grouped into weeks based on each material's week.
const courseMaterials = computed(() => {
    const result = {};

    (props.courses || []).forEach(course => {
        const enrolled = (props.enrolledCourses || []).find(c => c.id === course.id);

        const weeksMap = {};
        (course.materials || []).forEach(material => {
            const weekNumber = material.week || 1;
            if (!weeksMap[weekNumber]) {
                weeksMap[weekNumber] = {
                    weekNumber,
                    title: `Week ${weekNumber}`,
                    startDate: null,
                    status: 'completed',
                    materials: []
                };
            }
            weeksMap[weekNumber].materials.push({
                id: material.id,
                title: material.title,
                description: material.description,
                type: material.type,
                format: material.type,
                size: null,
                pages: null,
                duration: null,
                uploadDate: null,
                downloadCount: material.downloads,
                viewed: viewedMaterialIds.value.has(material.id),
                locked: false,
                url: material.downloadUrl,
                downloadUrl: material.downloadUrl,
                documentId: material.documentId
            });
        });

        result[course.id] = {
            courseInfo: {
                code: course.code,
                name: course.name,
                instructor: enrolled?.instructor || '',
                semester: enrolled?.semester || '',
                credits: enrolled?.credits || 0,
                description: ''
            },
            weeks: Object.values(weeksMap).sort((a, b) => a.weekNumber - b.weekNumber)
        };
    });

    return result;
});

const selectedCourseId = ref(null);

// Material type options (counts derived from the selected course's materials)
const materialTypes = computed(() => {
    const allMaterials = (currentMaterials.value.weeks || []).flatMap(week => week.materials || []);
    const countByType = (type) => allMaterials.filter(material => material.type === type).length;

    return [
        { value: 'all', label: 'All Materials', icon: DocumentTextIcon, count: allMaterials.length },
        { value: 'lecture', label: 'Lectures', icon: PresentationChartLineIcon, count: countByType('lecture') },
        { value: 'assignment', label: 'Assignments', icon: AcademicCapIcon, count: countByType('assignment') },
        { value: 'reading', label: 'Readings', icon: BookOpenIcon, count: countByType('reading') },
        { value: 'lab', label: 'Lab Work', icon: BeakerIcon, count: countByType('lab') }
    ];
});

// Computed properties
const currentCourse = computed(() => {
    return enrolledCourses.value
        .flatMap(semester => semester.courses)
        .find(course => course.id === selectedCourseId.value);
});

const currentMaterials = computed(() => {
    return courseMaterials.value[selectedCourseId.value] || { weeks: [] };
});

const filteredWeeks = computed(() => {
    if (!currentMaterials.value.weeks) return [];

    return currentMaterials.value.weeks.filter(week => {
        const matchesWeek = selectedWeek.value === 'all' || week.weekNumber === parseInt(selectedWeek.value);
        const matchesSearch = !searchQuery.value ||
            week.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            week.materials.some(material =>
                material.title.toLowerCase().includes(searchQuery.value.toLowerCase())
            );

        if (selectedMaterialType.value !== 'all') {
            const hasMatchingMaterials = week.materials.some(material =>
                material.type === selectedMaterialType.value
            );
            return matchesWeek && matchesSearch && hasMatchingMaterials;
        }

        return matchesWeek && matchesSearch;
    });
});

const weekOptions = computed(() => {
    if (!currentMaterials.value.weeks) return [{ value: 'all', label: 'All Weeks' }];

    const weeks = currentMaterials.value.weeks.map(week => ({
        value: week.weekNumber.toString(),
        label: `Week ${week.weekNumber}`
    }));

    return [{ value: 'all', label: 'All Weeks' }, ...weeks];
});

const overallProgress = computed(() => {
    if (!currentCourse.value) return 0;
    return Math.round((currentCourse.value.completedMaterials / currentCourse.value.totalMaterials) * 100);
});

const upcomingAssignments = computed(() => {
    const assignments = [];
    currentMaterials.value.weeks?.forEach(week => {
        week.materials.forEach(material => {
            if (material.type === 'assignment' && !material.submitted && material.dueDate) {
                assignments.push({
                    ...material,
                    weekNumber: week.weekNumber
                });
            }
        });
    });
    return assignments.sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate)).slice(0, 3);
});

// Utility functions
const getMaterialIcon = (type, format) => {
    if (format === 'video') return VideoCameraIcon;
    if (format === 'pdf' && type === 'assignment') return AcademicCapIcon;
    if (format === 'pdf' && type === 'reading') return BookOpenIcon;
    if (format === 'zip' || type === 'lab') return BeakerIcon;
    if (type === 'lecture') return PresentationChartLineIcon;
    return DocumentTextIcon;
};

// Maps a material type to a Badge variant for the modern UI.
const getMaterialBadgeVariant = (type) => {
    const variants = {
        lecture: 'info',
        assignment: 'success',
        reading: 'violet',
        lab: 'warning',
        exam: 'danger'
    };
    return variants[type] || 'slate';
};

// Maps a week status to a Badge variant for the modern UI.
const getWeekBadgeVariant = (status) => {
    switch (status) {
        case 'completed': return 'success';
        case 'in-progress': return 'info';
        case 'upcoming': return 'warning';
        case 'locked': return 'danger';
        default: return 'slate';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const formatFileSize = (size) => {
    return size;
};

const isOverdue = (dueDate) => {
    return dueDate && new Date(dueDate) < new Date();
};

const getDaysUntil = (dateString) => {
    const today = new Date();
    const dueDate = new Date(dateString);
    const diffTime = dueDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

// Marks a material as viewed (tracked client-side by id)
const markViewed = (material) => {
    if (material?.id != null && !viewedMaterialIds.value.has(material.id)) {
        viewedMaterialIds.value.add(material.id);
        viewedMaterialIds.value = new Set(viewedMaterialIds.value);
    }
};

// Actions
const downloadMaterial = (material) => {
    markViewed(material);
    if (material.downloadUrl) {
        window.open(material.downloadUrl, '_blank');
    }
};

const viewMaterial = (material) => {
    markViewed(material);
    if (material.downloadUrl) {
        window.open(material.downloadUrl, '_blank');
    }
};

const askAIAboutMaterial = (material) => {
    const query = `Explain the concepts in ${material.title}`;
    window.open(`/chat?q=${encodeURIComponent(query)}&material=${material.id}`, '_blank');
};

const submitAssignment = (material) => {
    // Assignment submission flow not yet wired to a backend endpoint.
    // Opens the material so the student can review it before submitting.
    if (material?.downloadUrl) {
        window.open(material.downloadUrl, '_blank');
    }
};

const selectCourse = (courseId) => {
    selectedCourseId.value = courseId;
    selectedWeek.value = 'all';
    selectedMaterialType.value = 'all';
    searchQuery.value = '';
};

const downloadWeekMaterials = (week) => {
    (week.materials || []).forEach(material => {
        if (material.downloadUrl) {
            window.open(material.downloadUrl, '_blank');
        }
        markViewed(material);
    });
};

onMounted(() => {
    // Select the first available course on load
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
                    subtitle="Access all your course materials organized by semester and week"
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
                                <div
                                    v-for="course in enrolledCourses[0]?.courses || []"
                                    :key="course.id"
                                    @click="selectCourse(course.id)"
                                    class="cursor-pointer"
                                >
                                    <button
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
                                                    :style="{ width: (course.completedMaterials / course.totalMaterials * 100) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-xs font-medium text-content-muted">
                                                {{ Math.round((course.completedMaterials / course.totalMaterials) * 100) }}%
                                            </span>
                                        </div>
                                    </button>
                                </div>
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
                                <button class="w-full flex items-center gap-3 p-3 rounded-control text-sm font-medium bg-success-bg text-success-fg hover:opacity-90 transition-opacity">
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
                                    <p class="text-content-muted mb-2">
                                        {{ currentMaterials.courseInfo.instructor }} • {{ currentMaterials.courseInfo.semester }}
                                    </p>
                                    <p class="text-sm text-content-muted">
                                        {{ currentMaterials.courseInfo.description }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <Badge variant="slate">{{ currentMaterials.courseInfo.credits }} Credits</Badge>
                                </div>
                            </div>
                        </Card>

                        <!-- Weekly Progress Overview -->
                        <Card title="Weekly Progress Overview" :icon="ChartBarIcon">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                                <div
                                    v-for="week in currentMaterials.weeks?.slice(0, 8)"
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
                                                :stroke-dashoffset="`${2 * Math.PI * 20 * (1 - (week.materials.filter(m => m.viewed).length / week.materials.length))}`"
                                                :class="week.status === 'completed' ? 'text-success-fg' : week.status === 'in-progress' ? 'text-primary' : 'text-content-faint'"
                                                class="transition-all duration-500"
                                            />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-bold text-content">
                                                {{ Math.round((week.materials.filter(m => m.viewed).length / week.materials.length) * 100) }}%
                                            </span>
                                        </div>
                                    </div>

                                    <Badge :variant="getWeekBadgeVariant(week.status)">{{ week.status }}</Badge>
                                </div>
                            </div>
                        </Card>

                        <!-- Upcoming Assignments -->
                        <Card v-if="upcomingAssignments.length > 0" title="Upcoming Assignments" :icon="ClockIcon">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div
                                    v-for="assignment in upcomingAssignments"
                                    :key="assignment.id"
                                    class="rounded-control border border-line bg-surface p-4"
                                >
                                    <h4 class="font-semibold text-content text-sm mb-1">
                                        {{ assignment.title }}
                                    </h4>
                                    <p class="text-xs text-content-muted mb-2">
                                        Week {{ assignment.weekNumber }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span :class="`text-xs font-medium ${
                                            getDaysUntil(assignment.dueDate) <= 3
                                                ? 'text-danger-fg'
                                                : 'text-warning-fg'
                                        }`">
                                            Due in {{ getDaysUntil(assignment.dueDate) }} days
                                        </span>
                                        <button
                                            @click="viewMaterial(assignment)"
                                            class="text-xs ui-btn-primary px-2.5 py-1"
                                        >
                                            View
                                        </button>
                                    </div>
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
                                                Week {{ week.weekNumber }}: {{ week.title }}
                                            </h3>
                                            <p class="text-sm text-content-muted mt-0.5">
                                                Started {{ formatDate(week.startDate) }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Badge :variant="getWeekBadgeVariant(week.status)">
                                                {{ week.status.charAt(0).toUpperCase() + week.status.slice(1) }}
                                            </Badge>
                                            <span class="text-sm text-content-muted">
                                                {{ week.materials.length }} materials
                                            </span>
                                            <button
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
                                            v-for="material in week.materials.filter(m => selectedMaterialType === 'all' || m.type === selectedMaterialType)"
                                            :key="material.id"
                                            :class="`rounded-control border border-line bg-surface p-4 transition-all duration-200 ${
                                                material.locked
                                                    ? 'opacity-50 cursor-not-allowed'
                                                    : 'hover:shadow-card hover:-translate-y-0.5 cursor-pointer'
                                            }`"
                                            @click="!material.locked && viewMaterial(material)"
                                        >
                                            <div class="flex items-start gap-4">
                                                <!-- Material Icon -->
                                                <div :class="`ui-icon-tile h-12 w-12 flex-shrink-0 ${
                                                    material.locked
                                                        ? 'bg-neutral-bg text-content-faint'
                                                        : 'bg-primary-soft text-primary'
                                                }`">
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
                                                        <div class="flex items-center gap-2 flex-shrink-0">
                                                            <CheckCircleIconSolid
                                                                v-if="material.viewed && !material.locked"
                                                                class="w-5 h-5 text-success-fg"
                                                            />
                                                            <LockClosedIcon v-if="material.locked" class="w-4 h-4 text-content-faint" />
                                                            <Badge
                                                                v-if="material.grade"
                                                                :variant="material.grade.includes('A') ? 'success' : material.grade.includes('B') ? 'info' : 'warning'"
                                                            >
                                                                {{ material.grade }}
                                                            </Badge>
                                                        </div>
                                                    </div>

                                                    <p v-if="material.description" class="text-sm text-content-muted mt-1 line-clamp-2">
                                                        {{ material.description }}
                                                    </p>

                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-3 text-xs text-content-muted">
                                                        <Badge :variant="getMaterialBadgeVariant(material.type)">{{ material.type }}</Badge>
                                                        <span>{{ formatFileSize(material.size) }}</span>
                                                        <span v-if="material.duration">{{ material.duration }}</span>
                                                        <span v-if="material.pages">{{ material.pages }} pages</span>
                                                        <span v-if="material.downloadCount">{{ material.downloadCount }} downloads</span>
                                                    </div>

                                                    <!-- Assignment specific info -->
                                                    <div v-if="material.type === 'assignment'" class="mt-3">
                                                        <div class="flex items-center gap-4 text-xs">
                                                            <span v-if="material.dueDate" :class="`font-medium ${
                                                                isOverdue(material.dueDate)
                                                                    ? 'text-danger-fg'
                                                                    : 'text-content-muted'
                                                            }`">
                                                                Due: {{ formatDate(material.dueDate) }}
                                                            </span>
                                                            <span v-if="material.submitted" class="text-success-fg font-medium inline-flex items-center gap-1">
                                                                <CheckCircleIcon class="w-3.5 h-3.5" /> Submitted
                                                            </span>
                                                            <span v-else-if="material.dueDate && !material.locked" class="text-warning-fg font-medium">
                                                                Not Submitted
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div v-if="!material.locked" class="flex items-center gap-2 mt-4 pt-4 border-t border-line">
                                                <!-- Primary Action Button -->
                                                <button
                                                    @click.stop="viewMaterial(material)"
                                                    class="flex-1 ui-btn-primary text-sm"
                                                >
                                                    <component :is="material.format === 'video' ? PlayIcon : EyeIcon" class="w-4 h-4" />
                                                    {{ material.format === 'video' ? 'Play Video' : 'Open' }}
                                                </button>

                                                <!-- Download Button -->
                                                <button
                                                    @click.stop="downloadMaterial(material)"
                                                    aria-label="Download"
                                                    class="px-3 py-2 bg-neutral-bg text-neutral-fg rounded-control hover:bg-primary-soft hover:text-primary transition-colors"
                                                    title="Download"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>

                                                <!-- AI Assistant Button -->
                                                <button
                                                    @click.stop="askAIAboutMaterial(material)"
                                                    aria-label="Ask AI about this material"
                                                    class="px-3 py-2 bg-primary-soft text-primary rounded-control hover:opacity-90 transition-opacity"
                                                    title="Ask AI about this material"
                                                >
                                                    <SparklesIcon class="w-4 h-4" />
                                                </button>

                                                <!-- Assignment Submission (if assignment) -->
                                                <button
                                                    v-if="material.type === 'assignment' && !material.submitted"
                                                    @click.stop="submitAssignment(material)"
                                                    class="px-3 py-2 bg-success-bg text-success-fg rounded-control hover:opacity-90 transition-opacity text-sm font-medium"
                                                >
                                                    Submit
                                                </button>
                                            </div>

                                            <!-- Locked Material Message -->
                                            <div v-if="material.locked" class="mt-4 p-3 bg-neutral-bg rounded-control">
                                                <div class="flex items-center gap-2 text-sm text-content-muted">
                                                    <LockClosedIcon class="w-4 h-4 flex-shrink-0" />
                                                    <span>This material will be available after completing previous weeks</span>
                                                </div>
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
/* Line clamp utilities */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Progress circle animation */
circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}
</style>
