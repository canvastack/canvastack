<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

/**
 * Secure Query Builder for CanvaStack Form System
 * 
 * Provides SQL injection protection for dynamic form queries
 * while maintaining flexibility for form synchronization features.
 * 
 * @package Canvastack\Form\Security
 * @version 2.0.0
 * @author CanvaStack Security Team
 */
class SecureQueryBuilder
{
    /**
     * Allowed table name pattern
     * @var string
     */
    private static $tablePattern = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    
    /**
     * Allowed column name pattern
     * @var string
     */
    private static $columnPattern = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    
    /**
     * Maximum query result limit
     * @var int
     */
    private static $maxLimit = 1000;
    
    /**
     * Build secure sync query for form select elements
     * 
     * @param string $table Table name
     * @param string $valueColumn Column for option values
     * @param string $labelColumn Column for option labels
     * @param array $conditions WHERE conditions
     * @param array $options Additional query options
     * @return \Illuminate\Support\Collection Query results
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public static function buildSyncQuery($table, $valueColumn, $labelColumn, $conditions = [], $options = [])
    {
        // Validate parameters
        self::validateQueryParams([
            'table' => $table,
            'value_column' => $valueColumn,
            'label_column' => $labelColumn,
            'conditions' => $conditions
        ]);
        
        try {
            $query = DB::table($table)->select($valueColumn, $labelColumn);
            
            // Apply conditions safely
            foreach ($conditions as $field => $value) {
                self::validateColumnName($field);
                
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, '=', $value);
                }
            }
            
            // Apply additional options
            if (isset($options['order_by'])) {
                self::validateColumnName($options['order_by']);
                $direction = isset($options['order_direction']) && 
                           strtolower($options['order_direction']) === 'desc' ? 'desc' : 'asc';
                $query->orderBy($options['order_by'], $direction);
            }
            
            // Apply limit
            $limit = isset($options['limit']) ? min((int)$options['limit'], self::$maxLimit) : self::$maxLimit;
            $query->limit($limit);
            
            // Execute query
            $results = $query->get();
            
            // Log query for security monitoring
            self::logSecureQuery($table, $valueColumn, $labelColumn, $conditions, $results->count());
            
            return $results;
            
        } catch (\Exception $e) {
            // Log security incident
            \Log::error('SECURITY: Secure query failed', [
                'table' => $table,
                'value_column' => $valueColumn,
                'label_column' => $labelColumn,
                'conditions' => $conditions,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            throw new \InvalidArgumentException('Query execution failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate query parameters for security
     * 
     * @param array $params Query parameters
     * @return bool True if valid
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public static function validateQueryParams($params)
    {
        $required = ['table', 'value_column', 'label_column'];
        
        // Check required parameters
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: {$param}");
            }
        }
        
        // Validate table name
        self::validateTableName($params['table']);
        
        // Validate column names
        self::validateColumnName($params['value_column']);
        self::validateColumnName($params['label_column']);
        
        // Validate conditions
        if (isset($params['conditions']) && is_array($params['conditions'])) {
            foreach ($params['conditions'] as $field => $value) {
                self::validateColumnName($field);
                self::validateConditionValue($value);
            }
        }
        
        return true;
    }
    
    /**
     * Validate table name against SQL injection
     * 
     * @param string $tableName Table name to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException If table name is invalid
     */
    private static function validateTableName($tableName)
    {
        if (!is_string($tableName)) {
            throw new \InvalidArgumentException("Table name must be a string");
        }
        
        if (!preg_match(self::$tablePattern, $tableName)) {
            throw new \InvalidArgumentException("Invalid table name: {$tableName}");
        }
        
        // Check for SQL injection patterns
        $dangerousPatterns = [
            '/\s*(DROP|DELETE|UPDATE|INSERT|CREATE|ALTER|TRUNCATE)\s+/i',
            '/--/',
            '/\/\*.*\*\//',
            '/;/',
            '/\s+(OR|AND)\s+/i',
            '/UNION\s+SELECT/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $tableName)) {
                throw new \InvalidArgumentException("Potentially dangerous table name: {$tableName}");
            }
        }
        
        // Check if table exists (optional security check)
        if (!self::tableExists($tableName)) {
            throw new \InvalidArgumentException("Table does not exist: {$tableName}");
        }
        
