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