<?php

namespace Tests\Helpers;

use Canvastack\Canvastack\Library\Components\Table\Objects;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test Helper Functions for Table Components
 * 
 * Provides utility functions for setting up table instances, configurations,
 * and test scenarios to make testing easier and more consistent.
 * 
 * Validates: Requirement 25 - Testing Support
 */
class TableTestHelper
{
    /**
     * Create a basic table instance with default configuration
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createBasicTable(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email'],
        array $options = []
    ): Objects {
        $table = new Objects();
        $table->setName($tableName);
        $table->setFields($fields);
        
        // Apply optional configurations
        if (isset($options['server_side'])) {
            $table->setServerSide($options['server_side']);
        }
        
        if (isset($options['actions'])) {
            $table->setActions($options['actions']);
        }
        
        if (isset($options['sortable'])) {
            $table->sortable($options['sortable']);
        }
        
        if (isset($options['searchable'])) {
            $table->searchable($options['searchable']);
        }
        
        if (isset($options['where'])) {
            foreach ($options['where'] as $condition) {
                $table->where($condition[0], $condition[1], $condition[2]);
            }
        }
        
        if (isset($options['orderby'])) {
            $table->orderby($options['orderby'][0], $options['orderby'][1] ?? 'asc');
        }
        
        return $table;
    }
    
    /**
     * Create a table with server-side processing enabled
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createServerSideTable(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email'],
        array $options = []
    ): Objects {
        $options['server_side'] = true;
        return self::createBasicTable($tableName, $fields, $options);
    }
    
    /**
     * Create a table with action buttons
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $actions Action button names
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createTableWithActions(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email'],
        array $actions = [
            TableConstants::ACTION_VIEW,
            TableConstants::ACTION_EDIT,
            TableConstants::ACTION_DELETE
        ],
        array $options = []
    ): Objects {
        $options['actions'] = $actions;
        return self::createBasicTable($tableName, $fields, $options);
    }
    
    /**
     * Create a table with sorting and searching enabled
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $sortableColumns Sortable column names
     * @param array $searchableColumns Searchable column names
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createTableWithSortingAndSearching(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email'],
        array $sortableColumns = ['name', 'email'],
        array $searchableColumns = ['name', 'email'],
        array $options = []
    ): Objects {
        $options['sortable'] = $sortableColumns;
        $options['searchable'] = $searchableColumns;
        return self::createBasicTable($tableName, $fields, $options);
    }
    
    /**
     * Create a table with filters
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $filters Where conditions [[column, operator, value], ...]
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createTableWithFilters(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email', 'status'],
        array $filters = [['status', '=', 'active']],
        array $options = []
    ): Objects {
        $options['where'] = $filters;
        return self::createBasicTable($tableName, $fields, $options);
    }
    
    /**
     * Create a table with formula columns
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param array $formulas Formula definitions [[name, label, fields, operator, value], ...]
     * @param array $options Additional options
     * @return Objects Configured table instance
     */
    public static function createTableWithFormulas(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'salary'],
        array $formulas = [
            ['annual_salary', 'Annual Salary', ['salary'], '*', 12]
        ],
        array $options = []
    ): Objects {
        $table = self::createBasicTable($tableName, $fields, $options);
        
        foreach ($formulas as $formula) {
            $table->formula(
                $formula[0], // name
                $formula[1], // label
                $formula[2], // fields
                $formula[3], // operator
                $formula[4] ?? null // value
            );
        }
        
        return $table;
    }
    
    /**
     * Create a Datatables instance for server-side processing
     * 
     * @return Datatables Datatables instance
     */
    public static function createDatatables(): Datatables
    {
        return new Datatables();
    }
    
    /**
     * Create a DataTables request array
     * 
     * @param int $draw Request counter
     * @param int $start Pagination start
     * @param int $length Page length
     * @param array $columns Column definitions
     * @param array $order Ordering definitions
     * @param array $search Search parameters
     * @return array DataTables request array
     */
    public static function createDatatablesRequest(
        int $draw = 1,
        int $start = 0,
        int $length = 10,
        array $columns = [
            ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
            ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
            ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
        ],
        array $order = [['column' => 0, 'dir' => 'asc']],
        array $search = ['value' => '', 'regex' => false]
    ): array {
        return [
            'draw' => $draw,
            'start' => $start,
            'length' => $length,
            'columns' => $columns,
            'order' => $order,
            'search' => $search,
        ];
    }
    
    /**
     * Create a DataTables data object
     * 
     * @param string $tableName Table name
     * @param array $fields Column fields
     * @param bool $serverSide Server-side processing enabled
     * @param array $options Additional options
     * @return object DataTables data object
     */
    public static function createDatatablesData(
        string $tableName = 'test_users',
        array $fields = ['id', 'name', 'email'],
        bool $serverSide = true,
        array $options = []
    ): object {
        $data = [
            'table_name' => $tableName,
            'fields' => $fields,
            'server_side' => $serverSide,
        ];
        
        // Add optional configurations
        if (isset($options['actions'])) {
            $data['actions'] = $options['actions'];
        }
        
        if (isset($options['action_url'])) {
            $data['action_url'] = $options['action_url'];
        }
        
        if (isset($options['where'])) {
            $data['where'] = $options['where'];
        }
        
        if (isset($options['orderby'])) {
            $data['orderby'] = $options['orderby'];
        }
        
        return (object) $data;
    }
    
    /**
     * Get table internal variables using reflection
     * 
     * @param Objects $table Table instance
     * @return array Table variables
     */
    public static function getTableVariables(Objects $table): array
    {
        $reflection = new \ReflectionClass($table);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        return $property->getValue($table);
    }
    
    /**
     * Get table internal conditions using reflection
     * 
     * @param Objects $table Table instance
     * @return array Table conditions
     */
    public static function getTableConditions(Objects $table): array
    {
        $reflection = new \ReflectionClass($table);
        $property = $reflection->getProperty('conditions');
        $property->setAccessible(true);
        return $property->getValue($table);
    }
    
    /**
     * Assert table has expected configuration
     * 
     * @param Objects $table Table instance
     * @param array $expectedConfig Expected configuration
     * @return void
     */
    public static function assertTableConfiguration(Objects $table, array $expectedConfig): void
    {
        $variables = self::getTableVariables($table);
        
        foreach ($expectedConfig as $key => $expectedValue) {
            if (!isset($variables[$key])) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Table configuration key '$key' not found"
                );
            }
            
            if ($variables[$key] !== $expectedValue) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Table configuration '$key' expected to be " . var_export($expectedValue, true) .
                    " but got " . var_export($variables[$key], true)
                );
            }
        }
    }
    
    /**
     * Assert DataTables response has valid structure
     * 
     * @param array $response DataTables response
     * @return void
     */
    public static function assertValidDatatablesResponse(array $response): void
    {
        $requiredKeys = ['draw', 'recordsTotal', 'recordsFiltered', 'data'];
        
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "DataTables response missing required key: $key"
                );
            }
        }
        
        if (!is_int($response['draw'])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "DataTables response 'draw' must be an integer"
            );
        }
        
        if (!is_int($response['recordsTotal'])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "DataTables response 'recordsTotal' must be an integer"
            );
        }
        
        if (!is_int($response['recordsFiltered'])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "DataTables response 'recordsFiltered' must be an integer"
            );
        }
        
        if (!is_array($response['data'])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "DataTables response 'data' must be an array"
            );
        }
    }
}
