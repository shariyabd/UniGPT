<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center mb-4">
            <div 
                :class="[
                    'w-10 h-10 rounded-lg flex items-center justify-center mr-3',
                    `bg-${color}-100 dark:bg-${color}-900`
                ]"
            >
                <svg 
                    :class="[
                        'w-6 h-6',
                        `text-${color}-600 dark:text-${color}-400`
                    ]"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        stroke-width="2" 
                        :d="icon"
                    ></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        </div>
        <p class="text-gray-600 dark:text-gray-300 text-sm">{{ description }}</p>
    </div>
</template>

<script setup>
defineProps({
    icon: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    color: {
        type: String,
        default: 'indigo',
    },
});
</script>

<style scoped>
/* Safelist colors for Tailwind purge */
.bg-indigo-100 { @apply bg-indigo-100; }
.bg-indigo-900 { @apply bg-indigo-900; }
.text-indigo-600 { @apply text-indigo-600; }
.text-indigo-400 { @apply text-indigo-400; }

.bg-green-100 { @apply bg-green-100; }
.bg-green-900 { @apply bg-green-900; }
.text-green-600 { @apply text-green-600; }
.text-green-400 { @apply text-green-400; }

.bg-blue-100 { @apply bg-blue-100; }
.bg-blue-900 { @apply bg-blue-900; }
.text-blue-600 { @apply text-blue-600; }
.text-blue-400 { @apply text-blue-400; }

.bg-purple-100 { @apply bg-purple-100; }
.bg-purple-900 { @apply bg-purple-900; }
.text-purple-600 { @apply text-purple-600; }
.text-purple-400 { @apply text-purple-400; }

.bg-yellow-100 { @apply bg-yellow-100; }
.bg-yellow-900 { @apply bg-yellow-900; }
.text-yellow-600 { @apply text-yellow-600; }
.text-yellow-400 { @apply text-yellow-400; }

.bg-pink-100 { @apply bg-pink-100; }
.bg-pink-900 { @apply bg-pink-900; }
.text-pink-600 { @apply text-pink-600; }
.text-pink-400 { @apply text-pink-400; }
</style>
