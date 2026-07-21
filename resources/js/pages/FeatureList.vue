<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    ArrowLeftIcon,
    SparklesIcon,
    XMarkIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    // [{ name, features: [...] }] — parsed server-side from docs/feature-list.md.
    groups: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const activeGroup = ref('All');

const totalCount = computed(() =>
    props.groups.reduce((sum, group) => sum + group.features.length, 0)
);

// A stable accent per group keeps the page structured without being loud.
const accents = {
    Student: { dot: 'bg-violet-500', chip: 'bg-violet-600', ring: 'ring-violet-200', text: 'text-violet-700', soft: 'bg-violet-50' },
    Faculty: { dot: 'bg-sky-500', chip: 'bg-sky-600', ring: 'ring-sky-200', text: 'text-sky-700', soft: 'bg-sky-50' },
    Admin: { dot: 'bg-amber-500', chip: 'bg-amber-600', ring: 'ring-amber-200', text: 'text-amber-700', soft: 'bg-amber-50' },
    Shared: { dot: 'bg-emerald-500', chip: 'bg-emerald-600', ring: 'ring-emerald-200', text: 'text-emerald-700', soft: 'bg-emerald-50' },
    Engine: { dot: 'bg-slate-500', chip: 'bg-slate-700', ring: 'ring-slate-200', text: 'text-slate-700', soft: 'bg-slate-50' },
};

const accentFor = (name) => {
    const key = Object.keys(accents).find((k) => name.startsWith(k));
    return accents[key] ?? accents.Engine;
};

// Filter by search (feature name) then by the selected group; drop empty groups.
const filteredGroups = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.groups
        .filter((group) => activeGroup.value === 'All' || group.name === activeGroup.value)
        .map((group) => ({
            ...group,
            features: query
                ? group.features.filter((f) => f.toLowerCase().includes(query))
                : group.features,
        }))
        .filter((group) => group.features.length > 0);
});

const visibleCount = computed(() =>
    filteredGroups.value.reduce((sum, group) => sum + group.features.length, 0)
);

const clearSearch = () => { searchQuery.value = ''; };
</script>

<template>
    <div class="min-h-dvh bg-gray-50 text-gray-900 antialiased">
        <Head title="Feature List" />

        <!-- Header -->
        <header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-5 py-4 sm:px-8">
                <Link :href="route('home')" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-900" title="Back home">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-600 text-white">
                    <SparklesIcon class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-semibold leading-tight sm:text-base">UniNexus — Feature List</h1>
                    <p class="text-xs text-gray-500">{{ totalCount }} features across {{ groups.length }} areas</p>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-8 sm:px-8">
            <!-- Intro -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Everything UniNexus does</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-500">
                    An interactive index of every shipped capability, grouped by who uses it. Search by name or filter by area.
                </p>
            </div>

            <!-- Search -->
            <div class="relative mb-4">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search features…"
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-12 pr-11 text-sm shadow-sm outline-none transition placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                    aria-label="Search features"
                />
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="absolute right-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Clear search"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>

            <!-- Group filter chips -->
            <div class="mb-8 flex flex-wrap gap-2">
                <button
                    @click="activeGroup = 'All'"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition',
                        activeGroup === 'All'
                            ? 'border-transparent bg-gray-900 text-white shadow-sm'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900',
                    ]"
                >
                    <Squares2X2Icon class="h-3.5 w-3.5" />
                    All
                    <span class="ml-0.5 rounded-full bg-white/20 px-1.5 py-px text-[10px] font-bold" :class="activeGroup === 'All' ? 'bg-white/20' : 'bg-gray-100 text-gray-500'">{{ totalCount }}</span>
                </button>

                <button
                    v-for="group in groups"
                    :key="group.name"
                    @click="activeGroup = group.name"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition',
                        activeGroup === group.name
                            ? ['border-transparent text-white shadow-sm', accentFor(group.name).chip]
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900',
                    ]"
                >
                    <span class="h-2 w-2 rounded-full" :class="activeGroup === group.name ? 'bg-white/70' : accentFor(group.name).dot"></span>
                    {{ group.name }}
                    <span
                        class="ml-0.5 rounded-full px-1.5 py-px text-[10px] font-bold"
                        :class="activeGroup === group.name ? 'bg-white/20' : 'bg-gray-100 text-gray-500'"
                    >{{ group.features.length }}</span>
                </button>
            </div>

            <!-- Groups -->
            <div v-if="filteredGroups.length" class="space-y-10">
                <section v-for="group in filteredGroups" :key="group.name">
                    <div class="mb-3 flex items-center gap-2.5">
                        <span class="h-2.5 w-2.5 rounded-full" :class="accentFor(group.name).dot"></span>
                        <h3 class="text-base font-bold tracking-tight">{{ group.name }}</h3>
                        <span class="text-xs font-medium text-gray-400">{{ group.features.length }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="(feature, index) in group.features"
                            :key="feature"
                            class="group flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md"
                        >
                            <span
                                class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg text-[11px] font-bold"
                                :class="[accentFor(group.name).soft, accentFor(group.name).text]"
                            >
                                {{ index + 1 }}
                            </span>
                            <span class="text-sm font-medium leading-snug text-gray-800">{{ feature }}</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Empty state -->
            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <MagnifyingGlassIcon class="h-5 w-5" />
                </div>
                <p class="text-sm font-semibold text-gray-700">No features match “{{ searchQuery }}”</p>
                <button @click="clearSearch" class="mt-3 text-xs font-semibold text-violet-600 hover:text-violet-700">Clear search</button>
            </div>

            <!-- Footer count -->
            <p v-if="filteredGroups.length" class="mt-10 border-t border-gray-200 pt-5 text-center text-xs text-gray-400">
                Showing {{ visibleCount }} of {{ totalCount }} features
            </p>
        </main>
    </div>
</template>
