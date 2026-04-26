# CSRF Protection Documentation

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

Alhamdulillah, CSRF (Cross-Site Request Forgery) protection has been implemented in the Core Controller Components to prevent unauthorized state-changing requests.

## How It Works

CSRF protection is automatically applied to all state-changing HTTP requests:
- POST
- PUT
- PATCH
- DELETE

The protection is implemented in the `Controller::callAction()` method, which intercepts all controller actions before they are executed.

## Token Validation

CSRF tokens are validated from multiple sources (in order of priority):
1. Request body: `_token` parameter
2. Request header: `X-CSRF-TOKEN`
3. Request header: `X-XSRF-TOKEN`
4. Query parameter: `_token`

## Configuration

CSRF protection can be configured in `config/canvastack.controller.php`:

```php
'security' => [
    /**
     * CSRF Protection
     * 
     * When enabled, all POST requests will require valid CSRF tokens.
     * This prevents Cross-Site Request Forgery attacks.
     * 
     * @var bool
     * @default true
     */
    'csrf_protection' => env('CANVASTACK_CONTROLLER_CSRF_PROTECTION', true),
],
```

### Environment Variable

You can also configure CSRF protection via environment variable in `.env`:

```env
CANVASTACK_CONTROLLER_CSRF_PROTECTION=true
```

## Usage

### Form Submissions

For HTML forms, include the CSRF token using Laravel's `@csrf` directive:

```blade
<form method="POST" action="/users">
    @csrf
    <input type="text" name="name">
    <button type="submit">Submit</button>
</form>
```

### AJAX Requests

For AJAX requests, include the CSRF token in the request headers:

```javascript
// Using jQuery
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Using Axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Using Fetch API
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify(data)
});
```

Make sure to include the CSRF token meta tag in your HTML head:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### File Uploads

File uploads via POST requests are automatically protected. Include the CSRF token in your form:

```blade
<form method="POST" action="/upload" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file">
    <button type="submit">Upload</button>
</form>
```

### DataTables POST Requests

DataTables server-side processing with POST method is automatically protected. Configure DataTables to include the CSRF token:

```javascript
$('#myTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/data',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    },
    columns: [
        // ... column definitions
    ]
});
```

## Error Handling

When CSRF validation fails, the system will:

1. Log the security event with details:
   - URL
   - HTTP method
   - IP address
   - User agent
   - Token presence status

2. Return a 419 HTTP status code with a user-friendly error message:
   ```json
   {
       "status": "error",
       "message": "Your request could not be processed due to a security check failure. Please refresh the page and try again."
   }
   ```

## Logging

CSRF failures are logged when security logging is enabled:

```php
'logging' => [
    'log_security_events' => env('CANVASTACK_CONTROLLER_LOG_SECURITY_EVENTS', true),
],
```

Log entries include:
- Event type: `csrf_failure`
- Failure reason: `CSRF token missing` or `CSRF token mismatch`
- Request context: URL, method, IP, user agent
- Controller and method information

## Disabling CSRF Protection

**WARNING:** Disabling CSRF protection is not recommended for production environments.

To disable CSRF protection (for testing or development):

1. Via configuration file:
   ```php
   'security' => [
       'csrf_protection' => false,
   ],
   ```

2. Via environment variable:
   ```env
   CANVASTACK_CONTROLLER_CSRF_PROTECTION=false
   ```

## Testing

CSRF protection can be tested using the provided test suite:

```bash
php artisan test tests/Security/CSRFProtectionTest.php
```

The test suite includes:
- Valid token validation
- Missing token detection
- Invalid token detection
- Token from request body
- Token from headers (X-CSRF-TOKEN, X-XSRF-TOKEN)
- Configuration toggle
- Exception context and user messages

## Security Best Practices

1. **Always enable CSRF protection in production**
2. **Include CSRF tokens in all forms**
3. **Configure AJAX libraries to send CSRF tokens**
4. **Monitor CSRF failure logs for potential attacks**
5. **Educate users to refresh the page if they see CSRF errors**
6. **Use HTTPS to prevent token interception**
7. **Set appropriate session timeout values**

## Troubleshooting

### Common Issues

**Issue:** "CSRF token is missing" error
- **Solution:** Ensure `@csrf` directive is included in forms or CSRF token is sent in AJAX headers

**Issue:** "CSRF token mismatch" error
- **Solution:** 
  - Clear browser cache and cookies
  - Ensure session is properly configured
  - Check that the token hasn't expired
  - Verify the token is being sent correctly

**Issue:** CSRF protection not working
- **Solution:**
  - Verify `csrf_protection` is enabled in configuration
  - Check that the request method is POST/PUT/PATCH/DELETE
  - Ensure the controller extends the Core Controller

## API Reference

### Helper Functions

#### `canvastack_controller_validate_csrf($request)`

Validates CSRF token for the given request.

**Parameters:**
- `$request` (Request): The HTTP request to validate

**Returns:**
- `bool`: True if validation passes

**Throws:**
- `CSRFException`: If token is missing or invalid

**Example:**
```php
try {
    canvastack_controller_validate_csrf($request);
    // Process request
} catch (CSRFException $e) {
    // Handle CSRF failure
    return response()->json([
        'error' => $e->getUserMessage()
    ], 419);
}
```

#### `canvastack_controller_log_security_event($type, $message, $context)`

Logs security-related events.

**Parameters:**
- `$type` (string): Event type (e.g., 'csrf_failure')
- `$message` (string): Event message
- `$context` (array): Additional context data

**Returns:**
- `void`

**Example:**
```php
canvastack_controller_log_security_event(
    'csrf_failure',
    'CSRF token validation failed',
    [
        'url' => $request->fullUrl(),
        'ip' => $request->ip(),
    ]
);
```

### Exception Classes

#### `CSRFException`

Exception thrown when CSRF token validation fails.

**Methods:**
- `getContext()`: Returns array of context data
- `getUserMessage()`: Returns user-friendly error message

**Example:**
```php
try {
    // ... validation code
} catch (CSRFException $e) {
    $context = $e->getContext();
    $userMessage = $e->getUserMessage();
    
    Log::warning('CSRF failure', $context);
    return response()->json(['error' => $userMessage], 419);
}
```

## Related Documentation

- [Security Configuration](./SECURITY_CONFIGURATION.md)
- [XSS Protection](./XSS_PROTECTION.md)
- [Input Validation](./INPUT_VALIDATION.md)
- [Session Security](./SESSION_SECURITY.md)

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, CSRF protection has been successfully implemented to secure the Core Controller Components against Cross-Site Request Forgery attacks.
