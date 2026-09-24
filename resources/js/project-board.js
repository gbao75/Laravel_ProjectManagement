import Sortable from 'sortablejs';

function initProjectBoard() {
    const board = document.getElementById('project-board');

    if (!board) return;

    const lists = [...board.querySelectorAll('.kanban-list')];
    const message = document.getElementById('board-message');
    const reloadButton = document.getElementById('board-reload');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    let version = Number(board.dataset.version);
    let busy = false;
    let blocked = false;
    let dragSnapshot = null;

    const sortables = [];

    function takeSnapshot() {
        return lists.map(list => ({
            list,
            cards: [...list.children],
        }));
    }

    function restore(snapshot) {
        snapshot.forEach(({ list, cards }) => {
            cards.forEach(card => list.appendChild(card));
        });

        refresh();
    }

    function refresh() {
        lists.forEach(list => {
            const cards = [...list.querySelectorAll('.kanban-card')];

            const count = board.querySelector(
                `[data-count-for="${list.dataset.status}"]`
            );

            count.textContent = String(cards.length);

            cards.forEach(card => {
                const select = card.querySelector('select[name="status"]');

                if (select) {
                    select.value = list.dataset.status;
                }
            });
        });
    }

    function setDisabled(disabled) {
        sortables.forEach(sortable => {
            sortable.option('disabled', disabled);
        });

        board.querySelectorAll(
            '.kanban-card button, .kanban-card select'
        ).forEach(control => {
            control.disabled = disabled;
        });

        board.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    async function saveMove(card, snapshot) {
        if (busy || blocked) {
            restore(snapshot);
            return;
        }

        busy = true;
        setDisabled(true);

        message.textContent = 'Đang lưu...';
        message.classList.remove('is-error');

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 15000);

        try {
            const nextCard = card.nextElementSibling;

            const response = await fetch(board.dataset.moveUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                signal: controller.signal,
                body: JSON.stringify({
                    task_id: card.dataset.id,
                    status: card.parentElement.dataset.status,
                    before_id: nextCard?.dataset.id ?? null,
                    version,
                }),
            });

            const contentType = response.headers.get('content-type') ?? '';

            const data = contentType.includes('application/json')
                ? await response.json()
                : {};

            if (!response.ok || response.redirected) {
                if (response.status === 409) {
                    throw new Error(
                        'Bảng đã thay đổi ở tab hoặc tài khoản khác. Hãy tải lại bảng.'
                    );
                }

                if ([401, 419].includes(response.status) || response.redirected) {
                    throw new Error(
                        'Phiên đăng nhập đã thay đổi hoặc hết hạn. Hãy tải lại trang.'
                    );
                }

                if (response.status === 403) {
                    throw new Error(
                        'Bạn không còn quyền thao tác công việc này. Hãy tải lại bảng.'
                    );
                }

                if (response.status === 404) {
                    throw new Error(
                        'Công việc hoặc dự án không còn tồn tại. Hãy tải lại bảng.'
                    );
                }

                throw new Error(
                    data.message ?? 'Không lưu được thay đổi. Hãy tải lại bảng.'
                );
            }

            if (!Number.isInteger(data.version)) {
                throw new Error(
                    'Phản hồi không hợp lệ. Hãy tải lại bảng để kiểm tra kết quả.'
                );
            }

            version = data.version;
            board.dataset.version = String(version);

            refresh();
            message.textContent = 'Đã lưu.';
        } catch (error) {
            restore(snapshot);

            // Không cho tiếp tục gửi trên trạng thái chưa chắc chắn.
            blocked = true;
            reloadButton.hidden = false;

            message.classList.add('is-error');
            message.textContent = error.name === 'AbortError'
                ? 'Yêu cầu quá thời gian chờ. Hãy tải lại để xác nhận dữ liệu đã lưu hay chưa.'
                : (
                    error instanceof TypeError
                        ? 'Mất kết nối. Hãy tải lại để xác nhận dữ liệu đã lưu hay chưa.'
                        : error.message
                );
        } finally {
            clearTimeout(timer);
            busy = false;
            setDisabled(blocked);
        }
    }

    lists.forEach(list => {
        const sortable = new Sortable(list, {
            group: 'project-tasks',
            animation: 150,
            draggable: '.kanban-card',
            filter: '[data-can-drag="0"], a, button, input, select, textarea, label, summary',
            preventOnFilter: false,
            ghostClass: 'kanban-ghost',
            chosenClass: 'kanban-chosen',
            scroll: true,
            scrollSensitivity: 80,
            scrollSpeed: 12,
            bubbleScroll: true,
            forceAutoScrollFallback: true,
            onStart() {
                dragSnapshot = takeSnapshot();
            },

            onEnd(event) {
                if (
                    event.from === event.to
                    && event.oldIndex === event.newIndex
                ) {
                    return;
                }

                saveMove(event.item, dragSnapshot);
            },
        });

        sortables.push(sortable);
    });

    board.addEventListener('click', event => {
        const button = event.target.closest('button[data-action]');

        if (!button || busy || blocked) return;

        const card = button.closest('.kanban-card');
        const list = card.parentElement;
        const snapshot = takeSnapshot();

        if (button.dataset.action === 'up') {
            const previous = card.previousElementSibling;
            if (!previous) return;

            list.insertBefore(card, previous);
        } else {
            const next = card.nextElementSibling;
            if (!next) return;
            list.insertBefore(card, next.nextElementSibling);
        }

        saveMove(card, snapshot);
    });

    board.addEventListener('submit', event => {
        const form = event.target.closest('.kanban-status-form');

        if (!form) return;

        event.preventDefault();

        if (busy || blocked) return;

        const card = form.closest('.kanban-card');
        const status = form.querySelector('select').value;

        if (card.parentElement.dataset.status === status) {
            return;
        }

        const targetList = lists.find(
            list => list.dataset.status === status
        );

        if (!targetList) return;

        const snapshot = takeSnapshot();

        // Chọn trạng thái bằng form: đưa xuống cuối cột đích.
        targetList.appendChild(card);

        saveMove(card, snapshot);
    });

    reloadButton.addEventListener('click', () => {
        window.location.reload();
    });

    if (!csrf) {
        blocked = true;
        setDisabled(true);
        message.textContent = 'Thiếu CSRF token. Kiểm tra layout của trang.';
        message.classList.add('is-error');
    }

    refresh();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectBoard);
} else {
    initProjectBoard();
}