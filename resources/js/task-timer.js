function initTaskTimers() {
    const timers = document.querySelectorAll('[data-task-timer]');

    if (timers.length === 0) {
        return;
    }

    function updateTimers() {
        timers.forEach((timer) => {
            const startedAt = Date.parse(timer.dataset.startedAt);

            if (Number.isNaN(startedAt)) {
                return;
            }

            const seconds = Math.max(
                0,
                Math.floor((Date.now() - startedAt) / 1000),
            );

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;

            timer.textContent = [
                hours,
                minutes,
                remainingSeconds,
            ]
                .map((value) => String(value).padStart(2, '0'))
                .join(':');
        });
    }

    updateTimers();

    window.setInterval(updateTimers, 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTaskTimers);
} else {
    initTaskTimers();
}