<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    MapIcon,
    AcademicCapIcon,
    ChartBarIcon,
    BookOpenIcon,
    CheckCircleIcon,
    ClockIcon,
    PlayCircleIcon,
    LockClosedIcon,
    StarIcon,
    CalendarIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    EyeIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    ArrowRightIcon,
    FireIcon,
    BeakerIcon,
    CodeBracketIcon,
    CpuChipIcon,
    ServerIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    SparklesIcon,
    BoltIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    roadmap: Object,
    student: Object
});

// Component state
const selectedSemester = ref(props.student?.semester ?? 1); // Current semester
const viewMode = ref('timeline'); // timeline, grid, progress
const expandedModules = ref(new Set()); // Currently expanded modules
const selectedTrack = ref('core');

// Student context — mapped from server `student` + `roadmap` props.
const studentContext = computed(() => {
    const student = props.student || {};
    const roadmap = props.roadmap || {};
    return {
        name: student.name,
        studentId: student.student_id,
        department: student.department ?? 'Not assigned',
        currentSemester: student.semester,
        currentYear: student.semester ? `Semester ${student.semester}` : '',
        overallProgress: roadmap.overallProgress ?? 0,
        cgpa: student.cgpa ?? roadmap.cgpa,
        completedCredits: roadmap.completedCredits ?? 0,
        totalCredits: roadmap.totalCredits ?? 0
    };
});


// Roadmap data — mapped from server `roadmap.semesters`. The template expects
// each module to expose `difficulty` and a `code`-based title; map those in.
const roadmapData = computed(() => {
    const semesters = (props.roadmap?.semesters || []).map((semester) => {
        const modules = (semester.modules || []).map((module) => ({
            ...module,
            difficulty: module.difficulty ?? 'medium'
        }));
        const allCompleted = modules.length > 0 && modules.every((module) => module.status === 'completed');
        const anyActive = modules.some((module) => module.status === 'in-progress');
        const status = allCompleted ? 'completed' : anyActive ? 'in-progress' : 'upcoming';
        return {
            semester: semester.semester,
            title: semester.title,
            status,
            credits: semester.credits,
            gpa: null,
            modules
        };
    });
    return { semesters };
});



// Computed properties
const currentSemesterData = computed(() => {
    return roadmapData.value.semesters.find(s => s.semester === selectedSemester.value);
});

const overallProgress = computed(() => props.roadmap?.overallProgress ?? 0);

const currentSemesterProgress = computed(() => {
    const currentSem = currentSemesterData.value;
    if (!currentSem) return 0;

    const totalProgress = currentSem.modules.reduce((sum, mod) => sum + mod.progress, 0);
    return Math.round(totalProgress / currentSem.modules.length);
});

const upcomingDeadlines = computed(() => {
    const deadlines = [];
    roadmapData.value.semesters.forEach(semester => {
        semester.modules.forEach(module => {
            if (module.assignments) {
                module.assignments.forEach(assignment => {
                    if (assignment.status !== 'completed') {
                        deadlines.push({
                            ...assignment,
                            moduleName: module.title,
                            moduleId: module.id
                        });
                    }
                });
            }
        });
    });

    return deadlines.sort((a, b) => new Date(a.due) - new Date(b.due)).slice(0, 5);
});

// Utility functions
const getStatusColor = (status) => {
    const colors = {
        completed: 'text-success-fg bg-success-bg',
        'in-progress': 'text-primary bg-primary-soft',
        locked: 'text-neutral-fg bg-neutral-bg',
        upcoming: 'text-warning-fg bg-warning-bg'
    };
    return colors[status] || colors.upcoming;
};

// Maps a status string to a Badge variant.
const getStatusVariant = (status) => {
    const variants = {
        completed: 'success',
        'in-progress': 'brand',
        locked: 'slate',
        upcoming: 'info'
    };
    return variants[status] || 'info';
};

// Maps a status string to a status icon.
const getStatusIcon = (status) => {
    const icons = {
        completed: CheckCircleIcon,
        'in-progress': PlayCircleIcon,
        locked: LockClosedIcon,
        upcoming: ClockIcon
    };
    return icons[status] || ClockIcon;
};

const getDifficultyColor = (difficulty) => {
    const colors = {
        easy: 'text-success-fg bg-success-bg',
        medium: 'text-warning-fg bg-warning-bg',
        hard: 'text-danger-fg bg-danger-bg'
    };
    return colors[difficulty] || colors.medium;
};

// Maps a difficulty string to a Badge variant.
const getDifficultyVariant = (difficulty) => {
    const variants = {
        easy: 'success',
        medium: 'warning',
        hard: 'danger'
    };
    return variants[difficulty] || 'warning';
};

