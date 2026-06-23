<script setup>
/**
 * UniGPT — standalone product presentation / pitch deck.
 *
 * Self-contained: does NOT use AppLayout, so there is no navbar / sidebar /
 * footer. Renders full-screen, viewport-fit (no scrolling). Slides are
 * generated from `deck.js` (config-driven) — see that file to edit content.
 *
 * Navigation: ← / → / Space / PageUp-Down / Home / End / number keys,
 * on-screen buttons, dot-nav, and touch swipe. `f` toggles fullscreen,
 * `t` toggles the light/dark theme. Transitions are pure CSS (no libraries).
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import { slides, meta } from '@/presentation/deck.js';
import AnimatedChat from '@/presentation/AnimatedChat.vue';
import DemoCarousel from '@/presentation/DemoCarousel.vue';
import {
    SparklesIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, FolderOpenIcon,
    ExclamationTriangleIcon, ClockIcon, LockClosedIcon, EyeIcon, BoltIcon,
    UsersIcon, AcademicCapIcon, BookOpenIcon, DocumentTextIcon, PuzzlePieceIcon,
    CpuChipIcon, MagnifyingGlassIcon, CheckBadgeIcon, GlobeAltIcon, PencilSquareIcon,
    ChartBarIcon, LightBulbIcon, BeakerIcon, UserGroupIcon, BellAlertIcon,
    Cog6ToothIcon, ArrowTrendingUpIcon, ArrowLeftIcon, ArrowRightIcon,
    ArrowsPointingOutIcon, SunIcon, MoonIcon,
    ChatBubbleLeftRightIcon, SignalIcon, ServerStackIcon, Squares2X2Icon,
} from '@heroicons/vue/24/outline';

const ICONS = {
    SparklesIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, FolderOpenIcon,
    ExclamationTriangleIcon, ClockIcon, LockClosedIcon, EyeIcon, BoltIcon,
    UsersIcon, AcademicCapIcon, BookOpenIcon, DocumentTextIcon, PuzzlePieceIcon,
    CpuChipIcon, MagnifyingGlassIcon, CheckBadgeIcon, GlobeAltIcon, PencilSquareIcon,
    ChartBarIcon, LightBulbIcon, BeakerIcon, UserGroupIcon, BellAlertIcon,
    Cog6ToothIcon, ArrowTrendingUpIcon,
    ChatBubbleLeftRightIcon, SignalIcon, ServerStackIcon, Squares2X2Icon,
};
const iconFor = (name) => ICONS[name] || SparklesIcon;

const ACCENTS = {
    primary: '#8B6FFF', cyan: '#22D3EE', violet: '#A78BFA', emerald: '#34D399',
    danger: '#F87171', warning: '#FBBF24', success: '#4ADE80',
};
const accentColor = (name) => ACCENTS[name] || ACCENTS.primary;

const index = ref(0);
const direction = ref('next');
const light = ref(false);

const total = computed(() => slides.length);
const current = computed(() => slides[index.value]);
const progress = computed(() => ((index.value + 1) / total.value) * 100);
const titleLines = (t) => (t ? t.split('\n') : []);

function go(to) {
    const next = Math.max(0, Math.min(total.value - 1, to));
    if (next === index.value) return;
    direction.value = next > index.value ? 'next' : 'prev';
    index.value = next;
}
const next = () => go(index.value + 1);
const prev = () => go(index.value - 1);

/* ---- keyboard ------------------------------------------------------------ */
function onKey(e) {
    if (['ArrowRight', 'PageDown', ' ', 'Enter'].includes(e.key)) { e.preventDefault(); next(); }
    else if (['ArrowLeft', 'PageUp', 'Backspace'].includes(e.key)) { e.preventDefault(); prev(); }
    else if (e.key === 'Home') { e.preventDefault(); go(0); }
    else if (e.key === 'End') { e.preventDefault(); go(total.value - 1); }
    else if (e.key === 'f') toggleFullscreen();
    else if (e.key === 't') light.value = !light.value;
    else if (/^[1-9]$/.test(e.key)) go(Number(e.key) - 1);
}

/* ---- touch swipe --------------------------------------------------------- */
let touchX = 0;
const onTouchStart = (e) => { touchX = e.changedTouches[0].clientX; };
const onTouchEnd = (e) => {
    const dx = e.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 50) (dx < 0 ? next() : prev());
};

