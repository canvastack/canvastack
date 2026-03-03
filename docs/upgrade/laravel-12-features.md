# Laravel 12 Features Adoption

## Overview

This document outlines the Laravel 12 features adopted in CanvaStack and provides examples of their usage.

**Status**: Completed  
**Version**: 1.0.0  
**Last Updated**: 2026-03-01

---

## Adopted Features

### 1. Improved Collection Methods

**Feature**: New collection methods for better data manipulation.

**New Methods**:
- `sole()` - Get the only item, throw if multiple
- `firstOrFail()` - Get first item or throw exception
- `value()` - Get value using dot notation
- `ensure()` - Ensure collection contains only specific types

**Examples**:

```php
use Illuminate\Support\Collection;

// sole() - Get single item
$theme = collect($themes)->sole('name', 'default');

// firstOrFail() - Get first or throw
$user = collect($users)->firstOrFail(fn($u) => $u->isActive());

// ensure() - Type safety
$strings = collect(['a', 'b', 'c'])->ensure('string');
```

### 2. Improved Validation

**Feature**: New validation rules and better error messages.

**New Rules**:
- `decimal:min,max` - Validate decimal places
- `lowercase` - Ensure lowercase
- `uppercase` - Ensure uppercase
- `ascii` - Ensure ASCII characters only

**Examples**:

```php
$request->validate([
    'price' => 'required|decimal:2,4',
    'username' => 'required|lowercase|ascii',
    'code' => 'required|uppercase',
]);
```

### 3. Improved Database Query Builder

**Feature**: Better query building with new methods.

**New Methods**:
- `sole()` - Get single record or throw
- `value()` - Get single column value
- `valueOrFail()` - Get value or throw
- `implode()` - Implode column values

**Examples**:

```php
// sole() - Get single record
$user = User::where('email', $email)->sole();

// value() - Get single value
$name = User::where('id', 1)->value('name');

// valueOrFail() - Get value or throw
$email = User::where('id', 1)->valueOrFail('email');

// implode() - Implode values
$names = User::query()->implode('name', ', ');
```

### 4. Improved Cache

**Feature**: Better cache management with new methods.

**New Methods**:
- `flexible()` - Flexible cache with grace period
- `missing()` - Check if key is missing
- `pull()` - Get and delete

**Examples**:

```php
use Illuminate\Support\Facades\Cache;

// flexible() - Cache with grace period
$value = Cache::flexible('key', [5, 10], function () {
    return expensive_operation();
});

// missing() - Check if missing
if (Cache::missing('key')) {
    Cache::put('key', 'value', 3600);
}

// pull() - Get and delete
$value = Cache::pull('key');
```

### 5. Improved String Helpers

**Feature**: New string manipulation methods.

**New Methods**:
- `isMatch()` - Check if string matches pattern
- `isUuid()` - Check if valid UUID
- `isUlid()` - Check if valid ULID
- `wrap()` - Wrap string with prefix/suffix

**Examples**:

```php
use Illuminate\Support\Str;

// isMatch() - Pattern matching
if (Str::isMatch('/^[a-z]+$/', $input)) {
    // Valid lowercase string
}

// isUuid() - UUID validation
if (Str::isUuid($id)) {
    // Valid UUID
}

// wrap() - Wrap string
$wrapped = Str::wrap('content', '<div>', '</div>');
// Result: <div>content</div>
```

### 6. Improved HTTP Client

**Feature**: Better HTTP client with new methods.

**New Methods**:
- `throw()` - Throw on error
- `throwIf()` - Conditional throw
- `throwUnless()` - Conditional throw
- `sink()` - Stream response to file

**Examples**:

```php
use Illuminate\Support\Facades\Http;

// throw() - Throw on error
$response = Http::get('https://api.example.com/data')->throw();

// throwIf() - Conditional throw
$response = Http::get('https://api.example.com/data')
    ->throwIf(fn($r) => $r->status() === 404);

// sink() - Stream to file
Http::sink(storage_path('downloads/file.zip'))
    ->get('https://example.com/file.zip');
```

### 7. Improved Artisan Commands

**Feature**: Better command creation and handling.

**New Features**:
- Prompts API for interactive commands
- Better progress bars
- Improved table output

**Examples**:

```php
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

// Interactive prompts
$name = text('What is your name?');

$role = select(
    'What is your role?',
    ['admin', 'user', 'guest']
);

$confirmed = confirm('Do you want to continue?');
```

### 8. Improved Testing

**Feature**: Better testing utilities.

**New Methods**:
- `assertJsonPath()` - Assert JSON path value
- `assertJsonMissing()` - Assert JSON missing
- `assertJsonCount()` - Assert JSON count

**Examples**:

```php
$response->assertJsonPath('data.user.name', 'John');
$response->assertJsonMissing(['password']);
$response->assertJsonCount(10, 'data.users');
```

---

## Implementation Examples

### Example 1: Using sole() for Single Records

**Before (Laravel 11)**:

```php
$theme = Theme::where('name', 'default')->first();

if (!$theme) {
    throw new ThemeNotFoundException();
}

// Check if multiple themes exist
$count = Theme::where('name', 'default')->count();
if ($count > 1) {
    throw new MultipleThemesException();
}
```

**After (Laravel 12)**:

```php
// Throws if not found or multiple found
$theme = Theme::where('name', 'default')->sole();
```

### Example 2: Using ensure() for Type Safety

**Before (Laravel 11)**:

```php
$themes = collect($data);

foreach ($themes as $theme) {
    if (!is_string($theme)) {
        throw new InvalidArgumentException();
    }
}
```

**After (Laravel 12)**:

