<?php

namespace Tests\Fixtures;

use Canvastack\Canvastack\Library\Constants\TableConstants;

/**
 * Test Fixtures for Table Components
 * 
 * Provides pre-configured data fixtures for common table scenarios
 * to make testing easier and more consistent.
 * 
 * Validates: Requirement 25 - Testing Support
 */
class TableFixtures
{
    /**
     * Get simple table configuration
     * 
     * Basic table with minimal configuration for simple tests.
     * 
     * @return array Table configuration
     */
    public static function simpleTableConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'email'],
            'server_side' => false,
            'actions' => false,
        ];
    }
    
    /**
     * Get server-side table configuration
     * 
     * Table with server-side processing enabled for testing
     * DataTables AJAX functionality.
     * 
     * @return array Table configuration
     */
    public static function serverSideTableConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'email', 'age', 'status'],
            'server_side' => true,
            'sortable' => ['name', 'email', 'age'],
            'searchable' => ['name', 'email'],
            'actions' => [
                TableConstants::ACTION_VIEW,
                TableConstants::ACTION_EDIT,
                TableConstants::ACTION_DELETE,
            ],
        ];
    }
    
    /**
     * Get table with relationships configuration
     * 
     * Table with foreign key relationships for testing joins
     * and eager loading.
     * 
     * @return array Table configuration
     */
    public static function tableWithRelationshipsConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'email', 'department_id'],
            'server_side' => true,
            'relationships' => [
                'department' => [
                    'type' => 'belongsTo',
                    'model' => 'App\\Models\\Department',
                    'foreign_key' => 'department_id',
                    'local_key' => 'id',
                    'display_fields' => ['name'],
                    'eager_load' => true,
                ],
            ],
        ];
    }
    
    /**
     * Get table with formulas configuration
     * 
     * Table with calculated columns for testing formula functionality.
     * 
     * @return array Table configuration
     */
    public static function tableWithFormulasConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'salary', 'age'],
            'server_side' => true,
            'formulas' => [
                [
                    'name' => 'annual_salary',
                    'label' => 'Annual Salary',
                    'fields' => ['salary'],
                    'operator' => '*',
                    'value' => 12,
                ],
                [
                    'name' => 'salary_per_age',
                    'label' => 'Salary per Year of Age',
                    'fields' => ['salary', 'age'],
                    'operator' => '/',
                ],
            ],
        ];
    }
    
    /**
     * Get table with filters configuration
     * 
     * Table with pre-applied filters for testing filtering functionality.
     * 
     * @return array Table configuration
     */
    public static function tableWithFiltersConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'email', 'age', 'status'],
            'server_side' => true,
            'where' => [
                ['status', '=', 'active'],
                ['age', '>=', 18],
            ],
            'orderby' => ['name', 'asc'],
        ];
    }
    
    /**
     * Get table with custom actions configuration
     * 
     * Table with custom action buttons for testing action functionality.
     * 
     * @return array Table configuration
     */
    public static function tableWithCustomActionsConfig(): array
    {
        return [
            'table_name' => 'test_users',
            'fields' => ['id', 'name', 'email', 'status'],
            'server_side' => true,
            'actions' => [
                TableConstants::ACTION_VIEW,
                TableConstants::ACTION_EDIT,
                'activate' => [
                    'label' => 'Activate',
                    'icon' => 'fa-check',
                    'url' => '/users/{id}/activate',
                    'method' => 'POST',
                    'confirm' => true,
                    'confirm_message' => 'Are you sure you want to activate this user?',
                ],
                'deactivate' => [
                    'label' => 'Deactivate',
                    'icon' => 'fa-times',
                    'url' => '/users/{id}/deactivate',
                    'method' => 'POST',
                    'confirm' => true,
                ],
            ],
            'action_url' => '/users',
        ];
    }
    
    /**
     * Get sample user data
     * 
     * Sample user records for testing table data processing.
     * 
     * @return array User records
     */
    public static function sampleUserData(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
                'status' => 'active',
                'salary' => 50000.00,
                'department_id' => 1,
                'created_at' => '2024-01-01 10:00:00',
                'updated_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'age' => 25,
                'status' => 'active',
                'salary' => 45000.00,
                'department_id' => 2,
                'created_at' => '2024-01-02 10:00:00',
                'updated_at' => '2024-01-02 10:00:00',
            ],
            [
                'id' => 3,
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'age' => 35,
                'status' => 'inactive',
                'salary' => 60000.00,
                'department_id' => 1,
                'created_at' => '2024-01-03 10:00:00',
                'updated_at' => '2024-01-03 10:00:00',
            ],
            [
                'id' => 4,
                'name' => 'Alice Williams',
                'email' => 'alice@example.com',
                'age' => 28,
                'status' => 'active',
                'salary' => 55000.00,
                'department_id' => 2,
                'created_at' => '2024-01-04 10:00:00',
                'updated_at' => '2024-01-04 10:00:00',
            ],
            [
                'id' => 5,
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'age' => 40,
                'status' => 'active',
                'salary' => 70000.00,
                'department_id' => 1,
                'created_at' => '2024-01-05 10:00:00',
                'updated_at' => '2024-01-05 10:00:00',
            ],
        ];
    }
    
    /**
     * Get sample department data
     * 
     * Sample department records for testing relationships.
     * 
     * @return array Department records
     */
    public static function sampleDepartmentData(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Engineering',
                'created_at' => '2024-01-01 09:00:00',
                'updated_at' => '2024-01-01 09:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Marketing',
                'created_at' => '2024-01-01 09:00:00',
                'updated_at' => '2024-01-01 09:00:00',
            ],
            [
                'id' => 3,
                'name' => 'Sales',
                'created_at' => '2024-01-01 09:00:00',
                'updated_at' => '2024-01-01 09:00:00',
            ],
        ];
    }
    
    /**
     * Get XSS attack payloads for security testing
     * 
     * Common XSS attack vectors for testing input sanitization.
     * 
     * @return array XSS payloads
     */
    public static function xssPayloads(): array
    {
        return [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '"><script>alert(String.fromCharCode(88,83,83))</script>',
            '<svg/onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
            '<body onload=alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<select onfocus=alert("XSS") autofocus>',
            '<textarea onfocus=alert("XSS") autofocus>',
            '<keygen onfocus=alert("XSS") autofocus>',
            '<video><source onerror="alert(\'XSS\')">',
            '<audio src=x onerror=alert("XSS")>',
            '<details open ontoggle=alert("XSS")>',
            '<marquee onstart=alert("XSS")>',
        ];
    }
    
    /**
     * Get SQL injection payloads for security testing
     * 
     * Common SQL injection attack vectors for testing query protection.
     * 
     * @return array SQL injection payloads
     */
    public static function sqlInjectionPayloads(): array
    {
        return [
            "' OR '1'='1",
            "'; DROP TABLE users--",
            "' UNION SELECT * FROM users--",
            "admin'--",
            "' OR 1=1--",
            "1' AND '1'='1",
            "1' UNION SELECT NULL, username, password FROM users--",
            "'; EXEC sp_MSForEachTable 'DROP TABLE ?'--",
            "' OR EXISTS(SELECT * FROM users WHERE username='admin')--",
            "1'; WAITFOR DELAY '00:00:05'--",
        ];
    }
    
    /**
     * Get DataTables request for pagination testing
     * 
     * Pre-configured DataTables request for first page.
     * 
     * @return array DataTables request
     */
    public static function datatablesFirstPageRequest(): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ];
    }
    
    /**
     * Get DataTables request for second page
     * 
     * Pre-configured DataTables request for second page.
     * 
     * @return array DataTables request
     */
    public static function datatablesSecondPageRequest(): array
    {
        return [
            'draw' => 2,
            'start' => 10,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ];
    }
    
    /**
     * Get DataTables request with search
     * 
     * Pre-configured DataTables request with global search.
     * 
     * @param string $searchTerm Search term
     * @return array DataTables request
     */
    public static function datatablesSearchRequest(string $searchTerm = 'John'): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => $searchTerm, 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ];
    }
    
    /**
     * Get DataTables request with sorting
     * 
     * Pre-configured DataTables request with column sorting.
     * 
     * @param int $columnIndex Column index to sort
     * @param string $direction Sort direction (asc/desc)
     * @return array DataTables request
     */
    public static function datatablesSortRequest(int $columnIndex = 1, string $direction = 'asc'): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['column' => $columnIndex, 'dir' => $direction]],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ];
    }
    
    /**
     * Get expected DataTables response structure
     * 
     * Template for validating DataTables response format.
     * 
     * @return array Expected response keys
     */
    public static function expectedDatatablesResponseKeys(): array
    {
        return [
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ];
    }
}
