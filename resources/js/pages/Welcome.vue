<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <div>
        <Head title="Welcome" />
        <div class="min-h-screen bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 flex items-center justify-center">
            <div class="text-center text-white p-8">
                <h1 class="text-6xl font-bold mb-4">UniGPT</h1>
                <p class="text-2xl mb-8">University Academic Copilot</p>

                <div class="space-x-4">
                    <Link
                        href="/dashboard"
                        class="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold inline-block hover:shadow-2xl transition-all"
                    >
                        Dashboard
                    </Link>
                    <Link
                        href="/chat"
                        class="px-8 py-4 bg-white/20 text-white rounded-xl font-bold inline-block border-2 border-white hover:bg-white/30 transition-all"
                    >
                        Chat
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>