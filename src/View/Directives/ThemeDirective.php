<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\View\Directives;

use Illuminate\Support\Facades\Blade;

/**
 * Theme Blade Directive.
 *
 * Provides Blade directives for accessing theme configuration
 */
class ThemeDirective
{
    /**
     * Register the theme directives.
     *
     * @return void
     */
    public static function register(): void
    {
        // @theme('colors.primary.500')
        Blade::directive('theme', function ($expression) {
            return "<?php echo theme({$expression}); ?>";
        });

        // @themeColor('primary', '500')
        Blade::directive('themeColor', function ($expression) {
            return "<?php echo theme()->current()->getColors()[{$expression}] ?? ''; ?>";
        });

        // @themeFont('sans')
        Blade::directive('themeFont', function ($expression) {
            return "<?php echo theme()->current()->getFonts()[{$expression}] ?? ''; ?>";
        });

        // @themeCss
        Blade::directive('themeCss', function () {
            return "<?php echo '<style>' . theme()->generateCss() . '</style>'; ?>";
        });

        // @themeVariables
        Blade::directive('themeVariables', function () {
            return <<<'PHP'
<?php
$variables = theme()->getCssVariables();
echo '<style>:root {';
foreach ($variables as $name => $value) {
    echo "{$name}: {$value};";
}
echo '}</style>';
?>
PHP;
        });
    }
}
