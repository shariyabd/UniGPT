<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    UserGroupIcon,
    DocumentTextIcon,
    ChartBarIcon,
    CalendarIcon,
    ClockIcon,
    BookOpenIcon,
    PresentationChartLineIcon,
    BeakerIcon,
    ChatBubbleLeftRightIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    StarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon
} from '@heroicons/vue/24/outline';

// Faculty profile data
const faculty = ref({
    name: 'Dr. Sarah Smith',
    title: 'Professor & Head of Department',
    department: 'Computer Science Engineering',
    email: 'sarah.smith@university.edu',
    phone: '+1 (555) 234-5678',
    office: 'CSE Building, Room 301',
    avatar: 'https://ui-avatars.com/api/?name=Sarah+Smith&background=10b981&color=fff',
    joinDate: '2015-07-01',
    qualification: 'Ph.D. Computer Science, MIT',
    specialization: ['Machine Learning', 'Data Science', 'AI Ethics'],
    researchAreas: ['Artificial Intelligence', 'Natural Language Processing', 'Educational Technology']
});

// Course statistics
const courseStats = ref([
    {
        label: 'Active Courses',
        value: '6',
        change: '+1 this semester',
        trend: 'up',
        icon: BookOpenIcon,
        gradient: 'from-blue-500 to-cyan-600',
        description: 'Currently teaching courses'
    },
    {
        label: 'Total Students',
        value: '324',
        change: '+23 this semester',
        trend: 'up',
        icon: UserGroupIcon,
        gradient: 'from-green-500 to-emerald-600',
        description: 'Enrolled across all courses'
    },
    {
        label: 'Pending Assignments',
        value: '47',
        change: '12 due today',
        trend: 'attention',
        icon: DocumentTextIcon,
        gradient: 'from-orange-500 to-red-600',
        description: 'Awaiting grading'
    },
    {
        label: 'Research Papers',
        value: '23',
        change: '+3 this year',
        trend: 'up',
        icon: BeakerIcon,
        gradient: 'from-purple-500 to-pink-600',
        description: 'Published works'
    }
]);

// Active courses
const activeCourses = ref([
    {
        id: 1,
        code: 'CSE301',
        name: 'Machine Learning Fundamentals',
        semester: 'Spring 2024',
        students: 85,
        maxStudents: 90,
        schedule: 'MWF 10:00-11:00 AM',
        classroom: 'CSE Lab 1',
        completion: 65,
        assignments: {
            pending: 12,
            graded: 28,
            total: 40
        },
        averageGrade: 82.5,
        attendance: 88.3,
        nextClass: '2024-01-16T10:00:00',
        recentActivity: [
            { type: 'assignment', title: 'Assignment 3 submitted', count: 15, time: '2 hours ago' },
            { type: 'question', title: 'New questions in forum', count: 3, time: '4 hours ago' }
        ]
    },
    {
        id: 2,
        code: 'CSE401',
        name: 'Advanced AI & Deep Learning',
        semester: 'Spring 2024',
        students: 45,
        maxStudents: 50,
        schedule: 'TTh 2:00-3:30 PM',
        classroom: 'CSE Lab 2',
        completion: 45,
        assignments: {
            pending: 8,
            graded: 15,
            total: 23
        },
        averageGrade: 87.2,
        attendance: 92.1,
        nextClass: '2024-01-16T14:00:00',
        recentActivity: [
            { type: 'submission', title: 'Project proposals due', count: 42, time: '1 day ago' }
        ]
    },
    {
        id: 3,
        code: 'CSE502',
        name: 'Research Methodology',
        semester: 'Spring 2024',
        students: 28,
        maxStudents: 30,
        schedule: 'W 4:00-6:00 PM',
        classroom: 'Conference Room A',
        completion: 30,
        assignments: {
            pending: 5,
            graded: 8,
            total: 13
        },
        averageGrade: 85.8,
        attendance: 96.4,
        nextClass: '2024-01-17T16:00:00',
        recentActivity: [
            { type: 'discussion', title: 'Research paper discussions', count: 8, time: '6 hours ago' }
        ]
    }
]);

