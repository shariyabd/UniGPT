<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import { ClockIcon, MapPinIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    slots: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const myBookings = computed(() => props.slots.filter((slot) => slot.isMine));
const openByFaculty = computed(() => {
    const groups = new Map();
    props.slots
        .filter((slot) => !slot.isMine)
        .forEach((slot) => {
            const key = slot.faculty?.name ?? 'Faculty';
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(slot);
        });
    return [...groups.entries()].map(([faculty, items]) => ({ faculty, items }));
});

const book = (slot) => {
    router.post(route('office-hours.book', slot.id), {}, { preserveScroll: true });
};

const cancel = async (slot) => {
    const ok = await confirm({
        title: 'Cancel booking',
        message: `Cancel your ${slot.date} ${slot.start} meeting with ${slot.faculty?.name}?`,
        confirmLabel: 'Cancel booking',
        variant: 'danger',
    });
    if (ok) {
        router.post(route('office-hours.cancel', slot.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <Head title="Office Hours" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Office Hours"
                    subtitle="Book a one-on-one slot with the faculty who teach your sections."
                    :icon="ClockIcon"
                />

                <!-- My bookings -->
                <Card v-if="myBookings.length > 0" title="My bookings" :icon="CalendarDaysIcon">
                    <div class="space-y-2">
                        <div
                            v-for="slot in myBookings"
                            :key="slot.id"
                            class="flex items-center justify-between gap-3 rounded-card bg-primary-soft p-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-content">
                                    {{ slot.date }} · {{ slot.start }}–{{ slot.end }} with {{ slot.faculty?.name }}
                                </p>
                                <p v-if="slot.location" class="mt-0.5 flex items-center gap-1 text-xs text-content-muted">
                                    <MapPinIcon class="w-3.5 h-3.5" /> {{ slot.location }}
                                </p>
                            </div>
                            <button @click="cancel(slot)" class="ui-btn-secondary flex-shrink-0">Cancel</button>
                        </div>
                    </div>
                </Card>

                <EmptyState
                    v-if="slots.length === 0"
                    :icon="ClockIcon"
                    title="No office hours published yet"
                    description="When your faculty publish bookable slots, they show up here."
                />

                <!-- Open slots grouped by faculty -->
                <Card v-for="group in openByFaculty" :key="group.faculty" :title="group.faculty">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="slot in group.items"
                            :key="slot.id"
                            class="rounded-card border border-line p-4"
                        >
                            <p class="font-semibold text-content">{{ slot.date }}</p>
                            <p class="text-sm text-content-muted">{{ slot.start }}–{{ slot.end }}</p>
                            <p v-if="slot.location" class="mt-1 flex items-center gap-1 text-xs text-content-muted">
                                <MapPinIcon class="w-3.5 h-3.5" /> {{ slot.location }}
                            </p>
                            <p v-if="slot.note" class="mt-1 text-xs text-content-faint">{{ slot.note }}</p>
                            <div class="mt-3">
                                <Badge v-if="slot.isBooked" variant="neutral">Taken</Badge>
                                <button v-else @click="book(slot)" class="ui-btn-primary w-full">Book</button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
