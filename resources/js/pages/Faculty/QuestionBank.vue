<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    RectangleStackIcon,
    PlusIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    tests: { type: Array, default: () => [] },
    courseFilter: { type: [Number, null], default: null },
});

const { confirm } = useConfirm();

// --- Filter ---------------------------------------------------------------
const activeCourse = ref(props.courseFilter);
const filterByCourse = () => {
    router.get(route('faculty.question-bank.index'), activeCourse.value ? { course: activeCourse.value } : {}, {
        preserveState: false,
        preserveScroll: true,
    });
};

// --- Add question ----------------------------------------------------------
const OPTION_KEYS = ['A', 'B', 'C', 'D', 'E', 'F'];
const showAddForm = ref(false);

const addForm = useForm({
    course_id: null,
    type: 'mcq',
    question_text: '',
    marks: 1,
    options: [
        { key: 'A', text: '' },
        { key: 'B', text: '' },
    ],
    correct_answer: 'A',
    topic: '',
    difficulty: 'medium',
});

const addOption = () => {
    if (addForm.options.length >= OPTION_KEYS.length) return;
    addForm.options.push({ key: OPTION_KEYS[addForm.options.length], text: '' });
};

const switchType = () => {
    addForm.correct_answer = addForm.type === 'mcq' ? 'A' : 'true';
};

const submitAdd = () => {
    addForm.transform((data) => ({
        ...data,
        options: data.type === 'mcq' ? data.options : [],
        topic: data.topic || null,
    })).post(route('faculty.question-bank.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset('question_text', 'topic');
            addForm.options = [{ key: 'A', text: '' }, { key: 'B', text: '' }];
        },
    });
};

// --- Import from an existing test -------------------------------------------
const importTestId = ref(null);
const importFromTest = () => {
    if (!importTestId.value) return;
    router.post(route('faculty.question-bank.import', importTestId.value), {}, { preserveScroll: true });
};

// --- Build a draft test from selected items ---------------------------------
const selected = ref([]);
const toggleSelected = (id) => {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((v) => v !== id)
        : [...selected.value, id];
};

const testForm = useForm({ section_id: null, title: '', item_ids: [] });

// Only sections of the course the selected questions belong to make sense.
const selectableSections = computed(() => {
    const courseIds = new Set(props.items.filter((i) => selected.value.includes(i.id)).map((i) => i.courseId));
    if (courseIds.size !== 1) return [];
    const [courseId] = courseIds;
    return props.sections.filter((s) => s.courseId === courseId);
});

const createTest = () => {
    testForm.item_ids = [...selected.value];
    testForm.post(route('faculty.question-bank.create-test'), {
        onError: () => {},
    });
};

