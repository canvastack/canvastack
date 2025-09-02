# Components/Utility — Canvatility Facade

This module hosts generic utilities migrated from legacy Helpers (e.g., Scripts.php) into organized, testable classes.

## Structure
- Html/ElementExtractor.php
- Assets/AssetPath.php
- Url/PathResolver.php
- Canvatility.php (static facade)

## Usage (new)
```php
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

$html = '<img src="/a.jpg" alt="x">';
$el = Canvatility::elementValue($html, 'img', 'src', true);
$base = Canvatility::assetBasePath();
$path = Canvatility::checkStringPath('css/app.css', false);
```

## Backward Compatibility
Legacy global functions continue to work via shim delegation in `Library/Helpers/Scripts.php`:
- `canvastack_script_html_element_value(...);`
- `canvastack_script_asset_path();`
- `canvastack_script_check_string_path(...);`

Enable deprecation notices in dev/CI only:
```php
// config/canvastack.php
'utility' => [
    'deprecate_scripts_helpers' => env('CANVASTACK_UTILITY_DEPRECATE_SCRIPTS_HELPERS', false),
],
```