import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/wiki.css',
                'resources/js/app.js',
                'resources/js/wiki-theme.js',
            ],
            refresh: true,
        }),
    ],
});
