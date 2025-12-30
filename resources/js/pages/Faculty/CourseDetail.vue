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
    PlusIcon,
    EyeIcon,
    PencilIcon,
    ChatBubbleLeftRightIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowDownTrayIcon,
    PlayIcon,
    VideoCameraIcon,
    MicrophoneIcon
} from '@heroicons/vue/24/outline';

// Component state
const activeTab = ref('overview');
const selectedWeek = ref(8);

// Mock course data
const courseData = ref({
    id: 1,
    code: 'CSE301',
    name: 'Machine Learning Fundamentals',
    semester: 'Spring 2024',
    credits: 3,
    description: 'An introduction to machine learning algorithms, their mathematical foundations, and practical applications. Students will learn supervised and unsupervised learning techniques, neural networks, and model evaluation methods.',
    instructor: 'Dr. Sarah Smith',
    assistants: ['John Doe (TA)', 'Jane Smith (RA)'],
    schedule: {
        lectures: 'MWF 10:00-11:00 AM',
        labs: 'Tuesday 2:00-4:00 PM',
        classroom: 'CSE Lab 1',
        office_hours: 'Thursday 3:00-5:00 PM'
    },
    enrollment: {
        current: 85,
        maximum: 90,
        waitlist: 12
    },
    syllabus: '/files/cse301-syllabus.pdf',
    grading: {
        assignments: 40,
        midterm: 25,
        final: 25,
        participation: 10
    },
    announcements: [
        {
            id: 1,
            title: 'Midterm Exam Schedule',
            content: 'The midterm exam will be held on March 15th, 2024 from 10:00 AM - 12:00 PM in the main auditorium.',
            date: '2024-01-15T10:00:00',
            priority: 'high',
            pinned: true
        },
        {
            id: 2,
            title: 'Assignment 3 Posted',
            content: 'Neural Network implementation assignment is now available. Due date: March 20th, 2024.',
            date: '2024-01-14T15:30:00',
            priority: 'medium',
            pinned: false
        }
    ]
});

// Course statistics
const courseStats = ref({
    averageGrade: 82.5,
    attendanceRate: 88.3,
    assignmentSubmissionRate: 94.1,
    forumActivity: 156,
    completionRate: 65,
    satisfactionScore: 4.6
});

// Student roster
const students = ref([
    {
        id: 1,
        name: 'Alice Wang',
        email: 'alice.wang@student.edu',
        studentId: 'CS2021001',
        currentGrade: 95.2,
        attendance: 100,
        assignments: { submitted: 8, total: 8, avgScore: 92.5 },
        lastActivity: '2024-01-15T14:30:00',
        status: 'excellent',
        avatar: 'https://ui-avatars.com/api/?name=Alice+Wang&background=10b981&color=fff'
    },
    {
        id: 2,
        name: 'Bob Johnson',
        email: 'bob.johnson@student.edu',
        studentId: 'CS2021002',
        currentGrade: 78.4,
        attendance: 85,
        assignments: { submitted: 7, total: 8, avgScore: 76.8 },
        lastActivity: '2024-01-14T16:20:00',
        status: 'good',
        avatar: 'https://ui-avatars.com/api/?name=Bob+Johnson&background=3b82f6&color=fff'
    },
    {
        id: 3,
        name: 'Carol Smith',
        email: 'carol.smith@student.edu',
        studentId: 'CS2021003',
        currentGrade: 65.1,
        attendance: 72,
        assignments: { submitted: 6, total: 8, avgScore: 68.2 },
        lastActivity: '2024-01-12T11:45:00',
        status: 'at-risk',
        avatar: 'https://ui-avatars.com/api/?name=Carol+Smith&background=ef4444&color=fff'
    }
]);

