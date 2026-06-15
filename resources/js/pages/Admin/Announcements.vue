<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MegaphoneIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    audiences: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const form = useForm({
    audience: 'all',
    title: '',
    message: '',
});

const submit = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'message'),
    });
};
</script>

<template>
    <Head title="Announcements" />

    <AppLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                    <MegaphoneIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Announcements</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Broadcast a notification to a group of users.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Audience</label>
                    <select v-model="form.audience"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option v-for="a in audiences" :key="a.value" :value="a.value">{{ a.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input v-model="form.title" type="text" maxlength="150" placeholder="Midterm schedule released"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                    <textarea v-model="form.message" rows="4" maxlength="2000" placeholder="Write your announcement…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    <p v-if="form.errors.message" class="text-xs text-red-500 mt-1">{{ form.errors.message }}</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <PaperAirplaneIcon class="w-4 h-4" />
                        {{ form.processing ? 'Sending…' : 'Send announcement' }}
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Recent announcements</h2>
                <div v-if="recent.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                    No announcements sent yet.
                </div>
                <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                    <div v-for="(item, i) in recent" :key="i" class="p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.title }}</p>
                            <span class="text-xs text-gray-400">{{ item.time }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{{ item.message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ item.recipients }} recipient(s)</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