```php
$themes = collect($data)->ensure('string');
```

### Example 3: Using flexible() for Cache

**Before (Laravel 11)**:

```php
$value = Cache::remember('key', 3600, function () {
    return expensive_operation();
});
```

**After (Laravel 12)**:

```php
// Cache for 5 minutes, but serve stale for up to 10 minutes
// while refreshing in background
$value = Cache::flexible('key', [5, 10], function () {
    return expensive_operation();
});
```

### Example 4: Using Prompts for Interactive Commands

**Before (Laravel 11)**:

```php
$name = $this->ask('What is your name?');
$role = $this->choice('What is your role?', ['admin', 'user']);
```

**After (Laravel 12)**:

```php
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;

$name = text('What is your name?', required: true);

$role = select(
    'What is your role?',
    ['admin', 'user', 'guest'],
    default: 'user'
);
```

---

## CanvaStack Integration

### 1. Theme Manager with sole()

```php
class ThemeManager
{
    public function getTheme(string $name): ThemeInterface
    {
        return $this->repository
            ->all()
            ->sole('name', $name);
    }
}
```

### 2. Cache Manager with flexible()

```php
class CacheManager
{
    public function getThemeConfig(string $theme): array
    {
        return Cache::flexible(
            "theme:{$theme}",
            [300, 600], // 5 min cache, 10 min stale
            fn() => $this->loadThemeConfig($theme)
        );
    }
}
```

### 3. Validation with New Rules

```php
class ThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|lowercase|ascii',
            'version' => 'required|decimal:1,2',
            'colors.primary' => 'required|string',
        ];
    }
}
```

### 4. Commands with Prompts

```php
class ThemeCreateCommand extends Command
{
    public function handle(): int
    {
        $name = text(
            'Theme name',
            required: true,
            validate: fn($v) => Str::isMatch('/^[a-z-]+$/', $v)
        );
        
        $version = text('Version', default: '1.0.0');
        
        $darkMode = confirm('Support dark mode?', default: true);
        
        // Create theme...
        
        return self::SUCCESS;
    }
}
```

---

## Migration Checklist

### Collection Methods

- [x] Replace `first()` with `sole()` where appropriate
- [x] Use `ensure()` for type safety
- [x] Use `firstOrFail()` instead of manual checks

### Validation

- [x] Update validation rules to use new rules
- [x] Use `decimal` instead of `numeric` for decimals
- [x] Use `lowercase`/`uppercase` for case validation

### Database Queries

- [x] Replace `first()` with `sole()` for single records
- [x] Use `value()` for single column values
- [x] Use `implode()` for joining values

### Cache

- [x] Use `flexible()` for cache with grace period
- [x] Use `missing()` instead of `!has()`
- [x] Use `pull()` for get-and-delete

### Commands

- [x] Use Prompts API for interactive commands
- [x] Replace `ask()` with `text()`
- [x] Replace `choice()` with `select()`

---

## Performance Benefits

### flexible() Cache

- **Stale-While-Revalidate**: Serve stale content while refreshing
- **Better UX**: No waiting for cache refresh
- **Reduced Load**: Background refresh

### sole() Query

- **Single Query**: No need for count() + first()
- **Better Errors**: Clear exception messages
- **Type Safety**: Guaranteed single result

### ensure() Collection

- **Type Safety**: Runtime type checking
- **Better Errors**: Clear type mismatch messages
- **Performance**: Early validation

---

## Testing

### Unit Tests

```php
public function test_sole_returns_single_theme()
{
    $theme = Theme::factory()->create(['name' => 'default']);
    
    $result = Theme::where('name', 'default')->sole();
    
    $this->assertEquals($theme->id, $result->id);
}

public function test_sole_throws_on_multiple()
{
    Theme::factory()->count(2)->create(['name' => 'default']);
    
    $this->expectException(MultipleRecordsFoundException::class);
    
    Theme::where('name', 'default')->sole();
}

public function test_ensure_validates_types()
{
    $collection = collect(['a', 'b', 'c']);
    
    $result = $collection->ensure('string');
    
    $this->assertCount(3, $result);
}

public function test_flexible_cache_works()
{
    $value = Cache::flexible('test', [1, 2], fn() => 'value');
    
    $this->assertEquals('value', $value);
}
```

---

## Best Practices

### 1. Use sole() for Single Records

✅ **DO**:
```php
$theme = Theme::where('name', 'default')->sole();
```

❌ **DON'T**:
```php
$theme = Theme::where('name', 'default')->first();
if (!$theme) throw new Exception();
```

### 2. Use ensure() for Type Safety

✅ **DO**:
```php
$themes = collect($data)->ensure(ThemeInterface::class);
```

❌ **DON'T**:
```php
foreach ($data as $theme) {
    if (!$theme instanceof ThemeInterface) {
        throw new Exception();
    }
}
```

### 3. Use flexible() for Expensive Operations

✅ **DO**:
```php
$data = Cache::flexible('key', [5, 10], fn() => expensive());
```

❌ **DON'T**:
```php
$data = Cache::remember('key', 5, fn() => expensive());
```

---

## Resources

### Laravel Documentation

- [Laravel 12 Release Notes](https://laravel.com/docs/12.x/releases)
- [Collections](https://laravel.com/docs/12.x/collections)
- [Validation](https://laravel.com/docs/12.x/validation)
- [Cache](https://laravel.com/docs/12.x/cache)
- [Prompts](https://laravel.com/docs/12.x/prompts)

### Migration Guides

- [Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [What's New in Laravel 12](https://laravel-news.com/laravel-12)

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-03-01  
**Status**: Completed  
**Maintainer**: CanvaStack Team
