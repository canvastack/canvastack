# Dynamic Content Translation

Translation support for dynamic content - content that comes from databases, user input, or is generated at runtime.

## 📦 Location

- **Trait**: `Canvastack\Canvastack\Support\Localization\Translatable`
- **Model**: `Canvastack\Canvastack\Models\Translation`
- **Helpers**: `packages/canvastack/canvastack/src/Support/Localization/helpers.php`
- **Migration**: `database/migrations/2024_01_01_000003_create_translations_table.php`

## 🎯 Features

- Store translations for any model attribute
- Automatic translation retrieval based on current locale
- Fallback to default locale when translation is missing
- Translation caching for performance
- Helper functions for easy translation management
- Polymorphic relationship support (works with any model)
- Bulk translation operations
- Translation versioning support

## 📖 Basic Usage

### 1. Make Model Translatable

Add the `Translatable` trait to your model and define translatable attributes:

```php
<?php

namespace App\Models;

use Canvastack\Canvastack\Models\BaseModel;
use Canvastack\Canvastack\Support\Localization\Translatable;

class Product extends BaseModel
{
    use Translatable;

    protected $fillable = ['name', 'description', 'price'];

    /**
     * Translatable attributes
     */
    protected array $translatable = [
        'name',
        'description',
    ];
}
```

### 2. Set Translations

```php
$product = Product::create([
    'name' => 'Laptop',
    'description' => 'High-performance laptop',
    'price' => 1000.00,
]);

// Set Indonesian translation
$product->setTranslation('name', 'Laptop', 'id');
$product->setTranslation('description', 'Laptop berkinerja tinggi', 'id');

// Set Spanish translation
$product->setTranslation('name', 'Portátil', 'es');
$product->setTranslation('description', 'Portátil de alto rendimiento', 'es');
```

### 3. Get Translations

```php
// Get translation for current locale
$name = $product->getTranslation('name');

// Get translation for specific locale
$nameIndonesian = $product->getTranslation('name', 'id');
$nameSpanish = $product->getTranslation('name', 'es');

// Automatic translation (uses current locale)
App::setLocale('id');
echo $product->name; // Output: "Laptop"
```

## 🔧 API Reference

### Translatable Trait Methods

#### `getTranslatableAttributes(): array`

Get list of translatable attributes.

```php
$attributes = $product->getTranslatableAttributes();
// Returns: ['name', 'description']
```

#### `isTranslatable(string $attribute): bool`

Check if an attribute is translatable.

```php
if ($product->isTranslatable('name')) {
    // Attribute is translatable
}
```

#### `setTranslation(string $attribute, string $value, ?string $locale = null): bool`

Set translation for an attribute.

```php
$product->setTranslation('name', 'Laptop', 'id');
```

#### `getTranslation(string $attribute, ?string $locale = null, bool $fallback = true): ?string`

Get translation for an attribute.

```php
// Get translation with fallback
$name = $product->getTranslation('name', 'id');

// Get translation without fallback
$name = $product->getTranslation('name', 'id', false);
```

#### `setTranslations(array $translations, ?string $locale = null): void`

Set multiple translations at once.

```php
$product->setTranslations([
    'name' => 'Laptop',
    'description' => 'Laptop berkinerja tinggi',
], 'id');
```

#### `getTranslations(string $attribute): array`

Get all translations for an attribute.

```php
$translations = $product->getTranslations('name');
// Returns: ['en' => 'Laptop', 'id' => 'Laptop', 'es' => 'Portátil']
```

#### `hasTranslation(string $attribute, ?string $locale = null): bool`

Check if translation exists.

```php
if ($product->hasTranslation('name', 'id')) {
    // Translation exists
}
```

#### `deleteTranslation(string $attribute, ?string $locale = null): bool`

Delete a translation.

```php
$product->deleteTranslation('name', 'id');
```

#### `deleteTranslations(): void`

Delete all translations for the model.

```php
$product->deleteTranslations();
```

#### `translate(string $attribute, string $locale): ?string`

Get translated attribute for a specific locale without changing current locale.

```php
App::setLocale('en');
$nameIndonesian = $product->translate('name', 'id');
// Current locale remains 'en'
```

