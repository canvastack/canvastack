<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database\Migrations;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for table_tab_sessions table migration.
 */
class CreateTableTabSessionsTableMigrationTest extends TestCase
{
    /**
     * Test that table_tab_sessions table exists.
     */
    public function test_table_tab_sessions_table_exists(): void
    {
        $capsule = Capsule::connection();
        
        $this->assertTrue(
            $capsule->getSchemaBuilder()->hasTable('table_tab_sessions'),
            'table_tab_sessions table should exist'
        );
    }

    /**
     * Test that table_tab_sessions table has required columns.
     */
    public function test_table_tab_sessions_table_has_required_columns(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        $columns = [
            'id',
            'session_id',
            'user_id',
            'table_name',
            'active_tab',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('table_tab_sessions', $column),
                "table_tab_sessions table should have {$column} column"
            );
        }
    }

    /**
     * Test that table_tab_sessions table has correct indexes.
     */
    public function test_table_tab_sessions_table_has_indexes(): void
    {
        $indexes = $this->getTableIndexes('table_tab_sessions');

        // Check for individual column indexes
        $this->assertContains(
            'table_tab_sessions_session_id_index',
            $indexes,
            'table_tab_sessions table should have index on session_id'
        );

        $this->assertContains(
            'table_tab_sessions_user_id_index',
            $indexes,
            'table_tab_sessions table should have index on user_id'
        );

        $this->assertContains(
            'table_tab_sessions_table_name_index',
            $indexes,
            'table_tab_sessions table should have index on table_name'
        );

        // Check for composite index
        $this->assertContains(
            'idx_tab_session_table',
            $indexes,
            'table_tab_sessions table should have composite index on session_id and table_name'
        );
    }

    /**
     * Test that table_tab_sessions table can store data.
     */
    public function test_table_tab_sessions_table_can_store_data(): void
    {
        $capsule = Capsule::connection();
        
        // Insert test data
        $id = $capsule->table('table_tab_sessions')->insertGetId([
            'session_id' => 'test_session_123',
            'user_id' => null,
            'table_name' => 'users',
            'active_tab' => 'summary',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertIsInt($id, 'Insert should return an integer ID');
        $this->assertGreaterThan(0, $id, 'Insert should return a positive ID');

        // Verify data was stored
        $record = $capsule->table('table_tab_sessions')->find($id);
        
        $this->assertNotNull($record, 'Record should exist');
        $this->assertEquals('test_session_123', $record->session_id);
        $this->assertEquals('users', $record->table_name);
        $this->assertEquals('summary', $record->active_tab);
    }

    /**
     * Test that table_tab_sessions table supports null user_id.
     */
    public function test_table_tab_sessions_table_supports_null_user_id(): void
    {
        $capsule = Capsule::connection();
        
        // Insert with null user_id (guest session)
        $id = $capsule->table('table_tab_sessions')->insertGetId([
            'session_id' => 'guest_session_456',
            'user_id' => null,
            'table_name' => 'products',
            'active_tab' => 'details',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_tab_sessions')->find($id);
        
        $this->assertNull($record->user_id, 'user_id should be null for guest sessions');
    }

    /**
     * Test that table_tab_sessions table can be queried by session_id and table_name.
     */
    public function test_table_tab_sessions_table_can_be_queried_by_composite_index(): void
    {
        $capsule = Capsule::connection();
        
        // Insert test data
        $capsule->table('table_tab_sessions')->insert([
            'session_id' => 'query_test_789',
            'user_id' => null,
            'table_name' => 'orders',
            'active_tab' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Query using composite index
        $record = $capsule->table('table_tab_sessions')
            ->where('session_id', 'query_test_789')
            ->where('table_name', 'orders')
            ->first();

        $this->assertNotNull($record, 'Record should be found using composite index');
        $this->assertEquals('pending', $record->active_tab);
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
