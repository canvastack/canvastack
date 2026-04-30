<?php

namespace Tests\Generators;

use Faker\Factory as Faker;

/**
 * Test Data Generator for Table Components
 * 
 * Provides methods to generate random test data for various scenarios
 * including security testing, performance testing, and edge case testing.
 * 
 * Validates: Requirement 25 - Testing Support
 */
class TestDataGenerator
{
    /**
     * @var \Faker\Generator Faker instance
     */
    private static $faker;
    
    /**
     * Get Faker instance
     * 
     * @return \Faker\Generator
     */
    private static function faker()
    {
        if (self::$faker === null) {
            self::$faker = Faker::create();
        }
        
        return self::$faker;
    }
    
    /**
     * Generate random user data
     * 
     * @param int $count Number of users to generate
     * @param array $overrides Optional field overrides
     * @return array User records
     */
    public static function generateUsers(int $count = 10, array $overrides = []): array
    {
        $faker = self::faker();
        $users = [];
        
        for ($i = 0; $i < $count; $i++) {
            $users[] = array_merge([
                'id' => $i + 1,
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'age' => $faker->numberBetween(18, 65),
                'status' => $faker->randomElement(['active', 'inactive', 'pending']),
                'salary' => $faker->randomFloat(2, 30000, 150000),
                'department_id' => $faker->numberBetween(1, 5),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                'updated_at' => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
            ], $overrides);
        }
        
        return $users;
    }
    