// Student performance insights
const studentInsights = ref([
    {
        type: 'at-risk',
        title: 'Students at Risk',
        count: 12,
        description: 'Students with attendance < 75% or failing grades',
        students: [
            { name: 'John Doe', course: 'CSE301', issue: 'Low attendance (62%)', severity: 'high' },
            { name: 'Jane Smith', course: 'CSE401', issue: 'Failing grade (45%)', severity: 'high' },
            { name: 'Mike Johnson', course: 'CSE301', issue: 'Missing assignments', severity: 'medium' }
        ],
        action: 'Review & Contact'
    },
    {
        type: 'high-performers',
        title: 'Top Performers',
        count: 23,
        description: 'Students consistently scoring above 90%',
        students: [
            { name: 'Alice Wang', course: 'CSE401', achievement: 'Perfect attendance, 95% average', level: 'excellent' },
            { name: 'David Lee', course: 'CSE301', achievement: 'Top 5% performance', level: 'excellent' },
            { name: 'Sarah Kim', course: 'CSE502', achievement: 'Outstanding research proposal', level: 'excellent' }
        ],
        action: 'Consider for TA/RA'
    },
    {
        type: 'engagement',
        title: 'Low Engagement',
        count: 8,
        description: 'Students with minimal forum/class participation',
        students: [
            { name: 'Tom Wilson', course: 'CSE301', issue: 'No forum participation', level: 'low' },
            { name: 'Lisa Brown', course: 'CSE401', issue: 'Minimal class interaction', level: 'medium' }
        ],
        action: 'Encourage Participation'
    }
]);

// Upcoming schedule
const upcomingSchedule = ref([
    {
        id: 1,
        type: 'class',
        title: 'CSE301 - Machine Learning Fundamentals',
        time: '2024-01-16T10:00:00',
        duration: '60 minutes',
        location: 'CSE Lab 1',
        attendees: 85,
        status: 'scheduled'
    },
    {
        id: 2,
        type: 'meeting',
        title: 'Faculty Meeting - Curriculum Review',
        time: '2024-01-16T15:00:00',
        duration: '90 minutes',
        location: 'Conference Room B',
        attendees: 12,
        status: 'scheduled'
    },
    {
        id: 3,
        type: 'office-hours',
        title: 'Office Hours',
        time: '2024-01-16T16:30:00',
        duration: '120 minutes',
        location: 'CSE 301',
        attendees: null,
        status: 'available'
    },
    {
        id: 4,
        type: 'class',
        title: 'CSE401 - Advanced AI & Deep Learning',
        time: '2024-01-18T14:00:00',
        duration: '90 minutes',
        location: 'CSE Lab 2',
        attendees: 45,
        status: 'scheduled'
    }
]);

// Recent activities
const recentActivities = ref([
    {
        id: 1,
        type: 'grading',
        title: 'Graded 15 assignments for CSE301',
        description: 'Machine Learning Assignment 3 - Average score: 82%',
        timestamp: '2024-01-15T14:30:00',
        course: 'CSE301',
        impact: 'positive'
    },
    {
        id: 2,
        type: 'material',
        title: 'Uploaded new lecture materials',
        description: 'Deep Learning Neural Networks - Week 8 content',
        timestamp: '2024-01-15T11:20:00',
        course: 'CSE401',
        impact: 'neutral'
    },
    {
        id: 3,
        type: 'student',
        title: 'Student consultation completed',
        description: 'Advised Alice Wang on research paper topic selection',
        timestamp: '2024-01-15T09:45:00',
        course: 'CSE502',
        impact: 'positive'
    },
    {
        id: 4,
        type: 'forum',
        title: 'Responded to student questions',
        description: '8 questions answered in course forums',
        timestamp: '2024-01-14T16:15:00',
        course: 'Multiple',
        impact: 'positive'
    }
]);

// Research & publications
const researchData = ref({
    currentProjects: [
        {
            title: 'Ethical AI in Educational Technology',
            status: 'active',
            funding: '$125,000',
            collaborators: 3,
            progress: 75,
            deadline: '2024-12-31'
        },
        {
            title: 'Natural Language Processing for Academic Writing',
            status: 'proposal',
            funding: '$200,000',
            collaborators: 5,
            progress: 15,
            deadline: '2024-06-30'
        }
    ],
    recentPublications: [
        {
            title: 'Machine Learning Approaches to Student Performance Prediction',
            journal: 'Journal of Educational Technology',
            year: 2024,
            citations: 23,
            impact: 'high'
        },
        {
            title: 'Ethical Considerations in AI-Powered Learning Systems',
            conference: 'International Conference on AI in Education',
            year: 2023,
            citations: 18,
            impact: 'medium'
        }
    ]
});

