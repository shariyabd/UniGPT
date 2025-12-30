<template>
    <AppLayout>
        <div class="h-[calc(100vh-4rem)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full py-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 h-full flex flex-col">
                    <!-- Chat Header -->
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">AI Chat Assistant</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ask me anything about your studies</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    Online
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Chat Messages -->
                    <ChatBox />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ChatBox from '@/components/ChatBox.vue'; // Fixed: lowercase 'components'

defineOptions({
    title: 'Chat',
});
</script>