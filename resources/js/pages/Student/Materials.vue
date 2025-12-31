<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    FolderIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';

// Component state
const selectedSemester = ref(5);
const searchQuery = ref('');
const selectedMaterialType = ref('all');
const selectedWeek = ref('all');
const viewMode = ref('grid'); // grid or list
const showFilters = ref(false);

// Student context
const studentContext = ref({
    name: 'John Doe',
    department: 'Computer Science Engineering',
    semester: '5th Semester',
    year: '3rd Year'
});

// Mock data for current student's enrolled courses
const enrolledCourses = ref([
    {
        semester: 5,
        title: 'Semester 5 - Current',
        courses: [
            {
                id: 1,
                code: 'CSE501',
                name: 'Machine Learning Fundamentals',
                instructor: 'Dr. Sarah Smith',
                totalMaterials: 48,
                completedMaterials: 36,
                lastAccessed: '2024-01-15T14:30:00',
                credits: 4
            },
            {
                id: 2,
                code: 'CSE502',
                name: 'Database Systems Advanced',
                instructor: 'Dr. Michael Chen',
                totalMaterials: 35,
                completedMaterials: 28,
                lastAccessed: '2024-01-14T16:20:00',
                credits: 4
            },
            {
                id: 3,
                code: 'CSE503',
                name: 'Computer Networks Security',
                instructor: 'Dr. Emily Johnson',
                totalMaterials: 42,
                completedMaterials: 25,
                lastAccessed: '2024-01-13T11:45:00',
                credits: 3
            },
            {
                id: 4,
                code: 'CSE504',
                name: 'Software Engineering Project',
                instructor: 'Prof. Robert Wilson',
                totalMaterials: 28,
                completedMaterials: 18,
                lastAccessed: '2024-01-12T09:30:00',
                credits: 3
            }
        ]
    },
    {
        semester: 4,
        title: 'Semester 4 - Completed',
        courses: [
            {
                id: 5,
                code: 'CSE401',
                name: 'Design & Analysis of Algorithms',
                instructor: 'Dr. James Brown',
                totalMaterials: 52,
                completedMaterials: 52,
                lastAccessed: '2023-12-15T10:30:00',
                credits: 4
            }
        ]
    }
]);

