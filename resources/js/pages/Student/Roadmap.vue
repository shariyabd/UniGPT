<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    ChartBarIcon,
    BookOpenIcon,
    CheckCircleIcon,
    ClockIcon,
    PlayIcon,
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
        completed: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        'in-progress': 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
        locked: 'text-gray-500 bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400',
        upcoming: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
    };
    return colors[status] || colors.upcoming;
};

const getDifficultyColor = (difficulty) => {
    const colors = {
        easy: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        medium: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
        hard: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[difficulty] || colors.medium;
};

const getGradeColor = (grade) => {
    if (!grade) return 'text-gray-500';
    if (grade.includes('A')) return 'text-green-600 dark:text-green-400';
    if (grade.includes('B')) return 'text-blue-600 dark:text-blue-400';
    return 'text-yellow-600 dark:text-yellow-400';
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
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-cyan-500 via-blue-600 to-indigo-600 dark:from-cyan-800 dark:via-blue-800 dark:to-indigo-800">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                                    <ChartBarIcon class="w-10 h-10" />
                                    Academic Roadmap
                                </h1>
                                <p class="text-xl text-white/90 mb-2">
                                    Your personalized journey to academic excellence
                                </p>
                                <p class="text-white/80">
                                    {{ studentContext.department }} • {{ studentContext.currentYear }} • Semester {{ studentContext.currentSemester }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Progress Badge -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-4 text-white text-center">
                                    <div class="text-3xl font-bold">{{ overallProgress }}%</div>
                                    <div class="text-sm text-white/80">Overall Progress</div>
                                </div>

                                <Link
                                    href="/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>

                        <!-- Progress Overview -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">{{ studentContext.cgpa }}</div>
                                <div class="text-xs text-white/80">Current CGPA</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">{{ studentContext.completedCredits }}</div>
                                <div class="text-xs text-white/80">Credits Earned</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">{{ currentSemesterProgress }}%</div>
                                <div class="text-xs text-white/80">Current Semester</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">{{ upcomingDeadlines.length }}</div>
                                <div class="text-xs text-white/80">Pending Tasks</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <!-- Left Sidebar - Quick Navigation -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-8">
                                <!-- Semester Navigation -->
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <CalendarIcon class="w-5 h-5 text-blue-600" />
                                    Semesters
                                </h3>

                                <div class="space-y-2 mb-6">
                                    <button
                                        v-for="semester in roadmapData.semesters"
                                        :key="semester.semester"
                                        @click="selectedSemester = semester.semester"
                                        :class="`w-full text-left p-3 rounded-xl transition-all ${
                                            selectedSemester === semester.semester
                                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-2 border-blue-300 dark:border-blue-600'
                                                : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-2 border-transparent'
                                        }`"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium">Sem {{ semester.semester }}</span>
                                            <span :class="`px-2 py-1 text-xs rounded-full ${getStatusColor(semester.status)}`">
                                                {{ semester.status === 'in-progress' ? 'Current' : semester.status }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ semester.modules.length }} modules • {{ semester.credits }} credits
                                        </div>
                                        <div v-if="semester.gpa" class="text-xs font-medium text-green-600 dark:text-green-400 mt-1">
                                            GPA: {{ semester.gpa }}
                                        </div>
                                    </button>
                                </div>

                                <!-- View Mode Toggle -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">View Mode</label>
                                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                        <button
                                            @click="viewMode = 'timeline'"
                                            :class="`flex-1 py-2 px-3 text-sm rounded-lg transition-colors ${viewMode === 'timeline' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''}`"
                                        >
                                            Timeline
                                        </button>
                                        <button
                                            @click="viewMode = 'grid'"
                                            :class="`flex-1 py-2 px-3 text-sm rounded-lg transition-colors ${viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''}`"
                                        >
                                            Grid
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="space-y-2">
                                    <button
                                        @click="downloadRoadmap"
                                        class="w-full flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors"
                                    >
                                        <DocumentTextIcon class="w-4 h-4" />
                                        Download PDF
                                    </button>
                                    <button
                                        @click="getAIRecommendations"
                                        class="w-full flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
                                    >
                                        <SparklesIcon class="w-4 h-4" />
                                        AI Recommendations
                                    </button>
                                </div>

                                <!-- Upcoming Deadlines -->
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <ClockIcon class="w-4 h-4 text-red-600" />
                                        Upcoming Deadlines
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="deadline in upcomingDeadlines.slice(0, 3)"
                                            :key="deadline.title"
                                            :class="`p-2 rounded-lg border-l-4 ${getDaysUntil(deadline.due) <= 3 ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'}`"
                                        >
                                            <p class="text-xs font-medium text-gray-900 dark:text-white">{{ deadline.title }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ deadline.moduleName }}</p>
                                            <p class="text-xs font-medium">
                                                <span :class="getDaysUntil(deadline.due) <= 3 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'">
                                                    {{ getDaysUntil(deadline.due) }} days left
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="lg:col-span-3">
                            <!-- Semester Header -->
                            <div v-if="currentSemesterData" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ currentSemesterData.title }}
                                        </h2>
                                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                                            {{ currentSemesterData.modules.length }} modules • {{ currentSemesterData.credits }} credits
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span :class="`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(currentSemesterData.status)}`">
                                            {{ currentSemesterData.status }}
                                        </span>
                                        <div v-if="currentSemesterData.gpa" class="text-sm font-semibold text-green-600 dark:text-green-400 mt-1">
                                            GPA: {{ currentSemesterData.gpa }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Semester Progress Bar -->
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2">
                                    <div
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 h-3 rounded-full transition-all duration-500"
                                        :style="{ width: currentSemesterProgress + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Progress: {{ currentSemesterProgress }}%</span>
                                    <span>{{ currentSemesterData.modules.filter(m => m.status === 'completed').length }}/{{ currentSemesterData.modules.length }} modules completed</span>
                                </div>
                            </div>

                            <!-- Modules Display -->
                            <div v-if="currentSemesterData" :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-6'">
                                <div
                                    v-for="module in currentSemesterData.modules"
                                    :key="module.id"
                                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300"
                                >
                                    <!-- Module Header -->
                                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-4">
                                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                                                    <component :is="getModuleIcon(module.title)" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                                        {{ module.title }}
                                                    </h3>
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(module.status)}`">
                                                            {{ module.status }}
                                                        </span>
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getDifficultyColor(module.difficulty)}`">
                                                            {{ module.difficulty }}
                                                        </span>
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">
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
                                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                <ChevronDownIcon
                                                    :class="`w-5 h-5 transition-transform ${expandedModules.has(module.id) ? 'rotate-180' : ''}`"
                                                />
                                            </button>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mt-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ module.progress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    :class="`h-2 rounded-full transition-all duration-500 ${
                                                        module.status === 'completed' ? 'bg-green-500' :
                                                        module.status === 'in-progress' ? 'bg-blue-500' :
                                                        'bg-gray-400'
                                                    }`"
                                                    :style="{ width: module.progress + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Expanded Content -->
                                    <div v-if="expandedModules.has(module.id)" class="p-6 bg-gray-50 dark:bg-gray-900/50">
                                        <!-- Current Topic (for in-progress modules) -->
                                        <div v-if="module.currentTopic" class="mb-4">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Currently Learning</h4>
                                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                                <p class="text-sm text-blue-800 dark:text-blue-200">{{ module.currentTopic }}</p>
                                            </div>
                                        </div>

                                        <!-- Upcoming Topics -->
                                        <div v-if="module.upcomingTopics && module.upcomingTopics.length" class="mb-4">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Upcoming Topics</h4>
                                            <div class="space-y-1">
                                                <div
                                                    v-for="topic in module.upcomingTopics"
                                                    :key="topic"
                                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                                                >
                                                    <ArrowRightIcon class="w-4 h-4" />
                                                    {{ topic }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Assignments -->
                                        <div v-if="module.assignments && module.assignments.length" class="mb-4">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Assignments</h4>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="assignment in module.assignments"
                                                    :key="assignment.title"
                                                    class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                                                >
                                                    <div>
                                                        <p class="font-medium text-gray-900 dark:text-white">{{ assignment.title }}</p>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">Due: {{ formatDate(assignment.due) }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(assignment.status)}`">
                                                            {{ assignment.status }}
                                                        </span>
                                                        <button
                                                            @click="viewAssignment(module.id, assignment.title)"
                                                            class="p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                        >
                                                            <EyeIcon class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Prerequisites -->
                                        <div v-if="module.prerequisites && module.prerequisites.length" class="mb-4">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Prerequisites</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="prereq in module.prerequisites"
                                                    :key="prereq"
                                                    class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 text-xs rounded-full"
                                                >
                                                    Module {{ prereq }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <button
                                                v-if="module.status === 'in-progress'"
                                                @click="startModule(module.id)"
                                                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                <PlayIcon class="w-4 h-4" />
                                                Continue Learning
                                            </button>
                                            <button
                                                v-else-if="module.status === 'locked'"
                                                disabled
                                                class="flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed"
                                            >
                                                <LockClosedIcon class="w-4 h-4" />
                                                Prerequisites Required
                                            </button>
                                            <button
                                                class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                            >
                                                <BookOpenIcon class="w-4 h-4" />
                                                View Materials
                                            </button>
                                            <Link
                                                href="/chat"
                                                class="flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                            >
                                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                                Ask AI
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover effects */
.hover\:-translate-y-1:hover {
    transform: translateY(-0.25rem);
}
</style>