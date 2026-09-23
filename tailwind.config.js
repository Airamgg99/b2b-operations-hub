import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#0f172a', // Un azul marino corporativo (Slate 900)
                    light: '#334155'
                }
            },
            // ... tipografías existentes de Breeze
        },
    },

    plugins: [forms],
};
