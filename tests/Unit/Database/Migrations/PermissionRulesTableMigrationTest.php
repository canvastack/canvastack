<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database\Migrations;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for permission_rules table migration.
 */
class PermissionRulesTableMigrationTest extends TestCase
{
    /**
     * Test that permission_rules table exists.
     *
     * @return void
     */
    public function test_permission_rules_table_exists(): void
    {
        $capsule = Capsule::connection();

        $this->assertTrue(
            $capsule->getSchemaBuilder()->hasTable('permission_rules'),
            'permission_rules table should exist'
        );
    }

    /**
     * Test that permission_rules table has required columns.
     *
     * @return void
     */
    public function test_permission_rules_table_has_required_columns(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        $columns = [
            'id',
            'permission_id',
            'rule_type',
            'rule_config',
            'priority',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('permission_rules', $column),
                "permission_rules table should have {$column} column"
            );
        }
    }

    /**
     * Test that permission_rules table has correct column types.
     *
     * @return void
     */
    public function test_permission_rules_table_has_correct_column_types(): void
    {
        $capsule = Capsule::connection();
        $columns = $capsule->select('PRAGMA table_info(permission_rules)');

        $columnTypes = [];
        foreach ($columns as $column) {
            $columnTypes[$column->name] = strtolower($column->type);
        }

        // Verify column types
        $this->assertStringContainsString('integer', $columnTypes['id']);
        $this->assertStringContainsString('integer', $columnTypes['permission_id']);
        $this->assertStringContainsString('integer', $columnTypes['priority']);

        // JSON columns might be stored as TEXT in SQLite
        $this->assertTrue(
            str_contains($columnTypes['rule_config'], 'text') ||
            str_contains($columnTypes['rule_config'], 'json'),
            'rule_config should be JSON or TEXT type'
        );
    }

    /**
     * Test that permission_rules table has correct indexes.
     *
     * @return void
     */
    public function test_permission_rules_table_has_indexes(): void
    {
        $indexes = $this->getTableIndexes('permission_rules');

        // Check for composite index on permission_id and rule_type
        $this->assertContains(
            'idx_permission_rule_type',
            $indexes,
            'permission_rules table should have idx_permission_rule_type index'
        );

        // Check for priority index
        $this->assertContains(
            'idx_priority',
            $indexes,
            'permission_rules table should have idx_priority index'
        );
    }

    /**
     * Test that permission_rules table has foreign key constraint.
     *
     * @return void
     */
    public function test_permission_rules_table_has_foreign_key_constraint(): void
    {
        $capsule = Capsule::connection();

        // Enable foreign keys for SQLite
        $capsule->statement('PRAGMA foreign_keys = ON');

        $foreignKeys = $capsule->select('PRAGMA foreign_key_list(permission_rules)');

        $this->assertNotEmpty(
            $foreignKeys,
            'permission_rules table should have foreign key constraints'
        );

        // Find the foreign key for permission_id
        $permissionFk = null;
        foreach ($foreignKeys as $fk) {
            if ($fk->from === 'permission_id' && $fk->table === 'permissions') {
                $permissionFk = $fk;
                break;
            }
        }

        $this->assertNotNull(
            $permissionFk,
            'permission_rules table should have foreign key on permission_id referencing permissions table'
        );

        // Verify CASCADE delete
        $this->assertEquals(
            'CASCADE',
            strtoupper($permissionFk->on_delete),
            'Foreign key should have CASCADE on delete'
        );
    }

    /**
     * Test that foreign key constraint works (CASCADE delete).
     *
     * @return void
     */
    public function test_foreign_key_cascade_delete_works(): void
    {
        $capsule = Capsule::connection();

        // Enable foreign keys for SQLite
        $capsule->statement('PRAGMA foreign_keys = ON');

        // Create a test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.permission',
            'display_name' => 'Test Permission',
            'description' => 'Test permission for migration test',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a permission rule
        $ruleId = $capsule->table('permission_rules')->insertGetId([
            'permission_id' => $permissionId,
            'rule_type' => 'row',
            'rule_config' => json_encode(['test' => 'config']),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify rule was created
        $rule = $capsule->table('permission_rules')->find($ruleId);
        $this->assertNotNull($rule, 'Permission rule should be created');

        // Delete the permission
        $capsule->table('permissions')->where('id', $permissionId)->delete();

        // Verify rule was cascade deleted
        $rule = $capsule->table('permission_rules')->find($ruleId);
        $this->assertNull($rule, 'Permission rule should be cascade deleted when permission is deleted');
    }

    /**
     * Test that rule_type enum constraint works.
     *
     * @return void
     */
    public function test_rule_type_enum_constraint(): void
    {
        $capsule = Capsule::connection();

        // Create a test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.enum.permission',
            'display_name' => 'Test Enum Permission',
            'description' => 'Test permission for enum test',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test valid enum values
        $validTypes = ['row', 'column', 'json_attribute', 'conditional'];

        foreach ($validTypes as $type) {
            $ruleId = $capsule->table('permission_rules')->insertGetId([
                'permission_id' => $permissionId,
                'rule_type' => $type,
                'rule_config' => json_encode(['test' => 'config']),
                'priority' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull(
                $ruleId,
                "Should be able to insert rule with rule_type '{$type}'"
            );
        }

        // Clean up
        $capsule->table('permissions')->where('id', $permissionId)->delete();
    }

    /**
     * Test that priority default value works.
     *
     * @return void
     */
    public function test_priority_default_value(): void
    {
        $capsule = Capsule::connection();

        // Create a test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.priority.permission',
            'display_name' => 'Test Priority Permission',
            'description' => 'Test permission for priority test',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert rule without priority
        $ruleId = $capsule->table('permission_rules')->insertGetId([
            'permission_id' => $permissionId,
            'rule_type' => 'row',
            'rule_config' => json_encode(['test' => 'config']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify default priority is 0
        $rule = $capsule->table('permission_rules')->find($ruleId);
        $this->assertEquals(
            0,
            $rule->priority,
            'Priority should default to 0'
        );

        // Clean up
        $capsule->table('permissions')->where('id', $permissionId)->delete();
    }

    /**
     * Get table indexes.
     *
     * @param string $table
     * @return array
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
