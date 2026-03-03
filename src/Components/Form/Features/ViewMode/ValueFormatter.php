<?php

namespace Canvastack\Canvastack\Components\Form\Features\ViewMode;

/**
 * ValueFormatter.
 *
 * Formats field values for display in view mode.
 * Handles dates, numbers, booleans, and other data types.
 */
class ValueFormatter
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Format date value.
     */
    public function formatDate(string $value, ?string $format = null): string
    {
        $format = $format ?? $this->config['date_format'];

        try {
            $date = new \DateTime($value);

            return $date->format($format);
        } catch (\Exception $e) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Format datetime value.
     */
    public function formatDateTime(string $value, ?string $format = null): string
    {
        $format = $format ?? $this->config['datetime_format'];

        try {
            $date = new \DateTime($value);

            return $date->format($format);
        } catch (\Exception $e) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Format boolean value.
     */
    public function formatBoolean($value): string
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        if ($value) {
            return '<span class="badge badge-success">Yes</span>';
        }

        return '<span class="badge badge-error">No</span>';
    }

    /**
     * Format number value.
     */
    public function formatNumber($value, int $decimals = 0): string
    {
        if (!is_numeric($value)) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        return number_format((float) $value, $decimals);
    }

    /**
     * Format currency value.
     */
    public function formatCurrency($value, string $currency = 'USD'): string
    {
        if (!is_numeric($value)) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'IDR' => 'Rp',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        $formatted = number_format((float) $value, 2);

        return "{$symbol}{$formatted}";
    }

    /**
     * Format array value.
     */
    public function formatArray(array $value): string
    {
        if (empty($value)) {
            return '<span class="text-gray-400 dark:text-gray-500">Empty</span>';
        }

        $items = array_map(function ($item) {
            return '<span class="badge badge-primary">' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</span>';
        }, $value);

        return implode(' ', $items);
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'date_format' => 'Y-m-d',
            'datetime_format' => 'Y-m-d H:i:s',
            'time_format' => 'H:i:s',
        ];
    }
}
