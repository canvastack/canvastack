<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart;

use Illuminate\Support\Collection;

/**
 * DataTransformer - Optimized data transformation for charts.
 *
 * Provides efficient data transformation methods for various chart types.
 * Performance target: < 100ms for typical datasets.
 *
 * Features:
 * - Optimized array operations
 * - Memory-efficient processing
 * - Support for multiple data formats
 * - Validation and sanitization
 */
class DataTransformer
{
    /**
     * Transform raw data to chart series format.
     *
     * Converts various data formats to ApexCharts series format:
     * [
     *   ['name' => 'Series 1', 'data' => [1, 2, 3]],
     *   ['name' => 'Series 2', 'data' => [4, 5, 6]],
     * ]
     *
     * @param mixed $data Raw data (array, Collection, or query result)
     * @param string|null $nameKey Key for series name
     * @param string|null $dataKey Key for series data
     * @return array Transformed series data
     */
    public function toSeries($data, ?string $nameKey = null, ?string $dataKey = null): array
    {
        // Convert to array if Collection
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        // Handle empty data
        if (empty($data)) {
            return [];
        }

        // If data is already in series format, return as-is
        if ($this->isSeriesFormat($data)) {
            return $data;
        }

        // Transform based on data structure
        if ($this->isKeyValuePairs($data)) {
            return $this->transformKeyValuePairs($data, $nameKey, $dataKey);
        }

        if ($this->isGroupedData($data)) {
            return $this->transformGroupedData($data, $nameKey, $dataKey);
        }

        // Default: treat as single series
        return [
            [
                'name' => $nameKey ?? 'Series 1',
                'data' => array_values($data),
            ],
        ];
    }

    /**
     * Transform data to labels array.
     *
     * Extracts labels from various data formats.
     *
     * @param mixed $data Raw data
     * @param string|null $labelKey Key for label extraction
     * @return array Labels array
     */
    public function toLabels($data, ?string $labelKey = null): array
    {
        // Convert to array if Collection
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        // Handle empty data
        if (empty($data)) {
            return [];
        }

        // If data is simple array, return as-is
        if ($this->isSimpleArray($data)) {
            return array_values($data);
        }

        // Extract labels from associative array
        if ($labelKey && $this->isAssociativeArray($data)) {
            return array_column($data, $labelKey);
        }

        // Extract keys as labels
        if ($this->isKeyValuePairs($data)) {
            return array_keys($data);
        }

        // Default: generate numeric labels
        return range(1, count($data));
    }

    /**
     * Transform data for pie/donut charts.
     *
     * Converts data to format: [value1, value2, value3]
     * And labels: [label1, label2, label3]
     *
     * @param mixed $data Raw data
     * @param string|null $valueKey Key for values
     * @param string|null $labelKey Key for labels
     * @return array ['series' => [...], 'labels' => [...]]
     */
    public function toPieData($data, ?string $valueKey = null, ?string $labelKey = null): array
    {
        // Convert to array if Collection
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        // Handle empty data
        if (empty($data)) {
            return ['series' => [], 'labels' => []];
        }

        // If data is key-value pairs
        if ($this->isKeyValuePairs($data)) {
            return [
                'series' => array_values($data),
                'labels' => array_keys($data),
            ];
        }

        // If data is array of objects/arrays
        if ($this->isAssociativeArray($data)) {
            $series = [];
            $labels = [];

            foreach ($data as $item) {
                if (is_array($item) || is_object($item)) {
                    $item = (array) $item;
                    $series[] = $valueKey ? ($item[$valueKey] ?? 0) : reset($item);
                    $labels[] = $labelKey ? ($item[$labelKey] ?? '') : key($item);
                } else {
                    $series[] = $item;
                    $labels[] = '';
                }
            }

            return ['series' => $series, 'labels' => $labels];
        }

        // Default: treat as simple values
        return [
            'series' => array_values($data),
            'labels' => range(1, count($data)),
        ];
    }

    /**
     * Transform time series data.
     *
     * Converts data with timestamps to ApexCharts time series format.
     *
     * @param mixed $data Raw data
     * @param string $timeKey Key for timestamp
     * @param string $valueKey Key for value
     * @return array Transformed time series data
     */
    public function toTimeSeries($data, string $timeKey = 'date', string $valueKey = 'value'): array
    {
        // Convert to array if Collection
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        // Handle empty data
        if (empty($data)) {
            return [];
        }

        $series = [];

        foreach ($data as $item) {
            if (is_array($item) || is_object($item)) {
                $item = (array) $item;
                $timestamp = $item[$timeKey] ?? null;
                $value = $item[$valueKey] ?? 0;

                // Convert timestamp to milliseconds if needed
                if ($timestamp) {
                    if (is_string($timestamp)) {
                        $timestamp = strtotime($timestamp) * 1000;
                    } elseif ($timestamp instanceof \DateTime) {
                        $timestamp = $timestamp->getTimestamp() * 1000;
                    }

                    $series[] = [$timestamp, $value];
                }
            }
        }

        return $series;
    }

