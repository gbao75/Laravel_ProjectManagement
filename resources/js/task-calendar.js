import { Calendar } from '@fullcalendar/core';
import viLocale from '@fullcalendar/core/locales/vi';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';

function initTaskCalendar() {
    const element = document.querySelector('#task-calendar');

    if (!element) {
        return;
    }

    const status = document.querySelector('#calendar-status');
    const errorBox = document.querySelector('#calendar-error');
    const refreshButton = document.querySelector('#calendar-refresh');

    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, listPlugin],

        locale: viLocale,
        firstDay: 1,

        initialView: window.innerWidth < 768
            ? 'listWeek'
            : 'dayGridMonth',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listWeek',
        },

        height: 'auto',
        dayMaxEvents: 3,
        editable: false,

        events: async (info, successCallback, failureCallback) => {
            const url = new URL(
                element.dataset.eventsUrl,
                window.location.origin,
            );

            url.searchParams.set('start', info.startStr.slice(0, 10));
            url.searchParams.set('end', info.endStr.slice(0, 10));

            if (status.value) {
                url.searchParams.set('status', status.value);
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Không tải được lịch công việc.',
                    );
                }

                errorBox.hidden = true;
                successCallback(data);
            } catch (error) {
                errorBox.textContent = error.message;
                errorBox.hidden = false;
                failureCallback(error);
            }
        },
    });

    calendar.render();

    status.addEventListener('change', () => {
        calendar.refetchEvents();
    });

    refreshButton.addEventListener('click', () => {
        calendar.refetchEvents();
    });

    window.addEventListener('focus', () => {
        calendar.refetchEvents();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTaskCalendar);
} else {
    initTaskCalendar();
}