/* ---- fullscreen ---------------------------------------------------------- */
function toggleFullscreen() {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
    else document.exitFullscreen?.();
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Head :title="`${meta.name} — Presentation`" />

    <div
        class="deck"
        :class="{ 'deck--light': light }"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
    >
        <!-- Ambient 3D background -->
        <div class="deck__bg">
            <div class="orb orb--a" :style="{ background: accentColor(current.accent) }"></div>
            <div class="orb orb--b"></div>
            <div class="deck__grid"></div>
            <!-- Floating 3D glass panels; re-keyed per slide so they sweep in on change -->
            <div :key="current.id" class="scene">
                <span class="shard shard--1" :style="{ '--accent': accentColor(current.accent) }"></span>
                <span class="shard shard--2"></span>
                <span class="shard shard--3" :style="{ '--accent': accentColor(current.accent) }"></span>
                <span class="shard shard--4"></span>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="deck__progress"><span :style="{ width: progress + '%' }"></span></div>

        <!-- Top bar -->
        <header class="deck__top">
            <div class="brand">
                <span class="brand__mark"><SparklesIcon class="h-4 w-4" /></span>
                <span class="brand__name">{{ meta.name }}</span>
                <span class="brand__tag">{{ meta.tagline }}</span>
            </div>
            <div class="deck__tools">
                <button class="iconbtn" title="Toggle theme (t)" @click="light = !light">
                    <SunIcon v-if="!light" class="h-4 w-4" /><MoonIcon v-else class="h-4 w-4" />
                </button>
                <button class="iconbtn" title="Fullscreen (f)" @click="toggleFullscreen">
                    <ArrowsPointingOutIcon class="h-4 w-4" />
                </button>
            </div>
        </header>

        <!-- Slide stage -->
        <main class="stage">
            <Transition :name="direction === 'next' ? 'slide-next' : 'slide-prev'" mode="out-in">
                <section :key="current.id" class="slide" :class="'slide--' + current.layout" :style="{ '--accent': accentColor(current.accent) }">

                    <!-- ===================================================== COVER -->
                    <div v-if="current.layout === 'cover'" class="cover stagger">
                        <span class="kicker"><SparklesIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                        <h1 class="cover__title">
                            <span v-for="(l, i) in titleLines(current.title)" :key="i" class="block">
                                <template v-if="i === titleLines(current.title).length - 1">
                                    <span class="grad">{{ l }}</span>
                                </template>
                                <template v-else>{{ l }}</template>
                            </span>
                        </h1>
                        <p class="cover__sub">{{ current.subtitle }}</p>
                        <div class="cover__tags">
                            <span v-for="t in current.tags" :key="t" class="pill">{{ t }}</span>
                        </div>
                        <div class="cover__chips">
                            <span v-for="c in current.chips" :key="c.label" class="chip">
                                <component :is="iconFor(c.icon)" class="h-4 w-4" />{{ c.label }}
                            </span>
                        </div>
                    </div>

                    <!-- ================================================= STATEMENT -->
                    <div v-else-if="current.layout === 'statement'" class="statement">
                        <div class="stagger statement__head">
                            <span class="kicker"><component :is="iconFor('LightBulbIcon')" class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">
                                <span v-for="(l, i) in titleLines(current.title)" :key="i" class="block">{{ l }}</span>
                            </h2>
                        </div>
                        <div class="trio stagger">
                            <div v-for="p in current.points" :key="p.title" class="trio__card">
                                <span class="trio__ic"><component :is="iconFor(p.icon)" class="h-5 w-5" /></span>
                                <h3>{{ p.title }}</h3>
                                <p>{{ p.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================== PILLARS -->
                    <div v-else-if="current.layout === 'pillars'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><SparklesIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                            <p v-if="current.subtitle" class="lead">{{ current.subtitle }}</p>
                        </div>
                        <div class="trio stagger">
                            <div v-for="(p, i) in current.items" :key="p.title" class="trio__card trio__card--lift">
                                <span class="trio__ic trio__ic--num">{{ i + 1 }}</span>
                                <span class="trio__bigic"><component :is="iconFor(p.icon)" class="h-6 w-6" /></span>
                                <h3>{{ p.title }}</h3>
                                <p>{{ p.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================= ARCHITECTURE -->
                    <div v-else-if="current.layout === 'architecture'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><CpuChipIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                            <p class="lead">{{ current.subtitle }}</p>
                        </div>
                        <div class="arch stagger">
                            <div
                                v-for="layer in current.layers"
                                :key="layer.name"
                                class="arch__layer"
                                :style="{ '--accent': accentColor(layer.accent) }"
                            >
                                <span class="arch__name">{{ layer.name }}</span>
                                <div class="arch__items">
                                    <span v-for="it in layer.items" :key="it" class="arch__pill">{{ it }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================== ROLES -->
                    <div v-else-if="current.layout === 'roles'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><ShieldCheckIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                            <p class="lead">{{ current.subtitle }}</p>
                        </div>
                        <div class="roles stagger">
                            <div
                                v-for="r in current.roles"
                                :key="r.name"
                                class="role"
                                :style="{ '--accent': accentColor(r.accent) }"
                            >
                                <div class="role__top">
                                    <span class="role__ic"><component :is="iconFor(r.icon)" class="h-6 w-6" /></span>
                                    <div>
                                        <h3>{{ r.name }}</h3>
                                        <span class="role__tag">{{ r.tagline }}</span>
                                    </div>
                                </div>
                                <ul>
                                    <li v-for="f in r.features" :key="f"><CheckBadgeIcon class="h-4 w-4" />{{ f }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================== PIPELINE -->
                    <div v-else-if="current.layout === 'pipeline'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><CpuChipIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                            <p class="lead">{{ current.subtitle }}</p>
                        </div>
                        <div class="pipe stagger">
                            <div
                                v-for="(s, i) in current.steps"
                                :key="s.label"
                                class="pipe__seg"
                                :style="{ '--d': i * 0.45 + 's' }"
                            >
                                <div class="pipe__node">
                                    <span class="pipe__ic"><component :is="iconFor(s.icon)" class="h-6 w-6" /></span>
                                    <span class="pipe__label">{{ s.label }}</span>
                                    <span class="pipe__sub">{{ s.sub }}</span>
                                </div>
                                <span v-if="i < current.steps.length - 1" class="pipe__wire">
                                    <ArrowRightIcon class="pipe__arrow h-5 w-5" />
                                    <span class="pipe__packet"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ====================================================== CHAT -->
                    <div v-else-if="current.layout === 'chat'" class="split">
                        <div class="stagger split__copy">
                            <span class="kicker"><SparklesIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                            <p class="lead">{{ current.subtitle }}</p>
                            <div class="kw">
                                <div v-for="k in current.keywords" :key="k.label" class="kw__row">
                                    <span class="kw__ic"><component :is="iconFor(k.icon)" class="h-5 w-5" /></span>
                                    <div><strong>{{ k.label }}</strong><span>{{ k.desc }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="split__visual">
                            <AnimatedChat :pairs="current.pairs" />
                        </div>
                    </div>

                    <!-- ====================================================== GRID -->
                    <div v-else-if="current.layout === 'grid'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><SparklesIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                        </div>
                        <div class="grid stagger" :class="`grid--${current.columns || 3}`">
                            <div v-for="f in current.items" :key="f.title" class="card">
                                <span class="card__ic"><component :is="iconFor(f.icon)" class="h-5 w-5" /></span>
                                <h3>{{ f.title }}</h3>
                                <p>{{ f.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================== WORKFLOW -->
                    <div v-else-if="current.layout === 'workflow'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><BoltIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                        </div>
                        <div class="flow stagger">
                            <div v-for="(s, i) in current.steps" :key="s.title" class="flow__seg">
                                <div class="flow__step">
                                    <span class="flow__n">{{ i + 1 }}<span class="flow__ring"></span></span>
                                    <h3>{{ s.title }}</h3>
                                    <p>{{ s.desc }}</p>
                                </div>
                                <div v-if="i < current.steps.length - 1" class="flow__line">
                                    <span class="flow__packet" :style="{ '--d': i * 0.5 + 's' }"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =================================================== METRICS -->
                    <div v-else-if="current.layout === 'metrics'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><ChartBarIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                        </div>
                        <div class="metrics stagger">
                            <div v-for="m in current.stats" :key="m.label" class="metric">
                                <span class="metric__v grad">{{ m.value }}</span>
                                <span class="metric__l">{{ m.label }}</span>
                                <span class="metric__s">{{ m.sub }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ====================================================== DEMO -->
                    <div v-else-if="current.layout === 'demo'" class="centered demoslide">
                        <div class="stagger demoslide__head">
                            <span class="kicker"><EyeIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="demoslide__title">{{ current.title }}</h2>
                        </div>
                        <DemoCarousel :frames="current.frames" :interval="1400" />
                    </div>

                    <!-- =================================================== ROADMAP -->
                    <div v-else-if="current.layout === 'roadmap'" class="centered">
                        <div class="stagger head">
                            <span class="kicker"><ArrowTrendingUpIcon class="h-3.5 w-3.5" />{{ current.kicker }}</span>
                            <h2 class="big">{{ current.title }}</h2>
                        </div>
                        <div class="road stagger">
                            <div class="road__col road__col--done">
                                <h3><CheckBadgeIcon class="h-5 w-5" />Shipped</h3>
                                <span v-for="s in current.shipped" :key="s" class="road__chip">{{ s }}</span>
                            </div>
                            <div class="road__col road__col--next">
                                <h3><SparklesIcon class="h-5 w-5" />Coming next</h3>
                                <span v-for="s in current.upcoming" :key="s" class="road__chip road__chip--ghost">{{ s }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- =================================================== CLOSING -->
                    <div v-else-if="current.layout === 'closing'" class="closing stagger">
                        <span class="closing__mark"><SparklesIcon class="h-8 w-8" /></span>
                        <h1 class="closing__title grad">{{ current.title }}</h1>
                        <p class="closing__sub">{{ current.subtitle }}</p>
                        <div class="closing__points">
                            <span v-for="p in current.points" :key="p" class="pill">{{ p }}</span>
                        </div>
                        <span class="closing__cta">{{ current.cta }}</span>
                    </div>

                </section>
            </Transition>
        </main>

        <!-- Bottom controls -->
        <footer class="deck__bottom">
            <button class="navbtn" :disabled="index === 0" @click="prev"><ArrowLeftIcon class="h-4 w-4" /></button>
            <div class="dots">
                <button
                    v-for="(s, i) in slides"
                    :key="s.id"
                    class="dot"
                    :class="{ 'dot--on': i === index }"
                    :title="s.kicker || s.title"
                    @click="go(i)"
                ></button>
            </div>
            <span class="counter">{{ String(index + 1).padStart(2, '0') }} / {{ String(total).padStart(2, '0') }}</span>
            <button class="navbtn" :disabled="index === total - 1" @click="next"><ArrowRightIcon class="h-4 w-4" /></button>
        </footer>
    </div>
</template>

<style scoped>
/* ============================================================ DECK PALETTE */
.deck {
    --bg: #0b0c12;
    --bg2: #11121b;
    --surface: rgba(255, 255, 255, 0.04);
    --surface2: rgba(255, 255, 255, 0.06);
    --line: rgba(255, 255, 255, 0.10);
    --text: #ECECF4;
    --muted: #A6A6BC;
    --faint: #6E6E86;
    --accent: #8B6FFF;
    --g1: var(--accent); --g2: #8b5cf6; --g3: #22d3ee;
    --card-shadow: 0 24px 60px -34px rgba(0, 0, 0, 0.7);
    position: fixed;
    inset: 0;
    overflow: hidden;
    background: radial-gradient(140% 120% at 50% -10%, var(--bg2), var(--bg) 60%);
    color: var(--text);
    font-family: 'Inter', system-ui, sans-serif;
    display: flex;
    flex-direction: column;
}
.deck--light {
    --bg: #eef0f7; --bg2: #ffffff;
    --surface: #ffffff;
    --surface2: #f4f5fb;
    --line: rgba(17, 18, 27, 0.12);
    --text: #16172a; --muted: #4f5168; --faint: #84869c;
    /* Darker, higher-contrast gradient stops so headings stay readable on white */
    --g1: #6b4ae8; --g2: #7c3aed; --g3: #0891b2;
    --card-shadow: 0 18px 44px -24px rgba(17, 18, 27, 0.22);
}
.deck--light .stage .slide { color: var(--text); }

/* ----------------------------------------------------------- background */
.deck__bg { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
.orb { position: absolute; border-radius: 9999px; filter: blur(90px); opacity: 0.5; transition: background 0.6s ease; }
.orb--a { width: 38rem; height: 38rem; top: -10rem; left: -8rem; }
.orb--b { width: 32rem; height: 32rem; bottom: -12rem; right: -6rem; background: #22d3ee; opacity: 0.28; }
.deck--light .orb { opacity: 0.22; }
.deck__grid {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: radial-gradient(120% 90% at 50% 30%, #000 40%, transparent 80%);
}
.deck--light .deck__grid {
    background-image:
        linear-gradient(rgba(17,18,27,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(17,18,27,0.05) 1px, transparent 1px);
}

/* ------------------------------------------------------- 3D background scene */
.scene { position: absolute; inset: 0; perspective: 1100px; transform-style: preserve-3d; pointer-events: none; }
.shard {
    position: absolute; display: block; border-radius: 1.4rem;
    border: 1px solid color-mix(in srgb, var(--accent) 35%, var(--line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 14%, transparent), transparent 70%);
    backdrop-filter: blur(2px);
    opacity: 0; transform-style: preserve-3d;
    /* sweep-in (keyed per slide) then a slow continuous 3D drift */
    animation: shard-in 0.9s cubic-bezier(.22,1,.36,1) forwards, drift 18s ease-in-out infinite;
}
.deck--light .shard { opacity: 0; }
.shard--1 { width: 16rem; height: 11rem; top: 14%; left: 8%; animation-delay: 0s, 0.9s; }
.shard--2 { width: 12rem; height: 8rem; top: 58%; left: 16%; animation-delay: 0.12s, 1s; }
.shard--3 { width: 14rem; height: 9rem; top: 20%; right: 9%; animation-delay: 0.2s, 1.1s; }
.shard--4 { width: 10rem; height: 13rem; bottom: 12%; right: 16%; animation-delay: 0.28s, 1.2s; }
@keyframes shard-in {
    from { opacity: 0; transform: translateZ(-240px) rotateX(28deg) rotateY(-22deg); }
    to   { opacity: 0.55; transform: translateZ(0) rotateX(14deg) rotateY(-12deg); }
}
.deck--light .shard { animation: shard-in-light 0.9s cubic-bezier(.22,1,.36,1) forwards, drift 18s ease-in-out infinite; }
@keyframes shard-in-light {
    from { opacity: 0; transform: translateZ(-240px) rotateX(28deg) rotateY(-22deg); }
    to   { opacity: 0.32; transform: translateZ(0) rotateX(14deg) rotateY(-12deg); }
}
@keyframes drift {
    0%, 100% { transform: translateZ(0) rotateX(14deg) rotateY(-12deg) translateY(0); }
    50%      { transform: translateZ(40px) rotateX(8deg) rotateY(10deg) translateY(-16px); }
}

/* ------------------------------------------------------------- progress */
.deck__progress { position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--line); z-index: 5; }
.deck__progress span { display: block; height: 100%; background: linear-gradient(90deg, var(--accent), #22d3ee); transition: width 0.4s cubic-bezier(.4,0,.2,1); }

/* ----------------------------------------------------------------- top */
.deck__top { position: relative; z-index: 4; display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.6rem; }
.brand { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }
.brand__mark { display: grid; place-items: center; width: 1.8rem; height: 1.8rem; border-radius: 0.6rem; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; flex: none; }
.brand__name { font-weight: 700; letter-spacing: -0.01em; }
.brand__tag { color: var(--faint); font-size: 0.8rem; border-left: 1px solid var(--line); padding-left: 0.6rem; }
@media (max-width: 640px) { .brand__tag { display: none; } }
.deck__tools { display: flex; gap: 0.5rem; }
.iconbtn { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 0.6rem; border: 1px solid var(--line); background: var(--surface); color: var(--muted); transition: 0.2s; }
.iconbtn:hover { color: var(--text); border-color: var(--accent); }

/* --------------------------------------------------------------- stage */
.stage { position: relative; z-index: 3; flex: 1; display: grid; place-items: center; padding: 0.5rem 2rem 1rem; min-height: 0; }
.slide { width: 100%; max-width: 1080px; max-height: 100%; }
.slide--demo { max-width: 1220px; }

/* The global app.css rule `h1..h5 { color: text-content }` (element specificity)
   binds headings to the HOST app's theme token, not the deck's. When the host
   page is in light mode that token is dark — making deck headings invisible on
   the deck's own dark canvas. Re-assert the deck token on every non-gradient
   heading so the deck stays fully self-themed regardless of the host. */
.slide :is(h1, h2, h3, h4):not(.grad) { color: var(--text); }

/* demo slide: compact header so the carousel gets maximum room */
.demoslide { width: 100%; }
.demoslide__head { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 1rem; }
.demoslide__title { font-size: clamp(1.3rem, 2.6vw, 2rem); font-weight: 800; letter-spacing: -0.02em; margin-top: 0.5rem; }

/* shared type ------------------------------------------------------------ */
.kicker { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 9999px; border: 1px solid var(--line); background: var(--surface); color: var(--accent); font-size: 0.78rem; font-weight: 600; letter-spacing: 0.01em; }
.big { font-size: clamp(1.6rem, 3.6vw, 3rem); font-weight: 800; line-height: 1.08; letter-spacing: -0.02em; margin-top: 1rem; }
.lead { color: var(--muted); font-size: clamp(0.95rem, 1.5vw, 1.15rem); margin-top: 0.9rem; max-width: 46rem; }
.head { text-align: center; display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; }
.centered { display: flex; flex-direction: column; align-items: center; }
.block { display: block; }
.grad { background: linear-gradient(120deg, var(--g1) 0%, var(--g2) 50%, var(--g3) 110%); -webkit-background-clip: text; background-clip: text; color: transparent; }
.pill { padding: 0.3rem 0.85rem; border-radius: 9999px; border: 1px solid var(--line); background: var(--surface); font-size: 0.82rem; font-weight: 500; color: var(--muted); }

/* --------------------------------------------------------------- cover */
.cover { text-align: center; display: flex; flex-direction: column; align-items: center; }
.cover__title { font-size: clamp(2rem, 5.2vw, 4.1rem); font-weight: 800; line-height: 1.04; letter-spacing: -0.03em; margin-top: 1.4rem; }
.cover__sub { color: var(--muted); font-size: clamp(1rem, 1.6vw, 1.2rem); max-width: 40rem; margin-top: 1.4rem; }
.cover__tags { display: flex; gap: 0.5rem; margin-top: 1.8rem; flex-wrap: wrap; justify-content: center; }
.cover__chips { display: flex; gap: 0.7rem; margin-top: 1.4rem; flex-wrap: wrap; justify-content: center; }
.chip { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.55rem 1rem; border-radius: 0.8rem; border: 1px solid var(--line); background: var(--surface2); font-size: 0.9rem; font-weight: 600; }
.chip svg { color: var(--accent); }

/* ----------------------------------------------------------- statement */
.statement { display: flex; flex-direction: column; }
.statement__head { text-align: center; align-items: center; display: flex; flex-direction: column; margin-bottom: 2.2rem; }
.trio { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.1rem; }
.trio__card { padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.trio__card--lift { position: relative; transition: 0.25s; }
.trio__card--lift:hover { transform: translateY(-4px); border-color: var(--accent); }
.trio__ic { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 0.7rem; background: color-mix(in srgb, var(--accent) 18%, transparent); color: var(--accent); }
.trio__ic--num { position: absolute; top: 1.2rem; right: 1.2rem; width: 1.9rem; height: 1.9rem; font-weight: 700; font-size: 0.85rem; }
.trio__bigic { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 0.8rem; background: color-mix(in srgb, var(--accent) 16%, transparent); color: var(--accent); }
.trio__card h3 { font-size: 1.1rem; font-weight: 700; margin-top: 1rem; }
.trio__card p { color: var(--muted); font-size: 0.92rem; margin-top: 0.5rem; line-height: 1.5; }

/* ------------------------------------------------------- architecture */
.arch { display: flex; flex-direction: column; gap: 0.7rem; width: 100%; max-width: 60rem; }
.arch__layer { display: flex; align-items: center; gap: 1.2rem; padding: 1rem 1.3rem; border-radius: 0.9rem; border: 1px solid var(--line); background: var(--surface); border-left: 3px solid var(--accent); box-shadow: var(--card-shadow); }
.arch__name { font-weight: 700; min-width: 9rem; color: var(--accent); }
.arch__items { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.arch__pill { padding: 0.3rem 0.75rem; border-radius: 9999px; background: var(--surface2); border: 1px solid var(--line); font-size: 0.82rem; color: var(--muted); }

/* --------------------------------------------------------------- roles */
.roles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.1rem; width: 100%; }
.role { padding: 1.4rem; border-radius: 1rem; border: 1px solid var(--line); background: var(--surface); border-top: 3px solid var(--accent); transition: 0.25s; box-shadow: var(--card-shadow); }
.role:hover { transform: translateY(-4px); }
.role__top { display: flex; align-items: center; gap: 0.8rem; }
.role__ic { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 0.8rem; background: color-mix(in srgb, var(--accent) 18%, transparent); color: var(--accent); flex: none; }
.role__top h3 { font-size: 1.25rem; font-weight: 700; }
.role__tag { color: var(--accent); font-size: 0.82rem; font-weight: 600; }
.role ul { margin-top: 1.1rem; display: flex; flex-direction: column; gap: 0.6rem; }
.role li { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--muted); }
.role li svg { color: var(--accent); flex: none; }

/* ------------------------------------------------------------ pipeline */
.pipe { display: flex; align-items: stretch; justify-content: center; flex-wrap: wrap; }
.pipe__seg { display: flex; align-items: stretch; }
.pipe__node { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.4rem; padding: 1.1rem 0.8rem; border-radius: 0.9rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); width: 9.2rem; }
.pipe__ic { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 0.8rem; background: color-mix(in srgb, var(--accent) 16%, transparent); color: var(--accent); animation: nodepulse 2.25s ease-in-out infinite; animation-delay: var(--d); }
.pipe__label { font-weight: 700; font-size: 0.95rem; }
.pipe__sub { font-size: 0.76rem; color: var(--faint); line-height: 1.35; }
.pipe__wire { position: relative; display: flex; align-items: center; justify-content: center; width: 2.4rem; align-self: center; }
.pipe__arrow { color: var(--accent); flex: none; opacity: 0.35; animation: flow 2.25s ease-in-out infinite; animation-delay: var(--d); }
.pipe__packet { position: absolute; top: 50%; left: 0; width: 0.5rem; height: 0.5rem; margin-top: -0.25rem; border-radius: 9999px; background: var(--accent); box-shadow: 0 0 10px 2px color-mix(in srgb, var(--accent) 70%, transparent); animation: travel 2.25s ease-in-out infinite; animation-delay: var(--d); }
@keyframes nodepulse { 0%, 70%, 100% { box-shadow: 0 0 0 0 transparent; } 12% { box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent) 22%, transparent); } }
@keyframes flow { 0%, 60%, 100% { opacity: 0.3; } 25% { opacity: 1; } }
@keyframes travel { 0% { left: 0; opacity: 0; } 15% { opacity: 1; } 60%, 100% { left: 100%; opacity: 0; } }

/* --------------------------------------------------------------- split */
.split { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 2.4rem; align-items: center; }
.kw { display: flex; flex-direction: column; gap: 1rem; margin-top: 1.6rem; }
.kw__row { display: flex; gap: 0.8rem; }
.kw__ic { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 0.7rem; background: color-mix(in srgb, var(--accent) 16%, transparent); color: var(--accent); flex: none; }
.kw__row strong { display: block; font-size: 0.98rem; }
.kw__row span { color: var(--muted); font-size: 0.86rem; }
.mock { border-radius: 1.1rem; padding: 1.3rem; border: 1px solid var(--line); background: var(--surface2); box-shadow: 0 30px 70px -30px rgba(0,0,0,0.6); }
.mock__head { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; padding-bottom: 0.9rem; border-bottom: 1px solid var(--line); }
.mock__avatar { display: grid; place-items: center; width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; }
.mock__online { margin-left: auto; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 9999px; background: rgba(74,222,128,0.16); color: #4ade80; }
.mock__q { margin: 1rem 0 0 auto; max-width: 80%; width: fit-content; padding: 0.6rem 0.9rem; border-radius: 1rem 1rem 0.2rem 1rem; background: var(--accent); color: #fff; font-size: 0.86rem; }
.mock__a { margin-top: 0.8rem; padding: 0.85rem; border-radius: 0.2rem 1rem 1rem 1rem; border: 1px solid var(--line); background: var(--surface); font-size: 0.86rem; line-height: 1.5; }
.mock__meta { display: flex; gap: 0.5rem; margin-top: 0.8rem; flex-wrap: wrap; }
.mock__conf { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.55rem; border-radius: 9999px; background: rgba(74,222,128,0.16); color: #4ade80; }
.mock__src { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 9999px; border: 1px solid var(--line); color: var(--muted); }
.mock__dots { display: flex; gap: 0.3rem; padding: 0.9rem 0 0 0.4rem; }
.mock__dots i { width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: var(--faint); animation: bob 1s infinite; }
.mock__dots i:nth-child(2) { animation-delay: 0.15s; }
.mock__dots i:nth-child(3) { animation-delay: 0.3s; }
@keyframes bob { 0%,100% { transform: translateY(0); opacity: 0.4; } 50% { transform: translateY(-3px); opacity: 1; } }

/* ---------------------------------------------------------------- grid */
.grid { display: grid; gap: 1rem; width: 100%; }
.grid--2 { grid-template-columns: repeat(2, 1fr); }
.grid--3 { grid-template-columns: repeat(3, 1fr); }
.card { padding: 1.3rem; border-radius: 0.95rem; border: 1px solid var(--line); background: var(--surface); transition: 0.25s; box-shadow: var(--card-shadow); }
.card:hover { transform: translateY(-4px); border-color: var(--accent); background: var(--surface2); }
.card__ic { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 0.7rem; background: color-mix(in srgb, var(--accent) 16%, transparent); color: var(--accent); }
.card h3 { font-weight: 700; margin-top: 0.85rem; font-size: 1.02rem; }
.card p { color: var(--muted); font-size: 0.87rem; margin-top: 0.4rem; line-height: 1.45; }

/* ---------------------------------------------------------------- flow */
.flow { display: flex; align-items: stretch; justify-content: center; flex-wrap: wrap; }
.flow__seg { display: flex; align-items: stretch; flex: 1; min-width: 12rem; max-width: 16rem; }
.flow__step { flex: 1; padding: 1.3rem; border-radius: 0.95rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.flow__n { position: relative; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 9999px; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; font-weight: 700; }
.flow__ring { position: absolute; inset: 0; border-radius: 9999px; border: 2px solid var(--accent); animation: ring 2.4s ease-out infinite; }
.flow__step h3 { font-weight: 700; margin-top: 0.9rem; font-size: 1.05rem; }
.flow__step p { color: var(--muted); font-size: 0.86rem; margin-top: 0.45rem; line-height: 1.45; }
.flow__line { position: relative; align-self: center; width: 2rem; height: 2px; background: var(--line); overflow: visible; }
.flow__line::before { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, var(--accent), transparent); }
.flow__packet { position: absolute; top: 50%; left: 0; width: 0.5rem; height: 0.5rem; margin-top: -0.25rem; border-radius: 9999px; background: var(--accent); box-shadow: 0 0 10px 2px color-mix(in srgb, var(--accent) 70%, transparent); animation: travel 2.4s ease-in-out infinite; animation-delay: var(--d); }
@keyframes ring { 0% { transform: scale(1); opacity: 0.7; } 70%, 100% { transform: scale(1.7); opacity: 0; } }
@media (max-width: 820px) { .flow__line { display: none; } }

/* ------------------------------------------------------------- metrics */
.metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.1rem; width: 100%; }
.metric { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 1.6rem 1rem; border-radius: 1rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.metric__v { font-size: clamp(2.4rem, 5vw, 3.6rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1; }
.metric__l { font-weight: 700; margin-top: 0.6rem; }
.metric__s { color: var(--faint); font-size: 0.8rem; margin-top: 0.25rem; }

/* ------------------------------------------------------------- roadmap */
.road { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; width: 100%; max-width: 56rem; }
.road__col { padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--card-shadow); }
.road__col h3 { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; margin-bottom: 1rem; }
.road__col--done h3 svg { color: #4ade80; }
.road__col--next h3 svg { color: var(--accent); }
.road__chip { display: inline-block; margin: 0.25rem; padding: 0.4rem 0.85rem; border-radius: 9999px; background: var(--surface2); border: 1px solid var(--line); font-size: 0.84rem; }
.road__chip--ghost { border-style: dashed; color: var(--muted); }

/* ------------------------------------------------------------- closing */
.closing { text-align: center; display: flex; flex-direction: column; align-items: center; }
.closing__mark { display: grid; place-items: center; width: 4.5rem; height: 4.5rem; border-radius: 1.3rem; background: linear-gradient(135deg, var(--accent), #22d3ee); color: #fff; box-shadow: 0 20px 60px -15px var(--accent); }
.closing__title { font-size: clamp(3rem, 9vw, 6rem); font-weight: 800; letter-spacing: -0.04em; margin-top: 1.6rem; }
.closing__sub { color: var(--muted); font-size: clamp(1.05rem, 2vw, 1.4rem); max-width: 34rem; margin-top: 1rem; }
.closing__points { display: flex; gap: 0.6rem; margin-top: 1.8rem; flex-wrap: wrap; justify-content: center; }
.closing__cta { margin-top: 2rem; font-size: 0.8rem; letter-spacing: 0.3em; text-transform: uppercase; color: var(--faint); }

/* -------------------------------------------------------------- bottom */
.deck__bottom { position: relative; z-index: 4; display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1.1rem 1.6rem; }
.navbtn { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 0.7rem; border: 1px solid var(--line); background: var(--surface); color: var(--text); transition: 0.2s; }
.navbtn:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }
.navbtn:disabled { opacity: 0.3; cursor: not-allowed; }
.dots { display: flex; gap: 0.4rem; max-width: 60vw; flex-wrap: wrap; justify-content: center; }
.dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; background: var(--line); transition: 0.25s; }
.dot--on { width: 1.6rem; background: linear-gradient(90deg, var(--accent), #22d3ee); }
.counter { font-variant-numeric: tabular-nums; font-size: 0.85rem; color: var(--muted); font-weight: 600; min-width: 5rem; text-align: center; }

/* ===================================================== SLIDE TRANSITIONS */
.slide-next-enter-active, .slide-prev-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(.22,1,.36,1); }
.slide-next-leave-active, .slide-prev-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.slide-next-enter-from { opacity: 0; transform: translateX(50px) scale(0.98); }
.slide-next-leave-to { opacity: 0; transform: translateX(-40px) scale(0.98); }
.slide-prev-enter-from { opacity: 0; transform: translateX(-50px) scale(0.98); }
.slide-prev-leave-to { opacity: 0; transform: translateX(40px) scale(0.98); }

/* ============================================== STAGGERED CONTENT REVEAL */
.stagger > * { animation: rise 0.55s both; }
.stagger > *:nth-child(1) { animation-delay: 0.05s; }
.stagger > *:nth-child(2) { animation-delay: 0.13s; }
.stagger > *:nth-child(3) { animation-delay: 0.21s; }
.stagger > *:nth-child(4) { animation-delay: 0.29s; }
.stagger > *:nth-child(5) { animation-delay: 0.37s; }
.stagger > *:nth-child(6) { animation-delay: 0.45s; }
@keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (prefers-reduced-motion: reduce) {
    .stagger > *, .slide-next-enter-active, .slide-prev-enter-active,
    .slide-next-leave-active, .slide-prev-leave-active { animation: none !important; transition: opacity 0.2s !important; }
}

/* --------------------------------------------------------- responsive */
@media (max-width: 900px) {
    .trio, .roles, .grid--3 { grid-template-columns: 1fr 1fr; }
    .metrics { grid-template-columns: 1fr 1fr; }
    .split { grid-template-columns: 1fr; }
    .split__visual { display: none; }
}
@media (max-width: 600px) {
    .trio, .roles, .grid--2, .grid--3, .metrics, .road { grid-template-columns: 1fr; }
    .stage { padding: 0.5rem 1rem 0.5rem; }
}
</style>
