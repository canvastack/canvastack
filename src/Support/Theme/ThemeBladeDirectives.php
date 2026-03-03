<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Illuminate\Support\Facades\Blade;

/**
 * Theme Blade Directives.
 *
 * Registers custom Blade directives for theme functionality.
 */
class ThemeBladeDirectives
{
    /**
     * Register all theme-related Blade directives.
     *
     * @return void
     */
    public static function register(): void
    {
        static::registerThemeDirective();
        static::registerThemeScriptDirective();
        static::registerThemeStyleDirective();
        static::registerThemeColorDirective();
        static::registerThemeGradientDirective();
    }

    /**
     * Register @theme directive.
     *
     * Usage: @theme('gradient.primary')
     *
     * @return void
     */
    protected static function registerThemeDirective(): void
    {
        Blade::directive('theme', function ($expression) {
            return "<?php echo app('canvastack.theme')->config({$expression}); ?>";
        });
    }

    /**
     * Register @themeScript directive.
     *
     * Injects theme initialization JavaScript.
     *
     * @return void
     */
    protected static function registerThemeScriptDirective(): void
    {
        Blade::directive('themeScript', function () {
            return <<<'PHP'
<?php
    $themeManager = app('canvastack.theme');
    $currentTheme = $themeManager->current();
    $allThemes = $themeManager->all();
    
    $themesData = [];
    foreach ($allThemes as $theme) {
        $themesData[$theme->getName()] = [
            'name' => $theme->getName(),
            'display_name' => $theme->getDisplayName(),
            'displayName' => $theme->getDisplayName(),
            'colors' => $theme->getColors(),
            'fonts' => $theme->getFonts(),
            'gradient' => $theme->get('gradient', []),
            'layout' => $theme->getLayout(),
        ];
    }
    
    echo '<script>';
    echo 'window.CanvastackTheme = window.CanvastackTheme || {};';
    echo 'window.CanvastackTheme.init({';
    echo '  defaultTheme: ' . json_encode($currentTheme->getName()) . ',';
    echo '  themes: ' . json_encode($themesData) . ',';
    echo '  config: {';
    echo '    storageKey: "canvastack_theme",';
    echo '    apiEndpoint: "' . url('/api/user/preferences/theme') . '",';
    echo '    enablePersistence: true,';
    echo '    enableDatabaseSync: ' . (auth()->check() ? 'true' : 'false') . ',';
    echo '  }';
    echo '});';
    echo '</script>';
?>
PHP;
        });
    }

    /**
     * Register @themeStyle directive.
     *
     * Injects theme CSS variables.
     *
     * @return void
     */
    protected static function registerThemeStyleDirective(): void
    {
        Blade::directive('themeStyle', function () {
            return "<?php echo app('canvastack.theme')->injectComplete(); ?>";
        });
    }

    /**
     * Register @themeColor directive.
     *
     * Usage: @themeColor('primary.500')
     *
     * @return void
     */
    protected static function registerThemeColorDirective(): void
    {
        Blade::directive('themeColor', function ($expression) {
            return <<<PHP
<?php
    \$colorPath = {$expression};
    \$colors = app('canvastack.theme')->colors();
    echo data_get(\$colors, \$colorPath, '#6366f1');
?>
PHP;
        });
    }

    /**
     * Register @themeGradient directive.
     *
     * Usage: @themeGradient('primary')
     *
     * @return void
     */
    protected static function registerThemeGradientDirective(): void
    {
        Blade::directive('themeGradient', function ($expression) {
            return <<<PHP
<?php
    \$gradientName = {$expression};
    echo app('canvastack.theme')->config("gradient.{\$gradientName}", 'linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)');
?>
PHP;
        });
    }
}
