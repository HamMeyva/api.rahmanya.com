/*import Echo from 'laravel-echo';
import { io } from 'socket.io-client';

// window.io global tanım gerekiyor
window.io = io;

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: `http://localhost:3001`
});*/

import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: 'http://localhost:6001',
    authEndpoint: 'http://localhost:8000/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer aaa`,
        },
    },
});