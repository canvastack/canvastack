# Translation Management

Complete guide to managing translations in CanvaStack using the advanced translation management system.

## 📦 Location

- **Registry**: `Canvastack\Canvastack\Support\Localization\TranslationRegistry`
- **Detector**: `Canvastack\Canvastack\Support\Localization\MissingTranslationDetector`
- **Fallback**: `Canvastack\Canvastack\Support\Localization\TranslationFallback`
- **Cache**: `Canvastack\Canvastack\Support\Localization\TranslationCache`
- **Version**: `Canvastack\Canvastack\Support\Localization\TranslationVersion`

## 🎯 Features

- Translation key registry with usage tracking
- Missing translation detection and reporting
- Intelligent fallback system with locale chains
- Advanced caching with warming and invalidation
- Version control with snapshots and rollback
- Export/import capabilities
- Comprehensive statistics and reporting

---

## Translation Registry

### Overview

The Translation Registry maintains a complete index of all translation keys used in your application, tracking usage statistics and coverage across locales.

### Basic Usage

```php
use Canvastack\Canvastack\Support\Localization\TranslationRegistry;

$registry = app('canvastack.translation.registry');

// Build registry from all translation files
$registry->buildRegistry();

// Track key usage
$registry->trackUsage('ui.welcome');

// Get key information
$info = $registry->get('ui.welcome');
// Returns: ['key' => 'ui.welcome', 'locales' => [...], 'usage_count' => 1, ...]
```

### Statistics

```php
// Get comprehensive statistics
$stats = $registry->getStatistics();
/*
[
    'total_keys' => 150,
    'total_usage' => 1250,
    'unused_keys' => 10,
    'locales' => [
        'en' => ['translated' => 150, 'missing' => 0, 'coverage' => 100],
        'id' => ['translated' => 140, 'missing' => 10, 'coverage' => 93.33],
    ]
]
*/

// Get missing translations for a locale
$missing = $registry->getMissing('id');

// Get unused translations
$unused = $registry->getUnused();

// Get rarely used translations
$rarelyUsed = $registry->getRarelyUsed(5); // Used less than 5 times

// Get most used translations
$mostUsed = $registry->getMostUsed(10);
```

### Export/Import

```php
// Export registry to JSON
$registry->export(storage_path('app/translations/registry.json'));

// Import registry from JSON
$registry->import(storage_path('app/translations/registry.json'));
```

### Artisan Commands

```bash
# Build registry
php artisan canvastack:translation:registry build

# Export registry
php artisan canvastack:translation:registry export --path=storage/app/registry.json

# Show statistics
php artisan canvastack:translation:registry stats
```

---

## Missing Translation Detection

### Overview

Automatically detects and logs missing translations in real-time, providing detailed reports and analytics.

### Basic Usage

```php
use Canvastack\Canvastack\Support\Localization\MissingTranslationDetector;

$detector = app('canvastack.translation.detector');

// Detection happens automatically when translations are accessed
// Manual detection:
$detector->detect('nonexistent.key', 'en');

// Get all missing translations
$missing = $detector->all();

// Get missing by locale
$enMissing = $detector->getByLocale('en');

// Get missing by key
$keyMissing = $detector->getByKey('ui.welcome');
```

### Reports

```php
// Generate comprehensive report
$report = $detector->generateReport();
/*
[
    'generated_at' => '2026-02-27 10:00:00',
    'statistics' => [...],
    'most_frequent' => [...],
    'recent' => [...],
    'by_locale' => [...],
    'by_group' => [...]
]
*/

// Get most frequently missing
$frequent = $detector->getMostFrequent(10);

// Get recently missing (last 24 hours)
$recent = $detector->getRecent(24);

// Get statistics
$stats = $detector->getStatistics();
```

### Export Reports

```php
// Export to JSON
$detector->exportReport(storage_path('app/missing.json'), 'json');

// Export to CSV
$detector->exportReport(storage_path('app/missing.csv'), 'csv');
```

### Configuration

```php
// config/canvastack.php
'localization' => [
    'detect_missing' => true,  // Enable detection
    'log_missing' => true,     // Log to Laravel log
],
```

### Artisan Commands

