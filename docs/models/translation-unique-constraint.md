# Translation Model Unique Constraint

## Overview

The Translation model enforces a unique constraint to prevent duplicate translations for the same model, attribute, and locale combination. This ensures data integrity and prevents conflicts in the translation system.

**Constraint Name**: `unique_translation`  
**Columns**: `translatable_type`, `translatable_id`, `attribute`, `locale`

---

## Database Schema

### Migration

The unique constraint is defined in the migration:

```php
// database/migrations/2024_01_01_000003_create_translations_table.php

Schema::create('translations', function (Blueprint $table) {
    $table->id();
    
    // Polymorphic relationship to any model
    $table->string('translatable_type', 255);
    $table->unsignedBigInteger('translatable_id');
    
    // Translation details
    $table->string('attribute', 100);
    $table->string('locale', 10);
    $table->text('value');
    
    $table->timestamps();
    
    // Unique constraint: one translation per model + attribute + locale
    $table->unique(
        ['translatable_type', 'translatable_id', 'attribute', 'locale'],
        'unique_translation'
    );
});
```

### Constraint Behavior

The unique constraint ensures:

1. ✅ **One translation per combination**: Each model instance can have only ONE translation per attribute per locale
2. ✅ **Multiple locales allowed**: Same attribute can have different translations in different locales
3. ✅ **Multiple attributes allowed**: Same locale can have translations for different attributes
4. ✅ **Database-level enforcement**: Constraint is enforced at the database level, not just application level

### Examples

#### ✅ Allowed: Same attribute, different locales

```php
// English translation
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'name',
    'locale' => 'en',
    'value' => 'Product Name',
]);

// Indonesian translation (ALLOWED - different locale)
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'name',
    'locale' => 'id',
    'value' => 'Nama Produk',
]);
```

#### ✅ Allowed: Same locale, different attributes

```php
// Name translation
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'name',
    'locale' => 'id',
    'value' => 'Nama Produk',
]);

// Description translation (ALLOWED - different attribute)
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'description',
    'locale' => 'id',
    'value' => 'Deskripsi Produk',
]);
```

#### ❌ Not Allowed: Duplicate combination

```php
// First translation
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'name',
    'locale' => 'id',
    'value' => 'Nama Produk',
]);

// Duplicate translation (NOT ALLOWED - same combination)
Translation::create([
    'translatable_type' => Product::class,
    'translatable_id' => 1,
    'attribute' => 'name',
    'locale' => 'id',
    'value' => 'Nama Produk Lain', // Different value, but same key
]);
// Throws: Illuminate\Database\QueryException
```

---

## Exception Handling

### DuplicateTranslationException

The Translation model provides a custom exception for better error messages:

```php
namespace Canvastack\Canvastack\Exceptions;

class DuplicateTranslationException extends Exception
{
    public static function forTranslation(
        string $translatableType,
        int $translatableId,
        string $attribute,
        string $locale
    ): static;
}
```

**Exception Message Format**:
```
A translation for {ModelName}#{id} attribute '{attribute}' in locale '{locale}' already exists. 
Use updateOrCreate() or delete the existing translation first.
```

**Example**:
```
A translation for Product#1 attribute 'name' in locale 'id' already exists. 
Use updateOrCreate() or delete the existing translation first.
```

---

## API Methods

### Method 1: `create()` - Raw Creation

**Use Case**: When you want to handle database exceptions directly

```php
try {
    Translation::create([
        'translatable_type' => Product::class,
        'translatable_id' => $product->id,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);
} catch (\Illuminate\Database\QueryException $e) {
    // Handle database exception
    if ($e->getCode() === '23000') {
        // Unique constraint violation
        Log::error('Duplicate translation', ['error' => $e->getMessage()]);
    }
}
```

**Exception**: `Illuminate\Database\QueryException`  
**Error Code**: `23000` (Integrity constraint violation)

### Method 2: `createTranslation()` - With Descriptive Exception

