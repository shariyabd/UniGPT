<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import {
    ClipboardDocumentListIcon,
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
    SparklesIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    mode: { type: String, default: 'create' },
    test: { type: Object, default: null },
    sections: { type: Array, default: () => [] },
    // Globally-available security layers (admin-gated) + the pre-ticked state
    // for a new test, and the default warning threshold.
    securityLayers: { type: Array, default: () => [] },
    defaultSecurity: { type: Object, default: () => ({}) },
    maxWarningsDefault: { type: Number, default: 3 },
});

const isEdit = computed(() => props.mode === 'edit');

// --- Security layers --------------------------------------------------------
const CATEGORY_META = {
    lockdown: { label: 'Lockdown', hint: 'Restrict what the student can do in the browser.' },
    integrity: { label: 'Question integrity', hint: 'Make each paper harder to share.' },
    monitoring: { label: 'Monitoring & evidence', hint: 'Record behaviour for later review.' },
    media: { label: 'Media recording', hint: 'Requires the student to grant camera / screen access before starting.' },
};
const CATEGORY_ORDER = ['lockdown', 'integrity', 'monitoring', 'media'];

// Every available layer gets an entry: the saved value when editing, otherwise
// the server-provided default. Keeps the checkbox map complete + reactive.
const initialSecurity = () => {
    const saved = props.test?.securityConfig ?? null;
    const out = {};
    for (const layer of props.securityLayers) {
        out[layer.key] = saved
            ? !!saved[layer.key]
            : (props.defaultSecurity?.[layer.key] ?? layer.default);
    }
    return out;
};

const groupedLayers = computed(() => {
    const groups = {};
    for (const layer of props.securityLayers) {
        if (!groups[layer.category]) groups[layer.category] = [];
        groups[layer.category].push(layer);
    }
    return CATEGORY_ORDER
        .filter((c) => groups[c]?.length)
        .map((c) => ({ key: c, ...CATEGORY_META[c], layers: groups[c] }));
});

// "Disable going back" is meaningless without "one question at a time".
const layerDisabled = (key) => key === 'lock_back' && !form.security_config.sequential;

const OPTION_KEYS = ['A', 'B', 'C', 'D', 'E', 'F'];

const blankQuestion = (type = 'mcq') => ({
    type,
    question_text: '',
    marks: 1,
    options: [
        { key: 'A', text: '' },
        { key: 'B', text: '' },
    ],
    correct_answer: type === 'mcq' ? 'A' : 'true',
});

const form = useForm({
    section_id: props.test?.sectionId ?? (props.sections[0]?.sectionId ?? null),
    title: props.test?.title ?? '',
    description: props.test?.description ?? '',
    duration_minutes: props.test?.durationMinutes ?? 15,
    pass_marks: props.test?.passMarks ?? null,
    status: props.test?.status === 'closed' ? 'published' : (props.test?.status ?? 'draft'),
    available_from: props.test?.availableFrom ?? '',
    available_until: props.test?.availableUntil ?? '',
    max_warnings: props.test?.maxWarnings ?? props.maxWarningsDefault,
    security_config: initialSecurity(),
    questions: props.test?.questions?.length
        ? props.test.questions.map((q) => ({
            type: q.type,
            question_text: q.question_text,
            marks: q.marks,
            options: q.options?.length ? q.options.map((o) => ({ ...o })) : blankQuestion().options,
            correct_answer: q.correct_answer,
        }))
        : [blankQuestion()],
});

const totalMarks = computed(() =>
    form.questions.reduce((sum, q) => sum + (Number(q.marks) || 0), 0)
);

// Clear the dependent "disable going back" layer whenever "one question at a
// time" is switched off, so the saved selection never contradicts itself.
watch(
    () => form.security_config.sequential,
    (on) => {
        if (!on && form.security_config.lock_back) form.security_config.lock_back = false;
    },
);

// --- AI generation (optional) ----------------------------------------------
const ai = reactive({
    topic: '',
    questionCount: 5,
    difficulty: 'medium',
    mcq: true,
    true_false: true,
    marks: 1,
});
const generating = ref(false);

const isBlankQuestion = (q) =>
    !q.question_text?.trim() && (q.type !== 'mcq' || q.options.every((o) => !o.text?.trim()));

