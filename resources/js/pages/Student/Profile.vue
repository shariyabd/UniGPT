<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UserCircleIcon,
    EnvelopeIcon,
    AcademicCapIcon,
    BuildingLibraryIcon,
    IdentificationIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    bio: props.user.bio ?? '',
    avatar: props.user.avatar ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};

const avatarUrl = () =>
    form.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(props.user.name || 'U')}&background=6366f1&color=fff&size=128`;
</script>

<template>
    <div>
        <Head title="Profile" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
                    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2">My Profile</h1>
                            <p class="text-xl text-white/90">Manage your personal information</p>
                        </div>
                        <Link href="/dashboard" class="bg-white/20 backdrop-blur-lg text-white px-6 py-3 rounded-xl font-medium hover:bg-white/30 transition-all">
                            ← Dashboard
                        </Link>
                    </div>
                </div>

                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Identity card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
                        <img :src="avatarUrl()" :alt="user.name" class="w-28 h-28 rounded-full mx-auto mb-4 ring-4 ring-indigo-100 dark:ring-indigo-900" />
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ user.email }}</p>
                        <div class="space-y-3 text-left mt-6">
                            <div class="flex items-center gap-3 text-sm">
                                <IdentificationIcon class="w-5 h-5 text-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">{{ user.student_id || user.employee_id || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <BuildingLibraryIcon class="w-5 h-5 text-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">{{ user.department?.name || 'No department' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <AcademicCapIcon class="w-5 h-5 text-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">{{ user.semester || 'N/A' }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <span v-for="role in user.roles" :key="role.id" class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ role.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Edit form -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <UserCircleIcon class="w-6 h-6 text-indigo-600" /> Edit Profile
                        </h3>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                <p v-if="form.errors.name" class="text-sm text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                                <div class="flex items-center gap-2 px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400">
                                    <EnvelopeIcon class="w-5 h-5" /> {{ user.email }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Avatar URL</label>
                                <input v-model="form.avatar" type="url" placeholder="https://…" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                <p v-if="form.errors.avatar" class="text-sm text-red-500 mt-1">{{ form.errors.avatar }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bio</label>
                                <textarea v-model="form.bio" rows="4" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"></textarea>
                                <p v-if="form.errors.bio" class="text-sm text-red-500 mt-1">{{ form.errors.bio }}</p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:shadow-lg transform hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                    {{ form.processing ? 'Saving…' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