// Course materials
const materials = ref([
    {
        id: 1,
        title: 'Week 8: Neural Networks Introduction',
        type: 'lecture',
        week: 8,
        files: [
            { name: 'Lecture Slides', type: 'pdf', size: '2.4 MB', url: '/files/week8-slides.pdf' },
            { name: 'Reading Material', type: 'pdf', size: '1.8 MB', url: '/files/week8-reading.pdf' },
            { name: 'Code Examples', type: 'zip', size: '856 KB', url: '/files/week8-code.zip' }
        ],
        uploaded: '2024-01-15T09:00:00',
        downloads: 78
    },
    {
        id: 2,
        title: 'Assignment 3: Neural Network Implementation',
        type: 'assignment',
        week: 8,
        dueDate: '2024-03-20T23:59:00',
        submissions: 45,
        totalStudents: 85,
        files: [
            { name: 'Assignment Instructions', type: 'pdf', size: '1.2 MB', url: '/files/assignment3.pdf' },
            { name: 'Starter Code', type: 'py', size: '4.3 KB', url: '/files/starter.py' }
        ],
        uploaded: '2024-01-14T10:00:00'
    }
]);

// Weekly schedule
const weeklySchedule = ref([
    {
        week: 8,
        topic: 'Neural Networks & Deep Learning',
        lectures: ['Introduction to Neural Networks', 'Backpropagation Algorithm', 'Deep Learning Fundamentals'],
        assignment: 'Neural Network Implementation',
        reading: 'Chapter 6: Neural Networks',
        completed: true
    },
    {
        week: 9,
        topic: 'Convolutional Neural Networks',
        lectures: ['CNN Architecture', 'Image Recognition', 'Transfer Learning'],
        assignment: 'CNN for Image Classification',
        reading: 'Chapter 7: CNNs',
        completed: false
    }
]);

// Assignments
const assignments = ref([
    {
        id: 1,
        title: 'Linear Regression Implementation',
        type: 'programming',
        dueDate: '2024-02-15T23:59:00',
        status: 'graded',
        submissions: 85,
        avgScore: 87.3,
        maxScore: 100,
        weight: 10
    },
    {
        id: 2,
        title: 'Decision Trees Analysis',
        type: 'report',
        dueDate: '2024-02-28T23:59:00',
        status: 'graded',
        submissions: 82,
        avgScore: 81.7,
        maxScore: 100,
        weight: 15
    },
    {
        id: 3,
        title: 'Neural Network Implementation',
        type: 'programming',
        dueDate: '2024-03-20T23:59:00',
        status: 'active',
        submissions: 45,
        avgScore: null,
        maxScore: 100,
        weight: 15
    }
]);

// Tabs
const tabs = [
    { id: 'overview', label: 'Overview', icon: AcademicCapIcon },
    { id: 'students', label: 'Students', icon: UserGroupIcon },
    { id: 'materials', label: 'Materials', icon: BookOpenIcon },
    { id: 'assignments', label: 'Assignments', icon: DocumentTextIcon },
    { id: 'analytics', label: 'Analytics', icon: ChartBarIcon }
];

// Utility functions
const getGradeColor = (grade) => {
    if (grade >= 90) return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    if (grade >= 80) return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400';
    if (grade >= 70) return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
    return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
};

const getStatusColor = (status) => {
    const colors = {
        excellent: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        good: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
        'at-risk': 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        average: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
    };
    return colors[status] || colors.average;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTimeAgo = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffInDays === 0) return 'Today';
    if (diffInDays === 1) return 'Yesterday';
    return `${diffInDays} days ago`;
};

// Actions
const addAnnouncement = () => {
    alert('Opening announcement composer...');
};

const gradeAssignment = (assignmentId) => {
    alert(`Opening grading interface for assignment ${assignmentId}`);
};

const viewStudent = (studentId) => {
    alert(`Opening student profile for ID: ${studentId}`);
};

const downloadFile = (fileUrl) => {
    alert(`Downloading: ${fileUrl}`);
};

const uploadMaterial = () => {
    alert('Opening file upload interface...');
};
</script>