**Use Case**: When you want descriptive error messages (RECOMMENDED for new translations)

```php
use Canvastack\Canvastack\Exceptions\DuplicateTranslationException;

try {
    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => $product->id,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);
} catch (DuplicateTranslationException $e) {
    // Handle duplicate translation with descriptive message
    Log::warning($e->getMessage());
    // "A translation for Product#1 attribute 'name' in locale 'id' already exists..."
}
```

**Exception**: `DuplicateTranslationException` (descriptive)  
**Benefits**:
- ✅ Clear, descriptive error message
- ✅ Includes model name, ID, attribute, and locale
- ✅ Suggests solution (use updateOrCreate)

### Method 3: `createOrUpdateTranslation()` - Upsert

**Use Case**: When you want to create or update without worrying about duplicates (RECOMMENDED)

```php
// Creates new or updates existing - no exception
$translation = Translation::createOrUpdateTranslation([
    'translatable_type' => Product::class,
    'translatable_id' => $product->id,
    'attribute' => 'name',
    'locale' => 'id',
], [
    'value' => 'Produk Test',
]);

// Always succeeds - creates new or updates existing
```

**Exception**: None  
**Benefits**:
- ✅ No exception handling needed
- ✅ Idempotent operation
- ✅ Safe for concurrent requests
- ✅ Recommended for most use cases

---

## Implementation Details

### Exception Handling in Model

The `createTranslation()` method wraps `create()` with proper exception handling:

```php
public static function createTranslation(array $attributes): static
{
    try {
        return static::create($attributes);
    } catch (UniqueConstraintViolationException $e) {
        // Laravel 11+ throws UniqueConstraintViolationException
        throw DuplicateTranslationException::forTranslation(
            $attributes['translatable_type'],
            $attributes['translatable_id'],
            $attributes['attribute'],
            $attributes['locale']
        );
    } catch (QueryException $e) {
        // Check if this is a unique constraint violation (for older Laravel versions)
        if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'unique_translation')) {
            throw DuplicateTranslationException::forTranslation(
                $attributes['translatable_type'],
                $attributes['translatable_id'],
                $attributes['attribute'],
                $attributes['locale']
            );
        }
        
        // Re-throw if it's a different error
        throw $e;
    }
}
```

**Features**:
- ✅ Catches `UniqueConstraintViolationException` (Laravel 11+)
- ✅ Catches `QueryException` with error code 23000 (older Laravel versions)
- ✅ Checks for `unique_translation` constraint name
- ✅ Throws descriptive `DuplicateTranslationException`
- ✅ Re-throws other exceptions unchanged

---

## Best Practices

### 1. Use `createOrUpdateTranslation()` for Most Cases

```php
// ✅ RECOMMENDED: Safe, idempotent, no exception handling needed
$translation = Translation::createOrUpdateTranslation([
    'translatable_type' => Product::class,
    'translatable_id' => $product->id,
    'attribute' => 'name',
    'locale' => 'id',
], [
    'value' => 'Produk Test',
]);
```

### 2. Use `createTranslation()` When You Need to Detect Duplicates

```php
// ✅ GOOD: When you need to know if translation already exists
try {
    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => $product->id,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);
    
    Log::info('New translation created');
} catch (DuplicateTranslationException $e) {
    Log::warning('Translation already exists, skipping');
}
```

### 3. Avoid Raw `create()` Unless Necessary

```php
// ❌ NOT RECOMMENDED: Generic exception, less descriptive
try {
    Translation::create([...]);
} catch (QueryException $e) {
    // Generic error handling
}

// ✅ BETTER: Use createTranslation() for descriptive exceptions
try {
    Translation::createTranslation([...]);
} catch (DuplicateTranslationException $e) {
    // Specific error handling
}
```

### 4. Batch Operations

For batch operations, use `createOrUpdateTranslation()` to avoid exceptions:

