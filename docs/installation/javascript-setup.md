# JavaScript Setup

## Installation Steps

### 1. Publish JavaScript Entry Point

```bash
php artisan vendor:publish --tag=canvastack-js
```

This will create `resources/js/app.js` with all required CanvaStack imports.

### 2. Install NPM Dependencies

```bash
npm install alpinejs @alpinejs/focus @alpinejs/collapse lucide apexcharts gsap flatpickr @tanstack/table-core @tanstack/virtual-core jquery datatables.net datatables.net-buttons jszip pdfmake
```

Or add to your `package.json`:

```json
{
  "dependencies": {
    "alpinejs": "^3.13.0",
    "@alpinejs/focus": "^3.13.0",
    "@alpinejs/collapse": "^3.13.0",
    "lucide": "^0.300.0",
    "apexcharts": "^3.45.0",
    "gsap": "^3.12.0",
    "flatpickr": "^4.6.13",
    "@tanstack/table-core": "^8.11.0",
    "@tanstack/virtual-core": "^3.0.0",
    "jquery": "^3.7.1",
    "datatables.net": "^1.13.8",
    "datatables.net-buttons": "^2.4.2",
    "jszip": "^3.10.1",
    "pdfmake": "^0.2.8"
  }
}
```

### 3. Configure Vite

Publish Vite configuration:

```bash
php artisan vendor:publish --tag=canvastack-config-vite
```

This will create:
- `vite.config.js` - Vite configuration with CanvaStack alias
- `tailwind.config.js` - Tailwind configuration
- `postcss.config.js` - PostCSS configuration
- `resources/css/app.css` - CSS entry point

### 4. Build Assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

## File Structure

After publishing, you'll have:

```
your-app/
├── resources/
│   ├── js/
│   │   ├── app.js          ← Main entry point (published)
│   │   └── bootstrap.js    ← Laravel bootstrap
│   └── css/
│       └── app.css         ← CSS entry point (published)
├── vite.config.js          ← Vite config (published)
├── tailwind.config.js      ← Tailwind config (published)
├── postcss.config.js       ← PostCSS config (published)
└── package.json            ← Add dependencies here
```

## Customization

The published `app.js` file is yours to customize. You can:

1. Add your own JavaScript below the "YOUR CUSTOM CODE" section
2. Import additional libraries
3. Register custom Alpine components

**DO NOT MODIFY** the CanvaStack imports section unless you know what you're doing.

## Updating

When CanvaStack is updated, you may need to re-publish the JavaScript file:

```bash
php artisan vendor:publish --tag=canvastack-js --force
```

⚠️ **Warning**: This will overwrite your `resources/js/app.js`. Make sure to backup any custom code first.

## Troubleshooting

### Component Not Found

If you see errors like "filterModal is not defined":

1. Make sure you've published the JavaScript file
2. Rebuild assets: `npm run build`
3. Clear browser cache (Ctrl+Shift+R)

### Import Errors

If you see import errors:

1. Make sure all NPM dependencies are installed
2. Check that `vite.config.js` has the correct alias configuration
3. Run `npm install` again

### Build Errors

If Vite build fails:

1. Delete `node_modules` and `package-lock.json`
2. Run `npm install` again
3. Run `npm run build`
