import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            // Não observar a árvore de projetos das submissões/processos —
            // cada submissão copia um Laravel inteiro (vendor/, node_modules/,
            // public/storage/seeders/*.pdf, etc.) e estoira o limite do inotify
            // do kernel ("ENOSPC: System limit for number of file watchers").
            ignored: [
                '**/storage/app/processes/**',
                '**/storage/app/autograding/**',
                '**/storage/framework/**',
                '**/storage/logs/**',
                '**/vendor/**',
            ],
        },
    },
});
