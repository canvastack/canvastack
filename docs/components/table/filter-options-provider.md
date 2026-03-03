# FilterOptionsProvider

Provides filter options from database with parent filter support for cascading filters. Includes query optimization and caching.

## 📦 Location

- **Class**: `Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider`
- **File**: `packages/canvastack/canvastack/src/Components/Table/Filter/FilterOptionsProvider.php`
- **Tests**: `packages/canvastack/canvastack/tests/Unit/Components/Table/Filter/FilterOptionsProviderTest.php`

## 🎯 Features

- Load filter options from database tables
- Support for parent filters (cascading filters)
- Query optimization with distinct and ordering
- Built-in caching with configurable TTL
- Custom query support for complex scenarios
- Array conversion utilities
- Null value filtering

## 📖 Basic Usage

### Simple Filter Options

```php
use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;

$provider = new FilterOptionsProvider();

// Get all distinct regions
$options = $provider->getOptions('users', 'region');

// Returns:
// [
//     ['value' => 'East', 'label' => 'East'],
//     ['value' => 'West', 'label' => 'West'],
// ]
```

### Cascading Filters (Parent Filters)

```php
// Get cities in a specific region
$options = $provider->getOptions('users', 'city', [
    'region' => 'West'
]);

// Get districts in a specific city and region
$options = $provider->getOptions('users', 'district', [
    'region' => 'West',
    'city' => 'Jakarta'
]);
```

## 🔧 Configuration

### Cache TTL

```php
// Set cache TTL to 10 minutes (600 seconds)
$provider->setCacheTtl(600);

// Get options (will be cached for 10 minutes)
$options = $provider->getOptions('users', 'region');
```

### Enable/Disable Caching

```php
// Disable caching
$provider->setCacheEnabled(false);

// Get options (will not be cached)
$options = $provider->getOptions('users', 'region');

// Re-enable caching
$provider->setCacheEnabled(true);
```

## 📝 Methods

### getOptions()

Get filter options from database with optional parent filters.

```php
public function getOptions(
    string $table, 
    string $column, 
    array $parentFilters = []
): array
```

**Parameters:**
- `$table` - Database table name
- `$column` - Column to get options from
- `$parentFilters` - Array of parent filter values for cascading

**Returns:** Array of options in format `[['value' => ..., 'label' => ...], ...]`

**Example:**
```php
// Simple usage
$regions = $provider->getOptions('users', 'region');

// With parent filters
$cities = $provider->getOptions('users', 'city', ['region' => 'West']);

// Multiple parent filters
$districts = $provider->getOptions('users', 'district', [
    'region' => 'West',
    'city' => 'Jakarta'
]);
```

### getOptionsWithQuery()

Get options using a custom query callback for complex scenarios.

```php
public function getOptionsWithQuery(
    callable $queryCallback,
    string $valueColumn,
    ?string $labelColumn = null
): array
```

**Parameters:**
- `$queryCallback` - Callback that receives a query builder instance
- `$valueColumn` - Column name for option value
- `$labelColumn` - Column name for option label (defaults to value column)

**Example:**
```php
$options = $provider->getOptionsWithQuery(
    function ($query) {
        $query->from('users')
            ->select('id', 'name', 'email')
            ->where('status', 'active')
            ->where('role', 'admin');
    },
    'id',
    'name'
);

// Returns:
// [
//     ['value' => 1, 'label' => 'John Doe'],
//     ['value' => 2, 'label' => 'Jane Smith'],
// ]
```

### getOptionsFromArray()

Convert a simple array to options format.

```php
public function getOptionsFromArray(array $values): array
```

**Example:**
```php
$values = ['Option 1', 'Option 2', 'Option 3'];
$options = $provider->getOptionsFromArray($values);

// Returns:
// [
//     ['value' => 'Option 1', 'label' => 'Option 1'],
//     ['value' => 'Option 2', 'label' => 'Option 2'],
//     ['value' => 'Option 3', 'label' => 'Option 3'],
// ]

// Already formatted arrays are preserved
$formatted = [
    ['value' => '1', 'label' => 'One'],
    ['value' => '2', 'label' => 'Two'],
];
$options = $provider->getOptionsFromArray($formatted);
// Returns the same array
```

### getOptionsFromKeyValue()

Convert a key-value array to options format.

```php
public function getOptionsFromKeyValue(array $keyValues): array
```

**Example:**
```php
$statuses = [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
];

$options = $provider->getOptionsFromKeyValue($statuses);

// Returns:
// [
//     ['value' => 'active', 'label' => 'Active'],
//     ['value' => 'inactive', 'label' => 'Inactive'],
//     ['value' => 'pending', 'label' => 'Pending'],
// ]
```

### clearCache()

Clear cached options for a specific table and column.

```php
public function clearCache(string $table, string $column): void
```

**Example:**
```php
// Clear cache for region options
$provider->clearCache('users', 'region');

// Next call will query database again
$options = $provider->getOptions('users', 'region');
```

### clearAllCache()

Clear all cached filter options.

```php
public function clearAllCache(): void
```

**Example:**
```php
// Clear all filter option caches
$provider->clearAllCache();
```

## 🔍 Implementation Details

### Query Optimization

The provider automatically:
- Uses `DISTINCT` to get unique values
- Filters out `NULL` values with `whereNotNull()`
- Orders results alphabetically
- Applies parent filters efficiently

### Caching Strategy

**Cache Key Format:**
```
filter_options:{table}:{column}:{parent_filters_hash}
```

