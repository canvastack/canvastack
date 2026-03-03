<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

/**
 * LocaleManager.
 *
 * Manages application locale, including detection, persistence, and switching.
 */
class LocaleManager
{
    /**
     * Available locales.
     *
     * @var array<string, array<string, string>>
     */
    protected array $availableLocales = [];

    /**
     * Default locale.
     *
     * @var string
     */
    protected string $defaultLocale;

    /**
     * Fallback locale.
     *
     * @var string
     */
    protected string $fallbackLocale;

    /**
     * Current locale.
     *
     * @var string
     */
    protected string $currentLocale;

    /**
     * Storage driver for locale persistence.
     *
     * @var string
     */
    protected string $storageDriver;

    /**
     * Cookie name for locale storage.
     *
     * @var string
     */
    protected string $cookieName = 'canvastack_locale';

    /**
     * Session key for locale storage.
     *
     * @var string
     */
    protected string $sessionKey = 'canvastack_locale';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->loadConfiguration();
        $this->detectLocale();
    }

    /**
     * Load configuration.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $this->availableLocales = Config::get('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
        ]);

        $this->defaultLocale = Config::get('canvastack.localization.default_locale', 'en');
        $this->fallbackLocale = Config::get('canvastack.localization.fallback_locale', 'en');
        $this->storageDriver = Config::get('canvastack.localization.storage', 'session');
    }

    /**
     * Detect locale from various sources.
     *
     * Priority: URL > Session > Cookie > Browser > Default
     *
     * @return void
     */
    protected function detectLocale(): void
    {
        // Try to get from URL parameter
        $locale = request()->input('locale');

        // Try to get from session
        if (!$locale && $this->storageDriver === 'session') {
            $locale = Session::get($this->sessionKey);
        }

        // Try to get from cookie
        if (!$locale && $this->storageDriver === 'cookie') {
            $locale = Cookie::get($this->cookieName);
        }

        // Try to get from browser
        if (!$locale && Config::get('canvastack.localization.detect_browser', true)) {
            $locale = $this->detectBrowserLocale();
        }

        // Use default locale
        if (!$locale || !$this->isAvailable($locale)) {
            $locale = $this->defaultLocale;
        }

        $this->setLocale($locale);
    }

    /**
     * Detect locale from browser Accept-Language header.
     *
     * @return string|null
     */
    protected function detectBrowserLocale(): ?string
    {
        $acceptLanguage = request()->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', $lang);
            $code = trim($parts[0]);
            $priority = isset($parts[1]) ? (float) $parts[1] : 1.0;
            $languages[$code] = $priority;
        }

        // Sort by priority
        arsort($languages);

        // Find first available locale
        foreach (array_keys($languages) as $code) {
            // Try exact match
            if ($this->isAvailable($code)) {
                return $code;
            }

            // Try language code only (e.g., 'en' from 'en-US')
            $langCode = substr($code, 0, 2);
            if ($this->isAvailable($langCode)) {
                return $langCode;
            }
        }

        return null;
    }

    /**
     * Set current locale.
     *
     * @param  string  $locale
     * @return bool
     */
    public function setLocale(string $locale): bool
    {
        if (!$this->isAvailable($locale)) {
            return false;
        }

        $previousLocale = $this->currentLocale ?? null;
        $this->currentLocale = $locale;
        App::setLocale($locale);

        // Persist locale
        $this->persistLocale($locale);

        // Fire locale changed event
        event(new \Canvastack\Canvastack\Events\Translation\LocaleChanged($locale, $previousLocale));

        return true;
    }

    /**
     * Persist locale to storage.
     *
     * @param  string  $locale
     * @return void
     */
    protected function persistLocale(string $locale): void
    {
        if ($this->storageDriver === 'session') {
            Session::put($this->sessionKey, $locale);
        } elseif ($this->storageDriver === 'cookie') {
            Cookie::queue($this->cookieName, $locale, 525600); // 1 year
        } elseif ($this->storageDriver === 'both') {
            Session::put($this->sessionKey, $locale);
            Cookie::queue($this->cookieName, $locale, 525600);
        }
    }

    /**
     * Get current locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->currentLocale ?? $this->defaultLocale;
    }

    /**
     * Get default locale.
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Get fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    /**
     * Get all available locales.
     *
     * @return array<string, array<string, string>>
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * Check if locale is available.
     *
     * @param  string  $locale
     * @return bool
     */
    public function isAvailable(string $locale): bool
    {
        return isset($this->availableLocales[$locale]);
    }

    /**
     * Get locale information.
     *
     * @param  string|null  $locale
     * @return array<string, string>|null
     */
    public function getLocaleInfo(?string $locale = null): ?array
    {
        $locale = $locale ?? $this->getLocale();

        return $this->availableLocales[$locale] ?? null;
    }

    /**
     * Get locale name.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getLocaleName(?string $locale = null): ?string
    {
        $info = $this->getLocaleInfo($locale);

        return $info['name'] ?? null;
    }

    /**
     * Get locale native name.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getLocaleNativeName(?string $locale = null): ?string
    {
        $info = $this->getLocaleInfo($locale);

        return $info['native'] ?? null;
    }

    /**
     * Get locale flag emoji.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getLocaleFlag(?string $locale = null): ?string
    {
        $info = $this->getLocaleInfo($locale);

        return $info['flag'] ?? null;
    }

    /**
     * Check if current locale is RTL.
     *
     * @param  string|null  $locale
     * @return bool
     */
    public function isRtl(?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();
        $rtlLocales = Config::get('canvastack.localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);

        return in_array($locale, $rtlLocales);
    }

    /**
     * Get text direction for current locale.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getDirection(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Clear locale cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags(['localization', 'translations'])->flush();
    }
}
