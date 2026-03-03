<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database\Migrations;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for table_filter_sessions table migration.
 */
class CreateTableFilterSessionsTableMigrationTest extends TestCase
{
    /**
     * Test that table_filter_sessions table exists.
     */
    public function test_table_filter_sessions_table_exists(): void
    {
        $capsule = Capsule::connection();
        
        $this->assertTrue(
            $capsule->getSchemaBuilder()->hasTable('table_filter_sessions'),
            'table_filter_sessions table should exist'
        );
    }

    /**
     * Test that table_filter_sessions table has required columns.
     */
    public function test_table_filter_sessions_table_has_required_columns(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        $columns = [
            'id',
            'session_id',
            'user_id',
            'table_name',
            'filters',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('table_filter_sessions', $column),
                "table_filter_sessions table should have {$column} column"
            );
        }
    }

    /**
     * Test that table_filter_sessions table has correct indexes.
     */
    public function test_table_filter_sessions_table_has_indexes(): void
    {
        $indexes = $this->getTableIndexes('table_filter_sessions');

        // Check for individual column indexes
        $this->assertContains(
            'table_filter_sessions_session_id_index',
            $indexes,
            'table_filter_sessions table should have index on session_id'
        );

        $this->assertContains(
            'table_filter_sessions_user_id_index',
            $indexes,
            'table_filter_sessions table should have index on user_id'
        );

        $this->assertContains(
            'table_filter_sessions_table_name_index',
            $indexes,
            'table_filter_sessions table should have index on table_name'
        );

        // Check for composite index
        $this->assertContains(
            'idx_filter_session_table',
            $indexes,
            'table_filter_sessions table should have composite index on session_id and table_name'
        );
    }

    /**
     * Test that table_filter_sessions table can store JSON data.
     */
    public function test_table_filter_sessions_table_can_store_json_data(): void
    {
        $capsule = Capsule::connection();
        
        $filters = [
            'period_string' => '2025-04',
            'cor' => 'A',
            'region' => 'WEST',
            'cluster' => 'JAKARTA',
        ];
        
        // Insert test data with JSON
        $id = $capsule->table('table_filter_sessions')->insertGetId([
            'session_id' => 'filter_test_123',
            'user_id' => null,
            'table_name' => 'reports',
            'filters' => json_encode($filters),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertIsInt($id, 'Insert should return an integer ID');
        $this->assertGreaterThan(0, $id, 'Insert should return a positive ID');

        // Verify JSON data was stored correctly
        $record = $capsule->table('table_filter_sessions')->find($id);
        
        $this->assertNotNull($record, 'Record should exist');
        
        $storedFilters = json_decode($record->filters, true);
        $this->assertIsArray($storedFilters, 'Filters should be decoded as array');
        $this->assertEquals($filters, $storedFilters, 'Stored filters should match original');
    }

    /**
     * Test that table_filter_sessions table can store empty filters.
     */
    public function test_table_filter_sessions_table_can_store_empty_filters(): void
    {
        $capsule = Capsule::connection();
        
        // Insert with empty filters
        $id = $capsule->table('table_filter_sessions')->insertGetId([
            'session_id' => 'empty_filter_456',
            'user_id' => null,
            'table_name' => 'users',
            'filters' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_filter_sessions')->find($id);
        
        $storedFilters = json_decode($record->filters, true);
        $this->assertIsArray($storedFilters, 'Empty filters should be decoded as array');
        $this->assertEmpty($storedFilters, 'Filters array should be empty');
    }

    /**
     * Test that table_filter_sessions table can store complex nested filters.
     */
    public function test_table_filter_sessions_table_can_store_complex_filters(): void
    {
        $capsule = Capsule::connection();
        
        $complexFilters = [
            'date_range' => [
                'start' => '2025-01-01',
                'end' => '2025-12-31',
            ],
            'categories' => ['electronics', 'books', 'clothing'],
            'price' => [
                'min' => 10,
                'max' => 1000,
            ],
            'in_stock' => true,
        ];
        
        // Insert complex nested filters
        $id = $capsule->table('table_filter_sessions')->insertGetId([
            'session_id' => 'complex_filter_789',
            'user_id' => null,
            'table_name' => 'products',
            'filters' => json_encode($complexFilters),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_filter_sessions')->find($id);
        
        $storedFilters = json_decode($record->filters, true);
        $this->assertEquals($complexFilters, $storedFilters, 'Complex nested filters should be preserved');
    }

    /**
     * Test that table_filter_sessions table supports null user_id.
     */
    public function test_table_filter_sessions_table_supports_null_user_id(): void
    {
        $capsule = Capsule::connection();
        
        // Insert with null user_id (guest session)
        $id = $capsule->table('table_filter_sessions')->insertGetId([
            'session_id' => 'guest_filter_999',
            'user_id' => null,
            'table_name' => 'orders',
            'filters' => json_encode(['status' => 'pending']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_filter_sessions')->find($id);
        
        $this->assertNull($record->user_id, 'user_id should be null for guest sessions');
    }

    /**
     * Test that table_filter_sessions table can be queried by composite index.
     */
    public function test_table_filter_sessions_table_can_be_queried_by_composite_index(): void
    {
        $capsule = Capsule::connection();
        
        // Insert test data
        $capsule->table('table_filter_sessions')->insert([
            'session_id' => 'query_filter_111',
            'user_id' => null,
            'table_name' => 'invoices',
            'filters' => json_encode(['year' => 2025]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Query using composite index
        $record = $capsule->table('table_filter_sessions')
            ->where('session_id', 'query_filter_111')
            ->where('table_name', 'invoices')
            ->first();

        $this->assertNotNull($record, 'Record should be found using composite index');
        
        $filters = json_decode($record->filters, true);
        $this->assertEquals(2025, $filters['year']);
    }

    /**
     * Get table indexes.
     */
    private function getTableIndexes(string $table): array
    {
        $indexes = [];
        $capsule = Capsule::connection();
        
        $results = $capsule->select("PRAGMA index_list({$table})");

        foreach ($results as $result) {
            $indexes[] = $result->name;
        }

        return $indexes;
    }
}