const remove = async (item) => {
    const ok = await confirm({
        title: 'Delete question',
        message: 'Remove this question from the bank? Tests already using a copy of it are unaffected.',
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('faculty.question-bank.destroy', item.id), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Question Bank" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Question Bank"
                    subtitle="Reusable questions per course — add your own, import from past tests, and spin up new drafts."
                    :icon="RectangleStackIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: add + import -->
                    <div class="space-y-6">
                        <Card title="Add a question" :icon="PlusIcon">
                            <form @submit.prevent="submitAdd" class="space-y-3">
                                <div>
                                    <label class="ui-label">Course</label>
                                    <select v-model="addForm.course_id" class="ui-input" required>
                                        <option :value="null" disabled>Select course…</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Type</label>
                                        <select v-model="addForm.type" @change="switchType" class="ui-input">
                                            <option value="mcq">Multiple choice</option>
                                            <option value="true_false">True / False</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Marks</label>
                                        <input v-model.number="addForm.marks" type="number" min="1" max="100" class="ui-input" />
                                    </div>
                                </div>
                                <div>
                                    <label class="ui-label">Question</label>
                                    <textarea v-model="addForm.question_text" rows="3" maxlength="2000" class="ui-input resize-none" required></textarea>
                                </div>

                                <template v-if="addForm.type === 'mcq'">
                                    <div v-for="option in addForm.options" :key="option.key" class="flex items-center gap-2">
                                        <span class="w-6 text-sm font-bold text-content-muted">{{ option.key }}</span>
                                        <input v-model="option.text" type="text" maxlength="500" class="ui-input flex-1" :placeholder="`Option ${option.key}`" />
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <button type="button" @click="addOption" class="text-xs font-semibold text-primary hover:underline" :disabled="addForm.options.length >= 6">+ option</button>
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs font-medium text-content-muted">Correct:</label>
                                            <select v-model="addForm.correct_answer" class="ui-input !w-auto !py-1 text-sm">
                                                <option v-for="option in addForm.options" :key="option.key" :value="option.key">{{ option.key }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                                <div v-else>
                                    <label class="ui-label">Correct answer</label>
                                    <select v-model="addForm.correct_answer" class="ui-input">
                                        <option value="true">True</option>
                                        <option value="false">False</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Topic <span class="font-normal text-content-faint">(optional)</span></label>
                                        <input v-model="addForm.topic" type="text" maxlength="120" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label">Difficulty</label>
                                        <select v-model="addForm.difficulty" class="ui-input">
                                            <option value="easy">Easy</option>
                                            <option value="medium">Medium</option>
                                            <option value="hard">Hard</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" :disabled="addForm.processing || !addForm.course_id" class="ui-btn-primary w-full disabled:opacity-50">
                                    {{ addForm.processing ? 'Saving…' : 'Add to bank' }}
                                </button>
                            </form>
                        </Card>

                        <Card title="Import from a test" :icon="ArrowDownTrayIcon">
                            <p class="text-sm text-content-muted mb-3">Copy an existing class test's questions into its course's bank (duplicates are skipped).</p>
                            <select v-model="importTestId" class="ui-input">
                                <option :value="null" disabled>Select a test…</option>
                                <option v-for="test in tests" :key="test.id" :value="test.id">{{ test.title }}</option>
                            </select>
                            <button @click="importFromTest" :disabled="!importTestId" class="ui-btn-secondary w-full mt-3 disabled:opacity-50">
                                Import questions
                            </button>
                        </Card>
                    </div>

                    <!-- Right: bank list + create test -->
                    <div class="lg:col-span-2 space-y-4">
                        <Card padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <select v-model="activeCourse" @change="filterByCourse" class="ui-input !w-auto">
                                    <option :value="null">All my courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                </select>
                                <span class="text-sm text-content-muted">{{ items.length }} question(s)</span>
                            </div>
                        </Card>

                        <!-- Create draft test from selection -->
                        <Card v-if="selected.length" padding="p-4">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="flex-1 min-w-40">
                                    <label class="ui-label">Draft test title</label>
                                    <input v-model="testForm.title" type="text" class="ui-input" placeholder="e.g. Midterm review quiz" />
                                </div>
                                <div class="min-w-48">
                                    <label class="ui-label">Section</label>
                                    <select v-model="testForm.section_id" class="ui-input">
                                        <option :value="null" disabled>
                                            {{ selectableSections.length ? 'Select section…' : 'Select questions from ONE course' }}
                                        </option>
                                        <option v-for="section in selectableSections" :key="section.id" :value="section.id">{{ section.label }}</option>
                                    </select>
                                </div>
                                <button
                                    @click="createTest"
                                    :disabled="!testForm.title || !testForm.section_id || testForm.processing"
                                    class="ui-btn-primary disabled:opacity-50"
                                >
                                    <ClipboardDocumentListIcon class="w-4 h-4" />
                                    Create draft test ({{ selected.length }})
                                </button>
                            </div>
                            <p v-if="testForm.errors.item_ids || testForm.errors.section_id" class="mt-2 text-xs text-danger-fg">
                                {{ testForm.errors.item_ids || testForm.errors.section_id }}
                            </p>
                        </Card>

                        <Card v-if="items.length" padding="p-0">
                            <div class="divide-y divide-line">
                                <div v-for="item in items" :key="item.id" class="flex items-start gap-3 p-4">
                                    <input
                                        type="checkbox"
                                        :checked="selected.includes(item.id)"
                                        @change="toggleSelected(item.id)"
                                        class="mt-1"
                                        :aria-label="`Select question ${item.id}`"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-content whitespace-pre-wrap">{{ item.question }}</p>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                            <Badge variant="slate">{{ item.course }}</Badge>
                                            <Badge variant="violet">{{ item.type === 'mcq' ? 'MCQ' : 'True/False' }}</Badge>
                                            <Badge variant="info">{{ item.marks }} mark(s)</Badge>
                                            <Badge :variant="item.difficulty === 'hard' ? 'danger' : (item.difficulty === 'easy' ? 'success' : 'warning')" class="capitalize">{{ item.difficulty }}</Badge>
                                            <span v-if="item.topic" class="text-xs text-content-faint">· {{ item.topic }}</span>
                                        </div>
                                        <p class="text-xs text-content-faint mt-1.5">
                                            <template v-if="item.type === 'mcq'">
                                                {{ item.options.map((o) => `${o.key}. ${o.text}`).join('   ') }} — answer: {{ item.correctAnswer }}
                                            </template>
                                            <template v-else>Answer: {{ item.correctAnswer }}</template>
                                        </p>
                                    </div>
                                    <button @click="remove(item)" class="p-1.5 rounded-control text-content-faint hover:text-danger-fg hover:bg-danger-bg" aria-label="Delete question">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </Card>

                        <EmptyState
                            v-else
                            title="The bank is empty"
                            description="Add questions manually or import them from one of your class tests."
                            :icon="RectangleStackIcon"
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>
