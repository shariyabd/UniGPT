<script setup>
/**
 * Auto-playing "See it in action" demo slider. Shows a real product
 * screenshot inside a browser-chrome frame alongside a detail panel
 * (role badge, screen title, description and feature bullets).
 *
 * Screenshots live in public/demo/*.png (see scripts/capture-demo.mjs).
 * If a frame has no `image`, a labelled placeholder is shown instead.
 *
 * Self-cleaning: mounted only while the slide is active, so the autoplay
 * interval is cleared on unmount.
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { ArrowLeftIcon, ArrowRightIcon, CheckCircleIcon, PlayPauseIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    frames: { type: Array, default: () => [] },
    interval: { type: Number, default: 4200 },
});

const i = ref(0);
const playing = ref(true);
const frame = computed(() => props.frames[i.value] || {});
let timer = null;

function schedule() {
    clearTimeout(timer);
    if (!playing.value) return;
    timer = setTimeout(() => { i.value = (i.value + 1) % props.frames.length; schedule(); }, props.interval);
}
function go(n) { i.value = (n + props.frames.length) % props.frames.length; schedule(); }
function togglePlay() { playing.value = !playing.value; schedule(); }

onMounted(schedule);
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="dc" :style="{ '--tint': frame.tint || 'var(--accent)' }">
        <div class="dc__stage">
            <!-- Browser frame with the real screenshot -->
            <div class="dc__browser">
                <div class="dc__bar">
                    <span class="dc__traffic"><i></i><i></i><i></i></span>
                    <span class="dc__url">uninexus.app<span class="dc__path">{{ frame.path }}</span></span>
                    <span class="dc__live"><span class="dc__livedot"></span>live</span>
                </div>
                <div class="dc__viewport">
                    <!-- All frames are rendered once and cross-faded, so every image is
                         loaded & decoded up front — switching is instant, no reload. -->
                    <div
                        v-for="(f, n) in frames"
                        :key="n"
                        class="dc__frame"
                        :class="{ 'dc__frame--on': n === i }"
                    >
                        <img v-if="f.image" :src="f.image" :alt="f.title" class="dc__img" loading="eager" decoding="async" />
                        <div v-else class="dc__ph"><span>{{ f.title }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Detail panel -->
            <aside class="dc__info">
                <span class="dc__role">{{ frame.role }}</span>
                <Transition name="info" mode="out-in">
                    <div :key="i">
                        <h3 class="dc__title">{{ frame.title }}</h3>
                        <p class="dc__caption">{{ frame.caption }}</p>
                        <ul class="dc__points">
                            <li v-for="pt in frame.points" :key="pt"><CheckCircleIcon class="h-4 w-4" />{{ pt }}</li>
                        </ul>
                    </div>
                </Transition>
                <!-- autoplay progress -->
                <div class="dc__progress"><span :key="i" :class="{ 'dc__progress--run': playing }" :style="{ animationDuration: interval + 'ms' }"></span></div>
            </aside>
        </div>

        <!-- Controls -->
        <div class="dc__foot">
            <button class="dc__nav" @click="go(i - 1)"><ArrowLeftIcon class="h-4 w-4" /></button>
            <button class="dc__nav" @click="togglePlay" :title="playing ? 'Pause' : 'Play'"><PlayPauseIcon class="h-4 w-4" /></button>
            <div class="dc__dots">
                <button
                    v-for="(f, n) in frames"
                    :key="n"
                    class="dc__dot"
                    :class="{ 'dc__dot--on': n === i }"
                    :style="n === i ? { background: f.tint } : {}"
                    :title="f.title"
                    @click="go(n)"
                ></button>
            </div>
            <span class="dc__count">{{ String(i + 1).padStart(2, '0') }} / {{ String(frames.length).padStart(2, '0') }}</span>
            <button class="dc__nav" @click="go(i + 1)"><ArrowRightIcon class="h-4 w-4" /></button>
        </div>
    </div>
</template>

<style scoped>
.dc { width: 100%; }
.dc__stage { display: grid; grid-template-columns: 1.7fr 1fr; gap: 1.3rem; align-items: stretch; }

/* ---- browser frame ---- */
.dc__browser { border-radius: 0.9rem; overflow: hidden; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.dc__bar { display: flex; align-items: center; gap: 0.7rem; padding: 0.55rem 0.85rem; border-bottom: 1px solid var(--line); background: var(--surface2); }
.dc__traffic { display: inline-flex; gap: 0.35rem; }
.dc__traffic i { width: 0.6rem; height: 0.6rem; border-radius: 9999px; }
.dc__traffic i:nth-child(1) { background: #f87171; }
.dc__traffic i:nth-child(2) { background: #fbbf24; }
.dc__traffic i:nth-child(3) { background: #34d399; }
.dc__url { font-size: 0.76rem; color: var(--muted); background: var(--bg); padding: 0.25rem 0.8rem; border-radius: 9999px; border: 1px solid var(--line); }
.dc__path { color: var(--tint); font-weight: 600; }
.dc__live { margin-left: auto; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; font-weight: 600; color: var(--muted); }
.dc__livedot { width: 0.45rem; height: 0.45rem; border-radius: 9999px; background: #22c55e; animation: blink2 1.4s infinite; }

.dc__viewport { aspect-ratio: 16 / 9; background: var(--bg); position: relative; overflow: hidden; }
.dc__frame { position: absolute; inset: 0; opacity: 0; transform: scale(1.03); transition: opacity 0.45s ease, transform 0.6s cubic-bezier(.22,1,.36,1); pointer-events: none; }
.dc__frame--on { opacity: 1; transform: scale(1); }
.dc__img { width: 100%; height: 100%; object-fit: cover; object-position: top left; display: block; }
.dc__ph { position: absolute; inset: 0; display: grid; place-items: center; color: var(--muted); font-weight: 700; }

/* ---- info panel ---- */
.dc__info { display: flex; flex-direction: column; padding: 1.4rem; border-radius: 0.9rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.dc__role { align-self: flex-start; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #fff; background: var(--tint); }
.dc__title { font-size: clamp(1.15rem, 2vw, 1.6rem); font-weight: 800; letter-spacing: -0.02em; margin-top: 0.9rem; color: var(--text); }
.dc__caption { color: var(--muted); font-size: 0.9rem; line-height: 1.5; margin-top: 0.5rem; }
.dc__points { margin-top: 1rem; display: flex; flex-direction: column; gap: 0.6rem; }
.dc__points li { display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.88rem; color: var(--text); }
.dc__points svg { color: var(--tint); flex: none; margin-top: 0.1rem; }
.dc__progress { margin-top: auto; height: 3px; border-radius: 9999px; background: var(--line); overflow: hidden; }
.dc__progress span { display: block; height: 100%; width: 0; background: var(--tint); }
.dc__progress--run { animation: fill linear forwards; }

/* ---- controls ---- */
.dc__foot { display: flex; align-items: center; gap: 0.8rem; margin-top: 1rem; }
.dc__nav { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 0.6rem; border: 1px solid var(--line); background: var(--surface); color: var(--text); transition: 0.2s; }
.dc__nav:hover { border-color: var(--tint); color: var(--tint); }
.dc__dots { display: flex; gap: 0.4rem; }
.dc__dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; background: var(--line); transition: 0.25s; }
.dc__dot--on { width: 1.5rem; }
.dc__count { margin-left: auto; font-variant-numeric: tabular-nums; font-size: 0.82rem; font-weight: 600; color: var(--muted); }

/* ---- transitions ---- */
.info-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(.22,1,.36,1); }
.info-enter-from { opacity: 0; transform: translateY(12px); }

@keyframes blink2 { 50% { opacity: 0.3; } }
@keyframes fill { from { width: 0; } to { width: 100%; } }

@media (max-width: 860px) {
    .dc__stage { grid-template-columns: 1fr; }
    .dc__info { order: -1; }
}
@media (prefers-reduced-motion: reduce) {
    .dc__frame { transition: opacity 0.2s; transform: none; }
    .dc__progress--run { animation: none; }
}
</style>
