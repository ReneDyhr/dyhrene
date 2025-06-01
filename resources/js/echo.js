import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: window.Laravel.reverbKey,
    wsHost: window.Laravel.reverbHost,
    wsPort: window.Laravel.reverbPort ?? 80,
    wssPort: window.Laravel.reverbPort ?? 443,
    forceTLS: (window.Laravel.reverbScheme ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
