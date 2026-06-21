<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    CalendarDaysIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
    CheckCircleIcon,
    ArchiveBoxArrowDownIcon,
    UserGroupIcon,
    RectangleStackIcon,
    LockOpenIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    terms: { type: Array, default: () => [] },
    // The standard term names not yet used (Fall / Spring / Summer).
    availableNames: { type: Array, default: () => [] },
    // Whether a term can still be added (fewer than the three standards exist).
    canAddTerm: { type: Boolean, default: false },
});

const { confirm, notify } = useConfirm();

/* ---- Create ---- */
const showCreate = ref(false);
const createForm = useForm({ name: '', start_date: '', end_date: '' });

const nameOptions = computed(() => props.availableNames.map((name) => ({ value: name, label: name })));

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    // Pre-select the first available standard name.
    createForm.name = props.availableNames[0] ?? '';
    showCreate.value = true;
};
const closeCreate = () => { showCreate.value = false; createForm.reset(); createForm.clearErrors(); };
const submitCreate = () => {
    createForm.post(route('admin.terms.store'), { preserveScroll: true, onSuccess: closeCreate });
};

const setCurrent = (term) => {
    router.patch(route('admin.terms.current', term.id), {}, { preserveScroll: true });
};

const toggleRegistration = (term) => {
    router.patch(route('admin.terms.registration', term.id), {}, { preserveScroll: true });
};

const removeTerm = async (term) => {
    if (term.isCurrent || term.sections > 0) {
        await notify({ title: 'Cannot delete term', message: 'A current term, or a term that still has sections, cannot be deleted.' });
        return;
    }
    const ok = await confirm({ title: 'Delete term', message: `Delete "${term.name}"?`, confirmLabel: 'Delete', variant: 'danger' });
    if (!ok) return;
    router.delete(route('admin.terms.destroy', term.id), { preserveScroll: true });
};

/* ---- Close / roll over ---- */
const showClose = ref(false);
const closingTerm = ref(null);
const closeForm = useForm({ next_term_id: null });

const otherTerms = computed(() => props.terms.filter((t) => t.id !== closingTerm.value?.id));

const nextTermOptions = computed(() =>
    otherTerms.value.map((t) => ({ value: t.id, label: t.name })),
);

const openClose = (term) => {
    closingTerm.value = term;
    closeForm.reset();
    closeForm.clearErrors();
    showClose.value = true;
};
const closeCloseModal = () => { showClose.value = false; closingTerm.value = null; closeForm.reset(); };
const submitClose = () => {
    closeForm.post(route('admin.terms.close', closingTerm.value.id), { preserveScroll: true, onSuccess: closeCloseModal });
};
</script>

