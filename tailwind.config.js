import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode is driven by a `.dark` class on <html>; the semantic token
    // overrides in app.css do the actual color flipping. Toast `dark:` variants
    // also resolve against this class.
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Acadify reference uses Inter. Plus Jakarta kept as fallback
                // until the font <link> in app.blade.php is swapped (next phase).
                sans: ['Inter', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                // Type scale from the brief.
                'card-title': ['15px', { lineHeight: '1.4', fontWeight: '600' }],
                stat: ['30px', { lineHeight: '1.15', fontWeight: '700' }],
                'field-label': ['13px', { lineHeight: '1.4', fontWeight: '500' }],
            },
            colors: {
                /* ----- SEMANTIC TOKENS (Acadify light theme) — prefer these ----- */
                // Surfaces
                bg: 'rgb(var(--color-bg) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                line: 'rgb(var(--color-border) / <alpha-value>)', // 1px hairline border
                // Primary (purple)
                primary: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    hover: 'rgb(var(--color-primary-hover) / <alpha-value>)',
                    soft: 'rgb(var(--color-primary-soft) / <alpha-value>)',
                },
                // Text
                content: {
                    DEFAULT: 'rgb(var(--color-text) / <alpha-value>)',
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                    faint: 'rgb(var(--color-text-faint) / <alpha-value>)',
                },
                // Status (paired bg + fg)
                success: {
                    bg: 'rgb(var(--color-success-bg) / <alpha-value>)',
                    fg: 'rgb(var(--color-success-fg) / <alpha-value>)',
                },
                warning: {
                    bg: 'rgb(var(--color-warning-bg) / <alpha-value>)',
                    fg: 'rgb(var(--color-warning-fg) / <alpha-value>)',
                },
                danger: {
                    bg: 'rgb(var(--color-danger-bg) / <alpha-value>)',
                    fg: 'rgb(var(--color-danger-fg) / <alpha-value>)',
                },
                neutral: {
                    bg: 'rgb(var(--color-neutral-bg) / <alpha-value>)',
                    fg: 'rgb(var(--color-neutral-fg) / <alpha-value>)',
                },

                /* ----- LEGACY tokens (kept so existing pages build; migrating next) ----- */
                // Near-black primary — buttons, active states, logo (lightweight, editorial).
                ink: {
                    DEFAULT: '#17181C',
                    900: '#17181C',
                    800: '#22242B',
                    700: '#33363F',
                    600: '#494D58',
                },
                // Soft app canvas behind white panels.
                canvas: '#F6F7F9',
                // Indigo kept for links/subtle focus accents (backwards-compatible).
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                // Pastel accent system for KPI cards, soft icon tiles and highlights.
                accent: {
                    yellow: { DEFAULT: '#FCEAA6', soft: '#FEF7D8', fg: '#6B5B12' },
                    rose: { DEFAULT: '#FBD9D8', soft: '#FDEDEC', fg: '#9B2F2F' },
                    lilac: { DEFAULT: '#E7E3FB', soft: '#F2F0FD', fg: '#4B3FA0' },
                    mint: { DEFAULT: '#D2EEE1', soft: '#E9F8F0', fg: '#1E6F52' },
                    sky: { DEFAULT: '#D9E8FB', soft: '#EDF4FD', fg: '#225C9E' },
                    peach: { DEFAULT: '#FBE3CE', soft: '#FDF1E6', fg: '#9A5A22' },
                },
            },
            boxShadow: {
                // Acadify card shadow (very subtle) — semantic token.
                card: 'var(--shadow-card)',
                'card-hover': '0 4px 12px rgba(16, 24, 40, 0.08)',
                // Legacy soft shadows (kept for existing pages).
                soft: '0 2px 8px -2px rgb(17 24 39 / 0.06), 0 4px 16px -4px rgb(17 24 39 / 0.05)',
                pop: '0 12px 40px -12px rgb(17 24 39 / 0.18)',
            },
            borderRadius: {
                // Brief: card 16px, button/input 10px, pill 999px.
                card: '16px',
                control: '10px',
                pill: '9999px',
                // Legacy.
                '2.5xl': '1.25rem',
                '4xl': '2rem',
            },
            keyframes: {
                'fade-in': {
                    // The final state intentionally omits `transform` so the
                    // element settles back to `transform: none`. A retained
                    // transform (e.g. via fill-mode) would make the element a
                    // containing block for `position: fixed` descendants,
                    // which mis-positions modals rendered inside it.
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1' },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.3s ease-out both',
            },
        },
    },
    plugins: [],
};
