<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

require_once __DIR__ . '/FormFormatter.php';
require_once __DIR__ . '/FormStructureDetector.php';
require_once __DIR__ . '/HtmlSanitizer.php';

/**
 * Universal Content Sanitizer - Modular XSS Protection System
 * 
 * This class provides comprehensive, context-aware content sanitization
 * that can be used across the entire CanvaStack system including:
 * - Form Builder System
 * - Table System  
 * - General Content Display
 * - User Input Processing
 * 
 * Features:
 * - Context-aware sanitization (form vs content vs table)
 * - Modular design for reusability
 * - High-performance caching
 * - Comprehensive XSS protection
 * - Preserves legitimate HTML structure
 * - Extensible for custom contexts
 * 
 * @package Canvastack\Security
 * @author CanvaStack Security Team
 * @version 2.0.0
 */
class ContentSanitizer
{
    /**
     * Sanitization contexts
     */
    const CONTEXT_FORM = 'form';
    const CONTEXT_TABLE = 'table';
    const CONTEXT_CONTENT = 'content';
    const CONTEXT_ATTRIBUTE = 'attribute';
    const CONTEXT_USER_INPUT = 'user_input';
    
    /**
     * Sanitization levels
     */
    const LEVEL_STRICT = 'strict';      // Remove all HTML
    const LEVEL_MODERATE = 'moderate';  // Allow safe HTML
    const LEVEL_PERMISSIVE = 'permissive'; // Allow most HTML, remove XSS
    
    /**
     * Cache for sanitization results
     */
    private static $sanitizationCache = [];
    
    /**
     * Configuration for different contexts
     */
    private static $contextConfig = [
        self::CONTEXT_FORM => [
            'preserve_structure' => true,
            'allowed_tags' => ['div', 'input', 'select', 'textarea', 'label', 'button', 'option', 'fieldset', 'legend'],
            'allowed_attributes' => ['class', 'id', 'name', 'type', 'value', 'placeholder', 'required', 'disabled'],
            'level' => self::LEVEL_PERMISSIVE
        ],
        self::CONTEXT_TABLE => [
            'preserve_structure' => true,
            'allowed_tags' => ['table', 'tr', 'td', 'th', 'thead', 'tbody', 'tfoot', 'div', 'span', 'a', 'button'],
            'allowed_attributes' => ['class', 'id', 'colspan', 'rowspan', 'href', 'target', 'data-*'],
            'level' => self::LEVEL_PERMISSIVE
        ],
        self::CONTEXT_CONTENT => [
            'preserve_structure' => false,
            'allowed_tags' => ['p', 'br', 'strong', 'em', 'u', 'i', 'b'],
            'allowed_attributes' => ['class'],
            'level' => self::LEVEL_MODERATE
        ],
        self::CONTEXT_ATTRIBUTE => [
            'preserve_structure' => false,
            'allowed_tags' => [],
            'allowed_attributes' => [],
            'level' => self::LEVEL_STRICT
        ],
        self::CONTEXT_USER_INPUT => [
            'preserve_structure' => false,
            'allowed_tags' => [],
            'allowed_attributes' => [],
            'level' => self::LEVEL_STRICT
        ]
    ];
    
    /**
     * Sanitize content based on context
     * 
     * @param string $content Content to sanitize
     * @param string $context Sanitization context
     * @param array $options Additional options
     * @return string Sanitized content
     */
    public static function sanitize(string $content, string $context = self::CONTEXT_CONTENT, array $options = []): string
    {
        if (empty($content)) {
            return $content;
        }
        
        // Check cache first for performance
        $cacheKey = self::getCacheKey($content, $context, $options);
        if (isset(self::$sanitizationCache[$cacheKey])) {
            return self::$sanitizationCache[$cacheKey];
        }
        
        // Get context configuration
        $config = self::getContextConfig($context, $options);
        
        // Perform sanitization based on context
        $sanitized = self::performSanitization($content, $config, $context);
        
        // Cache result
        self::$sanitizationCache[$cacheKey] = $sanitized;
        
        // Log if XSS was detected and removed
        if ($sanitized !== $content) {
            HtmlSanitizer::logXSSAttempt($content, $context);
        }
        
        return $sanitized;
    }
    
    /**
     * Smart sanitization that detects content type automatically
     * 
     * @param string $content Content to sanitize
     * @param array $options Additional options
     * @return string Sanitized content
     */
    public static function smartSanitize(string $content, array $options = []): string
    {
        if (empty($content)) {
            return $content;
        }
        
        // Auto-detect context
        $context = self::detectContext($content);
        
        return self::sanitize($content, $context, $options);
    }
    
