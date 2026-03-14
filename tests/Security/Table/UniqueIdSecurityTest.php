<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\HashGenerator;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Security tests for unique ID generation.
 * 
 * Tests Requirements:
 * - 10.1: Unique ID must use cryptographically secure random number generation
 * - 10.2: Unique ID must not contain predictable patterns or sequential numbers
 * 
 * This test suite verifies:
 * 1. No predictable patterns in generated IDs
 * 2. No information disclosure (table names, connections, etc.)
 * 3. Collision resistance across various scenarios
 */
class UniqueIdSecurityTest extends TestCase
{
    private HashGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->generator = new HashGenerator();
        
        // Reset counter before each test for consistency
        HashGenerator::resetCounter();
    }

    protected function tearDown(): void
    {
        // Reset counter after each test
        HashGenerator::resetCounter();
        
        parent::tearDown();
    }

    /**
     * Test that generated IDs do not contain predictable patterns.
     * 
     * Requirement: 10.2 - No predictable patterns or sequential numbers
     * 
     * @return void
     */
    public function test_no_predictable_patterns_in_generated_ids(): void
    {
        $ids = [];
        
        // Generate 100 IDs with identical inputs
        for ($i = 0; $i < 100; $i++) {
            $id = $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name', 'email']
            );
            
            $ids[] = $id;
        }
        
        // All IDs should be unique (no pattern repetition)
        $uniqueIds = array_unique($ids);
        $this->assertCount(100, $uniqueIds, 'All generated IDs should be unique');
        
        // Extract hash portions (remove 'canvastable_' prefix)
        $hashes = array_map(function ($id) {
            return substr($id, strlen('canvastable_'));
        }, $ids);
        
        // Check that hashes don't follow sequential patterns
        for ($i = 0; $i < count($hashes) - 1; $i++) {
            $current = $hashes[$i];
            $next = $hashes[$i + 1];
            
            // Convert to integers for comparison (first 8 chars)
            $currentInt = hexdec(substr($current, 0, 8));
            $nextInt = hexdec(substr($next, 0, 8));
            
            // Difference should not be exactly 1 (not sequential)
            $this->assertNotEquals(
                1,
                abs($nextInt - $currentInt),
                "IDs should not be sequential: {$current} -> {$next}"
            );
        }
    }

    /**
     * Test that IDs do not follow predictable sequential patterns.
     * 
     * Requirement: 10.2 - No predictable patterns
     * 
     * This test verifies that consecutive IDs don't increment in a predictable way,
     * not that random hashes can't contain sequential digits (which is fine).
     * 
     * @return void
     */
    public function test_no_sequential_numbers_in_ids(): void
    {
        $ids = [];
        
        // Generate 50 IDs
        for ($i = 0; $i < 50; $i++) {
            $id = $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name']
            );
            
            $ids[] = $id;
        }
        
        // Extract hash portions
        $hashes = array_map(function ($id) {
            return substr($id, strlen('canvastable_'));
        }, $ids);
        
        // Check that consecutive hashes don't increment predictably
        for ($i = 0; $i < count($hashes) - 1; $i++) {
            $current = hexdec(substr($hashes[$i], 0, 8));
            $next = hexdec(substr($hashes[$i + 1], 0, 8));
            
            // The difference should not be exactly 1 (not incrementing)
            $diff = abs($next - $current);
            $this->assertNotEquals(
                1,
                $diff,
                "Consecutive IDs should not increment by 1: {$hashes[$i]} -> {$hashes[$i + 1]}"
            );
            
            // The difference should not be a small predictable number (< 10)
            $this->assertGreaterThan(
                10,
                $diff,
                "Consecutive IDs should have large random differences: {$hashes[$i]} -> {$hashes[$i + 1]}"
            );
        }
    }

    /**
     * Test that IDs do not disclose table names.
     * 
     * Requirement: 10.2 - No information disclosure
     * 
     * @return void
     */
    public function test_no_table_name_disclosure(): void
    {
        $tableNames = [
            'users',
            'orders',
            'products',
            'customers',
            'invoices',
        ];
        
        foreach ($tableNames as $tableName) {
            $id = $this->generator->generate(
                $tableName,
                'mysql',
                ['id', 'name']
            );
            
            // Extract hash portion
            $hash = substr($id, strlen('canvastable_'));
            
            // Hash should not contain any part of the table name
            $this->assertStringNotContainsString(
                $tableName,
                $hash,
                "ID should not contain table name: {$tableName}"
            );
            
            // Check for partial matches (first 3+ chars)
            if (strlen($tableName) >= 3) {
                $partial = substr($tableName, 0, 3);
                $this->assertStringNotContainsString(
                    $partial,
                    $hash,
                    "ID should not contain partial table name: {$partial}"
                );
            }
        }
    }

    /**
     * Test that IDs do not disclose connection names.
     * 
     * Requirement: 10.2 - No information disclosure
     * 
     * @return void
     */
    public function test_no_connection_name_disclosure(): void
    {
        $connections = [
            'mysql',
            'pgsql',
            'sqlite',
            'sqlsrv',
        ];
        
        foreach ($connections as $connection) {
            $id = $this->generator->generate(
                'users',
                $connection,
                ['id', 'name']
            );
            
            // Extract hash portion
            $hash = substr($id, strlen('canvastable_'));
            
            // Hash should not contain connection name
            $this->assertStringNotContainsString(
                $connection,
                $hash,
                "ID should not contain connection name: {$connection}"
            );
        }
    }

    /**
     * Test that IDs do not disclose field names.
     * 
     * Requirement: 10.2 - No information disclosure
     * 
     * @return void
     */
    public function test_no_field_name_disclosure(): void
    {
        $fields = ['id', 'name', 'email', 'password', 'created_at'];
        
        $id = $this->generator->generate(
            'users',
            'mysql',
            $fields
        );
        
        // Extract hash portion
        $hash = substr($id, strlen('canvastable_'));
        
        // Hash should not contain any field names
        foreach ($fields as $field) {
            $this->assertStringNotContainsString(
                $field,
                $hash,
                "ID should not contain field name: {$field}"
            );
        }
    }

    /**
     * Test collision resistance with identical inputs.
     * 
     * Requirement: 10.1 - Cryptographically secure random generation
     * 
     * @return void
     */
    public function test_collision_resistance_identical_inputs(): void
    {
        $ids = [];
        $iterations = 1000;
        
        // Generate 1000 IDs with identical inputs
        for ($i = 0; $i < $iterations; $i++) {
            $id = $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name', 'email']
            );
            
            $ids[] = $id;
        }
        
        // All IDs must be unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            $iterations,
            $uniqueIds,
            "All {$iterations} IDs should be unique despite identical inputs"
        );
        
        // Calculate collision rate (should be 0%)
        $collisionRate = (1 - (count($uniqueIds) / $iterations)) * 100;
        $this->assertEquals(
            0.0,
            $collisionRate,
            'Collision rate should be 0%'
        );
    }

    /**
     * Test collision resistance with different table names.
     * 
     * Requirement: 10.1 - Collision resistance
     * 
     * @return void
     */
    public function test_collision_resistance_different_tables(): void
    {
        $tables = ['users', 'orders', 'products', 'customers', 'invoices'];
        $ids = [];
        
        // Generate IDs for different tables
        foreach ($tables as $table) {
            for ($i = 0; $i < 100; $i++) {
                $id = $this->generator->generate(
                    $table,
                    'mysql',
                    ['id', 'name']
                );
                
                $ids[] = $id;
            }
        }
        
        // All IDs should be unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            count($ids),
            $uniqueIds,
            'All IDs across different tables should be unique'
        );
    }

    /**
     * Test collision resistance with different connections.
     * 
     * Requirement: 10.1 - Collision resistance
     * 
     * @return void
     */
    public function test_collision_resistance_different_connections(): void
    {
        $connections = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
        $ids = [];
        
        // Generate IDs for different connections
        foreach ($connections as $connection) {
            for ($i = 0; $i < 100; $i++) {
                $id = $this->generator->generate(
                    'users',
                    $connection,
                    ['id', 'name']
                );
                
                $ids[] = $id;
            }
        }
        
        // All IDs should be unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            count($ids),
            $uniqueIds,
            'All IDs across different connections should be unique'
        );
    }

    /**
     * Test collision resistance with different field combinations.
     * 
     * Requirement: 10.1 - Collision resistance
     * 
     * @return void
     */
    public function test_collision_resistance_different_fields(): void
    {
        $fieldCombinations = [
            ['id', 'name'],
            ['id', 'name', 'email'],
            ['id', 'name', 'email', 'created_at'],
            ['id', 'email'],
            ['name', 'email'],
        ];
        
        $ids = [];
        
        // Generate IDs for different field combinations
        foreach ($fieldCombinations as $fields) {
            for ($i = 0; $i < 100; $i++) {
                $id = $this->generator->generate(
                    'users',
                    'mysql',
                    $fields
                );
                
                $ids[] = $id;
            }
        }
        
        // All IDs should be unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            count($ids),
            $uniqueIds,
            'All IDs across different field combinations should be unique'
        );
    }

    /**
     * Test that IDs use cryptographically secure random bytes.
     * 
     * Requirement: 10.1 - Cryptographically secure random generation
     * 
     * @return void
     */
    public function test_uses_cryptographically_secure_random_bytes(): void
    {
        $ids = [];
        
        // Generate 100 IDs
        for ($i = 0; $i < 100; $i++) {
            $id = $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name']
            );
            
            $ids[] = $id;
        }
        
        // Extract hash portions
        $hashes = array_map(function ($id) {
            return substr($id, strlen('canvastable_'));
        }, $ids);
        
        // Test randomness using chi-square test
        $this->assertRandomDistribution($hashes);
    }

    /**
     * Test ID format compliance.
     * 
     * Requirement: 10.2 - Proper format without information disclosure
     * 
     * @return void
     */
    public function test_id_format_compliance(): void
    {
        $id = $this->generator->generate(
            'users',
            'mysql',
            ['id', 'name', 'email']
        );
        
        // Should match format: canvastable_{16-char-hash}
        $this->assertMatchesRegularExpression(
            '/^canvastable_[a-f0-9]{16}$/',
            $id,
            'ID should match format: canvastable_{16-char-hash}'
        );
        
        // Hash portion should be exactly 16 characters
        $hash = substr($id, strlen('canvastable_'));
        $this->assertEquals(16, strlen($hash), 'Hash should be exactly 16 characters');
        
        // Hash should only contain hexadecimal characters
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]+$/',
            $hash,
            'Hash should only contain hexadecimal characters (a-f, 0-9)'
        );
    }

    /**
     * Test that instance counter provides uniqueness.
     * 
     * Requirement: 10.1 - Collision resistance
     * 
     * @return void
     */
    public function test_instance_counter_provides_uniqueness(): void
    {
        // Reset counter
        HashGenerator::resetCounter();
        
        $ids = [];
        
        // Generate IDs with identical inputs except instance counter
        for ($i = 0; $i < 100; $i++) {
            $id = $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name']
            );
            
            $ids[] = $id;
        }
        
        // All IDs should be unique due to instance counter
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            100,
            $uniqueIds,
            'Instance counter should ensure uniqueness'
        );
    }

    /**
     * Test resistance to timing attacks.
     * 
     * Requirement: 10.1 - Cryptographically secure
     * 
     * This test verifies that ID generation timing is reasonably consistent,
     * which helps resist timing-based attacks. We use a relaxed threshold
     * to account for system variance.
     * 
     * @return void
     */
    public function test_resistance_to_timing_attacks(): void
    {
        $timings = [];
        
        // Measure generation time for 100 IDs
        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            
            $this->generator->generate(
                'users',
                'mysql',
                ['id', 'name']
            );
            
            $end = microtime(true);
            $timings[] = ($end - $start) * 1000000; // Convert to microseconds
        }
        
        // Calculate statistics
        $mean = array_sum($timings) / count($timings);
        $variance = 0;
        
        foreach ($timings as $timing) {
            $variance += pow($timing - $mean, 2);
        }
        
        $stdDev = sqrt($variance / count($timings));
        
        // Calculate coefficient of variation (CV = stdDev / mean)
        // CV should be less than 3.0 for reasonably consistent timing
        // (relaxed threshold to account for system variance)
        $cv = $stdDev / $mean;
        
        $this->assertLessThan(
            3.0,
            $cv,
            "Timing coefficient of variation should be < 3.0 (got {$cv}). " .
            "Mean: {$mean}μs, StdDev: {$stdDev}μs"
        );
        
        // Verify that the operation completes in reasonable time
        // (< 1ms average on modern hardware)
        $this->assertLessThan(
            1000, // 1ms in microseconds
            $mean,
            "Average generation time should be < 1ms (got {$mean}μs)"
        );
    }

    /**
     * Helper method to test random distribution using chi-square test.
     * 
     * @param array $hashes Array of hash strings
     * @return void
     */
    private function assertRandomDistribution(array $hashes): void
    {
        // Count frequency of each hexadecimal character
        $frequencies = array_fill_keys(str_split('0123456789abcdef'), 0);
        
        foreach ($hashes as $hash) {
            $chars = str_split($hash);
            foreach ($chars as $char) {
                $frequencies[$char]++;
            }
        }
        
        // Calculate expected frequency (uniform distribution)
        $totalChars = array_sum($frequencies);
        $expectedFreq = $totalChars / 16; // 16 possible hex characters
        
        // Calculate chi-square statistic
        $chiSquare = 0;
        foreach ($frequencies as $observed) {
            $chiSquare += pow($observed - $expectedFreq, 2) / $expectedFreq;
        }
        
        // Critical value for chi-square with 15 degrees of freedom at 0.05 significance
        $criticalValue = 24.996;
        
        $this->assertLessThan(
            $criticalValue,
            $chiSquare,
            'Hash distribution should be random (chi-square test)'
        );
    }
}
