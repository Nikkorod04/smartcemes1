import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                lnu: {
                    50: '#eef4ff',
                    100: '#dbe6fe',
                    200: '#bfd2fe',
                    300: '#93b4fd',
                    400: '#608afa',
                    500: '#3b5ef6',
                    600: '#2547eb',
                    700: '#1d36d8',
                    800: '#003599',
                    900: '#0a2a66',
                    950: '#071d49',
                },
                gold: {
                    50: '#fffaeb',
                    100: '#fef1c7',
                    200: '#fee285',
                    300: '#fdd24a',
                    400: '#fbc021',
                    500: '#F6B800',
                    600: '#d99a06',
                    700: '#b87508',
                    800: '#955c0c',
                    900: '#7a4b0f',
                },
                charcoal: '#1f2937',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgba(16,24,40,.05)',
                'card-hover': '0 10px 28px -8px rgba(0,53,153,.16)',
                pop: '0 12px 40px -8px rgba(16,24,40,.22)',
            },
        },
    },

    plugins: [forms],
};