<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

/**
 * SecurityInputValidator
 * 
 * Comprehensive input validation for Canvastack Table security hardening
 * Implements table name validation, column whitelisting, value sanitization
 * 
 * @package Canvastack\Table\Security
 * @version 2.0
 * @author Security Hardening Team
 */
class SecurityInputValidator
{
    /**
     * Allowed table name patterns (alpha_dash, max:64)
     */
    private const TABLE_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const MAX_TABLE_NAME_LENGTH = 64;
    
    /**
     * Allowed column name patterns
     */
    private const COLUMN_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_\.]*$/';
    private const MAX_COLUMN_NAME_LENGTH = 64;
    
    /**
     * SQL injection patterns for detection
     */
    private const SQL_INJECTION_PATTERNS = [
        '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
        '/(\s|^)(or|and)\s+\d+\s*=\s*\d+/i',
        '/(\s|^)(or|and)\s+[\'"].*[\'"](\s*=\s*[\'"].*[\'"])?/i',
        '/--\s*.*$/m',
        '/\/\*.*\*\//s',
        '/;\s*(union|select|insert|update|delete|drop)/i',
        '/\b(script|javascript|vbscript|onload|onerror|onclick)\b/i'
    ];
    
    /**
     * XSS patterns for detection
     */
    private const XSS_PATTERNS = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/<embed\b[^>]*>/i',
        '/expression\s*\(/i',
        '/vbscript:/i'
    ];
    
    /**
     * Whitelisted column names for common database fields
     */
    private array $whitelistedColumns = [
        'id', 'name', 'email', 'created_at', 'updated_at', 'deleted_at',
        'title', 'description', 'status', 'type', 'category_id', 'user_id',
        'slug', 'content', 'price', 'quantity', 'active', 'published',
        'meta_title', 'meta_description', 'sort_order', 'parent_id'
    ];
    
    /**
     * Maximum lengths for different input types
     */
    private const MAX_LENGTHS = [
        'string' => 255,
        'text' => 65535,
        'search' => 100,
        'filter' => 50,
        'order' => 20
    ];
    
    /**
     * Validate table name
     *
     * @param string $tableName
     * @return bool
     * @throws SecurityException
     */
    public function validateTableName(string $tableName): bool
    {
        // Check length
        if (strlen($tableName) > self::MAX_TABLE_NAME_LENGTH) {
            $this->logSecurityViolation('table_name_too_long', [
                'table_name' => $tableName,
                'length' => strlen($tableName),
                'max_length' => self::MAX_TABLE_NAME_LENGTH
            ]);
            throw new SecurityException('Table name exceeds maximum length', [
                'table_name' => $tableName,
                'max_length' => self::MAX_TABLE_NAME_LENGTH
            ]);
        }
        
        // Check pattern
        if (!preg_match(self::TABLE_NAME_PATTERN, $tableName)) {
            $this->logSecurityViolation('invalid_table_name_pattern', [
                'table_name' => $tableName,
                'pattern' => self::TABLE_NAME_PATTERN
            ]);
            throw new SecurityException('Table name contains invalid characters', [
                'table_name' => $tableName,
                'allowed_pattern' => 'alphanumeric and underscores only'
            ]);
        }
        
        // Check for SQL injection attempts
        if ($this->containsSqlInjection($tableName)) {
            $this->logSecurityViolation('sql_injection_attempt_table_name', [
                'table_name' => $tableName,
                'detected_patterns' => $this->getMatchedPatterns($tableName, self::SQL_INJECTION_PATTERNS)
            ]);
            throw new SecurityException('SQL injection attempt detected in table name', [
                'table_name' => $tableName
            ]);
        }
        
        return true;
    }
    
    /**
     * Validate column name with whitelisting
     *
     * @param string $columnName
     * @param array $additionalWhitelist
     * @return bool
     * @throws SecurityException
     */
    public function validateColumnName(string $columnName, array $additionalWhitelist = []): bool
    {
        // Remove table prefix if present (table.column format)
        $cleanColumnName = $this->extractColumnName($columnName);
        
        // Check length
        if (strlen($cleanColumnName) > self::MAX_COLUMN_NAME_LENGTH) {
            $this->logSecurityViolation('column_name_too_long', [
                'column_name' => $columnName,
                'clean_name' => $cleanColumnName,
                'length' => strlen($cleanColumnName)
            ]);
            throw new SecurityException('Column name exceeds maximum length', [
                'column_name' => $columnName
            ]);
        }
        
        // Check pattern
        if (!preg_match(self::COLUMN_NAME_PATTERN, $columnName)) {
            $this->logSecurityViolation('invalid_column_name_pattern', [
                'column_name' => $columnName,
                'pattern' => self::COLUMN_NAME_PATTERN
            ]);
            throw new SecurityException('Column name contains invalid characters', [
                'column_name' => $columnName
            ]);
        }
        
        // Check whitelist
        $allWhitelist = array_merge($this->whitelistedColumns, $additionalWhitelist);
        if (!in_array($cleanColumnName, $allWhitelist) && !$this->isValidDynamicColumn($cleanColumnName)) {
            $this->logSecurityViolation('column_not_whitelisted', [
                'column_name' => $columnName,
                'clean_name' => $cleanColumnName,
                'whitelist' => $allWhitelist
            ]);
            throw new SecurityException('Column name not in whitelist', [
                'column_name' => $columnName,
                'allowed_columns' => $allWhitelist
            ]);
        }
        
        // Check for SQL injection
        if ($this->containsSqlInjection($columnName)) {
            $this->logSecurityViolation('sql_injection_attempt_column_name', [
                'column_name' => $columnName,
                'detected_patterns' => $this->getMatchedPatterns($columnName, self::SQL_INJECTION_PATTERNS)
            ]);
            throw new SecurityException('SQL injection attempt detected in column name', [
                'column_name' => $columnName
            ]);
        }
        
        return true;
    }
    
    /**
     * Sanitize and validate input values
     *
     * @param mixed $value
     * @param string $type
     * @param int|null $maxLength
     * @return mixed
     * @throws SecurityException
     */
    public function sanitizeValue($value, string $type = 'string', ?int $maxLength = null)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        
        // Convert to string for validation
        $stringValue = (string) $value;
        
        // Check length
        $maxLen = $maxLength ?? self::MAX_LENGTHS[$type] ?? self::MAX_LENGTHS['string'];
        if (strlen($stringValue) > $maxLen) {
            $this->logSecurityViolation('input_value_too_long', [
                'value' => substr($stringValue, 0, 100) . '...',
                'type' => $type,
                'length' => strlen($stringValue),
                'max_length' => $maxLen
            ]);
            throw new SecurityException('Input value exceeds maximum length', [
                'type' => $type,
                'max_length' => $maxLen
            ]);
        }
        
        // Check for SQL injection
        if ($this->containsSqlInjection($stringValue)) {
            $this->logSecurityViolation('sql_injection_attempt_value', [
                'value' => substr($stringValue, 0, 100) . '...',
                'type' => $type,
                'detected_patterns' => $this->getMatchedPatterns($stringValue, self::SQL_INJECTION_PATTERNS)
            ]);
            throw new SecurityException('SQL injection attempt detected in input value', [
                'type' => $type
            ]);
        }
        
        // Check for XSS
        if ($this->containsXss($stringValue)) {
            $this->logSecurityViolation('xss_attempt_value', [
                'value' => substr($stringValue, 0, 100) . '...',
                'type' => $type,
                'detected_patterns' => $this->getMatchedPatterns($stringValue, self::XSS_PATTERNS)
            ]);
            throw new SecurityException('XSS attempt detected in input value', [
                'type' => $type
            ]);
        }
        
        // Sanitize based on type
        return $this->applySanitization($value, $type);
    }
    
    /**
     * Validate array input with comprehensive checks
     *
     * @param array $inputArray
     * @param array $rules
     * @return array
     * @throws SecurityException
     */
    public function validateArrayInput(array $inputArray, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($inputArray as $key => $value) {
            // Validate key
            if (!$this->isValidArrayKey($key)) {
                $this->logSecurityViolation('invalid_array_key', [
                    'key' => $key,
                    'value' => is_string($value) ? substr($value, 0, 50) : gettype($value)
                ]);
                throw new SecurityException('Invalid array key detected', [
                    'key' => $key
                ]);
            }
            
            // Get rules for this key
            $keyRules = $rules[$key] ?? ['type' => 'string'];
            
            // Validate and sanitize value
            if (is_array($value)) {
                $sanitized[$key] = $this->validateArrayInput($value, $keyRules);
            } else {
                $sanitized[$key] = $this->sanitizeValue(
                    $value, 
                    $keyRules['type'] ?? 'string',
                    $keyRules['max_length'] ?? null
                );
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Check if string contains SQL injection patterns
     *
     * @param string $input
     * @return bool
     */
    private function containsSqlInjection(string $input): bool
    {
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if string contains XSS patterns
     *
     * @param string $input
     * @return bool
     */
    private function containsXss(string $input): bool
    {
        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get matched patterns for logging
     *
     * @param string $input
     * @param array $patterns
     * @return array
     */
    private function getMatchedPatterns(string $input, array $patterns): array
    {
        $matched = [];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches)) {
                $matched[] = [
                    'pattern' => $pattern,
                    'match' => $matches[0] ?? null
                ];
            }
        }
        return $matched;
    }
    
    /**
     * Extract column name from table.column format
     *
     * @param string $columnName
     * @return string
     */
    private function extractColumnName(string $columnName): string
    {
        if (strpos($columnName, '.') !== false) {
            $parts = explode('.', $columnName);
            return end($parts);
        }
        return $columnName;
    }
    
    /**
     * Check if column name is valid dynamic column
     *
     * @param string $columnName
     * @return bool
     */
    private function isValidDynamicColumn(string $columnName): bool
    {
        // Allow columns that end with common suffixes
        $allowedSuffixes = ['_id', '_at', '_by', '_count', '_sum', '_avg', '_max', '_min'];
        
        foreach ($allowedSuffixes as $suffix) {
            if (str_ends_with($columnName, $suffix)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if array key is valid
     *
     * @param mixed $key
     * @return bool
     */
    private function isValidArrayKey($key): bool
    {
        if (!is_string($key) && !is_int($key)) {
            return false;
        }
        
        $stringKey = (string) $key;
        
        // Check length
        if (strlen($stringKey) > 64) {
            return false;
        }
        
        // Check for suspicious patterns
        if ($this->containsSqlInjection($stringKey) || $this->containsXss($stringKey)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Apply sanitization based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function applySanitization($value, string $type)
    {
        switch ($type) {
            case 'string':
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                
            case 'text':
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                
            case 'search':
                // Remove special SQL characters but allow wildcards
                return preg_replace('/[^\w\s%*.-]/', '', (string) $value);
                
            case 'filter':
                // More restrictive for filter values
                return preg_replace('/[^\w\s.-]/', '', (string) $value);
                
            case 'order':
                // Only allow asc/desc and column names
                $clean = preg_replace('/[^\w.]/', '', (string) $value);
                return in_array(strtolower($clean), ['asc', 'desc']) ? $clean : 'asc';
                
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) ?: 0;
                
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) ?: 0.0;
                
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                
            default:
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Log security violation
     *
     * @param string $type
     * @param array $context
     */
    private function logSecurityViolation(string $type, array $context = []): void
    {
        $logContext = array_merge([
            'violation_type' => $type,
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'url' => request()->url(),
            'method' => request()->method()
        ], $context);
        
        Log::channel('security')->warning("Security input validation violation: {$type}", $logContext);
    }
    
    /**
     * Add custom column to whitelist
     *
     * @param string|array $columns
     */
    public function addWhitelistedColumns($columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->whitelistedColumns = array_merge($this->whitelistedColumns, $columns);
    }
    
    /**
     * Get current whitelisted columns
     *
     * @return array
     */
    public function getWhitelistedColumns(): array
    {
        return $this->whitelistedColumns;
    }
    
    /**
     * Validate SQL-safe patterns
     *
     * @param string $input
     * @param string $context
     * @return bool
     * @throws SecurityException
     */
    public function validateSqlSafePattern(string $input, string $context = 'general'): bool
    {
        // Check for dangerous SQL keywords
        $dangerousKeywords = [
            'drop', 'truncate', 'delete', 'insert', 'update', 'create', 'alter',
            'exec', 'execute', 'sp_', 'xp_', 'cmdshell', 'openrowset', 'openquery'
        ];
        
        $lowerInput = strtolower($input);
        foreach ($dangerousKeywords as $keyword) {
            if (strpos($lowerInput, $keyword) !== false) {
                $this->logSecurityViolation('dangerous_sql_keyword', [
                    'input' => $input,
                    'keyword' => $keyword,
                    'context' => $context
                ]);
                throw new SecurityException("Dangerous SQL keyword detected: {$keyword}", [
                    'keyword' => $keyword,
                    'context' => $context
                ]);
            }
        }
        
        return true;
    }
}