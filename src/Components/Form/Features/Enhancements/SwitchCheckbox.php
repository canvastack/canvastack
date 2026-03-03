<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

/**
 * SwitchCheckbox - Render checkboxes as toggle switches using DaisyUI.
 */
class SwitchCheckbox
{
    /**
     * Render switch checkbox.
     *
     * @param string $name Field name
     * @param array $options Options array (value => label)
     * @param mixed $checked Checked values (array or single value)
     * @param array $attributes Additional attributes (size, color, disabled, context, aria-label, aria-describedby)
     * @return string HTML output
     */
    public function render(
        string $name,
        array $options,
        $checked,
        array $attributes = []
    ): string {
        $size = $attributes['size'] ?? 'md';
        $color = $attributes['color'] ?? 'primary';
        $disabled = $attributes['disabled'] ?? false;
        $context = $attributes['context'] ?? 'admin';
        $ariaLabel = $attributes['aria-label'] ?? null;
        $ariaDescribedBy = $attributes['aria-describedby'] ?? null;

        $sizeClass = $this->getSizeClass($size);
        $colorClass = $this->getColorClass($color, $context);
        $disabledAttr = $disabled ? 'disabled' : '';
        $darkModeClass = $this->getDarkModeClass($context);

        // Build ARIA attributes
        $ariaAttrs = $this->buildAriaAttributes($ariaLabel, $ariaDescribedBy);

        $html = '';
        foreach ($options as $value => $label) {
            $isChecked = $this->isChecked($value, $checked);
            $checkedAttr = $isChecked ? 'checked' : '';
            $ariaChecked = $isChecked ? 'true' : 'false';
            $id = "{$name}_{$value}";

            $html .= <<<HTML
            <div class="form-control">
                <label class="label cursor-pointer">
                    <span class="label-text {$darkModeClass}">{$label}</span>
                    <input 
                        type="checkbox" 
                        name="{$name}[]" 
                        id="{$id}"
                        value="{$value}"
                        class="toggle {$sizeClass} {$colorClass} transition-all duration-200 ease-in-out"
                        role="switch"
                        aria-checked="{$ariaChecked}"
                        {$ariaAttrs}
                        {$checkedAttr}
                        {$disabledAttr}
                        tabindex="0"
                        x-on:keydown.space.prevent="\$el.click()"
                        x-on:change="\$dispatch('switch-changed', { name: '{$name}', value: '{$value}', checked: \$el.checked })"
                    />
                </label>
            </div>
            HTML;
        }

        return $html;
    }

    /**
     * Get size class for DaisyUI toggle.
     *
     * @param string $size Size variant (sm, md, lg)
     * @return string DaisyUI size class
     */
    protected function getSizeClass(string $size): string
    {
        return match ($size) {
            'sm' => 'toggle-sm',
            'lg' => 'toggle-lg',
            default => '',
        };
    }

    /**
     * Get color class for DaisyUI toggle.
     *
     * @param string $color Color variant
     * @param string $context Rendering context (admin or public)
     * @return string DaisyUI color class
     */
    protected function getColorClass(string $color, string $context): string
    {
        // Admin context uses standard DaisyUI colors
        if ($context === 'admin') {
            return match ($color) {
                'primary' => 'toggle-primary',
                'secondary' => 'toggle-secondary',
                'accent' => 'toggle-accent',
                'success' => 'toggle-success',
                'warning' => 'toggle-warning',
                'error' => 'toggle-error',
                default => 'toggle-primary',
            };
        }

        // Public context uses softer colors
        return match ($color) {
            'primary' => 'toggle-info',
            'secondary' => 'toggle-secondary',
            'accent' => 'toggle-accent',
            'success' => 'toggle-success',
            'warning' => 'toggle-warning',
            'error' => 'toggle-error',
            default => 'toggle-info',
        };
    }

    /**
     * Get dark mode class for label text.
     *
     * @param string $context Rendering context
     * @return string Dark mode class
     */
    protected function getDarkModeClass(string $context): string
    {
        return 'dark:text-gray-200';
    }

    /**
     * Build ARIA attributes string.
     *
     * @param string|null $ariaLabel ARIA label
     * @param string|null $ariaDescribedBy ARIA described by
     * @return string ARIA attributes HTML
     */
    protected function buildAriaAttributes(?string $ariaLabel, ?string $ariaDescribedBy): string
    {
        $attrs = [];

        if ($ariaLabel) {
            $attrs[] = 'aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES) . '"';
        }

        if ($ariaDescribedBy) {
            $attrs[] = 'aria-describedby="' . htmlspecialchars($ariaDescribedBy, ENT_QUOTES) . '"';
        }

        return implode(' ', $attrs);
    }

    /**
     * Check if value is checked.
     *
     * @param mixed $value Value to check
     * @param mixed $checked Checked values (array or single value)
     * @return bool True if checked
     */
    protected function isChecked($value, $checked): bool
    {
        // If checked is null, nothing is checked
        if ($checked === null) {
            return false;
        }

        if (is_array($checked)) {
            return in_array($value, $checked);
        }

        return $value == $checked;
    }
}
