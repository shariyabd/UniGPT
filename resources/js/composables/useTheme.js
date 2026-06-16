import { ref } from 'vue';

const STORAGE_KEY = 'theme';

// Shared singleton state so every consumer reflects the same theme.
const isDark = ref(false);

function applyTheme(dark) {
    isDark.value = dark;
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('dark', dark);
}

/**
 * Sync the reactive flag with whatever the pre-paint blade script already
 * applied to <html>, falling back to stored preference / OS setting.
 */
function initTheme() {
    if (typeof document === 'undefined') return;

    let stored = null;
    try {
        stored = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
        stored = null;
    }

    if (stored === 'dark' || stored === 'light') {
        applyTheme(stored === 'dark');
        return;
    }

    // No explicit choice: mirror the class the blade script set from OS pref.
    isDark.value = document.documentElement.classList.contains('dark');
}

function toggleTheme() {
    applyTheme(!isDark.value);
    try {
        localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light');
    } catch (e) {
        // Storage unavailable (private mode) — theme still applies for the session.
    }
}

export function useTheme() {
    return { isDark, initTheme, toggleTheme };
}