// Quick actions
const quickActions = ref([
    {
        label: 'Create Assignment',
        description: 'Design new coursework',
        icon: PlusIcon,
        gradient: 'from-blue-500 to-indigo-600',
        action: 'assignments.create'
    },
    {
        label: 'Grade Submissions',
        description: 'Review pending work',
        icon: DocumentTextIcon,
        gradient: 'from-orange-500 to-red-600',
        action: 'grading.pending'
    },
    {
        label: 'Course Analytics',
        description: 'View detailed insights',
        icon: ChartBarIcon,
        gradient: 'from-green-500 to-emerald-600',
        action: 'analytics.courses'
    },
    {
        label: 'AI Teaching Assistant',
        description: 'Get AI-powered help',
        icon: ChatBubbleLeftRightIcon,
        gradient: 'from-purple-500 to-pink-600',
        action: 'ai.assistant'
    },
    {
        label: 'Upload Materials',
        description: 'Add course content',
        icon: BookOpenIcon,
        gradient: 'from-cyan-500 to-blue-600',
        action: 'materials.upload'
    },
    {
        label: 'Schedule Office Hours',
        description: 'Set availability',
        icon: CalendarIcon,
        gradient: 'from-yellow-500 to-orange-600',
        action: 'schedule.office'
    }
]);

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTimeAgo = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
};

