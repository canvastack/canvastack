<?php

declare(strict_types=1);

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Illuminate\Support\Facades\App;

if (!function_exists('translate_model')) {
    /**
     * Translate a model attribute.
     *
     * @param  mixed  $model
     * @param  string  $attribute
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|null
     */
    function translate_model($model, string $attribute, ?string $locale = null, bool $fallback = true): ?string
    {
        if (!$model || !method_exists($model, 'getTranslation')) {
            return null;
        }

        return $model->getTranslation($attribute, $locale, $fallback);
    }
}

if (!function_exists('set_model_translation')) {
    /**
     * Set translation for a model attribute.
     *
     * @param  mixed  $model
     * @param  string  $attribute
     * @param  string  $value
     * @param  string|null  $locale
     * @return bool
     */
    function set_model_translation($model, string $attribute, string $value, ?string $locale = null): bool
    {
        if (!$model || !method_exists($model, 'setTranslation')) {
            return false;
        }

        return $model->setTranslation($attribute, $value, $locale);
    }
}

if (!function_exists('locale_manager')) {
    /**
     * Get the LocaleManager instance.
     *
     * @return LocaleManager
     */
    function locale_manager(): LocaleManager
    {
        return App::make(LocaleManager::class);
    }
}

if (!function_exists('current_locale')) {
    /**
     * Get the current locale.
     *
     * @return string
     */
    function current_locale(): string
    {
        return App::getLocale();
    }
}

if (!function_exists('available_locales')) {
    /**
     * Get all available locales.
     *
     * @return array<string, array<string, string>>
     */
    function available_locales(): array
    {
        return locale_manager()->getAvailableLocales();
    }
}

if (!function_exists('is_rtl')) {
    /**
     * Check if current locale is RTL.
     *
     * @param  string|null  $locale
     * @return bool
     */
    function is_rtl(?string $locale = null): bool
    {
        return locale_manager()->isRtl($locale);
    }
}

if (!function_exists('text_direction')) {
    /**
     * Get text direction for current locale.
     *
     * @param  string|null  $locale
     * @return string
     */
    function text_direction(?string $locale = null): string
    {
        return locale_manager()->getDirection($locale);
    }
}

if (!function_exists('translate_collection')) {
    /**
     * Translate a collection of models.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  array<string>  $attributes
     * @param  string|null  $locale
     * @return \Illuminate\Support\Collection
     */
    function translate_collection($collection, array $attributes, ?string $locale = null)
    {
        return $collection->map(function ($item) use ($attributes, $locale) {
            if (!method_exists($item, 'getTranslation')) {
                return $item;
            }

            foreach ($attributes as $attribute) {
                $item->$attribute = $item->getTranslation($attribute, $locale);
            }

            return $item;
        });
    }
}

if (!function_exists('translate_array')) {
    /**
     * Translate an array of data using a translation key prefix.
     *
     * @param  array<string, mixed>  $data
     * @param  string  $prefix
     * @param  string|null  $locale
     * @return array<string, mixed>
     */
    function translate_array(array $data, string $prefix, ?string $locale = null): array
    {
        $locale = $locale ?? App::getLocale();
        $translated = [];

        foreach ($data as $key => $value) {
            $translationKey = "{$prefix}.{$key}";
            $translated[$key] = __($translationKey, [], $locale);

            // If translation not found, use original value
            if ($translated[$key] === $translationKey) {
                $translated[$key] = $value;
            }
        }

        return $translated;
    }
}

if (!function_exists('has_translation')) {
    /**
     * Check if a model has translation for an attribute.
     *
     * @param  mixed  $model
     * @param  string  $attribute
     * @param  string|null  $locale
     * @return bool
     */
    function has_translation($model, string $attribute, ?string $locale = null): bool
    {
        if (!$model || !method_exists($model, 'hasTranslation')) {
            return false;
        }

        return $model->hasTranslation($attribute, $locale);
    }
}

if (!function_exists('get_translations')) {
    /**
     * Get all translations for a model attribute.
     *
     * @param  mixed  $model
     * @param  string  $attribute
     * @return array<string, string>
     */
    function get_translations($model, string $attribute): array
    {
        if (!$model || !method_exists($model, 'getTranslations')) {
            return [];
        }

        return $model->getTranslations($attribute);
    }
}

if (!function_exists('trans_choice_with_count')) {
    /**
     * Translate a message with pluralization and include the count.
     *
     * @param  string  $key
     * @param  int|array<string, mixed>  $count
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_choice_with_count(string $key, $count, array $replace = [], ?string $locale = null): string
    {
        $number = is_array($count) ? ($count['count'] ?? 0) : $count;
        $replace = array_merge(['count' => $number], $replace);

        return trans_choice($key, $number, $replace, $locale);
    }
}

if (!function_exists('trans_fallback')) {
    /**
     * Translate with fallback to default value.
     *
     * @param  string  $key
     * @param  string  $default
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_fallback(string $key, string $default, array $replace = [], ?string $locale = null): string
    {
        $translation = __($key, $replace, $locale);

        // If translation key is returned unchanged, use default
        return $translation === $key ? $default : $translation;
    }
}

if (!function_exists('trans_if_exists')) {
    /**
     * Translate only if translation exists, otherwise return null.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string|null
     */
    function trans_if_exists(string $key, array $replace = [], ?string $locale = null): ?string
    {
        $translation = __($key, $replace, $locale);

        // If translation key is returned unchanged, return null
        return $translation === $key ? null : $translation;
    }
}

if (!function_exists('trans_with_context')) {
    /**
     * Translate with context (admin/public).
     *
     * @param  string  $key
     * @param  string  $context
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_with_context(string $key, string $context, array $replace = [], ?string $locale = null): string
    {
        $contextKey = "{$context}.{$key}";
        $translation = __($contextKey, $replace, $locale);

        // If context-specific translation not found, try without context
        if ($translation === $contextKey) {
            return __($key, $replace, $locale);
        }

        return $translation;
    }
}

if (!function_exists('trans_cached')) {
    /**
     * Get cached translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_cached(string $key, array $replace = [], ?string $locale = null): string
    {
        $cache = App::make('canvastack.translation.cache');
        $locale = $locale ?? App::getLocale();

        return $cache->get($key, $locale, function () use ($key, $replace, $locale) {
            return __($key, $replace, $locale);
        });
    }
}

if (!function_exists('trans_component')) {
    /**
     * Translate component-specific key.
     *
     * @param  string  $component
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_component(string $component, string $key, array $replace = [], ?string $locale = null): string
    {
        return __("canvastack::components.{$component}.{$key}", $replace, $locale);
    }
}

if (!function_exists('trans_ui')) {
    /**
     * Translate UI element.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_ui(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("canvastack::ui.{$key}", $replace, $locale);
    }
}

if (!function_exists('trans_validation')) {
    /**
     * Translate validation message.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_validation(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("canvastack::validation.{$key}", $replace, $locale);
    }
}

if (!function_exists('trans_error')) {
    /**
     * Translate error message.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_error(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("canvastack::errors.{$key}", $replace, $locale);
    }
}
