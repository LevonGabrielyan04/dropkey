import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbConfig = window.__reverbConfig ?? {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME ?? 'https',
};

if (reverbConfig.key && reverbConfig.host) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbConfig.key,
        wsHost: reverbConfig.host,
        wsPort: reverbConfig.port ?? 80,
        wssPort: reverbConfig.port ?? 443,
        forceTLS: (reverbConfig.scheme ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
