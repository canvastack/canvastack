<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

/**
 * Form HTML Formatter - Fixes rendering issues in generated forms
 * 
 * This class handles common HTML formatting issues that can cause
 * rendering problems in browsers, including:
 * - Very long lines that break layout
 * - Non-ASCII character encoding issues
 * - Malformed HTML structure
 * - Proper indentation for readability
 * 
 * @package Canvastack\Security
 * @author CanvaStack Security Team
 * @version 1.0.0
 */
class FormFormatter
{
    /**
     * Maximum line length before formatting
     */
    const MAX_LINE_LENGTH = 120;
    
    /**
     * Format HTML form for proper rendering
     * 
     * @param string $html Raw HTML form content
     * @param array $options Formatting options
     * @return string Formatted HTML
     */
    public static function formatForm(string $html, array $options = []): string
    {
        if (empty($html)) {
            return $html;
        }
        
        // Default options
        $options = array_merge([
            'fix_encoding' => true,
            'format_lines' => true,
            'add_indentation' => true,
            'fix_structure' => true,
            'fix_javascript' => true,
        ], $options);
        
        $formatted = $html;
        
        // Step 1: Fix encoding issues
        if ($options['fix_encoding']) {
            $formatted = self::fixEncoding($formatted);
        }
        
        // Step 2: Fix JavaScript issues (before line formatting)
        if ($options['fix_javascript']) {
            $formatted = self::fixJavaScript($formatted);
        }
        
        // Step 3: Format long lines
        if ($options['format_lines']) {
            $formatted = self::formatLongLines($formatted);
        }
        
        // Step 4: Add proper indentation
        if ($options['add_indentation']) {
            $formatted = self::addIndentation($formatted);
        }
        
        // Step 5: Fix HTML structure
        if ($options['fix_structure']) {
            $formatted = self::fixHtmlStructure($formatted);
        }
        
        return $formatted;
    }
    
    /**
     * Fix encoding issues in HTML
     */
    private static function fixEncoding(string $html): string
    {
        // Convert to UTF-8 if needed
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }
        
        // Fix common encoding issues
        $replacements = [
            // Fix smart quotes
            "\u{201C}" => '"',  // Left double quotation mark
            "\u{201D}" => '"',  // Right double quotation mark
            "\u{2018}" => "'",  // Left single quotation mark
            "\u{2019}" => "'",  // Right single quotation mark
            
            // Fix dashes
            "\u{2013}" => '-',  // En dash
            "\u{2014}" => '-',  // Em dash
            
            // Fix other common issues
            "\u{2026}" => '...',  // Horizontal ellipsis
            "\u{00A9}" => '&copy;',  // Copyright sign
            "\u{00AE}" => '&reg;',   // Registered sign
            "\u{2122}" => '&trade;', // Trade mark sign
        ];
        
        foreach ($replacements as $search => $replace) {
            $html = str_replace($search, $replace, $html);
        }
        
