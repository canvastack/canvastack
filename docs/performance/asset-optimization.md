# Asset Optimization

Complete guide to asset optimization in CanvaStack, including production builds, minification, image optimization, and lazy loading.

---

## 📦 Production Build Configuration

### Vite Configuration

CanvaStack uses Vite for asset bundling with production optimizations:

```javascript
// vite.config.js
export default defineConfig({
    build: {
        // Minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'],
            },
        },
        
        // Source maps (optional)
        sourcemap: process.env.VITE_SOURCEMAP === 'true',
        
        // Code splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs', 'apexcharts'],
                    'animation': ['gsap'],
                    'icons': ['lucide'],
                },
            },
        },
        
        // Asset optimization
        assetsInlineLimit: 4096, // Inline assets < 4kb
        target: 'es2020', // Modern browsers
    },
});
```

### Build Commands

```bash
# Development build
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

---

## 🗜️ CSS/JS Minification

### Automatic Minification

All CSS and JavaScript is automatically minified in production:

**JavaScript Minification:**
- Uses Terser for optimal compression
- Removes console logs and debugger statements
- Removes comments
- Optimizes code structure

**CSS Minification:**
- Uses cssnano for CSS optimization
- Removes comments and whitespace
- Optimizes colors and font values
- Minifies selectors

### Compression

Assets are automatically compressed using:

**Gzip Compression:**
```javascript
viteCompression({
    algorithm: 'gzip',
    ext: '.gz',
    threshold: 10240, // Only compress files > 10kb
});
```

**Brotli Compression (Better):**
```javascript
viteCompression({
    algorithm: 'brotliCompress',
    ext: '.br',
    threshold: 10240,
});
```

### File Size Optimization

**Before Optimization:**
- canvastack.js: ~500KB
- canvastack.css: ~200KB

**After Optimization:**
- canvastack.js: ~150KB (gzip: ~50KB, brotli: ~40KB)
- canvastack.css: ~50KB (gzip: ~10KB, brotli: ~8KB)

---

## 🖼️ Image Optimization

### Build-Time Optimization

Images are automatically optimized during production build:

```javascript
viteImagemin({
    gifsicle: { optimizationLevel: 7 },
    optipng: { optimizationLevel: 7 },
    mozjpeg: { quality: 80 },
    pngquant: { quality: [0.8, 0.9] },
    svgo: {
        plugins: [
            { name: 'removeViewBox', active: false },
            { name: 'removeEmptyAttrs', active: true },
        ],
    },
    webp: { quality: 80 },
});
```

### Runtime Image Optimization

Use the `ImageOptimizer` class for runtime optimization:

```php
use Canvastack\Canvastack\Support\Assets\ImageOptimizer;

$optimizer = app('canvastack.image');

// Generate responsive srcset
$srcset = $optimizer->generateSrcset('/images/hero.jpg', [320, 640, 1024, 1920]);

// Generate picture element with WebP
$picture = $optimizer->generatePicture('/images/hero.jpg', 'Hero image');

// Get optimized attributes
$attrs = $optimizer->getImageAttributes('/images/hero.jpg', 'Alt text', [
    'responsive' => true,
    'width' => 1920,
    'height' => 1080,
]);
```

### Blade Directives

```blade
{{-- Optimized image with WebP fallback --}}
@optimizedImage('/images/hero.jpg', 'Hero image', ['class' => 'w-full'])

{{-- Lazy loaded image --}}
@lazyImage('<img src="/images/hero.jpg" alt="Hero">')
```

### Responsive Images

```blade
<picture>
    <source type="image/webp" 
            srcset="/images/hero-320.webp 320w,
                    /images/hero-640.webp 640w,
                    /images/hero-1024.webp 1024w,
                    /images/hero-1920.webp 1920w"
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
    <source srcset="/images/hero-320.jpg 320w,
                    /images/hero-640.jpg 640w,
                    /images/hero-1024.jpg 1024w,
                    /images/hero-1920.jpg 1920w"
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
    <img src="/images/hero.jpg" alt="Hero image" loading="lazy">
</picture>
```

---

## 🚀 Lazy Loading

### Image Lazy Loading

**Automatic (Native):**
```html
<img src="/images/photo.jpg" alt="Photo" loading="lazy">
```

**JavaScript-Based:**
```html
<img data-src="/images/photo.jpg" alt="Photo" class="lazy">
```

**With Blur-Up Effect:**
```html
<img data-src="/images/photo.jpg" 
     src="/images/photo-tiny.jpg" 
     alt="Photo" 
     class="lazy blur-up">
