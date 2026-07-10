<script setup>
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    PlusCircleIcon,
    AcademicCapIcon,
    UserIcon,
    UserGroupIcon,
    CheckCircleIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    // Courses the admin has assigned to this student (pending placements),
    // awaiting the student's confirmation.
    assigned: { type: Array, default: () => [] },
    registered: { type: Array, default: () => [] },
    waitlisted: { type: Array, default: () => [] },
    registrationOpen: { type: Boolean, default: false },
    term: { type: String, default: null },
    semester: { type: [Number, String], default: null },
});

const toast = useToast();
const { confirm } = useConfirm();

const registeredCredits = computed(() => props.registered.reduce((n, c) => n + (c.credits || 0), 0));

const unmetPrereqs = (section) => (section.prerequisites || []).filter((p) => !p.met);

const registerForm = useForm({ section_id: null });

const register = (section) => {
    registerForm.section_id = section.sectionId;
    registerForm.post(route('register.store'), {
        preserveScroll: true,
        // Success toast comes from the server flash (centralized pipeline) — firing
        // one here too would double it.
        onError: (errors) => toast.error(Object.values(errors)[0] || 'Could not register.'),
    });
};

const drop = async (course) => {
    const ok = await confirm({
        title: 'Drop course',
        message: `Drop ${course.code} — ${course.name}?`,
        confirmLabel: 'Drop',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('register.drop', course.sectionId), {
        preserveScroll: true,
        // Success toast comes from the server flash (centralized pipeline).
        onError: (errors) => toast.error(Object.values(errors)[0] || 'Could not drop.'),
    });
};
</script>

<template>
    <div>
        <Head title="Course Registration" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Course Registration"
                    :subtitle="term ? `${term}${semester ? ` · Semester ${semester}` : ''}` : 'Register for your semester\'s courses.'"
                    :icon="PlusCircleIcon"
                >
                    <template #actions>
                        <Badge :variant="registrationOpen ? 'success' : 'danger'" dot>
                            {{ registrationOpen ? 'Registration open' : 'Registration closed' }}
                        </Badge>
                    </template>
                </PageHeader>

                <!-- Closed banner -->
                <div v-if="!registrationOpen" class="ui-card flex items-center gap-3 p-4 text-sm text-content-muted">
                    <LockClosedIcon class="h-5 w-5 flex-shrink-0 text-content-faint" />
                    Registration is currently closed. You can view your registered courses below, but cannot add or drop.
                </div>

                <!-- Registered courses -->
                <Card title="Your registered courses" :icon="CheckCircleIcon">
                    <template #actions>
                        <span class="text-sm text-content-muted">{{ registered.length }} course(s) · {{ registeredCredits }} credits</span>
                    </template>

                    <EmptyState
                        v-if="registered.length === 0"
                        title="No courses registered"
                        description="Register for the courses assigned to you below to build your schedule."
                        :icon="AcademicCapIcon"
                    />

                    <div v-else class="divide-y divide-line">
                        <div v-for="course in registered" :key="course.sectionId" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-content">{{ course.code }}</span>
                                    <Badge variant="violet">{{ course.credits }} cr</Badge>
                                    <Badge v-if="course.label" variant="slate">Section {{ course.label }}</Badge>
                                </div>
                                <p class="text-sm text-content-muted mt-0.5 truncate">
                                    {{ course.name }}<span v-if="course.faculty"> · {{ course.faculty }}</span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="drop(course)"
                                class="ui-btn-secondary text-sm"
                                :disabled="!registrationOpen"
                            >
                                Drop
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Assigned courses awaiting confirmation -->
                <Card title="Assigned to you — register to confirm" :icon="PlusCircleIcon">
                    <EmptyState
                        v-if="assigned.length === 0"
                        title="Nothing to register"
                        :description="registrationOpen ? 'The registrar has not assigned you any courses to register for yet.' : 'Registration is closed.'"
                        :icon="CheckCircleIcon"
                    />

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="section in assigned" :key="section.sectionId" class="rounded-card border border-line bg-surface p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-content">{{ section.code }}</span>
                                        <Badge variant="violet">{{ section.credits }} cr</Badge>
                                        <Badge variant="slate">Section {{ section.label }}</Badge>
                                        <Badge v-if="section.semester" variant="info">Semester {{ section.semester }}</Badge>
                                    </div>
                                    <h3 class="font-bold text-content truncate mt-0.5">{{ section.name }}</h3>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-content-muted mt-2">
                                <span class="inline-flex items-center gap-1.5"><UserIcon class="w-4 h-4" /> {{ section.faculty || 'TBA' }}</span>
                                <span class="inline-flex items-center gap-1.5"><UserGroupIcon class="w-4 h-4" /> {{ section.seatsLeft }} seat(s) left</span>
                            </div>
                            <div v-if="section.prerequisites && section.prerequisites.length" class="flex flex-wrap items-center gap-1.5 mt-2">
                                <span class="text-xs font-medium text-content-muted">Prerequisites:</span>
                                <Badge
                                    v-for="prereq in section.prerequisites"
                                    :key="prereq.code"
                                    :variant="prereq.met ? 'success' : 'danger'"
                                >
                                    {{ prereq.code }} {{ prereq.met ? '✓' : '✗' }}
                                </Badge>
                            </div>
                            <button
                                type="button"
                                @click="register(section)"
                                class="ui-btn-primary w-full mt-3"
                                :disabled="!registrationOpen || registerForm.processing || unmetPrereqs(section).length > 0"
                            >
                                <PlusCircleIcon class="w-4 h-4" /> Register
                            </button>
                            <p v-if="unmetPrereqs(section).length" class="mt-1.5 text-xs text-danger-fg">
                                Complete {{ unmetPrereqs(section).map((p) => p.code).join(', ') }} first.
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- Waitlists -->
                <Card v-if="waitlisted.length" title="Waitlisted">
                    <div class="divide-y divide-line">
                        <div v-for="entry in waitlisted" :key="entry.sectionId" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <span class="font-semibold text-content">{{ entry.code }}</span>
                                <span class="text-sm text-content-muted"> — {{ entry.name }}<span v-if="entry.label"> · Section {{ entry.label }}</span></span>
                            </div>
                            <Badge variant="warning">#{{ entry.position }} in queue</Badge>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-content-muted">
                        You'll be assigned automatically (and notified) when a seat opens.
                    </p>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
