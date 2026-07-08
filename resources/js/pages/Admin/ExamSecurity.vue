<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    layers: { type: Array, default: () => [] },
    maxWarningsDefault: { type: Number, default: 3 },
});

const CATEGORY_META = {
    lockdown: 'Lockdown',
    integrity: 'Question integrity',
    monitoring: 'Monitoring & evidence',
    media: 'Media recording',
};
const CATEGORY_ORDER = ['lockdown', 'integrity', 'monitoring', 'media'];

const form = useForm({
    layers: Object.fromEntries(
        props.layers.map((layer) => [layer.key, { available: layer.available, default: layer.default }])
    ),
});

const grouped = computed(() => {
    const groups = {};
    for (const layer of props.layers) {
        if (!groups[layer.category]) groups[layer.category] = [];
        groups[layer.category].push(layer);
    }
    return CATEGORY_ORDER
        .filter((c) => groups[c]?.length)
        .map((c) => ({ key: c, label: CATEGORY_META[c] ?? c, layers: groups[c] }));
});

const submit = () => {
    form.patch(route('admin.exam-security.update'), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Exam Security" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <PageHeader
                    title="Exam security"
                    subtitle="Control which proctoring layers faculty can apply to class tests, and which are on by default."
                    :icon="ShieldCheckIcon"
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <Card v-for="group in grouped" :key="group.key" :title="group.label">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-line text-left text-content-muted">
                                        <th class="py-2 pr-4 font-medium">Layer</th>
                                        <th class="w-28 py-2 px-2 text-center font-medium">Available</th>
                                        <th class="w-28 py-2 px-2 text-center font-medium">On by default</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    <tr v-for="layer in group.layers" :key="layer.key">
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center gap-2 font-medium text-content">
                                                {{ layer.label }}
                                                <span v-if="layer.media" class="ui-badge bg-warning-soft text-warning-fg">consent</span>
                                            </div>
                                            <div class="text-xs text-content-muted">{{ layer.description }}</div>
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <input v-model="form.layers[layer.key].available" type="checkbox" class="h-4 w-4 rounded border-line" />
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <input
                                                v-model="form.layers[layer.key].default"
                                                type="checkbox"
                                                :disabled="!form.layers[layer.key].available"
                                                class="h-4 w-4 rounded border-line disabled:opacity-40"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-content-faint">
                            Disabling a layer's availability hides it from the faculty authoring form. Tests already
                            using a now-unavailable layer stop applying it. Default warnings before disqualification:
                            <strong>{{ maxWarningsDefault }}</strong> (set per test by faculty).
                        </p>
                        <button type="submit" :disabled="form.processing" class="ui-btn-primary">Save settings</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
</template>
