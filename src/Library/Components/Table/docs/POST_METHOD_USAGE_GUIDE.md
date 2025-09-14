# POST Method Usage Guide

## Overview

The Canvastack Datatables system now supports POST method for AJAX requests, providing better security and handling of large datasets. This implementation maintains full backward compatibility with existing GET method implementations.

## Key Features

- **Security**: Sensitive data no longer exposed in URL query strings
- **Large Data Support**: No URL length limitations
- **Backward Compatibility**: Existing GET method implementations continue to work
- **Seamless Integration**: Works with existing filter and export systems

## Usage

### Basic Implementation

```php
use Canvastack\Canvastack\Library\Components\Table\Objects;

$table = new Objects();
$table->method('POST'); // Enable POST method
$table->lists(['id', 'name', 'email']);
$table->render('users');
```

### Advanced Configuration

```php
$table = new Objects();
$table->method('POST');
$table->lists(['id', 'name', 'email', 'created_at']);
$table->labels([
    'id' => 'ID',
    'name' => 'Full Name',
    'email' => 'Email Address',
    'created_at' => 'Registration Date'
]);
$table->actions(['show', 'edit', 'delete']);
$table->render('users');
```

## Technical Implementation

### 1. AjaxController Enhancement

The `AjaxController` now handles POST datatables requests:

```php
// In AjaxController::post()
elseif (!empty($_GET['renderDataTables'])) {
    return $this->initRenderDatatables($_GET, $_POST);
}
```

### 2. DatatablesPostService

New service class handles POST request processing:

```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\DatatablesPostService;

$service = new DatatablesPostService();
$result = $service->handle($_GET, $_POST);
```

### 3. JavaScript Configuration

POST method automatically configures AJAX requests:

```javascript
ajax: {
    url: '/endpoint?renderDataTables=true',
    type: 'POST',
    headers: {'X-CSRF-TOKEN': token},
    data: function(data) {
        return {
            draw: data.draw,
            start: data.start,
            length: data.length,
            search: data.search,
            order: data.order,
            columns: data.columns,
            difta: {name: 'table_name', source: 'dynamics'},
            datatables_data: configData
        };
    }
}
```

## Migration from GET to POST

### Step 1: Update Table Configuration

```php
// Before (GET method - default)
$table = new Objects();
$table->render('users');

// After (POST method)
$table = new Objects();
$table->method('POST');
$table->render('users');
```

### Step 2: No Route Changes Required

The same routes work for both GET and POST methods. The system automatically detects the method and processes accordingly.

### Step 3: Filters and Export Continue to Work

All existing filter and export functionality remains unchanged:

```php
$table->method('POST');
$table->search(['name', 'email']); // Filters work the same
$table->export(true); // Export works the same
```

## Security Benefits

### 1. No Sensitive Data in URLs

```php
// GET method (data visible in URL)
/datatables?renderDataTables=true&difta[name]=users&search[value]=john

// POST method (data in request body)
/datatables?renderDataTables=true
// Data sent securely in POST body
```

### 2. CSRF Protection

POST requests automatically include CSRF tokens:

```javascript
headers: {'X-CSRF-TOKEN': token}
```

### 3. Request Size Limitations Removed

Large filter sets and complex queries no longer limited by URL length restrictions.

## Compatibility Matrix

| Feature | GET Method | POST Method | Notes |
|---------|------------|-------------|-------|
| Basic Tables | ✅ | ✅ | Full compatibility |
| Filters | ✅ | ✅ | Same API |
| Export | ✅ | ✅ | Same functionality |
| Actions | ✅ | ✅ | No changes required |
| Relations | ✅ | ✅ | Full support |
| Custom Models | ✅ | ✅ | Works identically |

## Troubleshooting

### Common Issues

1. **CSRF Token Errors**
   - Ensure CSRF middleware is properly configured
   - Check that meta tags include CSRF token

2. **POST Data Not Received**
   - Verify Content-Type headers
   - Check middleware configuration

3. **Backward Compatibility Issues**
   - Existing GET implementations should work unchanged
   - If issues occur, verify route configurations

### Debug Mode

Enable debug logging to troubleshoot issues:

```php
// In config/canvastack.php
'datatables' => [
    'debug' => true,
    'method' => 'POST' // Set default method
]
```

## Performance Considerations

### POST Method Benefits

- **Reduced Server Load**: No URL parsing overhead for large datasets
- **Better Caching**: POST requests can be cached more effectively
- **Network Efficiency**: Compressed request bodies

### Recommendations

1. Use POST method for tables with:
   - Large filter sets
   - Complex search criteria
   - Sensitive data
   - High-traffic applications

2. Keep GET method for:
   - Simple tables
   - Public data
   - Legacy integrations requiring URL-based parameters

## Examples

### Complete Implementation Example

```php
<?php

use Canvastack\Canvastack\Library\Components\Table\Objects;

class UserController extends Controller
{
    public function index()
    {
        $table = new Objects();
        
        // Configure POST method
        $table->method('POST');
        
        // Define columns
        $table->lists(['id', 'name', 'email', 'role', 'created_at']);
        
        // Set labels
        $table->labels([
            'id' => 'User ID',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'role' => 'User Role',
            'created_at' => 'Registration Date'
        ]);
        
        // Enable actions
        $table->actions(['show', 'edit', 'delete']);
        
        // Enable search
        $table->search(['name', 'email']);
        
        // Enable export
        $table->export(true);
        
        // Render table
        $html = $table->render('users');
        
        return view('users.index', compact('html'));
    }
}
```

This implementation provides a secure, efficient, and fully-featured datatables solution using the POST method while maintaining complete backward compatibility with existing GET method implementations.