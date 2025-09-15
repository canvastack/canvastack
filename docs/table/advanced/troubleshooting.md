# Troubleshooting

This guide helps you diagnose and resolve common issues when working with CanvaStack Table. Each issue includes symptoms, causes, and step-by-step solutions.

## Table of Contents

- [Installation Issues](#installation-issues)
- [Configuration Problems](#configuration-problems)
- [Table Display Issues](#table-display-issues)
- [AJAX and Server-Side Issues](#ajax-and-server-side-issues)
- [Performance Problems](#performance-problems)
- [Security Issues](#security-issues)
- [Filtering and Search Issues](#filtering-and-search-issues)
- [Action Button Problems](#action-button-problems)
- [Database and Query Issues](#database-and-query-issues)
- [JavaScript Errors](#javascript-errors)

## Installation Issues

### Issue: Composer Installation Fails

**Symptoms:**
- `composer require canvastack/canvastack` fails
- Package not found errors
- Version conflicts

**Solutions:**

1. **Check PHP Version:**
```bash
php -v
# Ensure PHP 8.0 or higher
```

2. **Update Composer:**
```bash
composer self-update
composer clear-cache
```

3. **Check Laravel Version:**
```bash
php artisan --version
# Ensure Laravel 9.0 or higher
```

4. **Install with Specific Version:**
```bash
composer require canvastack/canvastack:^2.0
```

### Issue: Asset Publishing Fails

**Symptoms:**
- Assets not found in public directory
- CSS/JS files missing
- 404 errors for CanvaStack assets

**Solutions:**

1. **Force Asset Publishing:**
```bash
php artisan vendor:publish --tag=canvastack-assets --force
```

2. **Check Directory Permissions:**
```bash
chmod -R 755 public/vendor/canvastack
```

3. **Clear Cache:**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Issue: Migration Errors

**Symptoms:**
- Migration fails to run
- Database table creation errors
- Foreign key constraint errors

**Solutions:**

1. **Check Database Connection:**
```bash
php artisan migrate:status
```

2. **Run Migrations Step by Step:**
```bash
php artisan migrate --path=/database/migrations/canvastack
```

3. **Check Database Permissions:**
```sql
SHOW GRANTS FOR 'your_user'@'localhost';
```

## Configuration Problems

### Issue: Configuration Not Loading

**Symptoms:**
- Default settings not applied
- Custom configuration ignored
- Environment variables not working

**Solutions:**

1. **Publish Configuration:**
```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

2. **Clear Configuration Cache:**
```bash
php artisan config:clear
php artisan config:cache
```

3. **Check Environment Variables:**
```bash
# In .env file
CANVASTACK_SECURITY_MODE=hardened
CANVASTACK_DEFAULT_METHOD=POST
```

4. **Verify Configuration Loading:**
```php
// In controller or tinker
dd(config('canvastack'));
dd(config('canvastack-security'));
```

### Issue: Security Mode Not Working

**Symptoms:**
- Security features not enabled
- Validation not working
- SQL injection protection disabled

**Solutions:**

1. **Check Security Configuration:**
```php
// config/canvastack-security.php
'mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),
```

2. **Verify Environment Variable:**
```bash
# .env
CANVASTACK_SECURITY_MODE=hardened
```

3. **Debug Security Settings:**
```php
dd(config('canvastack-security.mode'));
dd(config('canvastack-security.core.input_validation.enabled'));
```

## Table Display Issues

### Issue: Table Not Displaying

**Symptoms:**
- Empty page or blank table area
- No HTML table generated
- Missing table content

**Solutions:**

1. **Check Controller Setup:**
```php
public function index()
{
    $this->setPage(); // Required!
    
    $this->table->lists('users', ['name', 'email']);
    
    return $this->render(); // Required!
}
```

2. **Verify Model Configuration:**
```php
public function __construct()
{
    parent::__construct(User::class, 'users'); // Required!
}
```

3. **Check View Template:**
```blade
{{-- Ensure this is in your view --}}
{!! $table !!}
```

4. **Debug Table Generation:**
```php
// In controller
$tableHtml = $this->table->lists('users', ['name', 'email']);
dd($tableHtml); // Should contain HTML
```

### Issue: Columns Not Showing Correctly

**Symptoms:**
- Missing columns
- Wrong column labels
- Data not displaying

**Solutions:**

1. **Check Field Names:**
```php
// Ensure field names match database columns
$this->table->lists('users', [
    'name',        // Must exist in users table
    'email',       // Must exist in users table
    'created_at'   // Must exist in users table
]);
```

2. **Verify Database Schema:**
```bash
php artisan tinker
Schema::getColumnListing('users');
```

3. **Check for Hidden Columns:**
```php
// Remove hidden columns configuration
$this->table->setHiddenColumns([]); // Clear hidden columns
```

4. **Debug Column Configuration:**
```php
// Check what columns are being processed
dd($this->table->getColumns());
```

### Issue: Styling Problems

**Symptoms:**
- Table looks unstyled
- CSS not loading
- Layout issues

**Solutions:**

1. **Include Required CSS:**
```blade
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack-table.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables/datatables.min.css') }}">
@endpush
```

2. **Check Asset Paths:**
```php
// Verify assets exist
file_exists(public_path('vendor/canvastack/css/canvastack-table.css'));
```

3. **Clear Browser Cache:**
- Hard refresh (Ctrl+F5)
- Clear browser cache
- Check Network tab in DevTools

## AJAX and Server-Side Issues

### Issue: AJAX Requests Failing

**Symptoms:**
- Table shows "Loading..." indefinitely
- 500 or 404 errors in Network tab
- No data loading

**Solutions:**

1. **Check AJAX URL:**
```javascript
// In browser console
console.log('AJAX URL:', $('#table-id').DataTable().ajax.url());
```

2. **Verify Routes:**
```bash
php artisan route:list | grep canvastack
```

3. **Check CSRF Token:**
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

4. **Debug AJAX Response:**
```php
// In Datatables.php or controller
Log::info('AJAX Request Data:', request()->all());
```

### Issue: Server-Side Processing Not Working

**Symptoms:**
- POST method not working
- Data not filtering/sorting server-side
- Performance issues with large datasets

**Solutions:**

1. **Enable POST Method:**
```php
$this->table->method('POST'); // Before lists() call
```

2. **Check Route Configuration:**
```php
// routes/web.php
Route::post('canvastack/ajax/post', [Controller::class, 'ajaxPost']);
```

3. **Verify Server-Side Configuration:**
```php
// Check if server-side is enabled
dd($this->table->isServerSide());
```

4. **Debug POST Data:**
```php
// In AJAX handler
Log::info('POST Data:', request()->all());
```

### Issue: CSRF Token Mismatch

**Symptoms:**
- 419 errors on AJAX requests
- "CSRF token mismatch" errors
- POST requests failing

**Solutions:**

1. **Include CSRF Meta Tag:**
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

2. **Configure AJAX Headers:**
```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

3. **Check Token in Requests:**
```javascript
// Verify token is being sent
console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
```

## Performance Problems

### Issue: Slow Table Loading

**Symptoms:**
- Long loading times
- Browser freezing
- Timeout errors

**Solutions:**

1. **Enable Server-Side Processing:**
```php
$this->table->method('POST'); // For large datasets
```

2. **Optimize Database Queries:**
```php
// Add indexes to filtered/sorted columns
Schema::table('users', function (Blueprint $table) {
    $table->index('created_at');
    $table->index(['department_id', 'active']);
});
```

3. **Limit Data Selection:**
```php
// Only select needed columns
$this->table->lists('users', ['name', 'email']); // Not all columns
```

4. **Use Query Optimization:**
```php
// Use raw SQL for complex queries
$sql = "SELECT id, name, email FROM users WHERE active = 1";
$this->table->query($sql);
```

### Issue: Memory Limit Exceeded

**Symptoms:**
- "Fatal error: Allowed memory size exhausted"
- Server crashes
- Incomplete data loading

**Solutions:**

1. **Increase Memory Limit:**
```php
// In controller or config
ini_set('memory_limit', '512M');
```

2. **Use Chunking:**
```php
// Process data in chunks
$this->table->setChunkSize(1000);
```

3. **Enable Server-Side Processing:**
```php
$this->table->method('POST'); // Reduces memory usage
```

## Security Issues

### Issue: SQL Injection Warnings

**Symptoms:**
- Security exception errors
- "SQL injection attempt detected" messages
- Queries being blocked

**Solutions:**

1. **Use Parameter Binding:**
```php
// Wrong
$sql = "SELECT * FROM users WHERE id = " . $id;

// Correct
$sql = "SELECT * FROM users WHERE id = ?";
$this->table->query($sql, [$id]);
```

2. **Whitelist Columns:**
```php
// Add custom columns to whitelist
config(['canvastack-security.monitoring.input_validator.whitelist_columns' => [
    'custom_field_1',
    'custom_field_2'
]]);
```

3. **Check Security Mode:**
```php
// Temporarily reduce security for debugging
config(['canvastack-security.mode' => 'basic']);
```

### Issue: XSS Protection Blocking Content

**Symptoms:**
- HTML content not displaying
- Content being stripped
- Formatting lost

**Solutions:**

1. **Use Raw Columns for HTML:**
```php
$this->table->forceRawColumns(['description', 'content']);
```

2. **Disable XSS for Specific Fields:**
```php
// In configuration
'core.output_encoding.html_entities' => false, // Use with caution
```

3. **Properly Escape Content:**
```php
// In your model or data processing
public function getSafeContentAttribute()
{
    return strip_tags($this->content, '<p><br><strong><em>');
}
```

## Filtering and Search Issues

### Issue: Filters Not Working

**Symptoms:**
- Filter modal not opening
- Filters not applying
- No filter options showing

**Solutions:**

1. **Check Filter Configuration:**
```php
$this->table->filterGroups('status', 'selectbox', true); // Third parameter must be true
```

2. **Verify Field Names:**
```php
// Ensure field names match database columns
$this->table->filterGroups('users.status', 'selectbox', true); // Use table.column format
```

3. **Check JavaScript Console:**
- Open browser DevTools
- Look for JavaScript errors
- Check Network tab for failed requests

4. **Debug Filter Data:**
```php
// Check if filter data is being generated
dd($this->table->getFilterData());
```

### Issue: Search Not Working

**Symptoms:**
- Global search returns no results
- Search box not responding
- Incorrect search results

**Solutions:**

1. **Enable Search:**
```php
$this->table->searchable(); // Must be called before lists()
```

2. **Check Searchable Columns:**
```php
// Specify which columns are searchable
$this->table->setSearchableColumns(['name', 'email']);
```

3. **Verify Search Configuration:**
```php
// Check search settings
dd($this->table->getSearchConfig());
```

### Issue: Filter Dependencies Not Working

**Symptoms:**
- Dependent filters not updating
- Filter chains broken
- AJAX errors in dependent filters

**Solutions:**

1. **Check Relationship Configuration:**
```php
$this->table->relations($this->model, 'group', 'name'); // Required for dependencies
```

2. **Verify Filter Order:**
```php
// Parent filter must be defined before dependent filter
$this->table->filterGroups('country', 'selectbox', true)
            ->filterGroups('state', 'selectbox', true); // Depends on country
```

3. **Debug Filter Dependencies:**
```php
// Check dependency configuration
dd($this->table->getFilterDependencies());
```

## Action Button Problems

### Issue: Action Buttons Not Showing

**Symptoms:**
- No action buttons in table
- Actions column missing
- Buttons not rendering

**Solutions:**

1. **Enable Actions:**
```php
$this->table->lists('users', ['name', 'email'], true); // Third parameter enables actions
```

2. **Check Action Configuration:**
```php
// Verify actions are configured
dd($this->table->getActions());
```

3. **Check Permissions:**
```php
// Ensure user has permissions for actions
$this->table->setActions([
    'edit' => [
        'condition' => function($row) {
            return auth()->user()->can('update', $row);
        }
    ]
]);
```

### Issue: Custom Actions Not Working

**Symptoms:**
- Custom action buttons not appearing
- Action URLs not working
- JavaScript actions failing

**Solutions:**

1. **Check Action Configuration:**
```php
$this->table->setActions([
    'custom_action' => [
        'label' => 'Custom Action',
        'url' => '/custom/{id}',
        'class' => 'btn btn-primary btn-sm'
    ]
]);

// Enable custom action
$this->table->lists('users', ['name', 'email'], [
    'view' => true,
    'edit' => true,
    'custom_action' => true // Must be enabled
]);
```

2. **Verify URL Placeholders:**
```php
// Ensure placeholder fields exist in data
'url' => '/users/{id}/custom', // {id} must exist in row data
```

3. **Check JavaScript Actions:**
```javascript
// Verify JavaScript function exists
function customAction(id) {
    console.log('Custom action for ID:', id);
}
```

## Database and Query Issues

### Issue: Database Connection Errors

**Symptoms:**
- "Connection refused" errors
- "Access denied" errors
- Database timeout errors

**Solutions:**

1. **Check Database Configuration:**
```php
// config/database.php
'connections' => [
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
    ]
]
```

2. **Test Database Connection:**
```bash
php artisan tinker
DB::connection()->getPdo();
```

3. **Check Environment Variables:**
```bash
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Issue: Query Errors

**Symptoms:**
- SQL syntax errors
- Column not found errors
- Table doesn't exist errors

**Solutions:**

1. **Check Table Names:**
```php
// Verify table exists
Schema::hasTable('users');
```

2. **Verify Column Names:**
```php
// Check available columns
Schema::getColumnListing('users');
```

3. **Debug Generated Queries:**
```php
// Enable query logging
DB::enableQueryLog();
$this->table->lists('users', ['name', 'email']);
dd(DB::getQueryLog());
```

4. **Check Raw SQL:**
```php
// Test raw SQL separately
$result = DB::select("SELECT name, email FROM users LIMIT 5");
dd($result);
```

## JavaScript Errors

### Issue: DataTables Not Initializing

**Symptoms:**
- Table appears as plain HTML
- No DataTables features working
- JavaScript console errors

**Solutions:**

1. **Include Required JavaScript:**
```blade
@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('vendor/canvastack/js/canvastack-table.js') }}"></script>
@endpush
```

2. **Check JavaScript Console:**
- Open browser DevTools (F12)
- Check Console tab for errors
- Look for missing file errors

3. **Verify jQuery Loading:**
```javascript
// In browser console
console.log(typeof jQuery); // Should return "function"
```

4. **Check DataTables Loading:**
```javascript
// In browser console
console.log(typeof $.fn.DataTable); // Should return "function"
```

### Issue: AJAX Errors in Console

**Symptoms:**
- 500 Internal Server Error
- 404 Not Found errors
- CORS errors

**Solutions:**

1. **Check AJAX URL:**
```javascript
// Verify AJAX endpoint exists
console.log($('#table-id').DataTable().ajax.url());
```

2. **Check Server Logs:**
```bash
tail -f storage/logs/laravel.log
```

3. **Debug AJAX Response:**
```javascript
// Add error handler
$('#table-id').DataTable({
    ajax: {
        url: '/ajax/endpoint',
        error: function(xhr, error, thrown) {
            console.log('AJAX Error:', xhr.responseText);
        }
    }
});
```

## General Debugging Tips

### Enable Debug Mode

```php
// In controller
$this->table->debug(true);
```

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Security logs
tail -f storage/logs/security.log

# Web server logs
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log
```

### Use Browser DevTools

1. **Network Tab**: Check for failed requests
2. **Console Tab**: Look for JavaScript errors
3. **Elements Tab**: Inspect generated HTML
4. **Sources Tab**: Debug JavaScript code

### Test in Isolation

```php
// Create minimal test controller
class TestController extends Controller
{
    public function test()
    {
        $this->table = new Objects();
        $this->table->lists('users', ['id', 'name']);
        return response($this->table->render());
    }
}
```

### Common Error Messages and Solutions

| Error Message | Likely Cause | Solution |
|---------------|--------------|----------|
| "Class not found" | Missing use statement | Add `use` statement |
| "Method not found" | Wrong method name | Check API documentation |
| "Column not found" | Wrong field name | Verify database schema |
| "CSRF token mismatch" | Missing CSRF token | Add CSRF meta tag |
| "Memory limit exceeded" | Large dataset | Enable server-side processing |
| "SQL injection detected" | Unsafe query | Use parameter binding |
| "Access denied" | Permission issue | Check user permissions |

---

## Getting Additional Help

### Documentation Resources
- [API Reference](../api/objects.md) - Complete method documentation
- [Examples](../examples/basic.md) - Working code examples
- [Configuration](../configuration.md) - Configuration options

### Community Support
- GitHub Issues - Report bugs and request features
- Stack Overflow - Community Q&A
- Laravel Forums - Framework-specific questions

### Professional Support
- Paid support options
- Custom development services
- Training and consultation

Remember to always check the browser console and server logs when troubleshooting issues. Most problems can be identified by examining error messages and following the solutions provided above.