<?php

use Canvastack\Canvastack\Library\Helpers\FileUploadConfig;

if (!function_exists('canvastack_file_upload_meta_tags')) {
    /**
     * Get file upload configuration meta tags
     * 
     * @return string HTML meta tags
     */
    function canvastack_file_upload_meta_tags(): string
    {
        return FileUploadConfig::getMetaTags();
    }
}

if (!function_exists('canvastack_file_upload_script')) {
    /**
     * Get file upload validator script tag
     * 
     * @return string HTML script tag
     */
    function canvastack_file_upload_script(): string
    {
        return FileUploadConfig::getScriptTag();
    }
}

if (!function_exists('canvastack_file_upload_config')) {
    /**
     * Get complete file upload configuration
     * 
     * @return array Array with 'meta' and 'script' keys
     */
    function canvastack_file_upload_config(): array
    {
        return FileUploadConfig::getCompleteConfig();
    }
}

if (!function_exists('canvastack_file_upload_config_json')) {
    /**
     * Get file upload configuration as JSON
     * 
     * @return string JSON configuration
     */
    function canvastack_file_upload_config_json(): string
    {
        return FileUploadConfig::getConfigJson();
    }
}
