# Core Controller Configuration Guide

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide documents all configuration options available for the Core Controller Components in Canvastack Origin framework. The configuration file is located at `config/canvastack.controller.php`.

## Table of Contents

1. [Security Configuration](#security-configuration)
2. [Performance Configuration](#performance-configuration)
3. [Caching Configuration](#caching-configuration)
4. [File Upload Configuration](#file-upload-configuration)
5. [Validation Configuration](#validation-configuration)
6. [Logging Configuration](#logging-configuration)
7. [Session Configuration](#session-configuration)
8. [DataTables Configuration](#datatables-configuration)
9. [Script Management Configuration](#script-management-configuration)
10. [Error Handling Configuration](#error-handling-configuration)
11. [Environment Variables](#environment-variables)
12. [Usage Examples](#usage-examples)

---

## Security Configuration

### Overview

Security configuration controls XSS protection, CSRF verification, SQL injection prevention, and file upload security.

### Options

#### `xss_protection`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_XSS_PROTECTION`
- **Description**: Enable automatic XSS protection by escaping all user-controllable output
- **Recommendation**: Always keep enabled in production

#### `csrf_protection`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_CSRF_PROTECTION`
- **Description**: Enable CSRF token verification for POST requests
- **Recommendation**: Always keep enabled in production

#### `sql_injection_prevention`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_SQL_INJECTION_PREVENTION`
- **Description**: Validate table and column names to prevent SQL injection attacks
- **Recommendation**: Always keep enabled in production

#### `escape_output`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ESCAPE_OUTPUT`
- **Description**: Escape output by default before rendering
- **Recommendation**: Keep enabled unless you have specific trusted content

#### `allowed_file_extensions`
- **Type**: `array`
- **Default**: `['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar']`
- **Description**: Whitelist of allowed file extensions for uploads
- **Recommendation**: Restrict to only necessary file types for your application

#### `max_file_size`
- **Type**: `integer` (bytes)
- **Default**: `10485760` (10MB)
- **Environment Variable**: `CANVASTACK_MAX_FILE_SIZE`
- **Description**: Maximum file size allowed for uploads
- **Recommendation**: Set based on your server capacity and use case

#### `sanitize_filenames`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_SANITIZE_FILENAMES`
- **Description**: Sanitize filenames to remove dangerous characters
- **Recommendation**: Always keep enabled

#### `validate_mime_types`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_VALIDATE_MIME_TYPES`
- **Description**: Validate MIME types match file extensions
- **Recommendation**: Keep enabled for security

#### `scan_uploads`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SCAN_UPLOADS`
- **Description**: Scan uploaded files for malware (requires ClamAV or similar)
- **Recommendation**: Enable if you have antivirus software installed

---

## Performance Configuration

### Overview

Performance configuration controls caching, eager loading, query optimization, and memory management.

### Options

#### `enable_caching`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ENABLE_CACHING`
- **Description**: Enable caching for expensive operations
- **Recommendation**: Keep enabled in production for better performance

#### `cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `3600` (1 hour)
- **Environment Variable**: `CANVASTACK_CACHE_TTL`
- **Description**: Default cache time-to-live
- **Recommendation**: Adjust based on data volatility

#### `eager_loading`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_EAGER_LOADING`
- **Description**: Enable eager loading to prevent N+1 query problems
- **Recommendation**: Keep enabled for optimal database performance

#### `query_optimization`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_QUERY_OPTIMIZATION`
- **Description**: Enable automatic query optimization
- **Recommendation**: Keep enabled

#### `memory_limit`
- **Type**: `string`
- **Default**: `'256M'`
- **Environment Variable**: `CANVASTACK_MEMORY_LIMIT`
- **Description**: Memory limit for controller operations
- **Recommendation**: Adjust based on your server resources

#### `performance_monitoring`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_PERFORMANCE_MONITORING`
- **Description**: Enable performance monitoring and metrics collection
- **Recommendation**: Enable in development, optional in production

#### `slow_query_threshold`
- **Type**: `integer` (milliseconds)
- **Default**: `1000`
- **Environment Variable**: `CANVASTACK_SLOW_QUERY_THRESHOLD`
- **Description**: Threshold for logging slow queries
- **Recommendation**: Adjust based on your performance requirements

---

## Caching Configuration

### Overview

Caching configuration controls specific caching strategies for different controller components.

### Options

#### `privilege_cache_enabled`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_PRIVILEGE_CACHE_ENABLED`
- **Description**: Cache privilege checks to improve performance
- **Recommendation**: Keep enabled

#### `privilege_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `3600` (1 hour)
- **Environment Variable**: `CANVASTACK_PRIVILEGE_CACHE_TTL`
- **Description**: TTL for privilege cache
- **Recommendation**: Use longer TTL if privileges change infrequently

#### `route_info_cache_enabled`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ROUTE_INFO_CACHE_ENABLED`
- **Description**: Cache route information
- **Recommendation**: Keep enabled

#### `route_info_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `3600` (1 hour)
- **Environment Variable**: `CANVASTACK_ROUTE_INFO_CACHE_TTL`
- **Description**: TTL for route info cache
- **Recommendation**: Use longer TTL if routes are static

#### `preference_cache_enabled`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_PREFERENCE_CACHE_ENABLED`
- **Description**: Cache user preferences
- **Recommendation**: Keep enabled

#### `preference_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `7200` (2 hours)
- **Environment Variable**: `CANVASTACK_PREFERENCE_CACHE_TTL`
- **Description**: TTL for preference cache
- **Recommendation**: Adjust based on how often preferences change

#### `file_validation_cache_enabled`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_FILE_VALIDATION_CACHE_ENABLED`
- **Description**: Cache file validation results
- **Recommendation**: Keep enabled

#### `file_validation_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `1800` (30 minutes)
- **Environment Variable**: `CANVASTACK_FILE_VALIDATION_CACHE_TTL`
- **Description**: TTL for file validation cache
- **Recommendation**: Use shorter TTL for security

#### `query_cache_enabled`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_QUERY_CACHE_ENABLED`
- **Description**: Cache query results
- **Recommendation**: Enable for read-heavy applications

#### `query_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `600` (10 minutes)
- **Environment Variable**: `CANVASTACK_QUERY_CACHE_TTL`
- **Description**: TTL for query cache
- **Recommendation**: Use shorter TTL for frequently changing data

---

## File Upload Configuration

### Overview

File upload configuration controls chunking, thumbnails, storage paths, and processing options.

### Options

#### `enable_chunking`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ENABLE_CHUNKING`
- **Description**: Enable chunked uploads for large files
- **Recommendation**: Keep enabled for better large file handling

#### `chunk_size`
- **Type**: `integer` (bytes)
- **Default**: `1048576` (1MB)
- **Environment Variable**: `CANVASTACK_CHUNK_SIZE`
- **Description**: Size of each chunk for chunked uploads
- **Recommendation**: Adjust based on network conditions

#### `enable_thumbnails`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ENABLE_THUMBNAILS`
- **Description**: Automatically generate thumbnails for images
- **Recommendation**: Keep enabled for image uploads

#### `thumbnail_width`
- **Type**: `integer` (pixels)
- **Default**: `150`
- **Environment Variable**: `CANVASTACK_THUMBNAIL_WIDTH`
- **Description**: Thumbnail width
- **Recommendation**: Adjust based on your UI requirements

#### `thumbnail_height`
- **Type**: `integer` (pixels)
- **Default**: `150`
- **Environment Variable**: `CANVASTACK_THUMBNAIL_HEIGHT`
- **Description**: Thumbnail height
- **Recommendation**: Adjust based on your UI requirements

#### `storage_path`
- **Type**: `string`
- **Default**: `'uploads'`
- **Environment Variable**: `CANVASTACK_STORAGE_PATH`
- **Description**: Storage path relative to storage/app
- **Recommendation**: Use a path outside web root for security

#### `unique_filenames`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_UNIQUE_FILENAMES`
- **Description**: Generate unique filenames to prevent overwrites
- **Recommendation**: Keep enabled

#### `preserve_original_name`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_PRESERVE_ORIGINAL_NAME`
- **Description**: Store original filename in metadata
- **Recommendation**: Keep enabled for user reference

#### `track_progress`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_TRACK_PROGRESS`
- **Description**: Enable upload progress tracking
- **Recommendation**: Enable for better UX with large files

#### `max_concurrent_uploads`
- **Type**: `integer`
- **Default**: `3`
- **Environment Variable**: `CANVASTACK_MAX_CONCURRENT_UPLOADS`
- **Description**: Maximum concurrent uploads per user
- **Recommendation**: Adjust based on server capacity

#### `upload_timeout`
- **Type**: `integer` (seconds)
- **Default**: `300` (5 minutes)
- **Environment Variable**: `CANVASTACK_UPLOAD_TIMEOUT`
- **Description**: Upload timeout
- **Recommendation**: Increase for large files or slow connections

---

## Validation Configuration

### Overview

Validation configuration controls input validation strictness and security checks.

### Options

#### `strict_mode`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_STRICT_MODE`
- **Description**: Enable strict validation mode
- **Recommendation**: Keep enabled in production

#### `validate_table_names`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_VALIDATE_TABLE_NAMES`
- **Description**: Validate table names against whitelist
- **Recommendation**: Always keep enabled for security

#### `validate_column_names`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_VALIDATE_COLUMN_NAMES`
- **Description**: Validate column names against schema
- **Recommendation**: Always keep enabled for security

#### `max_query_length`
- **Type**: `integer`
- **Default**: `10000`
- **Environment Variable**: `CANVASTACK_MAX_QUERY_LENGTH`
- **Description**: Maximum query length to prevent DoS attacks
- **Recommendation**: Adjust based on your query complexity needs

#### `max_filter_depth`
- **Type**: `integer`
- **Default**: `5`
- **Environment Variable**: `CANVASTACK_MAX_FILTER_DEPTH`
- **Description**: Maximum depth for nested filters
- **Recommendation**: Keep low to prevent DoS attacks

#### `max_array_size`
- **Type**: `integer`
- **Default**: `1000`
- **Environment Variable**: `CANVASTACK_MAX_ARRAY_SIZE`
- **Description**: Maximum array size for input validation
- **Recommendation**: Adjust based on your use case

#### `validate_session_integrity`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_VALIDATE_SESSION_INTEGRITY`
- **Description**: Validate session data integrity
- **Recommendation**: Keep enabled for security

---

## Logging Configuration

### Overview

Logging configuration controls what events are logged and where.

### Options

#### `log_security_events`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_SECURITY_EVENTS`
- **Description**: Log security-related events
- **Recommendation**: Always keep enabled

#### `log_performance_issues`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_PERFORMANCE_ISSUES`
- **Description**: Log performance issues
- **Recommendation**: Enable in production for monitoring

#### `log_validation_failures`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_VALIDATION_FAILURES`
- **Description**: Log validation failures
- **Recommendation**: Enable for debugging

#### `log_file_uploads`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_FILE_UPLOADS`
- **Description**: Log file upload operations
- **Recommendation**: Enable for audit trail

#### `log_privilege_violations`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_PRIVILEGE_VIOLATIONS`
- **Description**: Log privilege violations
- **Recommendation**: Always keep enabled for security

#### `log_csrf_failures`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_CSRF_FAILURES`
- **Description**: Log CSRF token failures
- **Recommendation**: Always keep enabled for security

#### `log_sql_injection_attempts`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_LOG_SQL_INJECTION_ATTEMPTS`
- **Description**: Log SQL injection attempts
- **Recommendation**: Always keep enabled for security

#### `log_channel`
- **Type**: `string`
- **Default**: `'stack'`
- **Environment Variable**: `CANVASTACK_LOG_CHANNEL`
- **Description**: Log channel for controller events
- **Recommendation**: Use dedicated channel for easier filtering

---

## Session Configuration

### Overview

Session configuration controls session management, timeout, and security.

### Options

#### `timeout`
- **Type**: `integer` (minutes)
- **Default**: `120` (2 hours)
- **Environment Variable**: `CANVASTACK_SESSION_TIMEOUT`
- **Description**: Session timeout duration
- **Recommendation**: Adjust based on security requirements

#### `regenerate_on_auth`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_REGENERATE_ON_AUTH`
- **Description**: Regenerate session ID after authentication
- **Recommendation**: Always keep enabled for security

#### `encrypt_sensitive_data`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ENCRYPT_SENSITIVE_DATA`
- **Description**: Encrypt sensitive session data
- **Recommendation**: Keep enabled for security

#### `enable_versioning`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SESSION_VERSIONING`
- **Description**: Enable session data versioning
- **Recommendation**: Enable if you need session history

#### `integrity_check`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_SESSION_INTEGRITY_CHECK`
- **Description**: Perform session integrity checks
- **Recommendation**: Keep enabled for security

---

## DataTables Configuration

### Overview

DataTables configuration controls server-side processing for DataTables.

### Options

#### `default_page_length`
- **Type**: `integer`
- **Default**: `10`
- **Environment Variable**: `CANVASTACK_DT_PAGE_LENGTH`
- **Description**: Default number of rows per page
- **Recommendation**: Adjust based on UI requirements

#### `max_page_length`
- **Type**: `integer`
- **Default**: `100`
- **Environment Variable**: `CANVASTACK_DT_MAX_PAGE_LENGTH`
- **Description**: Maximum rows per page
- **Recommendation**: Set limit to prevent performance issues

#### `server_side`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_DT_SERVER_SIDE`
- **Description**: Enable server-side processing
- **Recommendation**: Keep enabled for large datasets

#### `validate_request`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_DT_VALIDATE_REQUEST`
- **Description**: Validate DataTables request structure
- **Recommendation**: Keep enabled for security

#### `cache_results`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_DT_CACHE_RESULTS`
- **Description**: Cache DataTables results
- **Recommendation**: Enable for read-heavy tables

#### `cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `300` (5 minutes)
- **Environment Variable**: `CANVASTACK_DT_CACHE_TTL`
- **Description**: Cache TTL for DataTables results
- **Recommendation**: Use short TTL for frequently changing data

---

## Script Management Configuration

### Overview

Script management configuration controls JavaScript and CSS asset handling.

### Options

#### `deduplicate`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_SCRIPTS_DEDUPLICATE`
- **Description**: Remove duplicate script includes
- **Recommendation**: Keep enabled

#### `minify`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SCRIPTS_MINIFY`
- **Description**: Minify scripts
- **Recommendation**: Enable in production for performance

#### `concatenate`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SCRIPTS_CONCATENATE`
- **Description**: Concatenate scripts into single file
- **Recommendation**: Enable in production for fewer HTTP requests

#### `async_loading`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SCRIPTS_ASYNC`
- **Description**: Add async attribute to scripts
- **Recommendation**: Enable for non-critical scripts

#### `defer_loading`
- **Type**: `boolean`
- **Default**: `false`
- **Environment Variable**: `CANVASTACK_SCRIPTS_DEFER`
- **Description**: Add defer attribute to scripts
- **Recommendation**: Enable for better page load performance

#### `cache_manifests`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_SCRIPTS_CACHE_MANIFESTS`
- **Description**: Cache script manifests
- **Recommendation**: Keep enabled

#### `manifest_cache_ttl`
- **Type**: `integer` (seconds)
- **Default**: `3600` (1 hour)
- **Environment Variable**: `CANVASTACK_SCRIPTS_MANIFEST_TTL`
- **Description**: TTL for manifest cache
- **Recommendation**: Use longer TTL in production

---

## Error Handling Configuration

### Overview

Error handling configuration controls exception reporting and graceful degradation.

### Options

#### `show_detailed_errors`
- **Type**: `boolean`
- **Default**: `env('APP_DEBUG', false)`
- **Environment Variable**: `CANVASTACK_SHOW_DETAILED_ERRORS`
- **Description**: Show detailed error messages
- **Recommendation**: Only enable in development

#### `graceful_degradation`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_GRACEFUL_DEGRADATION`
- **Description**: Enable graceful degradation on errors
- **Recommendation**: Keep enabled in production

#### `enable_retry`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_ENABLE_RETRY`
- **Description**: Retry failed operations
- **Recommendation**: Keep enabled for transient errors

#### `max_retry_attempts`
- **Type**: `integer`
- **Default**: `3`
- **Environment Variable**: `CANVASTACK_MAX_RETRY_ATTEMPTS`
- **Description**: Maximum retry attempts
- **Recommendation**: Keep low to avoid cascading failures

#### `cache_fallback`
- **Type**: `boolean`
- **Default**: `true`
- **Environment Variable**: `CANVASTACK_CACHE_FALLBACK`
- **Description**: Fallback to database on cache failure
- **Recommendation**: Keep enabled for reliability

---

## Environment Variables

All configuration options can be overridden using environment variables in your `.env` file:

```env
# Security
CANVASTACK_XSS_PROTECTION=true
CANVASTACK_CSRF_PROTECTION=true
CANVASTACK_SQL_INJECTION_PREVENTION=true
CANVASTACK_MAX_FILE_SIZE=10485760

# Performance
CANVASTACK_ENABLE_CACHING=true
CANVASTACK_CACHE_TTL=3600
CANVASTACK_EAGER_LOADING=true
CANVASTACK_MEMORY_LIMIT=256M

# Caching
CANVASTACK_PRIVILEGE_CACHE_ENABLED=true
CANVASTACK_PRIVILEGE_CACHE_TTL=3600
CANVASTACK_ROUTE_INFO_CACHE_ENABLED=true

# File Upload
CANVASTACK_ENABLE_CHUNKING=true
CANVASTACK_CHUNK_SIZE=1048576
CANVASTACK_ENABLE_THUMBNAILS=true

# Validation
CANVASTACK_STRICT_MODE=true
CANVASTACK_VALIDATE_TABLE_NAMES=true
CANVASTACK_MAX_QUERY_LENGTH=10000

# Logging
CANVASTACK_LOG_SECURITY_EVENTS=true
CANVASTACK_LOG_PERFORMANCE_ISSUES=true
CANVASTACK_LOG_CHANNEL=stack

# Session
CANVASTACK_SESSION_TIMEOUT=120
CANVASTACK_REGENERATE_ON_AUTH=true
CANVASTACK_ENCRYPT_SENSITIVE_DATA=true

# DataTables
CANVASTACK_DT_PAGE_LENGTH=10
CANVASTACK_DT_MAX_PAGE_LENGTH=100
CANVASTACK_DT_SERVER_SIDE=true

# Scripts
CANVASTACK_SCRIPTS_DEDUPLICATE=true
CANVASTACK_SCRIPTS_MINIFY=false
CANVASTACK_SCRIPTS_CONCATENATE=false

# Error Handling
CANVASTACK_GRACEFUL_DEGRADATION=true
CANVASTACK_ENABLE_RETRY=true
CANVASTACK_MAX_RETRY_ATTEMPTS=3
```

---

## Usage Examples

### Example 1: High Security Configuration

For applications requiring maximum security:

```php
// config/canvastack.controller.php
return [
    'security' => [
        'xss_protection' => true,
        'csrf_protection' => true,
        'sql_injection_prevention' => true,
        'escape_output' => true,
        'allowed_file_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_file_size' => 5242880, // 5MB
        'sanitize_filenames' => true,
        'validate_mime_types' => true,
        'scan_uploads' => true,
    ],
    'validation' => [
        'strict_mode' => true,
        'validate_table_names' => true,
        'validate_column_names' => true,
        'max_query_length' => 5000,
        'max_filter_depth' => 3,
        'validate_session_integrity' => true,
    ],
    'logging' => [
        'log_security_events' => true,
        'log_privilege_violations' => true,
        'log_csrf_failures' => true,
        'log_sql_injection_attempts' => true,
    ],
];
```

### Example 2: High Performance Configuration

For applications requiring maximum performance:

```php
// config/canvastack.controller.php
return [
    'performance' => [
        'enable_caching' => true,
        'cache_ttl' => 7200, // 2 hours
        'eager_loading' => true,
        'query_optimization' => true,
        'memory_limit' => '512M',
    ],
    'caching' => [
        'privilege_cache_enabled' => true,
        'privilege_cache_ttl' => 7200,
        'route_info_cache_enabled' => true,
        'route_info_cache_ttl' => 7200,
        'query_cache_enabled' => true,
        'query_cache_ttl' => 1800,
    ],
    'scripts' => [
        'deduplicate' => true,
        'minify' => true,
        'concatenate' => true,
        'cache_manifests' => true,
    ],
];
```

### Example 3: Development Configuration

For development environments:

```php
// config/canvastack.controller.php
return [
    'performance' => [
        'enable_caching' => false,
        'performance_monitoring' => true,
    ],
    'error_handling' => [
        'show_detailed_errors' => true,
        'graceful_degradation' => false,
    ],
    'logging' => [
        'log_security_events' => true,
        'log_performance_issues' => true,
        'log_validation_failures' => true,
    ],
];
```

### Example 4: Accessing Configuration in Code

```php
// Get security configuration
$xssProtection = config('canvastack.controller.security.xss_protection');

// Get caching configuration
$privilegeCacheTtl = config('canvastack.controller.caching.privilege_cache_ttl');

// Get file upload configuration
$maxFileSize = config('canvastack.controller.security.max_file_size');

// Check if feature is enabled
if (config('canvastack.controller.performance.enable_caching')) {
    // Use caching
}
```

---

## Best Practices

1. **Security First**: Always keep security features enabled in production
2. **Environment-Specific**: Use different configurations for development, staging, and production
3. **Monitor Performance**: Enable performance monitoring in production to identify bottlenecks
4. **Log Security Events**: Always log security-related events for audit trails
5. **Cache Wisely**: Use appropriate TTL values based on data volatility
6. **Validate Input**: Keep strict validation enabled to prevent attacks
7. **Test Configuration**: Test configuration changes in staging before production
8. **Document Changes**: Document any custom configuration changes for your team

---

## Troubleshooting

### Performance Issues

If experiencing slow performance:
1. Enable caching: `enable_caching = true`
2. Increase cache TTL values
3. Enable eager loading: `eager_loading = true`
4. Enable query optimization: `query_optimization = true`
5. Check slow query logs

### Security Concerns

If experiencing security issues:
1. Verify XSS protection is enabled
2. Verify CSRF protection is enabled
3. Check security event logs
4. Enable SQL injection prevention
5. Restrict allowed file extensions

### File Upload Problems

If file uploads are failing:
1. Check `max_file_size` setting
2. Verify `allowed_file_extensions` includes your file type
3. Check server PHP upload limits
4. Enable `log_file_uploads` for debugging
5. Verify storage path permissions

### Cache Issues

If cache is not working:
1. Verify cache driver is configured in Laravel
2. Check cache TTL values
3. Verify cache keys are unique
4. Enable cache fallback: `cache_fallback = true`
5. Clear cache: `php artisan cache:clear`

---

## Migration Guide

### From Default Configuration

If migrating from default Laravel configuration:

1. Publish the configuration file:
```bash
php artisan vendor:publish --tag=canvastack-config
```

2. Review and adjust settings based on your needs

3. Update environment variables in `.env`

4. Test thoroughly in staging environment

5. Deploy to production

### Upgrading Configuration

When upgrading to a new version:

1. Backup current configuration
2. Review new configuration options
3. Merge custom settings
4. Test in development
5. Deploy to production

---

## Support

For questions or issues with configuration:

- Documentation: Check this guide and other docs in `vendor/canvastack/canvastack/docs/CORE/`
- Issues: Report issues to your development team
- Security: Report security issues privately to security team

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, this configuration guide provides comprehensive documentation for all Core Controller configuration options.