// Materials for selected course
const courseMaterials = ref({
    1: { // CSE501 - Machine Learning
        courseInfo: {
            code: 'CSE501',
            name: 'Machine Learning Fundamentals',
            instructor: 'Dr. Sarah Smith',
            semester: 'Spring 2024',
            credits: 4,
            description: 'Comprehensive introduction to machine learning algorithms, statistical learning theory, and practical applications.'
        },
        weeks: [
            {
                weekNumber: 1,
                title: 'Introduction to Machine Learning',
                startDate: '2024-01-08',
                status: 'completed',
                materials: [
                    {
                        id: 1,
                        title: 'Course Introduction & Overview',
                        type: 'lecture',
                        format: 'video',
                        duration: '45 min',
                        size: '120 MB',
                        uploadDate: '2024-01-08T09:00:00',
                        downloadCount: 156,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse501/week1/intro.mp4',
                        description: 'Course introduction, objectives, and ML landscape overview'
                    },
                    {
                        id: 2,
                        title: 'ML Fundamentals Reading',
                        type: 'reading',
                        format: 'pdf',
                        pages: 25,
                        size: '2.4 MB',
                        uploadDate: '2024-01-08T10:00:00',
                        downloadCount: 134,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse501/week1/fundamentals.pdf',
                        description: 'Essential concepts and mathematical foundations'
                    },
                    {
                        id: 3,
                        title: 'Lab 1: Python Environment Setup',
                        type: 'lab',
                        format: 'zip',
                        size: '856 KB',
                        uploadDate: '2024-01-09T14:00:00',
                        downloadCount: 128,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse501/week1/lab1.zip',
                        description: 'Hands-on lab for setting up development environment'
                    },
                    {
                        id: 4,
                        title: 'Assignment 1: ML Concepts Quiz',
                        type: 'assignment',
                        format: 'pdf',
                        size: '450 KB',
                        uploadDate: '2024-01-09T16:00:00',
                        downloadCount: 98,
                        viewed: true,
                        locked: false,
                        dueDate: '2024-01-20T23:59:00',
                        submitted: true,
                        grade: 'A-',
                        url: '/materials/cse501/week1/assignment1.pdf',
                        description: 'Conceptual questions on ML fundamentals'
                    }
                ]
            },
            {
                weekNumber: 2,
                title: 'Supervised Learning Basics',
                startDate: '2024-01-15',
                status: 'completed',
                materials: [
                    {
                        id: 5,
                        title: 'Linear Regression Deep Dive',
                        type: 'lecture',
                        format: 'video',
                        duration: '50 min',
                        size: '135 MB',
                        uploadDate: '2024-01-15T09:00:00',
                        downloadCount: 98,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse501/week2/linear-regression.mp4',
                        description: 'Mathematical foundations and implementation of linear regression'
                    },
                    {
                        id: 6,
                        title: 'Assignment 2: Linear Regression Implementation',
                        type: 'assignment',
                        format: 'pdf',
                        size: '800 KB',
                        uploadDate: '2024-01-16T10:00:00',
                        downloadCount: 67,
                        viewed: true,
                        locked: false,
                        dueDate: '2024-01-28T23:59:00',
                        submitted: true,
                        grade: 'A',
                        url: '/materials/cse501/week2/assignment2.pdf',
                        description: 'Implement linear regression from scratch using NumPy'
                    }
                ]
            },
            {
                weekNumber: 3,
                title: 'Classification Algorithms',
                startDate: '2024-01-22',
                status: 'in-progress',
                materials: [
                    {
                        id: 7,
                        title: 'Logistic Regression & Decision Trees',
                        type: 'lecture',
                        format: 'video',
                        duration: '55 min',
                        size: '145 MB',
                        uploadDate: '2024-01-22T09:00:00',
                        downloadCount: 45,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse501/week3/classification.mp4',
                        description: 'Binary and multiclass classification techniques'
                    },
                    {
                        id: 8,
                        title: 'Classification Lab Exercise',
                        type: 'lab',
                        format: 'zip',
                        size: '1.2 MB',
                        uploadDate: '2024-01-23T14:00:00',
                        downloadCount: 23,
                        viewed: false,
                        locked: false,
                        url: '/materials/cse501/week3/classification-lab.zip',
                        description: 'Hands-on practice with scikit-learn classification'
                    },
                    {
                        id: 9,
                        title: 'Assignment 3: Classification Project',
                        type: 'assignment',
                        format: 'pdf',
                        size: '650 KB',
                        uploadDate: '2024-01-24T11:00:00',
                        downloadCount: 18,
                        viewed: false,
                        locked: false,
                        dueDate: '2024-02-05T23:59:00',
                        submitted: false,
                        url: '/materials/cse501/week3/assignment3.pdf',
                        description: 'Build and evaluate classification models on real dataset'
                    }
                ]
            },
            {
                weekNumber: 4,
                title: 'Neural Networks Introduction',
                startDate: '2024-01-29',
                status: 'upcoming',
                materials: [
                    {
                        id: 10,
                        title: 'Neural Network Fundamentals',
                        type: 'lecture',
                        format: 'video',
                        duration: '60 min',
                        size: '165 MB',
                        uploadDate: '2024-01-29T09:00:00',
                        downloadCount: 0,
                        viewed: false,
                        locked: true,
                        url: '/materials/cse501/week4/neural-networks.mp4',
                        description: 'Introduction to artificial neural networks and backpropagation'
                    }
                ]
            }
        ]
    },
    2: { // CSE502 - Database Systems
        courseInfo: {
            code: 'CSE502',
            name: 'Database Systems Advanced',
            instructor: 'Dr. Michael Chen',
            semester: 'Spring 2024',
            credits: 4,
            description: 'Advanced database concepts including normalization, query optimization, and distributed systems.'
        },
        weeks: [
            {
                weekNumber: 1,
                title: 'Database Design & Normalization',
                startDate: '2024-01-08',
                status: 'completed',
                materials: [
                    {
                        id: 11,
                        title: 'Normalization Theory',
                        type: 'lecture',
                        format: 'video',
                        duration: '50 min',
                        size: '128 MB',
                        uploadDate: '2024-01-08T11:00:00',
                        downloadCount: 89,
                        viewed: true,
                        locked: false,
                        url: '/materials/cse502/week1/normalization.mp4'
                    }
                ]
            }
        ]
    }
});

