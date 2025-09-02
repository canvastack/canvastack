# CanvaStack Release Notes

## 1.0.0-alpha (2025-09-02)

### Highlights
- Rebrand from Incodiy/CODIY to CanvaStack.
- New Utility facade: `Canvatility` consolidates helpers (HTML, Table, Template, Data, Db, Json, Url, Assets).
- Improved Yajra DataTables integration with better defaults (`processing`, `deferRender`) and optional server-side preview URL.
- Vendor publish tags: `CanvaStack` and `CanvaStack Public Folder` for assets/configs/templates.
- CLI toolset registered for snapshot validation, pipeline dry-run, DB checks, and performance benchmarks.

### Breaking/Important Changes
- Namespace standardized to `Canvastack\Canvastack`.
- Facade alias is `CanvaStack`.
- Configuration files renamed/prefixed with `canvastack.*.php`.

### New
- Modular structure under `src/Library/Components` and `src/Library/Helpers`.
- Preview table blade with auto-initialize DataTables (processing + deferRender; server-side via data attributes).
- Snapshot testing focused docs and examples.

### Improvements
- More consistent table action buttons and column renderers.
- Publishing flow refined to include routes, views, configs, databases, and public assets.

### Documentation
- README updated with clickable Table of Contents, docs links, support matrix, and changelog.
- Detailed docs available under `docs/` and `src/docs/` (CLI).

### Installation (excerpt)
```bash
composer require canvastack/canvastack
php artisan vendor:publish --tag="CanvaStack" --force
php artisan vendor:publish --tag="CanvaStack Public Folder" --force
php artisan migrate --seed
```

### Upgrade Notes
- Update composer package and namespaces from Incodiy/CODIY to CanvaStack.
- Re-publish vendor assets/configs if relying on vendor files.
- Adapt helper/facade names to `CanvaStack` and `Canvatility`.

### Links
- Changelog: [CHANGELOG.md](CHANGELOG.md)
- Package: https://packagist.org/packages/canvastack/canvastack
- Release tag: https://github.com/canvastack/canvastack/releases/tag/1.0.0-alpha