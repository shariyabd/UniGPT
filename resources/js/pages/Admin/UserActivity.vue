<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Pagination from '@/components/ui/Pagination.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    MapPinIcon,
    EyeIcon,
    UsersIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon,
    DeviceTabletIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    visits: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({ countries: [], devices: [] }) },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const device = ref(props.filters.device ?? '');
const country = ref(props.filters.country ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const roleOptions = computed(() => [
    { value: '', label: 'All roles' },
    ...props.roles.map((r) => ({ value: r.slug, label: r.name })),
]);
const deviceOptions = computed(() => [
    { value: '', label: 'All devices' },
    ...props.options.devices.map((d) => ({ value: d, label: d.charAt(0).toUpperCase() + d.slice(1) })),
]);
const countryOptions = computed(() => [
    { value: '', label: 'All countries' },
    ...props.options.countries.map((c) => ({ value: c, label: c })),
]);

const applyFilters = () => {
    router.get(
        route('admin.user-activity'),
        {
            search: search.value || undefined,
            role: role.value || undefined,
            device: device.value || undefined,
            country: country.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});
watch([role, device, country, from, to], applyFilters);

const deviceIcon = (type) => ({
    mobile: DevicePhoneMobileIcon,
    tablet: DeviceTabletIcon,
}[type] ?? ComputerDesktopIcon);

const roleVariant = (slug) => ({
    admin: 'danger',
    faculty: 'warning',
    student: 'info',
}[slug] ?? 'neutral');

const breakdown = (obj) => Object.entries(obj ?? {});
</script>

<template>
    <div>
        <Head title="User Activity" />
        <AppLayout>
            <div class="page-container space-y-6 py-8">
                <PageHeader
                    title="User Activity"
                    subtitle="Tracked page visits — who visited, from where, on what device, and their location"
                    :icon="MapPinIcon"
                    :count="stats.total"
                />

                <!-- Summary stats -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard label="Total Visits" :value="stats.total ?? 0" :icon="EyeIcon" color="primary" />
                    <StatCard label="Visits Today" :value="stats.today ?? 0" :icon="EyeIcon" color="success" />
                    <StatCard label="Unique Visitors" :value="stats.uniqueVisitors ?? 0" :icon="UsersIcon" color="violet" />
                    <StatCard label="Guest Visits" :value="stats.guestVisits ?? 0" :icon="GlobeAltIcon" color="warning" />
                </div>

                <!-- Device / country / page breakdowns -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card>
                        <h3 class="mb-3 text-sm font-semibold text-content">Devices</h3>
                        <ul class="space-y-2">
                            <li v-for="[type, count] in breakdown(stats.devices)" :key="type" class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 capitalize text-content-muted">
                                    <component :is="deviceIcon(type)" class="h-4 w-4" />{{ type }}
                                </span>
                                <span class="font-semibold text-content">{{ count }}</span>
                            </li>
                            <li v-if="!breakdown(stats.devices).length" class="text-sm text-content-faint">No data yet</li>
                        </ul>
                    </Card>
                    <Card>
                        <h3 class="mb-3 text-sm font-semibold text-content">Top Countries</h3>
                        <ul class="space-y-2">
                            <li v-for="[name, count] in breakdown(stats.topCountries)" :key="name" class="flex items-center justify-between text-sm">
                                <span class="text-content-muted">{{ name }}</span>
                                <span class="font-semibold text-content">{{ count }}</span>
                            </li>
                            <li v-if="!breakdown(stats.topCountries).length" class="text-sm text-content-faint">No data yet</li>
                        </ul>
                    </Card>
                    <Card>
                        <h3 class="mb-3 text-sm font-semibold text-content">Top Pages</h3>
                        <ul class="space-y-2">
                            <li v-for="[name, count] in breakdown(stats.topPages)" :key="name" class="flex items-center justify-between text-sm">
                                <span class="truncate text-content-muted">{{ name }}</span>
                                <span class="ml-2 font-semibold text-content">{{ count }}</span>
                            </li>
                            <li v-if="!breakdown(stats.topPages).length" class="text-sm text-content-faint">No data yet</li>
                        </ul>
                    </Card>
                </div>

                <!-- Filters -->
                <Card>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search user…"
                            class="ui-input lg:col-span-2"
                        />
                        <SearchableSelect v-model="role" :options="roleOptions" placeholder="All roles" />
                        <SearchableSelect v-model="device" :options="deviceOptions" placeholder="All devices" />
                        <SearchableSelect v-model="country" :options="countryOptions" placeholder="All countries" />
                        <div class="flex items-center gap-2">
                            <input v-model="from" type="date" class="ui-input" title="From" />
                            <input v-model="to" type="date" class="ui-input" title="To" />
                        </div>
                    </div>
                </Card>

                <!-- Activity table -->
                <Card padding="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                    <th class="px-4 py-3">User</th>
                                    <th class="px-4 py-3">Page</th>
                                    <th class="px-4 py-3">Came From</th>
                                    <th class="px-4 py-3">Device</th>
                                    <th class="px-4 py-3">Location</th>
                                    <th class="px-4 py-3 text-right">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="visit in visits.data"
                                    :key="visit.id"
                                    class="border-b border-border/60 hover:bg-bg"
                                >
                                    <td class="px-4 py-3">
                                        <template v-if="visit.user">
                                            <div class="font-medium text-content">{{ visit.user.name }}</div>
                                            <div class="flex items-center gap-2 text-xs text-content-muted">
                                                <Badge v-if="visit.role" :variant="roleVariant(visit.role)" class="capitalize">{{ visit.role }}</Badge>
                                                <span class="truncate">{{ visit.user.email }}</span>
                                            </div>
                                        </template>
                                        <span v-else class="text-content-faint">Guest</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-content">{{ visit.page }}</div>
                                        <div class="text-xs text-content-faint">/{{ visit.path }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="visit.referrer" class="text-content-muted" :title="visit.referrer">{{ visit.referrer }}</span>
                                        <span v-else class="text-content-faint">Direct / internal</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2 text-content-muted">
                                            <component :is="deviceIcon(visit.device)" class="h-4 w-4 shrink-0" />
                                            <span class="capitalize">{{ visit.device || '—' }}</span>
                                        </div>
                                        <div class="text-xs text-content-faint">{{ [visit.browser, visit.platform].filter(Boolean).join(' · ') || '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-content-muted">{{ visit.location || '—' }}</div>
                                        <div class="text-xs text-content-faint">{{ visit.ip }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-content-muted">{{ visit.visitedAt }}</div>
                                        <div class="text-xs text-content-faint">{{ visit.visitedAtExact }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState
                        v-if="!visits.data.length"
                        :icon="MapPinIcon"
                        title="No activity yet"
                        description="Tracked page visits will appear here as users browse the app."
                        class="py-12"
                    />
                </Card>

                <Pagination :paginator="visits" label="visits" />
            </div>
        </AppLayout>
    </div>
</template>
