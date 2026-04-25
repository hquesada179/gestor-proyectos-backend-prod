import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'app-bg':                     '#051424',
                'surface':                    '#051424',
                'surface-dim':                '#051424',
                'surface-container-lowest':   '#010f1f',
                'surface-container-low':      '#0d1c2d',
                'surface-container':          '#122131',
                'surface-container-high':     '#1c2b3c',
                'surface-container-highest':  '#273647',
                'surface-bright':             '#2c3a4c',
                'surface-variant':            '#273647',
                'on-background':              '#d4e4fa',
                'on-surface':                 '#d4e4fa',
                'on-surface-variant':         '#c7c6cb',
                'primary':                    '#c5c6ce',
                'primary-container':          '#0f1117',
                'on-primary':                 '#2e3037',
                'on-primary-container':       '#7b7c84',
                'secondary':                  '#c3c0ff',
                'secondary-container':        '#3626ce',
                'on-secondary-container':     '#b3b1ff',
                'tertiary':                   '#ddb8ff',
                'tertiary-container':         '#1d0039',
                'on-tertiary-container':      '#a74bfe',
                'ds-error':                   '#ffb4ab',
                'ds-error-container':         '#93000a',
                'on-error-container':         '#ffdad6',
                'outline':                    '#909095',
                'outline-variant':            '#46464b',
            },
        },
    },

    plugins: [forms],
};