    /**
     * Generate random department data
     * 
     * @param int $count Number of departments to generate
     * @return array Department records
     */
    public static function generateDepartments(int $count = 5): array
    {
        $faker = self::faker();
        $departments = [];
        
        $departmentNames = ['Engineering', 'Marketing', 'Sales', 'Human Resources', 'Finance', 
                           'Operations', 'Customer Service', 'IT', 'Legal', 'Research'];
        
        for ($i = 0; $i < min($count, count($departmentNames)); $i++) {
            $departments[] = [
                'id' => $i + 1,
                'name' => $departmentNames[$i],
                'code' => strtoupper(substr($departmentNames[$i], 0, 3)),
                'created_at' => $faker->dateTimeBetween('-2 years', '-1 year')->format('Y-m-d H:i:s'),
                'updated_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            ];
        }
        
        return $departments;
    }
    
    /**
     * Generate random product data
     * 
     * @param int $count Number of products to generate
     * @return array Product records
     */
    public static function generateProducts(int $count = 20): array
    {
        $faker = self::faker();
        $products = [];
        
        $categories = ['Electronics', 'Clothing', 'Books', 'Food', 'Toys', 'Sports', 'Home', 'Garden'];
        
        for ($i = 0; $i < $count; $i++) {
            $products[] = [
                'id' => $i + 1,
                'name' => $faker->words(3, true),
                'description' => $faker->sentence(10),
                'price' => $faker->randomFloat(2, 5, 1000),
                'stock' => $faker->numberBetween(0, 500),
                'category' => $faker->randomElement($categories),
                'active' => $faker->boolean(80), // 80% active
                'created_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                'updated_at' => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
            ];
        }
        
        return $products;
    }
    
    /**
     * Generate random order data
     * 
     * @param int $count Number of orders to generate
     * @param int $maxUserId Maximum user ID for foreign key
     * @return array Order records
     */
    public static function generateOrders(int $count = 50, int $maxUserId = 10): array
    {
        $faker = self::faker();
        $orders = [];
        
        $statuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        
        for ($i = 0; $i < $count; $i++) {
            $orderedAt = $faker->dateTimeBetween('-6 months', 'now');
            
            $orders[] = [
                'id' => $i + 1,
                'user_id' => $faker->numberBetween(1, $maxUserId),
                'order_number' => 'ORD-' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'total' => $faker->randomFloat(2, 10, 5000),
                'status' => $faker->randomElement($statuses),
                'ordered_at' => $orderedAt->format('Y-m-d H:i:s'),
                'created_at' => $orderedAt->format('Y-m-d H:i:s'),
                'updated_at' => $faker->dateTimeBetween($orderedAt, 'now')->format('Y-m-d H:i:s'),
            ];
        }
        
        return $orders;
    }
    
    /**
     * Generate XSS attack payloads with variations
     * 
     * @param int $count Number of payloads to generate
     * @return array XSS payloads
     */
    public static function generateXSSPayloads(int $count = 20): array
    {
        $basePayloads = [
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
            '<style>@import\'http://evil.com/xss.css\';</style>',
            '<link rel="stylesheet" href="javascript:alert(\'XSS\')">',
            '<meta http-equiv="refresh" content="0;url=javascript:alert(\'XSS\')">',
            '<form action="javascript:alert(\'XSS\')"><input type="submit"></form>',
            '<button onclick="alert(\'XSS\')">Click</button>',
        ];
        
        $payloads = [];
        $faker = self::faker();
        
        for ($i = 0; $i < $count; $i++) {
            if ($i < count($basePayloads)) {
                $payloads[] = $basePayloads[$i];
            } else {
                // Generate variations
                $payload = $faker->randomElement($basePayloads);
                $payloads[] = str_replace('XSS', 'XSS' . $i, $payload);
            }
        }
        
        return $payloads;
    }
    
    /**
     * Generate SQL injection attack payloads
     * 
     * @param int $count Number of payloads to generate
     * @return array SQL injection payloads
     */
    public static function generateSQLInjectionPayloads(int $count = 15): array
    {
        $basePayloads = [
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
            "' UNION SELECT NULL, NULL, NULL--",
            "1' ORDER BY 10--",
            "' AND 1=CONVERT(int, (SELECT @@version))--",
            "' UNION ALL SELECT NULL, NULL, NULL, NULL, NULL--",
            "admin' OR '1'='1'/*",
        ];
        
        return array_slice($basePayloads, 0, min($count, count($basePayloads)));
    }
    
    /**
     * Generate random table names for testing
     * 
     * @param int $count Number of table names to generate
     * @param bool $includeInvalid Include invalid table names
     * @return array Table names
     */
    public static function generateTableNames(int $count = 10, bool $includeInvalid = false): array
    {
        $faker = self::faker();
        $tableNames = [];
        
        $validPrefixes = ['test_', 'tmp_', 'data_', 'user_', 'product_', 'order_'];
        $validSuffixes = ['users', 'products', 'orders', 'items', 'records', 'data'];
        
        for ($i = 0; $i < $count; $i++) {
            if ($includeInvalid && $i % 3 === 0) {
                // Generate invalid table names
                $invalidNames = [
                    'table-with-dashes',
                    'table with spaces',
                    'table.with.dots',
                    'table;with;semicolons',
                    'table\'with\'quotes',
                    'table"with"doublequotes',
                    'table(with)parens',
                    'table[with]brackets',
                ];
                $tableNames[] = $faker->randomElement($invalidNames);
            } else {
                // Generate valid table names
                $prefix = $faker->randomElement($validPrefixes);
                $suffix = $faker->randomElement($validSuffixes);
                $tableNames[] = $prefix . $suffix . ($i > 0 ? '_' . $i : '');
            }
        }
        
        return $tableNames;
    }
    
    /**
     * Generate random column names for testing
     * 
     * @param int $count Number of column names to generate
     * @param bool $includeInvalid Include invalid column names
     * @return array Column names
     */
    public static function generateColumnNames(int $count = 10, bool $includeInvalid = false): array
    {
        $faker = self::faker();
        $columnNames = [];
        
        $validColumns = ['id', 'name', 'email', 'age', 'status', 'created_at', 'updated_at', 
                        'deleted_at', 'user_id', 'department_id', 'price', 'quantity', 'total'];
        
        for ($i = 0; $i < $count; $i++) {
            if ($includeInvalid && $i % 4 === 0) {
                // Generate invalid column names
                $invalidNames = [
                    'column-with-dashes',
                    'column with spaces',
                    'column.with.dots',
                    'column;with;semicolons',
                    'column\'with\'quotes',
                    'column"with"doublequotes',
                ];
                $columnNames[] = $faker->randomElement($invalidNames);
            } else {
                // Generate valid column names
                if ($i < count($validColumns)) {
                    $columnNames[] = $validColumns[$i];
                } else {
                    $columnNames[] = 'column_' . $i;
                }
            }
        }
        
        return $columnNames;
    }
    
    /**
     * Generate random pagination parameters
     * 
     * @param int $count Number of parameter sets to generate
     * @param bool $includeInvalid Include invalid parameters
     * @return array Pagination parameters [start, length]
     */
    public static function generatePaginationParams(int $count = 10, bool $includeInvalid = false): array
    {
        $faker = self::faker();
        $params = [];
        
        for ($i = 0; $i < $count; $i++) {
            if ($includeInvalid && $i % 3 === 0) {
                // Generate invalid parameters
                $params[] = [
                    'start' => $faker->randomElement([-1, -10, 'invalid', null]),
                    'length' => $faker->randomElement([0, -5, 'invalid', null, 1000000]),
                ];
            } else {
                // Generate valid parameters
                $params[] = [
                    'start' => $faker->numberBetween(0, 1000),
                    'length' => $faker->randomElement([10, 25, 50, 100]),
                ];
            }
        }
        
        return $params;
    }
    
    /**
     * Generate random sort parameters
     * 
     * @param int $count Number of parameter sets to generate
     * @param bool $includeInvalid Include invalid parameters
     * @return array Sort parameters [column, direction]
     */
    public static function generateSortParams(int $count = 10, bool $includeInvalid = false): array
    {
        $faker = self::faker();
        $params = [];
        
        $validColumns = ['id', 'name', 'email', 'age', 'status', 'created_at'];
        $validDirections = ['asc', 'desc'];
        
        for ($i = 0; $i < $count; $i++) {
            if ($includeInvalid && $i % 3 === 0) {
                // Generate invalid parameters
                $params[] = [
                    'column' => $faker->randomElement(['invalid_column', 'column;with;semicolons', null]),
                    'direction' => $faker->randomElement(['invalid', 'up', 'down', null, 'ASC', 'DESC']),
                ];
            } else {
                // Generate valid parameters
                $params[] = [
                    'column' => $faker->randomElement($validColumns),
                    'direction' => $faker->randomElement($validDirections),
                ];
            }
        }
        
        return $params;
    }
    
    /**
     * Generate random search terms
     * 
     * @param int $count Number of search terms to generate
     * @param bool $includeSpecialChars Include special characters
     * @return array Search terms
     */
    public static function generateSearchTerms(int $count = 10, bool $includeSpecialChars = false): array
    {
        $faker = self::faker();
        $terms = [];
        
        for ($i = 0; $i < $count; $i++) {
            if ($includeSpecialChars && $i % 3 === 0) {
                // Generate terms with special characters
                $specialTerms = [
                    'search%term',
                    'search_term',
                    'search*term',
                    'search?term',
                    'search[term]',
                    'search(term)',
                    'search+term',
                    'search-term',
                    'search.term',
                    'search,term',
                ];
                $terms[] = $faker->randomElement($specialTerms);
            } else {
                // Generate normal search terms
                $terms[] = $faker->words($faker->numberBetween(1, 3), true);
            }
        }
        
        return $terms;
    }
    
    /**
     * Generate DataTables request parameters
     * 
     * @param array $overrides Optional parameter overrides
     * @return array DataTables request
     */
    public static function generateDatatablesRequest(array $overrides = []): array
    {
        $faker = self::faker();
        
        $columns = [
            ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true],
            ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true],
            ['data' => 'email', 'name' => 'email', 'searchable' => true, 'orderable' => true],
            ['data' => 'age', 'name' => 'age', 'searchable' => false, 'orderable' => true],
            ['data' => 'status', 'name' => 'status', 'searchable' => true, 'orderable' => true],
        ];
        
