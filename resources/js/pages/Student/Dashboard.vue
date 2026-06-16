<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    AcademicCapIcon,
    BookmarkIcon,
    BookOpenIcon,
    ChatBubbleLeftRightIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    CalendarIcon,
    ChartBarIcon,
    BellIcon,
    SparklesIcon,
    DocumentTextIcon,
    ArrowRightIcon,
    FireIcon,
    FlagIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
    permissions: Array,
    student: Object,
    stats: Array,
    recentActivities: Array,
    upcomingDeadlines: Array,
    courses: Array,
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

// Map server `student` (snake_case) into the field names the template uses.
const student = computed(() => {
    const source = props.student || {};
    return {
        ...source,
        studentId: source.student_id,
        department: source.department ?? 'Not assigned',
        year: source.semester ? `Semester ${source.semester}` : '',
        program: source.department ?? '',
        avatar: source.avatar
    };
});

// Semantic StatCard tones, cycled across the KPI row.
const statTones = ['primary', 'success', 'warning', 'danger'];

// Map server `stats` into the shape the template renders.
const stats = computed(() =>
    (props.stats || []).map((stat) => ({
        ...stat,
        icon: iconMap[stat.icon] || ChartBarIcon
    }))
);

// Map server `quickActions` route names into resolved hrefs and icon components.
const quickActions = computed(() =>
    (props.quickActions || []).map((action) => ({
        ...action,
        route: action.route ? route(action.route) : '#',
        icon: iconMap[action.icon] || SparklesIcon
    }))
);

// Map server `upcomingDeadlines` into the field names the template uses.
const upcomingDeadlines = computed(() =>
    (props.upcomingDeadlines || []).map((deadline) => ({
        ...deadline,
        daysLeft: deadline.days_left,
        date: deadline.due_date,
        urgent: deadline.priority === 'high' || deadline.days_left <= 3
    }))
);

// Use server `recentActivities` to power the "Recent Conversations" list.
const recentChats = computed(() =>
    (props.recentActivities || []).map((activity) => ({
        id: activity.id,
        question: activity.title,
        time: activity.time,
        mode: activity.type,
        confidence: null,
        saved: false
    }))
);

// Derive course-materials summary from enrolled `courses`.
const materialsStats = computed(() => {
    const courses = props.courses || [];
    const total = courses.reduce((sum, course) => sum + (course.totalMaterials || 0), 0);
    const viewed = courses.reduce(
        (sum, course) => sum + Math.round(((course.totalMaterials || 0) * (course.progress || 0)) / 100),
        0
    );
    return {
        total,
        viewed,
        pending: total - viewed,
        recentlyAdded: 0
    };
});

const navigateToMaterials = () => {
    router.visit('/student/materials');
};

// Derive the learning-path preview from enrolled `courses`.
const roadmapPreview = computed(() => {
    const courses = props.courses || [];
    const activeCourse = courses.find((course) => course.status === 'in-progress') || courses[0] || {};
    const nextCourse = courses.find((course) => course.status === 'not-started') || {};
    const completedTopics = courses.filter((course) => course.status === 'completed').length;
    const overallProgress = courses.length
        ? Math.round(courses.reduce((sum, course) => sum + (course.progress || 0), 0) / courses.length)
        : 0;
    return {
        currentTopic: activeCourse.name || 'No active course',
        progress: overallProgress,
        nextTopic: nextCourse.name || 'To be announced',
        completedTopics,
        totalTopics: courses.length
    };
});

// Confidence chip tone (semantic).
const getConfidenceColor = (confidence) => {
    if (confidence >= 90) return 'bg-success-bg text-success-fg';
    if (confidence >= 75) return 'bg-primary-soft text-primary';
    return 'bg-warning-bg text-warning-fg';
};

// Deadline card accent: urgent → danger, soon → warning, otherwise success.
const getDeadlineColor = (daysLeft) => {
    if (daysLeft <= 3) return 'border-danger-fg bg-danger-bg';
    if (daysLeft <= 7) return 'border-warning-fg bg-warning-bg';
    return 'border-success-fg bg-success-bg';
};
</script>

