# CanvaStack Utility (Canvatility)

The Utility module provides a single static facade `Canvatility` that consolidates HTML helpers, DataTables table builders, template utilities, URL/asset tools, DB schema helpers, and JSON helpers. It aims to reduce cross-module coupling by delegating to small, focused classes.

- Root class: `Canvastack\\Canvastack\\Library\\Components\\Utility\\Canvatility`
- Submodules: `Html/*`, `Table/*`, `Data/*`, `Db/*`, `Json/*`, `Url/*`, `Assets/*`

## Highlights

- `attributesToString()` to normalize attributes arrays to HTML attributes.
- `elementValue()` to extract values from HTML nodes.
- Centralized UI helpers for forms, tables, and template fragments.
- Convenience wrappers around LaravelCollective form functions.

## HTML Utilities

```php
$html = '<input type="text" name="email" value="a@b.com" class="form-control">';
$value = Canvatility::elementValue($html, 'input', 'value');
$attrs = Canvatility::attributesToString(['class' => 'btn btn-primary', 'id' => 'submit']);
```

## URL & Assets

```php
$base = Canvatility::assetBasePath();
$path = Canvatility::checkStringPath('assets/images/logo.png', true); // returns resolved path or null
```

## DB Schema

```php
$tables  = Canvatility::getAllTables();
$exists  = Canvatility::hasColumn('users', 'email');
$columns = Canvatility::getColumns('users');
$type    = Canvatility::getColumnType('users', 'email');
```

## JSON Tools

```php
$pretty = Canvatility::clearJson("{\n  \"k\":\n\t\t\"v\"\n}");
```

## Template Convenience

```php
// Breadcrumbs, sidebar, avatar, CSS/JS injection
$bc = Canvatility::breadcrumb('Users', ['Home' => '/', 'Users' => '/users']);
$css = Canvatility::css(['css/app.css']);
$js  = Canvatility::js(['js/app.js']);
```

## Table Utilities

See Table docs for `generateTable`, `createActionButtons`, `modalContentHtml`, plus filters and formula columns.

## Form Utilities

See Form docs for `formOpen`, `formClose`, `formLabel`, `formText`, `formPassword`, `formSubmit`, `formSelectbox`, and more.

## Error Handling & Contracts

- Methods are designed to be side-effect free and return strings/arrays.
- Many helpers gracefully degrade when optional config is absent.
- Prefer passing explicit arrays with clear keys for attributes.

## Testing

- See `src/Library/Components/Utility/tests` for behavior and edge cases (attributes rendering, buttons building, modal content, and formula columns).