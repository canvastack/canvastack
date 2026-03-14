import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build',
        }),
    ],
    
    build: {
        // Output directory (relative to package root)
        outDir: 'public/build',
        
        // Generate manifest for Laravel
        manifest: true,
        
        // Minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
            },
        },
        
        // Source maps for debugging
        sourcemap: true,
        
        // Rollup options
        rollupOptions: {
            output: {
                // Manual chunks for better caching
                manualChunks: {
                    'filter-components': [
                        './resources/js/components/filter/FilterModal.js',
                        './resources/js/components/filter/FilterCache.js',
                        './resources/js/components/filter/FilterCascade.js',
                        './resources/js/components/filter/FilterFlatpickr.js',
                    ],
                    'utils': [
                        './resources/js/utils/debounce.js',
                        './resources/js/utils/fetch.js',
                    ],
                    'vendor': [
                        'flatpickr',
                    ],
                },
            },
        },
    },
    
    // Resolve aliases
    resolve: {
        alias: {
            '@canvastack': path.resolve(__dirname, 'resources/js'),
            '@canvastack/components': path.resolve(__dirname, 'resources/js/components'),
            '@canvastack/utils': path.resolve(__dirname, 'resources/js/utils'),
        },
    },
    
    // Server options for development
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        
        // HMR options
        hmr: {
            host: 'localhost',
        },
        
        // CORS for development
        cors: true,
    },
    
    // Optimizations
    optimizeDeps: {
        include: [
            'flatpickr',
        ],
    },
});
