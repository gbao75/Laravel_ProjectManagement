import './project-board';
import './task-timer';
const menuToggle = document.querySelector('#menu-toggle');
const sidebar = document.querySelector('#sidebar');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');

        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.textContent = isOpen ? 'Đóng menu' : 'Menu';
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            sidebar.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.textContent = 'Menu';
        }
    });
}


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
