

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import './dashboard';
import './pages/ordens';
import './pages/upgrade';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;

window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', (event) => {
    const storedTheme = localStorage.getItem('theme');

    if (!storedTheme) {
        document.documentElement.classList.toggle('dark', event.matches);
    }
});

Alpine.start();

const dateInputs = document.querySelectorAll('[data-datepicker]');

dateInputs.forEach((input) => {
    flatpickr(input, {
        altInput: true,
        altFormat: 'd/m/Y',
        dateFormat: 'Y-m-d',
        allowInput: true,
        disableMobile: true,
        locale: Portuguese,
        monthSelectorType: 'static',
        prevArrow: '<',
        nextArrow: '>',
        onReady(_, __, instance) {
            instance.calendarContainer.classList.add('gpr-datepicker');
            instance.altInput?.classList.add('app-field', 'gpr-datepicker-input');
        },
        onOpen(_, __, instance) {
            instance.calendarContainer.classList.add('gpr-datepicker');
        },
    });
});
