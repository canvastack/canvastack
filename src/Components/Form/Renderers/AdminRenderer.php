<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox;
use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Canvastack\Canvastack\Components\Form\Fields\CheckboxField;
use Canvastack\Canvastack\Components\Form\Fields\FileField;
use Canvastack\Canvastack\Components\Form\Fields\HiddenField;
use Canvastack\Canvastack\Components\Form\Fields\RadioField;
use Canvastack\Canvastack\Components\Form\Fields\SelectField;
use Canvastack\Canvastack\Components\Form\Fields\TextareaField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;

/**
 * AdminRenderer - Renders form fields for admin panel.
 *
 * Uses Tailwind CSS + DaisyUI styling with dark mode support.
 */
class AdminRenderer implements RendererInterface
{
    use TabRenderingTrait;

    /**
     * Render a complete form field with label, input, help text, and errors.
     */
    public function render(BaseField $field): string
    {
        // Hidden fields don't need wrapper
        if ($field instanceof HiddenField) {
            return $this->renderInput($field);
        }

        $html = '<div class="mb-5">';
        $html .= $this->renderLabel($field);
        $html .= $this->renderInput($field);
        $html .= $this->renderCharacterCounter($field);
        $html .= $this->renderHelpText($field);
        $html .= $this->renderErrors($field);
        $html .= '</div>';

        return $html;
    }

    /**
     * Set validation errors for ARIA attributes.
     */
    protected array $validationErrors = [];

    public function setValidationErrors(array $errors): void
    {
        $this->validationErrors = $errors;
    }

    /**
     * Get validation errors.
     */
    protected function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Render field label.
     */
    public function renderLabel(BaseField $field): string
    {
        $label = $field->getLabel();
        if (!$label) {
            return '';
        }

        $required = $field->isRequired() ? '<span class="text-red-500 ml-1">*</span>' : '';

        return sprintf(
            '<label for="%s" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">%s%s</label>',
            htmlspecialchars($field->getName()),
            htmlspecialchars($label),
            $required
        );
    }

    /**
     * Render field input based on type.
     */
    public function renderInput(BaseField $field): string
    {
        if ($field instanceof SelectField) {
            return $this->renderSelect($field);
        }

        if ($field instanceof CheckboxField) {
            return $this->renderCheckbox($field);
        }

        if ($field instanceof RadioField) {
            return $this->renderRadio($field);
        }

        if ($field instanceof TextareaField) {
            return $this->renderTextarea($field);
        }

        if ($field instanceof FileField) {
            return $this->renderFile($field);
        }

        if ($field instanceof HiddenField) {
            return $this->renderHidden($field);
        }

        // Default text-based input
        return $this->renderTextInput($field);
    }

    /**
     * Render text-based input (text, email, password, number, date, etc.).
     */
    protected function renderTextInput(BaseField $field): string
    {
        $attributes = $this->buildAttributes($field, $this->getValidationErrors());
        $icon = $field->getIcon();
        $iconPosition = $field->getIconPosition();

        if ($icon) {
            $html = '<div class="relative">';

            if ($iconPosition === 'left') {
                $html .= sprintf(
                    '<i data-lucide="%s" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>',
                    htmlspecialchars($icon)
                );
                $attributes['class'] = ($attributes['class'] ?? '') . ' pl-10';
            } else {
                $html .= sprintf(
                    '<i data-lucide="%s" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>',
                    htmlspecialchars($icon)
                );
                $attributes['class'] = ($attributes['class'] ?? '') . ' pr-10';
            }
        } else {
            $html = '';
        }

        $baseClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition';
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $baseClasses);

        $html .= sprintf(
            '<input type="%s" name="%s" id="%s" value="%s" %s>',
            htmlspecialchars($field->getType()),
            htmlspecialchars($field->getName()),
            htmlspecialchars($field->getName()),
            htmlspecialchars((string) $field->getValue()),
            $this->attributesToString($attributes)
        );

        if ($icon) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render textarea field.
     */
    protected function renderTextarea(TextareaField $field): string
    {
        $attributes = $this->buildAttributes($field, $this->getValidationErrors());
        $baseClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition resize-y';
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $baseClasses);
        $attributes['rows'] = $field->getRows();

