<?php

namespace Canvastack\Canvastack\Components\Form\Features\ViewMode;

use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Canvastack\Canvastack\Components\Form\Fields\CheckboxField;
use Canvastack\Canvastack\Components\Form\Fields\DateField;
use Canvastack\Canvastack\Components\Form\Fields\DateTimeField;
use Canvastack\Canvastack\Components\Form\Fields\FileField;
use Canvastack\Canvastack\Components\Form\Fields\RadioField;
use Canvastack\Canvastack\Components\Form\Fields\SelectField;

/**
 * ViewModeRenderer.
 *
 * Renders form fields as read-only display for view mode.
 * Formats values appropriately based on field type.
 */
class ViewModeRenderer
{
    protected ValueFormatter $formatter;

    protected string $context;

    public function __construct(string $context = 'admin')
    {
        $this->formatter = new ValueFormatter();
        $this->context = $context;
    }

    /**
     * Render a field in view mode.
     */
    public function render(BaseField $field): string
    {
        $label = $field->getLabel() ?? ucfirst($field->getName());
        $value = $this->formatValue($field);

        $labelClasses = $this->getLabelClasses();
        $valueClasses = $this->getValueClasses();
        $containerClasses = $this->getContainerClasses();

        return <<<HTML
        <div class="{$containerClasses}">
            <div class="{$labelClasses}">{$label}</div>
            <div class="{$valueClasses}">{$value}</div>
        </div>
        HTML;
    }

    /**
     * Format field value based on field type.
     */
    protected function formatValue(BaseField $field): string
    {
        $value = $field->getValue();

        if ($value === null || $value === '') {
            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        // Handle different field types
        if ($field instanceof SelectField) {
            return $this->formatSelectValue($field);
        }

        if ($field instanceof CheckboxField) {
            return $this->formatCheckboxValue($field);
        }

        if ($field instanceof RadioField) {
            return $this->formatRadioValue($field);
        }

        if ($field instanceof FileField) {
            return $this->formatFileValue($field);
        }

        if ($field instanceof DateField || $field instanceof DateTimeField) {
            return $this->formatter->formatDate($value);
        }

        // Default: escape and return
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format select field value (display label instead of value).
     */
    protected function formatSelectValue(SelectField $field): string
    {
        $value = $field->getValue();
        $options = $field->getOptions();

        if (isset($options[$value])) {
            return htmlspecialchars($options[$value], ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format checkbox field value (display as badges).
     */
    protected function formatCheckboxValue(CheckboxField $field): string
    {
        $checked = $field->getChecked();
        $options = $field->getOptions();

        if (empty($checked)) {
            return '<span class="text-gray-400 dark:text-gray-500">None selected</span>';
        }

        $badges = [];
        foreach ((array) $checked as $value) {
            $label = $options[$value] ?? $value;
            $badges[] = $this->renderBadge($label);
        }

        return implode(' ', $badges);
    }

    /**
     * Format radio field value.
     */
    protected function formatRadioValue(RadioField $field): string
    {
        $checked = $field->getChecked();
        $options = $field->getOptions();

        if (isset($options[$checked])) {
            return htmlspecialchars($options[$checked], ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars((string) $checked, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format file field value (display preview or download link).
     */
    protected function formatFileValue(FileField $field): string
    {
        $value = $field->getValue();

        if (empty($value)) {
            return '<span class="text-gray-400 dark:text-gray-500">No file</span>';
        }

        // Check if it's an image
        $extension = pathinfo($value, PATHINFO_EXTENSION);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (in_array(strtolower($extension), $imageExtensions)) {
            return <<<HTML
            <img src="{$value}" alt="File preview" class="max-w-xs rounded-lg shadow-sm">
            HTML;
        }

        // Otherwise, show download link
        $filename = basename($value);

        return <<<HTML
        <a href="{$value}" download class="text-primary hover:underline flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {$filename}
        </a>
        HTML;
    }

    /**
     * Render a badge for checkbox values.
     */
    protected function renderBadge(string $label): string
    {
        return <<<HTML
        <span class="badge badge-primary">{$label}</span>
        HTML;
    }

    /**
     * Get container CSS classes.
     */
    protected function getContainerClasses(): string
    {
        return 'mb-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0';
    }

    /**
     * Get label CSS classes.
     */
    protected function getLabelClasses(): string
    {
        return 'text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1';
    }

    /**
     * Get value CSS classes.
     */
    protected function getValueClasses(): string
    {
        return 'text-base text-gray-900 dark:text-gray-100';
    }
}
