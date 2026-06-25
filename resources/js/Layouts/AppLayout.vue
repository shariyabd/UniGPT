<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTheme } from '@/composables/useTheme';
import { useHeartbeat } from '@/composables/useHeartbeat';
import {
    Squares2X2Icon,
    ChatBubbleLeftRightIcon,
    ChatBubbleLeftEllipsisIcon,
    BookmarkIcon,
    MapIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    BookOpenIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentListIcon,
    PlusCircleIcon,
    PencilSquareIcon,
    CalendarIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    PencilIcon,
    SparklesIcon,
    ChartBarIcon,
    CpuChipIcon,
    UsersIcon,
    BuildingOffice2Icon,
    ShieldCheckIcon,
    CheckBadgeIcon,
    MegaphoneIcon,
    Cog6ToothIcon,
    ServerStackIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowRightOnRectangleIcon,
    UserCircleIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ArrowRightIcon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';

const { can, hasRole, primaryRole } = usePermissions();
const { isDark, initTheme, toggleTheme } = useTheme();
const page = usePage();

// Presence heartbeat — runs on every authenticated page so "active" status
// reflects the user being anywhere in the app, not just the messenger.
useHeartbeat();

onMounted(initTheme);

const sidebarOpen = ref(false);
const showUserMenu = ref(false);

// Per-role navigation, grouped into labelled sections (Acadify pattern).
// Routes, icons and permissions are unchanged — only reorganised into groups.
const navByRole = {
    student: [
        { section: 'Overview', items: [
            { label: 'Dashboard', route: 'dashboard', icon: Squares2X2Icon },
        ] },
        { section: 'Academic', items: [
            { label: 'Registration', route: 'register', icon: PlusCircleIcon, permission: 'enroll_course' },
            { label: 'Roadmap', route: 'roadmap', icon: MapIcon, permission: 'view_courses' },
            { label: 'Transcript', route: 'transcript', icon: AcademicCapIcon, permission: 'view_courses' },
            { label: 'Materials', route: 'materials', icon: BookOpenIcon, permission: 'view_courses' },
            { label: 'Assignments', route: 'assignments', icon: ClipboardDocumentListIcon, permission: 'view_assignments' },
            { label: 'Class Tests', route: 'class-tests', icon: ClipboardDocumentCheckIcon, permission: 'take_class_test' },
            { label: 'Attendance', route: 'attendance', icon: ClipboardDocumentCheckIcon, permission: 'view_attendance' },
            { label: 'Exams', route: 'exams', icon: PencilSquareIcon, permission: 'view_exams' },
        ] },
        { section: 'AI Copilot', items: [
            { label: 'AI Chat', route: 'chat', icon: ChatBubbleLeftRightIcon, permission: 'use_ai_chat' },
            { label: 'Saved Answers', route: 'saved', icon: BookmarkIcon, permission: 'view_chat_history' },
        ] },
        { section: 'Connect', items: [
            { label: 'My Faculty', route: 'my-faculty', icon: UsersIcon },
            { label: 'Messages', route: 'messages', icon: ChatBubbleLeftEllipsisIcon },
        ] },
        { section: 'Planner', items: [
            { label: 'Calendar', route: 'calendar', icon: CalendarIcon },
            { label: 'Tasks', route: 'tasks', icon: CheckCircleIcon },
            { label: 'Notes', route: 'notes', icon: PencilIcon },
        ] },
        { section: 'Documents', items: [
            { label: 'Documents', route: 'documents', icon: DocumentTextIcon, permission: 'view_documents' },
        ] },
    ],
    faculty: [
        { section: 'Overview', items: [
            { label: 'Dashboard', route: 'faculty.dashboard', icon: Squares2X2Icon },
        ] },
        { section: 'Teaching', items: [
            { label: 'Courses', route: 'faculty.courses', icon: BookOpenIcon, permission: 'view_courses' },
            { label: 'Grading', route: 'faculty.grading', icon: ClipboardDocumentCheckIcon, permission: 'grade_assignment' },
            { label: 'Class Tests', route: 'faculty.class-tests', icon: ClipboardDocumentListIcon, permission: 'manage_class_tests' },
            { label: 'Exams', route: 'faculty.exams', icon: PencilSquareIcon, permission: 'view_exams' },
        ] },
        { section: 'Connect', items: [
            { label: 'My Students', route: 'faculty.students', icon: UsersIcon },
            { label: 'Messages', route: 'faculty.messages', icon: ChatBubbleLeftEllipsisIcon },
        ] },
        { section: 'Insights', items: [
            { label: 'Analytics', route: 'faculty.analytics', icon: ChartBarIcon, permission: 'view_department_analytics' },
        ] },
        { section: 'AI Copilot', items: [
            { label: 'AI Assistant', route: 'faculty.ai-assistant', icon: SparklesIcon, permission: 'use_ai_chat' },
        ] },
    ],
    admin: [
        { section: 'Overview', items: [
            { label: 'Dashboard', route: 'admin.dashboard', icon: Squares2X2Icon },
        ] },
        { section: 'People', items: [
            { label: 'Users', route: 'admin.users', icon: UsersIcon, permission: 'view_users' },
            { label: 'Departments', route: 'admin.departments', icon: BuildingOffice2Icon, permission: 'manage_departments' },
            { label: 'Roles', route: 'admin.roles', icon: ShieldCheckIcon, permission: 'manage_permissions' },
        ] },
        { section: 'Academics', items: [
            { label: 'Courses', route: 'admin.courses', icon: AcademicCapIcon, permission: 'view_courses' },
            { label: 'Terms', route: 'admin.terms', icon: CalendarDaysIcon, permission: 'manage_terms' },
            { label: 'Documents', route: 'admin.documents', icon: DocumentTextIcon, permission: 'view_documents' },
            { label: 'Approvals', route: 'admin.approvals', icon: CheckBadgeIcon, permission: 'approve_document' },
            { label: 'Exams', route: 'admin.exams', icon: PencilSquareIcon, permission: 'manage_exams' },
        ] },
        { section: 'System', items: [
            { label: 'Analytics', route: 'admin.analytics', icon: ChartBarIcon, permission: 'view_all_analytics' },
            { label: 'AI Usage', route: 'admin.ai-usage', icon: CpuChipIcon, permission: 'view_all_analytics' },
            { label: 'Announcements', route: 'admin.announcements', icon: MegaphoneIcon, permission: 'send_notifications' },
            { label: 'Settings', route: 'admin.settings', icon: Cog6ToothIcon, permission: 'configure_ai' },
            { label: 'Monitor', route: 'admin.monitor', icon: ServerStackIcon, permission: 'manage_system' },
        ] },
    ],
};

// Lightweight per-role identity + a contextual sidebar promo CTA.
const roleMeta = {
    student: { label: 'Student', promoTitle: 'Ask UniGPT anything', promoCta: 'New Chat', promoRoute: 'chat' },
    faculty: { label: 'Faculty', promoTitle: 'AI Teaching Assistant', promoCta: 'Open Assistant', promoRoute: 'faculty.ai-assistant' },
    admin: { label: 'Admin', promoTitle: 'Manage your campus', promoCta: 'Add User', promoRoute: 'admin.users' },
};

const meta = computed(() => roleMeta[primaryRole.value] ?? roleMeta.student);

// Filter each group's items by permission, then drop empty groups.
const navGroups = computed(() => {
    const groups = navByRole[primaryRole.value] ?? [];
    return groups
        .map((group) => ({
            section: group.section,
            items: group.items.filter((item) => !item.permission || can(item.permission)),
        }))
        .filter((group) => group.items.length > 0);
});

// --- Header navigation search ----------------------------------------------
// A flat, permission-filtered list of every page the current user can reach,
// searchable by page title. The header search jumps between modules/pages.
const searchableItems = computed(() =>
    navGroups.value.flatMap((group) =>
        group.items
            .filter((item) => hasRoute(item.route))
            .map((item) => ({
                label: item.label,
                section: group.section,
                route: item.route,
                icon: item.icon,
            })),
    ),
);

const searchQuery = ref('');
const searchOpen = ref(false);
const searchInput = ref(null);
const activeIndex = ref(0);

const searchResults = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return searchableItems.value;
    }

    return searchableItems.value.filter(
        (item) =>
            item.label.toLowerCase().includes(query) ||
            item.section.toLowerCase().includes(query),
    );
});

