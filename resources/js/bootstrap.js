/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

// Re-initialize Masonry every time .list changes content
const list = document.querySelector('.recipe-list');
if (list) {
    const observer = new MutationObserver(() => {
        rePinterest();
        console.log('Masonry layout updated');
    });
    observer.observe(list, { childList: true, subtree: false });
}

// Initial Masonry layout on document ready
$(document).ready(function () {
    rePinterest();
});
var masonry;

window.rePinterest = function() {
    masonry = $('.list').masonry({
        // options...
        itemSelector: '.recipe',
        gutter: 15,
        percentPosition: true
    });
};

window.Echo.join('user.' + window.userId).listen('Masonry', (e) => {
    if (e.type == 'refresh') {
        setTimeout(() => {
            rePinterest();
        }, 100);
    }
});
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
