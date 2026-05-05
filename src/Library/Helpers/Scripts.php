<?php
/**
 * Created on 12 Mar 2021
 * Time Created	: 13:48:55
 *
 * @filesource	Scripts.php canvastack_config("baseURL") . '/' . canvastack_config("template_folder")
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
 
if (!function_exists('canvastack_script_html_element_value')) {
    
    /**
     * Find Match HTML Elements to get string Value and all HTML Tag
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @param string $string
     * @param string $tagname
     * @param string $elm
     *
     * @return string
     */
    function canvastack_script_html_element_value($string, $tagname, $elm, $asHTML = true) {
        $match = false;
        preg_match("/<{$tagname}\s.*?\b{$elm}=\"(.*?)\".*?>/si", $string, $match);
        
        $data = null;
        if (false === $asHTML) {
            $data = $match[1];
        } else {
            $data = $match[0];
        }
        
        return $data;
    }
}

if (!function_exists('canvastack_script_asset_path')) {
    
    /**
     * Get Asset Path
     * 
     * @return string
     */
    function canvastack_script_asset_path() {
    	return canvastack_config("baseURL") . '/' . canvastack_config("base_template") . '/' . canvastack_config("template");
    }
}

if (!function_exists('canvastack_script_check_string_path')) {
    
    /**
     * Check string path
     * 
     * Handles three types of paths:
     * 1. CDN URLs (http:// or https://) - returned as-is
     * 2. Parent directory paths (../) - resolved relative to base template path
     * 3. Local paths - prepended with asset path
     * 
     * @param string $string Path to check
     * @param bool $exist_check Whether to check if path exists
     * 
     * @return string Processed path
     */
    function canvastack_script_check_string_path($string, $exist_check = false) {
        // 1. Check if it's a CDN URL (http:// or https://)
        if (str_contains($string, 'https://') || str_contains($string, 'http://')) {
            $path = $string;  // Return CDN URL as-is
        }
        // 2. Check if it's a parent directory path (../)
        else if (str_starts_with($string, '../')) {
            // Get base URL and template folder
            $baseURL = canvastack_config("baseURL");
            $baseTemplate = canvastack_config("base_template");
            
            // Remove '../' and build path from base template folder
            $relativePath = ltrim($string, './');  // Remove leading ../ or ./
            $path = "{$baseURL}/{$baseTemplate}/{$relativePath}";
        }
        // 3. Local path - prepend with asset path
        else {
            $path = canvastack_script_asset_path() . "/{$string}";
        }
        
        if (true === $exist_check) {
            if (canvastack_exist_url($path)) return $path;
        } else {
            return $path;
        }
    }
}