const getGradeColor = (grade) => {
    if (!grade) return 'text-content-muted';
    if (grade.includes('A')) return 'text-success-fg';
    if (grade.includes('B')) return 'text-primary';
    return 'text-warning-fg';
};

const getModuleIcon = (moduleTitle) => {
    const title = moduleTitle.toLowerCase();
    if (title.includes('machine learning') || title.includes('ai') || title.includes('artificial')) return BeakerIcon;
    if (title.includes('programming') || title.includes('java') || title.includes('python')) return CodeBracketIcon;
    if (title.includes('database') || title.includes('data')) return ServerIcon;
    if (title.includes('network') || title.includes('web')) return GlobeAltIcon;
    if (title.includes('security') || title.includes('cyber')) return ShieldCheckIcon;
    if (title.includes('computer') || title.includes('architecture')) return CpuChipIcon;
    return BookOpenIcon;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

const getDaysUntil = (dateString) => {
    const today = new Date();
    const dueDate = new Date(dateString);
    const diffTime = dueDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

// Actions
const toggleModuleExpansion = (moduleId) => {
    if (expandedModules.value.has(moduleId)) {
        expandedModules.value.delete(moduleId);
    } else {
        expandedModules.value.add(moduleId);
    }
};

const startModule = () => {
    router.visit('/student/materials');
};

const viewAssignment = () => {
    router.visit('/student/materials');
};

const downloadRoadmap = () => {
    router.visit('/student/materials');
};

const getAIRecommendations = () => {
    router.visit('/chat');
};
</script>

<template>
    <div>
        <Head title="Academic Roadmap" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Learning Roadmap"
                    subtitle="Your personalized journey to academic excellence"
                    :icon="MapIcon"
                    :eyebrow="`${studentContext.department} • ${studentContext.currentYear}`"
                >
                    <template #actions>
                        <Link
                            href="/dashboard"
                            class="ui-btn-secondary"
                        >
                            <ChevronRightIcon class="w-4 h-4 rotate-180" />
                            Dashboard
                        </Link>
                        <button
                            type="button"
                            @click="getAIRecommendations"
                            class="ui-btn-primary"
                        >
                            <SparklesIcon class="w-4 h-4" />
                            AI Recommendations
                        </button>
                    </template>
                </PageHeader>

                <!-- KPI Overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Overall Progress"
                        :value="`${overallProgress}%`"
                        :icon="ChartBarIcon"
                        variant="filled"
                        color="lilac"
                    />
                    <StatCard
                        label="Current CGPA"
                        :value="studentContext.cgpa || '-'"
                        :icon="StarIcon"
                        variant="filled"
                        color="yellow"
                    />
                    <StatCard
                        label="Credits Earned"
                        :value="studentContext.completedCredits"
                        :icon="AcademicCapIcon"
                        variant="filled"
                        color="mint"
                    />
                    <StatCard
                        label="Pending Tasks"
                        :value="upcomingDeadlines.length"
                        :icon="ClockIcon"
                        variant="filled"
                        color="rose"
                    />
                </div>

                <!-- Main split -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Semester Navigation -->
                        <Card title="Semesters" :icon="CalendarIcon">
                            <div class="space-y-2">
                                <button
                                    v-for="semester in roadmapData.semesters"
                                    :key="semester.semester"
                                    @click="selectedSemester = semester.semester"
                                    :class="[
                                        'w-full text-left p-3 rounded-control transition-all duration-200',
                                        selectedSemester === semester.semester
                                            ? 'bg-primary text-white'
                                            : 'bg-neutral-bg hover:bg-surface hover:shadow-card hover:-translate-y-0.5'
                                    ]"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span
                                            :class="[
                                                'font-semibold',
                                                selectedSemester === semester.semester ? 'text-white' : 'text-content'
                                            ]"
                                        >Sem {{ semester.semester }}</span>
                                        <Badge :variant="getStatusVariant(semester.status)" dot>
                                            {{ semester.status === 'in-progress' ? 'Current' : semester.status }}
                                        </Badge>
                                    </div>
                                    <div
                                        :class="[
                                            'text-xs mt-1',
                                            selectedSemester === semester.semester ? 'text-white/70' : 'text-content-muted'
                                        ]"
                                    >
                                        {{ semester.modules.length }} modules • {{ semester.credits }} credits
                                    </div>
                                    <div
                                        v-if="semester.gpa"
                                        :class="[
                                            'text-xs font-medium mt-1',
                                            selectedSemester === semester.semester ? 'text-white/80' : 'text-success-fg'
                                        ]"
                                    >
                                        GPA: {{ semester.gpa }}
                                    </div>
                                </button>
                            </div>

                            <!-- View Mode Toggle -->
                            <div class="mt-6">
                                <label class="ui-label">View Mode</label>
                                <div class="flex bg-neutral-bg rounded-control p-1 mt-1">
                                    <button
                                        @click="viewMode = 'timeline'"
                                        :class="[
                                            'flex-1 py-2 px-3 text-sm font-medium rounded-control transition-colors',
                                            viewMode === 'timeline'
                                                ? 'bg-primary text-white shadow-sm'
                                                : 'text-content-muted'
                                        ]"
                                    >
                                        Timeline
                                    </button>
                                    <button
                                        @click="viewMode = 'grid'"
                                        :class="[
                                            'flex-1 py-2 px-3 text-sm font-medium rounded-control transition-colors',
                                            viewMode === 'grid'
                                                ? 'bg-primary text-white shadow-sm'
                                                : 'text-content-muted'
                                        ]"
                                    >
                                        Grid
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="mt-6 space-y-2">
                                <button
                                    @click="downloadRoadmap"
                                    class="ui-btn-secondary w-full justify-center"
                                >
                                    <DocumentTextIcon class="w-4 h-4" />
                                    Download PDF
                                </button>
                            </div>
                        </Card>

                        <!-- Upcoming Deadlines -->
                        <Card title="Upcoming Deadlines" :icon="ClockIcon">
                            <div v-if="upcomingDeadlines.length" class="space-y-2">
                                <div
                                    v-for="deadline in upcomingDeadlines.slice(0, 3)"
                                    :key="deadline.title"
                                    :class="[
                                        'p-3 rounded-control',
                                        getDaysUntil(deadline.due) <= 3
                                            ? 'bg-danger-bg'
                                            : 'bg-warning-bg'
                                    ]"
                                >
                                    <p class="text-xs font-semibold text-content">{{ deadline.title }}</p>
                                    <p class="text-xs text-content-muted">{{ deadline.moduleName }}</p>
                                    <p class="text-xs font-medium mt-1">
                                        <span :class="getDaysUntil(deadline.due) <= 3 ? 'text-danger-fg' : 'text-warning-fg'">
                                            {{ getDaysUntil(deadline.due) }} days left
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                title="No deadlines"
                                description="You're all caught up for now."
                                :icon="CheckCircleIcon"
                            />
                        </Card>
                    </div>

                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Semester Header -->
                        <Card v-if="currentSemesterData">
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <h2 class="text-xl sm:text-2xl font-bold text-content">
                                        {{ currentSemesterData.title }}
                                    </h2>
                                    <p class="text-content-muted mt-1 text-sm">
                                        {{ currentSemesterData.modules.length }} modules • {{ currentSemesterData.credits }} credits
                                    </p>
                                </div>
                                <div class="text-right">
                                    <Badge :variant="getStatusVariant(currentSemesterData.status)">
                                        <component :is="getStatusIcon(currentSemesterData.status)" class="w-3.5 h-3.5" />
                                        {{ currentSemesterData.status }}
                                    </Badge>
                                    <div v-if="currentSemesterData.gpa" class="text-sm font-semibold text-success-fg mt-1">
                                        GPA: {{ currentSemesterData.gpa }}
                                    </div>
                                </div>
                            </div>

                            <!-- Semester Progress Bar -->
                            <div class="mt-5">
                                <div class="w-full bg-neutral-bg rounded-full h-2.5">
                                    <div
                                        class="bg-primary h-2.5 rounded-full transition-all duration-500"
                                        :style="{ width: currentSemesterProgress + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-xs text-content-muted mt-2">
                                    <span>Progress: {{ currentSemesterProgress }}%</span>
                                    <span>{{ currentSemesterData.modules.filter(m => m.status === 'completed').length }}/{{ currentSemesterData.modules.length }} modules completed</span>
                                </div>
                            </div>
                        </Card>

                        <!-- Modules Display -->
                        <div
                            v-if="currentSemesterData"
                            :class="viewMode === 'grid' ? 'grid grid-cols-1 xl:grid-cols-2 gap-5' : 'space-y-5'"
                        >
                            <Card
                                v-for="module in currentSemesterData.modules"
                                :key="module.id"
                                padding="p-0"
                                hover
                                class="overflow-hidden"
                            >
                                <!-- Module Header -->
                                <div class="p-5 sm:p-6 border-b border-line">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-4 min-w-0">
                                            <div
                                                :class="[
                                                    'p-3 rounded-control shrink-0',
                                                    module.status === 'completed'
                                                        ? 'bg-success-bg text-success-fg'
                                                        : module.status === 'in-progress'
                                                            ? 'bg-primary-soft text-primary'
                                                            : 'bg-neutral-bg text-neutral-fg'
                                                ]"
                                            >
                                                <component :is="getModuleIcon(module.title)" class="w-6 h-6" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-base sm:text-lg font-bold text-content mb-2">
                                                    {{ module.title }}
                                                </h3>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <Badge :variant="getStatusVariant(module.status)">
                                                        <component :is="getStatusIcon(module.status)" class="w-3.5 h-3.5" />
                                                        {{ module.status }}
                                                    </Badge>
                                                    <Badge :variant="getDifficultyVariant(module.difficulty)">
                                                        {{ module.difficulty }}
                                                    </Badge>
                                                    <span class="text-sm text-content-muted">
                                                        {{ module.credits }} credits
                                                    </span>
                                                    <span v-if="module.grade" :class="`text-sm font-bold ${getGradeColor(module.grade)}`">
                                                        Grade: {{ module.grade }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Expand/Collapse Button -->
                                        <button
                                            @click="toggleModuleExpansion(module.id)"
                                            class="p-2 text-content-faint hover:text-primary rounded-control hover:bg-primary-soft transition-colors shrink-0"
                                            :aria-label="expandedModules.has(module.id) ? 'Collapse module' : 'Expand module'"
                                        >
                                            <ChevronDownIcon
                                                :class="`w-5 h-5 transition-transform ${expandedModules.has(module.id) ? 'rotate-180' : ''}`"
                                            />
                                        </button>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mt-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-content-muted">Progress</span>
                                            <span class="text-sm font-bold text-content">{{ module.progress }}%</span>
                                        </div>
                                        <div class="w-full bg-neutral-bg rounded-full h-2">
                                            <div
                                                class="h-2 rounded-full bg-primary transition-all duration-500"
                                                :style="{ width: module.progress + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expanded Content -->
                                <div v-if="expandedModules.has(module.id)" class="p-5 sm:p-6 bg-neutral-bg">
                                    <!-- Current Topic (for in-progress modules) -->
                                    <div v-if="module.currentTopic" class="mb-4">
                                        <h4 class="font-semibold text-content mb-2">Currently Learning</h4>
                                        <div class="p-3 bg-primary-soft rounded-control">
                                            <p class="text-sm text-primary">{{ module.currentTopic }}</p>
                                        </div>
                                    </div>

                                    <!-- Upcoming Topics -->
                                    <div v-if="module.upcomingTopics && module.upcomingTopics.length" class="mb-4">
                                        <h4 class="font-semibold text-content mb-2">Upcoming Topics</h4>
                                        <div class="space-y-1">
                                            <div
                                                v-for="topic in module.upcomingTopics"
                                                :key="topic"
                                                class="flex items-center gap-2 text-sm text-content-muted"
                                            >
                                                <ArrowRightIcon class="w-4 h-4 text-content-faint shrink-0" />
                                                {{ topic }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assignments -->
                                    <div v-if="module.assignments && module.assignments.length" class="mb-4">
                                        <h4 class="font-semibold text-content mb-2">Assignments</h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="assignment in module.assignments"
                                                :key="assignment.title"
                                                class="flex items-center justify-between gap-3 p-3 bg-surface rounded-control border border-line"
                                            >
                                                <div class="min-w-0">
                                                    <p class="font-medium text-content truncate">{{ assignment.title }}</p>
                                                    <p class="text-sm text-content-muted">Due: {{ formatDate(assignment.due) }}</p>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <Badge :variant="getStatusVariant(assignment.status)">
                                                        {{ assignment.status }}
                                                    </Badge>
                                                    <button
                                                        @click="viewAssignment(module.id, assignment.title)"
                                                        class="p-1.5 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                                        aria-label="View assignment"
                                                    >
                                                        <EyeIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Prerequisites -->
                                    <div v-if="module.prerequisites && module.prerequisites.length" class="mb-4">
                                        <h4 class="font-semibold text-content mb-2">Prerequisites</h4>
                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                v-for="prereq in module.prerequisites"
                                                :key="prereq"
                                                variant="warning"
                                            >
                                                Module {{ prereq }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-line">
                                        <button
                                            v-if="module.status === 'in-progress'"
                                            @click="startModule(module.id)"
                                            class="ui-btn-primary"
                                        >
                                            <PlayCircleIcon class="w-4 h-4" />
                                            Continue Learning
                                        </button>
                                        <button
                                            v-else-if="module.status === 'locked'"
                                            disabled
                                            class="ui-btn-secondary opacity-60 cursor-not-allowed"
                                        >
                                            <LockClosedIcon class="w-4 h-4" />
                                            Prerequisites Required
                                        </button>
                                        <button class="ui-btn-secondary">
                                            <BookOpenIcon class="w-4 h-4" />
                                            View Materials
                                        </button>
                                        <Link href="/chat" class="ui-btn-ghost">
                                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                            Ask AI
                                        </Link>
                                    </div>
                                </div>
                            </Card>
                        </div>

                        <EmptyState
                            v-else
                            title="No roadmap data"
                            description="Select a semester to view your learning path."
                            :icon="MapIcon"
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
