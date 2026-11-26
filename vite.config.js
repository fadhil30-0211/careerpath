import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        proxy: {
            '/api': 'http://127.0.0.1:8123',
            '/sanctum': 'http://127.0.0.1:8123',
            '/login': 'http://127.0.0.1:8123',
            '/logout': 'http://127.0.0.1:8123',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/storage/app/browser-*'],
        },
    },
});