```php
$translations = [
    ['attribute' => 'name', 'value' => 'Nama Produk'],
    ['attribute' => 'description', 'value' => 'Deskripsi Produk'],
    ['attribute' => 'features', 'value' => 'Fitur Produk'],
];

foreach ($translations as $data) {
    Translation::createOrUpdateTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => $product->id,
        'attribute' => $data['attribute'],
        'locale' => 'id',
    ], [
        'value' => $data['value'],
    ]);
}
```

---

## Testing

### Test 1: Verify Constraint Enforcement

```php
public function test_unique_constraint_is_enforced()
{
    Translation::create([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    Translation::create([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test 2', // Different value, same key
    ]);
}
```

### Test 2: Verify Exception Message

```php
public function test_descriptive_exception_is_thrown()
{
    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);

    $this->expectException(DuplicateTranslationException::class);
    $this->expectExceptionMessage("A translation for Product#1 attribute 'name' in locale 'id' already exists");

    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test 2',
    ]);
}
```

### Test 3: Verify Allowed Combinations

```php
public function test_same_attribute_different_locales_allowed()
{
    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);

    // Should not throw exception
    $translation = Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'es', // Different locale
        'value' => 'Producto de Prueba',
    ]);

    $this->assertInstanceOf(Translation::class, $translation);
}

public function test_same_locale_different_attributes_allowed()
{
    Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
        'value' => 'Produk Test',
    ]);

    // Should not throw exception
    $translation = Translation::createTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'description', // Different attribute
        'locale' => 'id',
        'value' => 'Deskripsi Test',
    ]);

    $this->assertInstanceOf(Translation::class, $translation);
}
```

### Test 4: Verify Upsert Behavior

```php
public function test_create_or_update_works_correctly()
{
    // Create new translation
    $translation = Translation::createOrUpdateTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
    ], [
        'value' => 'Produk Test',
    ]);

    $this->assertEquals('Produk Test', $translation->value);

    // Update existing translation
    $updated = Translation::createOrUpdateTranslation([
        'translatable_type' => Product::class,
        'translatable_id' => 1,
        'attribute' => 'name',
        'locale' => 'id',
    ], [
        'value' => 'Produk Test Updated',
    ]);

    $this->assertEquals($translation->id, $updated->id);
    $this->assertEquals('Produk Test Updated', $updated->value);

    // Verify only one translation exists
    $count = Translation::where('translatable_type', Product::class)
        ->where('translatable_id', 1)
        ->where('attribute', 'name')
        ->where('locale', 'id')
        ->count();

    $this->assertEquals(1, $count);
}
```

---

## Troubleshooting

### Issue 1: Constraint Violation Not Detected

**Symptom**: Duplicate translations are created without error

**Cause**: Migration not run or constraint not created

**Solution**:
```bash
# Check if migration has been run
php artisan migrate:status

# Run migrations
php artisan migrate

# Verify constraint exists
php artisan tinker
>>> DB::select("SHOW INDEX FROM translations WHERE Key_name = 'unique_translation'");
```

### Issue 2: Generic QueryException Instead of DuplicateTranslationException

**Symptom**: `QueryException` thrown instead of `DuplicateTranslationException`

**Cause**: Using `create()` instead of `createTranslation()`

**Solution**: Use `createTranslation()` method:
```php
// ❌ Wrong
Translation::create([...]);

// ✅ Correct
Translation::createTranslation([...]);
```

### Issue 3: Constraint Violation in Production

**Symptom**: Unique constraint violation in production logs

**Cause**: Concurrent requests creating same translation

**Solution**: Use `createOrUpdateTranslation()` for idempotent operations:
```php
// ✅ Safe for concurrent requests
Translation::createOrUpdateTranslation([
    'translatable_type' => Product::class,
    'translatable_id' => $product->id,
    'attribute' => 'name',
    'locale' => 'id',
], [
    'value' => 'Produk Test',
]);
```

---

## Related Documentation

- [Translation Model](./translation.md)
- [Translatable Trait](../traits/translatable.md)
- [i18n System](../i18n/overview.md)
- [Database Migrations](../database/migrations.md)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