        return $html;
    }
    
    /**
     * Fix JavaScript issues - wrap naked JavaScript in script tags
     */
    private static function fixJavaScript(string $html): string
    {
        // CRITICAL FIX: Enhanced JavaScript detection to prevent double-processing
        // This prevents double-processing of JavaScript that's already correct
        
        // First, check if JavaScript is already properly wrapped in complete script tags
        if (preg_match('/<script[^>]*>.*?\$\(document\)\.ready\(.*?<\/script>/s', $html)) {
            // JavaScript is already properly wrapped, return as-is
            return $html;
        }
        
        // Check for script tags that are properly opened but may not be closed yet
        if (preg_match('/<script[^>]*>.*?\$\(document\)\.ready\(/s', $html)) {
            // Looks like properly started script tag, don't interfere
            return $html;
        }
        
        // Look for NAKED JavaScript patterns that need wrapping
        $jsPattern = '/\$\(document\)\.ready\(/';
        
        if (!preg_match($jsPattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            // No JavaScript found, return as is
            return $html;
        }
        
        $jsStart = $matches[0][1];
        
        // Enhanced check: make sure this JavaScript is truly NAKED (not in script tags)
        $beforeJs = substr($html, 0, $jsStart);
        
        // Look for the last script tag before this JavaScript
        $lastScriptOpen = strrpos($beforeJs, '<script');
        $lastScriptClose = strrpos($beforeJs, '</script>');
        
        // If there's an unclosed script tag before this JS, it's already wrapped
        if ($lastScriptOpen !== false && ($lastScriptClose === false || $lastScriptOpen > $lastScriptClose)) {
            return $html; // JavaScript is already in script tags
        }
        
        // Additional check: look for script tags in a wider context
        $contextBefore = substr($html, max(0, $jsStart - 200), 200);
        if (preg_match('/<script[^>]*>[^<]*$/', $contextBefore)) {
            return $html; // JavaScript appears to be inside script tags
        }
        
        if ($jsStart === -1) {
            // No naked JavaScript found, return as is
            return $html;
        }
        
        // Extract everything from JS start to find where it ends
        $beforeJs = substr($html, 0, $jsStart);
        $jsAndAfter = substr($html, $jsStart);
        
        // Try to find the end of the JavaScript block
        // Look for patterns that indicate end of JS or start of HTML
        $jsContent = '';
        $afterJs = '';
        
        // Split by lines to analyze
        $lines = explode("\n", $jsAndAfter);
        $jsLines = [];
        $htmlLines = [];
        $foundJsEnd = false;
        
        foreach ($lines as $i => $line) {
            $trimmedLine = trim($line);
            
            if (!$foundJsEnd) {
                // We're still in JavaScript
                $jsLines[] = $line;
                
                // Check if this line indicates end of JavaScript
                // Look for HTML tags that clearly indicate we're back in HTML
                if (preg_match('/^<(div|input|select|label|form|span|a)\b/', $trimmedLine)) {
                    // This looks like HTML, so previous line was end of JS
                    array_pop($jsLines); // Remove this HTML line from JS
                    $htmlLines[] = $line;
                    $foundJsEnd = true;
                }
                // Also check for incomplete JS that just trails off
                elseif ($i > 0 && strlen($trimmedLine) < 10 && 
                       !preg_match('/[;})\]]$/', $trimmedLine) && 
                       !preg_match('/^[\'"]/', $trimmedLine)) {
                    // This looks like incomplete/truncated content
                    array_pop($jsLines); // Remove this incomplete line
                    $htmlLines[] = $line;
                    $foundJsEnd = true;
                }
            } else {
                // We're back in HTML
                $htmlLines[] = $line;
            }
        }
        
        // Clean up the JavaScript content
        $jsContent = implode("\n", $jsLines);
        $jsContent = trim($jsContent);
        
        // Remove any trailing incomplete content
        $jsContent = preg_replace('/[^;})\]]+$/', '', $jsContent);
        
        // If JS doesn't end properly, try to close it
        if (!preg_match('/[;})\]]$/', $jsContent)) {
            // Count open parentheses and try to close them
            $openParens = substr_count($jsContent, '(') - substr_count($jsContent, ')');
            $openBraces = substr_count($jsContent, '{') - substr_count($jsContent, '}');
            
            for ($i = 0; $i < $openBraces; $i++) {
                $jsContent .= ' }';
            }
            for ($i = 0; $i < $openParens; $i++) {
                $jsContent .= ')';
            }
            
            if (!preg_match('/;$/', $jsContent)) {
                $jsContent .= ';';
            }
        }
        
        // Reconstruct the HTML
        $result = $beforeJs;
        
        if (!empty($jsContent)) {
            $result .= "<script type=\"text/javascript\">\n";
            $result .= $jsContent . "\n";
            $result .= "</script>\n";
        }
        
        if (!empty($htmlLines)) {
            $result .= implode("\n", $htmlLines);
        }
        
        return $result;
    }
    
    /**
     * Format very long lines for better readability
     */
    private static function formatLongLines(string $html): string
    {
        // CRITICAL FIX: Don't format lines that contain JavaScript
        // This prevents breaking JSON strings in JavaScript function calls
        if (preg_match('/<script[^>]*>.*?ajaxSelectionBox.*?<\/script>/s', $html)) {
            // This is JavaScript content - don't format to avoid breaking JSON strings
            return $html;
        }
        
        // Split on major HTML elements to create line breaks
        $html = preg_replace('/(<\/?(form|div|input|select|textarea|label|button)[^>]*>)/', "\n$1\n", $html);
        
        // Clean up multiple newlines
        $html = preg_replace('/\n+/', "\n", $html);
        
        // Split very long lines at logical points
        $lines = explode("\n", $html);
        $formattedLines = [];
        
        foreach ($lines as $line) {
            // Don't split lines that contain JavaScript function calls
            if (preg_match('/ajaxSelectionBox\s*\(/', $line)) {
                $formattedLines[] = $line;
            } elseif (strlen(trim($line)) > self::MAX_LINE_LENGTH) {
                $formattedLines = array_merge($formattedLines, self::splitLongLine($line));
            } else {
                $formattedLines[] = $line;
            }
        }
        
        return implode("\n", $formattedLines);
    }
    
    /**
     * Split a long line at logical HTML points
     */
    private static function splitLongLine(string $line): array
    {
        $parts = [];
        $remaining = trim($line);
        
        // Split at HTML tag boundaries
        while (strlen($remaining) > self::MAX_LINE_LENGTH) {
            $splitPoint = self::findBestSplitPoint($remaining);
            
            if ($splitPoint === false || $splitPoint < 50) {
                // Force split if no good point found
                $splitPoint = self::MAX_LINE_LENGTH;
            }
            
            $parts[] = substr($remaining, 0, $splitPoint);
            $remaining = trim(substr($remaining, $splitPoint));
        }
        
        if (!empty($remaining)) {
            $parts[] = $remaining;
        }
        
        return $parts;
    }
    
    /**
     * Find the best point to split a long line
     */
    private static function findBestSplitPoint(string $line): int
    {
        $maxLength = self::MAX_LINE_LENGTH;
        
        // Look for HTML tag endings within reasonable distance
        for ($i = $maxLength; $i > 50; $i--) {
            if (isset($line[$i]) && $line[$i] === '>') {
                return $i + 1;
            }
        }
        
        // Look for spaces within reasonable distance
        for ($i = $maxLength; $i > 50; $i--) {
            if (isset($line[$i]) && $line[$i] === ' ') {
                return $i;
            }
        }
        
        return false;
    }
    
    /**
     * Add proper indentation to HTML
     */
    private static function addIndentation(string $html): string
    {
        $lines = explode("\n", $html);
        $indentedLines = [];
        $indentLevel = 0;
        $indentString = '  '; // 2 spaces
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine)) {
                $indentedLines[] = '';
                continue;
            }
            
            // Decrease indent for closing tags
            if (preg_match('/^<\//', $trimmedLine)) {
                $indentLevel = max(0, $indentLevel - 1);
            }
            
            // Add indentation
            $indentedLines[] = str_repeat($indentString, $indentLevel) . $trimmedLine;
            
            // Increase indent for opening tags (but not self-closing)
            if (preg_match('/^<[^\/][^>]*[^\/]>/', $trimmedLine)) {
                $indentLevel++;
            }
        }
        
        return implode("\n", $indentedLines);
    }
    
    /**
     * Fix common HTML structure issues
     */
    private static function fixHtmlStructure(string $html): string
    {
        // Remove extra whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Fix self-closing tags
        $html = preg_replace('/<(input|br|hr|img|meta|link)([^>]*[^\/])>/', '<$1$2 />', $html);
        
        // Ensure proper spacing around attributes
        $html = preg_replace('/(\w)=(["\'])/', '$1=$2', $html);
        
        return $html;
    }
    
    /**
     * Quick format for debugging - formats HTML for better readability
     */
    public static function debugFormat(string $html): string
    {
        return self::formatForm($html, [
            'fix_encoding' => true,
            'format_lines' => true,
            'add_indentation' => true,
            'fix_structure' => false, // Keep original structure for debugging
        ]);
    }
    
    /**
     * Production format - optimized for browser rendering
     */
    public static function productionFormat(string $html): string
    {
        return self::formatForm($html, [
            'fix_encoding' => true,
            'format_lines' => false, // Keep compact for production
            'add_indentation' => false,
            'fix_structure' => true,
            'fix_javascript' => true, // Fix JavaScript issues
        ]);
    }
}