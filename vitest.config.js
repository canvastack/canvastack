import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
    test: {
        // Test environment
        environment: 'jsdom',
        
        // Global test setup
        globals: true,
        
        // Coverage configuration
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html'],
            exclude: [
                'node_modules/',
                'tests/',
                '**/*.config.js',
                '**/*.test.js',
            ],
        },
        
        // Test file patterns
        include: [
            'resources/js/**/*.test.js',
            'tests/js/**/*.test.js',
        ],
        
        // Setup files
        setupFiles: ['./tests/js/setup.js'],
    },
    
    // Resolve aliases (same as vite.config.js)
    resolve: {
        alias: {
            '@canvastack': path.resolve(__dirname, 'resources/js'),
            '@canvastack/components': path.resolve(__dirname, 'resources/js/components'),
            '@canvastack/utils': path.resolve(__dirname, 'resources/js/utils'),
        },
    },
});
