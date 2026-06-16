<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { ArrowLeftIcon, BookOpenIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    course: {
        type: Object,
        default: null,
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const isEdit = computed(() => props.course !== null);

const form = useForm({
    code: props.course?.code ?? '',
    name: props.course?.name ?? '',
    description: props.course?.description ?? '',
    department_id: props.course?.department_id ?? null,
    semester: props.course?.semester ?? null,
    credits: props.course?.credits ?? 3,
    max_enrollment: props.course?.max_enrollment ?? 60,
    is_active: props.course?.is_active ?? true,
    schedule: {
        lectures: props.course?.schedule?.lectures ?? '',
        classroom: props.course?.schedule?.classroom ?? '',
        office_hours: props.course?.schedule?.office_hours ?? '',
    },
});

const submit = () => {
    if (isEdit.value) {
        form.patch(route('faculty.courses.update', props.course.id));
    } else {
        form.post(route('faculty.courses.store'));
    }
};
</script>

<template>
    <div>
        <Head :title="isEdit ? 'Edit Course' : 'New Course'" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <div class="max-w-3xl">
                    <Link
                        :href="isEdit ? route('faculty.courses.show', course.id) : route('faculty.courses')"
                        class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-content-muted transition-colors hover:text-primary"
                    >
                        <ArrowLeftIcon class="h-4 w-4" /> Back
                    </Link>

                    <PageHeader
                        :title="isEdit ? 'Edit Course' : 'Create Course'"
                        :subtitle="isEdit ? `Editing ${course.code}` : 'Set up a new course offering for your department.'"
                        :icon="BookOpenIcon"
                    />
                </div>

                <form @submit.prevent="submit" class="max-w-3xl space-y-6">
                    <Card>
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="code" class="ui-label">Code</label>
                                    <input id="code" v-model="form.code" type="text" placeholder="CS301" class="ui-input" />
                                    <p v-if="form.errors.code" class="mt-1 text-xs text-danger">{{ form.errors.code }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="name" class="ui-label">Name</label>
                                    <input id="name" v-model="form.name" type="text" placeholder="Data Structures & Algorithms" class="ui-input" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="description" class="ui-label">Description</label>
                                <textarea id="description" v-model="form.description" rows="3" class="ui-input resize-none"></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-xs text-danger">{{ form.errors.description }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="department_id" class="ui-label">Department</label>
                                    <select id="department_id" v-model="form.department_id" class="ui-input">
                                        <option :value="null">— None —</option>
                                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="semester" class="ui-label">Semester</label>
                                    <input id="semester" v-model.number="form.semester" type="number" min="1" max="12" class="ui-input" />
                                    <p v-if="form.errors.semester" class="mt-1 text-xs text-danger">{{ form.errors.semester }}</p>
                                </div>
                                <div>
                                    <label for="credits" class="ui-label">Credits</label>
                                    <input id="credits" v-model.number="form.credits" type="number" min="1" max="12" class="ui-input" />
                                    <p v-if="form.errors.credits" class="mt-1 text-xs text-danger">{{ form.errors.credits }}</p>
                                </div>
                                <div>
                                    <label for="max_enrollment" class="ui-label">Max Enrollment</label>
                                    <input id="max_enrollment" v-model.number="form.max_enrollment" type="number" min="1" max="1000" class="ui-input" />
                                    <p v-if="form.errors.max_enrollment" class="mt-1 text-xs text-danger">{{ form.errors.max_enrollment }}</p>
                                </div>
                            </div>

                            <fieldset class="rounded-card border border-line p-4">
                                <legend class="flex items-center gap-1.5 px-2 text-sm font-medium text-content">
                                    <CalendarDaysIcon class="h-4 w-4 text-primary" />
                                    Schedule
                                </legend>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <input v-model="form.schedule.lectures" type="text" placeholder="Mon/Wed 10:00–11:30" class="ui-input" />
                                    <input v-model="form.schedule.classroom" type="text" placeholder="Room 101" class="ui-input" />
                                    <input v-model="form.schedule.office_hours" type="text" placeholder="Tue 14:00–16:00" class="ui-input" />
                                </div>
                            </fieldset>

                            <label class="flex cursor-pointer items-center gap-2">
                                <input v-model="form.is_active" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                <span class="text-sm font-medium text-content">Active (visible &amp; open for enrollment)</span>
                            </label>
                        </div>

                        <template #actions>
                            <div class="flex w-full items-center justify-end gap-3">
                                <Link
                                    :href="isEdit ? route('faculty.courses.show', course.id) : route('faculty.courses')"
                                    class="ui-btn-secondary"
                                >
                                    Cancel
                                </Link>
                                <button type="submit" :disabled="form.processing" class="ui-btn-primary disabled:opacity-50">
                                    {{ form.processing ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Course') }}
                                </button>
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </AppLayout>
    </div>
</template>
