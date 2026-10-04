import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/js/app.jsx'], refresh: true }),
        react(),
        tailwindcss(),
    ],
    server: {
        // The preview tool assigns a free port through PORT; Vite does not read it by itself.
        // Without PORT this is Vite's usual 5173. laravel-vite-plugin writes the real URL to public/hot.
        port: Number(process.env.PORT) || 5173,
        strictPort: Boolean(process.env.PORT),
        // Composer updates rewrite thousands of files under vendor/; watching them floods (and stalls) the dev server.
        watch: { ignored: ['**/vendor/**', '**/storage/**', '**/node_modules/**'] },
    },
});
