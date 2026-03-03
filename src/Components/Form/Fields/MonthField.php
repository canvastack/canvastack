<?php

namespace Canvastack\Canvastack\Components\Form\Fields;

/**
 * MonthField Component.
 *
 * Represents a month picker field for selecting year and month.
 */
class MonthField extends BaseField
{
    protected string $format = 'Y-m';

    protected ?string $minMonth = null;

    protected ?string $maxMonth = null;

    protected bool $multipleSelection = false;

    /**
     * Set month format.
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set minimum month.
     */
    public function minMonth(string $month): self
    {
        $this->minMonth = $month;

        return $this;
    }

    /**
     * Set maximum month.
     */
    public function maxMonth(string $month): self
    {
        $this->maxMonth = $month;

        return $this;
    }

    /**
     * Enable multiple month selection.
     */
    public function multiple(bool $enable = true): self
    {
        $this->multipleSelection = $enable;

        return $this;
    }

    /**
     * Get month picker configuration.
     */
    public function getMonthConfig(): array
    {
        return [
            'format' => $this->format,
            'minMonth' => $this->minMonth,
            'maxMonth' => $this->maxMonth,
            'multiple' => $this->multipleSelection,
        ];
    }

    /**
     * Get field type.
     */
    public function getType(): string
    {
        return 'month';
    }

    /**
     * Render the month field.
     */
    public function render(): string
    {
        $name = $this->getName();
        $label = $this->getLabel();
        $value = $this->getValue();
        $attributes = $this->getAttributesString();
        $config = $this->getMonthConfig();

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
        $html .= ' class="month-input form-input w-full" ';
        $html .= ' data-month-config=\'' . json_encode($config) . '\' ';
        $html .= '/>';

        $html .= '</div>';

        return $html;
    }
}
