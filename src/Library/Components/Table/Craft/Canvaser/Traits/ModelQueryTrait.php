<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * ModelQueryTrait
 *
 * Handles model/sql source selection and table-level configs (server-side, orderby, naming, connection).
 * Provides dynamic configuration support without hardcoded values.
 *
 * @version 2.0.1
 * @author Canvastack Team
 */
trait ModelQueryTrait
{
    /**
     * Configuration constants
     */
    private const FUNCTION_SEPARATOR = '::';
    private const DEFAULT_TABLE_TYPE = 'self::table';
    private const SQL_MODEL_TYPE = 'sql';
    private const DEFAULT_ORDER_DIRECTION = 'asc';
    
    /**
     * Valid sort directions
     */
    private const VALID_ORDER_DIRECTIONS = ['asc', 'desc', 'ASC', 'DESC'];

    /**
     * Set datatable type with dynamic configuration support
     *
     * @param bool $set Enable/disable datatable mode
     * @return $this For method chaining
     */
    public function setDatatableType($set = true)
    {
        $this->setDatatable = $set;
        
        if (true !== $this->setDatatable) {
            // Use configuration-based default table type
            $this->tableType = $this->getConfigValue('canvastack.table.default_type', self::DEFAULT_TABLE_TYPE);
        }
        
        $this->element_name['table'] = $this->tableType;
        
        return $this;
    }

    /**
     * Set table name with validation
     *
     * @param string $table_name The table name
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function setName($table_name)
    {
        if (empty($table_name) || !is_string($table_name)) {
            throw new InvalidArgumentException('Table name must be a non-empty string');
        }
        
        $this->variables['table_name'] = $table_name;
        
        return $this;
    }

    /**
     * Set table fields with validation
     *
     * @param array|string $fields Table fields
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function setFields($fields)
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('Fields cannot be empty');
        }
        
        // Convert string to array if needed
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }
        
        if (!is_array($fields)) {
            throw new InvalidArgumentException('Fields must be an array or comma-separated string');
        }
        
        $this->variables['table_fields'] = $fields;
        
        return $this;
    }

    /**
     * Set model with validation
     *
     * @param string|object $model Model class name or instance
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function model($model)
    {
        if (empty($model)) {
            throw new InvalidArgumentException('Model cannot be empty');
        }
        
        $this->variables['table_data_model'] = $model;
        
        return $this;
    }

    /**
     * Run model with dynamic connection resolution
     *
     * @param object|string $model_object Model instance or class name
     * @param string $function_name Function name (supports namespace separation)
     * @param bool $strict Enable strict mode
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function runModel($model_object, $function_name, $strict = false)
    {
        // Validate inputs
        if (empty($model_object)) {
            throw new InvalidArgumentException('Model object cannot be empty');
        }
        
        if (empty($function_name) || !is_string($function_name)) {
            throw new InvalidArgumentException('Function name must be a non-empty string');
        }
        
        // Get dynamic database connection
        $connection = $this->resolveConnection();
        
        // Parse function name with configurable separator
        $separator = $this->getConfigValue('canvastack.function.separator', self::FUNCTION_SEPARATOR);
        $modelFunction = $function_name;
        $tableFunction = $function_name;
        
        if (strpos($function_name, $separator) !== false) {
            $split = explode($separator, $function_name, 2); // Limit to 2 parts for safety
            if (count($split) === 2) {
                $modelFunction = $split[0];
                $tableFunction = "{$split[1]}_{$split[0]}";
            }
        }

        $this->variables['model_processing'] = [
            'model' => $model_object,
            'function' => $modelFunction,
            'connection' => $connection,
            'table' => $tableFunction,
            'strict' => $strict,
            'separator' => $separator,
            'timestamp' => now(),
        ];
        
        return $this;
    }

    /**
     * Set SQL query with model type
     *
     * @param string $sql SQL query string
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function query($sql)
    {
        if (empty($sql) || !is_string($sql)) {
            throw new InvalidArgumentException('SQL query must be a non-empty string');
        }
        
        $this->variables['query'] = trim($sql);
        $this->model(self::SQL_MODEL_TYPE);
        
        return $this;
    }

    /**
     * Set server-side processing mode
     *
     * @param bool $server_side Enable server-side processing
     * @return $this For method chaining
     */
    public function setServerSide($server_side = true)
    {
        $this->variables['table_server_side'] = (bool) $server_side;
        
        return $this;
    }

    /**
     * Set table ordering with validation
     *
     * @param string $column Column name to order by
     * @param string $order Order direction (asc/desc)
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function orderby($column, $order = self::DEFAULT_ORDER_DIRECTION)
    {
        if (empty($column) || !is_string($column)) {
            throw new InvalidArgumentException('Order column must be a non-empty string');
        }
        
        // Validate order direction
        if (!in_array(strtolower($order), array_map('strtolower', self::VALID_ORDER_DIRECTIONS), true)) {
            throw new InvalidArgumentException(
                'Order direction must be one of: ' . implode(', ', self::VALID_ORDER_DIRECTIONS)
            );
        }
        
        $this->variables['orderby_column'] = [
            'column' => $column,
            'order' => strtolower($order),
            'timestamp' => now(),
        ];
        
        return $this;
    }

    /**
     * Set database connection with validation
     *
     * @param string $db_connection Connection name
     * @return $this For method chaining
     * @throws InvalidArgumentException
     */
    public function connection($db_connection)
    {
        if (empty($db_connection) || !is_string($db_connection)) {
            throw new InvalidArgumentException('Database connection must be a non-empty string');
        }
        
        // Validate connection exists in config
        $availableConnections = array_keys(Config::get('database.connections', []));
        if (!in_array($db_connection, $availableConnections, true)) {
            throw new InvalidArgumentException(
                "Database connection '{$db_connection}' is not configured. Available: " . 
                implode(', ', $availableConnections)
            );
        }
        
        $this->connection = $db_connection;
        
        return $this;
    }

    /**
     * Reset connection to default
     *
     * @return $this For method chaining
     */
    public function resetConnection()
    {
        $this->connection = null;
        
        return $this;
    }

    /**
     * Get current connection name
     *
     * @return string Current connection name
     */
    public function getCurrentConnection()
    {
        return $this->resolveConnection();
    }

    /**
     * Resolve database connection dynamically
     *
     * @return string Resolved connection name
     */
    private function resolveConnection()
    {
        // Priority: 1. Explicitly set connection, 2. Config default, 3. Laravel default
        if (null !== $this->connection) {
            return $this->connection;
        }
        
        // Try Canvastack-specific configuration first
        $configConnection = $this->getConfigValue('canvastack.database.default_connection');
        if ($configConnection) {
            return $configConnection;
        }
        
        // Fall back to Laravel's default database connection
        return Config::get('database.default', 'mysql');
    }

    /**
     * Get configuration value with fallback
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if config not found
     * @return mixed Configuration value
     */
    private function getConfigValue($key, $default = null)
    {
        return Config::get($key, $default);
    }

    /**
     * Get all current table variables for debugging
     *
     * @return array Current variables state
     */
    public function getTableVariables()
    {
        return $this->variables ?? [];
    }

    /**
     * Reset all table variables
     *
     * @return $this For method chaining
     */
    public function resetTableVariables()
    {
        $this->variables = [];
        
        return $this;
    }
}