const getGradeColor = (grade) => {
    if (grade >= 90) return 'text-green-600 dark:text-green-400';
    if (grade >= 80) return 'text-blue-600 dark:text-blue-400';
    if (grade >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getAttendanceColor = (attendance) => {
    if (attendance >= 90) return 'text-green-600 dark:text-green-400';
    if (attendance >= 80) return 'text-blue-600 dark:text-blue-400';
    if (attendance >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getActivityIcon = (type) => {
    const icons = {
        grading: DocumentTextIcon,
        material: BookOpenIcon,
        student: UserGroupIcon,
        forum: ChatBubbleLeftRightIcon,
        class: AcademicCapIcon
    };
    return icons[type] || DocumentTextIcon;
};

const getScheduleIcon = (type) => {
    const icons = {
        class: AcademicCapIcon,
        meeting: UserGroupIcon,
        'office-hours': ClockIcon
    };
    return icons[type] || CalendarIcon;
};

// Actions
const navigateTo = (action) => {
    alert(`Navigating to: ${action}`);
};

const viewCourse = (courseId) => {
    alert(`Viewing course details for course ${courseId}`);
};

const contactStudent = (studentName) => {
    alert(`Contacting student: ${studentName}`);
};
</script>

<template>
    <div>
        <Head title="Faculty Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Faculty Profile Header -->
                <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-900 dark:via-emerald-900 dark:to-teal-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                            <!-- Profile Info -->
                            <div class="flex items-center gap-6">
                                <img
                                    :src="faculty.avatar"
                                    :alt="faculty.name"
                                    class="w-24 h-24 rounded-2xl shadow-2xl border-4 border-white/20"
                                />
                                <div class="text-white">
                                    <h1 class="text-4xl font-bold mb-2">
                                        Welcome back, {{ faculty.name.split(' ')[1] }}! 👋
                                    </h1>
                                    <p class="text-xl text-white/90 mb-2">
                                        {{ faculty.title }}
                                    </p>
                                    <p class="text-white/80 mb-1">
                                        {{ faculty.department }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span
                                            v-for="area in faculty.specialization"
                                            :key="area"
                                            class="px-3 py-1 bg-white/20 backdrop-blur-lg rounded-full text-sm font-medium"
                                        >
                                            {{ area }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="flex flex-wrap gap-4 ml-auto">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">6</div>
                                    <div class="text-xs text-white/80">Active Courses</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">324</div>
                                    <div class="text-xs text-white/80">Students</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">8.7</div>
                                    <div class="text-xs text-white/80">Avg Rating</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Overview Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div
                            v-for="stat in courseStats"
                            :key="stat.label"
                            class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2"
                        >
                            <!-- Gradient Background -->
                            <div :class="`absolute inset-0 bg-gradient-to-br ${stat.gradient} opacity-5 group-hover:opacity-10 transition-opacity`"></div>

                            <div class="relative p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div :class="`p-3 rounded-xl bg-gradient-to-br ${stat.gradient} shadow-lg`">
                                        <component :is="stat.icon" class="w-6 h-6 text-white" />
                                    </div>
                                    <span :class="`text-xs font-semibold px-2 py-1 rounded-full ${
                                        stat.trend === 'up' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                        stat.trend === 'attention' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' :
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    }`">
                                        {{ stat.change }}
                                    </span>
                                </div>

                                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                    {{ stat.value }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    {{ stat.label }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ stat.description }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column - Courses & Activities -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Active Courses -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <BookOpenIcon class="w-6 h-6 text-green-600" />
                                        Active Courses
                                    </h2>
                                    <Link href="/faculty/courses" class="text-sm text-green-600 dark:text-green-400 hover:text-green-700 font-medium">
                                        View All →
                                    </Link>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="course in activeCourses"
                                        :key="course.id"
                                        class="group p-4 border-2 border-gray-100 dark:border-gray-700 rounded-xl hover:border-green-300 dark:hover:border-green-700 hover:bg-green-50 dark:hover:bg-green-950/30 transition-all duration-300 cursor-pointer"
                                        @click="viewCourse(course.id)"
                                    >
                                        <div class="flex items-start justify-between gap-4 mb-3">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ course.code }}</h3>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ course.semester }}</span>
                                                </div>
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ course.name }}</h4>
                                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                    <span>👥 {{ course.students }}/{{ course.maxStudents }} students</span>
                                                    <span>📅 {{ course.schedule }}</span>
                                                    <span>🏛️ {{ course.classroom }}</span>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                                    {{ course.completion }}%
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Progress</div>
                                            </div>
                                        </div>

                                        <!-- Course Metrics -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                                <div :class="`text-lg font-bold ${getGradeColor(course.averageGrade)}`">
                                                    {{ course.averageGrade }}%
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">Avg Grade</div>
                                            </div>
                                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                                <div :class="`text-lg font-bold ${getAttendanceColor(course.attendance)}`">
                                                    {{ course.attendance }}%
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">Attendance</div>
                                            </div>
                                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                                    {{ course.assignments.pending }}
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">Pending</div>
                                            </div>
                                        </div>

                                        <!-- Recent Activity -->
                                        <div class="space-y-2">
                                            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Activity:</h5>
                                            <div
                                                v-for="activity in course.recentActivity"
                                                :key="activity.title"
                                                class="flex items-center justify-between text-sm"
                                            >
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    {{ activity.title }} ({{ activity.count }})
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-500">{{ activity.time }}</span>
                                            </div>
                                        </div>

                                        <!-- Quick Actions -->
                                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                            <button class="flex items-center gap-1 px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                <EyeIcon class="w-3 h-3" />
                                                View Details
                                            </button>
                                            <button class="flex items-center gap-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                <PencilIcon class="w-3 h-3" />
                                                Grade Work
                                            </button>
                                            <button class="flex items-center gap-1 px-3 py-1.5 text-xs bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                                <ChatBubbleLeftRightIcon class="w-3 h-3" />
                                                AI Assist
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <BoltIcon class="w-6 h-6 text-purple-600" />
                                    Quick Actions
                                </h2>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <button
                                        v-for="action in quickActions"
                                        :key="action.label"
                                        @click="navigateTo(action.action)"
                                        class="group relative bg-gradient-to-br p-[2px] rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                        :class="action.gradient"
                                    >
                                        <div class="bg-white dark:bg-gray-800 rounded-[10px] p-4 h-full">
                                            <component :is="action.icon" :class="`w-8 h-8 mb-3 bg-gradient-to-br ${action.gradient} bg-clip-text text-transparent`" />
                                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                                {{ action.label }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ action.description }}
                                            </p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Student Insights -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <UserGroupIcon class="w-6 h-6 text-blue-600" />
                                    Student Insights
                                </h2>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div
                                        v-for="insight in studentInsights"
                                        :key="insight.type"
                                        class="border border-gray-200 dark:border-gray-700 rounded-xl p-4"
                                    >
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ insight.title }}</h3>
                                            <span :class="`px-2 py-1 text-xs font-bold rounded-full ${
                                                insight.type === 'at-risk' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                                insight.type === 'high-performers' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                            }`">
                                                {{ insight.count }}
                                            </span>
                                        </div>

                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ insight.description }}</p>

                                        <div class="space-y-2 mb-4">
                                            <div
                                                v-for="student in insight.students.slice(0, 2)"
                                                :key="student.name"
                                                class="text-sm"
                                            >
                                                <div class="font-medium text-gray-900 dark:text-white">{{ student.name }}</div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    {{ student.course }} - {{ student.issue || student.achievement }}
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            {{ insight.action }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Schedule & Activities -->
                        <div class="space-y-8">
                            <!-- Upcoming Schedule -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <CalendarIcon class="w-6 h-6 text-indigo-600" />
                                    Today's Schedule
                                </h2>

                                <div class="space-y-4">
                                    <div
                                        v-for="item in upcomingSchedule.slice(0, 4)"
                                        :key="item.id"
                                        class="flex items-start gap-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div :class="`w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ${
                                            item.type === 'class' ? 'bg-green-100 dark:bg-green-900/30' :
                                            item.type === 'meeting' ? 'bg-blue-100 dark:bg-blue-900/30' :
                                            'bg-purple-100 dark:bg-purple-900/30'
                                        }`">
                                            <component
                                                :is="getScheduleIcon(item.type)"
                                                :class="`w-5 h-5 ${
                                                    item.type === 'class' ? 'text-green-600 dark:text-green-400' :
                                                    item.type === 'meeting' ? 'text-blue-600 dark:text-blue-400' :
                                                    'text-purple-600 dark:text-purple-400'
                                                }`"
                                            />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ item.title }}</h4>
                                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                <span>{{ formatTime(item.time) }}</span>
                                                <span>•</span>
                                                <span>{{ item.duration }}</span>
                                                <span>•</span>
                                                <span>{{ item.location }}</span>
                                            </div>
                                            <div v-if="item.attendees" class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                {{ item.attendees }} attendees
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/schedule"
                                    class="block mt-4 text-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium"
                                >
                                    View Full Calendar →
                                </Link>
                            </div>

                            <!-- Recent Activities -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <ClockIcon class="w-6 h-6 text-gray-600" />
                                    Recent Activities
                                </h2>

                                <div class="space-y-4">
                                    <div
                                        v-for="activity in recentActivities.slice(0, 5)"
                                        :key="activity.id"
                                        class="flex items-start space-x-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                                    >
                                        <div :class="`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                                            activity.impact === 'positive' ? 'bg-green-100 dark:bg-green-900/30' :
                                            activity.impact === 'negative' ? 'bg-red-100 dark:bg-red-900/30' :
                                            'bg-blue-100 dark:bg-blue-900/30'
                                        }`">
                                            <component
                                                :is="getActivityIcon(activity.type)"
                                                :class="`w-4 h-4 ${
                                                    activity.impact === 'positive' ? 'text-green-600 dark:text-green-400' :
                                                    activity.impact === 'negative' ? 'text-red-600 dark:text-red-400' :
                                                    'text-blue-600 dark:text-blue-400'
                                                }`"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.title }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ activity.description }}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">{{ activity.course }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-500">{{ getTimeAgo(activity.timestamp) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/activities"
                                    class="block mt-4 text-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-700 font-medium"
                                >
                                    View All Activities →
                                </Link>
                            </div>

                            <!-- Research Overview -->
                            <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <BeakerIcon class="w-6 h-6" />
                                    Research Overview
                                </h2>

                                <div class="space-y-4">
                                    <div v-for="project in researchData.currentProjects" :key="project.title">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-semibold text-sm">{{ project.title }}</h4>
                                            <span :class="`text-xs px-2 py-1 rounded-full ${
                                                project.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                            }`">
                                                {{ project.status }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-white/20 rounded-full h-2 mb-2">
                                            <div
                                                class="bg-white h-2 rounded-full transition-all duration-500"
                                                :style="{ width: project.progress + '%' }"
                                            ></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-white/80">
                                            <span>{{ project.funding }}</span>
                                            <span>{{ project.collaborators }} collaborators</span>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/faculty/research"
                                    class="block mt-4 text-center text-sm text-white/90 hover:text-white font-medium"
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Hover animations */
.document-card {
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-4px);
}

/* Custom gradient text */
.bg-clip-text {
    -webkit-background-clip: text;
    background-clip: text;
}
</style>