const generateWithAi = async () => {
    if (!ai.topic.trim()) {
        toast.warning('Enter a topic for the AI to generate questions.');
        return;
    }
    const types = [];
    if (ai.mcq) types.push('mcq');
    if (ai.true_false) types.push('true_false');
    if (types.length === 0) {
        toast.warning('Select at least one question type.');
        return;
    }

    generating.value = true;
    try {
        const { data } = await window.axios.post(route('faculty.class-tests.generate'), {
            topic: ai.topic,
            questionCount: ai.questionCount,
            difficulty: ai.difficulty,
            types,
            marks: ai.marks,
        });

        const generated = (data.questions ?? []).map((q) => ({
            type: q.type,
            question_text: q.question_text,
            marks: q.marks ?? 1,
            options: q.options?.length ? q.options.map((o) => ({ ...o })) : blankQuestion().options,
            correct_answer: q.correct_answer,
        }));

        if (generated.length === 0) {
            toast.info('No questions were generated. Try a different topic.');
            return;
        }

        // Drop a single empty starter question so AI output isn't preceded by a blank.
        if (form.questions.length === 1 && isBlankQuestion(form.questions[0])) {
            form.questions = generated;
        } else {
            form.questions.push(...generated);
        }
        toast.success(`Added ${generated.length} AI-generated question${generated.length === 1 ? '' : 's'}. Review and edit before saving.`);
    } catch (e) {
        toast.error(e?.response?.data?.message ?? 'Could not generate questions. Please try again.');
    } finally {
        generating.value = false;
    }
};

const addQuestion = () => form.questions.push(blankQuestion());
const removeQuestion = (index) => {
    if (form.questions.length > 1) form.questions.splice(index, 1);
};

const onTypeChange = (question) => {
    if (question.type === 'true_false') {
        question.correct_answer = 'true';
    } else {
        if (!question.options || question.options.length < 2) {
            question.options = blankQuestion().options;
        }
        question.correct_answer = question.options[0]?.key ?? 'A';
    }
};

const addOption = (question) => {
    if (question.options.length >= OPTION_KEYS.length) return;
    question.options.push({ key: OPTION_KEYS[question.options.length], text: '' });
};

const removeOption = (question, index) => {
    if (question.options.length <= 2) return;
    const removedKey = question.options[index].key;
    question.options.splice(index, 1);
    // Re-key sequentially so keys stay A, B, C…
    question.options.forEach((o, i) => { o.key = OPTION_KEYS[i]; });
    if (question.correct_answer === removedKey) {
        question.correct_answer = question.options[0].key;
    } else {
        const stillThere = question.options.find((o) => o.key === question.correct_answer);
        if (!stillThere) question.correct_answer = question.options[0].key;
    }
};

// Send a clean payload: True/False questions carry no options (the form keeps a
// hidden options array for the builder, but those blank fields must not be sent).
const buildPayload = (data) => ({
    ...data,
    questions: data.questions.map((q) => ({
        type: q.type,
        question_text: q.question_text,
        marks: q.marks,
        correct_answer: q.correct_answer,
        options: q.type === 'mcq' ? q.options : [],
    })),
});

const onError = (errors) => {
    const first = Object.values(errors ?? {})[0];
    toast.error(first || 'Please fix the highlighted fields and try again.');
};

const submit = () => {
    const options = { onError, preserveScroll: true };
    if (isEdit.value) {
        form.transform(buildPayload).patch(route('faculty.class-tests.update', props.test.id), options);
    } else {
        form.transform(buildPayload).post(route('faculty.class-tests.store'), options);
    }
};

const sectionLabel = (s) => `${s.courseCode} — ${s.courseName} · Section ${s.sectionLabel}`;
</script>

