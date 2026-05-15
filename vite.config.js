import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/wiki.css',
                'resources/css/etui.css',
                'resources/js/app.js',
                'resources/js/wiki-theme.js',
                'resources/js/etui/renderer.js',
                'resources/js/etui/editor.js',
            ],
            refresh: true,
        }),
    ],
});
