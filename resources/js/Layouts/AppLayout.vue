<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can, hasRole, primaryRole } = usePermissions();

const showUserMenu = ref(false);

// Per-role navigation. Each item names the route it points at and (optionally)
// the permission required to use it — mirroring the server-side route matrix so
// users never see a link the backend would reject. Items without a permission
// are always shown to that role.
const navByRole = {
    student: [
        { label: 'Dashboard', route: 'dashboard' },
        { label: 'Chat', route: 'chat', permission: 'use_ai_chat' },
        { label: 'Saved Answers', route: 'saved', permission: 'view_chat_history' },
        { label: 'Roadmap', route: 'roadmap', permission: 'view_courses' },
        { label: 'Transcript', route: 'transcript', permission: 'view_courses' },
        { label: 'Documents', route: 'documents', permission: 'view_documents' },
        { label: 'Materials', route: 'materials', permission: 'view_courses' },
        { label: 'Attendance', route: 'attendance', permission: 'view_attendance' },
        { label: 'Exams', route: 'exams', permission: 'view_exams' },
        { label: 'Calendar', route: 'calendar' },
        { label: 'Tasks', route: 'tasks' },
        { label: 'Notes', route: 'notes' },
    ],
    faculty: [
        { label: 'Dashboard', route: 'faculty.dashboard' },
        { label: 'Courses', route: 'faculty.courses', permission: 'view_courses' },
        { label: 'AI Assistant', route: 'faculty.ai-assistant', permission: 'use_ai_chat' },
        { label: 'Grading', route: 'faculty.grading', permission: 'grade_assignment' },
        { label: 'Analytics', route: 'faculty.analytics', permission: 'view_department_analytics' },
        { label: 'Exams', route: 'faculty.exams', permission: 'view_exams' },
    ],
    admin: [
        { label: 'Dashboard', route: 'admin.dashboard' },
        { label: 'Users', route: 'admin.users', permission: 'view_users' },
        { label: 'Roles', route: 'admin.roles', permission: 'manage_permissions' },
        { label: 'Documents', route: 'admin.documents', permission: 'view_documents' },
        { label: 'Approvals', route: 'admin.approvals', permission: 'approve_document' },
        { label: 'Analytics', route: 'admin.analytics', permission: 'view_all_analytics' },
        { label: 'Announcements', route: 'admin.announcements', permission: 'send_notifications' },
        { label: 'Exams', route: 'admin.exams', permission: 'manage_exams' },
        { label: 'Settings', route: 'admin.settings', permission: 'configure_ai' },
        { label: 'Monitor', route: 'admin.monitor', permission: 'manage_system' },
    ],
};

const navItems = computed(() => {
    const items = navByRole[primaryRole.value] ?? [];

    return items.filter((item) => !item.permission || can(item.permission));
});

// Close dropdown when clicking outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', (e) => {
        if (!e.target.closest('button')) {
            showUserMenu.value = false;
        }
    });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-xl font-bold text-white">🎓</span>
                            </div>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">UniGPT</span>
                        </Link>
                    </div>

                    <!-- Navigation Links (role + permission aware) -->
                    <div class="hidden md:flex items-center space-x-6">
                        <NavLink
                            v-for="item in navItems"
                            :key="item.route"
                            :href="route(item.route)"
                            :active="route().current(item.route)"
                        >
                            {{ item.label }}
                        </NavLink>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <NotificationBell v-if="$page.props.auth?.user" />

                        <div v-if="$page.props.auth?.user" class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </span>
                            </button>

                            <!-- Dropdown Menu -->
                            <div v-show="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-50">
                                <template v-if="hasRole('student')">
                                    <Link
                                        href="/profile"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                    >
                                        Profile
                                    </Link>
                                    <Link
                                        href="/settings"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                    >
                                        Settings
                                    </Link>
                                    <Link
                                        v-if="can('view_chat_history')"
                                        href="/saved"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                    >
                                        📚 Saved Answers
                                    </Link>
                                    <hr class="my-1 border-slate-200 dark:border-slate-700">
                                </template>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                        <div v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400"
                            >
                                Login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <FlashMessages />

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>