import echo from './echo';

function initRealtime() {
    if (!echo) {
        return;
    }

    const userId = document.querySelector('meta[name="user-id"]')?.content;
    const bell = document.querySelector('#notification-bell');
    const badge = document.querySelector('#notification-count');
    const notificationNotice = document.querySelector('#notifications-live-notice');

    let fetchingCount = false;
    let countRefreshPending = false;

    async function refreshUnreadCount() {
        if (!bell || !badge) {
            return;
        }

        countRefreshPending = true;

        if (fetchingCount) {
            return;
        }

        fetchingCount = true;

        try {
            do {
                countRefreshPending = false;

                const response = await fetch(bell.dataset.countUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Không lấy được số thông báo.');
                }

                const data = await response.json();

                // Tài khoản đã đổi ở một tab khác.
                if (String(data.user_id) !== String(userId)) {
                    window.location.reload();
                    return;
                }

                const count = Number(data.count);

                if (!Number.isInteger(count) || count < 0) {
                    throw new Error('Số thông báo không hợp lệ.');
                }

                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count === 0;

                bell.setAttribute(
                    'aria-label',
                    `Thông báo, ${count} chưa đọc`,
                );
            } while (countRefreshPending);
        } catch (error) {
            console.error(error);
        } finally {
            fetchingCount = false;
        }
    }

    echo.private(`users.${userId}`)
        .subscribed(refreshUnreadCount)
        .listen('.notifications.changed', () => {
            refreshUnreadCount();

            if (notificationNotice) {
                notificationNotice.hidden = false;
            }
        })
        .error((error) => {
            console.error('Không kết nối được kênh thông báo:', error);
        });

    const board = document.querySelector('#project-board');
    const boardNotice = document.querySelector('#board-realtime-notice');

    async function checkBoardVersion() {
        if (!board || !board.dataset.versionUrl) {
            return;
        }

        try {
            const response = await fetch(board.dataset.versionUrl, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            showBoardNotice(data.version);
        } catch (error) {
            console.error(error);
        }
    }

    function showBoardNotice(version) {
        if (
            board
            && boardNotice
            && Number(version) > Number(board.dataset.version)
        ) {
            boardNotice.hidden = false;
        }
    }

    if (board?.dataset.projectId) {
        echo.private(`projects.${board.dataset.projectId}`)
            .subscribed(checkBoardVersion)
            .listen('.board.changed', (event) => {
                // Chờ response kéo thả của chính tab này cập nhật version.
                window.setTimeout(() => {
                    showBoardNotice(event.version);
                }, 400);
            })
            .error((error) => {
                console.error('Không kết nối được kênh Kanban:', error);
            });
    }

    window.addEventListener('focus', () => {
        refreshUnreadCount();
        checkBoardVersion();
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-reload-page]')) {
            window.location.reload();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRealtime);
} else {
    initRealtime();
}

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});