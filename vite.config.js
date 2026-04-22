// vite.config.js — Build tool configuration
// Vite handles: dev server with HMR, production bundling, asset hashing, and alias resolution.

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path'; // Node.js path module for absolute alias paths

export default defineConfig({
    plugins: [
        // laravel-vite-plugin wires Vite into Laravel:
        // - Writes the manifest.json that Blade's @vite() directive reads
        // - Injects the Vite client script in dev mode for HMR
        // - refresh: true → auto-reloads the browser when PHP/Blade files change
        laravel({
            input: 'resources/js/app.js', // single entry point — all JS/CSS starts here
            refresh: true,
        }),

        // @vitejs/plugin-vue enables .vue Single File Component support
        vue({
            template: {
                transformAssetUrls: {
                    // base: null → Vite will NOT rewrite relative URLs inside <img src="...">
                    // We want Laravel's asset() helper to control URLs, not Vite.
                    base: null,
                    // includeAbsolute: false → absolute URLs (http://) are left as-is
                    includeAbsolute: false,
                },
            },
        }),
    ],

    resolve: {
        alias: {
            // '@' maps to resources/js/ — lets you write:
            //   import Foo from '@/Components/Foo.vue'
            // instead of:
            //   import Foo from '../../Components/Foo.vue'
            // Must match the path in jsconfig.json → compilerOptions.paths → "@/*"
            '@': resolve(__dirname, 'resources/js'),
        },
    },

    build: {
        // Raise the chunk-size warning threshold from 500 KB (default) to 1 MB.
        // PrimeVue + Quill are large; this silences noisy but harmless warnings.
        // For production, consider splitting chunks further with dynamic imports.
        chunkSizeWarningLimit: 1024,
    },
});