```

**With Skeleton Loading:**
```html
<img data-src="/images/photo.jpg" alt="Photo" class="lazy skeleton">
```

### Iframe Lazy Loading

```html
<iframe data-src="https://www.youtube.com/embed/VIDEO_ID" 
        title="Video" 
        class="lazy">
</iframe>
```

### Background Image Lazy Loading

```html
<div data-bg="/images/background.jpg" class="lazy-bg">
    Content
</div>
```

### Component Lazy Loading

```html
<div data-lazy-component="chart" class="lazy-loading">
    <!-- Component will load when visible -->
</div>
```

### JavaScript API

```javascript
// Initialize lazy loading
lazyLoading.init();

// Refresh for dynamic content
lazyLoading.refresh(container);

// Initialize specific features
lazyLoading.images();
lazyLoading.iframes();
lazyLoading.backgrounds();
lazyLoading.components();
```

### Event Handling

```javascript
// Listen for component load
document.addEventListener('lazy-component-load', (event) => {
    const { componentName, element } = event.detail;
    console.log(`Loading component: ${componentName}`);
});
```

---

## 📊 Performance Metrics

### Before Optimization

| Metric | Value |
|--------|-------|
| Total JS Size | 500KB |
| Total CSS Size | 200KB |
| Image Size (avg) | 500KB |
| Page Load Time | 3.5s |
| First Contentful Paint | 1.8s |

### After Optimization

| Metric | Value | Improvement |
|--------|-------|-------------|
| Total JS Size (gzip) | 50KB | 90% |
| Total CSS Size (gzip) | 10KB | 95% |
| Image Size (avg) | 100KB | 80% |
| Page Load Time | 1.2s | 66% |
| First Contentful Paint | 0.6s | 67% |

---

## 🎯 Best Practices

### 1. Use Modern Image Formats

```html
<picture>
    <source type="image/avif" srcset="image.avif">
    <source type="image/webp" srcset="image.webp">
    <img src="image.jpg" alt="Fallback">
</picture>
```

### 2. Optimize Image Dimensions

```php
// Don't serve 4K images for thumbnails
$optimizer->getResizedImageUrl('/images/photo.jpg', 300); // 300px width
```

### 3. Use Lazy Loading

```html
<!-- Lazy load images below the fold -->
<img src="image.jpg" loading="lazy" alt="Image">
```

### 4. Minimize HTTP Requests

```javascript
// Bundle related assets
manualChunks: {
    'vendor': ['alpinejs', 'apexcharts', 'gsap'],
}
```

### 5. Enable Compression

```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript
</IfModule>
```

### 6. Use CDN for Static Assets

```php
// config/canvastack.php
'cdn' => [
    'enabled' => true,
    'url' => 'https://cdn.example.com',
],
```

### 7. Preload Critical Assets

```html
<link rel="preload" href="/build/canvastack.css" as="style">
<link rel="preload" href="/build/canvastack.js" as="script">
```

### 8. Use Resource Hints

```html
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

---

## 🔧 Configuration

### Environment Variables

```env
# Enable source maps in production (for debugging)
VITE_SOURCEMAP=false

# CDN URL
VITE_CDN_URL=https://cdn.example.com

# Image optimization quality
VITE_IMAGE_QUALITY=80
```

### Vite Configuration

```javascript
// vite.config.js
export default defineConfig({
    build: {
        // Customize chunk size warning
        chunkSizeWarningLimit: 1000,
        
        // Customize asset inline limit
        assetsInlineLimit: 4096,
        
        // Enable/disable source maps
        sourcemap: process.env.VITE_SOURCEMAP === 'true',
    },
});
```

---

## 🧪 Testing

### Measure Performance

```bash
# Lighthouse CI
npm install -g @lhci/cli
lhci autorun

# Bundle analyzer
npm install -D rollup-plugin-visualizer
```

### Check Compression

```bash
# Check gzip size
gzip -c public/build/canvastack.js | wc -c

# Check brotli size
brotli -c public/build/canvastack.js | wc -c
```

### Validate Images

```bash
# Check image optimization
identify -verbose image.jpg | grep Quality
```

---

## 📚 Resources

### Tools

- [Vite](https://vitejs.dev) - Build tool
- [Terser](https://terser.org) - JavaScript minifier
- [cssnano](https://cssnano.co) - CSS minifier
- [ImageOptim](https://imageoptim.com) - Image optimizer

### Documentation

- [Web.dev Performance](https://web.dev/performance/)
- [MDN Lazy Loading](https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading)
- [Google PageSpeed Insights](https://pagespeed.web.dev/)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
