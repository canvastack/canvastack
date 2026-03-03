<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Blade;

/**
 * Blade Directives for Translation.
 *
 * Provides custom Blade directives for translation functionality.
 */
class BladeDirectives
{
    /**
     * Register all translation Blade directives.
     *
     * @return void
     */
    public static function register(): void
    {
        static::registerTransDirective();
        static::registerTransChoiceDirective();
        static::registerTransFallbackDirective();
        static::registerTransContextDirective();
        static::registerTransComponentDirective();
        static::registerTransUiDirective();
        static::registerTransValidationDirective();
        static::registerTransErrorDirective();
        static::registerLocaleDirective();
        static::registerRtlDirective();
    }

    /**
     * Register @trans directive
     * Usage: @trans('key', ['param' => 'value']).
     *
     * @return void
     */
    protected static function registerTransDirective(): void
    {
        Blade::directive('trans', function ($expression) {
            return "<?php echo __({$expression}); ?>";
        });
    }

    /**
     * Register @transChoice directive
     * Usage: @transChoice('key', $count, ['param' => 'value']).
     *
     * @return void
     */
    protected static function registerTransChoiceDirective(): void
    {
        Blade::directive('transChoice', function ($expression) {
            return "<?php echo trans_choice({$expression}); ?>";
        });
    }

    /**
     * Register @transFallback directive
     * Usage: @transFallback('key', 'default value', ['param' => 'value']).
     *
     * @return void
     */
    protected static function registerTransFallbackDirective(): void
    {
        Blade::directive('transFallback', function ($expression) {
            return "<?php echo trans_fallback({$expression}); ?>";
        });
    }

    /**
     * Register @transContext directive
     * Usage: @transContext('key', 'admin', ['param' => 'value']).
     *
     * @return void
     */
    protected static function registerTransContextDirective(): void
    {
        Blade::directive('transContext', function ($expression) {
            return "<?php echo trans_with_context({$expression}); ?>";
        });
    }

    /**
     * Register @transComponent directive
     * Usage: @transComponent('form', 'submit_button').
     *
     * @return void
     */
    protected static function registerTransComponentDirective(): void
    {
        Blade::directive('transComponent', function ($expression) {
            return "<?php echo trans_component({$expression}); ?>";
        });
    }

    /**
     * Register @transUi directive
     * Usage: @transUi('buttons.save').
     *
     * @return void
     */
    protected static function registerTransUiDirective(): void
    {
        Blade::directive('transUi', function ($expression) {
            return "<?php echo trans_ui({$expression}); ?>";
        });
    }

    /**
     * Register @transValidation directive
     * Usage: @transValidation('required', ['attribute' => 'email']).
     *
     * @return void
     */
    protected static function registerTransValidationDirective(): void
    {
        Blade::directive('transValidation', function ($expression) {
            return "<?php echo trans_validation({$expression}); ?>";
        });
    }

    /**
     * Register @transError directive
     * Usage: @transError('not_found').
     *
     * @return void
     */
    protected static function registerTransErrorDirective(): void
    {
        Blade::directive('transError', function ($expression) {
            return "<?php echo trans_error({$expression}); ?>";
        });
    }

    /**
     * Register @locale directive
     * Usage: @locale('en') ... @endlocale.
     *
     * @return void
     */
    protected static function registerLocaleDirective(): void
    {
        Blade::directive('locale', function ($expression) {
            return "<?php app()->setLocale({$expression}); ?>";
        });

        Blade::directive('endlocale', function () {
            return "<?php app()->setLocale(config('app.locale')); ?>";
        });
    }

    /**
     * Register @rtl directive
     * Usage: @rtl ... @endrtl.
     *
     * @return void
     */
    protected static function registerRtlDirective(): void
    {
        Blade::if('rtl', function ($locale = null) {
            return is_rtl($locale);
        });

        Blade::if('ltr', function ($locale = null) {
            return !is_rtl($locale);
        });
    }
}
