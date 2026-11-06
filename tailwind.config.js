import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['"Plus Jakarta Sans Variable"', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },

            // Palet AgroTofu (dari desain referensi)
            colors: {
                'primary': '#7f5700',
                'primary-container': '#f2a900',
                'primary-fixed': '#ffdead',
                'primary-fixed-dim': '#ffba3b',
                'on-primary': '#ffffff',
                'on-primary-container': '#614200',
                'on-primary-fixed': '#281900',
                'on-primary-fixed-variant': '#604100',
                'inverse-primary': '#ffba3b',
                'surface-tint': '#7f5700',

                'secondary': '#904d00',
                'secondary-container': '#ff9734',
                'secondary-fixed': '#ffdcc3',
                'secondary-fixed-dim': '#ffb77d',
                'on-secondary': '#ffffff',
                'on-secondary-container': '#693600',
                'on-secondary-fixed': '#2f1500',
                'on-secondary-fixed-variant': '#6e3900',

                'tertiary': '#6b5e2d',
                'tertiary-container': '#c7b67c',
                'tertiary-fixed': '#f5e2a4',
                'tertiary-fixed-dim': '#d8c68a',
                'on-tertiary': '#ffffff',
                'on-tertiary-container': '#534718',
                'on-tertiary-fixed': '#221b00',
                'on-tertiary-fixed-variant': '#524617',

                'background': '#fff8f5',
                'on-background': '#2d1600',
                'surface': '#fff8f5',
                'surface-bright': '#fff8f5',
                'surface-dim': '#ffd2a9',
                'surface-variant': '#ffdcbe',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#fff1e7',
                'surface-container': '#ffeada',
                'surface-container-high': '#ffe3cc',
                'surface-container-highest': '#ffdcbe',
                'on-surface': '#2d1600',
                'on-surface-variant': '#514533',
                'inverse-surface': '#4a2800',
                'inverse-on-surface': '#ffeee1',

                'outline': '#847560',
                'outline-variant': '#d6c4ac',

                'error': '#ba1a1a',
                'error-container': '#ffdad6',
                'on-error': '#ffffff',
                'on-error-container': '#93000a',
            },

            // Skala tipografi dari referensi: text-headline-lg, text-body-md, dst.
            fontSize: {
                'display-hero': ['48px', { lineHeight: '56px', letterSpacing: '-0.02em', fontWeight: '800' }],
                'display-hero-mobile': ['32px', { lineHeight: '40px', letterSpacing: '-0.01em', fontWeight: '800' }],
                'headline-xl': ['36px', { lineHeight: '44px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'headline-xl-mobile': ['26px', { lineHeight: '34px', letterSpacing: '-0.01em', fontWeight: '700' }],
                'headline-lg': ['28px', { lineHeight: '36px', letterSpacing: '-0.01em', fontWeight: '700' }],
                'headline-md': ['22px', { lineHeight: '28px', letterSpacing: '-0.005em', fontWeight: '600' }],
                'headline-sm': ['18px', { lineHeight: '24px', fontWeight: '600' }],
                'body-lg': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'body-md': ['14px', { lineHeight: '20px', fontWeight: '400' }],
                'body-sm': ['12px', { lineHeight: '16px', fontWeight: '400' }],
                'label-lg': ['14px', { lineHeight: '20px', letterSpacing: '0.01em', fontWeight: '600' }],
                'label-md': ['12px', { lineHeight: '16px', letterSpacing: '0.02em', fontWeight: '600' }],
                'label-sm': ['11px', { lineHeight: '14px', letterSpacing: '0.04em', fontWeight: '700' }],
            },

            // Radius referensi (nilainya lebih kecil dari bawaan Tailwind), diberi nama sendiri
            // supaya komponen lain yang memakai rounded-md/lg/xl tidak ikut berubah.
            borderRadius: {
                'tm-xs': '0.125rem',
                'tm-sm': '0.25rem',
                'tm-md': '0.5rem',
                'tm-lg': '0.75rem',
            },
        },
    },

    plugins: [forms],
};