// Keep the highlighted result valid as the query narrows the list.
watch(searchResults, () => { activeIndex.value = 0; });

const openSearch = () => { searchOpen.value = true; };
const closeSearch = () => { searchOpen.value = false; };

const goToResult = (item) => {
    if (!item) {
        return;
    }
    searchOpen.value = false;
    searchQuery.value = '';
    router.visit(route(item.route));
};

const onSearchEnter = () => {
    goToResult(searchResults.value[activeIndex.value] ?? searchResults.value[0]);
};

const moveActive = (delta) => {
    const count = searchResults.value.length;
    if (count === 0) {
        return;
    }
    activeIndex.value = (activeIndex.value + delta + count) % count;
};

const user = computed(() => page.props.auth?.user ?? null);
const userInitial = computed(() => (user.value?.name?.charAt(0) ?? '?').toUpperCase());

// Function declarations (hoisted) so the header-search computeds defined above
// can reference hasRoute() even though it appears later in source order.
function hasRoute(name) {
    try { return !!route().has(name); } catch (e) { return false; }
}
function isActive(name) {
    try { return route().current(name); } catch (e) { return false; }
}
const promoHref = computed(() => (hasRoute(meta.value.promoRoute) ? route(meta.value.promoRoute) : '#'));

// The AI assistant is the headline feature, so it gets a prominent header CTA.
// Students land in the AI Chat; faculty in their AI Teaching Assistant. Admins
// have no conversational assistant, so the CTA is hidden for them.
const aiAssistant = computed(() => {
    if (primaryRole.value === 'faculty') {
        return { route: 'faculty.ai-assistant', label: 'AI Assistant' };
    }
    if (primaryRole.value === 'student') {
        return { route: 'chat', label: 'AI Assistant' };
    }
    return null;
});
const aiAssistantHref = computed(() =>
    aiAssistant.value && hasRoute(aiAssistant.value.route) ? route(aiAssistant.value.route) : null,
);

