import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'routes/**',
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            usePolling: true,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

