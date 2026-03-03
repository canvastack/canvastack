<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Filter;

/**
 * Filter - Represents a single filter configuration.
 *
 * Handles filter type, options, value, and cascading relationships.
 */
class Filter
{
    /**
     * Column name to filter.
     *
     * @var string
     */
    protected string $column;

    /**
     * Filter type (selectbox, inputbox, datebox).
     *
     * @var string
     */
    protected string $type;

    /**
     * Related filters for cascading.
     *
     * @var bool|string|array
     */
    protected $relate;

    /**
     * Whether to enable bi-directional cascade.
     *
     * @var bool
     */
    protected bool $bidirectional = false;

    /**
     * Filter options (for selectbox).
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Current filter value.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Filter label.
     *
     * @var string|null
     */
    protected ?string $label = null;

    /**
     * Whether to auto-submit on change.
     *
     * @var bool
     */
    protected bool $autoSubmit = false;

    /**
     * Whether filter is currently loading options.
     *
     * @var bool
     */
    protected bool $loading = false;

    /**
     * Error message if option loading failed.
     *
     * @var string|null
     */
    protected ?string $error = null;

    /**
     * Constructor.
     *
     * @param string $column Column name
     * @param string $type Filter type
     * @param bool|string|array $relate Related filters
     */
    public function __construct(string $column, string $type, $relate = false)
    {
        $this->column = $column;
        $this->type = $type;
        $this->relate = $relate;

        // Generate default label from column name
        $this->label = ucwords(str_replace('_', ' ', $column));
    }

    /**
     * Get column name.
     *
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * Get filter type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get related filters.
     *
     * @return bool|string|array
     */
    public function getRelate()
    {
        return $this->relate;
    }

    /**
     * Set bidirectional cascade.
     *
     * @param bool $bidirectional Whether to enable bi-directional cascade
     * @return void
     */
    public function setBidirectional(bool $bidirectional): void
    {
        $this->bidirectional = $bidirectional;
    }

    /**
     * Check if bidirectional cascade is enabled.
     *
     * @return bool
     */
    public function isBidirectional(): bool
    {
        return $this->bidirectional;
    }

    /**
     * Set filter options.
     *
     * @param array $options Filter options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Get filter options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set filter value.
     *
     * @param mixed $value Filter value
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Get filter value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set filter label.
     *
     * @param string $label Filter label
     * @return void
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get filter label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? ucwords(str_replace('_', ' ', $this->column));
    }

    /**
     * Set auto-submit behavior.
     *
     * @param bool $autoSubmit Whether to auto-submit
     * @return void
     */
    public function setAutoSubmit(bool $autoSubmit): void
    {
        $this->autoSubmit = $autoSubmit;
    }

    /**
     * Check if filter should auto-submit.
     *
     * @return bool
     */
    public function shouldAutoSubmit(): bool
    {
        return $this->autoSubmit;
    }

    /**
     * Get related filter columns.
     *
     * Returns an array of column names that should be updated when this filter changes.
     *
     * @return array
     */
    public function getRelatedFilters(): array
    {
        if ($this->relate === false) {
            return [];
        }

        if ($this->relate === true) {
            // True means cascade to all filters after this one
            // This will be handled by FilterManager
            return [];
        }

        if (is_string($this->relate)) {
            return [$this->relate];
        }

        if (is_array($this->relate)) {
            return $this->relate;
        }

        return [];
    }

    /**
     * Check if filter has cascading relationships.
     *
     * @return bool
     */
    public function hasCascading(): bool
    {
        return $this->relate !== false;
    }

    /**
     * Check if filter cascades to all subsequent filters.
     *
     * @return bool
     */
    public function cascadesToAll(): bool
    {
        return $this->relate === true;
    }

    /**
     * Check if filter has a value.
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return $this->value !== null && $this->value !== '';
    }

    /**
     * Set loading state.
     *
     * @param bool $loading Whether filter is loading
     * @return void
     */
    public function setLoading(bool $loading): void
    {
        $this->loading = $loading;
    }

    /**
     * Check if filter is loading.
     *
     * @return bool
     */
    public function isLoading(): bool
    {
        return $this->loading;
    }

    /**
     * Set error message.
     *
     * @param string|null $error Error message
     * @return void
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    /**
     * Get error message.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Check if filter has an error.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Clear error message.
     *
     * @return void
     */
    public function clearError(): void
    {
        $this->error = null;
    }

    /**
     * Convert filter to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'type' => $this->type,
            'label' => $this->getLabel(),
            'value' => $this->value,
            'options' => $this->options,
            'relate' => $this->relate,
            'bidirectional' => $this->bidirectional,
            'autoSubmit' => $this->autoSubmit,
            'loading' => $this->loading,
            'error' => $this->error,
        ];
    }
}
