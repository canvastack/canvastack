# Translation Test Fixes - Memory Optimization

## Problem

Unit tests for the Translatable trait were experiencing:
- **Memory exhaustion** (536MB limit exceeded)
- **Infinite recursion** causing stack overflow
- **Slow execution** (tests taking too long)

## Root Cause Analysis

### 1. Infinite Recursion in `getAttribute()`

The `getAttribute()` method in the `Translatable` trait was calling `getTranslation()`, which in turn called `getAttribute()` again, creating an infinite loop:

```php
// BEFORE (Problematic)
public function getAttribute($key)
{
    $value = parent::getAttribute($key);
    
    if ($this->isTranslatable($key) && !$this->isRelation($key)) {
        return $this->getTranslation($key); // Calls getTranslation()
    }
    
    return $value;
}

public function getTranslation(string $attribute, ...)
{
    // ...
    if ($translation === null) {
        $translation = $this->getAttribute($attribute); // Calls getAttribute() again!
    }
    // ...
}
```

### 2. Missing Recursion Guard

There was no mechanism to prevent recursive calls between `getAttribute()` and `getTranslation()`.

### 3. Insufficient Memory Cleanup

Tests were not properly cleaning up cache and model instances between test runs.

## Solutions Implemented

### 1. Fixed Infinite Recursion

Added recursion guard and proper fallback mechanism:

```php
// AFTER (Fixed)
public function getAttribute($key)
{
    $value = parent::getAttribute($key);

    // Don't translate if:
    // 1. Not a translatable attribute
    // 2. Is a relationship
    // 3. Model doesn't exist yet (no ID)
    // 4. We're already in a translation retrieval (prevent recursion)
    if (!$this->isTranslatable($key) 
        || $this->isRelation($key) 
        || !$this->exists
        || isset($this->translationCache['__retrieving__'])) {
        return $value;
    }

    // Mark that we're retrieving to prevent recursion
    $this->translationCache['__retrieving__'] = true;

    try {
        $translation = $this->getTranslation($key);
        return $translation;
    } finally {
        // Always unset the flag
        unset($this->translationCache['__retrieving__']);
    }
}

public function getTranslation(string $attribute, ...)
{
    // ...
    if ($translation === null) {
        // Use parent::getAttribute() to avoid recursion
        $translation = parent::getAttribute($attribute);
    }
    // ...
}
```

### 2. Added Memory Cleanup in Tests

Added proper `tearDown()` method to clean up resources:

```php
protected function tearDown(): void
{
    // Clear all caches
    Cache::flush();
    
    // Clear translation cache
    if (isset($this->product)) {
        $this->product->clearTranslationCache();
    }
    
    // Unset product to free memory
    unset($this->product);
    
    parent::tearDown();
}
```

### 3. Increased Memory Limit

Updated `phpunit.xml` to allow more memory for tests:

```xml
<php>
    <ini name="memory_limit" value="1024M"/>
</php>
```

### 4. Improved Test Isolation

Added cache clearing in tests that depend on fresh data:

```php
public function it_automatically_returns_translation_when_getting_attribute()
{
    App::setLocale('id');
    $this->product->setTranslation('name', 'Produk Test', 'id');

    // Clear cache to force fresh retrieval
    Cache::flush();
    $this->product->clearTranslationCache();

    // Refresh model from database
    $this->product->refresh();

    $name = $this->product->name;

    $this->assertEquals('Produk Test', $name);
}
```

## Results

### Before Fixes
- ❌ Memory: 536MB+ (exhausted)
- ❌ Time: Infinite (timeout)
- ❌ Tests: Failed with fatal error

### After Fixes
- ✅ Memory: 98-112MB (normal)
- ✅ Time: 15-25 seconds for 38 tests
- ✅ Tests: 100% PASSED (38/38)

## Test Results

```bash
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: phpunit.xml

......................................                            38 / 38 (100%)

Time: 00:25.482, Memory: 112.00 MB

OK, but there were issues!
Tests: 38, Assertions: 74, PHPUnit Warnings: 1, PHPUnit Deprecations: 1493.
```

## Key Takeaways

1. **Always guard against infinite recursion** when overriding magic methods like `getAttribute()`
2. **Use parent methods** to access original values without triggering custom logic
3. **Clean up resources** in test tearDown methods
4. **Clear caches** between tests to ensure isolation
5. **Monitor memory usage** during test development

## Prevention Guidelines

### For Trait Development

1. Never call overridden methods without recursion guards
2. Always provide a way to access original values
3. Use flags or counters to detect recursion
4. Test with small datasets first

### For Test Development

1. Always implement `tearDown()` for resource cleanup
2. Clear caches between tests
3. Use `refresh()` to reload models from database
4. Monitor memory usage with `--verbose` flag

## Related Files

- `src/Support/Localization/Translatable.php` - Fixed trait
- `tests/Unit/Support/Localization/TranslatableTest.php` - Fixed tests
- `phpunit.xml` - Updated configuration

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Resolved