    /**
     * Sanitize for form context with structure preservation
     * 
     * @param string $content Form HTML content
     * @param array $options Additional options
     * @return string Sanitized form content
     */
    public static function sanitizeForm(string $content, array $options = []): string
    {
        $sanitized = '';
        
        // Check if this is form structure
        if (FormStructureDetector::isFormStructure($content)) {
            // Preserve form structure, only remove XSS
            $sanitized = self::sanitizeFormStructure($content, $options);
        } else {
            // Regular content sanitization
            $sanitized = self::sanitize($content, self::CONTEXT_CONTENT, $options);
        }
        
        // Apply formatting if requested
        if (isset($options['format']) && $options['format']) {
            $sanitized = FormFormatter::formatForm($sanitized, $options['format_options'] ?? []);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize for table context with structure preservation
     * 
     * @param string $content Table HTML content
     * @param array $options Additional options
     * @return string Sanitized table content
     */
    public static function sanitizeTable(string $content, array $options = []): string
    {
        // Check if this is table structure
        if (self::isTableStructure($content)) {
            // Preserve table structure, only remove XSS
            return self::sanitizeTableStructure($content, $options);
        } else {
            // Regular content sanitization
            return self::sanitize($content, self::CONTEXT_CONTENT, $options);
        }
    }
    
    /**
     * Sanitize user input strictly
     * 
     * @param string $input User input
     * @param array $options Additional options
     * @return string Sanitized input
     */
    public static function sanitizeUserInput(string $input, array $options = []): string
    {
        return self::sanitize($input, self::CONTEXT_USER_INPUT, $options);
    }
    
    /**
     * Sanitize HTML attributes
     * 
     * @param string $attribute Attribute value
     * @param array $options Additional options
     * @return string Sanitized attribute
     */
    public static function sanitizeAttribute(string $attribute, array $options = []): string
    {
        return self::sanitize($attribute, self::CONTEXT_ATTRIBUTE, $options);
    }
    
    /**
     * Detect content context automatically
     */
    private static function detectContext(string $content): string
    {
        // Check for form structure
        if (FormStructureDetector::isFormStructure($content)) {
            return self::CONTEXT_FORM;
        }
        
        // Check for table structure
        if (self::isTableStructure($content)) {
            return self::CONTEXT_TABLE;
        }
        
        // Default to content
        return self::CONTEXT_CONTENT;
    }
    
    /**
     * Check if content is table structure
     */
    private static function isTableStructure(string $content): bool
    {
        return preg_match('/<table[^>]*>|<tr[^>]*>|<td[^>]*>|<th[^>]*>/i', $content) ||
               preg_match('/class=["\'][^"\']*table[^"\']*["\']/i', $content);
    }
    
    /**
     * Get context configuration
     */
    private static function getContextConfig(string $context, array $options): array
    {
        $config = self::$contextConfig[$context] ?? self::$contextConfig[self::CONTEXT_CONTENT];
        
        // Merge with options
        return array_merge($config, $options);
    }
    
    /**
     * Perform actual sanitization
     */
    private static function performSanitization(string $content, array $config, string $context): string
    {
        // Quick XSS check first
        if (!HtmlSanitizer::containsXSS($content)) {
            return $content;
        }
        
        switch ($config['level']) {
            case self::LEVEL_STRICT:
                return self::strictSanitization($content, $config);
                
            case self::LEVEL_MODERATE:
                return self::moderateSanitization($content, $config);
                
            case self::LEVEL_PERMISSIVE:
                return self::permissiveSanitization($content, $config, $context);
                
            default:
                return self::moderateSanitization($content, $config);
        }
    }
    
    /**
     * Strict sanitization - remove all HTML
     */
    private static function strictSanitization(string $content, array $config): string
    {
        // Remove all HTML tags
        $sanitized = strip_tags($content);
        
        // Remove dangerous characters
        $sanitized = preg_replace('/[<>"\']/', '', $sanitized);
        
        // Remove JavaScript protocols
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Moderate sanitization - allow safe HTML
     */
    private static function moderateSanitization(string $content, array $config): string
    {
        // Use HTMLPurifier if available, otherwise fallback
        if (class_exists('\HTMLPurifier')) {
            return HtmlSanitizer::clean($content);
        }
        
        // Fallback sanitization
        return self::fallbackSanitization($content, $config);
    }
    
    /**
     * Permissive sanitization - preserve structure, remove XSS
     */
    private static function permissiveSanitization(string $content, array $config, string $context): string
    {
        if ($context === self::CONTEXT_FORM && $config['preserve_structure']) {
            return self::sanitizeFormStructure($content, $config);
        }
        
        if ($context === self::CONTEXT_TABLE && $config['preserve_structure']) {
            return self::sanitizeTableStructure($content, $config);
        }
        
        // Default permissive sanitization
        return self::removeXSSOnly($content, $config);
    }
    
    /**
     * Sanitize form structure while preserving HTML
     */
    private static function sanitizeFormStructure(string $content, array $config): string
    {
        // Check if this is legitimate CanvaStack JavaScript (contains ajaxSelectionBox or similar functions)
        $isCanvaStackJS = preg_match('/<script[^>]*>.*?ajaxSelectionBox\s*\(/is', $content) ||
                         preg_match('/<script[^>]*>.*?\$\(document\)\.ready\(/is', $content);
        
        if ($isCanvaStackJS) {
            // For CanvaStack-generated JavaScript, only remove dangerous inline event handlers
            // but preserve the script tags and legitimate JavaScript functions
            $sanitized = preg_replace('/on\w+\s*=[^>]*/i', '', $content);
            $sanitized = preg_replace('/\s(onerror|onload|onclick|onmouseover|onfocus|onblur)\s*=[^>]*/i', '', $sanitized);
            
            // Remove javascript: protocol but preserve legitimate script content
            $sanitized = preg_replace('/href\s*=\s*["\']javascript:/i', 'href="#"', $sanitized);
        } else {
            // For non-CanvaStack content, remove all scripts as before
            $sanitized = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
            $sanitized = preg_replace('/javascript:/i', '', $sanitized);
            $sanitized = preg_replace('/on\w+\s*=[^>]*/i', '', $sanitized);
            
            // Remove dangerous attributes but preserve form structure
            $sanitized = preg_replace('/\s(onerror|onload|onclick|onmouseover|onfocus|onblur)\s*=[^>]*/i', '', $sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize table structure while preserving HTML
     */
    private static function sanitizeTableStructure(string $content, array $config): string
    {
        // Remove dangerous scripts and event handlers
        $sanitized = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        $sanitized = preg_replace('/on\w+\s*=[^>]*/i', '', $sanitized);
        
        // Remove dangerous attributes but preserve table structure
        $sanitized = preg_replace('/\s(onerror|onload|onclick|onmouseover|onfocus|onblur)\s*=[^>]*/i', '', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Remove only XSS, preserve everything else
     */
    private static function removeXSSOnly(string $content, array $config): string
    {
        // Remove script tags
        $sanitized = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        
        // Remove javascript: protocols
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        // Remove event handlers
        $sanitized = preg_replace('/on\w+\s*=[^>]*/i', '', $sanitized);
        
        // Remove dangerous attributes
        $sanitized = preg_replace('/\s(onerror|onload|onclick|onmouseover|onfocus|onblur|onsubmit)\s*=[^>]*/i', '', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Fallback sanitization when HTMLPurifier is not available
     */
    private static function fallbackSanitization(string $content, array $config): string
    {
        // Allow only specific tags if configured
        if (!empty($config['allowed_tags'])) {
            $allowedTags = '<' . implode('><', $config['allowed_tags']) . '>';
            $content = strip_tags($content, $allowedTags);
        }
        
        // Remove dangerous patterns
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        $content = preg_replace('/on\w+\s*=[^>]*/i', '', $content);
        
        return $content;
    }
    
    /**
     * Generate cache key
     */
    private static function getCacheKey(string $content, string $context, array $options): string
    {
        return md5($content . $context . serialize($options));
    }
    
    /**
     * Add custom context configuration
     * 
     * @param string $context Context name
     * @param array $config Context configuration
     */
    public static function addContext(string $context, array $config): void
    {
        self::$contextConfig[$context] = $config;
        self::clearCache();
    }
    
    /**
     * Update existing context configuration
     * 
     * @param string $context Context name
     * @param array $config Configuration updates
     */
    public static function updateContext(string $context, array $config): void
    {
        if (isset(self::$contextConfig[$context])) {
            self::$contextConfig[$context] = array_merge(self::$contextConfig[$context], $config);
            self::clearCache();
        }
    }
    
    /**
     * Get all available contexts
     * 
     * @return array List of available contexts
     */
    public static function getAvailableContexts(): array
    {
        return array_keys(self::$contextConfig);
    }
    
    /**
     * Clear sanitization cache
     */
    public static function clearCache(): void
    {
        self::$sanitizationCache = [];
        FormStructureDetector::clearCache();
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'cache_size' => count(self::$sanitizationCache),
            'memory_usage' => memory_get_usage(),
            'contexts' => count(self::$contextConfig)
        ];
    }
    
    /**
     * Batch sanitize multiple contents
     * 
     * @param array $contents Array of content to sanitize
     * @param string $context Sanitization context
     * @param array $options Additional options
     * @return array Sanitized contents
     */
    public static function batchSanitize(array $contents, string $context = self::CONTEXT_CONTENT, array $options = []): array
    {
        $sanitized = [];
        
        foreach ($contents as $key => $content) {
            if (is_string($content)) {
                $sanitized[$key] = self::sanitize($content, $context, $options);
            } else {
                $sanitized[$key] = $content;
            }
        }
        
        return $sanitized;
    }
}