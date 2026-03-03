<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Lang;

/**
 * Pluralizer.
 *
 * Handles pluralization for different locales with support for complex plural rules.
 */
class Pluralizer
{
    /**
     * Locale manager instance.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Plural rules for different locales.
     *
     * @var array<string, callable>
     */
    protected array $pluralRules = [];

    /**
     * Constructor.
     *
     * @param  LocaleManager  $localeManager
     */
    public function __construct(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;
        $this->registerDefaultRules();
    }

    /**
     * Register default plural rules for common locales.
     *
     * @return void
     */
    protected function registerDefaultRules(): void
    {
        // English: one, other
        $this->pluralRules['en'] = function (int $count): string {
            return $count === 1 ? 'one' : 'other';
        };

        // Indonesian: other (no plural distinction)
        $this->pluralRules['id'] = function (int $count): string {
            return 'other';
        };

        // Arabic: zero, one, two, few, many, other
        $this->pluralRules['ar'] = function (int $count): string {
            if ($count === 0) {
                return 'zero';
            }
            if ($count === 1) {
                return 'one';
            }
            if ($count === 2) {
                return 'two';
            }
            if ($count % 100 >= 3 && $count % 100 <= 10) {
                return 'few';
            }
            if ($count % 100 >= 11 && $count % 100 <= 99) {
                return 'many';
            }

            return 'other';
        };

        // Russian: one, few, many, other
        $this->pluralRules['ru'] = function (int $count): string {
            $mod10 = $count % 10;
            $mod100 = $count % 100;

            if ($mod10 === 1 && $mod100 !== 11) {
                return 'one';
            }
            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
                return 'few';
            }

            return 'many';
        };

        // French: one, other
        $this->pluralRules['fr'] = function (int $count): string {
            return $count <= 1 ? 'one' : 'other';
        };

        // Polish: one, few, many, other
        $this->pluralRules['pl'] = function (int $count): string {
            if ($count === 1) {
                return 'one';
            }
            $mod10 = $count % 10;
            $mod100 = $count % 100;
            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
                return 'few';
            }

            return 'many';
        };
    }

    /**
     * Get plural form for a count.
     *
     * @param  string  $key
     * @param  int|array<string, mixed>  $count
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function choice(string $key, int|array $count, array $replace = [], ?string $locale = null): string
    {
        // Handle array format (Laravel's trans_choice compatibility)
        if (is_array($count)) {
            $replace = $count;
            $count = $replace['count'] ?? 1;
        }

        $locale = $locale ?? $this->localeManager->getLocale();

        // Get translation string
        $line = Lang::get($key, $replace, $locale);

        // If translation not found or no plural forms, return as is
        if ($line === $key || !str_contains($line, '|')) {
            return $this->replacePlaceholders($line, array_merge($replace, ['count' => $count]));
        }

        // Parse plural forms
        $segments = $this->parseSegments($line);

        // Get appropriate segment based on count
        $segment = $this->getSegment($segments, $count, $locale);

        // Replace placeholders
        return $this->replacePlaceholders($segment, array_merge($replace, ['count' => $count]));
    }

    /**
     * Parse plural segments from translation string.
     *
     * Format: {0} No items|{1} One item|[2,*] :count items
     * Or simple: No items|One item|Many items
     *
     * @param  string  $line
     * @return array<string, string>
     */
    protected function parseSegments(string $line): array
    {
        $segments = [];
        $parts = explode('|', $line);

        foreach ($parts as $part) {
            $part = trim($part);

            // Check for explicit count: {0}, {1}, [2,5], [6,*]
            if (preg_match('/^\{(\d+)\}\s*(.+)$/', $part, $matches)) {
                // Exact count: {0} No items
                $segments[$matches[1]] = trim($matches[2]);
            } elseif (preg_match('/^\[(\d+),(\d+|\*)\]\s*(.+)$/', $part, $matches)) {
                // Range: [2,5] Some items or [6,*] Many items
                $start = (int) $matches[1];
                $end = $matches[2] === '*' ? INF : (int) $matches[2];
                $segments["{$start},{$end}"] = trim($matches[3]);
            } else {
                // Simple form without explicit count
                $segments[] = $part;
            }
        }

        return $segments;
    }

    /**
     * Get appropriate segment for count.
     *
     * @param  array<string, string>  $segments
     * @param  int  $count
     * @param  string  $locale
     * @return string
     */
    protected function getSegment(array $segments, int $count, string $locale): string
    {
        // Check for exact count match
        if (isset($segments[(string) $count])) {
            return $segments[(string) $count];
        }

        // Check for range match
        foreach ($segments as $key => $value) {
            if (str_contains($key, ',')) {
                [$start, $end] = explode(',', $key);
                $start = (int) $start;
                $end = $end === 'INF' ? INF : (int) $end;

                if ($count >= $start && $count <= $end) {
                    return $value;
                }
            }
        }

        // Use plural rules for locale
        $pluralForm = $this->getPluralForm($count, $locale);

        // Map plural form to segment index
        $index = $this->getPluralIndex($pluralForm, $locale);

        // Get segment by index
        $values = array_values(array_filter($segments, fn ($key) => is_int($key), ARRAY_FILTER_USE_KEY));

        return $values[$index] ?? $values[count($values) - 1] ?? '';
    }

    /**
     * Get plural form for count and locale.
     *
     * @param  int  $count
     * @param  string  $locale
     * @return string
     */
    protected function getPluralForm(int $count, string $locale): string
    {
        $rule = $this->pluralRules[$locale] ?? $this->pluralRules['en'];

        return $rule($count);
    }

    /**
     * Get plural index for form.
     *
     * @param  string  $form
     * @param  string  $locale
     * @return int
     */
    protected function getPluralIndex(string $form, string $locale): int
    {
        $forms = $this->getPluralForms($locale);

        $index = array_search($form, $forms);

        return $index !== false ? $index : 0;
    }

    /**
     * Get plural forms for locale.
     *
     * @param  string  $locale
     * @return array<string>
     */
    protected function getPluralForms(string $locale): array
    {
        $forms = [
            'en' => ['one', 'other'],
            'id' => ['other'],
            'ar' => ['zero', 'one', 'two', 'few', 'many', 'other'],
            'ru' => ['one', 'few', 'many'],
            'fr' => ['one', 'other'],
            'pl' => ['one', 'few', 'many'],
        ];

        return $forms[$locale] ?? ['one', 'other'];
    }

    /**
     * Replace placeholders in string.
     *
     * @param  string  $value
     * @param  array<string, mixed>  $replace
     * @return string
     */
    protected function replacePlaceholders(string $value, array $replace): string
    {
        foreach ($replace as $key => $val) {
            $value = str_replace([':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)], $val, $value);
        }

        return $value;
    }

    /**
     * Register custom plural rule for locale.
     *
     * @param  string  $locale
     * @param  callable  $rule
     * @return void
     */
    public function registerRule(string $locale, callable $rule): void
    {
        $this->pluralRules[$locale] = $rule;
    }

    /**
     * Check if locale has plural rule.
     *
     * @param  string  $locale
     * @return bool
     */
    public function hasRule(string $locale): bool
    {
        return isset($this->pluralRules[$locale]);
    }
}
