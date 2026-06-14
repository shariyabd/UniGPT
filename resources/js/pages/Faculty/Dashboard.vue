<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import {
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChartBarIcon,
    CalendarIcon,
    ClockIcon,
    EyeIcon,
    PlusIcon,
    ArrowRightIcon,
    BellIcon,
    UserIcon,
    Cog6ToothIcon,
    AcademicCapIcon,
    BeakerIcon,
    PresentationChartBarIcon,
    UsersIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ChatBubbleLeftIcon,
    FireIcon,
    StarIcon,
    TrophyIcon,
    ChartPieIcon,
    CursorArrowRaysIcon,
    PlayIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline';

// Server-provided props
const props = defineProps({
    faculty: {
        type: Object,
        default: () => ({})
    },
    courseStats: {
        type: Array,
        default: () => []
    },
    activeCourses: {
        type: Array,
        default: () => []
    },
    recentActivities: {
        type: Array,
        default: () => []
    }
});

// Faculty information — map server data into the field names the template uses,
// keeping decorative fields (qualification, specialization, research areas,
// achievements) static so the existing markup keeps rendering.
const faculty = computed(() => ({
    name: props.faculty.name ?? '',
    department: props.faculty.department ?? '',
    employeeId: props.faculty.employeeId ?? '',
    email: props.faculty.email ?? '',
    avatar: props.faculty.avatar
        ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(props.faculty.name ?? 'Faculty')}&background=10b981&color=fff`,
    qualification: props.faculty.qualification ?? 'Ph.D.',
    specialization: props.faculty.specialization ?? ['Machine Learning', 'Data Science', 'AI Ethics'],
    researchAreas: props.faculty.researchAreas ?? ['Artificial Intelligence', 'Natural Language Processing', 'Educational Technology'],
    achievements: props.faculty.achievements ?? [
        { title: 'Excellence in Teaching Award', year: '2023' },
        { title: 'Best Research Paper', year: '2022' }
    ]
}));

// Course statistics — re-attach the heroicon components (icons can't cross the
// JSON prop boundary) by mapping the server-provided icon key to a component.
const statIconMap = {
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChartBarIcon
};

const courseStats = computed(() =>
    props.courseStats.map((stat) => ({
        ...stat,
        icon: typeof stat.icon === 'string' ? (statIconMap[stat.icon] ?? ChartBarIcon) : (stat.icon ?? ChartBarIcon)
    }))
);

// Active Courses Section — map server fields to the template's field names,
// providing safe fallbacks for display-only fields not in the payload.
const activeCourses = computed(() =>
    props.activeCourses.map((course) => ({
        id: course.id,
        code: course.code,
        name: course.name,
        semester: course.semester,
        students: course.students ?? 0,
        progress: course.progress ?? 0,
        averageGrade: course.averageGrade ?? 0,
        status: course.status ?? 'active',
        recentActivity: course.recentActivity
            ?? `${course.materials ?? 0} materials • ${course.assignments ?? 0} assignments`
    }))
);

// Recent activities — map server fields to the template's field names; the
// icon is derived from `type` via getActivityIcon(), so only display fallbacks
// for title/course/status/priority are added here.
const recentActivities = computed(() =>
    props.recentActivities.map((activity) => ({
        id: activity.id,
        type: activity.type,
        title: activity.title ?? activity.description,
        description: activity.description,
        time: activity.time,
        course: activity.course ?? '',
        status: activity.status ?? 'completed',
        priority: activity.priority ?? 'normal'
    }))
);

// Student Insights Data
const studentInsights = ref({
    totalStudents: 324,
    activeStudents: 298,
    averagePerformance: 82.1,
    improvementTrend: '+4.2%',
    topPerformers: [
        { name: 'Alex Chen', course: 'CS 401', grade: 95.5 },
        { name: 'Maria Rodriguez', course: 'CS 301', grade: 94.2 },
        { name: 'John Smith', course: 'CS 101', grade: 93.8 }
    ],
    strugglingStudents: [
        { name: 'David Kim', course: 'CS 401', grade: 65.2, needsAttention: true },
        { name: 'Sarah Johnson', course: 'CS 301', grade: 68.5, needsAttention: true }
    ],
    engagementMetrics: {
        participationRate: 76,
        assignmentSubmission: 89,
        forumActivity: 45,
        officeHoursAttendance: 23
    },
    weeklyStats: [
        { week: 'Week 1', engagement: 85 },
        { week: 'Week 2', engagement: 78 },
        { week: 'Week 3', engagement: 82 },
        { week: 'Week 4', engagement: 89 }
    ]
});

// Quick actions with enhanced features
const quickActions = ref([
    {
        title: 'AI Teaching Assistant',
        description: 'Generate quiz questions and assignments',
        icon: SparklesIcon,
        action: 'faculty.ai-assistant',
        gradient: 'from-purple-500 to-indigo-600',
        type: 'primary',
        badge: 'Popular',
        usageCount: 45
    },
    {
        title: 'Grade Assignments',
        description: 'Review and grade student submissions',
        icon: DocumentTextIcon,
        action: 'grading',
        gradient: 'from-orange-500 to-red-600',
        type: 'urgent',
        badge: '47 Pending',
        usageCount: 120
    },
    {
        title: 'Upload Materials',
        description: 'Share course resources and documents',
        icon: PlusIcon,
        action: 'upload',
        gradient: 'from-green-500 to-emerald-600',
        type: 'secondary',
        badge: 'New',
        usageCount: 23
    },
    {
        title: 'Course Analytics',
        description: 'View student performance insights',
        icon: ChartBarIcon,
        action: 'analytics',
        gradient: 'from-blue-500 to-cyan-600',
        type: 'secondary',
        badge: 'Updated',
        usageCount: 67
    }
]);

// Enhanced schedule with more details
const upcomingSchedule = ref([
    {
        id: 1,
        title: 'Machine Learning Lecture',
        course: 'CS 401',
        time: '10:00 AM - 11:30 AM',
        room: 'Room 205',
        type: 'lecture',
        studentsCount: 45,
        topic: 'Neural Networks Introduction',
        status: 'upcoming'
    },
    {
        id: 2,
        title: 'Database Lab Session',
        course: 'CS 301',
        time: '2:00 PM - 4:00 PM',
        room: 'Lab 3',
        type: 'lab',
        studentsCount: 30,
        topic: 'Advanced Queries Practice',
        status: 'upcoming'
    },
    {
        id: 3,
        title: 'Research Meeting',
        course: 'AI Ethics Project',
        time: '4:30 PM - 5:30 PM',
        room: 'Conference Room A',
        type: 'meeting',
        studentsCount: 8,
        topic: 'Progress Review',
        status: 'upcoming'
    },
    {
        id: 4,
        title: 'Office Hours',
        course: 'All Courses',
        time: '5:30 PM - 6:30 PM',
        room: 'Office 304',
        type: 'office_hours',
        studentsCount: 0,
        topic: 'Student Consultations',
        status: 'available'
    }
]);

// Research projects with comprehensive data
const researchProjects = ref([
    {
        id: 1,
        title: 'AI Ethics in Educational Technology',
        description: 'Investigating ethical implications of AI in learning platforms and student privacy concerns',
        progress: 75,
        status: 'active',
        collaborators: 4,
        funding: '$50,000',
        deadline: '2024-08-15',
        publications: 2,
        category: 'Ethics',
        priority: 'high'
    },
    {
        id: 2,
        title: 'Natural Language Processing for Academic Writing',
        description: 'Developing AI tools to assist students with academic writing and citation management',
        progress: 45,
        status: 'active',
        collaborators: 6,
        funding: '$75,000',
        deadline: '2024-12-01',
        publications: 1,
        category: 'NLP',
        priority: 'medium'
    },
    {
        id: 3,
        title: 'Machine Learning in Curriculum Design',
        description: 'Optimizing course content delivery using ML algorithms for personalized learning',
        progress: 30,
        status: 'planning',
        collaborators: 3,
        funding: '$35,000',
        deadline: '2025-03-15',
        publications: 0,
        category: 'ML',
        priority: 'medium'
    }
]);

// Computed properties
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const todayClasses = computed(() => {
    return upcomingSchedule.value.filter(item =>
        item.status === 'upcoming' && (item.type === 'lecture' || item.type === 'lab')
    );
});

const urgentTasks = computed(() => {
    const urgentGrading = courseStats.value.find(stat => stat.label === 'Pending Assignments');
    return parseInt(urgentGrading?.change?.match(/\d+/)?.[0] || 0);
});

const researchProgress = computed(() => {
    const total = researchProjects.value.length;
    const avgProgress = researchProjects.value.reduce((sum, project) => sum + project.progress, 0) / total;
    return Math.round(avgProgress);
});

const overallStudentPerformance = computed(() => {
    const total = activeCourses.value.length;
    const avgGrade = activeCourses.value.reduce((sum, course) => sum + course.averageGrade, 0) / total;
    return Math.round(avgGrade * 10) / 10;
});

// Methods
const handleQuickAction = (action) => {
    if (action.startsWith('faculty.')) {
        router.visit(`/${action.replace('.', '/')}`);
    } else if (action === 'grading') {
        navigateToGrading();
    } else if (action === 'upload') {
        navigateToUpload();
    } else if (action === 'analytics') {
        router.visit('/faculty/analytics');
    } else if (action.startsWith('http')) {
        window.open(action, '_blank');
    } else {
        console.warn(`No route mapping found for action: ${action}`);
        const constructedRoute = `/faculty/${action.replace(/\./g, '/')}`;
        router.visit(constructedRoute);
    }
};

const viewCourse = (courseId) => {
    router.visit(route('faculty.courses.show', courseId));
};

const contactStudent = (studentName, studentId = null) => {
    if (studentId) {
        router.visit(`/faculty/students/${studentId}`);
    } else {
        router.visit(`/faculty/messages?student=${encodeURIComponent(studentName)}`);
    }
};

const navigateToAIAssistant = () => {
    router.visit('/faculty/ai-assistant');
};

const navigateToUpload = () => {
    router.visit('/faculty/materials/upload');
};

const navigateToGrading = () => {
    router.visit('/faculty/grading');
};

const getActivityIcon = (type) => {
    const iconMap = {
        grading: DocumentTextIcon,
        ai_assistant: SparklesIcon,
        student_contact: ChatBubbleLeftIcon,
        course_update: PresentationChartBarIcon
    };
    return iconMap[type] || InformationCircleIcon;
};

const getActivityColor = (type) => {
    const colorMap = {
        grading: 'orange',
        ai_assistant: 'purple',
        student_contact: 'blue',
        course_update: 'green'
    };
    return colorMap[type] || 'gray';
};

const getPriorityClass = (priority) => {
    const classes = {
        high: 'border-red-500 bg-red-50 dark:bg-red-900/20',
        medium: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
        normal: 'border-green-500 bg-green-50 dark:bg-green-900/20'
    };
    return classes[priority] || classes.normal;
};

const getCourseStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        upcoming: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    };
    return classes[status] || classes.active;
};
</script>

<template>
    <div>
        <Head title="Faculty Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">

                <!-- Enhanced Faculty Header with Perfect Placement -->
                <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-900 dark:via-emerald-900 dark:to-teal-900">
                    <!-- Decorative Background Pattern -->
                    <div class="absolute inset-0 bg-black/5"></div>
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 0%, transparent 50%)"></div>

                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">

                            <!-- Left Section: Faculty Profile Info -->
                            <div class="flex items-center gap-6 flex-1">
                                <div class="relative flex-shrink-0">
                                    <img
                                        :src="faculty.avatar"
                                        :alt="faculty.name"
                                        class="w-24 h-24 rounded-2xl shadow-2xl border-4 border-white/20 floating-avatar"
                                    />
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-400 rounded-full ring-4 ring-white dark:ring-gray-800 flex items-center justify-center pulse-animation">
                                        <AcademicCapIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <!-- Achievement Badge -->
                                    <div class="absolute -top-2 -left-2 w-6 h-6 bg-yellow-400 rounded-full ring-2 ring-white dark:ring-gray-800 flex items-center justify-center">
                                        <TrophyIcon class="w-4 h-4 text-white" />
                                    </div>
                                </div>

                                <div class="text-white min-w-0">
                                    <h1 class="text-3xl md:text-4xl font-bold mb-2 gradient-text">
                                        {{ greeting }}, {{ faculty.name.split(' ')[1] }}! 👨‍🏫
                                    </h1>
                                    <p class="text-white/90 text-lg mb-1">
                                        {{ faculty.employeeId }} • {{ faculty.department }}
                                    </p>
                                    <p class="text-white/80 text-sm mb-1">
                                        {{ faculty.qualification }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span
                                            v-for="area in faculty.specialization"
                                            :key="area"
                                            class="text-xs bg-white/20 backdrop-blur-sm px-2 py-1 rounded-full border border-white/20"
                                        >
                                            {{ area }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Section: Action Buttons - Perfect Two Rows Layout -->
                            <div class="flex flex-col gap-3 xl:items-end">

                                <!-- Top Row: Quick Action Buttons -->
                                <div class="flex items-center gap-2 flex-wrap justify-center xl:justify-end">
                                    <!-- Notifications with Enhanced Badge -->
                                    <button class="action-button group flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 relative">
                                        <BellIcon class="w-4 h-4 group-hover:animate-pulse" />
                                        <span class="text-sm font-medium hidden sm:inline">Alerts</span>
                                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center badge-pulse">
                                            <span class="text-xs text-white font-bold">{{ urgentTasks }}</span>
                                        </div>
                                    </button>

                                    <!-- AI Assistant with Sparkle Animation -->
                                    <Link
                                        :href="route('faculty.ai-assistant')"
                                        class="action-button group flex items-center gap-2 px-3 py-2 bg-purple-500/30 backdrop-blur-sm text-white hover:bg-purple-500/40 border border-purple-400/40 hover:border-purple-400/60 rounded-lg transition-all duration-200 transform hover:scale-105"
                                    >
                                        <SparklesIcon class="w-4 h-4 group-hover:animate-spin" />
                                        <span class="text-sm font-medium hidden sm:inline">AI Assistant</span>
                                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                    </Link>
                                </div>

                                <!-- Bottom Row: User Management Buttons -->
                                <div class="flex items-center gap-2 flex-wrap justify-center xl:justify-end">
                                    <!-- Profile -->
                                    <Link
                                        href="/profile"
                                        class="action-button flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 hover:scale-105"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        <span class="text-sm font-medium hidden sm:inline">Profile</span>
                                    </Link>

                                    <!-- Settings -->
                                    <Link
                                        href="/settings"
                                        class="action-button flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 hover:scale-105"
                                    >
                                        <Cog6ToothIcon class="w-4 h-4" />
                                        <span class="text-sm font-medium hidden sm:inline">Settings</span>
                                    </Link>

                                    <!-- Logout with Enhanced Styling -->
                                    <div class="logout-btn-wrapper">
                                        <LogoutButton class="action-button flex items-center gap-2 px-3 py-2 bg-red-500/30 backdrop-blur-sm text-white hover:bg-red-500/40 border border-red-400/40 hover:border-red-400/60 rounded-lg transition-all duration-200 hover:scale-105" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Quick Stats Row -->
                        <div class="mt-8 pt-6 border-t border-white/20">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1">6</div>
                                    <div class="text-sm text-white/80">Active Courses</div>
                                    <div class="text-xs text-green-300 mt-1">+1 this sem</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1">324</div>
                                    <div class="text-sm text-white/80">Total Students</div>
                                    <div class="text-xs text-green-300 mt-1">+23 this sem</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1 text-yellow-200">47</div>
                                    <div class="text-sm text-white/80">Pending Grading</div>
                                    <div class="text-xs text-yellow-300 mt-1">{{ urgentTasks }} urgent</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1">89</div>
                                    <div class="text-sm text-white/80">AI Queries</div>
                                    <div class="text-xs text-purple-300 mt-1">Today</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Content -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                    <!-- Quick Actions Grid with Enhanced Cards -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <FireIcon class="w-6 h-6 text-orange-500" />
                            Quick Actions
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div
                                v-for="action in quickActions"
                                :key="action.title"
                                @click="handleQuickAction(action.action)"
                                class="action-card group cursor-pointer bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden"
                            >
                                <!-- Badge -->
                                <div v-if="action.badge" class="absolute top-4 right-4 text-xs px-2 py-1 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white font-medium">
                                    {{ action.badge }}
                                </div>

                                <div class="flex items-center justify-between mb-4">
                                    <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${action.gradient} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform`">
                                        <component :is="action.icon" class="w-6 h-6 text-white" />
                                    </div>
                                    <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:translate-x-1 transition-all" />
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ action.title }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ action.description }}
                                </p>
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-500">
                                    <span>Used {{ action.usageCount }} times</span>
                                    <div class="flex items-center gap-1">
                                        <StarIcon class="w-3 h-3 text-yellow-400 fill-current" />
                                        <span>4.8</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <!-- Left Column: Statistics and Activities (8 cols) -->
                        <div class="lg:col-span-8 space-y-8">

                            <!-- Enhanced Statistics Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    v-for="stat in courseStats"
                                    :key="stat.label"
                                    class="stat-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                                >
                                    <div class="flex items-center justify-between mb-4">
                                        <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${stat.gradient} flex items-center justify-center shadow-lg`">
                                            <component :is="stat.icon" class="w-6 h-6 text-white" />
                                        </div>
                                        <div :class="`text-xs px-2 py-1 rounded-full ${stat.trend === 'up' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : stat.trend === 'down' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}`">
                                            {{ stat.change }}
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                            {{ stat.value }}
                                        </h3>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            {{ stat.label }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mb-2">
                                            {{ stat.description }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-600">
                                            {{ stat.details }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Courses Section -->
                            <div class="courses-section bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <BookOpenIcon class="w-5 h-5 text-blue-500" />
                                        Active Courses
                                        <span class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full">
                                            {{ activeCourses.length }} courses
                                        </span>
                                    </h3>
                                    <Link href="/faculty/courses" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm font-medium hover-lift">
                                        View All Courses →
                                    </Link>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <div
                                        v-for="course in activeCourses"
                                        :key="course.id"
                                        @click="viewCourse(course.id)"
                                        class="course-card cursor-pointer bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                                    >
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">
                                                    {{ course.code }}
                                                </h4>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ course.name }}
                                                </p>
                                            </div>
                                            <span :class="`text-xs px-2 py-1 rounded-full ${getCourseStatusClass(course.status)}`">
                                                {{ course.status }}
                                            </span>
                                        </div>

                                        <div class="space-y-2 mb-3">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ course.progress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                                <div
                                                    class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full transition-all duration-500"
                                                    :style="`width: ${course.progress}%`"
                                                ></div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400 mb-3">
                                            <div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ course.students }}</span> students
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ course.averageGrade }}%</span> avg
                                            </div>
                                        </div>

                                        <div class="border-t border-blue-200 dark:border-blue-800 pt-2">
                                            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                                {{ course.recentActivity }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Recent Activities -->
                            <div class="activity-section bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ClockIcon class="w-5 h-5 text-indigo-500" />
                                        Recent Activities
                                    </h3>
                                    <Link href="#" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium hover-lift">
                                        View All →
                                    </Link>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="activity in recentActivities"
                                        :key="activity.id"
                                        :class="`activity-item flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-l-4 ${getPriorityClass(activity.priority)}`"
                                    >
                                        <div :class="`w-10 h-10 rounded-lg flex items-center justify-center ${activity.type === 'grading' ? 'bg-orange-100 dark:bg-orange-900/30' : activity.type === 'ai_assistant' ? 'bg-purple-100 dark:bg-purple-900/30' : activity.type === 'student_contact' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-green-100 dark:bg-green-900/30'}`">
                                            <component :is="getActivityIcon(activity.type)" :class="`w-5 h-5 ${activity.type === 'grading' ? 'text-orange-600 dark:text-orange-400' : activity.type === 'ai_assistant' ? 'text-purple-600 dark:text-purple-400' : activity.type === 'student_contact' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400'}`" />
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="font-medium text-gray-900 dark:text-white">{{ activity.title }}</h4>
                                                <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ activity.course }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ activity.description }}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <p class="text-xs text-gray-500 dark:text-gray-500">{{ activity.time }}</p>
                                                <div class="flex items-center gap-1">
                                                    <CheckCircleIcon class="w-4 h-4 text-green-500" />
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ activity.status }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Schedule, Student Insights & Research (4 cols) -->
                        <div class="lg:col-span-4 space-y-8">

                            <!-- Enhanced Today's Schedule -->
                            <div class="schedule-section bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <CalendarIcon class="w-5 h-5 text-indigo-500" />
                                        Today's Schedule
                                    </h3>
                                    <div class="text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2 py-1 rounded-full">
                                        {{ todayClasses.length }} classes
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="item in upcomingSchedule"
                                        :key="item.id"
                                        :class="`schedule-item p-4 rounded-lg border-l-4 transition-colors ${item.status === 'upcoming' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : item.status === 'available' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-500 bg-gray-50 dark:bg-gray-900/20'}`"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                                    {{ item.title }}
                                                </h4>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ item.course }} • {{ item.room }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                    {{ item.topic }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs font-medium text-gray-900 dark:text-white">
                                                    {{ item.time }}
                                                </p>
                                                <p v-if="item.studentsCount > 0" class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                                    {{ item.studentsCount }} students
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span :class="`text-xs px-2 py-1 rounded-full ${item.type === 'lecture' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : item.type === 'lab' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : item.type === 'meeting' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}`">
                                                {{ item.type.replace('_', ' ') }}
                                            </span>
                                            <span :class="`text-xs font-medium ${item.status === 'upcoming' ? 'text-indigo-600 dark:text-indigo-400' : item.status === 'available' ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'}`">
                                                {{ item.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/schedule"
                                    class="block mt-4 text-center py-2 text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium hover-lift"
                                >
                                    View Full Schedule →
                                </Link>
                            </div>

                            <!-- Student Insights Section -->
                            <div class="student-insights bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg p-6 text-white">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold flex items-center gap-2">
                                        <ChartPieIcon class="w-5 h-5 text-white/80" />
                                        Student Insights
                                    </h3>
                                    <div class="text-xs bg-white/20 px-2 py-1 rounded-full">
                                        {{ overallStudentPerformance }}% avg
                                    </div>
                                </div>

                                <!-- Performance Metrics -->
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">{{ studentInsights.activeStudents }}</div>
                                        <div class="text-xs text-white/80">Active Students</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">{{ studentInsights.engagementMetrics.participationRate }}%</div>
                                        <div class="text-xs text-white/80">Participation</div>
                                    </div>
                                </div>

                                <!-- Top Performers -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium mb-2 flex items-center gap-1">
                                        <StarIcon class="w-4 h-4 text-yellow-300" />
                                        Top Performers
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="student in studentInsights.topPerformers.slice(0, 2)"
                                            :key="student.name"
                                            class="flex items-center justify-between bg-white/10 rounded-lg p-2"
                                        >
                                            <div>
                                                <div class="text-sm font-medium">{{ student.name }}</div>
                                                <div class="text-xs text-white/70">{{ student.course }}</div>
                                            </div>
                                            <div class="text-sm font-bold text-yellow-300">
                                                {{ student.grade }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Students Needing Attention -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium mb-2 flex items-center gap-1">
                                        <ExclamationTriangleIcon class="w-4 h-4 text-red-300" />
                                        Needs Attention
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="student in studentInsights.strugglingStudents"
                                            :key="student.name"
                                            class="flex items-center justify-between bg-red-500/20 rounded-lg p-2 border border-red-400/20"
                                        >
                                            <div>
                                                <div class="text-sm font-medium">{{ student.name }}</div>
                                                <div class="text-xs text-white/70">{{ student.course }}</div>
                                            </div>
                                            <div class="text-sm font-bold text-red-300">
                                                {{ student.grade }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/student-analytics"
                                    class="block text-center text-sm text-white/90 hover:text-white font-medium hover-lift"
                                >
                                    View Detailed Analytics →
                                </Link>
                            </div>

                            <!-- Enhanced Research Projects -->
                            <div class="research-section bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold flex items-center gap-2">
                                        <BeakerIcon class="w-5 h-5 text-white/80" />
                                        Active Research
                                    </h3>
                                    <div class="text-xs bg-white/20 px-2 py-1 rounded-full">
                                        {{ researchProgress }}% avg progress
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="project in researchProjects.slice(0, 2)"
                                        :key="project.id"
                                        class="research-card bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 hover:bg-white/15 transition-colors"
                                    >
                                        <div class="flex justify-between items-start mb-3">
                                            <h4 class="font-medium text-white text-sm line-clamp-2 flex-1">
                                                {{ project.title }}
                                            </h4>
                                            <div class="flex flex-col items-end gap-1 ml-2">
                                                <span :class="`text-xs px-2 py-1 rounded-full ${project.status === 'active' ? 'bg-green-400/20 text-green-200' : project.status === 'planning' ? 'bg-yellow-400/20 text-yellow-200' : 'bg-gray-400/20 text-gray-200'}`">
                                                    {{ project.status }}
                                                </span>
                                                <span class="text-xs bg-white/20 px-2 py-1 rounded-full">
                                                    {{ project.category }}
                                                </span>
                                            </div>
                                        </div>

                                        <p class="text-xs text-white/80 mb-3 line-clamp-2">
                                            {{ project.description }}
                                        </p>

                                        <!-- Progress Bar -->
                                        <div class="mb-3">
                                            <div class="flex justify-between text-xs text-white/80 mb-1">
                                                <span>Progress</span>
                                                <span>{{ project.progress }}%</span>
                                            </div>
                                            <div class="w-full bg-white/20 rounded-full h-2">
                                                <div
                                                    class="bg-gradient-to-r from-green-400 to-blue-400 h-2 rounded-full transition-all duration-500"
                                                    :style="`width: ${project.progress}%`"
                                                ></div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-2 text-xs text-white/80">
                                            <div class="text-center">
                                                <div class="font-medium text-white">{{ project.collaborators }}</div>
                                                <div>Team</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="font-medium text-white">{{ project.publications }}</div>
                                                <div>Papers</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="font-medium text-white">{{ project.funding }}</div>
                                                <div>Funding</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/research"
                                    class="block mt-4 text-center text-sm text-white/90 hover:text-white font-medium hover-lift"
                                >
                                    View Research Dashboard →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Line clamping utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Enhanced Header Styling */
.floating-avatar {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.gradient-text {
    background: linear-gradient(45deg, #ffffff, #e0f2fe, #ffffff);
    background-size: 200% 200%;
    background-clip: text;
    -webkit-background-clip: text;
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
/* Continue from where .pulse-a was cut off */
.pulse-animation {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Action Button Enhancements */
.action-button {
    position: relative;
    overflow: hidden;
}

.action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s;
}

.action-button:hover::before {
    left: 100%;
}

/* Perfect Header Styling */
.logout-btn-wrapper :deep(.flex) {
    @apply !bg-red-500/30 !border-red-400/40 !text-white;
}

.logout-btn-wrapper :deep(.flex:hover) {
    @apply !bg-red-500/40 !border-red-400/60 !text-white;
}

/* Backdrop blur effects */
.bg-white\/20,
.bg-purple-500\/30,
.bg-red-500\/30 {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Stats Card Enhancements */
.stats-card {
    position: relative;
    overflow: hidden;
}

.stats-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    transform: translateX(100%);
    transition: transform 0.6s;
}

.stats-card:hover::after {
    transform: translateX(-100%);
}

/* Card Hover Effects */
.action-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.action-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.dark .action-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.action-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s ease;
}

.action-card:hover::after {
    left: 100%;
}

/* Stat Card Effects */
.stat-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.dark .stat-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

/* Activity Item Animations */
.activity-item {
    transition: all 0.3s ease;
    position: relative;
}

.activity-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .activity-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Schedule Item Enhancements */
.schedule-item {
    transition: all 0.3s ease;
    position: relative;
}

.schedule-item:hover {
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Research Card Effects */
.research-card {
    transition: all 0.3s ease;
    position: relative;
}

.research-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

/* Student Insights Section Styles */
.student-insights {
    position: relative;
    overflow: hidden;
}

.student-insights::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.insight-metric {
    transition: all 0.3s ease;
}

.insight-metric:hover {
    transform: scale(1.05);
    background: rgba(255,255,255,0.2);
}

/* Performance Progress Bar */
.performance-progress {
    background: linear-gradient(90deg,
        rgba(255,255,255,0.2) 0%,
        rgba(255,255,255,0.1) 50%,
        rgba(255,255,255,0.2) 100%);
    animation: progressShimmer 2s ease-in-out infinite;
}

@keyframes progressShimmer {
    0%, 100% { opacity: 0.8; }
    50% { opacity: 1; }
}

/* Course Section Enhancements */
.course-section {
    transition: all 0.3s ease;
    position: relative;
}

.course-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.dark .course-section:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.course-card {
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.course-card:hover {
    border-color: rgba(99, 102, 241, 0.3);
    background: rgba(99, 102, 241, 0.02);
    transform: scale(1.01);
}

.dark .course-card:hover {
    border-color: rgba(99, 102, 241, 0.5);
    background: rgba(99, 102, 241, 0.05);
}

/* Assignment section cards */
.assignment-section-card {
    transition: all 0.3s ease;
}

.assignment-section-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Badge animations */
.badge-pulse {
    animation: badgePulse 2s infinite;
}

@keyframes badgePulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

/* Hover lift effect */
.hover-lift:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}

/* Document card effects */
.document-card {
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.dark .document-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

/* Section fade-in animations */
.section-fade {
    animation: sectionFadeIn 0.6s ease-out;
}

@keyframes sectionFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive text visibility */
@media (max-width: 640px) {
    .hidden.sm\:inline {
        display: none;
    }

    .action-button {
        justify-content: center;
        min-width: 44px;
        min-height: 44px;
    }

    .stats-card {
        text-align: center;
        padding: 12px;
    }

    .insight-metric {
        padding: 12px;
    }
}

/* Enhanced mobile responsiveness */
@media (max-width: 768px) {
    .activity-item {
        padding: 12px;
        flex-direction: column;
        gap: 8px;
    }

    .schedule-item {
        padding: 12px;
    }

    .research-card {
        padding: 16px;
    }

    .course-card {
        padding: 16px;
    }

    .student-insights {
        padding: 16px;
    }
}

/* Tablet responsiveness */
@media (max-width: 1024px) and (min-width: 769px) {
    .action-button span {
        display: none;
    }

    .xl\:justify-end {
        justify-content: center;
    }

    .xl\:items-end {
        align-items: center;
    }
}

/* Loading states */
.skeleton {
    background: linear-gradient(90deg,
        rgba(255,255,255,0.1) 0%,
        rgba(255,255,255,0.2) 50%,
        rgba(255,255,255,0.1) 100%);
    background-size: 200% 100%;
    animation: skeleton 1.5s ease-in-out infinite;
}

@keyframes skeleton {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Focus states for accessibility */
.action-button:focus,
.course-card:focus,
.activity-item:focus {
    outline: 2px solid rgba(99, 102, 241, 0.5);
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .action-button,
    .logout-btn-wrapper,
    .badge-pulse,
    .floating-avatar {
        display: none;
    }

    .stats-card,
    .course-card,
    .research-card {
        break-inside: avoid;
    }
}
</style>