        return array_merge([
            'draw' => $faker->numberBetween(1, 100),
            'start' => $faker->randomElement([0, 10, 20, 50, 100]),
            'length' => $faker->randomElement([10, 25, 50, 100]),
            'search' => [
                'value' => $faker->boolean(30) ? $faker->word : '',
                'regex' => false,
            ],
            'order' => [
                [
                    'column' => $faker->numberBetween(0, count($columns) - 1),
                    'dir' => $faker->randomElement(['asc', 'desc']),
                ],
            ],
            'columns' => $columns,
        ], $overrides);
    }
    
    /**
     * Generate large dataset for performance testing
     * 
     * @param int $count Number of records to generate
     * @return array Large dataset
     */
    public static function generateLargeDataset(int $count = 10000): array
    {
        $faker = self::faker();
        $data = [];
        
        // Use chunking to avoid memory issues
        $chunkSize = 1000;
        $chunks = ceil($count / $chunkSize);
        
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $chunkData = [];
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $count);
            
            for ($i = $start; $i < $end; $i++) {
                $chunkData[] = [
                    'id' => $i + 1,
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'value' => $faker->randomFloat(2, 0, 10000),
                    'status' => $faker->randomElement(['active', 'inactive']),
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                ];
            }
            
            $data = array_merge($data, $chunkData);
            unset($chunkData); // Free memory
        }
        
        return $data;
    }
    
    /**
     * Generate edge case data for testing
     * 
     * @return array Edge case data
     */
    public static function generateEdgeCaseData(): array
    {
        return [
            // Empty values
            ['id' => 1, 'name' => '', 'email' => '', 'age' => null, 'status' => ''],
            
            // Very long strings
            ['id' => 2, 'name' => str_repeat('A', 255), 'email' => str_repeat('a', 240) . '@example.com', 'age' => 999, 'status' => str_repeat('X', 50)],
            
            // Special characters
            ['id' => 3, 'name' => 'Name with "quotes" and \'apostrophes\'', 'email' => 'special+chars@example.com', 'age' => 0, 'status' => 'active'],
            
            // Unicode characters
            ['id' => 4, 'name' => '日本語 中文 한국어', 'email' => 'unicode@例え.jp', 'age' => 25, 'status' => 'активный'],
            
            // Boundary values
            ['id' => 5, 'name' => 'A', 'email' => 'a@b.c', 'age' => 1, 'status' => 'a'],
            ['id' => 6, 'name' => 'Z', 'email' => 'z@z.z', 'age' => 150, 'status' => 'z'],
            
            // Numeric strings
            ['id' => 7, 'name' => '12345', 'email' => '123@456.789', 'age' => 30, 'status' => '999'],
            
            // HTML entities
            ['id' => 8, 'name' => '&lt;Name&gt;', 'email' => 'html&amp;entities@example.com', 'age' => 35, 'status' => '&quot;active&quot;'],
            
            // Whitespace variations
            ['id' => 9, 'name' => '  Name with spaces  ', 'email' => 'spaces@example.com', 'age' => 40, 'status' => "\t\nactive\r\n"],
            
            // Mixed case
            ['id' => 10, 'name' => 'MiXeD CaSe NaMe', 'email' => 'MiXeD@ExAmPlE.CoM', 'age' => 45, 'status' => 'AcTiVe'],
        ];
    }
}
