<?php

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * DateRangeField Component.
 *
 * Represents a date range picker field for selecting start and end dates.
 */
class DateRangeField extends BaseField
{
    protected string $format = 'Y-m-d';

    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected array $predefinedRanges = [];

    protected bool $enableTime = false;

    /**
     * Set date format.
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set minimum date.
     */
    public function minDate(string $date): self
    {
        $this->minDate = $date;

        return $this;
    }

    /**
     * Set maximum date.
     */
    public function maxDate(string $date): self
    {
        $this->maxDate = $date;

        return $this;
    }

    /**
     * Add predefined date ranges.
     */
    public function predefinedRanges(array $ranges): self
    {
        $this->predefinedRanges = $ranges;

        return $this;
    }

    /**
     * Enable time selection.
     */
    public function enableTime(bool $enable = true): self
    {
        $this->enableTime = $enable;

        return $this;
    }

    /**
     * Get date range configuration.
     */
    public function getDateRangeConfig(): array
    {
        return [
            'format' => $this->format,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'predefinedRanges' => $this->predefinedRanges,
            'enableTime' => $this->enableTime,
        ];
    }

    /**
     * Get field type.
     */
    public function getType(): string
    {
        return 'daterange';
    }

    /**
     * Render the date range field.
     */
    public function render(): string
    {
        $name = $this->getName();
        $label = $this->getLabel();
        $value = $this->getValue();
        $attributes = $this->getAttributesString();
        $config = $this->getDateRangeConfig();

        $html = '<div class="form-group mb-4">';

        if ($label) {
            $html .= '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">';
            $html .= htmlspecialchars($label);
            $html .= '</label>';
        }

        $html .= '<input type="text" ';
        $html .= 'name="' . htmlspecialchars($name) . '" ';
        $html .= 'id="' . htmlspecialchars($name) . '" ';
        $html .= 'value="' . htmlspecialchars($value ?? '') . '" ';
        $html .= $attributes;
        $html .= ' class="daterange-input form-input w-full" ';
        $html .= ' data-daterange-config=\'' . json_encode($config) . '\' ';
        $html .= '/>';

        $html .= '</div>';

        return $html;
    }
}