const selectedCourseId = ref(1);

// Material type options
const materialTypes = ref([
    { value: 'all', label: 'All Materials', icon: DocumentTextIcon, count: 48 },
    { value: 'lecture', label: 'Lectures', icon: PresentationChartLineIcon, count: 16 },
    { value: 'assignment', label: 'Assignments', icon: AcademicCapIcon, count: 8 },
    { value: 'reading', label: 'Readings', icon: BookOpenIcon, count: 12 },
    { value: 'lab', label: 'Lab Work', icon: BeakerIcon, count: 12 }
]);

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

const getMaterialColor = (type) => {
    const colors = {
        lecture: 'blue',
        assignment: 'green',
        reading: 'purple',
        lab: 'orange',
        exam: 'red'
    };
    return colors[type] || 'gray';
};

const getWeekStatusColor = (status) => {
    switch (status) {
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'in-progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'upcoming': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'locked': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
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

// Actions
const downloadMaterial = (material) => {
    material.downloadCount = (material.downloadCount || 0) + 1;

    if (!material.viewed) {
        material.viewed = true;
        if (currentCourse.value) {
            currentCourse.value.completedMaterials++;
        }
    }

    alert(`Downloading: ${material.title}\nSize: ${material.size}`);
};

const viewMaterial = (material) => {
    if (!material.viewed) {
        material.viewed = true;
        if (currentCourse.value) {
            currentCourse.value.completedMaterials++;
        }
    }

    if (material.format === 'video') {
        alert(`Opening video player for: ${material.title}\nDuration: ${material.duration}`);
    } else {
        alert(`Opening ${material.format.toUpperCase()} viewer for: ${material.title}`);
    }
};

const askAIAboutMaterial = (material) => {
    const query = `Explain the concepts in ${material.title}`;
    window.open(`/chat?q=${encodeURIComponent(query)}&material=${material.id}`, '_blank');
};

const submitAssignment = (material) => {
    alert(`Opening assignment submission for: ${material.title}`);
};

const selectCourse = (courseId) => {
    selectedCourseId.value = courseId;
    selectedWeek.value = 'all';
    selectedMaterialType.value = 'all';
    searchQuery.value = '';
};

const downloadWeekMaterials = (week) => {
    alert(`Downloading all materials for Week ${week.weekNumber}: ${week.title}`);
};

onMounted(() => {
    // Load initial data or perform setup
});
</script>

<template>
    <div>
        <Head title="Course Materials" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="text-white">
                                <h1 class="text-4xl font-bold mb-2 flex items-center gap-3">
                                    <BookOpenIcon class="w-10 h-10" />
                                    Course Materials
                                </h1>
                                <p class="text-white/90 text-xl mb-2">
                                    Access all your course materials organized by semester and week
                                </p>
                                <p class="text-white/80">
                                    {{ studentContext.department }} • {{ studentContext.year }} • {{ studentContext.semester }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Quick Stats -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-4 text-white text-center">
                                    <div class="text-3xl font-bold">{{ currentCourse?.totalMaterials || 0 }}</div>
                                    <div class="text-xs text-white/80">Total Materials</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-4 text-white text-center">
                                    <div class="text-3xl font-bold">{{ currentCourse?.completedMaterials || 0 }}</div>
                                    <div class="text-xs text-white/80">Completed</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-4 text-white text-center">
                                    <div class="text-3xl font-bold">{{ overallProgress }}%</div>
                                    <div class="text-xs text-white/80">Progress</div>
                                </div>

                                <Link
                                    href="/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <!-- Left Sidebar - Course Selection & Filters -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-8">
                                <!-- Current Semester Courses -->
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <FolderIcon class="w-5 h-5 text-blue-600" />
                                        Current Semester
                                    </h3>
                                    <div class="space-y-3">
                                        <div
                                            v-for="course in enrolledCourses[0]?.courses || []"
                                            :key="course.id"
                                            @click="selectCourse(course.id)"
                                            class="cursor-pointer"
                                        >
                                            <button
                                                :class="`w-full text-left p-4 rounded-xl border-2 transition-all transform hover:scale-105 ${
                                                    selectedCourseId === course.id
                                                        ? 'border-blue-300 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 dark:border-blue-700 shadow-lg'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md'
                                                }`"
                                            >
                                                <div class="flex items-center gap-3 mb-2">
                                                    <div :class="`w-3 h-3 rounded-full ${selectedCourseId === course.id ? 'bg-blue-500' : 'bg-gray-300'}`"></div>
                                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">
                                                        {{ course.code }}
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                                    {{ course.name }}
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <div class="text-xs text-gray-500">
                                                        {{ course.completedMaterials }}/{{ course.totalMaterials }}
                                                    </div>
                                                    <div class="flex-1 ml-2 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                        <div
                                                            class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full transition-all duration-500"
                                                            :style="{ width: (course.completedMaterials / course.totalMaterials * 100) + '%' }"
                                                        ></div>
                                                    </div>
                                                    <span class="ml-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        {{ Math.round((course.completedMaterials / course.totalMaterials) * 100) }}%
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Material Type Filter -->
                                <div class="mb-6">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">Material Types</h4>
                                    <div class="space-y-2">
                                        <button
                                            v-for="type in materialTypes"
                                            :key="type.value"
                                            @click="selectedMaterialType = type.value"
                                            :class="`w-full flex items-center justify-between p-3 rounded-xl text-sm transition-all transform hover:scale-105 ${
                                                selectedMaterialType === type.value
                                                    ? 'bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 dark:from-blue-900/30 dark:to-indigo-900/30 dark:text-blue-400 border-2 border-blue-300 dark:border-blue-600'
                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border-2 border-transparent'
                                            }`"
                                        >
                                            <div class="flex items-center gap-3">
                                                <component :is="type.icon" class="w-5 h-5" />
                                                <span class="font-medium">{{ type.label }}</span>
                                            </div>
                                            <span class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded-full text-xs font-medium">
                                                {{ type.count }}
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Week Filter -->
                                <div class="mb-6">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">Week Filter</h4>
                                    <select
                                        v-model="selectedWeek"
                                        class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option
                                            v-for="week in weekOptions"
                                            :key="week.value"
                                            :value="week.value"
                                        >
                                            {{ week.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Quick Actions -->
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">Quick Actions</h4>
                                    <div class="space-y-2">
                                        <button class="w-full flex items-center gap-3 p-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-xl hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors text-sm">
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                            Download All Materials
                                        </button>
                                        <button
                                            @click="askAIAboutMaterial({ title: 'course concepts' })"
                                            class="w-full flex items-center gap-3 p-3 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors text-sm"
                                        >
                                            <SparklesIcon class="w-4 h-4" />
                                            AI Study Assistant
                                        </button>
                                        <Link
                                            href="/roadmap"
                                            class="w-full flex items-center gap-3 p-3 bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 rounded-xl hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors text-sm"
                                        >
                                            <ChartBarIcon class="w-4 h-4" />
                                            View Roadmap
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="lg:col-span-3">
                            <!-- Search and Controls -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <!-- Search -->
                                    <div class="flex-1 relative">
                                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search materials by title or description..."
                                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>

                                    <!-- View Mode Toggle -->
                                    <div class="flex gap-2">
                                        <button
                                            @click="viewMode = 'grid'"
                                            :class="`px-4 py-3 rounded-xl transition-colors ${
                                                viewMode === 'grid'
                                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                            }`"
                                        >
                                            <Squares2X2Icon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="viewMode = 'list'"
                                            :class="`px-4 py-3 rounded-xl transition-colors ${
                                                viewMode === 'list'
                                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                            }`"
                                        >
                                            <ListBulletIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Info -->
                            <div v-if="currentMaterials.courseInfo" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                            {{ currentMaterials.courseInfo.code }} - {{ currentMaterials.courseInfo.name }}
                                        </h2>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">
                                            {{ currentMaterials.courseInfo.instructor }} • {{ currentMaterials.courseInfo.semester }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ currentMaterials.courseInfo.description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full text-sm font-medium">
                                            {{ currentMaterials.courseInfo.credits }} Credits
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly Progress Overview -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    📊 Weekly Progress Overview
                                </h3>

                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                                    <div
                                        v-for="week in currentMaterials.weeks?.slice(0, 8)"
                                        :key="week.weekNumber"
                                        class="text-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all"
                                    >
                                        <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">
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
                                                    class="text-gray-200 dark:text-gray-700"
                                                />
                                                <circle
                                                    cx="24" cy="24" r="20"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                    stroke-linecap="round"
                                                    :stroke-dasharray="`${2 * Math.PI * 20}`"
                                                    :stroke-dashoffset="`${2 * Math.PI * 20 * (1 - (week.materials.filter(m => m.viewed).length / week.materials.length))}`"
                                                    :class="week.status === 'completed' ? 'text-green-500' : week.status === 'in-progress' ? 'text-blue-500' : 'text-gray-400'"
                                                    class="transition-all duration-500"
                                                />
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">
                                                    {{ Math.round((week.materials.filter(m => m.viewed).length / week.materials.length) * 100) }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div :class="`text-xs px-2 py-1 rounded-full ${getWeekStatusColor(week.status)}`">
                                            {{ week.status }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upcoming Assignments -->
                            <div v-if="upcomingAssignments.length > 0" class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-2xl shadow-lg p-6 mb-8 border border-orange-200 dark:border-orange-800">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    ⏰ Upcoming Assignments
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div
                                        v-for="assignment in upcomingAssignments"
                                        :key="assignment.id"
                                        class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm"
                                    >
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
                                            {{ assignment.title }}
                                        </h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            Week {{ assignment.weekNumber }}
                                        </p>
                                        <div class="flex items-center justify-between">
                                            <span :class="`text-xs font-medium ${
                                                getDaysUntil(assignment.dueDate) <= 3
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : 'text-orange-600 dark:text-orange-400'
                                            }`">
                                                Due in {{ getDaysUntil(assignment.dueDate) }} days
                                            </span>
                                            <button
                                                @click="viewMaterial(assignment)"
                                                class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition-colors"
                                            >
                                                View
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Materials by Week -->
                            <div class="space-y-8">
                                <div
                                    v-for="week in filteredWeeks"
                                    :key="week.weekNumber"
                                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden"
                                >
                                    <!-- Week Header -->
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                                    Week {{ week.weekNumber }}: {{ week.title }}
                                                </h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Started {{ formatDate(week.startDate) }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span :class="`px-3 py-1 rounded-full text-xs font-medium ${getWeekStatusColor(week.status)}`">
                                                    {{ week.status.charAt(0).toUpperCase() + week.status.slice(1) }}
                                                </span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ week.materials.length }} materials
                                                </span>
                                                <button
                                                    @click="downloadWeekMaterials(week)"
                                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                    title="Download all week materials"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Materials Grid/List -->
                                    <div class="p-6">
                                        <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-3'">
                                            <div
                                                v-for="material in week.materials.filter(m => selectedMaterialType === 'all' || m.type === selectedMaterialType)"
                                                :key="material.id"
                                                :class="`border border-gray-200 dark:border-gray-700 rounded-xl p-4 transition-all duration-300 ${
                                                    material.locked
                                                        ? 'opacity-50 cursor-not-allowed'
                                                        : 'hover:shadow-lg hover:border-blue-300 dark:hover:border-blue-700 cursor-pointer'
                                                } ${
                                                    material.viewed ? 'bg-gray-50 dark:bg-gray-900/50' : 'bg-white dark:bg-gray-800'
                                                }`"
                                                @click="!material.locked && viewMaterial(material)"
                                            >
                                                <div class="flex items-start gap-4">
                                                    <!-- Material Icon -->
                                                    <div :class="`p-3 rounded-xl ${
                                                        material.locked
                                                            ? 'bg-gray-100 dark:bg-gray-800'
                                                            : `bg-${getMaterialColor(material.type)}-100 dark:bg-${getMaterialColor(material.type)}-900/30`
                                                    }`">
                                                        <component
                                                            :is="getMaterialIcon(material.type, material.format)"
                                                            :class="`w-6 h-6 ${
                                                                material.locked
                                                                    ? 'text-gray-400'
                                                                    : `text-${getMaterialColor(material.type)}-600 dark:text-${getMaterialColor(material.type)}-400`
                                                            }`"
                                                        />
                                                    </div>

                                                    <!-- Material Info -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                                                                {{ material.title }}
                                                            </h4>
                                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                                <CheckCircleIconSolid
                                                                    v-if="material.viewed && !material.locked"
                                                                    class="w-5 h-5 text-green-500"
                                                                />
                                                                <span v-if="material.locked" class="text-xs text-gray-400">🔒</span>
                                                                <span v-if="material.grade" :class="`text-xs font-bold px-2 py-1 rounded-full ${
                                                                    material.grade.includes('A') ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                                                    material.grade.includes('B') ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                                }`">
                                                                    {{ material.grade }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <p v-if="material.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                                            {{ material.description }}
                                                        </p>

                                                        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                                            <span class="capitalize font-medium">{{ material.type }}</span>
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
                                                                        ? 'text-red-600 dark:text-red-400'
                                                                        : 'text-blue-600 dark:text-blue-400'
                                                                }`">
                                                                    Due: {{ formatDate(material.dueDate) }}
                                                                </span>
                                                                <span v-if="material.submitted" class="text-green-600 dark:text-green-400 font-medium">
                                                                    ✓ Submitted
                                                                </span>
                                                                <span v-else-if="material.dueDate && !material.locked" class="text-orange-600 dark:text-orange-400 font-medium">
                                                                    Not Submitted
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div v-if="!material.locked" class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                                    <!-- Primary Action Button -->
                                                    <button
                                                        @click.stop="viewMaterial(material)"
                                                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                                                    >
                                                        <component :is="material.format === 'video' ? PlayIcon : EyeIcon" class="w-4 h-4" />
                                                        {{ material.format === 'video' ? 'Play Video' : 'Open' }}
                                                    </button>

                                                    <!-- Download Button -->
                                                    <button
                                                        @click.stop="downloadMaterial(material)"
                                                        class="px-3 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                        title="Download"
                                                    >
                                                        <ArrowDownTrayIcon class="w-4 h-4" />
                                                    </button>

                                                    <!-- AI Assistant Button -->
                                                    <button
                                                        @click.stop="askAIAboutMaterial(material)"
                                                        class="px-3 py-2 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
                                                        title="Ask AI about this material"
                                                    >
                                                        <SparklesIcon class="w-4 h-4" />
                                                    </button>

                                                    <!-- Assignment Submission (if assignment) -->
                                                    <button
                                                        v-if="material.type === 'assignment' && !material.submitted"
                                                        @click.stop="submitAssignment(material)"
                                                        class="px-3 py-2 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors text-sm font-medium"
                                                    >
                                                        Submit
                                                    </button>
                                                </div>

                                                <!-- Locked Material Message -->
                                                <div v-if="material.locked" class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        🔒 <span>This material will be available after completing previous weeks</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State for Week -->
                                <div v-if="filteredWeeks.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                                    <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        No materials found
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        Try adjusting your search criteria or filters to find the materials you're looking for.
                                    </p>
                                    <button
                                        @click="searchQuery = ''; selectedMaterialType = 'all'; selectedWeek = 'all'"
                                        class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
                                    >
                                        Clear Filters
                                    </button>
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

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover effects */
.hover\:scale-105:hover {
    transform: scale(1.02);
}

/* Progress circle animation */
circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}

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

/* Mobile optimizations */
@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .lg\:col-span-1,
    .lg\:col-span-3 {
        grid-column: span 1;
    }

    .text-4xl {
        font-size: 2rem;
    }
}

/* Enhanced hover animations */
@keyframes pulse-gentle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.9; }
}

.animate-pulse-gentle {
    animation: pulse-gentle 2s ease-in-out infinite;
}

/* Button hover effects */
button:hover {
    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
}
</style>