<template>
    <div>
        <Head :title="`${courseData.code} - ${courseData.name}`" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Course Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="text-white">
                                <div class="flex items-center gap-4 mb-4">
                                    <Link
                                        href="/faculty/dashboard"
                                        class="bg-white/20 backdrop-blur-lg rounded-lg p-2 hover:bg-white/30 transition-all"
                                    >
                                        ← Back
                                    </Link>
                                    <h1 class="text-4xl font-bold">
                                        {{ courseData.code }}
                                    </h1>
                                    <span class="px-3 py-1 bg-white/20 backdrop-blur-lg rounded-full text-sm font-medium">
                                        {{ courseData.credits }} Credits
                                    </span>
                                </div>
                                <h2 class="text-2xl font-medium text-white/90 mb-2">
                                    {{ courseData.name }}
                                </h2>
                                <p class="text-white/80 mb-4">{{ courseData.semester }}</p>
                                <div class="flex flex-wrap gap-4 text-sm">
                                    <span>📅 {{ courseData.schedule.lectures }}</span>
                                    <span>🏛️ {{ courseData.schedule.classroom }}</span>
                                    <span>👥 {{ courseData.enrollment.current }}/{{ courseData.enrollment.maximum }} students</span>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="flex flex-wrap gap-4">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">{{ courseStats.averageGrade }}%</div>
                                    <div class="text-xs text-white/80">Avg Grade</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">{{ courseStats.attendanceRate }}%</div>
                                    <div class="text-xs text-white/80">Attendance</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center min-w-[100px]">
                                    <div class="text-2xl font-bold">{{ courseStats.satisfactionScore }}</div>
                                    <div class="text-xs text-white/80">Rating</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Tab Navigation -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg mb-8 overflow-hidden">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="flex overflow-x-auto">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                                        activeTab === tab.id
                                            ? 'border-blue-600 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
                                    }`"
                                >
                                    <component :is="tab.icon" class="w-4 h-4" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>

                        <div class="p-6">
                            <!-- Overview Tab -->
                            <div v-if="activeTab === 'overview'">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    <!-- Left Column -->
                                    <div class="lg:col-span-2 space-y-6">
                                        <!-- Course Description -->
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Course Description</h3>
                                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {{ courseData.description }}
                                            </p>
                                        </div>

                                        <!-- Announcements -->
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Announcements</h3>
                                                <button
                                                    @click="addAnnouncement"
                                                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                                >
                                                    <PlusIcon class="w-4 h-4" />
                                                    Add Announcement
                                                </button>
                                            </div>

                                            <div class="space-y-4">
                                                <div
                                                    v-for="announcement in courseData.announcements"
                                                    :key="announcement.id"
                                                    :class="`p-4 rounded-xl border-l-4 ${
                                                        announcement.priority === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' :
                                                        announcement.priority === 'medium' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' :
                                                        'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                    }`"
                                                >
                                                    <div class="flex items-start justify-between mb-2">
                                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ announcement.title }}</h4>
                                                        <div class="flex items-center gap-2">
                                                            <span v-if="announcement.pinned" class="text-yellow-600">📌</span>
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ formatDate(announcement.date) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <p class="text-gray-700 dark:text-gray-300">{{ announcement.content }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="space-y-6">
                                        <!-- Quick Actions -->
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h4>
                                            <div class="space-y-3">
                                                <button class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                    <PlusIcon class="w-4 h-4 text-blue-600" />
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Create Assignment</span>
                                                </button>
                                                <button class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                    <DocumentTextIcon class="w-4 h-4 text-green-600" />
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Upload Materials</span>
                                                </button>
                                                <button class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                    <ChartBarIcon class="w-4 h-4 text-purple-600" />
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">View Analytics</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Course Info -->
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Course Information</h4>
                                            <div class="space-y-3 text-sm">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Instructor:</span>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ courseData.instructor }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Credits:</span>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ courseData.credits }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Enrollment:</span>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ courseData.enrollment.current }}/{{ courseData.enrollment.maximum }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Waitlist:</span>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ courseData.enrollment.waitlist }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grading Breakdown -->
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Grading Breakdown</h4>
                                            <div class="space-y-3">
                                                <div v-for="(percentage, component) in courseData.grading" :key="component" class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ component }}:</span>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                            <div
                                                                class="bg-blue-600 h-2 rounded-full"
                                                                :style="{ width: percentage + '%' }"
                                                            ></div>
                                                        </div>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ percentage }}%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Students Tab -->
                            <div v-if="activeTab === 'students'">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Student Roster</h3>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grade</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Attendance</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assignments</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr
                                                    v-for="student in students"
                                                    :key="student.id"
                                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                                >
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <img :src="student.avatar" :alt="student.name" class="w-10 h-10 rounded-full mr-4" />
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.name }}</div>
                                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ student.studentId }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :class="`text-sm font-semibold ${student.currentGrade >= 90 ? 'text-green-600 dark:text-green-400' : student.currentGrade >= 80 ? 'text-blue-600 dark:text-blue-400' : student.currentGrade >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'}`">
                                                            {{ student.currentGrade.toFixed(1) }}%
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :class="`text-sm ${student.attendance >= 90 ? 'text-green-600 dark:text-green-400' : student.attendance >= 80 ? 'text-blue-600 dark:text-blue-400' : student.attendance >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'}`">
                                                            {{ student.attendance }}%
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {{ student.assignments.submitted }}/{{ student.assignments.total }}
                                                        <span class="text-gray-500 dark:text-gray-400">({{ student.assignments.avgScore }}%)</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(student.status)}`">
                                                            {{ student.status }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex items-center gap-2">
                                                            <button
                                                                @click="viewStudent(student.id)"
                                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                            >
                                                                <EyeIcon class="w-4 h-4" />
                                                            </button>
                                                            <button
                                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                            >
                                                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Other tabs content would follow similar patterns... -->
                            <div v-if="activeTab === 'materials'">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Course Materials</h3>
                                    <button
                                        @click="uploadMaterial"
                                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        <PlusIcon class="w-4 h-4" />
                                        Upload Material
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="material in materials"
                                        :key="material.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-xl p-6"
                                    >
                                        <div class="flex items-start justify-between mb-4">
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ material.title }}</h4>
                                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    <span>Week {{ material.week }}</span>
                                                    <span>{{ getTimeAgo(material.uploaded) }}</span>
                                                    <span v-if="material.downloads">{{ material.downloads }} downloads</span>
                                                </div>
                                            </div>
                                            <span :class="`px-2 py-1 text-xs font-medium rounded-full ${material.type === 'lecture' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'}`">
                                                {{ material.type }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <div
                                                v-for="file in material.files"
                                                :key="file.name"
                                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <DocumentTextIcon class="w-5 h-5 text-gray-400" />
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ file.name }}</div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ file.size }}</div>
                                                    </div>
                                                </div>
                                                <button
                                                    @click="downloadFile(file.url)"
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'assignments'">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Assignments</h3>
                                    <button class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <PlusIcon class="w-4 h-4" />
                                        Create Assignment
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="assignment in assignments"
                                        :key="assignment.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-xl p-6"
                                    >
                                        <div class="flex items-start justify-between mb-4">
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ assignment.title }}</h4>
                                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    <span>Due: {{ formatDate(assignment.dueDate) }}</span>
                                                    <span>Weight: {{ assignment.weight }}%</span>
                                                    <span>{{ assignment.submissions }} submissions</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span :class="`px-2 py-1 text-xs font-medium rounded-full ${assignment.status === 'graded' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : assignment.status === 'active' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'}`">
                                                    {{ assignment.status }}
                                                </span>
                                                <button
                                                    @click="gradeAssignment(assignment.id)"
                                                    class="px-3 py-1 bg-purple-600 text-white text-xs rounded-lg hover:bg-purple-700 transition-colors"
                                                >
                                                    Grade
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="assignment.avgScore" class="flex items-center gap-6">
                                            <div class="text-center">
                                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ assignment.avgScore }}%</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Average Score</div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div
                                                        class="bg-blue-600 h-2 rounded-full"
                                                        :style="{ width: assignment.avgScore + '%' }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'analytics'">
                                <div class="text-center py-16">
                                    <ChartBarIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Course Analytics</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Detailed performance metrics and insights coming soon...</p>
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
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>