<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

/**
 * HTML Sanitization Service for CanvaStack Form System
 * 
 * Provides comprehensive HTML sanitization to prevent XSS attacks
 * while preserving safe HTML elements and attributes.
 * 
 * @package Canvastack\Form\Security
 * @version 2.0.0
 * @author CanvaStack Security Team
 */
class HtmlSanitizer
{
    /**
     * HTMLPurifier instance
     * @var \HTMLPurifier|null
     */
    private static $purifier = null;
    
    /**
     * Allowed HTML tags for form content
     * @var array
     */
    private static $allowedTags = [
        'b', 'i', 'u', 'strong', 'em', 'p', 'br', 'ul', 'ol', 'li', 
        'a', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'code', 'pre'
    ];
    
    /**
     * Allowed HTML attributes
     * @var array
     */
    private static $allowedAttributes = [
        'href', 'class', 'id', 'title', 'alt', 'target'
    ];
    
    /**
     * Allowed URL schemes
     * @var array
     */
    private static $allowedSchemes = [
        'http', 'https', 'mailto', 'tel'
    ];
    
    /**
     * Initialize HTMLPurifier with secure configuration
     * 
     * @return \HTMLPurifier
     */
    public static function init()
    {
        if (!self::$purifier) {
            // Check if HTMLPurifier is available
            if (!class_exists('\HTMLPurifier')) {
                // Fallback to basic sanitization if HTMLPurifier not available
                return null;
            }
            
            $config = \HTMLPurifier_Config::createDefault();
            
            // Set allowed HTML elements
            $allowedHtml = implode(',', self::$allowedTags);
            $allowedAttrs = implode(',', self::$allowedAttributes);
            
            $config->set('HTML.Allowed', $allowedHtml . '[' . $allowedAttrs . ']');
            $config->set('HTML.AllowedAttributes', $allowedAttrs);
            $config->set('HTML.AllowedSchemes', self::$allowedSchemes);
            $config->set('HTML.Nofollow', true);
            $config->set('HTML.TargetBlank', true);
            
            // Additional security settings
            $config->set('Core.RemoveInvalidImg', true);
            $config->set('Cache.SerializerPath', storage_path('framework/cache'));
            $config->set('HTML.TidyLevel', 'heavy');
            
            // Disable dangerous elements
            $config->set('HTML.ForbiddenElements', [
                'script', 'object', 'embed', 'applet', 'iframe', 'frame', 'frameset',
                'meta', 'link', 'style', 'base', 'form', 'input', 'button', 'select',
                'textarea', 'option'
            ]);
            
            self::$purifier = new \HTMLPurifier($config);
        }
        
        return self::$purifier;
    }
    
    /**
     * Clean HTML content using HTMLPurifier
     * 
     * @param string $html Raw HTML content
     * @return string Sanitized HTML content
     */
    public static function clean($html)
    {
        if (empty($html)) {
            return '';
        }
        
        $purifier = self::init();
        
        if ($purifier) {
            return $purifier->purify($html);
        }
        
        // Fallback sanitization if HTMLPurifier not available
        return self::basicSanitize($html);
    }
    
    /**
     * Clean HTML attribute values
     * 
     * @param string $value Attribute value
     * @return string Sanitized attribute value
     */
    public static function cleanAttribute($value)
    {
        if (empty($value)) {
            return '';
        }
        
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Clean form input values
     * 
     * @param mixed $value Input value
     * @return mixed Sanitized input value
     */
    public static function cleanInput($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'cleanInput'], $value);
        }
        
        if (is_string($value)) {
            // Remove null bytes
            $value = str_replace("\0", '', $value);
            
            // Clean HTML entities
            return self::cleanAttribute($value);
        }
        
        return $value;
    }
    
    /**
     * Basic HTML sanitization fallback
     * 
     * @param string $html Raw HTML content
     * @return string Sanitized HTML content
     */
    private static function basicSanitize($html)
    {
        // Remove script tags and their content
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        
        // Remove dangerous attributes
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*javascript\s*:/i', '', $html);
        $html = preg_replace('/\s*vbscript\s*:/i', '', $html);
        $html = preg_replace('/\s*data\s*:/i', '', $html);
        
        // Remove dangerous tags
        $dangerousTags = [
            'script', 'object', 'embed', 'applet', 'iframe', 'frame', 'frameset',
            'meta', 'link', 'style', 'base', 'form', 'input', 'button', 'select',
            'textarea', 'option'
        ];
        
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>/i', '', $html);
            $html = preg_replace('/<\/' . $tag . '>/i', '', $html);
        }
        
        return $html;
    }
    
    /**
     * Validate if HTML content is safe
     * 
     * @param string $html HTML content to validate
     * @return bool True if safe, false otherwise
     */
    public static function isSafe($html)
    {
        if (empty($html)) {
            return true;
        }
        
        // Check for dangerous patterns
        $dangerousPatterns = [
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:/i',
            '/<script\b/i',
            '/on\w+\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<applet\b/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $html)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if content contains XSS patterns
     * 
     * @param string $content Content to check
     * @return bool True if contains XSS, false otherwise
     */
    public static function containsXSS($content)
    {
        return !self::isSafe($content);
    }
    
    /**
     * Clean and validate form data recursively
     * 
     * @param array $data Form data
     * @return array Cleaned form data
     */
    public static function cleanFormData(array $data)
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            $cleanKey = self::cleanAttribute($key);
            
            if (is_array($value)) {
                $cleaned[$cleanKey] = self::cleanFormData($value);
            } else {
                $cleaned[$cleanKey] = self::cleanInput($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Log security incidents
     * 
     * @param string $type Incident type
     * @param string $content Dangerous content
     * @param array $context Additional context
     */
    private static function logSecurityIncident($type, $content, array $context = [])
    {
        \Log::warning('SECURITY: XSS attempt detected', [
            'type' => $type,
            'content' => substr($content, 0, 200),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'context' => $context
        ]);
    }
    
    /**
     * Log XSS attempt with specific context
     * 
     * @param string $content Dangerous content
     * @param string $context Context where XSS was detected
     */
    public static function logXSSAttempt($content, $context = 'unknown')
    {
        self::logSecurityIncident('xss_attempt', $content, ['context' => $context]);
    }
    
    /**
     * Clean HTML with security logging
     * 
     * @param string $html Raw HTML content
     * @param array $context Additional context for logging
     * @return string Sanitized HTML content
     */
    public static function cleanWithLogging($html, array $context = [])
    {
        if (empty($html)) {
            return '';
        }
        
        // Check if content is potentially dangerous
        if (!self::isSafe($html)) {
            self::logSecurityIncident('xss_attempt', $html, $context);
        }
        
        return self::clean($html);
    }
}