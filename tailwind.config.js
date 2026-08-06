import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Design tokens are transcribed from the MTF-KebunJeruk design system.
 * Do not invent values here — every token below has a documented source.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './app/View/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                // Brand & accent. Primary is near-black ink, never the link blue.
                primary: {
                    DEFAULT: '#181d26',
                    active: '#0d1218',
                },

                // Text
                ink: '#181d26',
                body: '#333840',
                muted: '#41454d',

                // Lines
                hairline: '#dddddd',
                'border-strong': '#9297a0',
                // Lighter than hairline — divides rows inside a bordered table
                // so the outer frame stays the stronger line.
                divider: '#eef0f2',

                // Surfaces
                canvas: '#ffffff',
                'surface-soft': '#f8fafc',
                'surface-strong': '#e0e2e6',
                'surface-dark': '#181d26',
                'surface-dark-elevated': '#1d1f25',

                // Signature card surfaces — full-bleed only, never small accents.
                signature: {
                    coral: '#aa2d00',
                    forest: '#0a2e0e',
                    cream: '#f5e9d4',
                    peach: '#fcab79',
                    mint: '#a8d8c4',
                    yellow: '#f4d35e',
                    mustard: '#d9a441',
                },

                // Semantic
                link: {
                    DEFAULT: '#1b61c9',
                    active: '#1a3866',
                },
                info: {
                    DEFAULT: '#254fad',
                    border: '#458fff',
                },
                success: {
                    DEFAULT: '#006400',
                    border: '#39bf45',
                },

                // Form error surface. The design system documents signature-coral
                // as the error colour but never a background to pair it with —
                // its Known Gaps section says input error states were not
                // extracted. This is a light wash of that same coral, not a new
                // accent.
                'danger-bg': '#fdf2ef',

                'on-primary': '#ffffff',
                'on-dark': '#ffffff',
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter Display', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['ui-monospace', 'Menlo', ...defaultTheme.fontFamily.mono],
            },

            // Each entry carries its own line-height and letter-spacing so a single
            // utility reproduces the token exactly.
            fontSize: {
                'display-xl': ['48px', { lineHeight: '1.1', fontWeight: '500' }],
                'display-lg': ['40px', { lineHeight: '1.2', fontWeight: '400' }],
                'display-md': ['32px', { lineHeight: '1.2', fontWeight: '400' }],
                'title-lg': ['24px', { lineHeight: '1.35', letterSpacing: '0.12px', fontWeight: '400' }],
                'title-md': ['20px', { lineHeight: '1.5', fontWeight: '400' }],
                'title-sm': ['18px', { lineHeight: '1.4', fontWeight: '500' }],
                'label-md': ['16px', { lineHeight: '1.4', fontWeight: '500' }],
                button: ['16px', { lineHeight: '1.4', fontWeight: '500' }],
                'body-md': ['14px', { lineHeight: '1.25', fontWeight: '400' }],
                // Running prose needs more leading than the 1.25 body token.
                prose: ['14px', { lineHeight: '1.6', fontWeight: '400' }],
                caption: ['14px', { lineHeight: '1.35', letterSpacing: '0.16px', fontWeight: '500' }],
                legal: ['13.12px', { lineHeight: '1.2', fontWeight: '600' }],
                // Uppercase eyebrow used above section headings and on form group labels.
                eyebrow: ['13px', { lineHeight: '1.35', letterSpacing: '0.12em', fontWeight: '500' }],
                helper: ['12px', { lineHeight: '1.5', fontWeight: '400' }],
            },

            borderRadius: {
                xs: '2px',
                sm: '6px',
                md: '10px',
                lg: '12px',
                pill: '9999px',
            },

            spacing: {
                xxs: '4px',
                xs: '8px',
                sm: '12px',
                md: '16px',
                lg: '24px',
                xl: '32px',
                xxl: '48px',
                section: '96px',
            },

            maxWidth: {
                container: '1280px',
            },
        },
    },

    plugins: [forms],
};