const closeSidebar = () => { sidebarOpen.value = false; };

if (typeof window !== 'undefined') {
    window.addEventListener('click', (e) => {
        if (!e.target.closest('[data-user-menu]')) showUserMenu.value = false;
    });
    // ⌘K / Ctrl+K focuses the header page search.
    window.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchInput.value?.focus();
        }
    });
}
</script>

<template>
    <div class="min-h-dvh bg-bg">
        <!-- Mobile scrim -->
        <Transition
            enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0"
        >
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-content/30 backdrop-blur-sm lg:hidden" @click="closeSidebar"></div>
        </Transition>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[264px] flex-col border-r border-line bg-surface transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->
            <div class="flex h-16 flex-shrink-0 items-center justify-between px-5">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-control bg-primary text-white">
                        <AcademicCapIcon class="h-5 w-5" />
                    </div>
                    <span class="text-lg font-bold tracking-tight text-content">UniGPT</span>
                </Link>
                <button type="button" class="rounded-control p-1.5 text-content-faint hover:bg-primary-soft lg:hidden" @click="closeSidebar" aria-label="Close menu">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Nav (grouped) -->
            <nav class="flex-1 overflow-y-auto px-3 pb-4">
                <template v-for="group in navGroups" :key="group.section">
                    <p class="ui-nav-section">{{ group.section }}</p>
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in group.items"
                            :key="item.route"
                            :href="route(item.route)"
                            @click="closeSidebar"
                            class="ui-nav-link"
                            :class="isActive(item.route) ? 'ui-nav-link-active' : 'ui-nav-link-idle'"
                        >
                            <component :is="item.icon" class="h-[18px] w-[18px] flex-shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </div>
                </template>

                <!-- Contextual promo card -->
                <div class="mt-6 rounded-card bg-primary-soft p-4">
                    <span class="ui-icon-tile mb-3 bg-primary text-white">
                        <SparklesIcon class="h-5 w-5" />
                    </span>
                    <p class="text-sm font-semibold text-content">{{ meta.promoTitle }}</p>
                    <p class="mt-0.5 text-xs text-content-muted">Jump straight to what matters.</p>
                    <Link :href="promoHref" class="ui-btn-primary mt-3 w-full px-3 py-2 text-xs">
                        {{ meta.promoCta }}
                        <ArrowRightIcon class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </nav>

            <!-- Footer links -->
            <div class="flex-shrink-0 space-y-0.5 border-t border-line px-3 py-4">
                <Link href="/settings" v-if="hasRole('student')" class="ui-nav-link ui-nav-link-idle">
                    <Cog6ToothIcon class="h-[18px] w-[18px] flex-shrink-0" />
                    Settings
                </Link>
                <Link
                    :href="route('logout')" method="post" as="button"
                    class="ui-nav-link w-full text-content-muted transition-colors hover:bg-danger-bg hover:text-danger-fg"
                >
                    <ArrowRightOnRectangleIcon class="h-[18px] w-[18px] flex-shrink-0" />
                    Sign Out
                </Link>
            </div>
        </aside>

        <!-- Main column -->
        <div class="lg:pl-[264px]">
            <!-- Topbar -->
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-bg/80 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                <button type="button" class="rounded-control border border-line bg-surface p-2 text-content-muted shadow-card lg:hidden" @click.stop="sidebarOpen = true" aria-label="Open menu">
                    <Bars3Icon class="h-5 w-5" />
                </button>

                <!-- Search (jump to a page) -->
                <div class="relative hidden max-w-md flex-1 sm:block">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search pages..."
                        aria-label="Search pages"
                        class="w-full rounded-pill border border-line bg-surface py-2.5 pl-11 pr-16 text-sm text-content shadow-card placeholder:text-content-faint focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        @focus="openSearch"
                        @blur="closeSearch"
                        @keydown.down.prevent="moveActive(1)"
                        @keydown.up.prevent="moveActive(-1)"
                        @keydown.enter.prevent="onSearchEnter"
                        @keydown.esc="closeSearch"
                    />
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-neutral-bg px-2 py-1 text-[11px] font-semibold text-content-faint">⌘ K</span>

                    <!-- Results dropdown -->
                    <div
                        v-if="searchOpen"
                        class="absolute left-0 right-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-card border border-line bg-surface py-2 shadow-card"
                    >
                        <button
                            v-for="(item, idx) in searchResults"
                            :key="item.route"
                            type="button"
                            @mousedown.prevent="goToResult(item)"
                            @mouseenter="activeIndex = idx"
                            class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm transition-colors"
                            :class="idx === activeIndex ? 'bg-primary-soft text-primary' : 'text-content hover:bg-neutral-bg'"
                        >
                            <component :is="item.icon" class="h-4 w-4 flex-shrink-0" />
                            <span class="flex-1 truncate">{{ item.label }}</span>
                            <span class="text-xs text-content-faint">{{ item.section }}</span>
                        </button>
                        <p v-if="searchResults.length === 0" class="px-4 py-3 text-sm text-content-muted">
                            No pages match “{{ searchQuery }}”.
                        </p>
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="flex h-10 w-10 items-center justify-center rounded-pill border border-line bg-surface text-content-muted shadow-card transition-colors hover:bg-primary-soft hover:text-primary"
                        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    >
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>

                    <NotificationBell v-if="user && !(user.preferences && user.preferences.notifications === false)" />

                    <!-- AI Assistant — the app's headline feature, given a prominent
                         always-visible CTA for students and faculty. -->
                    <Link
                        v-if="aiAssistantHref"
                        :href="aiAssistantHref"
                        class="group relative flex h-10 items-center gap-2 rounded-pill bg-brand-gradient px-3 text-sm font-semibold text-white shadow-[0_8px_22px_-8px_rgba(124,92,252,0.65)] ring-1 ring-white/20 transition-transform duration-200 hover:-translate-y-0.5 sm:px-4"
                        :title="aiAssistant.label"
                        :aria-label="aiAssistant.label"
                    >
                        <span class="absolute right-1.5 top-1.5 flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                        </span>
                        <SparklesIcon class="h-5 w-5 transition-transform duration-200 group-hover:scale-110" />
                        <span class="hidden sm:inline">{{ aiAssistant.label }}</span>
                    </Link>

                    <div v-if="user" class="relative" data-user-menu>
                        <button @click.stop="showUserMenu = !showUserMenu" class="flex items-center gap-2.5 rounded-pill border border-line bg-surface py-1.5 pl-1.5 pr-2.5 shadow-card transition-colors hover:bg-primary-soft">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">{{ userInitial }}</div>
                            <span class="hidden text-left leading-tight sm:block">
                                <span class="block text-sm font-semibold text-content">{{ user.name }}</span>
                                <span class="block text-xs capitalize text-content-faint">{{ user.primary_role || meta.label }}</span>
                            </span>
                            <ChevronDownIcon class="hidden h-4 w-4 text-content-faint sm:block" />
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95"
                            leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0 scale-95"
                        >
                            <div v-show="showUserMenu" class="absolute right-0 mt-2 w-56 origin-top-right overflow-hidden rounded-card border border-line bg-surface py-1.5 shadow-card-hover">
                                <div class="border-b border-line px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-content">{{ user.name }}</p>
                                    <p class="truncate text-xs text-content-faint">{{ user.email }}</p>
                                </div>
                                <template v-if="hasRole('student')">
                                    <Link href="/profile" class="flex items-center gap-2.5 px-4 py-2 text-sm text-content-muted hover:bg-primary-soft hover:text-primary"><UserCircleIcon class="h-4 w-4" /> Profile</Link>
                                    <Link href="/settings" class="flex items-center gap-2.5 px-4 py-2 text-sm text-content-muted hover:bg-primary-soft hover:text-primary"><Cog6ToothIcon class="h-4 w-4" /> Settings</Link>
                                    <Link v-if="can('view_chat_history')" href="/saved" class="flex items-center gap-2.5 px-4 py-2 text-sm text-content-muted hover:bg-primary-soft hover:text-primary"><BookmarkIcon class="h-4 w-4" /> Saved Answers</Link>
                                    <div class="my-1 border-t border-line"></div>
                                </template>
                                <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-danger-fg hover:bg-danger-bg"><ArrowRightOnRectangleIcon class="h-4 w-4" /> Sign Out</Link>
                            </div>
                        </Transition>
                    </div>
                    <Link v-else :href="route('login')" class="ui-btn-primary">Login</Link>
                </div>
            </header>

            <ConfirmDialog />
            <main class="app-main min-h-[calc(100dvh-64px)] animate-fade-in">
                <slot />
            </main>
        </div>
    </div>
</template>
