<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

/**
 * Form Structure Detector - Advanced HTML Form Element Detection
 * 
 * This class provides comprehensive detection of HTML form structures
 * to differentiate between legitimate form HTML and user content.
 * 
 * Features:
 * - Complete HTML5 form element coverage
 * - Dynamic pattern matching
 * - Extensible for future form elements
 * - High-performance caching
 * - Context-aware detection
 * 
 * @package Canvastack\Form\Security
 * @author CanvaStack Security Team
 * @version 2.0.0
 */
class FormStructureDetector
{
    /**
     * Comprehensive list of all HTML form elements and attributes
     */
    private static $formElements = [
        // Core Form Elements
        'form', 'input', 'textarea', 'select', 'option', 'optgroup',
        'button', 'label', 'fieldset', 'legend', 'datalist',
        
        // HTML5 Form Elements
        'output', 'progress', 'meter',
        
        // Form Container Elements
        'div', 'span', 'p', 'section', 'article'
    ];
    
    /**
     * HTML5 Input Types
     */
    private static $inputTypes = [
        'text', 'password', 'email', 'url', 'tel', 'search',
        'number', 'range', 'date', 'time', 'datetime-local',
        'month', 'week', 'color', 'file', 'hidden',
        'checkbox', 'radio', 'submit', 'reset', 'button', 'image'
    ];
    
    /**
     * Form-specific CSS classes and attributes
     */
    private static $formClasses = [
        'form-group', 'form-control', 'form-check', 'form-switch',
        'input-group', 'form-floating', 'form-select', 'form-range',
        'btn', 'button', 'checkbox', 'radio', 'switch',
        'ckbox', 'rdio', 'form-label', 'col-form-label'
    ];
    
    /**
     * Form-specific attributes
     */
    private static $formAttributes = [
        'name', 'id', 'value', 'placeholder', 'required', 'disabled',
        'readonly', 'checked', 'selected', 'multiple', 'size',
        'maxlength', 'minlength', 'min', 'max', 'step', 'pattern',
        'autocomplete', 'autofocus', 'form', 'formaction', 'formmethod'
    ];
    
    /**
     * Cache for compiled patterns (performance optimization)
     */
    private static $patternCache = [];
    
    /**
     * Detect if content contains form structure HTML
     * 
     * @param string $content The HTML content to analyze
     * @param array $options Detection options
     * @return bool True if form structure detected
     */
    public static function isFormStructure(string $content, array $options = []): bool
    {
        if (empty($content)) {
            return false;
        }
        
        // Quick performance check - if no HTML tags, definitely not form structure
        if (!preg_match('/<[^>]+>/', $content)) {
            return false;
        }
        
        // Multi-level detection for accuracy
        return self::detectFormElements($content) ||
               self::detectFormClasses($content) ||
               self::detectFormAttributes($content) ||
               self::detectFormContext($content);
    }
    
    /**
     * Get detailed analysis of form structure
     * 
     * @param string $content The HTML content to analyze
     * @return array Detailed analysis results
     */
    public static function analyzeFormStructure(string $content): array
    {
        $analysis = [
            'is_form_structure' => false,
            'detected_elements' => [],
            'detected_classes' => [],
            'detected_attributes' => [],
            'confidence_score' => 0,
            'structure_type' => 'unknown'
        ];
        
        if (empty($content) || !preg_match('/<[^>]+>/', $content)) {
            return $analysis;
        }
        
        // Detect form elements
        $analysis['detected_elements'] = self::findFormElements($content);
        
        // Detect form classes
        $analysis['detected_classes'] = self::findFormClasses($content);
        
        // Detect form attributes
        $analysis['detected_attributes'] = self::findFormAttributes($content);
        
        // Calculate confidence score
        $analysis['confidence_score'] = self::calculateConfidenceScore($analysis);
        
        // Determine structure type
        $analysis['structure_type'] = self::determineStructureType($analysis);
        
        // Final determination
        $analysis['is_form_structure'] = $analysis['confidence_score'] >= 0.7;
        
        return $analysis;
    }
    
