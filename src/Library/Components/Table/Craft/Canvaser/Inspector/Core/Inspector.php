<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Diagnostics\ContextCapture;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Storage\FileManager;

/**
 * Main Inspector Class
 *
 * Primary entry point for all datatable diagnostic operations.
 * Provides a clean, simple API for capturing and storing diagnostic data.
 *
 * @version 1.0.0
 */
class Inspector
{
    /**
     * Capture and store datatable diagnostic context.
     *
     * This is the main method called from the orchestrator to capture
     * comprehensive diagnostic information about datatable operations.
     *
     * @param  mixed  $context Raw context data from datatable operation or $this object
     *
     * @example
     * // Simple usage with $this object (recommended)
     * Inspector::inspect($this);
     *
     * // Legacy array usage (still supported)
     * Inspector::inspect([
     *     'table_name' => 'users',
     *     'model_type' => 'User',
     *     'columns' => ['id', 'name', 'email'],
     *     'filters' => ['status' => 'active'],
     * ]);
     */
    public static function inspect($context): void
    {
        try {
            // Check if inspector is enabled
            if (! InspectorConfig::isEnabled()) {
                return;
            }

            // Convert context to array if it's an object
            $contextArray = self::normalizeContext($context);

            // Capture comprehensive context
            $diagnosticData = ContextCapture::capture($contextArray);

            // Store diagnostic data
            FileManager::store($diagnosticData);

        } catch (\Throwable $e) {
            // Silent fail - never break production flow
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector error: '.$e->getMessage());
            }
        }
    }

    /**
     * Quick diagnostic dump for immediate debugging.
     *
     * Simplified version of inspect() for quick debugging scenarios.
     * Stores data with minimal processing for immediate use.
     *
     * @param  array  $data Data to dump
     * @param  string|null  $label Optional label for the dump
     */
    public static function dump(array $data, ?string $label = null): void
    {
        try {
            if (! InspectorConfig::isEnabled()) {
                return;
            }

            $dumpData = [
                'timestamp' => date('c'),
                'label' => $label ?? 'quick_dump',
                'data' => $data,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            ];

            FileManager::quickDump($dumpData);

        } catch (\Throwable $e) {
            // Silent fail
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector dump error: '.$e->getMessage());
            }
        }
    }

    /**
     * Get inspector status and configuration.
     *
     * @return array Status information
     */
    public static function status(): array
    {
        return [
            'enabled' => InspectorConfig::isEnabled(),
            'mode' => InspectorConfig::getMode(),
            'storage_path' => InspectorConfig::getStoragePath(),
            'debug_mode' => InspectorConfig::isDebugMode(),
            'environment' => app()->environment(),
            'version' => '1.0.0',
        ];
    }

    /**
     * Clean up old diagnostic files.
     *
     * @param  int|null  $daysOld Files older than this many days will be removed
     * @return int Number of files cleaned up
     */
    public static function cleanup(?int $daysOld = null): int
    {
        try {
            return FileManager::cleanup($daysOld);
        } catch (\Throwable $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector cleanup error: '.$e->getMessage());
            }

            return 0;
        }
    }

    /**
     * Normalize context data from various input types.
     *
     * Converts object properties to array format for consistent processing.
     * Handles both legacy array input and new object-based input.
     *
     * @param  mixed  $context Input context (array or object)
     * @return array Normalized context array
     */
    private static function normalizeContext($context): array
    {
        // If already an array, return as-is (legacy support)
        if (is_array($context)) {
            return $context;
        }

        // If it's an object, extract relevant properties
        if (is_object($context)) {
            return self::extractObjectContext($context);
        }

        // For other types, wrap in array
        return ['raw_context' => $context];
    }

    /**
     * Extract context data from object properties.
     *
     * Dynamically extracts datatable-related properties from the object
     * to create comprehensive diagnostic context.
     *
     * @param  object  $object Source object (typically $this from orchestrator)
     * @return array Extracted context data
     */
    private static function extractObjectContext($object): array
    {
        $context = [];

        // Get object class information
        $reflection = new \ReflectionClass($object);
        $context['object_info'] = [
            'class' => $reflection->getName(),
            'short_name' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
        ];

        // Extract public properties
        $publicProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($publicProperties as $property) {
            $propertyName = $property->getName();
            if ($property->isInitialized($object)) {
                $context['public_properties'][$propertyName] = $property->getValue($object);
            }
        }

        // Extract common datatable properties if they exist
        $datatableProperties = [
            'table_name', 'model_type', 'model_source',
            'blacklists', 'orderBy', 'action_list', 'removed_privileges',
            'joinFields', 'fconds', 'limit', 'row_attributes', 'urlTarget',
        ];

        foreach ($datatableProperties as $propName) {
            if (property_exists($object, $propName)) {
                try {
                    $property = $reflection->getProperty($propName);
                    if ($property->isPublic() || $property->isProtected()) {
                        $property->setAccessible(true);
                        if ($property->isInitialized($object)) {
                            $context['datatable_properties'][$propName] = $property->getValue($object);
                        }
                    }
                } catch (\ReflectionException $e) {
                    // Property doesn't exist or can't be accessed
                    continue;
                }
            }
        }

        // Try to extract method results for common getters
        $getterMethods = [
            'getTableName', 'getModelType', 'getModelSource',
            'getColumns', 'getFilters', 'getPaging', 'getJoins',
        ];

        foreach ($getterMethods as $methodName) {
            if (method_exists($object, $methodName)) {
                try {
                    $context['method_results'][$methodName] = $object->$methodName();
                } catch (\Throwable $e) {
                    // Method call failed, skip
                    continue;
                }
            }
        }

        // Extract global data if accessible
        if (property_exists($object, 'data') && isset($object->data)) {
            $context['global_data'] = self::extractGlobalData($object->data);
        }

        // Extract request data
        if (function_exists('request')) {
            $context['request_data'] = [
                'all' => request()->all(),
                'route' => request()->route() ? [
                    'name' => request()->route()->getName(),
                    'uri' => request()->route()->uri(),
                    'parameters' => request()->route()->parameters(),
                ] : null,
            ];
        }

        return $context;
    }

    /**
     * Extract relevant data from global data object.
     *
     * @param  mixed  $data Global data object
     * @return array Extracted data
     */
    private static function extractGlobalData($data): array
    {
        if (! is_object($data)) {
            return ['raw_data' => $data];
        }

        $extracted = [];

        // Extract datatables configuration if available
        if (property_exists($data, 'datatables')) {
            $extracted['datatables'] = $data->datatables;
        }

        // Extract other common properties
        $commonProps = ['config', 'settings', 'options', 'parameters'];
        foreach ($commonProps as $prop) {
            if (property_exists($data, $prop)) {
                $extracted[$prop] = $data->$prop;
            }
        }

        return $extracted;
    }
}
