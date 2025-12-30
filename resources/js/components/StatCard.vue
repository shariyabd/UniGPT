<template>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1">
        <div class="flex items-center">
            <div :class="[
                'p-3 rounded-xl mr-4',
                color === 'blue' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' :
                color === 'green' ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' :
                color === 'purple' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' :
                color === 'pink' ? 'bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400' :
                'bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400'
            ]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ title }}</h3>
                <div class="flex items-baseline">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
                    <p :class="[
                        'ml-2 text-sm font-medium',
                        trend.startsWith('+') ? 'text-green-600' : 'text-red-600'
                    ]">
                        {{ trend }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    value: String,
    icon: String,
    color: {
        type: String,
        default: 'blue'
    },
    trend: String
});
</script>