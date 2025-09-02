<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Diagnostics;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\InspectorConfig;

/**
 * Context Data Capture
 *
 * Captures comprehensive context information from datatable operations.
 * Handles data sanitization, enrichment, and formatting.
 *
 * @version 1.0.0
 */
class ContextCapture
{
    /**
     * Capture comprehensive datatable context.
     *
     * @param  array  $rawContext Raw context data from datatable operation
     * @return array Enriched and sanitized context data
     */
    public static function capture(array $rawContext): array
    {
        $context = [
            'meta' => self::captureMeta(),
            'request' => self::captureRequest(),
            'datatable' => self::sanitizeContext($rawContext),
            'performance' => self::capturePerformance(),
            'environment' => self::captureEnvironment(),
        ];

        // Add stack trace if enabled
        if (InspectorConfig::shouldIncludeTrace()) {
            $context['trace'] = self::captureTrace();
        }

        return $context;
    }

    /**
     * Capture metadata about the inspection.
     */
    private static function captureMeta(): array
    {
        return [
            'timestamp' => date('c'),
            'inspector_version' => '1.0.0',
            'capture_id' => uniqid('capture_', true),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Capture request information.
     */
    private static function captureRequest(): array
    {
        if (! InspectorConfig::shouldIncludeRequestData()) {
            return ['included' => false, 'reason' => 'disabled_by_config'];
        }

        try {
            if (! function_exists('request') || ! request()) {
                return ['included' => false, 'reason' => 'no_request_context'];
            }

            $request = request();
            $route = $request->route();

            $requestData = [
                'included' => true,
                'method' => $request->method(),
                'url' => $request->url(),
                'path' => $request->path(),
                'query' => $request->query(),
                'headers' => self::sanitizeHeaders($request->headers->all()),
                'route' => [
                    'name' => $route ? $route->getName() : null,
                    'uri' => $route ? $route->uri() : null,
                    'action' => $route ? $route->getActionName() : null,
                    'parameters' => $route ? $route->parameters() : [],
                ],
                'user' => self::captureUser(),
                'session' => self::captureSession(),
            ];

            // Include request body for POST/PUT/PATCH
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                $requestData['body'] = self::sanitizeRequestBody($request->all());
            }

            return $requestData;

        } catch (\Throwable $e) {
            return [
                'included' => false,
                'reason' => 'capture_error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sanitize and enrich the datatable context.
     */
    private static function sanitizeContext(array $rawContext): array
    {
        $context = $rawContext;

        // Add enrichment data
        $context['enriched'] = [
            'total_columns' => count($context['columns']['lists'] ?? []),
            'total_filters' => count($context['filters']['applied'] ?? []),
            'has_joins' => ! empty($context['joins']['foreign_keys'] ?? []),
            'has_actions' => ! empty($context['columns']['actions'] ?? []),
            'pagination_enabled' => ($context['paging']['length'] ?? 0) > 0,
        ];

        // Sanitize sensitive data
        if (InspectorConfig::shouldExcludeSensitive()) {
            $context = self::removeSensitiveData($context);
        }

        return $context;
    }

    /**
     * Capture performance metrics.
     */
    private static function capturePerformance(): array
    {
        return [
            'memory_current' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'time_limit' => ini_get('max_execution_time'),
            'microtime' => microtime(true),
        ];
    }

    /**
     * Capture environment information.
     */
    private static function captureEnvironment(): array
    {
        return [
            'app_env' => function_exists('app') ? app()->environment() : (getenv('APP_ENV') ?: 'unknown'),
            'app_debug' => InspectorConfig::isDebugMode(),
            'php_sapi' => PHP_SAPI,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'database' => self::captureDatabaseInfo(),
        ];
    }

    /**
     * Capture stack trace information.
     */
    private static function captureTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Filter out Inspector internal calls
        $filteredTrace = array_filter($trace, function ($frame) {
            $class = $frame['class'] ?? '';

            return strpos($class, 'Inspector') === false;
        });

        return array_values($filteredTrace);
    }

    /**
     * Capture user information (if available).
     */
    private static function captureUser(): array
    {
        try {
            if (! function_exists('auth') || ! auth()->check()) {
                return ['authenticated' => false];
            }

            $user = auth()->user();

            return [
                'authenticated' => true,
                'id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [],
            ];

        } catch (\Throwable $e) {
            return [
                'authenticated' => false,
                'error' => 'capture_failed',
            ];
        }
    }

    /**
     * Capture session information.
     */
    private static function captureSession(): array
    {
        try {
            if (! function_exists('session') || ! session()->isStarted()) {
                return ['active' => false];
            }

            return [
                'active' => true,
                'id' => session()->getId(),
                'name' => session()->getName(),
                'driver' => config('session.driver', 'unknown'),
            ];

        } catch (\Throwable $e) {
            return [
                'active' => false,
                'error' => 'capture_failed',
            ];
        }
    }

    /**
     * Capture database information.
     */
    private static function captureDatabaseInfo(): array
    {
        try {
            if (! function_exists('config')) {
                return ['available' => false];
            }

            $defaultConnection = config('database.default');
            $connections = config('database.connections', []);
            $defaultConfig = $connections[$defaultConnection] ?? [];

            return [
                'available' => true,
                'default_connection' => $defaultConnection,
                'driver' => $defaultConfig['driver'] ?? 'unknown',
                'host' => $defaultConfig['host'] ?? 'unknown',
                'database' => $defaultConfig['database'] ?? 'unknown',
                'port' => $defaultConfig['port'] ?? null,
            ];

        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => 'capture_failed',
            ];
        }
    }

    /**
     * Sanitize HTTP headers.
     */
    private static function sanitizeHeaders(array $headers): array
    {
        if (! InspectorConfig::shouldExcludeSensitive()) {
            return $headers;
        }

        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
            'x-csrf-token',
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body data.
     */
    private static function sanitizeRequestBody(array $data): array
    {
        if (! InspectorConfig::shouldExcludeSensitive()) {
            return $data;
        }

        return self::removeSensitiveData($data);
    }

    /**
     * Remove sensitive data from array recursively.
     */
    private static function removeSensitiveData(array $data): array
    {
        $sensitivePatterns = InspectorConfig::getSensitivePatterns();

        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);

            // Check if key matches sensitive patterns
            foreach ($sensitivePatterns as $pattern) {
                if (strpos($keyLower, $pattern) !== false) {
                    $data[$key] = '[REDACTED]';

                    continue 2; // Skip to next key
                }
            }

            // Recursively process arrays
            if (is_array($value)) {
                $data[$key] = self::removeSensitiveData($value);
            }
        }

        return $data;
    }
}
