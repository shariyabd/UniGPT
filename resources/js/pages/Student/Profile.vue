<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    UserCircleIcon,
    EnvelopeIcon,
    AcademicCapIcon,
    BuildingLibraryIcon,
    IdentificationIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    bio: props.user.bio ?? '',
    avatar: props.user.avatar ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};

const avatarUrl = () =>
    form.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(props.user.name || 'U')}&background=6366f1&color=fff&size=128`;
</script>

<template>
    <div>
        <Head title="Profile" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Profile"
                    subtitle="Manage your personal information"
                    :icon="UserCircleIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Identity / header card -->
                    <Card class="lg:col-span-1">
                        <div class="flex flex-col items-center text-center">
                            <img
                                :src="avatarUrl()"
                                :alt="user.name"
                                class="w-28 h-28 rounded-full mb-4 ring-4 ring-primary/20 object-cover"
                            />
                            <h2 class="text-xl font-bold text-content">{{ user.name }}</h2>
                            <p class="text-sm text-content-muted">{{ user.email }}</p>

                            <div class="flex flex-wrap justify-center gap-2 pt-4">
                                <Badge v-for="role in user.roles" :key="role.id" variant="brand">
                                    {{ role.name }}
                                </Badge>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3 border-t border-line pt-6">
                            <div class="flex items-center gap-3 text-sm">
                                <IdentificationIcon class="w-5 h-5 text-primary flex-shrink-0" />
                                <span class="text-content-muted">{{ user.student_id || user.employee_id || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <BuildingLibraryIcon class="w-5 h-5 text-primary flex-shrink-0" />
                                <span class="text-content-muted">{{ user.department?.name || 'No department' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <AcademicCapIcon class="w-5 h-5 text-primary flex-shrink-0" />
                                <span class="text-content-muted">{{ user.semester || 'N/A' }}</span>
                            </div>
                        </div>
                    </Card>

                    <!-- Edit form -->
                    <Card
                        class="lg:col-span-2"
                        title="Edit Profile"
                        subtitle="Update your name, avatar and bio"
                        :icon="UserCircleIcon"
                    >
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="ui-label" for="profile-name">Full Name</label>
                                <input id="profile-name" v-model="form.name" type="text" class="ui-input" />
                                <p v-if="form.errors.name" class="text-sm text-danger-fg mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Email</label>
                                <div class="flex items-center gap-2 rounded-control border border-line bg-bg px-4 py-3 text-content-muted">
                                    <EnvelopeIcon class="w-5 h-5 flex-shrink-0" />
                                    <span>{{ user.email }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="ui-label" for="profile-avatar">Avatar URL</label>
                                <input id="profile-avatar" v-model="form.avatar" type="url" placeholder="https://…" class="ui-input" />
                                <p v-if="form.errors.avatar" class="text-sm text-danger-fg mt-1">{{ form.errors.avatar }}</p>
                            </div>

                            <div>
                                <label class="ui-label" for="profile-bio">Bio</label>
                                <textarea id="profile-bio" v-model="form.bio" rows="4" class="ui-input resize-none"></textarea>
                                <p v-if="form.errors.bio" class="text-sm text-danger-fg mt-1">{{ form.errors.bio }}</p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" :disabled="form.processing" class="ui-btn-primary disabled:opacity-50">
                                    {{ form.processing ? 'Saving…' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
