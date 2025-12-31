<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    ChatBubbleLeftRightIcon,
    BookOpenIcon,
    ChartBarIcon,
    CalendarIcon,
    UserIcon,
    SparklesIcon,
    BookmarkIcon,
    ClockIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

// Student data
const student = ref({
    name: 'John Doe',
    studentId: 'CS2021001',
    department: 'Computer Science Engineering',
    year: '3rd Year',
    semester: '5th Semester',
    program: 'B.Tech',
    avatar: 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff'
});

// Quick actions - FIXED LINKS
const quickActions = ref([
    {
        label: 'Start New Chat',
        icon: ChatBubbleLeftRightIcon,
        href: '/chat',
        gradient: 'from-indigo-500 to-purple-600',
        description: 'Ask anything academic'
    },
    {
        label: 'View Roadmap',
        icon: ChartBarIcon,
        href: '/roadmap',
        gradient: 'from-cyan-500 to-blue-600',
        description: 'Your learning path'
    },
    {
        label: 'Saved Answers',
        icon: BookmarkIcon,
        href: '/saved',  // FIXED: Now points to our SavedAnswers page
        gradient: 'from-pink-500 to-rose-600',
        description: 'Bookmarked content'
    },
    {
        label: 'Documents',
        icon: DocumentTextIcon,
        href: '/documents',
        gradient: 'from-green-500 to-emerald-600',
        description: 'Course materials'
    }
]);
</script>

<template>
    <div>
        <Head title="Student Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                            <!-- Profile Info -->
                            <div class="flex items-center gap-6">
                                <div class="relative">
                                    <img
                                        :src="student.avatar"
                                        alt="Profile"
                                        class="w-24 h-24 rounded-2xl shadow-2xl ring-4 ring-white/50 dark:ring-white/20"
                                    />
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full ring-4 ring-white dark:ring-gray-800 flex items-center justify-center">
                                        <SparklesIcon class="w-5 h-5 text-white" />
                                    </div>
                                </div>
                                <div class="text-white">
                                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                                        Welcome back, {{ student.name }}! 👋
                                    </h1>
                                    <p class="text-white/90 text-lg">
                                        {{ student.studentId }} • {{ student.department }}
                                    </p>
                                    <p class="text-white/80 text-sm mt-1">
                                        {{ student.year }} • {{ student.semester }} • {{ student.program }}
                                    </p>
                                </div>
                            </div>

                            <!-- Quick Stats Badge -->
                            <div class="flex flex-wrap gap-3 ml-auto">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                    <div class="text-2xl font-bold">87%</div>
                                    <div class="text-xs text-white/80">Attendance</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                    <div class="text-2xl font-bold">4.2</div>
                                    <div class="text-xs text-white/80">GPA</div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                    <div class="text-2xl font-bold">7</div>
                                    <div class="text-xs text-white/80">Day Streak</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Quick Actions Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="group relative bg-gradient-to-br p-[2px] rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                            :class="action.gradient"
                        >
                            <div class="bg-white dark:bg-gray-800 rounded-[10px] p-6 h-full">
                                <component :is="action.icon" :class="`w-8 h-8 mb-3 bg-gradient-to-br ${action.gradient} bg-clip-text text-transparent`" />
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ action.label }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ action.description }}
                                </p>
                            </div>
                        </Link>
                    </div>

                    <!-- Welcome Message -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <AcademicCapIcon class="w-8 h-8 text-white" />
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Welcome to Your Learning Journey!
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
                            UniGPT is here to help you excel in your studies. Ask questions, explore course materials,
                            and get personalized learning assistance powered by advanced AI.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-4">
                            <Link
                                href="/chat"
                                class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-colors font-medium"
                            >
                                Start Chatting
                            </Link>
                            <Link
                                href="/saved"
                                class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
                            >
                                View Saved Answers
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>