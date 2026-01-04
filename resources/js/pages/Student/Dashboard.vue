<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import {
    AcademicCapIcon,
    BookmarkIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    CalendarIcon,
    ChartBarIcon,
    BellIcon,
    SparklesIcon,
    DocumentTextIcon,
    ArrowRightIcon,
    FireIcon,
    UserIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
    permissions: Array,
    student: Object,
    stats: Array,
    recentActivities: Array,
    upcomingDeadlines: Array,
    quickActions: Array
});

const iconMap = {
    AcademicCapIcon,
    ChartBarIcon,
    DocumentTextIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    BookmarkIcon,
    SparklesIcon,
    FireIcon,
    CalendarIcon
};


const recentChats = ref([
    {
        id: 1,
        question: 'Explain the attendance policy for CSE 3rd year',
        time: '2 hours ago',
        mode: 'Academic',
        confidence: 95,
        saved: true
    },
    {
        id: 2,
        question: 'What are the exam eligibility criteria?',
        time: '5 hours ago',
        mode: 'Policy',
        confidence: 98,
        saved: false
    },
    {
        id: 3,
        question: 'Explain DBMS normalization with examples',
        time: '1 day ago',
        mode: 'Exam Prep',
        confidence: 92,
        saved: true
    }
]);



// Add this after your existing ref declarations
const materialsStats = ref({
    total: 48,
    viewed: 36,
    pending: 12,
    recentlyAdded: 5
});

const recentMaterials = ref([
    {
        id: 1,
        title: 'Neural Networks Lecture',
        course: 'Machine Learning',
        week: 'Week 8',
        type: 'lecture',
        addedDate: '2024-01-15T10:00:00'
    },
    {
        id: 2,
        title: 'Database Assignment 3',
        course: 'Database Systems',
        week: 'Week 7',
        type: 'assignment',
        addedDate: '2024-01-14T15:30:00'
    },
    {
        id: 3,
        title: 'Network Security Reading',
        course: 'Computer Networks',
        week: 'Week 6',
        type: 'reading',
        addedDate: '2024-01-13T09:00:00'
    }
]);
const navigateToMaterials = () => {
    router.visit('/student/materials');
};
const roadmapPreview = ref({
    currentTopic: 'Database Management Systems - Normalization',
    progress: 67,
    nextTopic: 'Transaction Management',
    completedTopics: 12,
    totalTopics: 18
});

const getConfidenceColor = (confidence) => {
    if (confidence >= 90) return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    if (confidence >= 75) return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400';
    return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
};

const getDeadlineColor = (daysLeft) => {
    if (daysLeft <= 3) return 'border-red-500 bg-red-50 dark:bg-red-900/20';
    if (daysLeft <= 7) return 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20';
    return 'border-green-500 bg-green-50 dark:bg-green-900/20';
};

const navigateToRoute = (routeName) => {
    router.visit(route(routeName));
};

const getPriorityColor = (priority) => {
    const colors = {
        high: 'border-red-500 bg-red-50 dark:bg-red-900/20',
        medium: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
        low: 'border-green-500 bg-green-50 dark:bg-green-900/20'
    };
    return colors[priority] || colors.low;
};

