# CanvaStack Delete Assets Publishing Guide

## Overview

CanvaStack package includes enhanced delete functionality with modal confirmations and improved UX. The delete assets (JavaScript and CSS) need to be published to the public directory for optimal performance.

## Assets Included

- **JavaScript**: `delete-handler.js` - Enhanced delete confirmation modal handler
- **CSS**: `delete-modal.css` - Styling for delete confirmation modals

## Publishing Commands

### Publish Delete Assets Only
```bash
php artisan vendor:publish --tag="CanvaStack Delete Assets"
```

### Publish All CanvaStack Assets
```bash
php artisan vendor:publish --tag="CanvaStack"
```

### Force Republish (Overwrite Existing)
```bash
php artisan vendor:publish --tag="CanvaStack Delete Assets" --force
```

## Asset Locations

### Package Source
- `packages/canvastack/canvastack/src/Library/Components/Table/Craft/assets/js/delete-handler.js`
- `packages/canvastack/canvastack/src/Library/Components/Table/Craft/assets/css/delete-modal.css`

### Published Destination
- `public/assets/js/delete-handler.js`
- `public/assets/css/delete-modal.css`

## Automatic Asset Injection

The system automatically injects these assets when:
1. A controller extends `Canvastack\Canvastack\Controllers\Core\Craft\Action`
2. The page contains delete functionality
3. Assets are available (either published or served from package)

## Fallback Mechanism

If assets are not published, the system will:
1. First try to use published assets from `public/assets/`
2. Fallback to serving assets directly from package via routes:
   - `/canvastack/assets/js/delete-handler.js`
   - `/canvastack/assets/css/delete-modal.css`

## Features

### Enhanced Delete Messages
- **Soft Delete**: "Anda akan menghapus record data dari tabel 'table_name' dengan ID 123. Data akan dipindahkan ke recycle bin dan dapat dipulihkan kembali. Apakah Anda yakin?"
- **Hard Delete**: "Anda akan menghapus permanen record data dari tabel 'table_name' dengan ID 123. Tindakan ini tidak dapat dibatalkan. Apakah Anda yakin?"

### Dynamic Table Name Detection
The system automatically detects the actual table name from:
1. Controller info
2. Model class
3. Current route
4. Fallback to generic "records"

### Improved UX
- Loading states during deletion
- AJAX-based deletion with DataTable refresh
- Proper z-index handling for modals
- Responsive design
- Multiple notification system support (toastr, SweetAlert, etc.)

## Installation Steps

1. **Publish Assets** (Recommended):
   ```bash
   php artisan vendor:publish --tag="CanvaStack Delete Assets"
   ```

2. **Verify Assets**:
   Check that files exist in `public/assets/js/` and `public/assets/css/`

3. **Clear Cache** (if needed):
   ```bash
   php artisan route:clear
   php artisan view:clear
   ```

## Troubleshooting

### Assets Not Loading
1. Check if assets are published: `ls -la public/assets/js/delete-handler.js`
2. If not published, run: `php artisan vendor:publish --tag="CanvaStack Delete Assets"`
3. Check browser console for 404 errors
4. Verify routes are cached: `php artisan route:list | grep canvastack`

### Modal Not Appearing
1. Check browser console for JavaScript errors
2. Verify jQuery and Bootstrap are loaded
3. Check z-index conflicts in CSS
4. Ensure modal HTML is being generated

### Delete Not Working
1. Check CSRF token is present
2. Verify delete route exists
3. Check controller method permissions
4. Review server logs for errors

## Development

For development, you can modify assets directly in the package and they will be served via fallback routes. For production, always publish assets for better performance.

## Version Compatibility

- Laravel 8+
- Bootstrap 4+
- jQuery 3+
- Font Awesome 5+