import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    safelist: [
        'os-row',
        'os-row-passada',
        'os-row-concluida',
        'os-row-pendente',
        'os-row-retornar',
        'os-row-sem-contato',
        'os-row-sem-viabilidade',
        'os-row-cancelada',
        'os-row-default',
        'os-row-id',
        'os-row-id-passada',
        'os-row-id-concluida',
        'os-row-id-pendente',
        'os-row-id-retornar',
        'os-row-id-sem-contato',
        'os-row-id-sem-viabilidade',
        'os-row-id-cancelada',
        'os-row-id-default',
        'os-badge',
        'os-prioridade-normal',
        'os-prioridade-alta',
        'os-prioridade-urgente',
        'os-prioridade-default',
        'os-turno-manha',
        'os-turno-tarde',
        'os-turno-default',
        'os-status-pendente',
        'os-status-passada',
        'os-status-concluida',
        'os-status-cancelada',
        'os-status-retornar',
        'os-status-sem-contato',
        'os-status-sem-viabilidade',
        'os-status-default',
    ],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