<template>
    <div>
        <Head :title="isEdit ? 'Edit Class Test' : 'New Class Test'" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <Link :href="route('faculty.class-tests')" class="ui-btn-ghost w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to class tests
                </Link>

                <PageHeader
                    :title="isEdit ? 'Edit class test' : 'New class test'"
                    subtitle="Build a timed, auto-graded quiz. Students see results instantly."
                    :icon="ClipboardDocumentListIcon"
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <!-- Test details -->
                    <Card title="Details">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="ui-label">Section</label>
                                <p v-if="isEdit" class="ui-input bg-neutral-bg">
                                    {{ test.course.code }} — {{ test.course.name }} · Section {{ test.section }}
                                </p>
                                <select v-else v-model="form.section_id" class="ui-input">
                                    <option v-for="s in sections" :key="s.sectionId" :value="s.sectionId">
                                        {{ sectionLabel(s) }}
                                    </option>
                                </select>
                                <p v-if="!isEdit && sections.length === 0" class="mt-1 text-xs text-danger-fg">
                                    You are not assigned to any section yet.
                                </p>
                                <p v-if="form.errors.section_id" class="mt-1 text-xs text-danger-fg">{{ form.errors.section_id }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="ui-label">Title</label>
                                <input v-model="form.title" type="text" class="ui-input" placeholder="e.g. Week 5 Class Test — Data Structures" />
                                <p v-if="form.errors.title" class="mt-1 text-xs text-danger-fg">{{ form.errors.title }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="ui-label">Rules / instructions <span class="text-content-faint">(shown before the test starts)</span></label>
                                <textarea v-model="form.description" rows="3" class="ui-input" placeholder="e.g. No copying. Do not switch tabs or leave fullscreen — doing so will disqualify you."></textarea>
                            </div>

                            <div>
                                <label class="ui-label">Duration (minutes)</label>
                                <input v-model.number="form.duration_minutes" type="number" min="1" max="600" class="ui-input" />
                                <p v-if="form.errors.duration_minutes" class="mt-1 text-xs text-danger-fg">{{ form.errors.duration_minutes }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Pass marks <span class="text-content-faint">(optional)</span></label>
                                <input v-model.number="form.pass_marks" type="number" min="0" class="ui-input" placeholder="—" />
                            </div>

                            <div>
                                <label class="ui-label">Available from <span class="text-content-faint">(optional)</span></label>
                                <input v-model="form.available_from" type="datetime-local" class="ui-input" />
                            </div>

                            <div>
                                <label class="ui-label">Available until <span class="text-content-faint">(optional)</span></label>
                                <input v-model="form.available_until" type="datetime-local" class="ui-input" />
                                <p v-if="form.errors.available_until" class="mt-1 text-xs text-danger-fg">{{ form.errors.available_until }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Status</label>
                                <select v-model="form.status" class="ui-input">
                                    <option value="draft">Draft (hidden from students)</option>
                                    <option value="published">Published (visible to students)</option>
                                </select>
                            </div>
                        </div>
                    </Card>

                    <!-- Security layers (independent, per-test) -->
                    <Card
                        title="Exam security"
                        :icon="ShieldCheckIcon"
                        subtitle="Pick the proctoring layers that apply to this test. Each is independent."
                    >
                        <template #actions>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-content-muted">Warnings before disqualify</label>
                                <input
                                    v-model.number="form.max_warnings"
                                    type="number"
                                    min="0"
                                    max="20"
                                    class="ui-input w-20"
                                />
                            </div>
                        </template>

                        <p v-if="securityLayers.length === 0" class="text-sm text-content-muted">
                            No security layers are enabled by your administrator.
                        </p>

                        <div v-else class="space-y-6">
                            <div v-for="group in groupedLayers" :key="group.key">
                                <h4 class="text-sm font-semibold text-content">{{ group.label }}</h4>
                                <p class="mb-3 text-xs text-content-faint">{{ group.hint }}</p>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label
                                        v-for="layer in group.layers"
                                        :key="layer.key"
                                        class="flex items-start gap-3 rounded-card border border-line p-3"
                                        :class="layerDisabled(layer.key)
                                            ? 'cursor-not-allowed opacity-50'
                                            : 'cursor-pointer hover:border-primary/40'"
                                    >
                                        <input
                                            v-model="form.security_config[layer.key]"
                                            type="checkbox"
                                            :disabled="layerDisabled(layer.key)"
                                            class="mt-0.5 h-4 w-4 rounded border-line"
                                        />
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-2 text-sm font-medium text-content">
                                                {{ layer.label }}
                                                <span v-if="layer.media" class="ui-badge bg-warning-soft text-warning-fg">
                                                    consent
                                                </span>
                                            </span>
                                            <span class="mt-0.5 block text-xs text-content-muted">{{ layer.description }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <p class="text-xs text-content-faint">
                                “Consent” layers ask the student to grant camera/screen access before the exam
                                and record for the whole session — recordings are visible only to faculty and admins.
                            </p>
                        </div>
                    </Card>

                    <!-- AI generation (optional) -->
                    <Card title="Generate with AI" :icon="SparklesIcon" subtitle="Optional — draft questions automatically, then edit them below.">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="ui-label">Topic</label>
                                <input v-model="ai.topic" type="text" class="ui-input" placeholder="e.g. Binary search trees" />
                            </div>
                            <div>
                                <label class="ui-label">Number of questions</label>
                                <input v-model.number="ai.questionCount" type="number" min="1" max="20" class="ui-input" />
                            </div>
                            <div>
                                <label class="ui-label">Difficulty</label>
                                <select v-model="ai.difficulty" class="ui-input">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div>
                                <label class="ui-label">Marks per question</label>
                                <input v-model.number="ai.marks" type="number" min="1" max="100" class="ui-input" />
                            </div>
                            <div>
                                <label class="ui-label">Question types</label>
                                <div class="flex items-center gap-4 pt-2">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="ai.mcq" type="checkbox" class="h-4 w-4 rounded border-line" /> Multiple choice
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="ai.true_false" type="checkbox" class="h-4 w-4 rounded border-line" /> True / False
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <button type="button" class="ui-btn-primary" :disabled="generating" @click="generateWithAi">
                                <SparklesIcon class="h-4 w-4" />
                                {{ generating ? 'Generating…' : 'Generate questions' }}
                            </button>
                            <p class="text-xs text-content-faint">AI-generated questions are added below — always review the correct answers before publishing.</p>
                        </div>
                    </Card>

                    <!-- Questions -->
                    <Card title="Questions">
                        <template #actions>
                            <span class="text-sm font-normal text-content-muted">{{ totalMarks }} total marks</span>
                        </template>

                        <div class="space-y-5">
                            <div
                                v-for="(question, qi) in form.questions"
                                :key="qi"
                                class="rounded-card border border-line p-4"
                            >
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <span class="ui-badge bg-primary-soft text-primary">Q{{ qi + 1 }}</span>
                                    <button
                                        v-if="form.questions.length > 1"
                                        type="button"
                                        class="ui-btn-ghost text-danger-fg"
                                        @click="removeQuestion(qi)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div class="sm:col-span-3">
                                        <label class="ui-label">Question</label>
                                        <textarea v-model="question.question_text" rows="2" class="ui-input" placeholder="Enter the question"></textarea>
                                        <p v-if="form.errors[`questions.${qi}.question_text`]" class="mt-1 text-xs text-danger-fg">
                                            {{ form.errors[`questions.${qi}.question_text`] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="ui-label">Type</label>
                                        <select v-model="question.type" class="ui-input" @change="onTypeChange(question)">
                                            <option value="mcq">Multiple choice</option>
                                            <option value="true_false">True / False</option>
                                        </select>
                                        <label class="ui-label mt-2">Marks</label>
                                        <input v-model.number="question.marks" type="number" min="1" max="100" class="ui-input" />
                                    </div>
                                </div>

                                <!-- MCQ options -->
                                <div v-if="question.type === 'mcq'" class="mt-3 space-y-2">
                                    <label class="ui-label">Options <span class="text-content-faint">(select the correct one)</span></label>
                                    <div
                                        v-for="(option, oi) in question.options"
                                        :key="oi"
                                        class="flex items-center gap-2"
                                    >
                                        <input
                                            type="radio"
                                            :name="`correct-${qi}`"
                                            :value="option.key"
                                            v-model="question.correct_answer"
                                            class="h-4 w-4"
                                        />
                                        <span class="w-5 text-sm font-semibold text-content-muted">{{ option.key }}</span>
                                        <input v-model="option.text" type="text" class="ui-input flex-1" :placeholder="`Option ${option.key}`" />
                                        <button
                                            v-if="question.options.length > 2"
                                            type="button"
                                            class="ui-btn-ghost text-danger-fg"
                                            @click="removeOption(question, oi)"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <button
                                        v-if="question.options.length < OPTION_KEYS.length"
                                        type="button"
                                        class="ui-btn-ghost"
                                        @click="addOption(question)"
                                    >
                                        <PlusIcon class="h-4 w-4" /> Add option
                                    </button>
                                    <p v-if="form.errors[`questions.${qi}.options`]" class="text-xs text-danger-fg">
                                        {{ form.errors[`questions.${qi}.options`] }}
                                    </p>
                                    <p v-if="form.errors[`questions.${qi}.correct_answer`]" class="text-xs text-danger-fg">
                                        {{ form.errors[`questions.${qi}.correct_answer`] }}
                                    </p>
                                </div>

                                <!-- True / False -->
                                <div v-else class="mt-3">
                                    <label class="ui-label">Correct answer</label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="radio" :name="`tf-${qi}`" value="true" v-model="question.correct_answer" class="h-4 w-4" /> True
                                        </label>
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="radio" :name="`tf-${qi}`" value="false" v-model="question.correct_answer" class="h-4 w-4" /> False
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="ui-btn-ghost mt-4" @click="addQuestion">
                            <PlusIcon class="h-4 w-4" /> Add question
                        </button>
                    </Card>

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('faculty.class-tests')" class="ui-btn-ghost">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="ui-btn-primary">
                            {{ isEdit ? 'Save changes' : 'Create class test' }}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
</template>