    /**
     * Detect form elements in content
     */
    private static function detectFormElements(string $content): bool
    {
        // Check for core form elements
        foreach (self::$formElements as $element) {
            if (preg_match("/<{$element}[^>]*>/i", $content)) {
                return true;
            }
        }
        
        // Check for input types
        foreach (self::$inputTypes as $type) {
            if (preg_match("/type=[\"']{$type}[\"']/i", $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect form classes in content
     */
    private static function detectFormClasses(string $content): bool
    {
        foreach (self::$formClasses as $class) {
            if (preg_match("/class=[\"'][^\"']*{$class}/i", $content)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Detect form attributes in content
     */
    private static function detectFormAttributes(string $content): bool
    {
        foreach (self::$formAttributes as $attr) {
            if (preg_match("/{$attr}=[\"']/i", $content)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Detect form context patterns
     */
    private static function detectFormContext(string $content): bool
    {
        $contextPatterns = [
            // Form wrapper patterns
            '/<div[^>]*class=["\'][^"\']*form/i',
            '/<form[^>]*>/i',
            '/<fieldset[^>]*>/i',
            
            // Bootstrap form patterns
            '/<div[^>]*class=["\'][^"\']*row/i',
            '/<div[^>]*class=["\'][^"\']*col-/i',
            
            // Input group patterns
            '/<div[^>]*class=["\'][^"\']*input-group/i',
        ];
        
        foreach ($contextPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Find specific form elements in content
     */
    private static function findFormElements(string $content): array
    {
        $found = [];
        
        foreach (self::$formElements as $element) {
            if (preg_match_all("/<{$element}[^>]*>/i", $content, $matches)) {
                $found[$element] = count($matches[0]);
            }
        }
        
        return $found;
    }
    
    /**
     * Find form classes in content
     */
    private static function findFormClasses(string $content): array
    {
        $found = [];
        
        foreach (self::$formClasses as $class) {
            if (preg_match_all("/class=[\"'][^\"']*{$class}/i", $content, $matches)) {
                $found[$class] = count($matches[0]);
            }
        }
        
        return $found;
    }
    
    /**
     * Find form attributes in content
     */
    private static function findFormAttributes(string $content): array
    {
        $found = [];
        
        foreach (self::$formAttributes as $attr) {
            if (preg_match_all("/{$attr}=[\"']/i", $content, $matches)) {
                $found[$attr] = count($matches[0]);
            }
        }
        
        return $found;
    }
    
    /**
     * Calculate confidence score based on detected elements
     */
    private static function calculateConfidenceScore(array $analysis): float
    {
        $score = 0;
        
        // Element detection score (40% weight)
        $elementCount = array_sum($analysis['detected_elements']);
        $elementScore = min($elementCount * 0.2, 0.4);
        
        // Class detection score (30% weight)
        $classCount = array_sum($analysis['detected_classes']);
        $classScore = min($classCount * 0.15, 0.3);
        
        // Attribute detection score (30% weight)
        $attributeCount = array_sum($analysis['detected_attributes']);
        $attributeScore = min($attributeCount * 0.1, 0.3);
        
        $score = $elementScore + $classScore + $attributeScore;
        
        return min($score, 1.0);
    }
    
    /**
     * Determine the type of form structure
     */
    private static function determineStructureType(array $analysis): string
    {
        $elements = $analysis['detected_elements'];
        $classes = $analysis['detected_classes'];
        
        // Check for specific form types
        if (isset($elements['input']) && $elements['input'] > 0) {
            if (isset($classes['form-check']) || isset($classes['checkbox'])) {
                return 'checkbox_form';
            }
            if (isset($classes['rdio']) || isset($classes['radio'])) {
                return 'radio_form';
            }
            return 'input_form';
        }
        
        if (isset($elements['select']) && $elements['select'] > 0) {
            return 'select_form';
        }
        
        if (isset($elements['textarea']) && $elements['textarea'] > 0) {
            return 'textarea_form';
        }
        
        if (isset($elements['button']) && $elements['button'] > 0) {
            return 'button_form';
        }
        
        if (isset($classes['form-group']) || isset($classes['form-control'])) {
            return 'bootstrap_form';
        }
        
        return 'generic_form';
    }
    
    /**
     * Add custom form elements for detection
     * 
     * @param array $elements Custom elements to add
     */
    public static function addCustomElements(array $elements): void
    {
        self::$formElements = array_merge(self::$formElements, $elements);
        self::$patternCache = []; // Clear cache
    }
    
    /**
     * Add custom form classes for detection
     * 
     * @param array $classes Custom classes to add
     */
    public static function addCustomClasses(array $classes): void
    {
        self::$formClasses = array_merge(self::$formClasses, $classes);
        self::$patternCache = []; // Clear cache
    }
    
    /**
     * Get all supported form elements
     * 
     * @return array List of supported form elements
     */
    public static function getSupportedElements(): array
    {
        return self::$formElements;
    }
    
    /**
     * Get all supported form classes
     * 
     * @return array List of supported form classes
     */
    public static function getSupportedClasses(): array
    {
        return self::$formClasses;
    }
    
    /**
     * Clear pattern cache (useful for testing or dynamic updates)
     */
    public static function clearCache(): void
    {
        self::$patternCache = [];
    }
}