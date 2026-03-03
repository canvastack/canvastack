<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\View;

use Illuminate\Support\Facades\Blade;

/**
 * Blade Directives for CanvaStack.
 *
 * Registers custom Blade directives for theme management and other features.
 */
class BladeDirectives
{
    /**
     * Register all Blade directives.
     *
     * @return void
     */
    public static function register(): void
    {
        static::registerThemeDirectives();
    }

    /**
     * Register theme-related Blade directives.
     *
     * @return void
     */
    protected static function registerThemeDirectives(): void
    {
        // @theme('key', 'default')
        Blade::directive('theme', function ($expression) {
            return "<?php echo theme({$expression}); ?>";
        });

        // @themeColor('primary')
        Blade::directive('themeColor', function ($expression) {
            return "<?php echo theme_color({$expression}); ?>";
        });

        // @themeFont('sans')
        Blade::directive('themeFont', function ($expression) {
            return "<?php echo theme_font({$expression}); ?>";
        });

        // @themeCss
        Blade::directive('themeCss', function () {
            return '<?php echo theme_css(); ?>';
        });

        // @themeInject
        Blade::directive('themeInject', function () {
            return '<?php echo theme_inject(); ?>';
        });

        // @themeStyles
        Blade::directive('themeStyles', function () {
            return <<<'HTML'
<?php echo '<style>' . theme_css(true) . '</style>'; ?>
HTML;
        });

        // @themeName
        Blade::directive('themeName', function () {
            return '<?php echo current_theme()->getName(); ?>';
        });

        // @themeVersion
        Blade::directive('themeVersion', function () {
            return '<?php echo current_theme()->getVersion(); ?>';
        });

        // @themeAuthor
        Blade::directive('themeAuthor', function () {
            return '<?php echo current_theme()->getAuthor(); ?>';
        });

        // @darkMode
        Blade::directive('darkMode', function () {
            return "<?php echo theme()->supportsDarkMode() ? 'true' : 'false'; ?>";
        });
    }
}
