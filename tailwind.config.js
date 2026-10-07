import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Config ADITIVA.
 *
 * Los tokens de Fase 0/1 (fontFamily sans/display/body y los colores por defecto de
 * Tailwind: slate, blue, emerald, amber, red, purple) NO se tocan: 21+ archivos del
 * panel dependen de ellos.
 *
 * El sistema "Obsidian Cyber Grid" se agrega en Etapa 1 (login + fundamentos) y se
 * migrará al panel por etapas. Ver docs/design/obsidian-cyber-grid.md.
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Fase 0/1 — intactos
                sans: ['Chakra Petch', ...defaultTheme.fontFamily.sans],
                display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                body: ['Chakra Petch', ...defaultTheme.fontFamily.sans],

                // Obsidian Cyber Grid
                'headline-xl': ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                'headline-lg': ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                'headline-md': ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                'headline-sm': ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                'body-lg': ['Geist', ...defaultTheme.fontFamily.sans],
                'body-md': ['Geist', ...defaultTheme.fontFamily.sans],
                'body-sm': ['Geist', ...defaultTheme.fontFamily.sans],
                'label-lg': ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                'label-md': ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                'label-sm': ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Obsidian Cyber Grid — no redefine ningún color existente
                background: '#0d1322',
                surface: '#0d1322',
                'surface-container-lowest': '#080e1d',
                'surface-container-low': '#151b2b',
                'surface-container': '#191f2f',
                'surface-container-high': '#242a3a',
                'surface-variant': '#2f3445',
                'surface-bright': '#33394a',
                'on-surface': '#dde2f8',
                'on-surface-variant': '#c3c6d7',
                outline: '#8d90a0',
                'outline-variant': '#434655',
                primary: '#b4c5ff',
                'primary-container': '#2563eb',
                'on-primary-container': '#eeefff',
                secondary: '#a4c9ff',
                tertiary: '#00dbe9',
                error: '#ffb4ab',
                'error-container': '#93000a',
            },

            fontSize: {
                // Obsidian Cyber Grid — escala nueva, no pisa xs/sm/base/lg/xl/2xl...
                'headline-xl': ['48px', { lineHeight: '56px', letterSpacing: '-0.03em', fontWeight: '700' }],
                'headline-xl-mobile': ['32px', { lineHeight: '40px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'headline-lg': ['36px', { lineHeight: '44px', letterSpacing: '-0.025em', fontWeight: '700' }],
                'headline-lg-mobile': ['26px', { lineHeight: '34px', letterSpacing: '-0.015em', fontWeight: '700' }],
                'headline-md': ['24px', { lineHeight: '32px', letterSpacing: '-0.015em', fontWeight: '600' }],
                'headline-sm': ['18px', { lineHeight: '26px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'body-lg': ['16px', { lineHeight: '24px', letterSpacing: '-0.005em', fontWeight: '400' }],
                'body-md': ['14px', { lineHeight: '20px', letterSpacing: '0', fontWeight: '400' }],
                'body-sm': ['12px', { lineHeight: '18px', letterSpacing: '0.005em', fontWeight: '400' }],
                'label-lg': ['14px', { lineHeight: '20px', letterSpacing: '0.02em', fontWeight: '500' }],
                'label-md': ['12px', { lineHeight: '16px', letterSpacing: '0.04em', fontWeight: '500' }],
                'label-sm': ['10px', { lineHeight: '14px', letterSpacing: '0.08em', fontWeight: '600' }],
            },

            borderRadius: {
                // Namespaced: no toca DEFAULT / lg / xl / full de Tailwind
                obsidian: '4px',
                'obsidian-lg': '8px',
                'obsidian-xl': '12px',
            },
        },
    },

    plugins: [forms],
};