const getStatColor = (color) => {
    const colors = {
        green: 'from-green-500 to-emerald-600',
        blue: 'from-blue-500 to-cyan-600',
        purple: 'from-purple-500 to-indigo-600',
        orange: 'from-orange-500 to-red-600'
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div>
    <Head title="Student Dashboard" />
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
            <!-- Hero Section with Profile -->
            <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
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
  <!-- Action Buttons -->
                <div class="flex items-center gap-3 lg:flex-shrink-0">
                    <!-- Profile Button -->
                    <Link
                        :href="route('profile')"
                        class="flex items-center gap-2 px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-xl transition-all duration-200 hover:scale-105"
                    >
                        <UserIcon class="w-4 h-4" />
                        <span class="text-sm font-medium">Profile</span>
                    </Link>

                    <!-- Settings Button -->
                    <Link
                        :href="route('settings')"
                        class="flex items-center gap-2 px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-xl transition-all duration-200 hover:scale-105"
                    >
                        <Cog6ToothIcon class="w-4 h-4" />
                        <span class="text-sm font-medium">Settings</span>
                    </Link>

                    <!-- Logout Button with Custom Styling -->
                    <LogoutButton class="flex items-center gap-2 px-4 py-2.5 bg-red-500/20 backdrop-blur-sm text-white hover:bg-red-500/30 border border-red-400/30 hover:border-red-400/50 rounded-xl transition-all duration-200 hover:scale-105" />
                </div>
                        <!-- Quick Stats Badge -->
                        <div class="flex flex-wrap gap-3">
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">87%</div>
                                <div class="text-xs text-white/80">Attendance</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                <div class="text-2xl font-bold">7</div>
                                <div class="text-xs text-white/80">Day Streak</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wave decoration -->
                <div class="absolute bottom-0 left-0 right-0">
                    <svg viewBox="0 0 1440 120" class="w-full h-12 fill-gray-50 dark:fill-gray-900">
                        <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,58.7C960,64,1056,64,1152,58.7C1248,53,1344,43,1392,37.3L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
                    </svg>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div
                        v-for="stat in stats"
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
                                <span :class="`text-xs font-semibold px-2 py-1 rounded-full ${stat.trend === 'up' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700'}`">
                                    {{ stat.change }}
                                </span>
                            </div>

                            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                {{ stat.value }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ stat.label }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column - 2/3 width -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Quick Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                <SparklesIcon class="w-6 h-6 text-indigo-600" />
                                Quick Actions
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        </div>

                        <!-- Recent Chats -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <ChatBubbleLeftRightIcon class="w-6 h-6 text-purple-600" />
                                    Recent Conversations
                                </h2>
                                <Link href="/chat/history" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium">
                                    View All →
                                </Link>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="chat in recentChats"
                                    :key="chat.id"
                                    class="group p-4 rounded-xl border-2 border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-all duration-300 cursor-pointer"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                                    {{ chat.mode }}
                                                </span>
                                                <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getConfidenceColor(chat.confidence)}`">
                                                    {{ chat.confidence }}% Confidence
                                                </span>
                                                <span v-if="chat.saved" class="text-yellow-500">
                                                    <BookmarkIcon class="w-4 h-4 fill-current" />
                                                </span>
                                            </div>
                                            <p class="text-gray-900 dark:text-white font-medium mb-1 line-clamp-2">
                                                {{ chat.question }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ chat.time }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Roadmap Preview -->
                        <div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold flex items-center gap-2">
                                    <ChartBarIcon class="w-6 h-6" />
                                    Your Learning Path
                                </h2>
                                <Link href="/roadmap" class="text-sm text-white/90 hover:text-white font-medium bg-white/20 px-4 py-2 rounded-lg hover:bg-white/30 transition-all">
                                    View Full Roadmap →
                                </Link>
                            </div>

                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium">Current Progress</span>
                                    <span class="text-2xl font-bold">{{ roadmapPreview.progress }}%</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-3 mb-2">
                                    <div
                                        class="bg-white rounded-full h-3 transition-all duration-500 shadow-lg"
                                        :style="{ width: roadmapPreview.progress + '%' }"
                                    ></div>
                                </div>
                                <p class="text-sm text-white/80">
                                    {{ roadmapPreview.completedTopics }} of {{ roadmapPreview.totalTopics }} topics completed
                                </p>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
                                    <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                                        <span class="text-lg">📚</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-white/70">Currently Learning</p>
                                        <p class="font-semibold">{{ roadmapPreview.currentTopic }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
                                    <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                                        <span class="text-lg">🎯</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-white/70">Up Next</p>
                                        <p class="font-semibold">{{ roadmapPreview.nextTopic }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add this card to your existing dashboard grid (around line 580-600) -->
<!-- Course Materials Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 cursor-pointer group"
                    @click="navigateToMaterials">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl group-hover:scale-110 transition-transform">
                        <DocumentTextIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors" />
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Course Materials</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                    Access lecture notes, assignments, and course resources
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>📚 {{ materialsStats.total }} materials</span>
                    <span>✅ {{ materialsStats.viewed }} viewed</span>
                </div>
                <div v-if="materialsStats.pending > 0" class="mt-2">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 rounded-full text-xs font-medium">
                        ⏰ {{ materialsStats.pending }} pending
                    </span>
                </div>
                </div>
                                </div>

                    <!-- Right Column - 1/3 width -->
                    <div class="space-y-8">
                        <!-- Upcoming Deadlines -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <BellIcon class="w-6 h-6 text-red-600" />
                                    Deadlines
                                </h2>
                                <Link href="/deadlines" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium">
                                    View All
                                </Link>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="deadline in upcomingDeadlines"
                                    :key="deadline.id"
                                    :class="`p-4 rounded-xl border-l-4 ${getDeadlineColor(deadline.daysLeft)} transition-all duration-300 hover:shadow-md`"
                                >
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                                            {{ deadline.title }}
                                        </h3>
                                        <span
                                            v-if="deadline.urgent"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 animate-pulse"
                                        >
                                            URGENT
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <CalendarIcon class="w-4 h-4" />
                                        <span>{{ deadline.date }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <ClockIcon class="w-4 h-4 text-gray-500" />
                                        <span class="text-sm font-bold" :class="deadline.daysLeft <= 3 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'">
                                            {{ deadline.daysLeft }} days left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Study Tips Card -->
                        <div class="bg-gradient-to-br from-amber-400 via-orange-500 to-red-500 rounded-2xl shadow-lg p-6 text-white">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    <span class="text-2xl">💡</span>
                                </div>
                                <h3 class="font-bold text-lg">Daily Tip</h3>
                            </div>
                            <p class="text-white/90 text-sm leading-relaxed">
                                Break your study sessions into 25-minute focused intervals with 5-minute breaks. This technique improves concentration and retention!
                            </p>
                            <button class="mt-4 w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg py-2 text-sm font-medium transition-all">
                                Get More Tips
                            </button>
                        </div>

                        <!-- Motivational Quote
                        -->
                        <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                            <div class="text-4xl mb-3">✨</div>
                            <p class="text-lg font-semibold mb-2 italic">
                                "Education is the most powerful weapon which you can use to change the world."
                            </p>
                            <p class="text-sm text-white/80">
                                — Nelson Mandela
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    </div>
</template>

<style scoped>
/* Custom animations */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.group:hover .animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Smooth gradient animation */
@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 15s ease infinite;
}
/* Custom logout button styling */
.logout-wrapper :deep(.flex) {
    @apply !bg-red-500/20 !border-red-400/30 !text-white;
}

.logout-wrapper :deep(.flex:hover) {
    @apply !bg-red-500/30 !border-red-400/50 !text-white;
}

/* Backdrop blur effects */
.bg-white\/20 {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
</style>