<template>
    <div>
        <Head title="Academic Terms" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Academic Terms"
                    subtitle="Manage terms and run the end-of-term rollover."
                    :icon="CalendarDaysIcon"
                    :count="terms.length"
                >
                    <template #actions>
                        <button v-if="canAddTerm" type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" /> New term
                        </button>
                    </template>
                </PageHeader>

                <EmptyState
                    v-if="terms.length === 0"
                    title="No terms yet"
                    description="Create your first academic term to start scheduling sections."
                    :icon="CalendarDaysIcon"
                >
                    <button type="button" @click="openCreate" class="ui-btn-primary">
                        <PlusIcon class="w-4 h-4" /> New term
                    </button>
                </EmptyState>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    <Card v-for="term in terms" :key="term.id" class="flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-content truncate">{{ term.name }}</p>
                                    <Badge v-if="term.isCurrent" variant="success" dot>Current</Badge>
                                </div>
                                <p v-if="term.startDate || term.endDate" class="text-xs text-content-muted mt-1">
                                    {{ term.startDate || '—' }} → {{ term.endDate || '—' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="removeTerm(term)"
                                aria-label="Delete term"
                                class="ui-btn-danger p-2 disabled:opacity-30 flex-shrink-0"
                                :disabled="term.isCurrent || term.sections > 0"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 mt-4 text-xs text-content-muted">
                            <span class="inline-flex items-center gap-1.5"><RectangleStackIcon class="w-4 h-4" /> {{ term.sections }} section(s)</span>
                            <span class="inline-flex items-center gap-1.5"><UserGroupIcon class="w-4 h-4" /> {{ term.activeEnrollments }} active</span>
                            <Badge :variant="term.isRegistrationOpen ? 'success' : 'slate'" dot>
                                Registration {{ term.isRegistrationOpen ? 'open' : 'closed' }}
                            </Badge>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-line">
                            <button
                                v-if="!term.isCurrent"
                                type="button"
                                @click="setCurrent(term)"
                                class="ui-btn-secondary text-sm"
                            >
                                <CheckCircleIcon class="w-4 h-4" /> Set current
                            </button>
                            <button
                                type="button"
                                @click="toggleRegistration(term)"
                                class="ui-btn-secondary text-sm"
                            >
                                <LockOpenIcon v-if="!term.isRegistrationOpen" class="w-4 h-4" />
                                <LockClosedIcon v-else class="w-4 h-4" />
                                {{ term.isRegistrationOpen ? 'Close registration' : 'Open registration' }}
                            </button>
                            <button
                                v-if="term.isCurrent || term.activeEnrollments > 0"
                                type="button"
                                @click="openClose(term)"
                                class="ui-btn-secondary text-sm"
                            >
                                <ArchiveBoxArrowDownIcon class="w-4 h-4" /> Close / roll over
                            </button>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Create modal -->
            <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeCreate"></div>
                    <div class="relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">New term</h3>
                            <button type="button" @click="closeCreate" class="ui-btn-ghost p-2" aria-label="Close"><XMarkIcon class="h-5 w-5" /></button>
                        </div>
                        <form @submit.prevent="submitCreate" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <div>
                                    <label class="ui-label">Name *</label>
                                    <SearchableSelect
                                        v-model="createForm.name"
                                        :options="nameOptions"
                                        placeholder="Select a term"
                                    />
                                    <p class="mt-1 text-xs text-content-muted">Terms are standardised to Fall, Spring and Summer.</p>
                                    <p v-if="createForm.errors.name" class="text-xs text-danger-fg mt-1">{{ createForm.errors.name }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Start date</label>
                                        <input v-model="createForm.start_date" type="date" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label">End date</label>
                                        <input v-model="createForm.end_date" type="date" class="ui-input" />
                                        <p v-if="createForm.errors.end_date" class="text-xs text-danger-fg mt-1">{{ createForm.errors.end_date }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeCreate" class="ui-btn-ghost">Cancel</button>
                                <button type="submit" :disabled="createForm.processing" class="ui-btn-primary">
                                    {{ createForm.processing ? 'Saving…' : 'Create term' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </Teleport>

            <!-- Close / rollover modal -->
            <Teleport to="body">
            <div v-if="showClose" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeCloseModal"></div>
                    <div class="relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">Close {{ closingTerm?.name }}</h3>
                            <button type="button" @click="closeCloseModal" class="ui-btn-ghost p-2" aria-label="Close"><XMarkIcon class="h-5 w-5" /></button>
                        </div>
                        <form @submit.prevent="submitClose" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <p class="text-sm text-content-muted">
                                    Closing this term will mark its <strong>{{ closingTerm?.sections }} section(s)</strong> as historical and
                                    complete its <strong>{{ closingTerm?.activeEnrollments }} active enrollment(s)</strong>. Grades are preserved.
                                </p>
                                <div>
                                    <label class="ui-label">Promote next term to current (optional)</label>
                                    <SearchableSelect
                                        v-model="closeForm.next_term_id"
                                        :options="nextTermOptions"
                                        placeholder="Don't change current term"
                                        clearable
                                    />
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeCloseModal" class="ui-btn-ghost">Cancel</button>
                                <button type="submit" :disabled="closeForm.processing" class="ui-btn-primary">
                                    {{ closeForm.processing ? 'Closing…' : 'Close term' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </Teleport>
        </AppLayout>
    </div>
</template>
