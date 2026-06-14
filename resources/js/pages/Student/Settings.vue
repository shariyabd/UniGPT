<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Cog6ToothIcon, SunIcon, MoonIcon, ComputerDesktopIcon, BellIcon, LanguageIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    preferences: { type: Object, default: () => ({ theme: 'system', notifications: true, language: 'en' }) },
});

const form = useForm({
    theme: props.preferences.theme ?? 'system',
    notifications: props.preferences.notifications ?? true,
    language: props.preferences.language ?? 'en',
});

const themes = [
    { value: 'light', label: 'Light', icon: SunIcon },
    { value: 'dark', label: 'Dark', icon: MoonIcon },
    { value: 'system', label: 'System', icon: ComputerDesktopIcon },
];

const languages = [
    { value: 'en', label: 'English' },
    { value: 'ar', label: 'العربية' },
    { value: 'es', label: 'Español' },
    { value: 'fr', label: 'Français' },
    { value: 'de', label: 'Deutsch' },
];

const submit = () => {
    form.patch(route('settings.update'), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Settings" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <div class="bg-gradient-to-r from-slate-700 via-gray-700 to-zinc-700 dark:from-slate-900 dark:via-gray-900 dark:to-zinc-900">
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                                <Cog6ToothIcon class="w-9 h-9" /> Settings
                            </h1>
                            <p class="text-xl text-white/90">Customize your UniGPT experience</p>
                        </div>
                        <Link href="/dashboard" class="bg-white/20 backdrop-blur-lg text-white px-6 py-3 rounded-xl font-medium hover:bg-white/30 transition-all">
                            ← Dashboard
                        </Link>
                    </div>
                </div>

                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 space-y-10">
                        <!-- Theme -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Appearance</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <button v-for="theme in themes" :key="theme.value" type="button" @click="form.theme = theme.value"
                                    :class="`flex flex-col items-center gap-2 p-5 rounded-xl border-2 transition-all ${form.theme === theme.value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300'}`">
                                    <component :is="theme.icon" class="w-7 h-7 text-indigo-600 dark:text-indigo-400" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ theme.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-8">
                            <div class="flex items-center gap-3">
                                <BellIcon class="w-6 h-6 text-indigo-600" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Notifications</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Receive updates about deadlines and replies</p>
                                </div>
                            </div>
                            <button type="button" @click="form.notifications = !form.notifications"
                                :class="`relative w-14 h-8 rounded-full transition-colors ${form.notifications ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'}`">
                                <span :class="`absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform ${form.notifications ? 'translate-x-6' : ''}`"></span>
                            </button>
                        </div>

                        <!-- Language -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <LanguageIcon class="w-6 h-6 text-indigo-600" /> Language
                            </h3>
                            <select v-model="form.language" class="w-full md:w-72 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option v-for="lang in languages" :key="lang.value" :value="lang.value">{{ lang.label }}</option>
                            </select>
                        </div>

                        <div class="flex justify-end border-t border-gray-100 dark:border-gray-700 pt-8">
                            <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:shadow-lg transform hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                {{ form.processing ? 'Saving…' : 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
