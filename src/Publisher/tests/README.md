# Table Components Test Helpers Documentation

This document provides comprehensive documentation for the test helpers, fixtures, mocks, and assertion utilities created to support testing of the Table Components in CanvaStack framework.

## Overview

The test infrastructure includes:

1. **MockDatabase** - Mock database objects and test tables
2. **TableFixtures** - Pre-configured test data and scenarios
3. **TestDataGenerator** - Random data generation for testing
4. **TableAssertions** - Custom assertion helpers for validation

## Table of Contents

- [MockDatabase](#mockdatabase)
- [TableFixtures](#tablefixtures)
- [TestDataGenerator](#testdatagenerator)
- [TableAssertions](#tableassertions)
- [Usage Examples](#usage-examples)

---

## MockDatabase

**Location:** `tests/Mocks/MockDatabase.php`

**Purpose:** Provides mock database tables and data for testing without requiring a real database connection.

### Available Test Tables

1. **test_users** - User records with relationships
2. **test_departments** - Department records
3. **test_products** - Product catalog
4. **test_orders** - Order records with foreign keys

### Key Methods

#### Setup and Teardown

```php
// Setup complete test environment (creates tables and seeds data)
MockDatabase::setupTestEnvironment();

// Teardown (drops all test tables)
MockDatabase::teardownTestEnvironment();

// Reset (clears and reseeds data)
MockDatabase::resetTestEnvironment();
```

#### Individual Table Management

```php
// Create individual tables
MockDatabase::createTestUsersTable();
MockDatabase::createTestDepartmentsTable();
MockDatabase::createTestProductsTable();
MockDatabase::createTestOrdersTable();

// Seed individual tables
MockDatabase::seedTestUsers(10);  // Create 10 users
MockDatabase::seedTestDepartments();
MockDatabase::seedTestProducts(20);
MockDatabase::seedTestOrders(50);

// Clear data
MockDatabase::clearAllTestData();

// Drop tables
MockDatabase::dropAllTestTables();
```

#### Query Builders

```php
// Get query builders for testing
$usersQuery = MockDatabase::getMockUsersQueryBuilder();
$departmentsQuery = MockDatabase::getMockDepartmentsQueryBuilder();
$productsQuery = MockDatabase::getMockProductsQueryBuilder();
$ordersQuery = MockDatabase::getMockOrdersQueryBuilder();
```

#### Schema Information

```php
// Get table schema
$schema = MockDatabase::getTableSchema('test_users');

// Check table existence
$exists = MockDatabase::tableExists('test_users');

// Check column existence
$hasColumn = MockDatabase::columnExists('test_users', 'email');

// Get column names
$columns = MockDatabase::getTableColumns('test_users');
```

### Usage Example

```php
use Tests\Mocks\MockDatabase;

class MyTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockDatabase::setupTestEnvironment();
    }
    
    protected function tearDown(): void
    {
        MockDatabase::teardownTestEnvironment();
        parent::tearDown();
    }
    
    public function testTableRendering()
    {
        $query = MockDatabase::getMockUsersQueryBuilder();
        // Use query in your test...
    }
}
```

---

## TableFixtures

**Location:** `tests/Fixtures/TableFixtures.php`

**Purpose:** Provides pre-configured data fixtures for common table scenarios.

### Configuration Fixtures

#### Simple Table

```php
$config = TableFixtures::simpleTableConfig();
// Returns basic table configuration with minimal settings
```

#### Server-Side Table

```php
$config = TableFixtures::serverSideTableConfig();
// Returns table with server-side processing enabled
```

#### Table with Relationships

```php
$config = TableFixtures::tableWithRelationshipsConfig();
// Returns table with foreign key relationships
```

#### Table with Formulas

```php
$config = TableFixtures::tableWithFormulasConfig();
// Returns table with calculated columns
```

#### Table with Filters

```php
$config = TableFixtures::tableWithFiltersConfig();
// Returns table with pre-applied filters
```

#### Table with Custom Actions

```php
$config = TableFixtures::tableWithCustomActionsConfig();
// Returns table with custom action buttons
```

### Data Fixtures

#### Sample Data

```php
// Get sample user data
$users = TableFixtures::sampleUserData();

// Get sample department data
$departments = TableFixtures::sampleDepartmentData();
```

#### Security Testing Payloads

```php
// Get XSS attack payloads
$xssPayloads = TableFixtures::xssPayloads();

// Get SQL injection payloads
$sqlPayloads = TableFixtures::sqlInjectionPayloads();
```

#### DataTables Requests

```php
// First page request
$request = TableFixtures::datatablesFirstPageRequest();

// Second page request
$request = TableFixtures::datatablesSecondPageRequest();

// Search request
$request = TableFixtures::datatablesSearchRequest('John');

// Sort request
$request = TableFixtures::datatablesSortRequest(1, 'asc');
```

#### Response Validation

```php
// Get expected response keys
$expectedKeys = TableFixtures::expectedDatatablesResponseKeys();
```

### Usage Example

```php
use Tests\Fixtures\TableFixtures;

public function testServerSideProcessing()
{
    $config = TableFixtures::serverSideTableConfig();
    $request = TableFixtures::datatablesFirstPageRequest();
    
    // Test with configuration and request...
}
```

---

## TestDataGenerator

**Location:** `tests/Generators/TestDataGenerator.php`

**Purpose:** Generates random test data for various testing scenarios.

### Data Generation Methods

#### User Data

```php
// Generate 10 random users
$users = TestDataGenerator::generateUsers(10);

// Generate users with overrides
$users = TestDataGenerator::generateUsers(5, ['status' => 'active']);
```

#### Department Data

```php
$departments = TestDataGenerator::generateDepartments(5);
```

#### Product Data

```php
$products = TestDataGenerator::generateProducts(20);
```

#### Order Data

```php
// Generate 50 orders for users 1-10
$orders = TestDataGenerator::generateOrders(50, 10);
```

### Security Testing

#### XSS Payloads

```php
// Generate 20 XSS attack payloads
$xssPayloads = TestDataGenerator::generateXSSPayloads(20);
```

#### SQL Injection Payloads

```php
// Generate 15 SQL injection payloads
$sqlPayloads = TestDataGenerator::generateSQLInjectionPayloads(15);
```

### Validation Testing

#### Table Names

```php
// Generate valid table names
$tableNames = TestDataGenerator::generateTableNames(10, false);

// Generate table names including invalid ones
$tableNames = TestDataGenerator::generateTableNames(10, true);
```

#### Column Names

```php
// Generate valid column names
$columnNames = TestDataGenerator::generateColumnNames(10, false);

// Generate column names including invalid ones
$columnNames = TestDataGenerator::generateColumnNames(10, true);
```

#### Pagination Parameters

```php
// Generate valid pagination parameters
$params = TestDataGenerator::generatePaginationParams(10, false);

// Generate parameters including invalid ones
$params = TestDataGenerator::generatePaginationParams(10, true);
```

#### Sort Parameters

```php
// Generate valid sort parameters
$params = TestDataGenerator::generateSortParams(10, false);

// Generate parameters including invalid ones
$params = TestDataGenerator::generateSortParams(10, true);
```

#### Search Terms

```php
// Generate normal search terms
$terms = TestDataGenerator::generateSearchTerms(10, false);

// Generate terms with special characters
$terms = TestDataGenerator::generateSearchTerms(10, true);
```

### DataTables Requests

```php
// Generate random DataTables request
$request = TestDataGenerator::generateDatatablesRequest();

// Generate with overrides
$request = TestDataGenerator::generateDatatablesRequest([
    'start' => 0,
    'length' => 25,
]);
```

### Performance Testing

#### Large Datasets

```php
// Generate 10,000 records for performance testing
$largeDataset = TestDataGenerator::generateLargeDataset(10000);
```

#### Edge Cases

```php
// Generate edge case data (empty values, long strings, special chars, etc.)
$edgeCases = TestDataGenerator::generateEdgeCaseData();
```

### Usage Example

```php
use Tests\Generators\TestDataGenerator;

public function testWithRandomData()
{
    $users = TestDataGenerator::generateUsers(100);
    
    foreach ($users as $user) {
        // Test with random user data...
    }
}

public function testXSSProtection()
{
    $xssPayloads = TestDataGenerator::generateXSSPayloads(20);
    
    foreach ($xssPayloads as $payload) {
        // Test XSS protection with payload...
    }
}
```

---

## TableAssertions

**Location:** `tests/Helpers/TableAssertions.php`

**Purpose:** Provides custom assertion methods for validating table components.

### Security Assertions

#### XSS Protection

```php
// Assert HTML is properly escaped
TableAssertions::assertHtmlIsEscaped($html);

// Assert string is HTML escaped
TableAssertions::assertStringIsHtmlEscaped($original, $escaped);

// Assert data array is escaped
TableAssertions::assertDataIsEscaped($data);
```

#### Input Validation

```php
// Assert table name is valid
TableAssertions::assertValidTableName('test_users');

// Assert column name is valid
TableAssertions::assertValidColumnName('email');

// Assert pagination parameters are valid
TableAssertions::assertValidPaginationParams(0, 10);

// Assert sort parameters are valid
TableAssertions::assertValidSortParams('name', 'asc');
```

### Structure Assertions

#### HTML Structure

```php
// Assert valid table structure
TableAssertions::assertValidTableStructure($html);

// Assert table has ARIA attributes
TableAssertions::assertTableHasAriaAttributes($html);

// Assert keyboard navigation support
TableAssertions::assertKeyboardNavigationSupport($html);
```

#### DataTables Response

```php
// Assert valid DataTables response structure
TableAssertions::assertValidDatatablesResponse($response);

// Assert correct pagination
TableAssertions::assertCorrectPagination($response, 0, 10);
```

### Functional Assertions

#### Action Buttons

```php
// Assert action buttons are present
TableAssertions::assertActionButtonsPresent($html, ['view', 'edit', 'delete']);

// Assert action buttons have ARIA labels
TableAssertions::assertActionButtonsHaveAriaLabels($html);
```

#### Search and Sort

```php
// Assert search results match search term
TableAssertions::assertSearchResultsMatch($results, 'John', ['name', 'email']);

// Assert results are sorted correctly
TableAssertions::assertResultsSorted($results, 'name', 'asc');
```

#### Formulas

```php
// Assert formula calculation is correct
TableAssertions::assertFormulaResult($result, $expected, 0.01);
```

### Performance Assertions

#### Query Optimization

```php
// Assert query uses eager loading
TableAssertions::assertQueryUsesEagerLoading($query, ['department']);

// Assert no N+1 queries
$result = TableAssertions::assertNoNPlusOneQueries(5, function() {
    // Execute code that should not cause N+1 queries
    return $table->getData();
});
```

#### Response Time

```php
$startTime = microtime(true);
// Execute operation...
TableAssertions::assertResponseTimeAcceptable($startTime, 1.0);
```

#### Memory Usage

```php
$startMemory = memory_get_usage();
// Execute operation...
TableAssertions::assertMemoryUsageAcceptable($startMemory, 10485760); // 10MB
```

### Configuration Assertions

```php
// Assert table configuration is valid
TableAssertions::assertValidTableConfig($config);
```

### Export Assertions

```php
// Assert export file is valid
TableAssertions::assertValidExportFile('/path/to/file.csv', 'csv');
```

### Usage Example

```php
use Tests\Helpers\TableAssertions;

public function testTableRendering()
{
    $html = $table->render();
    
    TableAssertions::assertValidTableStructure($html);
    TableAssertions::assertTableHasAriaAttributes($html);
    TableAssertions::assertHtmlIsEscaped($html);
}

public function testDataTablesResponse()
{
    $response = $datatables->process($request);
    
    TableAssertions::assertValidDatatablesResponse($response);
    TableAssertions::assertCorrectPagination($response, 0, 10);
    TableAssertions::assertDataIsEscaped($response['data']);
}
```

---

## Usage Examples

### Complete Test Example

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Mocks\MockDatabase;
use Tests\Fixtures\TableFixtures;
use Tests\Generators\TestDataGenerator;
use Tests\Helpers\TableAssertions;
use Canvastack\Canvastack\Library\Components\Table\Objects;

class TableComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockDatabase::setupTestEnvironment();
    }
    
    protected function tearDown(): void
    {
        MockDatabase::teardownTestEnvironment();
        parent::tearDown();
    }
    
    public function testBasicTableRendering()
    {
        // Arrange
        $config = TableFixtures::simpleTableConfig();
        $table = new Objects();
        
        // Act
        $html = $table->lists(
            $config['table_name'],
            $config['fields'],
            $config['actions']
        );
        
        // Assert
        TableAssertions::assertValidTableStructure($html);
        TableAssertions::assertHtmlIsEscaped($html);
    }
    
    public function testServerSideProcessing()
    {
        // Arrange
        $config = TableFixtures::serverSideTableConfig();
        $request = TableFixtures::datatablesFirstPageRequest();
        
        // Act
        $response = $datatables->process($request);
        
        // Assert
        TableAssertions::assertValidDatatablesResponse($response);
        TableAssertions::assertCorrectPagination($response, 0, 10);
        TableAssertions::assertDataIsEscaped($response['data']);
    }
    
    public function testXSSProtection()
    {
        // Arrange
        $xssPayloads = TestDataGenerator::generateXSSPayloads(10);
        
        foreach ($xssPayloads as $payload) {
            // Act
            $html = $table->renderCell($payload);
            
            // Assert
            TableAssertions::assertHtmlIsEscaped($html);
        }
    }
    
    public function testPerformanceWithLargeDataset()
    {
        // Arrange
        $largeDataset = TestDataGenerator::generateLargeDataset(10000);
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Act
        $result = $table->processData($largeDataset);
        
        // Assert
        TableAssertions::assertResponseTimeAcceptable($startTime, 2.0);
        TableAssertions::assertMemoryUsageAcceptable($startMemory, 20971520); // 20MB
    }
    
    public function testNoNPlusOneQueries()
    {
        // Arrange
        $config = TableFixtures::tableWithRelationshipsConfig();
        
        // Act & Assert
        $result = TableAssertions::assertNoNPlusOneQueries(3, function() use ($config) {
            return $datatables->getData($config);
        });
        
        $this->assertNotEmpty($result);
    }
}
```

### Property-Based Test Example

```php
<?php

namespace Tests\Property;

use Tests\TestCase;
use Tests\Generators\TestDataGenerator;
use Tests\Helpers\TableAssertions;

class SecurityPropertiesTest extends TestCase
{
    /**
     * Property 1: XSS Protection - User Data Escaping
     * 
     * For any user-controllable data rendered to HTML output,
     * all special characters SHALL be escaped.
     * 
     * Validates: Requirement 1.1
     */
    public function testProperty_XSSProtection_UserDataEscaping()
    {
        // Generate 100 random XSS payloads
        $payloads = TestDataGenerator::generateXSSPayloads(100);
        
        foreach ($payloads as $payload) {
            // Act
            $escaped = canvastack_table_escape_html($payload);
            
            // Assert
            TableAssertions::assertStringIsHtmlEscaped($payload, $escaped);
            TableAssertions::assertHtmlIsEscaped($escaped);
        }
    }
    
    /**
     * Property 7: SQL Injection Prevention - Table Name Validation
     * 
     * For any table name used in queries, the table name SHALL be
     * validated against a whitelist of allowed tables.
     * 
     * Validates: Requirement 2.2
     */
    public function testProperty_SQLInjection_TableNameValidation()
    {
        // Generate table names including invalid ones
        $tableNames = TestDataGenerator::generateTableNames(50, true);
        $allowedTables = ['test_users', 'test_products', 'test_orders'];
        
        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $allowedTables)) {
                // Should not throw exception
                $validated = canvastack_table_validate_table_name($tableName, $allowedTables);
                $this->assertEquals($tableName, $validated);
            } else {
                // Should throw exception
                $this->expectException(\InvalidArgumentException::class);
                canvastack_table_validate_table_name($tableName, $allowedTables);
            }
        }
    }
}
```

---

## Best Practices

### 1. Use MockDatabase for Integration Tests

Always use MockDatabase for tests that require database interaction:

```php
protected function setUp(): void
{
    parent::setUp();
    MockDatabase::setupTestEnvironment();
}

