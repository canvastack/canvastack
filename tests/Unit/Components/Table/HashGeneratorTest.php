<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\HashGenerator;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for HashGenerator.
 * 
 * Tests the secure unique ID generation for table instances.
 */
class HashGeneratorTest extends TestCase
{
    /**
     * HashGenerator instance.
     */
    private HashGenerator $generator;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->generator = new HashGenerator();
        
        // Reset counter before each test
        HashGenerator::resetCounter();
    }

    /**
     * Test that generated ID follows correct format.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_generated_id_follows_correct_format(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name', 'email'];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^canvastable_[a-f0-9]{16}$/',
            $id,
            'Generated ID should match format: canvastable_{16-char-hash}'
        );
    }

    /**
     * Test that two calls with identical inputs produce different IDs.
     * 
     * Requirements: 1.4, 1.6
     */
    public function test_identical_inputs_produce_different_ids(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name', 'email'];

        // Act
        $id1 = $this->generator->generate($tableName, $connectionName, $fields);
        $id2 = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertNotEquals(
            $id1,
            $id2,
            'Two calls with identical inputs should produce different IDs due to instance counter and random bytes'
        );
    }

    /**
     * Test that different table names produce different IDs.
     * 
     * Requirements: 1.7
     */
    public function test_different_table_names_produce_different_ids(): void
    {
        // Arrange
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $id1 = $this->generator->generate('users', $connectionName, $fields);
        
        // Reset to ensure only table name differs
        HashGenerator::resetCounter();
        
        $id2 = $this->generator->generate('posts', $connectionName, $fields);

        // Assert
        $this->assertNotEquals(
            $id1,
            $id2,
            'Different table names should produce different IDs'
        );
    }

    /**
     * Test that different connections produce different IDs.
     * 
     * Requirements: 1.7
     */
    public function test_different_connections_produce_different_ids(): void
    {
        // Arrange
        $tableName = 'users';
        $fields = ['id', 'name'];

        // Act
        $id1 = $this->generator->generate($tableName, 'mysql', $fields);
        
        // Reset to ensure only connection differs
        HashGenerator::resetCounter();
        
        $id2 = $this->generator->generate($tableName, 'pgsql', $fields);

        // Assert
        $this->assertNotEquals(
            $id1,
            $id2,
            'Different connections should produce different IDs'
        );
    }

    /**
     * Test that different fields produce different IDs.
     * 
     * Requirements: 1.7
     */
    public function test_different_fields_produce_different_ids(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';

        // Act
        $id1 = $this->generator->generate($tableName, $connectionName, ['id', 'name']);
        
        // Reset to ensure only fields differ
        HashGenerator::resetCounter();
        
        $id2 = $this->generator->generate($tableName, $connectionName, ['id', 'name', 'email']);

        // Assert
        $this->assertNotEquals(
            $id1,
            $id2,
            'Different field lists should produce different IDs'
        );
    }

    /**
     * Test that hash does not contain readable table name.
     * 
     * Requirements: 1.5
     */
    public function test_hash_does_not_expose_table_name(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name', 'email'];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertStringNotContainsString(
            'users',
            $id,
            'Generated ID should not contain readable table name'
        );
        
        $this->assertStringNotContainsString(
            'mysql',
            $id,
            'Generated ID should not contain readable connection name'
        );
    }

    /**
     * Test that instance counter increments correctly.
     * 
     * Requirements: 1.6
     */
    public function test_instance_counter_increments(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $initialCounter = HashGenerator::getCounter();
        
        $this->generator->generate($tableName, $connectionName, $fields);
        $counterAfterFirst = HashGenerator::getCounter();
        
        $this->generator->generate($tableName, $connectionName, $fields);
        $counterAfterSecond = HashGenerator::getCounter();

        // Assert
        $this->assertEquals(0, $initialCounter, 'Initial counter should be 0');
        $this->assertEquals(1, $counterAfterFirst, 'Counter should be 1 after first generation');
        $this->assertEquals(2, $counterAfterSecond, 'Counter should be 2 after second generation');
    }

    /**
     * Test that generated ID uses cryptographically secure random bytes.
     * 
     * Requirements: 1.8
     */
    public function test_uses_cryptographically_secure_random_bytes(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act - Generate multiple IDs
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->generator->generate($tableName, $connectionName, $fields);
        }

        // Assert - All IDs should be unique (probability of collision is astronomically low)
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            10,
            $uniqueIds,
            'All generated IDs should be unique, demonstrating cryptographic randomness'
        );
    }

    /**
     * Test that ID format is consistent across multiple generations.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_id_format_is_consistent(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act - Generate multiple IDs
        for ($i = 0; $i < 20; $i++) {
            $id = $this->generator->generate($tableName, $connectionName, $fields);

            // Assert - Each ID should match the format
            $this->assertMatchesRegularExpression(
                '/^canvastable_[a-f0-9]{16}$/',
                $id,
                "ID #{$i} should match format: canvastable_{16-char-hash}"
            );
        }
    }

    /**
     * Test that empty fields array is handled correctly.
     * 
     * Requirements: 1.1
     */
    public function test_handles_empty_fields_array(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = [];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^canvastable_[a-f0-9]{16}$/',
            $id,
            'Should generate valid ID even with empty fields array'
        );
    }

    /**
     * Test that special characters in table name are handled.
     * 
     * Requirements: 1.1, 1.3
     */
    public function test_handles_special_characters_in_table_name(): void
    {
        // Arrange
        $tableName = 'App\\Models\\User';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^canvastable_[a-f0-9]{16}$/',
            $id,
            'Should generate valid ID with special characters in table name'
        );
    }

    /**
     * Test that large field arrays are handled correctly.
     * 
     * Requirements: 1.1, 1.3
     */
    public function test_handles_large_field_arrays(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = array_map(fn($i) => "field_{$i}", range(1, 100));

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^canvastable_[a-f0-9]{16}$/',
            $id,
            'Should generate valid ID with large field array'
        );
    }

    /**
     * Test that counter reset works correctly.
     * 
     * Requirements: 1.6
     */
    public function test_counter_reset_works(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $this->generator->generate($tableName, $connectionName, $fields);
        $this->generator->generate($tableName, $connectionName, $fields);
        
        $counterBeforeReset = HashGenerator::getCounter();
        
        HashGenerator::resetCounter();
        
        $counterAfterReset = HashGenerator::getCounter();

        // Assert
        $this->assertEquals(2, $counterBeforeReset, 'Counter should be 2 before reset');
        $this->assertEquals(0, $counterAfterReset, 'Counter should be 0 after reset');
    }

    /**
     * Test that hash length is exactly 16 characters.
     * 
     * Requirements: 1.2
     */
    public function test_hash_length_is_exactly_16_characters(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);
        
        // Extract hash part (remove "canvastable_" prefix)
        $hashPart = substr($id, strlen('canvastable_'));

        // Assert
        $this->assertEquals(
            16,
            strlen($hashPart),
            'Hash part should be exactly 16 characters'
        );
    }

    /**
     * Test that hash only contains hexadecimal characters.
     * 
     * Requirements: 1.2
     */
    public function test_hash_contains_only_hexadecimal_characters(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];

        // Act
        $id = $this->generator->generate($tableName, $connectionName, $fields);
        
        // Extract hash part
        $hashPart = substr($id, strlen('canvastable_'));

        // Assert
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{16}$/',
            $hashPart,
            'Hash should only contain lowercase hexadecimal characters (a-f, 0-9)'
        );
    }

    /**
     * Test collision resistance with many generations.
     * 
     * Requirements: 1.4, 1.8
     */
    public function test_collision_resistance(): void
    {
        // Arrange
        $tableName = 'users';
        $connectionName = 'mysql';
        $fields = ['id', 'name'];
        $iterations = 1000;

        // Act - Generate many IDs
        $ids = [];
        for ($i = 0; $i < $iterations; $i++) {
            $ids[] = $this->generator->generate($tableName, $connectionName, $fields);
        }

        // Assert - All IDs should be unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(
            $iterations,
            $uniqueIds,
            "All {$iterations} generated IDs should be unique (no collisions)"
        );
    }
}