**Example Cache Keys:**
```
filter_options:users:region:d41d8cd98f00b204e9800998ecf8427e
filter_options:users:city:5d41402abc4b2a76b9719d911017c592
```

**Cache Behavior:**
- Default TTL: 300 seconds (5 minutes)
- Separate cache for each combination of table, column, and parent filters
- Cache can be disabled for real-time data

### Parent Filter Handling

**Empty Values:**
- Empty strings (`''`) are ignored
- `null` values are ignored
- Only non-empty values are applied as filters

**Multiple Parent Filters:**
```php
// All parent filters are applied with AND logic
$options = $provider->getOptions('users', 'district', [
    'region' => 'West',      // WHERE region = 'West'
    'city' => 'Jakarta'      // AND city = 'Jakarta'
]);
```

## 🎮 Integration with FilterManager

The FilterOptionsProvider is typically used by FilterManager for cascading filters:

```php
use Canvastack\Canvastack\Components\Table\Filter\FilterManager;
use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;

$manager = new FilterManager();
$provider = new FilterOptionsProvider();

// Add cascading filters
$manager->addFilter('region', 'selectbox', true);
$manager->addFilter('city', 'selectbox', true);
$manager->addFilter('district', 'selectbox', false);

// Load options for first filter
$regionOptions = $provider->getOptions('users', 'region');

// When user selects a region, load cities for that region
$cityOptions = $provider->getOptions('users', 'city', [
    'region' => $selectedRegion
]);

// When user selects a city, load districts
$districtOptions = $provider->getOptions('users', 'district', [
    'region' => $selectedRegion,
    'city' => $selectedCity
]);
```

## 💡 Tips & Best Practices

### 1. Use Caching for Static Data

```php
// For data that rarely changes, use longer cache TTL
$provider->setCacheTtl(3600); // 1 hour
$options = $provider->getOptions('countries', 'name');
```

### 2. Disable Caching for Real-Time Data

```php
// For data that changes frequently, disable caching
$provider->setCacheEnabled(false);
$options = $provider->getOptions('live_stats', 'status');
```

### 3. Use Custom Queries for Complex Scenarios

```php
// When you need joins, aggregations, or complex conditions
$options = $provider->getOptionsWithQuery(
    function ($query) {
        $query->from('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('users.id', DB::raw('CONCAT(users.name, " - ", departments.name) as label'))
            ->where('users.active', true)
            ->orderBy('users.name');
    },
    'id',
    'label'
);
```

### 4. Clear Cache After Data Changes

```php
// After updating data, clear the cache
DB::table('users')->where('id', $id)->update(['region' => 'East']);

// Clear affected caches
$provider->clearCache('users', 'region');
$provider->clearCache('users', 'city');
```

### 5. Handle Empty Results Gracefully

```php
$options = $provider->getOptions('users', 'city', ['region' => 'NonExistent']);

if (empty($options)) {
    // Show message or provide default options
    $options = [['value' => '', 'label' => 'No cities available']];
}
```

## 🎭 Common Patterns

### Pattern 1: Three-Level Cascading Filter

```php
// Region → City → District
$provider = new FilterOptionsProvider();

// Level 1: Regions (no parent)
$regions = $provider->getOptions('locations', 'region');

// Level 2: Cities (filtered by region)
$cities = $provider->getOptions('locations', 'city', [
    'region' => $selectedRegion
]);

// Level 3: Districts (filtered by region and city)
$districts = $provider->getOptions('locations', 'district', [
    'region' => $selectedRegion,
    'city' => $selectedCity
]);
```

### Pattern 2: Status-Based Options

```php
// Get only active users
$options = $provider->getOptionsWithQuery(
    function ($query) {
        $query->from('users')
            ->select('id', 'name')
            ->where('status', 'active')
            ->where('deleted_at', null);
    },
    'id',
    'name'
);
```

### Pattern 3: Grouped Options

```php
// Get options grouped by category
$options = $provider->getOptionsWithQuery(
    function ($query) {
        $query->from('products')
            ->select('id', DB::raw('CONCAT(category, " - ", name) as label'))
            ->orderBy('category')
            ->orderBy('name');
    },
    'id',
    'label'
);
```

## 🔗 Related Components

- [FilterManager](./filter-manager.md) - Manages filters and uses FilterOptionsProvider
- [Filter](./filter.md) - Individual filter configuration
- [TableBuilder](../table-builder.md) - Main table component that uses filters

## 🧪 Testing

### Unit Tests

```php
public function test_get_options_returns_distinct_values()
{
    $provider = new FilterOptionsProvider();
    
    $options = $provider->getOptions('users', 'region');
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('value', $options[0]);
    $this->assertArrayHasKey('label', $options[0]);
}

public function test_get_options_filters_by_parent_filters()
{
    $provider = new FilterOptionsProvider();
    
    $options = $provider->getOptions('users', 'city', [
        'region' => 'West'
    ]);
    
    $this->assertGreaterThan(0, count($options));
}

public function test_get_options_caches_results()
{
    $provider = new FilterOptionsProvider();
    
    $options1 = $provider->getOptions('users', 'region');
    $options2 = $provider->getOptions('users', 'region');
    
    $this->assertEquals($options1, $options2);
}
```

## 📚 Resources

### Documentation
- [Filter System Overview](./filter-system-overview.md)
- [Cascading Filters Guide](./cascading-filters.md)
- [Caching Strategy](./caching-strategy.md)

### Examples
- [Basic Filter Options](../../examples/filter-options-basic.md)
- [Cascading Filters](../../examples/cascading-filters.md)
- [Custom Query Options](../../examples/filter-options-custom.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete
