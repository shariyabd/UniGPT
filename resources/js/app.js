import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast, { POSITION, useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
import { reveal } from '@/composables/useReveal';

const appName = import.meta.env.VITE_APP_NAME || 'UniNexus';
const toastOptions = {
    position: POSITION.TOP_RIGHT,
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false,
    // Plain fade in/out. The default "bounce" leave animation springs the toast
    // back before it vanishes, which reads as the toast re-appearing and closing a
    // second time — bad UX. A fade closes cleanly: shown once, gone once.
    transition: "Vue-Toastification__fade",
    maxToasts: 3,
    newestOnTop: true,
    // Suppress only exact duplicates (same type AND same text). Distinct
    // messages of the same severity are still allowed to stack.
    filterBeforeCreate: (toast, toasts) => {
        if (toasts.some(t => t.type === toast.type && t.content === toast.content)) {
            return false;
        }
        return toast;
    }
};

// Single, centralized flash → toast pipeline. The backend shares a normalized
// `flash` object ({ id, success, error, warning, info }); this is the ONLY place
// that turns it into toasts, so no page or layout should fire flash toasts itself.
//
// Each flash response carries a unique `id`. We track shown ids in a Set and skip
// any id we've already displayed, so a single action shows its toast exactly once
// even if Inertia re-emits the same props (success event + initial-page read, or a
// re-render firing the handler again). This check is synchronous, so it wins the
// race that vue-toastification's async content de-dupe could otherwise lose.
const shownFlashIds = new Set();
const showFlash = (flash) => {
    if (!flash || !flash.id || shownFlashIds.has(flash.id)) return;
    shownFlashIds.add(flash.id);
    const toast = useToast();
    if (flash.error)   toast.error(flash.error);
    if (flash.warning) toast.warning(flash.warning);
    if (flash.success) toast.success(flash.success);
    if (flash.info)    toast.info(flash.info);
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, toastOptions)
            .directive('reveal', reveal);

        vueApp.mount(el);

        // Partial reloads (e.g. polling `router.reload({ only: [...] })`) retain the
        // previous flash values client-side; skip them so already-shown messages
        // don't re-appear on every poll tick.
        let partialVisit = false;
        router.on('before', (event) => {
            const only = event.detail.visit?.only;
            partialVisit = Array.isArray(only) && only.length > 0;
        });
        router.on('success', (event) => {
            if (partialVisit) return;
            showFlash(event.detail.page?.props?.flash);
        });

        // First full page load (e.g. landing on /login after a logout redirect).
        showFlash(props.initialPage?.props?.flash);

        return vueApp;
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
    },});