```bash
# List missing translations
php artisan canvastack:translation:missing list
php artisan canvastack:translation:missing list --locale=id

# Show report
php artisan canvastack:translation:missing report

# Export report
php artisan canvastack:translation:missing export --format=json --path=storage/app/missing.json
php artisan canvastack:translation:missing export --format=csv

# Clear missing translations
php artisan canvastack:translation:missing clear
php artisan canvastack:translation:missing clear --locale=id
```

---

## Translation Fallback

### Overview

Provides intelligent fallback mechanism for missing translations with support for locale chains and multiple strategies.

### Basic Usage

```php
use Canvastack\Canvastack\Support\Localization\TranslationFallback;

$fallback = app('canvastack.translation.fallback');

// Get translation with automatic fallback
$value = $fallback->get('ui.welcome', [], 'id');
// If not found in 'id', falls back to chain: id -> en -> default

// Check if translation exists (including fallbacks)
$exists = $fallback->has('ui.welcome', 'id');

// Get translation source (which locale provided it)
$source = $fallback->getSource('ui.welcome', 'id');
// Returns: 'id' or 'en' or null
```

### Fallback Chains

```php
// Get fallback chain for a locale
$chain = $fallback->getFallbackChain('id');
// Returns: ['en']

// Set custom fallback chain
$fallback->setFallbackChain('id', ['en', 'es']);

// Add fallback locale
$fallback->addFallback('id', 'es');

// Remove fallback locale
$fallback->removeFallback('id', 'es');
```

### Fallback Strategies

```php
// Set fallback strategy
$fallback->setStrategy('chain');  // Use locale chain (default)
$fallback->setStrategy('default'); // Fall back to default locale only
$fallback->setStrategy('key');     // Return key itself
$fallback->setStrategy('empty');   // Return empty string

// Get current strategy
$strategy = $fallback->getStrategy();
```

### Configuration

```php
// config/canvastack.php
'localization' => [
    'fallback_strategy' => 'chain',
    'fallback_chain' => [
        'id' => ['en'],
        'es' => ['en'],
        'en' => [],
    ],
],
```

---

## Translation Cache

### Overview

Advanced caching system for translations with warming, invalidation, and statistics.

### Basic Usage

```php
use Canvastack\Canvastack\Support\Localization\TranslationCache;

$cache = app('canvastack.translation.cache');

// Get translation (automatically cached)
$value = $cache->get('en', 'ui.welcome');

// Get all translations for locale
$translations = $cache->getAll('en');

// Put translation in cache
$cache->put('en', 'custom.key', 'Custom Value');

// Check if cached
$exists = $cache->has('en', 'ui.welcome');
```

### Cache Management

```php
// Warm cache for locale
$count = $cache->warm('en');
$count = $cache->warm('en', ['ui', 'validation']); // Specific groups

// Flush cache
$cache->flush('en');              // Flush locale
$cache->flushGroup('en', 'ui');   // Flush group
$cache->flushAll();               // Flush all

// Refresh cache (flush + warm)
$count = $cache->refresh('en');
$count = $cache->refresh('en', ['ui']);

// Invalidate cache
$cache->invalidate('en');
$cache->invalidate('en', 'ui');
```

### Statistics

```php
$stats = $cache->getStatistics();
/*
[
    'enabled' => true,
    'driver' => 'redis',
    'ttl' => 3600,
    'locales' => [
        'en' => ['cached_keys' => 150],
        'id' => ['cached_keys' => 140],
    ]
]
*/
```

### Configuration

```php
// config/canvastack.php
'localization' => [
    'cache_enabled' => true,
    'cache_driver' => 'redis',
    'cache_ttl' => 3600,
],
```

### Artisan Commands

```bash
# Warm cache
php artisan canvastack:translation:cache warm
php artisan canvastack:translation:cache warm --locale=en
php artisan canvastack:translation:cache warm --locale=en --group=ui

# Flush cache
php artisan canvastack:translation:cache flush
php artisan canvastack:translation:cache flush --locale=en
php artisan canvastack:translation:cache flush --locale=en --group=ui

# Refresh cache
php artisan canvastack:translation:cache refresh
php artisan canvastack:translation:cache refresh --locale=en

# Show statistics
php artisan canvastack:translation:cache stats
```

---

## Translation Versioning

### Overview

Version control system for translations with snapshots, rollback, and diff capabilities.

### Basic Usage