    /**
     * Aggregate data by grouping.
     *
     * Groups data by a key and aggregates values.
     *
     * @param mixed $data Raw data
     * @param string $groupKey Key to group by
     * @param string $valueKey Key for values
     * @param string $aggregation Aggregation method (sum, avg, count, min, max)
     * @return array Aggregated data
     */
    public function aggregate($data, string $groupKey, string $valueKey, string $aggregation = 'sum'): array
    {
        // Convert to array if Collection
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        // Handle empty data
        if (empty($data)) {
            return [];
        }

        $grouped = [];

        // Group data
        foreach ($data as $item) {
            if (is_array($item) || is_object($item)) {
                $item = (array) $item;
                $group = $item[$groupKey] ?? 'Unknown';
                $value = $item[$valueKey] ?? 0;

                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }

                $grouped[$group][] = $value;
            }
        }

        // Aggregate values
        $result = [];
        foreach ($grouped as $group => $values) {
            $result[$group] = $this->applyAggregation($values, $aggregation);
        }

        return $result;
    }

    /**
     * Apply aggregation function to values.
     *
     * @param array $values Values to aggregate
     * @param string $aggregation Aggregation method
     * @return float|int Aggregated value
     */
    protected function applyAggregation(array $values, string $aggregation)
    {
        return match ($aggregation) {
            'sum' => array_sum($values),
            'avg', 'average' => count($values) > 0 ? array_sum($values) / count($values) : 0,
            'count' => count($values),
            'min' => count($values) > 0 ? min($values) : 0,
            'max' => count($values) > 0 ? max($values) : 0,
            default => array_sum($values),
        };
    }

    /**
     * Check if data is in series format.
     *
     * @param array $data Data to check
     * @return bool
     */
    protected function isSeriesFormat(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $first = reset($data);

        return is_array($first) && isset($first['name']) && isset($first['data']);
    }

    /**
     * Check if data is key-value pairs.
     *
     * @param array $data Data to check
     * @return bool
     */
    protected function isKeyValuePairs(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Check if all keys are strings and values are scalars
        foreach ($data as $key => $value) {
            if (!is_string($key) || (is_array($value) && !is_numeric(key($value)))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data is grouped data.
     *
     * @param array $data Data to check
     * @return bool
     */
    protected function isGroupedData(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $first = reset($data);

        return is_array($first) && !isset($first['name']) && !isset($first['data']);
    }

    /**
     * Check if data is simple array.
     *
     * @param array $data Data to check
     * @return bool
     */
    protected function isSimpleArray(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $value) {
            if (is_array($value) || is_object($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data is associative array.
     *
     * @param array $data Data to check
     * @return bool
     */
    protected function isAssociativeArray(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $first = reset($data);

        return is_array($first) || is_object($first);
    }

    /**
     * Transform key-value pairs to series.
     *
     * @param array $data Key-value pairs
     * @param string|null $nameKey Series name
     * @param string|null $dataKey Data key
     * @return array Series data
     */
    protected function transformKeyValuePairs(array $data, ?string $nameKey = null, ?string $dataKey = null): array
    {
        return [
            [
                'name' => $nameKey ?? 'Series 1',
                'data' => array_values($data),
            ],
        ];
    }

    /**
     * Transform grouped data to series.
     *
     * @param array $data Grouped data
     * @param string|null $nameKey Name key
     * @param string|null $dataKey Data key
     * @return array Series data
     */
    protected function transformGroupedData(array $data, ?string $nameKey = null, ?string $dataKey = null): array
    {
        $series = [];

        foreach ($data as $key => $values) {
            if (is_array($values)) {
                $series[] = [
                    'name' => $nameKey ? ($values[$nameKey] ?? $key) : $key,
                    'data' => $dataKey ? ($values[$dataKey] ?? []) : array_values($values),
                ];
            }
        }

        return $series;
    }

    /**
     * Sanitize data for chart rendering.
     *
     * Removes null values, converts to proper types, etc.
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        if ($data === null) {
            return 0;
        }

        if (is_numeric($data)) {
            return (float) $data;
        }

        return $data;
    }

    /**
     * Validate data structure.
     *
     * @param mixed $data Data to validate
     * @return bool
     */
    public function validate($data): bool
    {
        if ($data === null || $data === []) {
            return false;
        }

        if (!is_array($data) && !($data instanceof Collection)) {
            return false;
        }

        return true;
    }
}