<template>
    <div>
        <Head title="Student Dashboard" />
        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <!-- Greeting + page intro -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="student.avatar"
                            :src="student.avatar"
                            alt="Profile"
                            class="h-14 w-14 flex-shrink-0 rounded-card object-cover ring-1 ring-line"
                        />
                        <div class="min-w-0">
                            <h1 class="text-2xl font-bold tracking-tight text-content">
                                Welcome back, {{ student.name }}
                            </h1>
                            <p class="mt-0.5 text-sm text-content-muted">
                                {{ student.studentId }} · {{ student.department }} · {{ student.year }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2.5 rounded-card border border-line bg-surface px-4 py-2.5 shadow-card">
                            <span class="ui-icon-tile h-9 w-9 bg-success-bg text-success-fg">
                                <ClipboardDocumentCheckIcon class="h-5 w-5" />
                            </span>
                            <div class="leading-tight"><div class="text-lg font-bold text-content">87%</div><div class="text-[11px] font-medium text-content-muted">Attendance</div></div>
                        </div>
                        <div class="flex items-center gap-2.5 rounded-card border border-line bg-surface px-4 py-2.5 shadow-card">
                            <span class="ui-icon-tile h-9 w-9 bg-warning-bg text-warning-fg">
                                <FireIcon class="h-5 w-5" />
                            </span>
                            <div class="leading-tight"><div class="text-lg font-bold text-content">7</div><div class="text-[11px] font-medium text-content-muted">Day Streak</div></div>
                        </div>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
                    <StatCard
                        v-for="(stat, i) in stats"
                        :key="stat.label"
                        :label="stat.label"
                        :value="stat.value"
                        :icon="stat.icon"
                        :color="statTones[i % statTones.length]"
                        :change="stat.change"
                        :trend="stat.trend"
                    />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Column -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Quick Actions -->
                        <Card title="Quick Actions" :icon="SparklesIcon">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Link
                                    v-for="action in quickActions"
                                    :key="action.label"
                                    :href="action.route"
                                    class="group flex flex-col rounded-card border border-line bg-bg p-5 transition-shadow duration-200 hover:bg-surface hover:shadow-card-hover"
                                >
                                    <div class="ui-icon-tile mb-3 h-11 w-11 bg-primary-soft text-primary">
                                        <component :is="action.icon" class="h-6 w-6" />
                                    </div>
                                    <h3 class="font-semibold text-content">
                                        {{ action.label }}
                                    </h3>
                                    <p class="mt-1 text-sm text-content-muted">
                                        {{ action.description }}
                                    </p>
                                </Link>
                            </div>
                        </Card>

                        <!-- Recent Chats -->
                        <Card title="Recent Conversations" :icon="ChatBubbleLeftRightIcon">
                            <template #actions>
                                <Link href="/chat/history" class="ui-btn-ghost text-sm">
                                    View All
                                    <ArrowRightIcon class="h-4 w-4" />
                                </Link>
                            </template>

                            <div class="space-y-3">
                                <div
                                    v-for="chat in recentChats"
                                    :key="chat.id"
                                    class="group cursor-pointer rounded-card border border-line bg-bg p-4 transition-shadow duration-200 hover:bg-surface hover:shadow-card-hover"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                                <Badge variant="primary">{{ chat.mode }}</Badge>
                                                <span v-if="chat.confidence != null" class="ui-badge" :class="getConfidenceColor(chat.confidence)">
                                                    {{ chat.confidence }}% Confidence
                                                </span>
                                                <span v-if="chat.saved" class="text-warning-fg">
                                                    <BookmarkIcon class="h-4 w-4 fill-current" />
                                                </span>
                                            </div>
                                            <p class="mb-1 line-clamp-2 font-medium text-content">
                                                {{ chat.question }}
                                            </p>
                                            <p class="text-sm text-content-muted">
                                                {{ chat.time }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Academic Roadmap Preview -->
                        <Card>
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <h2 class="flex items-center gap-2 text-lg font-bold text-content">
                                    <ChartBarIcon class="h-5 w-5 text-primary" />
                                    Your Learning Path
                                </h2>
                                <Link href="/roadmap" class="ui-pill">
                                    Roadmap
                                    <ArrowRightIcon class="h-3.5 w-3.5" />
                                </Link>
                            </div>

                            <div class="mb-4 rounded-card border border-line bg-bg p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium text-content-muted">Current Progress</span>
                                    <span class="text-2xl font-bold text-content">{{ roadmapPreview.progress }}%</span>
                                </div>
                                <div class="mb-2 h-2.5 w-full rounded-full bg-neutral-bg">
                                    <div
                                        class="h-2.5 rounded-full bg-primary transition-all duration-500"
                                        :style="{ width: roadmapPreview.progress + '%' }"
                                    ></div>
                                </div>
                                <p class="text-sm text-content-muted">
                                    {{ roadmapPreview.completedTopics }} of {{ roadmapPreview.totalTopics }} topics completed
                                </p>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-3 rounded-card border border-line bg-bg p-3">
                                    <div class="ui-icon-tile flex-shrink-0 bg-primary-soft text-primary">
                                        <BookOpenIcon class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs text-content-faint">Currently Learning</p>
                                        <p class="truncate font-semibold text-content">{{ roadmapPreview.currentTopic }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 rounded-card border border-line bg-bg p-3">
                                    <div class="ui-icon-tile flex-shrink-0 bg-primary-soft text-primary">
                                        <FlagIcon class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs text-content-faint">Up Next</p>
                                        <p class="truncate font-semibold text-content">{{ roadmapPreview.nextTopic }}</p>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Course Materials Card -->
                        <Card hover>
                            <div class="group cursor-pointer" @click="navigateToMaterials">
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="ui-icon-tile h-11 w-11 bg-primary-soft text-primary transition-transform group-hover:scale-110">
                                        <DocumentTextIcon class="h-6 w-6" />
                                    </div>
                                    <ArrowRightIcon class="h-5 w-5 text-content-faint transition-colors group-hover:text-primary" />
                                </div>
                                <h3 class="mb-2 text-lg font-bold text-content">Course Materials</h3>
                                <p class="mb-3 text-sm text-content-muted">
                                    Access lecture notes, assignments, and course resources
                                </p>
                                <div class="flex items-center gap-4 text-sm text-content-muted">
                                    <span class="flex items-center gap-1.5">
                                        <BookOpenIcon class="h-4 w-4" />
                                        {{ materialsStats.total }} materials
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <DocumentTextIcon class="h-4 w-4" />
                                        {{ materialsStats.viewed }} viewed
                                    </span>
                                </div>
                                <div v-if="materialsStats.pending > 0" class="mt-3">
                                    <Badge variant="warning" :dot="true">
                                        {{ materialsStats.pending }} pending
                                    </Badge>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Upcoming Deadlines -->
                        <Card title="Deadlines" :icon="BellIcon">
                            <template #actions>
                                <Link href="/deadlines" class="ui-btn-ghost text-sm">
                                    View All
                                    <ArrowRightIcon class="h-4 w-4" />
                                </Link>
                            </template>

                            <div class="space-y-4">
                                <div
                                    v-for="deadline in upcomingDeadlines"
                                    :key="deadline.id"
                                    :class="`rounded-control border-l-4 p-4 transition-shadow duration-200 hover:shadow-card ${getDeadlineColor(deadline.daysLeft)}`"
                                >
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-content">
                                            {{ deadline.title }}
                                        </h3>
                                        <Badge v-if="deadline.urgent" variant="danger">URGENT</Badge>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-content-muted">
                                        <CalendarIcon class="h-4 w-4" />
                                        <span>{{ deadline.date }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <ClockIcon class="h-4 w-4 text-content-faint" />
                                        <span class="text-sm font-bold" :class="deadline.daysLeft <= 3 ? 'text-danger-fg' : 'text-content'">
                                            {{ deadline.daysLeft }} days left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Daily Tip Card -->
                        <Card>
                            <div class="mb-4 flex items-center gap-3">
                                <span class="ui-icon-tile bg-warning-bg text-warning-fg">
                                    <LightBulbIcon class="h-5 w-5" />
                                </span>
                                <h3 class="text-lg font-bold text-content">Daily Tip</h3>
                            </div>
                            <p class="text-sm leading-relaxed text-content-muted">
                                Break your study sessions into 25-minute focused intervals with 5-minute breaks. This technique improves concentration and retention!
                            </p>
                            <button class="ui-btn-secondary mt-4 w-full">
                                Get More Tips
                            </button>
                        </Card>

                        <!-- Motivational Quote -->
                        <Card>
                            <SparklesIcon class="mb-3 h-8 w-8 text-primary" />
                            <p class="mb-2 text-lg font-semibold italic text-content">
                                "Education is the most powerful weapon which you can use to change the world."
                            </p>
                            <p class="text-sm text-content-muted">
                                — Nelson Mandela
                            </p>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