        return sprintf(
            '<textarea name="%s" id="%s" %s>%s</textarea>',
            htmlspecialchars($field->getName()),
            htmlspecialchars($field->getName()),
            $this->attributesToString($attributes),
            htmlspecialchars((string) $field->getValue())
        );
    }

    /**
     * Render select field.
     */
    protected function renderSelect(SelectField $field): string
    {
        $attributes = $this->buildAttributes($field, $this->getValidationErrors());
        $baseClasses = 'w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition';
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $baseClasses);

        $selected = $field->getSelected();
        $options = '';

        foreach ($field->getOptions() as $value => $label) {
            $isSelected = $this->isSelected($value, $selected);
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars((string) $value),
                $isSelected ? ' selected' : '',
                htmlspecialchars((string) $label)
            );
        }

        return sprintf(
            '<select name="%s" id="%s" %s>%s</select>',
            htmlspecialchars($field->getName()),
            htmlspecialchars($field->getName()),
            $this->attributesToString($attributes),
            $options
        );
    }

    /**
     * Render checkbox field.
     */
    protected function renderCheckbox(CheckboxField $field): string
    {
        // Check if this should be rendered as a switch
        if ($field->getCheckType() === 'switch') {
            $switchCheckbox = new SwitchCheckbox();

            // If no options provided, create a single checkbox with field label
            $options = $field->getOptions();
            if (empty($options)) {
                $options = [1 => $field->getLabel() ?? ''];
            }

            // Pass ARIA attributes from field
            $attributes = $field->getAttributes();
            $attributes['context'] = $this->getContext();

            // Add ARIA label if not present
            if (!isset($attributes['aria-label']) && $field->getLabel()) {
                $attributes['aria-label'] = $field->getLabel();
            }

            // Add aria-describedby if field has errors
            $errors = $this->getValidationErrors();
            if (isset($errors[$field->getName()])) {
                $attributes['aria-describedby'] = $field->getName() . '-error';
            }

            return $switchCheckbox->render(
                $field->getName(),
                $options,
                $field->getChecked(),
                $attributes
            );
        }

        // Standard checkbox rendering
        $checked = $field->getChecked();
        $options = $field->getOptions();

        // If no options provided, create a single checkbox
        if (empty($options)) {
            $options = [1 => $field->getLabel() ?? ''];
        }

        $inline = $field->isInline();

        $html = '<div class="' . ($inline ? 'flex flex-wrap gap-4' : 'space-y-2') . '">';

        foreach ($options as $value => $label) {
            $isChecked = $this->isChecked($value, $checked);
            $id = $field->getName() . '_' . $value;

            $html .= '<label class="flex items-center cursor-pointer">';
            $html .= sprintf(
                '<input type="checkbox" name="%s[]" id="%s" value="%s" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"%s>',
                htmlspecialchars($field->getName()),
                htmlspecialchars($id),
                htmlspecialchars((string) $value),
                $isChecked ? ' checked' : ''
            );
            $html .= sprintf(
                '<span class="ml-2 text-sm text-gray-700 dark:text-gray-300">%s</span>',
                htmlspecialchars((string) $label)
            );
            $html .= '</label>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get current rendering context.
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * Render radio field.
     */
    protected function renderRadio(RadioField $field): string
    {
        $checked = $field->getChecked();
        $options = $field->getOptions();
        $inline = $field->isInline();

        $html = '<div class="' . ($inline ? 'flex flex-wrap gap-4' : 'space-y-2') . '">';

        foreach ($options as $value => $label) {
            $isChecked = $value == $checked;
            $id = $field->getName() . '_' . $value;

            $html .= '<label class="flex items-center cursor-pointer">';
            $html .= sprintf(
                '<input type="radio" name="%s" id="%s" value="%s" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"%s>',
                htmlspecialchars($field->getName()),
                htmlspecialchars($id),
                htmlspecialchars((string) $value),
                $isChecked ? ' checked' : ''
            );
            $html .= sprintf(
                '<span class="ml-2 text-sm text-gray-700 dark:text-gray-300">%s</span>',
                htmlspecialchars((string) $label)
            );
            $html .= '</label>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render file upload field.
     */
    protected function renderFile(FileField $field): string
    {
        $attributes = $this->buildAttributes($field, $this->getValidationErrors());
        $baseClasses = 'block w-full text-sm text-gray-900 border border-gray-300 rounded-xl cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400';
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $baseClasses);

        $fileInput = sprintf(
            '<input type="file" name="%s" id="%s" %s>',
            htmlspecialchars($field->getName()),
            htmlspecialchars($field->getName()),
            $this->attributesToString($attributes)
        );

        // Add image preview if enabled
        if ($field->hasPreview()) {
            $imagePreview = new \Canvastack\Canvastack\Components\Form\Features\FileUpload\ImagePreview();
            $currentImage = $field->getValue();
            $fileInput .= $imagePreview->render($field->getName(), $currentImage);
        }

        return $fileInput;
    }

    /**
     * Render hidden field.
     */
    protected function renderHidden(HiddenField $field): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($field->getName()),
            htmlspecialchars((string) $field->getValue())
        );
    }

    /**
     * Render character counter if field has maxLength.
     */
    protected function renderCharacterCounter(BaseField $field): string
    {
        // Only render for TextField and TextareaField with maxLength
        if (!($field instanceof TextField || $field instanceof TextareaField)) {
            return '';
        }

        $maxLength = $field->getMaxLength();
        if (!$maxLength) {
            return '';
        }

        $counter = new CharacterCounter();

        return $counter->render($field->getName(), $maxLength, 'admin');
    }

    /**
     * Render help text.
     */
    public function renderHelpText(BaseField $field): string
    {
        $helpText = $field->getHelpText();
        if (!$helpText) {
            return '';
        }

        return sprintf(
            '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">%s</p>',
            htmlspecialchars($helpText)
        );
    }

    /**
     * Render validation errors.
     */
    public function renderErrors(BaseField $field): string
    {
        $errors = $this->getValidationErrors();

        if (!isset($errors[$field->getName()])) {
            return '';
        }

        $errorMessages = is_array($errors[$field->getName()])
            ? $errors[$field->getName()]
            : [$errors[$field->getName()]];

        $html = sprintf(
            '<div id="%s-error" class="mt-1 text-sm text-red-600 dark:text-red-400">',
            htmlspecialchars($field->getName())
        );

        foreach ($errorMessages as $message) {
            $html .= sprintf('<p>%s</p>', htmlspecialchars($message));
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build attributes array from field.
     */
    protected function buildAttributes(BaseField $field, array $validationErrors = []): array
    {
        $attributes = $field->getAttributes();

        if ($field->isRequired()) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }

        if ($field->getPlaceholder()) {
            $attributes['placeholder'] = $field->getPlaceholder();
        }

        // Add ARIA label if not already present
        if (!isset($attributes['aria-label']) && !isset($attributes['aria-labelledby'])) {
            if ($field->getLabel()) {
                $attributes['aria-label'] = $field->getLabel();
            }
        }

        // Add aria-invalid and aria-describedby if field has validation errors
        if (isset($validationErrors[$field->getName()])) {
            $attributes['aria-invalid'] = 'true';
            $attributes['aria-describedby'] = $field->getName() . '-error';
        }

        return $attributes;
    }

    /**
     * Convert attributes array to HTML string.
     */
    protected function attributesToString(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = htmlspecialchars($key);
                }
            } elseif ($value !== null) {
                $html[] = sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars((string) $value));
            }
        }

        return implode(' ', $html);
    }

    /**
     * Check if value is selected.
     */
    protected function isSelected($value, $selected): bool
    {
        if (is_array($selected)) {
            return in_array($value, $selected);
        }

        return $value == $selected;
    }

    /**
     * Check if value is checked.
     */
    protected function isChecked($value, $checked): bool
    {
        if (is_array($checked)) {
            return in_array($value, $checked);
        }

        return $value == $checked;
    }
}
