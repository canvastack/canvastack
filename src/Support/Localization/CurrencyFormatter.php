<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Config;

/**
 * CurrencyFormatter.
 *
 * Handles currency formatting for different locales.
 */
class CurrencyFormatter
{
    /**
     * Locale manager instance.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Number formatter instance.
     *
     * @var NumberFormatter
     */
    protected NumberFormatter $numberFormatter;

    /**
     * Constructor.
     *
     * @param  LocaleManager  $localeManager
     * @param  NumberFormatter  $numberFormatter
     */
    public function __construct(LocaleManager $localeManager, NumberFormatter $numberFormatter)
    {
        $this->localeManager = $localeManager;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * Format currency according to locale.
     *
     * @param  float|int|null  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function format(float|int|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        if ($amount === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        // Get currency config
        $currencyConfig = $this->getCurrencyConfig($currency, $locale);

        // Get number format config
        $numberConfig = Config::get("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);

        // Format number
        $formatted = number_format(
            $amount,
            $currencyConfig['decimals'] ?? $numberConfig['decimals'],
            $numberConfig['decimal_separator'],
            $numberConfig['thousands_separator']
        );

        // Add currency symbol
        return $this->addSymbol($formatted, $currencyConfig);
    }

    /**
     * Format currency with code (e.g., "USD 1,234.56").
     *
     * @param  float|int|null  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function formatWithCode(float|int|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        if ($amount === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        // Get currency config
        $currencyConfig = $this->getCurrencyConfig($currency, $locale);

        // Get number format config
        $numberConfig = Config::get("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);

        // Format number
        $formatted = number_format(
            $amount,
            $currencyConfig['decimals'] ?? $numberConfig['decimals'],
            $numberConfig['decimal_separator'],
            $numberConfig['thousands_separator']
        );

        // Add currency code
        $space = $currencyConfig['space'] ?? false ? ' ' : '';

        if (($currencyConfig['position'] ?? 'before') === 'before') {
            return $currency . $space . $formatted;
        }

        return $formatted . $space . $currency;
    }

    /**
     * Format currency without symbol (just the number).
     *
     * @param  float|int|null  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function formatWithoutSymbol(float|int|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        if ($amount === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        // Get currency config
        $currencyConfig = $this->getCurrencyConfig($currency, $locale);

        // Get number format config
        $numberConfig = Config::get("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);

        // Format number
        return number_format(
            $amount,
            $currencyConfig['decimals'] ?? $numberConfig['decimals'],
            $numberConfig['decimal_separator'],
            $numberConfig['thousands_separator']
        );
    }

    /**
     * Format currency in accounting format (negative in parentheses).
     *
     * @param  float|int|null  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function formatAccounting(float|int|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        if ($amount === null) {
            return '';
        }

        $isNegative = $amount < 0;
        $absAmount = abs($amount);

        $formatted = $this->format($absAmount, $currency, $locale);

        if ($isNegative) {
            return "({$formatted})";
        }

        return $formatted;
    }

    /**
     * Parse currency string to float.
     *
     * @param  string  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return float
     */
    public function parse(string $amount, ?string $currency = null, ?string $locale = null): float
    {
        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        // Get currency config
        $currencyConfig = $this->getCurrencyConfig($currency, $locale);

        // Remove currency symbol
        $amount = str_replace($currencyConfig['symbol'], '', $amount);

        // Remove spaces
        $amount = trim($amount);

        // Use number formatter to parse
        return $this->numberFormatter->parse($amount, $locale);
    }

    /**
     * Convert currency amount between currencies.
     *
     * @param  float|int  $amount
     * @param  string  $fromCurrency
     * @param  string  $toCurrency
     * @param  array<string, float>  $rates
     * @param  string|null  $locale
     * @return string
     */
    public function convert(
        float|int $amount,
        string $fromCurrency,
        string $toCurrency,
        array $rates,
        ?string $locale = null
    ): string {
        // Get exchange rates
        $fromRate = $rates[$fromCurrency] ?? 1;
        $toRate = $rates[$toCurrency] ?? 1;

        // Convert to base currency then to target currency
        $baseAmount = $amount / $fromRate;
        $convertedAmount = $baseAmount * $toRate;

        return $this->format($convertedAmount, $toCurrency, $locale);
    }

    /**
     * Format currency range.
     *
     * @param  float|int|null  $min
     * @param  float|int|null  $max
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function formatRange(
        float|int|null $min,
        float|int|null $max,
        ?string $currency = null,
        ?string $locale = null
    ): string {
        if ($min === null && $max === null) {
            return '';
        }

        if ($min === null) {
            return 'Up to ' . $this->format($max, $currency, $locale);
        }

        if ($max === null) {
            return 'From ' . $this->format($min, $currency, $locale);
        }

        return $this->format($min, $currency, $locale) . ' - ' . $this->format($max, $currency, $locale);
    }

    /**
     * Get currency symbol.
     *
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function getSymbol(?string $currency = null, ?string $locale = null): string
    {
        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        $config = $this->getCurrencyConfig($currency, $locale);

        return $config['symbol'] ?? $currency;
    }

    /**
     * Get currency name.
     *
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public function getName(?string $currency = null, ?string $locale = null): string
    {
        $locale = $locale ?? $this->localeManager->getLocale();
        $currency = $currency ?? $this->getDefaultCurrency($locale);

        $config = $this->getCurrencyConfig($currency, $locale);

        return $config['name'] ?? $currency;
    }

    /**
     * Get available currencies.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableCurrencies(): array
    {
        return Config::get('canvastack.localization.currencies', []);
    }

    /**
     * Add currency symbol to formatted number.
     *
     * @param  string  $formatted
     * @param  array<string, mixed>  $config
     * @return string
     */
    protected function addSymbol(string $formatted, array $config): string
    {
        $symbol = $config['symbol'] ?? '$';
        $position = $config['position'] ?? 'before';
        $space = $config['space'] ?? false ? ' ' : '';

        if ($position === 'before') {
            return $symbol . $space . $formatted;
        }

        return $formatted . $space . $symbol;
    }

    /**
     * Get currency configuration.
     *
     * @param  string  $currency
     * @param  string  $locale
     * @return array<string, mixed>
     */
    protected function getCurrencyConfig(string $currency, string $locale): array
    {
        // Try to get currency-specific config for locale
        $config = Config::get("canvastack.localization.currencies.{$currency}.{$locale}");

        if ($config) {
            return $config;
        }

        // Try to get default currency config
        $config = Config::get("canvastack.localization.currencies.{$currency}.default");

        if ($config) {
            return $config;
        }

        // Fallback to locale's currency format
        return Config::get("canvastack.localization.currency_format.{$locale}", [
            'symbol' => $currency,
            'position' => 'before',
            'space' => false,
            'decimals' => 2,
        ]);
    }

    /**
     * Get default currency for locale.
     *
     * @param  string  $locale
     * @return string
     */
    protected function getDefaultCurrency(string $locale): string
    {
        return Config::get("canvastack.localization.default_currency.{$locale}", 'USD');
    }
}
