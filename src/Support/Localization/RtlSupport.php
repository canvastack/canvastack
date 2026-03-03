<?php

namespace Canvastack\Canvastack\Support\Localization;

/**
 * RtlSupport.
 *
 * Provides utilities for Right-to-Left (RTL) language support.
 */
class RtlSupport
{
    /**
     * Locale manager instance.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * RTL locales.
     *
     * @var array<string>
     */
    protected array $rtlLocales = ['ar', 'he', 'fa', 'ur', 'yi', 'ji'];

    /**
     * Constructor.
     *
     * @param  LocaleManager  $localeManager
     */
    public function __construct(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;
    }

    /**
     * Check if current locale is RTL.
     *
     * @param  string|null  $locale
     * @return bool
     */
    public function isRtl(?string $locale = null): bool
    {
        return $this->localeManager->isRtl($locale);
    }

    /**
     * Get text direction for current locale.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getDirection(?string $locale = null): string
    {
        return $this->localeManager->getDirection($locale);
    }

    /**
     * Get opposite direction.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getOppositeDirection(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'ltr' : 'rtl';
    }

    /**
     * Get start position (left for LTR, right for RTL).
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getStart(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'right' : 'left';
    }

    /**
     * Get end position (right for LTR, left for RTL).
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getEnd(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'left' : 'right';
    }

    /**
     * Get float direction (left for LTR, right for RTL).
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getFloat(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'right' : 'left';
    }

    /**
     * Get opposite float direction.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getOppositeFloat(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'left' : 'right';
    }

    /**
     * Get text align direction.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getTextAlign(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'right' : 'left';
    }

    /**
     * Get margin/padding start property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getMarginStart(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'margin-right' : 'margin-left';
    }

    /**
     * Get margin/padding end property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getMarginEnd(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'margin-left' : 'margin-right';
    }

    /**
     * Get padding start property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getPaddingStart(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'padding-right' : 'padding-left';
    }

    /**
     * Get padding end property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getPaddingEnd(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'padding-left' : 'padding-right';
    }

    /**
     * Get border start property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getBorderStart(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'border-right' : 'border-left';
    }

    /**
     * Get border end property.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getBorderEnd(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'border-left' : 'border-right';
    }

    /**
     * Get transform scale for horizontal flip.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getFlipTransform(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'scaleX(-1)' : 'scaleX(1)';
    }

    /**
     * Get rotation angle for RTL (180 degrees).
     *
     * @param  string|null  $locale
     * @return int
     */
    public function getRotationAngle(?string $locale = null): int
    {
        return $this->isRtl($locale) ? 180 : 0;
    }

    /**
     * Convert CSS property to RTL-aware property.
     *
     * @param  string  $property
     * @param  string|null  $locale
     * @return string
     */
    public function convertCssProperty(string $property, ?string $locale = null): string
    {
        if (!$this->isRtl($locale)) {
            return $property;
        }

        $conversions = [
            'left' => 'right',
            'right' => 'left',
            'margin-left' => 'margin-right',
            'margin-right' => 'margin-left',
            'padding-left' => 'padding-right',
            'padding-right' => 'padding-left',
            'border-left' => 'border-right',
            'border-right' => 'border-left',
            'border-top-left-radius' => 'border-top-right-radius',
            'border-top-right-radius' => 'border-top-left-radius',
            'border-bottom-left-radius' => 'border-bottom-right-radius',
            'border-bottom-right-radius' => 'border-bottom-left-radius',
        ];

        return $conversions[$property] ?? $property;
    }

    /**
     * Get HTML dir attribute value.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getDirAttribute(?string $locale = null): string
    {
        return $this->getDirection($locale);
    }

    /**
     * Get HTML lang attribute value.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getLangAttribute(?string $locale = null): string
    {
        return $locale ?? $this->localeManager->getLocale();
    }

    /**
     * Get CSS class for RTL.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getRtlClass(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Get Tailwind direction classes.
     *
     * @param  string|null  $locale
     * @return array<string>
     */
    public function getTailwindClasses(?string $locale = null): array
    {
        if ($this->isRtl($locale)) {
            return ['rtl', 'text-right', 'dir-rtl'];
        }

        return ['ltr', 'text-left', 'dir-ltr'];
    }

    /**
     * Flip icon direction for RTL.
     *
     * @param  string  $iconClass
     * @param  string|null  $locale
     * @return string
     */
    public function flipIcon(string $iconClass, ?string $locale = null): string
    {
        if (!$this->isRtl($locale)) {
            return $iconClass;
        }

        // Icons that should be flipped in RTL
        $flipIcons = [
            'arrow-left',
            'arrow-right',
            'chevron-left',
            'chevron-right',
            'angle-left',
            'angle-right',
            'caret-left',
            'caret-right',
        ];

        foreach ($flipIcons as $icon) {
            if (str_contains($iconClass, $icon)) {
                return $iconClass . ' flip-rtl';
            }
        }

        return $iconClass;
    }

    /**
     * Get logical property value (start/end instead of left/right).
     *
     * @param  string  $property
     * @param  string  $value
     * @param  string|null  $locale
     * @return array<string, string>
     */
    public function getLogicalProperty(string $property, string $value, ?string $locale = null): array
    {
        $isRtl = $this->isRtl($locale);

        $logicalProperties = [
            'margin-start' => $isRtl ? 'margin-right' : 'margin-left',
            'margin-end' => $isRtl ? 'margin-left' : 'margin-right',
            'padding-start' => $isRtl ? 'padding-right' : 'padding-left',
            'padding-end' => $isRtl ? 'padding-left' : 'padding-right',
            'border-start' => $isRtl ? 'border-right' : 'border-left',
            'border-end' => $isRtl ? 'border-left' : 'border-right',
            'inset-start' => $isRtl ? 'right' : 'left',
            'inset-end' => $isRtl ? 'left' : 'right',
        ];

        $physicalProperty = $logicalProperties[$property] ?? $property;

        return [$physicalProperty => $value];
    }
}