#### `toArrayWithTranslations(): array`

Convert model to array including all translations.

```php
$array = $product->toArrayWithTranslations();
// Returns:
// [
//     'id' => 1,
//     'name' => 'Laptop',
//     'description' => 'High-performance laptop',
//     'price' => 1000.00,
//     'name_translations' => ['en' => 'Laptop', 'id' => 'Laptop', 'es' => 'Portátil'],
//     'description_translations' => [...],
// ]
```

## 📝 Helper Functions

### `translate_model($model, string $attribute, ?string $locale = null, bool $fallback = true): ?string`

Translate a model attribute.

```php
$name = translate_model($product, 'name', 'id');
```

### `set_model_translation($model, string $attribute, string $value, ?string $locale = null): bool`

Set translation for a model attribute.

```php
set_model_translation($product, 'name', 'Laptop', 'id');
```

### `has_translation($model, string $attribute, ?string $locale = null): bool`

Check if model has translation.

```php
if (has_translation($product, 'name', 'id')) {
    // Translation exists
}
```

### `get_translations($model, string $attribute): array`

Get all translations for a model attribute.

```php
$translations = get_translations($product, 'name');
```

### `translate_collection($collection, array $attributes, ?string $locale = null)`

Translate a collection of models.

```php
$products = Product::all();
$translated = translate_collection($products, ['name', 'description'], 'id');
```

### `translate_array(array $data, string $prefix, ?string $locale = null): array`

Translate an array of data using translation keys.

```php
$statuses = ['active' => 'Active', 'inactive' => 'Inactive'];
$translated = translate_array($statuses, 'statuses', 'id');
```

## 🎮 Advanced Usage

### Bulk Translation Import

```php
$translations = [
    'id' => [
        'name' => 'Laptop',
        'description' => 'Laptop berkinerja tinggi',
    ],
    'es' => [
        'name' => 'Portátil',
        'description' => 'Portátil de alto rendimiento',
    ],
];

foreach ($translations as $locale => $attributes) {
    $product->setTranslations($attributes, $locale);
}
```

### Translation Fallback Chain

```php
// Set default locale translation
$product->setTranslation('name', 'Laptop', 'en');

// Try to get French translation (not available)
App::setLocale('fr');
$name = $product->name; // Falls back to 'en': "Laptop"
```

### Conditional Translation

```php
if ($product->hasTranslation('name', 'id')) {
    $name = $product->getTranslation('name', 'id');
} else {
    // Use default or prompt for translation
    $name = $product->name;
}
```

### Translation in Blade Templates

```blade
{{-- Automatic translation based on current locale --}}
<h1>{{ $product->name }}</h1>

{{-- Specific locale --}}
<h1>{{ $product->translate('name', 'id') }}</h1>

{{-- With helper --}}
<h1>{{ translate_model($product, 'name', 'id') }}</h1>

{{-- Check if translation exists --}}
@if(has_translation($product, 'name', 'id'))
    <h1>{{ $product->translate('name', 'id') }}</h1>
@else
    <h1>{{ $product->name }}</h1>
@endif
```

### Translation in API Responses

```php
public function show(Product $product)
{
    return response()->json([
        'product' => $product->toArrayWithTranslations(),
    ]);
}

// Response:
// {
//     "product": {
//         "id": 1,
//         "name": "Laptop",
//         "description": "High-performance laptop",
//         "price": 1000.00,
//         "name_translations": {
//             "en": "Laptop",
//             "id": "Laptop",
//             "es": "Portátil"
//         },
//         "description_translations": {
//             "en": "High-performance laptop",
//             "id": "Laptop berkinerja tinggi",
//             "es": "Portátil de alto rendimiento"
//         }
//     }
// }
```

## 🔍 Integration with Components

### FormBuilder Integration

```php
public function edit(Product $product, FormBuilder $form): View
{
    $form->setContext('admin');
    $form->setModel($product);
    
    // Add translation tabs
    foreach (available_locales() as $locale => $info) {
        $form->openTab($info['name']);
        
        $form->text('name', __('ui.name'))
            ->value($product->translate('name', $locale))
            ->required();
        
        $form->textarea('description', __('ui.description'))
            ->value($product->translate('description', $locale));
        
        $form->closeTab();
    }
    
    return view('products.edit', compact('form', 'product'));
}
```

