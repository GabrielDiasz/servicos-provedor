

import Alpine from 'alpinejs';
import './dashboard';

window.Alpine = Alpine;

window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', (event) => {
    const storedTheme = localStorage.getItem('theme');

    if (!storedTheme) {
        document.documentElement.classList.toggle('dark', event.matches);
    }
});

Alpine.start();
