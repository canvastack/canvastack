<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

class InputValidator
{
    /**
     * Dangerous patterns that indicate potential attacks
     */
    private static $dangerousPatterns = [
        // XSS patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onerror\s*=/i',
        '/onclick\s*=/i',
        '/onmouseover\s*=/i',
        
        // SQL injection patterns
        '/union\s+select/i',
        '/drop\s+table/i',
        '/delete\s+from/i',
        '/insert\s+into/i',
        '/update\s+.*set/i',
        '/exec\s*\(/i',
        '/\'\s+or\s+\'/i',
        '/\'\s*=\s*\'/i',
        '/--\s*$/m',
        '/\/\*.*\*\//s',
        
        // Path traversal patterns
        '/\.\.\//',
        '/\.\.\\\\/',
        '/\.\.\%2f/i',
        '/\.\.\%5c/i',
        '/%2e%2e%2f/i',
        '/%2e%2e%5c/i',
        
        // Command injection patterns
        '/;\s*rm\s+/i',
        '/;\s*cat\s+/i',
        '/;\s*ls\s+/i',
        '/\|\s*nc\s+/i',
        '/`.*`/',
        '/\$\(.*\)/',
    ];

    /**
     * Validate input for potential security threats
     */
    public static function validateInput($input, $fieldName = null)
    {
        if (!is_string($input)) {
            return true; // Non-string inputs are generally safe
        }

        foreach (self::$dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                SecurityLogger::logSecurityEvent('DANGEROUS_INPUT_DETECTED', [
                    'field' => $fieldName,
                    'pattern' => $pattern,
                    'input_preview' => substr($input, 0, 100),
                    'severity' => 'HIGH'
                ]);
                
                throw new \InvalidArgumentException("Potentially dangerous input detected in field: {$fieldName}");
            }
        }

        return true;
    }

    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename($filename)
    {
        // Extract extension safely
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';
        $name = $pathInfo['filename'] ?? 'file';
        
        // Remove dangerous characters
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = trim($name, '._-');
        
        // Prevent empty filename
        if (empty($name)) {
            $name = 'upload_' . bin2hex(random_bytes(8));
        }
        
        // Limit length
        $name = substr($name, 0, 100);
        
        return $name . ($extension ? '.' . $extension : '');
    }

    /**
     * Validate and sanitize HTML content
     */
    public static function sanitizeHtml($html)
    {
        // Log dangerous patterns but don't throw exception for HTML sanitization
        try {
            self::validateInput($html, 'html_content');
        } catch (\Exception $e) {
            // Log the attempt but continue with sanitization
            SecurityLogger::logSecurityEvent('HTML_SANITIZATION_REQUIRED', [
                'reason' => $e->getMessage(),
                'input_preview' => substr($html, 0, 100),
                'severity' => 'MEDIUM'
            ]);
        }
        
        // Use HTMLPurifier if available, otherwise basic sanitization
        if (class_exists('\HTMLPurifier')) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'b,i,u,strong,em,p,br,ul,ol,li,a[href],span[class]');
            $config->set('HTML.AllowedAttributes', 'href,class');
            $config->set('HTML.AllowedSchemes', 'http,https,mailto');
            $config->set('HTML.Nofollow', true);
            
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }
        
        // Fallback: basic sanitization
        return htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validate database table/column names
     */
    public static function validateDatabaseIdentifier($identifier, $type = 'table')
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            SecurityLogger::logSecurityEvent('INVALID_DB_IDENTIFIER', [
                'identifier' => $identifier,
                'type' => $type,
                'severity' => 'HIGH'
            ]);
            
            throw new \InvalidArgumentException("Invalid {$type} name: {$identifier}");
        }
        
        return true;
    }

    /**
     * Validate URL for safe redirects
     */
    public static function validateUrl($url)
    {
        // Check for dangerous protocols
        $dangerousProtocols = ['javascript:', 'vbscript:', 'data:', 'file:'];
        
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                SecurityLogger::logSecurityEvent('DANGEROUS_URL_DETECTED', [
                    'url' => $url,
                    'protocol' => $protocol,
                    'severity' => 'HIGH'
                ]);
                
                throw new \InvalidArgumentException("Dangerous URL protocol detected");
            }
        }
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL format");
        }
        
        return true;
    }
}