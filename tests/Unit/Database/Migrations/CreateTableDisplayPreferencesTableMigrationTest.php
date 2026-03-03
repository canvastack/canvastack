<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database\Migrations;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for table_display_preferences table migration.
 */
class CreateTableDisplayPreferencesTableMigrationTest extends TestCase
{
    /**
     * Test that table_display_preferences table exists.
     */
    public function test_table_display_preferences_table_exists(): void
    {
        $capsule = Capsule::connection();
        
        $this->assertTrue(
            $capsule->getSchemaBuilder()->hasTable('table_display_preferences'),
            'table_display_preferences table should exist'
        );
    }

    /**
     * Test that table_display_preferences table has required columns.
     */
    public function test_table_display_preferences_table_has_required_columns(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        $columns = [
            'id',
            'session_id',
            'user_id',
            'table_name',
            'display_limit',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('table_display_preferences', $column),
                "table_display_preferences table should have {$column} column"
            );
        }
    }

    /**
     * Test that table_display_preferences table has correct indexes.
     */
    public function test_table_display_preferences_table_has_indexes(): void
    {
        $indexes = $this->getTableIndexes('table_display_preferences');

        // Check for individual column indexes
        $this->assertContains(
            'table_display_preferences_session_id_index',
            $indexes,
            'table_display_preferences table should have index on session_id'
        );

        $this->assertContains(
            'table_display_preferences_user_id_index',
            $indexes,
            'table_display_preferences table should have index on user_id'
        );

        $this->assertContains(
            'table_display_preferences_table_name_index',
            $indexes,
            'table_display_preferences table should have index on table_name'
        );

        // Check for composite index
        $this->assertContains(
            'idx_display_session_table',
            $indexes,
            'table_display_preferences table should have composite index on session_id and table_name'
        );
    }

    /**
     * Test that table_display_preferences table has default value for display_limit.
     */
    public function test_table_display_preferences_table_has_default_display_limit(): void
    {
        $capsule = Capsule::connection();
        
        // Insert without specifying display_limit
        $id = $capsule->table('table_display_preferences')->insertGetId([
            'session_id' => 'default_test_123',
            'user_id' => null,
            'table_name' => 'users',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_display_preferences')->find($id);
        
        $this->assertEquals('10', $record->display_limit, 'Default display_limit should be 10');
    }

    /**
     * Test that table_display_preferences table can store integer display limits.
     */
    public function test_table_display_preferences_table_can_store_integer_limits(): void
    {
        $capsule = Capsule::connection();
        
        $limits = ['10', '25', '50', '100'];
        
        foreach ($limits as $limit) {
            $id = $capsule->table('table_display_preferences')->insertGetId([
                'session_id' => "limit_test_{$limit}",
                'user_id' => null,
                'table_name' => 'products',
                'display_limit' => $limit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = $capsule->table('table_display_preferences')->find($id);
            
            $this->assertEquals($limit, $record->display_limit, "Should store display_limit of {$limit}");
        }
    }

    /**
     * Test that table_display_preferences table can store special 'all' value.
     */
    public function test_table_display_preferences_table_can_store_all_value(): void
    {
        $capsule = Capsule::connection();
        
        // Test 'all' value
        $id1 = $capsule->table('table_display_preferences')->insertGetId([
            'session_id' => 'all_test_456',
            'user_id' => null,
            'table_name' => 'orders',
            'display_limit' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record1 = $capsule->table('table_display_preferences')->find($id1);
        $this->assertEquals('all', $record1->display_limit, "Should store 'all' value");

        // Test '*' value
        $id2 = $capsule->table('table_display_preferences')->insertGetId([
            'session_id' => 'asterisk_test_789',
            'user_id' => null,
            'table_name' => 'invoices',
            'display_limit' => '*',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record2 = $capsule->table('table_display_preferences')->find($id2);
        $this->assertEquals('*', $record2->display_limit, "Should store '*' value");
    }

    /**
     * Test that table_display_preferences table supports null user_id.
     */
    public function test_table_display_preferences_table_supports_null_user_id(): void
    {
        $capsule = Capsule::connection();
        
        // Insert with null user_id (guest session)
        $id = $capsule->table('table_display_preferences')->insertGetId([
            'session_id' => 'guest_pref_999',
            'user_id' => null,
            'table_name' => 'reports',
            'display_limit' => '50',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = $capsule->table('table_display_preferences')->find($id);
        
        $this->assertNull($record->user_id, 'user_id should be null for guest sessions');
    }

    /**
     * Test that table_display_preferences table can be queried by composite index.
     */
    public function test_table_display_preferences_table_can_be_queried_by_composite_index(): void
    {
        $capsule = Capsule::connection();
        
        // Insert test data
        $capsule->table('table_display_preferences')->insert([
            'session_id' => 'query_pref_111',
            'user_id' => null,
            'table_name' => 'customers',
            'display_limit' => '25',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Query using composite index
        $record = $capsule->table('table_display_preferences')
            ->where('session_id', 'query_pref_111')
            ->where('table_name', 'customers')
            ->first();

        $this->assertNotNull($record, 'Record should be found using composite index');
        $this->assertEquals('25', $record->display_limit);
    }

    /**
     * Test that table_display_preferences table can update existing preferences.
     */
    public function test_table_display_preferences_table_can_update_preferences(): void
    {
        $capsule = Capsule::connection();
        
        // Insert initial preference
        $id = $capsule->table('table_display_preferences')->insertGetId([
            'session_id' => 'update_test_222',
            'user_id' => null,
            'table_name' => 'products',
            'display_limit' => '10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update preference
        $capsule->table('table_display_preferences')
            ->where('id', $id)
            ->update([
                'display_limit' => '100',
                'updated_at' => now(),
            ]);

        $record = $capsule->table('table_display_preferences')->find($id);
        
        $this->assertEquals('100', $record->display_limit, 'Display limit should be updated');
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
