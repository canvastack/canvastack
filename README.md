# CanvaStack

[![Packagist](https://img.shields.io/packagist/v/canvastack/canvastack?style=flat-square)](https://packagist.org/packages/canvastack/canvastack)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-10-orange?style=flat-square)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

<p>
  <img src="https://avatars.githubusercontent.com/u/218853734?v=4" alt="CanvaStack" width="64" height="64" style="border-radius:50%; vertical-align:middle; margin-right:12px;" />
  A modular Laravel toolkit for rapidly building back-office applications with consistent UI patterns. CanvaStack provides a cohesive set of builders for Forms and Tables (DataTables integration), utilities (facade: Canvatility), CLI tools, and publishable assets to bootstrap admin systems quickly and safely.
</p>

- Maintained package: [canvastack/canvastack](https://packagist.org/packages/canvastack/canvastack)
- Namespace: `Canvastack\Canvastack`
- Facade: `CanvaStack`
- Status: Actively refactored and modernized (rebranded from Incodiy/CODIY)

---

## Table of Contents

1. Features
2. Requirements
3. Installation
4. Publishing & Setup
5. Quick Start
6. Components Overview
7. Documentation Links
8. Support Matrix
9. DataTables & Performance Notes
10. CLI Tools
11. Configuration
12. Directory Structure
13. Upgrading from Incodiy
14. Changelog
15. Roadmap
16. Contributing
17. License

---

## 1) Features

- Form Builder: server-rendered form components with consistent layout and validation hooks.
- Table Builder: DataTables-powered listing with action buttons, column renderers, server-side integration, and flexible mapping.
- Utility Facade (Canvatility): helpers for HTML, assets, URL, template, scripts, charts, and more.
- Publisher: one-command publish for config, routes, migrations, seeds, views, and public assets.
- CLI & Inspector: artisan commands for snapshot testing, pipeline dry-runs, relation checks, and performance benchmarking.
- Modular Architecture: components are grouped under `Library/Components/*` and helpers `Library/Helpers/*` for clarity and reuse.
- Laravel-first: integrates with Laravel routing, views, service container, and vendor publishing.

---

## 2) Requirements

- PHP 8.1+
- Laravel 10 (tested)
- Node/Vite for assets (optional)
- Composer dependencies (excerpt):
  - yajra/laravel-datatables ~9.0
  - laravelcollective/html ~6.4
  - doctrine/dbal ~3.4
  - intervention/image ~3.9

---

## 3) Installation

```bash
# Create Laravel project (example)
composer create-project --prefer-dist laravel/laravel:10.0 myapp
cd myapp

# Install CanvaStack
composer require canvastack/canvastack
```

If you install from a VCS repository, add to composer.json:

```json
"require": {
  "canvastack/canvastack": "dev-master"
},
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:canvastack/canvastack.git"
  }
]
```

---

## 4) Publishing & Setup

1) Publish package resources:
```bash
php artisan vendor:publish --tag="CanvaStack" --force
php artisan vendor:publish --tag="CanvaStack Public Folder" --force
```

This publishes to your app:
- database/migrations, database/seeders
- config/*.php (e.g., canvastack.php, canvastack.settings.php, canvastack.datatables.php, etc.)
- routes/*
- app/* (scaffold)
- resources/views/* (templates)
- public/* (assets)

2) Configure environment:
- Ensure DB settings in `.env` are correct.

3) Run migrations and optional seeders:
```bash
php artisan migrate --seed
```

---

## 5) Quick Start

Example: render a simple table using Canvatility.

Controller action:
```php
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

public function previewSystemLog() {
    $header = ['no', 'id', 'message', 'level', 'created_at', 'Action'];
    $rows = [
        ['1','1001','Boot OK','info','2025-09-01', Canvatility::createActionButtons('/logs/1001','/logs/1001/edit', false, [])],
        ['2','1002','Job Done','success','2025-09-01', Canvatility::createActionButtons('/logs/1002', false, false, [])],
    ];

    $html = Canvatility::generateTable(
        'System Log',      // title
        'syslog',          // id suffix
        $header,
        $rows,
        ['class' => 'table table-bordered'],
        false,             // numbering
        true               // containers
    );

    return view('admin.table_preview', [
        'title' => 'system.config.log',
        'tableHtml' => $html,
    ]);
}
```

Blade preview (`resources/views/admin/table_preview.blade.php`) auto-initializes DataTables with:
- processing: true
- deferRender: true
- serverSide: true (if table has data attributes for AJAX)

To use server-side in preview, pass a server-side URL:
```php
$html = Canvatility::generateTable(
    'System Log', 'syslog', $header, [], ['class' => 'table table-bordered'],
    false, true,
    true,                      // server_side
    route('preview.system.config.log.data') // server_side_custom_url
);
```

Short form example (see docs for more):
```php
{!! Canvatility::formOpen(['route' => 'users.store']) !!}
  {!! Canvatility::formLabel('name', 'Full Name') !!}
  {!! Canvatility::formText('name', old('name'), ['class' => 'form-control']) !!}
  {!! Canvatility::formSubmit('Create', ['class' => 'btn btn-primary']) !!}
{!! Canvatility::formClose() !!}
```

---

## 6) Components Overview

- Form System
  - Declarative form generation, helpers under `Library/Helpers/FormObject.php` and components under `Library/Components/Form`.
  - Consistent Bootstrap-ready markup and validation messages.

- Table System
  - Builder in `Library/Helpers/Table.php` and core in `Library/Components/Table/*`.
  - Integrates Yajra DataTables with action buttons, column renderers, and performance utilities.

- Utility (Canvatility)
  - HTML, attributes, assets, URL, templates, scripts, charts helpers.
  - Central facade to keep views/controllers clean and DRY.

---

## 7) Documentation Links

- Forms: [docs/form/README.md](docs/form/README.md)
- Tables: [docs/table/README.md](docs/table/README.md)
- Utility (Canvatility): [docs/utility/README.md](docs/utility/README.md)
- Template Engine: [docs/template/README.md](docs/template/README.md)
- User & Role Management: [docs/auth/README.md](docs/auth/README.md)

---

## 8) Support Matrix

| CanvaStack | PHP   | Laravel | yajra/laravel-datatables | laravelcollective/html |
|-----------:|:-----:|:------:|:-------------------------:|:----------------------:|
| 1.x-dev    | 8.1+  | 10.x   | ~9.0                      | ~6.4                   |

---

## 9) DataTables & Performance Notes

- Preview tables now initialize with:
  - `processing: true` for better UX while loading
  - `deferRender: true` to reduce initial DOM cost
  - Optional `serverSide: true` when data attributes `data-server-side="1"` and `data-ajax-url` are present

- Server-side JSON must follow DataTables spec (draw, recordsTotal, recordsFiltered, data). Yajra integration in controllers typically handles this.

- For very large datasets:
  - Prefer server-side mode.
  - Avoid eager-loading entire result sets just for column detection; sample a single row to register renderers.

---

## 10) CLI Tools

Artisan commands are registered by the service provider. Explore available commands with:
```bash
php artisan list | findstr /i canvastack
```

See detailed docs: [src/docs/CANVASTACK_CLI.md](src/docs/CANVASTACK_CLI.md)

Common tools include (names may vary by version):
- canvastack:test
- canvastack:snapshot:validate
- canvastack:snapshot:make
- canvastack:pipeline:dry-run
- canvastack:db:check
- canvastack:inspector:summary
- canvastack:pipeline:bench
- canvastack:relation:bench

---

## 11) Configuration

Published config files (examples):
- `config/canvastack.php`
- `config/canvastack.settings.php`
- `config/canvastack.datatables.php`
- `config/canvastack.routes.php`
- `config/canvastack.templates.php`
- `config/canvastack.connections.php`
- `config/canvastack.registers.php`

Tune base URLs, templates, DataTables defaults, route mounting, and feature flags here.

---

## 12) Directory Structure (package)

```
packages/canvastack/canvastack/
├─ src/
│  ├─ CanvastackServiceProvider.php
│  ├─ Controllers/
│  ├─ Library/
│  │  ├─ Components/
│  │  └─ Helpers/
│  ├─ Models/
│  ├─ Publisher/    # publishable scaffolds (config, routes, views, database, public)
│  ├─ routes/web.php
│  └─ docs/
├─ composer.json
├─ LICENSE
└─ README.md
```

---

## 13) Upgrading from Incodiy

- Package renamed to `canvastack/canvastack`.
- Namespace standardized: `Canvastack\Canvastack`.
- Facade alias: `CanvaStack`.
- Publish tags: `CanvaStack` and `CanvaStack Public Folder`.
- Configuration file names use `canvastack.*.php` prefix.

Migration tips:
- Update composer package name and namespaces in your code.
- Re-publish assets/configs if you rely on vendor files.
- Review helpers (Form/Table/Utility) usage and adapt to new facade naming.

---

## 14) Changelog

See [CHANGELOG.md](CHANGELOG.md) for notable changes and migration notes.

---

## 15) Roadmap

- Authz Module (RBAC): dedicated role/permission system with facade and middleware, backward-compatible with legacy privilege mapping.
- Performance: continued optimization for DataTables orchestration and server-side pipelines.
- Testing: snapshot tests for UI consistency and behavior-preserving refactors.

---

## 16) Contributing

Pull requests and issues are welcome. Please:
- Keep changes focused and covered by tests where practical.
- Follow existing code style and component organization.

---

## 17) License

MIT License. See the `LICENSE` file for details.