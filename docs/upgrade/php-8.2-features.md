# PHP 8.2+ Features Adoption

## Overview

This document outlines the PHP 8.2+ features adopted in CanvaStack and provides examples of their usage.

**Status**: Completed  
**Version**: 1.0.0  
**Last Updated**: 2026-03-01

---

## Adopted Features

### 1. Readonly Classes

**Feature**: Classes where all properties are implicitly readonly.

**Benefits**:
- Immutability by default
- Less boilerplate code
- Better performance
- Clearer intent

**Examples**:

#### Value Objects

```php
readonly class ThemeConfig
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $version,
        public array $colors,
        public array $fonts,
    ) {}
}
```

#### DTOs (Data Transfer Objects)

```php
readonly class UserPreference
{
    public function __construct(
        public string $theme,
        public string $locale,
        public bool $darkMode,
    ) {}
}
```

### 2. Disjunctive Normal Form (DNF) Types

**Feature**: Combine union and intersection types.

**Syntax**: `(A&B)|(C&D)`

**Examples**:

```php
class CacheManager
{
    public function store(
        string $key,
        (Stringable&JsonSerializable)|array|string $value
    ): bool {
        // Implementation
    }
}
```

### 3. Constants in Traits

**Feature**: Define constants in traits.

**Examples**:

```php
trait HasCaching
{
    public const CACHE_TTL = 3600;
    public const CACHE_PREFIX = 'canvastack:';
    
    protected function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }
}
```

### 4. Null, False, and True as Standalone Types

**Feature**: Use `null`, `false`, and `true` as standalone types.

**Examples**:

```php
class Validator
{
    public function validate(array $data): true
    {
        // Returns true or throws exception
        if (!$this->isValid($data)) {
            throw new ValidationException();
        }
        
        return true;
    }
    
    public function tryValidate(array $data): bool
    {
        // Returns true or false
        return $this->isValid($data);
    }
}
```

### 5. Deprecate Dynamic Properties

**Feature**: Dynamic properties are deprecated in PHP 8.2.

**Solution**: Use `#[AllowDynamicProperties]` attribute or define properties explicitly.

**Examples**:

```php
// Option 1: Allow dynamic properties (not recommended)
#[AllowDynamicProperties]
class LegacyClass
{
    // ...
}

// Option 2: Define properties explicitly (recommended)
class ModernClass
{
    protected array $data = [];
    
    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }
    
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
```

---

## Implementation Examples

### Example 1: Theme Value Object

**Before (PHP 8.1)**:

```php
class ThemeConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly string $version,
        public readonly array $colors,
        public readonly array $fonts,
    ) {}
}
```

**After (PHP 8.2)**:

```php
readonly class ThemeConfig
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $version,
        public array $colors,
        public array $fonts,
    ) {}
}
```

### Example 2: Cache Configuration

**Before (PHP 8.1)**:

```php
trait HasCaching
{
    protected function getCacheTtl(): int
    {
        return 3600;
    }
    
    protected function getCachePrefix(): string
    {
        return 'canvastack:';
    }
}
```

**After (PHP 8.2)**:

```php
trait HasCaching
{
    public const CACHE_TTL = 3600;
    public const CACHE_PREFIX = 'canvastack:';
    
    protected function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }
}
```

### Example 3: Validation Return Types

**Before (PHP 8.1)**:

```php
class Validator
{
    public function validate(array $data): bool
    {
        if (!$this->isValid($data)) {
            throw new ValidationException();
        }
        
        return true;
    }
}
```

**After (PHP 8.2)**:

```php
class Validator
{
    public function validate(array $data): true
    {
        if (!$this->isValid($data)) {
            throw new ValidationException();
        }
        
        return true;
    }
}
```

---

## Migration Checklist

### Readonly Classes

- [x] Identify value objects and DTOs
- [x] Convert to readonly classes
- [x] Remove individual readonly keywords
- [x] Test immutability

### Constants in Traits

- [x] Identify trait constants
- [x] Move constants to traits
- [x] Update references
- [x] Test functionality

### Standalone Types

- [x] Identify methods that always return true/false/null
- [x] Update return types
- [x] Update PHPDoc
- [x] Test type safety

### Dynamic Properties

- [x] Identify classes with dynamic properties
- [x] Define properties explicitly
- [x] Add `#[AllowDynamicProperties]` if needed
- [x] Test functionality

---

## Performance Benefits

### Readonly Classes

- **Memory**: Reduced memory overhead
- **Performance**: Faster property access
- **Optimization**: Better opcache optimization

### Constants in Traits

- **Performance**: Compile-time constants
- **Memory**: No runtime overhead

### Standalone Types

- **Type Safety**: Better type checking
- **Performance**: Faster type validation

---

## Testing

### Unit Tests

```php
public function test_readonly_class_is_immutable()
{
    $config = new ThemeConfig(
        name: 'default',
        displayName: 'Default Theme',
        version: '1.0.0',
        colors: ['primary' => '#6366f1'],
        fonts: ['sans' => 'Inter'],
    );
    
    $this->assertEquals('default', $config->name);
    
    // This should fail (readonly)
    $this->expectException(Error::class);
    $config->name = 'modified';
}

public function test_trait_constants_are_accessible()
{
    $class = new class {
        use HasCaching;
    };
    
    $this->assertEquals(3600, $class::CACHE_TTL);
    $this->assertEquals('canvastack:', $class::CACHE_PREFIX);
}

public function test_true_return_type()
{
    $validator = new Validator();
    
    $result = $validator->validate(['valid' => true]);
    
    $this->assertTrue($result);
    $this->assertIsBool($result);
}
```

---

## Best Practices

### 1. Use Readonly Classes for Value Objects

✅ **DO**:
```php
readonly class UserPreference
{
    public function __construct(
        public string $theme,
        public string $locale,
    ) {}
}
```

❌ **DON'T**:
```php
class UserPreference
{
    public function __construct(
        public readonly string $theme,
        public readonly string $locale,
    ) {}
}
```

### 2. Use Constants in Traits for Shared Values

✅ **DO**:
```php
trait HasCaching
{
    public const CACHE_TTL = 3600;
    
    protected function cache(string $key, mixed $value): void
    {
        Cache::put($key, $value, self::CACHE_TTL);
    }
}
```

❌ **DON'T**:
```php
trait HasCaching
{
    protected function getCacheTtl(): int
    {
        return 3600;
    }
}
```

### 3. Use Standalone Types for Clear Intent

✅ **DO**:
```php
public function validate(array $data): true
{
    // Always returns true or throws
}

public function tryValidate(array $data): bool
{
    // Returns true or false
}
```

❌ **DON'T**:
```php
public function validate(array $data): bool
{
    // Unclear if it returns false or throws
}
```

---

## Resources

### PHP Documentation

- [Readonly Classes](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)
- [DNF Types](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.dnf)
- [Constants in Traits](https://www.php.net/manual/en/language.oop5.traits.php#language.oop5.traits.constants)
- [Standalone Types](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.null-false-true)

### Migration Guides

- [PHP 8.2 Migration Guide](https://www.php.net/manual/en/migration82.php)
- [PHP 8.2 New Features](https://www.php.net/releases/8.2/en.php)

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-03-01  
**Status**: Completed  
**Maintainer**: CanvaStack Team
