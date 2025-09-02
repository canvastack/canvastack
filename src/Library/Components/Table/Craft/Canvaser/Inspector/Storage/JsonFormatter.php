<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Storage;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\InspectorConfig;

/**
 * JSON Formatting Utilities
 *
 * Handles JSON formatting, pretty printing, and data serialization
 * for the Inspector module.
 *
 * @version 1.0.0
 */
class JsonFormatter
{
    /**
     * Format data for storage.
     *
     * @param  array  $data Data to format
     * @return string Formatted JSON string
     */
    public static function format(array $data): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if (InspectorConfig::shouldFormatJson()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        // Add metadata
        $data['_inspector_meta'] = [
            'version' => '1.0.0',
            'formatted_at' => date('c'),
            'format_flags' => $flags,
        ];

        return json_encode($data, $flags);
    }

    /**
     * Format data for human readability.
     *
     * @param  array  $data Data to format
     * @return string Pretty-printed JSON
     */
    public static function prettyPrint(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format data for compact storage.
     *
     * @param  array  $data Data to format
     * @return string Compact JSON
     */
    public static function compact(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse JSON string back to array.
     *
     * @param  string  $json JSON string
     * @return array|null Parsed data or null if invalid
     */
    public static function parse(string $json): ?array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (\JsonException $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector JSON parse error: '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Validate JSON structure.
     *
     * @param  string  $json JSON string to validate
     * @return array Validation result
     */
    public static function validate(string $json): array
    {
        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return [
                'valid' => true,
                'error' => null,
                'size' => strlen($json),
            ];
        } catch (\JsonException $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'size' => strlen($json),
            ];
        }
    }

    /**
     * Sanitize data before JSON encoding.
     *
     * @param  mixed  $data Data to sanitize
     * @return mixed Sanitized data
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }

        if (is_object($data)) {
            // Convert objects to arrays for JSON serialization
            if (method_exists($data, 'toArray')) {
                return self::sanitize($data->toArray());
            }

            if (method_exists($data, '__toString')) {
                return (string) $data;
            }

            // Convert to array using object properties
            return self::sanitize(get_object_vars($data));
        }

        if (is_resource($data)) {
            return '[RESOURCE: '.get_resource_type($data).']';
        }

        if (is_callable($data)) {
            return '[CALLABLE]';
        }

        // Handle binary data
        if (is_string($data) && ! mb_check_encoding($data, 'UTF-8')) {
            return '[BINARY DATA: '.strlen($data).' bytes]';
        }

        return $data;
    }

    /**
     * Create a summary of large data structures.
     *
     * @param  array  $data Data to summarize
     * @param  int  $maxDepth Maximum depth to traverse
     * @param  int  $maxItems Maximum items per array/object
     * @return array Summarized data
     */
    public static function summarize(array $data, int $maxDepth = 3, int $maxItems = 10): array
    {
        return self::summarizeRecursive($data, $maxDepth, $maxItems, 0);
    }

    /**
     * Recursively summarize data structure.
     *
     * @param  mixed  $data Data to summarize
     * @param  int  $maxDepth Maximum depth
     * @param  int  $maxItems Maximum items
     * @param  int  $currentDepth Current recursion depth
     * @return mixed Summarized data
     */
    private static function summarizeRecursive($data, int $maxDepth, int $maxItems, int $currentDepth)
    {
        if ($currentDepth >= $maxDepth) {
            if (is_array($data)) {
                return '[ARRAY: '.count($data).' items, depth limit reached]';
            }
            if (is_object($data)) {
                return '[OBJECT: '.get_class($data).', depth limit reached]';
            }

            return $data;
        }

        if (is_array($data)) {
            $count = count($data);
            if ($count > $maxItems) {
                $summarized = array_slice($data, 0, $maxItems, true);
                $summarized['_truncated'] = '... and '.($count - $maxItems).' more items';

                return array_map(function ($item) use ($maxDepth, $maxItems, $currentDepth) {
                    return self::summarizeRecursive($item, $maxDepth, $maxItems, $currentDepth + 1);
                }, $summarized);
            }

            return array_map(function ($item) use ($maxDepth, $maxItems, $currentDepth) {
                return self::summarizeRecursive($item, $maxDepth, $maxItems, $currentDepth + 1);
            }, $data);
        }

        if (is_object($data)) {
            return '[OBJECT: '.get_class($data).']';
        }

        if (is_string($data) && strlen($data) > 1000) {
            return substr($data, 0, 1000).'... [TRUNCATED: '.strlen($data).' total chars]';
        }

        return $data;
    }

    /**
     * Extract key statistics from data.
     *
     * @param  array  $data Data to analyze
     * @return array Statistics
     */
    public static function getStats(array $data): array
    {
        return [
            'total_keys' => self::countKeys($data),
            'max_depth' => self::getMaxDepth($data),
            'data_types' => self::getDataTypes($data),
            'memory_usage' => strlen(json_encode($data)),
        ];
    }

    /**
     * Count total keys in nested array.
     */
    private static function countKeys(array $data): int
    {
        $count = count($data);

        foreach ($data as $value) {
            if (is_array($value)) {
                $count += self::countKeys($value);
            }
        }

        return $count;
    }

    /**
     * Get maximum depth of nested array.
     */
    private static function getMaxDepth(array $data, int $depth = 1): int
    {
        $maxDepth = $depth;

        foreach ($data as $value) {
            if (is_array($value)) {
                $childDepth = self::getMaxDepth($value, $depth + 1);
                $maxDepth = max($maxDepth, $childDepth);
            }
        }

        return $maxDepth;
    }

    /**
     * Get data type distribution.
     */
    private static function getDataTypes(array $data): array
    {
        $types = [];

        foreach ($data as $value) {
            $type = gettype($value);
            $types[$type] = ($types[$type] ?? 0) + 1;

            if (is_array($value)) {
                $childTypes = self::getDataTypes($value);
                foreach ($childTypes as $childType => $count) {
                    $types[$childType] = ($types[$childType] ?? 0) + $count;
                }
            }
        }

        return $types;
    }
}
