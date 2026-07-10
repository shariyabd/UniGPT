<script setup>
/**
 * Looping, "live" student ↔ AI chat for the presentation's grounded-answer
 * slide. Types a student question, shows a typing indicator, streams the AI
 * answer, reveals citation + confidence, holds, then loops to the next pair.
 *
 * Self-cleaning: this component is only mounted while its slide is active
 * (the deck uses <Transition :key> out-in), so onBeforeUnmount cancels every
 * pending timer and stops the loop the moment the slide changes.
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { SparklesIcon, CheckBadgeIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ pairs: { type: Array, default: () => [] } });

const inputText = ref('');
const question = ref('');
const answer = ref('');
const showTyping = ref(false);
const showMeta = ref(false);
const meta = ref(null);

let alive = true;
const timers = [];
const wait = (ms) => new Promise((r) => timers.push(setTimeout(r, ms)));

async function type(setter, text, speed) {
    for (let i = 0; i <= text.length; i++) {
        if (!alive) return;
        setter(text.slice(0, i));
        await wait(speed);
    }
}

async function runPair(pair) {
    // reset
    inputText.value = ''; question.value = ''; answer.value = '';
    showTyping.value = false; showMeta.value = false; meta.value = null;
    await wait(500);

    // student types into the composer, then "sends"
    await type((v) => (inputText.value = v), pair.q, 34);
    await wait(450);
    question.value = pair.q;
    inputText.value = '';
    await wait(400);

    // AI is thinking
    showTyping.value = true;
    await wait(1300);
    showTyping.value = false;

    // AI streams the answer, then reveals grounding
    await type((v) => (answer.value = v), pair.a, 16);
    await wait(350);
    meta.value = pair;
    showMeta.value = true;
    await wait(3200);
}

async function loop() {
    while (alive) {
        for (const pair of props.pairs) {
            if (!alive) return;
            await runPair(pair);
        }
    }
}

onMounted(() => { alive = true; loop(); });
onBeforeUnmount(() => { alive = false; timers.forEach(clearTimeout); });
</script>

<template>
    <div class="ac">
        <div class="ac__head">
            <span class="ac__avatar"><SparklesIcon class="h-4 w-4" /></span>
            UniNexus · Academic mode
            <span class="ac__online"><i></i>online</span>
        </div>

        <div class="ac__body">
            <Transition name="bubble">
                <div v-if="question" key="q" class="ac__q">{{ question }}</div>
            </Transition>

            <div v-if="showTyping" class="ac__typing">
                <span class="ac__a-avatar"><SparklesIcon class="h-3.5 w-3.5" /></span>
                <span class="ac__dots"><i></i><i></i><i></i></span>
            </div>

            <Transition name="bubble">
                <div v-if="answer" key="a" class="ac__a">
                    <span class="ac__a-avatar"><SparklesIcon class="h-3.5 w-3.5" /></span>
                    <div>
                        <p>{{ answer }}<span v-if="!showMeta" class="ac__caret"></span></p>
                        <Transition name="fade">
                            <div v-if="showMeta && meta" class="ac__meta">
                                <span class="ac__conf"><CheckBadgeIcon class="h-3.5 w-3.5" />{{ meta.confidence }}</span>
                                <span class="ac__src"><DocumentTextIcon class="h-3.5 w-3.5" />{{ meta.source }}</span>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </div>

        <div class="ac__composer">
            <span class="ac__field">{{ inputText }}<span v-if="inputText" class="ac__caret"></span><span v-else class="ac__ph">Ask anything about your courses…</span></span>
            <span class="ac__send"><SparklesIcon class="h-4 w-4" /></span>
        </div>
    </div>
</template>

<style scoped>
.ac { border-radius: 1.1rem; padding: 1.2rem; border: 1px solid var(--line); background: var(--surface2); box-shadow: var(--card-shadow, 0 30px 70px -30px rgba(0,0,0,0.6)); display: flex; flex-direction: column; min-height: 22rem; }
.ac__head { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--text); padding-bottom: 0.9rem; border-bottom: 1px solid var(--line); }
.ac__avatar { display: grid; place-items: center; width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; }
.ac__online { margin-left: auto; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 9999px; background: rgba(74,222,128,0.16); color: #16a34a; }
.ac__online i { width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: #22c55e; animation: pulse 1.4s infinite; }
.dark .ac__online, .deck:not(.deck--light) .ac__online { color: #4ade80; }

.ac__body { flex: 1; display: flex; flex-direction: column; gap: 0.7rem; padding: 1rem 0; min-height: 0; }
.ac__q { margin-left: auto; max-width: 82%; width: fit-content; padding: 0.6rem 0.9rem; border-radius: 1rem 1rem 0.2rem 1rem; background: var(--accent); color: #fff; font-size: 0.88rem; line-height: 1.4; }
.ac__typing { display: flex; align-items: center; gap: 0.5rem; }
.ac__a { display: flex; gap: 0.55rem; }
.ac__a-avatar { display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 9999px; background: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); flex: none; margin-top: 0.1rem; }
.ac__a > div { max-width: 86%; padding: 0.7rem 0.9rem; border-radius: 0.2rem 1rem 1rem 1rem; border: 1px solid var(--line); background: var(--surface); }
.ac__a p { font-size: 0.86rem; line-height: 1.5; color: var(--text); }
.ac__meta { display: flex; gap: 0.5rem; margin-top: 0.7rem; flex-wrap: wrap; }
.ac__conf { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.55rem; border-radius: 9999px; background: rgba(34,197,94,0.16); color: #16a34a; }
.ac__src { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 9999px; border: 1px solid var(--line); color: var(--muted); }
.ac__dots { display: inline-flex; gap: 0.25rem; padding: 0.55rem 0.7rem; border-radius: 0.2rem 1rem 1rem 1rem; border: 1px solid var(--line); background: var(--surface); }
.ac__dots i { width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: var(--faint); animation: bob 1s infinite; }
.ac__dots i:nth-child(2) { animation-delay: 0.15s; }
.ac__dots i:nth-child(3) { animation-delay: 0.3s; }

.ac__composer { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.7rem; border-radius: 0.8rem; border: 1px solid var(--line); background: var(--surface); }
.ac__field { flex: 1; font-size: 0.84rem; color: var(--text); min-height: 1.2rem; }
.ac__ph { color: var(--faint); }
.ac__send { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 0.6rem; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; }
.ac__caret { display: inline-block; width: 2px; height: 0.95em; vertical-align: -0.12em; margin-left: 1px; background: var(--accent); animation: blink 1s step-end infinite; }

@keyframes bob { 0%,100% { transform: translateY(0); opacity: 0.4; } 50% { transform: translateY(-3px); opacity: 1; } }
@keyframes blink { 50% { opacity: 0; } }
@keyframes pulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.6); opacity: 0.5; } }

.bubble-enter-active { transition: transform 0.35s cubic-bezier(.22,1,.36,1), opacity 0.35s; }
.bubble-enter-from { opacity: 0; transform: translateY(10px) scale(0.96); }
.fade-enter-active { transition: opacity 0.4s ease; }
.fade-enter-from { opacity: 0; }
</style>
