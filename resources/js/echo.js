import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const userId = document.querySelector('meta[name="user-id"]')?.content;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const echo = userId
    ? new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,

        wsHost: import.meta.env.VITE_REVERB_HOST,

        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),

        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',

        enabledTransports: ['ws', 'wss'],

        authEndpoint: '/broadcasting/auth',

        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        },
    })
    : null;

window.Echo = echo;

export default echo;