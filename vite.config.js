import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // ---  sección para el despliegue ---
    server: {
        host: '0.0.0.0', // Permite que el servidor sea accesible desde fuera
        hmr: {
            host: 'bolsaempleo-burlada.ddns.net', //  dominio
        },
        allowedHosts: [
            'bolsaempleo-burlada.ddns.net', 
            '.ddns.net', 
            'localhost'
        ],
    },
});