<?php
/**
 * Created on 12 Mar 2021
 * Time Created	: 13:48:55
 *
 * @filesource	Scripts.php canvastack_config("baseURL") . '/' . canvastack_config("template_folder")
 *
 * author: wisnuwidi@canvastack.com - 2021
 * email:  wisnuwidi@canvastack.com
 */

use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

if (! function_exists('canvastack_script_html_element_value')) {

    /**
     * Find Match HTML Elements to get string Value and all HTML Tag
     *
     * BC: delegate to Canvatility::elementValue
     */
    function canvastack_script_html_element_value($string, $tagname, $elm, $asHTML = true)
    {
        return Canvatility::elementValue($string, $tagname, $elm, $asHTML);
    }
}

if (! function_exists('canvastack_script_asset_path')) {

    /**
     * Get Asset Path
     *
     * Delegate to Canvatility for centralized logic.
     */
    function canvastack_script_asset_path()
    {
        return Canvatility::assetBasePath();
    }
}

if (! function_exists('canvastack_script_check_string_path')) {

    /**
     * Check string path
     *
     * Delegate to Canvatility for centralized logic.
     */
    function canvastack_script_check_string_path($string, $exist_check = false)
    {
        return Canvatility::checkStringPath($string, $exist_check);
    }
}

if (! function_exists('canvastack_image_validations')) {
    /**
     * Back-compat image validation helper
     * Example: canvastack_image_validations(2000) => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2000'
     */
    function canvastack_image_validations($maxKb = 2048)
    {
        return Canvatility::imageValidations((int) $maxKb);
    }
}

if (! function_exists('canvastack_set_filesize')) {
    /**
     * Back-compat helper to convert to kilobytes.
     * Legacy code already passes KB, so this is effectively passthrough.
     */
    function canvastack_set_filesize($size)
    {
        return Canvatility::toKilobytes((int) $size);
    }
}