### TableBuilder Integration

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    // Translations are automatically applied based on current locale
    $table->setFields([
        'name:' . __('ui.name'),
        'description:' . __('ui.description'),
        'price:' . __('ui.price'),
    ]);
    
    $table->format();
    
    return view('products.index', compact('table'));
}
```

## 💡 Performance Considerations

### Caching

Translations are automatically cached for 1 hour. Cache is cleared when:
- Translation is updated
- Translation is deleted
- Model is saved
- Model is deleted

### Eager Loading

When loading multiple models with translations:

```php
// Load products with translations
$products = Product::with('translations')->get();

// Access translations (uses eager loaded data)
foreach ($products as $product) {
    echo $product->name; // No additional query
}
```

### Batch Operations

For bulk translation operations, use database transactions:

```php
DB::transaction(function () use ($product, $translations) {
    foreach ($translations as $locale => $attributes) {
        $product->setTranslations($attributes, $locale);
    }
});
```

## 🧪 Testing

### Unit Test Example

```php
public function test_product_can_be_translated()
{
    $product = Product::create([
        'name' => 'Laptop',
        'description' => 'High-performance laptop',
        'price' => 1000.00,
    ]);
    
    $product->setTranslation('name', 'Laptop', 'id');
    
    $this->assertEquals('Laptop', $product->getTranslation('name', 'id'));
    $this->assertTrue($product->hasTranslation('name', 'id'));
}
```

### Feature Test Example

```php
public function test_product_displays_translated_name()
{
    $product = Product::create([
        'name' => 'Laptop',
        'description' => 'High-performance laptop',
        'price' => 1000.00,
    ]);
    
    $product->setTranslation('name', 'Laptop', 'id');
    
    App::setLocale('id');
    
    $response = $this->get(route('products.show', $product));
    
    $response->assertSee('Laptop');
}
```

## 🎭 Common Patterns

### Pattern 1: Multi-Language Form

```php
public function update(Request $request, Product $product)
{
    // Update base attributes
    $product->update($request->only(['price']));
    
    // Update translations for each locale
    foreach (available_locales() as $locale => $info) {
        if ($request->has("name_{$locale}")) {
            $product->setTranslation('name', $request->input("name_{$locale}"), $locale);
        }
        
        if ($request->has("description_{$locale}")) {
            $product->setTranslation('description', $request->input("description_{$locale}"), $locale);
        }
    }
    
    return redirect()->route('products.index')
        ->with('success', __('ui.product_updated'));
}
```

### Pattern 2: API with Locale Parameter

```php
public function show(Request $request, Product $product)
{
    $locale = $request->input('locale', App::getLocale());
    
    return response()->json([
        'id' => $product->id,
        'name' => $product->translate('name', $locale),
        'description' => $product->translate('description', $locale),
        'price' => $product->price,
    ]);
}
```

### Pattern 3: Translation Export/Import

```php
// Export translations
public function exportTranslations(Product $product)
{
    $translations = [];
    
    foreach ($product->getTranslatableAttributes() as $attribute) {
        $translations[$attribute] = $product->getTranslations($attribute);
    }
    
    return response()->json($translations);
}

// Import translations
public function importTranslations(Request $request, Product $product)
{
    $translations = $request->input('translations');
    
    foreach ($translations as $attribute => $locales) {
        foreach ($locales as $locale => $value) {
            $product->setTranslation($attribute, $value, $locale);
        }
    }
    
    return response()->json(['success' => true]);
}
```

## 🔗 Related Documentation

- [Internationalization System](i18n.md) - Complete i18n system overview
- [LocaleManager](../api/locale-manager.md) - Locale management API
- [FormBuilder](../components/form-builder.md) - Form component integration
- [TableBuilder](../components/table-builder.md) - Table component integration

## 📚 Resources

- [Laravel Localization](https://laravel.com/docs/localization)
- [Polymorphic Relationships](https://laravel.com/docs/eloquent-relationships#polymorphic-relationships)
- [Database Caching](https://laravel.com/docs/cache)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published

