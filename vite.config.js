import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';
import fs from 'fs';
import viteCompression from 'vite-plugin-compression';
import viteImagemin from 'vite-plugin-imagemin';

// Check if generated Tailwind config exists, otherwise use default
const tailwindConfig = fs.existsSync(resolve(__dirname, 'tailwind.config.generated.js'))
  ? resolve(__dirname, 'tailwind.config.generated.js')
  : resolve(__dirname, 'tailwind.config.js');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/canvastack.css',
                'resources/js/canvastack.js',
            ],
            refresh: true,
        }),
        // Gzip compression
        viteCompression({
            algorithm: 'gzip',
            ext: '.gz',
            threshold: 10240, // Only compress files > 10kb
            deleteOriginFile: false,
        }),
        // Brotli compression (better compression than gzip)
        viteCompression({
            algorithm: 'brotliCompress',
            ext: '.br',
            threshold: 10240,
            deleteOriginFile: false,
        }),
        // Image optimization (only in production)
        ...(process.env.NODE_ENV === 'production' ? [
            viteImagemin({
                gifsicle: {
                    optimizationLevel: 7,
                    interlaced: false,
                },
                optipng: {
                    optimizationLevel: 7,
                },
                mozjpeg: {
                    quality: 80,
                },
                pngquant: {
                    quality: [0.8, 0.9],
                    speed: 4,
                },
                svgo: {
                    plugins: [
                        {
                            name: 'removeViewBox',
                            active: false,
                        },
                        {
                            name: 'removeEmptyAttrs',
                            active: true,
                        },
                    ],
                },
                webp: {
                    quality: 80,
                },
            }),
        ] : []),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@css': resolve(__dirname, 'resources/css'),
            '@themes': resolve(__dirname, 'resources/themes'),
        },
    },
    css: {
        postcss: {
            plugins: [
                require('tailwindcss')(tailwindConfig),
                require('autoprefixer'),
                // Minify CSS in production
                ...(process.env.NODE_ENV === 'production' ? [
                    require('cssnano')({
                        preset: ['default', {
                            discardComments: {
                                removeAll: true,
                            },
                            normalizeWhitespace: true,
                            colormin: true,
                            minifyFontValues: true,
                            minifySelectors: true,
                        }],
                    }),
                ] : []),
            ],
        },
    },
    build: {
        outDir: 'public/build',
        manifest: true,
        // Production optimizations
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'],
            },
            format: {
                comments: false,
            },
        },
        // Source maps for production debugging (optional)
        sourcemap: process.env.VITE_SOURCEMAP === 'true',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs', 'apexcharts'],
                    'animation': ['gsap'],
                    'icons': ['lucide'],
                    'datepicker': ['flatpickr'],
                },
                // Optimize CSS output
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    if (/\.(png|jpe?g|gif|svg|webp|avif)$/.test(assetInfo.name)) {
                        return 'images/[name]-[hash][extname]';
                    }
                    if (/\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name)) {
                        return 'fonts/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
                // Optimize chunk naming
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
            },
        },
        // Enable CSS code splitting for better caching
        cssCodeSplit: true,
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        // Optimize asset inlining
        assetsInlineLimit: 4096, // 4kb
        // Report compressed size
        reportCompressedSize: true,
        // Target modern browsers for smaller bundles
        target: 'es2020',
    },
    optimizeDeps: {
        include: ['alpinejs', 'apexcharts', 'gsap', 'flatpickr'],
    },
});
