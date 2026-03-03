<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\View\Components\Table;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Display Limit Component
 * 
 * Provides a dropdown UI for changing the number of rows displayed in a table.
 * Supports session persistence and DataTable integration.
 */
class DisplayLimit extends Component
{
    /**
     * Available display limit options
     */
    public const DEFAULT_OPTIONS = [
        ['value' => '10', 'label' => '10'],
        ['value' => '25', 'label' => '25'],
        ['value' => '50', 'label' => '50'],
        ['value' => '100', 'label' => '100'],
        ['value' => 'all', 'label' => 'All'],
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $tableName = 'default',
        public int|string $currentLimit = 10,
        public array $options = self::DEFAULT_OPTIONS,
        public bool $showLabel = true,
        public string $size = 'sm'
    ) {
        // Validate current limit
        $this->currentLimit = $this->validateLimit($currentLimit);
        
        // Ensure options are properly formatted
        $this->options = $this->formatOptions($options);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('canvastack::components.table.display-limit');
    }

    /**
     * Validate the display limit value
     */
    protected function validateLimit(int|string $limit): int|string
    {
        if ($limit === 'all' || $limit === '*') {
            return 'all';
        }

        if (is_numeric($limit) && (int) $limit > 0) {
            return (int) $limit;
        }

        // Default fallback
        return 10;
    }

    /**
     * Format options array to ensure consistent structure
     */
    protected function formatOptions(array $options): array
    {
        return array_map(function ($option) {
            if (is_array($option) && isset($option['value'], $option['label'])) {
                return $option;
            }

            if (is_string($option) || is_numeric($option)) {
                $value = (string) $option;
                $label = $value === 'all' ? 'All' : $value;
                return ['value' => $value, 'label' => $label];
            }

            // Invalid option, skip
            return null;
        }, $options);
    }

    /**
     * Get the current limit from session or default
     */
    public function getCurrentLimit(): int|string
    {
        $sessionKey = "table_display_limit_{$this->tableName}";
        $sessionLimit = session($sessionKey);

        if ($sessionLimit !== null) {
            return $this->validateLimit($sessionLimit);
        }

        return $this->currentLimit;
    }

    /**
     * Check if the given limit is the current limit
     */
    public function isCurrentLimit(int|string $limit): bool
    {
        return $this->getCurrentLimit() == $limit;
    }

    /**
     * Get CSS classes for the select element
     */
    public function getSelectClasses(): string
    {
        $baseClasses = 'select select-bordered min-w-0';
        
        $sizeClasses = match ($this->size) {
            'xs' => 'select-xs w-16',
            'sm' => 'select-sm w-20',
            'md' => 'select-md w-24',
            'lg' => 'select-lg w-28',
            default => 'select-sm w-20',
        };

        return "{$baseClasses} {$sizeClasses}";
    }

    /**
     * Get the table name for JavaScript
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get options for JavaScript
     */
    public function getOptionsForJs(): array
    {
        return array_filter($this->options);
    }

    /**
     * Get current limit for JavaScript
     */
    public function getCurrentLimitForJs(): int|string
    {
        return $this->getCurrentLimit();
    }
}