<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ChatBubbleLeftRightIcon,
    HandThumbUpIcon,
    ChatBubbleOvalLeftIcon,
    FlagIcon,
    TrashIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline';
import { HandThumbUpIcon as HandThumbUpSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    activeSectionId: { type: [Number, null], default: null },
    canModerate: { type: Boolean, default: false },
    posts: { type: Object, default: () => ({ data: [] }) },
});

const { confirm } = useConfirm();

const activeSection = ref(props.activeSectionId);
const postItems = computed(() => props.posts.data ?? []);

const switchSection = () => {
    router.get(route('discussions.index'), { section_id: activeSection.value }, { preserveScroll: true, preserveState: false });
};

// New post composer
const postForm = useForm({ body: '' });
const submitPost = () => {
    if (!activeSection.value) return;
    postForm.post(route('discussions.posts.store', activeSection.value), {
        preserveScroll: true,
        onSuccess: () => postForm.reset(),
    });
};

// Per-post comment drafts
const commentDrafts = ref({});
const submitComment = (postId) => {
    const body = (commentDrafts.value[postId] ?? '').trim();
    if (!body) return;
    router.post(route('discussions.comments.store', postId), { body }, {
        preserveScroll: true,
        onSuccess: () => { commentDrafts.value[postId] = ''; },
    });
};

const toggleLike = (post) => {
    router.post(route('discussions.posts.like', post.id), {}, { preserveScroll: true });
};

const togglePin = (post) => {
    router.patch(route('discussions.posts.pin', post.id), {}, { preserveScroll: true });
};

const removePost = async (post) => {
    const ok = await confirm({ title: 'Remove post', message: 'Remove this post?', confirmLabel: 'Remove', variant: 'danger' });
    if (ok) router.delete(route('discussions.posts.destroy', post.id), { preserveScroll: true });
};

const removeComment = async (comment) => {
    const ok = await confirm({ title: 'Remove comment', message: 'Remove this comment?', confirmLabel: 'Remove', variant: 'danger' });
    if (ok) router.delete(route('discussions.comments.destroy', comment.id), { preserveScroll: true });
};

const report = (routeName, id) => {
    const reason = window.prompt('Why are you reporting this? (a moderator will review it)');
    if (reason && reason.trim()) {
        router.post(route(routeName, id), { reason: reason.trim() }, { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <Head title="Discussions" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <PageHeader
                    title="Discussions"
                    subtitle="Ask questions and share ideas with your section."
                    :icon="ChatBubbleLeftRightIcon"
                />

                <EmptyState
                    v-if="sections.length === 0"
                    title="No discussion groups"
                    description="You'll see a group here for each section you're enrolled in or teach."
                    :icon="ChatBubbleLeftRightIcon"
                />

                <template v-else>
                    <div class="flex items-center gap-2 flex-wrap">
                        <label class="ui-label mb-0" for="section">Group</label>
                        <select id="section" v-model.number="activeSection" class="ui-input w-auto" @change="switchSection">
                            <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.label }}</option>
                        </select>
                    </div>

                    <!-- Composer -->
                    <Card>
                        <form @submit.prevent="submitPost" class="space-y-3">
                            <textarea
                                v-model="postForm.body"
                                rows="3"
                                placeholder="Share something with your section…"
                                class="ui-input resize-none"
                            ></textarea>
                            <p v-if="postForm.errors.body" class="text-xs text-danger-fg">{{ postForm.errors.body }}</p>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="postForm.processing || !postForm.body.trim()" class="ui-btn-primary">
                                    {{ postForm.processing ? 'Posting…' : 'Post' }}
                                </button>
                            </div>
                        </form>
                    </Card>

                    <!-- Feed -->
                    <EmptyState
                        v-if="postItems.length === 0"
                        title="No posts yet"
                        description="Be the first to start a discussion in this group."
                        :icon="ChatBubbleOvalLeftIcon"
                    />

                    <Card v-for="post in postItems" :key="post.id" :class="post.isPinned ? 'ring-1 ring-primary/40' : ''">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-content">{{ post.author.name }}</p>
                                    <Badge v-if="post.isPinned" variant="primary" class="gap-1"><MapPinIcon class="w-3 h-3" /> Pinned</Badge>
                                    <span class="text-xs text-content-faint">{{ post.createdAt }}</span>
                                </div>
                                <p class="text-content mt-2 whitespace-pre-line">{{ post.body }}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button v-if="post.canModerate" type="button" class="ui-btn-ghost p-1.5" :title="post.isPinned ? 'Unpin' : 'Pin'" @click="togglePin(post)">
                                    <MapPinIcon class="w-4 h-4" />
                                </button>
                                <button type="button" class="ui-btn-ghost p-1.5 text-content-faint" title="Report" @click="report('discussions.posts.report', post.id)">
                                    <FlagIcon class="w-4 h-4" />
                                </button>
                                <button v-if="post.canDelete" type="button" class="ui-btn-ghost p-1.5 text-content-faint hover:text-danger-fg" title="Remove" @click="removePost(post)">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4 mt-3 text-sm">
                            <button type="button" class="inline-flex items-center gap-1.5" :class="post.likedByViewer ? 'text-primary font-medium' : 'text-content-muted'" @click="toggleLike(post)">
                                <component :is="post.likedByViewer ? HandThumbUpSolid : HandThumbUpIcon" class="w-4 h-4" />
                                {{ post.likeCount }}
                            </button>
                            <span class="inline-flex items-center gap-1.5 text-content-muted">
                                <ChatBubbleOvalLeftIcon class="w-4 h-4" /> {{ post.comments.length }}
                            </span>
                        </div>

                        <!-- Comments -->
                        <div class="mt-4 space-y-3 border-t border-line pt-4">
                            <div v-for="comment in post.comments" :key="comment.id" class="flex items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm">
                                        <span class="font-medium text-content">{{ comment.author.name }}</span>
                                        <span class="text-xs text-content-faint ml-2">{{ comment.createdAt }}</span>
                                    </p>
                                    <p class="text-sm text-content-muted whitespace-pre-line">{{ comment.body }}</p>
                                </div>
                                <button type="button" class="ui-btn-ghost p-1 text-content-faint" title="Report" @click="report('discussions.comments.report', comment.id)">
                                    <FlagIcon class="w-3.5 h-3.5" />
                                </button>
                                <button v-if="comment.canDelete" type="button" class="ui-btn-ghost p-1 text-content-faint hover:text-danger-fg" title="Remove" @click="removeComment(comment)">
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <form class="flex items-center gap-2" @submit.prevent="submitComment(post.id)">
                                <input
                                    v-model="commentDrafts[post.id]"
                                    type="text"
                                    placeholder="Write a comment…"
                                    class="ui-input flex-1"
                                />
                                <button type="submit" class="ui-btn-secondary">Reply</button>
                            </form>
                        </div>
                    </Card>

                    <Pagination :paginator="posts" label="posts" />
                </template>
            </div>
        </AppLayout>
    </div>
</template>
