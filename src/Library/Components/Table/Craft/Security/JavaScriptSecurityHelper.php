<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Security;

/**
 * JavaScript Security Helper Class
 * 
 * Provides secure output encoding for JavaScript contexts to prevent XSS attacks.
 * Implements comprehensive security measures for dynamic JavaScript generation.
 */
class JavaScriptSecurityHelper
{
    /**
     * JSON encoding flags for security
     */
    const SECURE_JSON_FLAGS = JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES;

    /**
     * Encode data safely for JavaScript context
     *
     * @param mixed $data Data to encode
     * @param bool $forAttribute Whether output is for HTML attribute context
     * @return string Safely encoded JSON string
     */
    public static function encodeForJS($data, bool $forAttribute = false): string
    {
        $encoded = json_encode($data, self::SECURE_JSON_FLAGS);
        
        if ($forAttribute) {
            // Additional escaping for HTML attribute context
            $encoded = htmlspecialchars($encoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $encoded;
    }

    /**
     * Encode string for safe inclusion in JavaScript strings
     *
     * @param string $str String to encode
     * @return string Safely encoded string
     */
    public static function encodeString(string $str): string
    {
        // Comprehensive XSS prevention patterns
        $dangerous_patterns = [
            // Script tags
            '/<script/i' => '&lt;script',
            '/<\/script/i' => '&lt;/script',
            '/<iframe/i' => '&lt;iframe',
            '/<\/iframe/i' => '&lt;/iframe',
            
            // JavaScript/VBScript URLs
            '/javascript:/i' => 'javascript\\x3a',
            '/vbscript:/i' => 'vbscript\\x3a',
            '/data:/i' => 'data\\x3a',
            
            // Event handlers
            '/on\w+\s*=/i' => '',
            '/onerror\s*=/i' => '',
            '/onload\s*=/i' => '',
            '/onclick\s*=/i' => '',
            
            // JavaScript functions
            '/alert\s*\(/i' => 'alert\\x28',
            '/confirm\s*\(/i' => 'confirm\\x28',
            '/prompt\s*\(/i' => 'prompt\\x28',
            '/eval\s*\(/i' => 'eval\\x28',
            '/setTimeout\s*\(/i' => 'setTimeout\\x28',
            '/setInterval\s*\(/i' => 'setInterval\\x28',
        ];
        
        // Apply all security replacements
        $encoded = $str;
        foreach ($dangerous_patterns as $pattern => $replacement) {
            $encoded = preg_replace($pattern, $replacement, $encoded);
        }
        
        // Escape for JavaScript string context
        $encoded = addcslashes($encoded, "\x00..\x1f\"\\");
        
        return $encoded;
    }

    /**
     * Generate secure CSRF token for JavaScript
     *
     * @return string Safely encoded CSRF token
     */
    public static function getSecureCSRFToken(): string
    {
        $token = csrf_token();
        return self::encodeString($token);
    }

    /**
     * Create safe JavaScript variable assignment
     *
     * @param string $varName Variable name (will be validated)
     * @param mixed $value Variable value
     * @param bool $global Whether to create global variable
     * @return string Safe JavaScript variable assignment
     */
    public static function createSafeVariable(string $varName, $value, bool $global = false): string
    {
        // Validate variable name to prevent injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $varName)) {
            throw new \InvalidArgumentException("Invalid JavaScript variable name: {$varName}");
        }
        
        $encodedValue = self::encodeForJS($value);
        $scope = $global ? 'window.' : 'var ';
        
        if ($global) {
            return "{$scope}{$varName} = {$encodedValue};";
        } else {
            return "{$scope}{$varName} = {$encodedValue};";
        }
    }

    /**
     * Create safe JavaScript object property assignment
     *
     * @param string $objectName Object name
     * @param string $property Property name
     * @param mixed $value Property value
     * @return string Safe JavaScript property assignment
     */
    public static function createSafePropertyAssignment(string $objectName, string $property, $value): string
    {
        // Validate object and property names
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $objectName)) {
            throw new \InvalidArgumentException("Invalid JavaScript object name: {$objectName}");
        }
        
        $encodedProperty = self::encodeString($property);
        $encodedValue = self::encodeForJS($value);
        
        return "{$objectName}[\"{$encodedProperty}\"] = {$encodedValue};";
    }

    /**
     * Validate and encode ID for safe use in JavaScript
     *
     * @param string $id HTML/DataTable ID
     * @return string Safely encoded ID
     */
    public static function encodeId(string $id): string
    {
        // Allow alphanumeric, hyphens, underscores
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $id)) {
            throw new \InvalidArgumentException("Invalid ID format for JavaScript: {$id}");
        }
        
        return htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Create secure AJAX data function
     *
     * @param string $token CSRF token
     * @param array $extraData Additional data to include
     * @return string Safe JavaScript function string
     */
    public static function createSecureAjaxDataFunction(string $token, array $extraData = []): string
    {
        $safeToken = self::encodeString($token);
        $safeExtraData = self::encodeForJS($extraData);
        
        $functionBody = "function(data) {
            var postData = {
                draw: data.draw,
                start: data.start,
                length: data.length,
                search: data.search,
                order: data.order,
                columns: data.columns,
                _token: \"{$safeToken}\"
            };
            
            // Merge extra data safely
            var extraData = {$safeExtraData};
            if (extraData && typeof extraData === 'object') {
                Object.keys(extraData).forEach(function(key) {
                    postData[key] = extraData[key];
                });
            }
            
            return postData;
        }";
        
        return $functionBody;
    }

    /**
     * Create secure console.log statement for debugging
     *
     * @param string $message Debug message
     * @param mixed $data Data to log
     * @return string Safe console.log statement
     */
    public static function createSecureConsoleLog(string $message, $data): string
    {
        $safeMessage = self::encodeString($message);
        $safeData = self::encodeForJS($data);
        
        return "console.log(\"{$safeMessage}\", {$safeData});";
    }

    /**
     * Sanitize DataTable configuration for safe JavaScript output
     *
     * @param array $config DataTable configuration
     * @return array Sanitized configuration
     */
    public static function sanitizeDataTableConfig(array $config): array
    {
        // Deep sanitization of configuration values
        return array_map(function($value) {
            if (is_string($value)) {
                // Use comprehensive string encoding
                $value = self::encodeString($value);
            } elseif (is_array($value)) {
                $value = self::sanitizeDataTableConfig($value);
            }
            
            return $value;
        }, $config);
    }
}