protected function tearDown(): void
{
    MockDatabase::teardownTestEnvironment();
    parent::tearDown();
}
```

### 2. Use Fixtures for Consistent Test Data

Use TableFixtures for consistent, repeatable test scenarios:

```php
$config = TableFixtures::serverSideTableConfig();
// Always returns the same configuration
```

### 3. Use Generators for Randomized Testing

Use TestDataGenerator for property-based tests and randomized testing:

```php
$users = TestDataGenerator::generateUsers(100);
// Generates 100 different random users each time
```

### 4. Use Assertions for Validation

Use TableAssertions for consistent validation across tests:

```php
TableAssertions::assertValidTableStructure($html);
TableAssertions::assertHtmlIsEscaped($html);
```

### 5. Test Security Properties

Always test security properties with attack payloads:

```php
$xssPayloads = TestDataGenerator::generateXSSPayloads(20);
foreach ($xssPayloads as $payload) {
    TableAssertions::assertHtmlIsEscaped($table->render($payload));
}
```

### 6. Test Performance Properties

Always test performance with large datasets:

```php
$largeDataset = TestDataGenerator::generateLargeDataset(10000);
$startTime = microtime(true);
$result = $table->process($largeDataset);
TableAssertions::assertResponseTimeAcceptable($startTime, 2.0);
```

---

## Troubleshooting

### MockDatabase Issues

**Problem:** Tables already exist
```php
// Solution: Drop tables before creating
MockDatabase::dropAllTestTables();
MockDatabase::setupTestEnvironment();
```

**Problem:** Data persists between tests
```php
// Solution: Reset environment in setUp
protected function setUp(): void
{
    parent::setUp();
    MockDatabase::resetTestEnvironment();
}
```

### TestDataGenerator Issues

**Problem:** Unique constraint violations
```php
// Solution: Use Faker's unique() modifier
$faker->unique()->safeEmail;
```

**Problem:** Memory issues with large datasets
```php
// Solution: Use chunking
$data = TestDataGenerator::generateLargeDataset(10000);
// Generator already uses chunking internally
```

### TableAssertions Issues

**Problem:** Assertion fails unexpectedly
```php
// Solution: Add custom message for debugging
TableAssertions::assertHtmlIsEscaped($html, 'Custom debug message');
```

---

## Contributing

When adding new test helpers:

1. Add comprehensive PHPDoc comments
2. Include usage examples in this documentation
3. Follow existing naming conventions
4. Add validation for parameters
5. Include error handling
6. Write tests for the test helpers themselves

---

## References

- [Requirements Document](../.kiro/specs/table-components-audit-fixes/requirements.md)
- [Design Document](../.kiro/specs/table-components-audit-fixes/design.md)
- [Tasks Document](../.kiro/specs/table-components-audit-fixes/tasks.md)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