```php
use Canvastack\Canvastack\Support\Localization\TranslationVersion;

$version = app('canvastack.translation.version');

// Create snapshot
$version->createSnapshot('1.0.0', 'Initial release');
$version->createSnapshot('1.1.0', 'Added new features', ['en', 'id']);

// Load snapshot
$snapshot = $version->loadSnapshot('1.0.0');

// Restore from snapshot
$version->restore('1.0.0');
$version->restore('1.0.0', ['en']); // Specific locales only
```

### Version Management

```php
// Get all versions
$versions = $version->getAllVersions();
/*
[
    '1.0.0' => [
        'version' => '1.0.0',
        'description' => 'Initial release',
        'created_at' => '2026-02-27 10:00:00',
        'created_by' => 'admin'
    ],
    ...
]
*/

// Get version metadata
$metadata = $version->getVersionMetadata('1.0.0');

// Get current version
$current = $version->getCurrentVersion();

// Set current version
$version->setCurrentVersion('1.1.0');

// Delete version
$version->deleteVersion('1.0.0');
```

### Version Diff

```php
// Compare two versions
$diff = $version->diff('1.0.0', '1.1.0');
/*
[
    'from_version' => '1.0.0',
    'to_version' => '1.1.0',
    'locales' => [
        'en' => [
            'added' => ['ui.new_feature' => 'New Feature'],
            'removed' => ['ui.old_feature' => 'Old Feature'],
            'modified' => [
                'ui.welcome' => [
                    'from' => 'Welcome',
                    'to' => 'Welcome!'
                ]
            ]
        ]
    ]
]
*/
```

### Export/Import

```php
// Export version
$version->export('1.0.0', storage_path('app/version-1.0.0.json'));

// Import version
$version->import(storage_path('app/version-1.0.0.json'), '1.0.0');
```

### Artisan Commands

```bash
# Create snapshot
php artisan canvastack:translation:version snapshot 1.0.0 --description="Initial release"
php artisan canvastack:translation:version snapshot 1.1.0 --locale=en

# Restore version
php artisan canvastack:translation:version restore 1.0.0
php artisan canvastack:translation:version restore 1.0.0 --locale=en

# Show diff
php artisan canvastack:translation:version diff --from=1.0.0 --to=1.1.0

# List versions
php artisan canvastack:translation:version list

# Delete version
php artisan canvastack:translation:version delete 1.0.0

# Export version
php artisan canvastack:translation:version export 1.0.0 --path=storage/app/version.json

# Import version
php artisan canvastack:translation:version import 1.0.0 --path=storage/app/version.json
```

---

## 💡 Best Practices

### 1. Registry Management

- Build registry after adding new translations
- Track usage in production to identify unused keys
- Export registry regularly for backup
- Review coverage statistics monthly

### 2. Missing Translation Detection

- Enable detection in development and staging
- Review missing translations weekly
- Export reports before releases
- Clear old missing translations periodically

### 3. Fallback Configuration

- Set up logical fallback chains (e.g., regional → language → English)
- Use 'chain' strategy for best user experience
- Test fallback behavior for all locales
- Document fallback chains in your project

### 4. Cache Management

- Warm cache after deployments
- Set appropriate TTL based on update frequency
- Use Redis for production caching
- Monitor cache hit rates

### 5. Version Control

- Create snapshots before major changes
- Use semantic versioning (1.0.0, 1.1.0, 2.0.0)
- Add descriptive version descriptions
- Keep at least 3 recent versions
- Export versions before cleanup

---

## 🧪 Testing

### Unit Tests

```php
// Test registry
$registry = app('canvastack.translation.registry');
$registry->buildRegistry();
$this->assertGreaterThan(0, count($registry->all()));

// Test detector
$detector = app('canvastack.translation.detector');
$detector->detect('test.key', 'en');
$this->assertNotEmpty($detector->all());

// Test fallback
$fallback = app('canvastack.translation.fallback');
$value = $fallback->get('ui.welcome', [], 'id');
$this->assertIsString($value);

// Test cache
$cache = app('canvastack.translation.cache');
$count = $cache->warm('en');
$this->assertGreaterThan(0, $count);

// Test versioning
$version = app('canvastack.translation.version');
$result = $version->createSnapshot('test-1.0.0', 'Test');
$this->assertTrue($result);
```

---

## 🔗 Related Documentation

- [Locale Switcher](locale-switcher.md) - UI component for switching locales
- [Translation Loader](translation-loader.md) - Loading translation files
- [Locale Manager](locale-manager.md) - Managing application locale

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published

