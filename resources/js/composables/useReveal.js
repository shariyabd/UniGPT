/**
 * Scroll-reveal Vue directive — adds `.is-visible` to an element the first time
 * it scrolls into view. Pairs with the `.reveal` utility in app.css.
 *
 * Usage:
 *   import { reveal as vReveal } from '@/composables/useReveal';
 *   <div v-reveal class="reveal">…</div>
 *   <div v-reveal="120" class="reveal">…</div>   // optional stagger delay (ms)
 *
 * Falls back to immediately visible when IntersectionObserver is unavailable or
 * the user prefers reduced motion.
 */
const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const reveal = {
    mounted(el, binding) {
        const delay = Number(binding.value) || 0;
        if (delay) {
            el.style.transitionDelay = `${delay}ms`;
        }

        if (typeof IntersectionObserver === 'undefined' || prefersReducedMotion()) {
            el.classList.add('is-visible');
            return;
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        el.classList.add('is-visible');
                        obs.unobserve(el);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        );

        observer.observe(el);
        el._revealObserver = observer;
    },
    unmounted(el) {
        el._revealObserver?.disconnect();
    },
};