        return true;
    }
    
    /**
     * Validate column name against SQL injection
     * 
     * @param string $columnName Column name to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException If column name is invalid
     */
    private static function validateColumnName($columnName)
    {
        if (!is_string($columnName)) {
            throw new \InvalidArgumentException("Column name must be a string");
        }
        
        if (!preg_match(self::$columnPattern, $columnName)) {
            throw new \InvalidArgumentException("Invalid column name: {$columnName}");
        }
        
        // Check for SQL injection patterns
        $dangerousPatterns = [
            '/\s*(DROP|DELETE|UPDATE|INSERT|CREATE|ALTER)\s+/i',
            '/--/',
            '/\/\*.*\*\//',
            '/;/',
            '/\s+(OR|AND)\s+/i',
            '/UNION\s+SELECT/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $columnName)) {
                throw new \InvalidArgumentException("Potentially dangerous column name: {$columnName}");
            }
        }
        
        return true;
    }
    
    /**
     * Validate condition value
     * 
     * @param mixed $value Condition value to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException If value is invalid
     */
    private static function validateConditionValue($value)
    {
        // Allow basic data types
        if (is_null($value) || is_bool($value) || is_numeric($value)) {
            return true;
        }
        
        if (is_string($value)) {
            // Check for SQL injection patterns in string values
            $dangerousPatterns = [
                '/\s*(DROP|DELETE|UPDATE|INSERT|CREATE|ALTER)\s+/i',
                '/--/',
                '/\/\*.*\*\//',
                '/;\s*(DROP|DELETE|UPDATE|INSERT|CREATE|ALTER)\s+/i',
                '/UNION\s+SELECT/i'
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    throw new \InvalidArgumentException("Potentially dangerous condition value");
                }
            }
            
            return true;
        }
        
        if (is_array($value)) {
            foreach ($value as $item) {
                self::validateConditionValue($item);
            }
            return true;
        }
        
        throw new \InvalidArgumentException("Invalid condition value type");
    }
    
    /**
     * Check if table exists in database
     * 
     * @param string $tableName Table name to check
     * @return bool True if table exists
     */
    private static function tableExists($tableName)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Build secure query for form synchronization
     * 
     * @param array $queryParams Encrypted query parameters
     * @return array Query results
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public static function executeSecureSync($queryParams)
    {
        try {
            // Decrypt and validate parameters
            $params = json_decode(decrypt($queryParams), true);
            
            if (!is_array($params)) {
                throw new \InvalidArgumentException("Invalid query parameters");
            }
            
            // Extract parameters
            $table = $params['table'] ?? '';
            $valueColumn = $params['value_column'] ?? 'id';
            $labelColumn = $params['label_column'] ?? 'name';
            $conditions = $params['conditions'] ?? [];
            $options = $params['options'] ?? [];
            
            // Execute secure query
            $results = self::buildSyncQuery($table, $valueColumn, $labelColumn, $conditions, $options);
            
            // Format results for form synchronization
            $formattedResults = [];
            foreach ($results as $row) {
                $formattedResults[] = [
                    'value' => $row->$valueColumn,
                    'label' => $row->$labelColumn
                ];
            }
            
            return $formattedResults;
            
        } catch (\Exception $e) {
            \Log::error('SECURITY: Secure sync execution failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            throw new \InvalidArgumentException('Sync execution failed');
        }
    }
    
    /**
     * Log secure query execution for monitoring
     * 
     * @param string $table Table name
     * @param string $valueColumn Value column
     * @param string $labelColumn Label column
     * @param array $conditions Query conditions
     * @param int $resultCount Number of results returned
     */
    private static function logSecureQuery($table, $valueColumn, $labelColumn, $conditions, $resultCount)
    {
        \Log::info('Secure query executed', [
            'table' => $table,
            'value_column' => $valueColumn,
            'label_column' => $labelColumn,
            'conditions_count' => count($conditions),
            'result_count' => $resultCount,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()
        ]);
    }
    
    /**
     * Create safe query parameters for encryption
     * 
     * @param string $table Table name
     * @param string $valueColumn Value column
     * @param string $labelColumn Label column
     * @param array $conditions Query conditions
     * @param array $options Additional options
     * @return string Encrypted query parameters
     */
    public static function createSecureQueryParams($table, $valueColumn, $labelColumn, $conditions = [], $options = [])
    {
        // Validate all parameters first
        self::validateQueryParams([
            'table' => $table,
            'value_column' => $valueColumn,
            'label_column' => $labelColumn,
            'conditions' => $conditions
        ]);
        
        $params = [
            'table' => $table,
            'value_column' => $valueColumn,
            'label_column' => $labelColumn,
            'conditions' => $conditions,
            'options' => $options,
            'created_at' => now()->timestamp,
            'user_id' => auth()->id()
        ];
        
        return encrypt(json_encode($params));
    }
}