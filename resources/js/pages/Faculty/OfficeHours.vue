<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import { ClockIcon, PlusIcon, TrashIcon, MapPinIcon } from '@heroicons/vue/24/outline';

defineProps({
    slots: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const form = useForm({
    starts_at: '',
    ends_at: '',
    location: '',
    note: '',
});

const publish = () => {
    form.post(route('faculty.office-hours.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const removeSlot = async (slot) => {
    const ok = await confirm({
        title: 'Remove slot',
        message: slot.isBooked
            ? 'This slot is booked — the student will be notified that the meeting is cancelled.'
            : 'Remove this open slot?',
        confirmLabel: 'Remove',
        variant: 'danger',
    });
    if (ok) {
        router.delete(route('faculty.office-hours.destroy', slot.id), { preserveScroll: true });
    }
};

const cancelBooking = async (slot) => {
    const ok = await confirm({
        title: 'Cancel booking',
        message: `Cancel ${slot.student?.name}'s booking? The slot re-opens and the student is notified.`,
        confirmLabel: 'Cancel booking',
        variant: 'danger',
    });
    if (ok) {
        router.post(route('faculty.office-hours.cancel-booking', slot.id), {}, { preserveScroll: true });
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
                    subtitle="Publish bookable time slots; students of your sections book them and you both get notified."
                    :icon="ClockIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="h-fit">
                        <template #header>
                            <div class="flex items-center gap-2 font-semibold text-content">
                                <PlusIcon class="w-5 h-5 text-primary" />
                                Publish a slot
                            </div>
                        </template>
                        <form @submit.prevent="publish" class="space-y-3">
                            <div>
                                <label for="oh-start" class="block text-sm font-medium text-content mb-1">Starts</label>
                                <input id="oh-start" v-model="form.starts_at" type="datetime-local" required class="ui-input w-full" />
                                <p v-if="form.errors.starts_at" class="mt-1 text-xs text-danger-fg">{{ form.errors.starts_at }}</p>
                            </div>
                            <div>
                                <label for="oh-end" class="block text-sm font-medium text-content mb-1">Ends</label>
                                <input id="oh-end" v-model="form.ends_at" type="datetime-local" required class="ui-input w-full" />
                                <p v-if="form.errors.ends_at" class="mt-1 text-xs text-danger-fg">{{ form.errors.ends_at }}</p>
                            </div>
                            <div>
                                <label for="oh-location" class="block text-sm font-medium text-content mb-1">Location / link (optional)</label>
                                <input id="oh-location" v-model="form.location" type="text" maxlength="120" placeholder="Room 402 or meeting link" class="ui-input w-full" />
                            </div>
                            <div>
                                <label for="oh-note" class="block text-sm font-medium text-content mb-1">Note (optional)</label>
                                <input id="oh-note" v-model="form.note" type="text" maxlength="200" placeholder="e.g. Project consultations" class="ui-input w-full" />
                            </div>
                            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing || !form.starts_at || !form.ends_at">
                                Publish slot
                            </button>
                        </form>
                    </Card>

                    <div class="lg:col-span-2 space-y-4">
                        <EmptyState
                            v-if="slots.length === 0"
                            :icon="ClockIcon"
                            title="No upcoming slots"
                            description="Publish your first office-hours slot — students of your sections will see it instantly."
                        />

                        <Card v-for="slot in slots" :key="slot.id">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-content">{{ slot.date }} · {{ slot.start }}–{{ slot.end }}</p>
                                    <p v-if="slot.location" class="mt-1 flex items-center gap-1 text-sm text-content-muted">
                                        <MapPinIcon class="w-4 h-4" /> {{ slot.location }}
                                    </p>
                                    <p v-if="slot.note" class="mt-1 text-sm text-content-muted">{{ slot.note }}</p>
                                    <div class="mt-2">
                                        <Badge v-if="slot.isBooked" variant="primary">
                                            Booked — {{ slot.student?.name }} ({{ slot.student?.student_id }})
                                        </Badge>
                                        <Badge v-else variant="success">Open</Badge>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button v-if="slot.isBooked" @click="cancelBooking(slot)" class="ui-btn-secondary">
                                        Cancel booking
                                    </button>
                                    <button
                                        @click="removeSlot(slot)"
                                        class="p-2 rounded-control text-content-faint hover:text-danger-fg hover:bg-danger-bg transition-colors"
                                        title="Remove slot"
                                        aria-label="Remove slot"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
