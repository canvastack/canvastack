<?php

namespace Canvastack\Canvastack\View\Directives;

use Illuminate\Support\Facades\Blade;

/**
 * RtlDirectives.
 *
 * Registers Blade directives for RTL support.
 */
class RtlDirectives
{
    /**
     * Register RTL directives.
     *
     * @return void
     */
    public static function register(): void
    {
        // @rtl directive - outputs content only for RTL locales
        Blade::directive('rtl', function () {
            return '<?php if(is_rtl()): ?>';
        });

        Blade::directive('endrtl', function () {
            return '<?php endif; ?>';
        });

        // @ltr directive - outputs content only for LTR locales
        Blade::directive('ltr', function () {
            return '<?php if(!is_rtl()): ?>';
        });

        Blade::directive('endltr', function () {
            return '<?php endif; ?>';
        });

        // @dir directive - outputs dir attribute
        Blade::directive('dir', function ($locale = null) {
            return "<?php echo 'dir=\"'.text_direction({$locale}).'\"'; ?>";
        });

        // @textDirection directive - outputs text direction
        Blade::directive('textDirection', function ($locale = null) {
            return "<?php echo text_direction({$locale}); ?>";
        });

        // @rtlClass directive - outputs RTL/LTR class
        Blade::directive('rtlClass', function ($locale = null) {
            return "<?php echo is_rtl({$locale}) ? 'rtl' : 'ltr'; ?>";
        });

        // @marginStart directive - outputs margin-start value
        Blade::directive('marginStart', function ($value) {
            return "<?php echo is_rtl() ? 'margin-right: {$value}' : 'margin-left: {$value}'; ?>";
        });

        // @marginEnd directive - outputs margin-end value
        Blade::directive('marginEnd', function ($value) {
            return "<?php echo is_rtl() ? 'margin-left: {$value}' : 'margin-right: {$value}'; ?>";
        });

        // @paddingStart directive - outputs padding-start value
        Blade::directive('paddingStart', function ($value) {
            return "<?php echo is_rtl() ? 'padding-right: {$value}' : 'padding-left: {$value}'; ?>";
        });

        // @paddingEnd directive - outputs padding-end value
        Blade::directive('paddingEnd', function ($value) {
            return "<?php echo is_rtl() ? 'padding-left: {$value}' : 'padding-right: {$value}'; ?>";
        });

        // @floatStart directive - outputs float-start value
        Blade::directive('floatStart', function () {
            return "<?php echo is_rtl() ? 'float: right' : 'float: left'; ?>";
        });

        // @floatEnd directive - outputs float-end value
        Blade::directive('floatEnd', function () {
            return "<?php echo is_rtl() ? 'float: left' : 'float: right'; ?>";
        });

        // @textStart directive - outputs text-align-start value
        Blade::directive('textStart', function () {
            return "<?php echo is_rtl() ? 'text-align: right' : 'text-align: left'; ?>";
        });

        // @textEnd directive - outputs text-align-end value
        Blade::directive('textEnd', function () {
            return "<?php echo is_rtl() ? 'text-align: left' : 'text-align: right'; ?>";
        });
    }
}
