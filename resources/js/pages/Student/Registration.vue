<script setup>
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    PlusCircleIcon,
    AcademicCapIcon,
    UserIcon,
    UserGroupIcon,
    CheckCircleIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    available: { type: Array, default: () => [] },
    registered: { type: Array, default: () => [] },
    registrationOpen: { type: Boolean, default: false },
    term: { type: String, default: null },
    semester: { type: [Number, String], default: null },
});

const toast = useToast();

const registeredCredits = computed(() => props.registered.reduce((n, c) => n + (c.credits || 0), 0));

const registerForm = useForm({ section_id: null });

const register = (section) => {
    registerForm.section_id = section.sectionId;
    registerForm.post(route('register.store'), {
        preserveScroll: true,
        onSuccess: () => toast.success(`Registered for ${section.code}.`),
        onError: (errors) => toast.error(Object.values(errors)[0] || 'Could not register.'),
    });
};

const drop = (course) => {
    if (!confirm(`Drop ${course.code} — ${course.name}?`)) return;
    router.delete(route('register.drop', course.sectionId), {
        preserveScroll: true,
        onSuccess: () => toast.success(`Dropped ${course.code}.`),
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
                        description="Pick from the available courses below to build your schedule."
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

                <!-- Available courses -->
                <Card title="Available for your semester" :icon="PlusCircleIcon">
                    <EmptyState
                        v-if="available.length === 0"
                        title="Nothing left to register"
                        :description="registrationOpen ? 'You are registered for all offered courses for your semester.' : 'Registration is closed.'"
                        :icon="CheckCircleIcon"
                    />

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="section in available" :key="section.sectionId" class="rounded-card border border-line bg-surface p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-content">{{ section.code }}</span>
                                        <Badge variant="violet">{{ section.credits }} cr</Badge>
                                        <Badge variant="slate">Section {{ section.label }}</Badge>
                                    </div>
                                    <h3 class="font-bold text-content truncate mt-0.5">{{ section.name }}</h3>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-content-muted mt-2">
                                <span class="inline-flex items-center gap-1.5"><UserIcon class="w-4 h-4" /> {{ section.faculty || 'TBA' }}</span>
                                <span class="inline-flex items-center gap-1.5"><UserGroupIcon class="w-4 h-4" /> {{ section.seatsLeft }} seat(s) left</span>
                            </div>
                            <button
                                type="button"
                                @click="register(section)"
                                class="ui-btn-primary w-full mt-3"
                                :disabled="!registrationOpen || registerForm.processing"
                            >
                                <PlusCircleIcon class="w-4 h-4" /> Register
                            </button>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>
