<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';
import FlashMessages from '@/components/FlashMessages.vue';

const showUserMenu = ref(false);

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

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-6">
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </NavLink>
                        <NavLink :href="route('chat')" :active="route().current('chat')">
                            Chat
                        </NavLink>
                        <!-- FIXED: Add saved answers to nav -->
                        <NavLink :href="route('saved')" :active="route().current('saved')">
                            Saved Answers
                        </NavLink>
                        <NavLink :href="route('roadmap')" :active="route().current('roadmap')">
                            Roadmap
                        </NavLink>
                        <NavLink href="/student/materials" :active="route().current('student.materials')">
                            <DocumentTextIcon class="w-5 h-5" />
                            Materials
                        </NavLink>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
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
                                <!-- FIXED: Add saved answers to user menu -->
                                <Link
                                    href="/saved"
                                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                >
                                    📚 Saved Answers
                                </Link>
                                <hr class="my-1 border-slate-200 dark:border-slate-700">
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